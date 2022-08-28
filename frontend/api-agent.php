<?php
require_once('../loader.inc.php');


///// handle package download requests
if(empty($_SERVER['CONTENT_TYPE']) && !empty($_GET['id'])) {
	// get package
	$package = $db->getPackage($_GET['id']);
	if($package === null || !$package->getFilePath()) {
		header('HTTP/1.1 404 Not Found'); die();
	}
	// check if agent key is correct
	$computer = $db->getComputerByName($_GET['hostname']??'');
	if($computer == null || ($_GET['agent-key']??'') !== $computer->agent_key) {
		header('HTTP/1.1 401 Client Not Authorized'); die();
	}
	// allow download only if a job is active
	if(!$db->getActiveJobByComputerPackage($computer->id, $package->id)) {
		header('HTTP/1.1 401 No Active Job'); die();
	}
	// start download
	try {
		$package->download();
	} catch(Exception $e) {
		header('HTTP/1.1 500 Internal Server Error'); die();
	}
}


///// handle agent api metadata requests
elseif(!empty($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {

// get & log body
$body = file_get_contents('php://input');
$srcdata = json_decode($body, true);
$db->addLogEntry(Models\Log::LEVEL_DEBUG, null, null, Models\Log::ACTION_CLIENT_API_RAW, $body);

// validate JSON-RPC
if($srcdata === null || !isset($srcdata['jsonrpc']) || $srcdata['jsonrpc'] != '2.0' || !isset($srcdata['method']) || !isset($srcdata['params']) || !isset($srcdata['id'])) {
	errorExit('400 Payload Corrupt', null, null, 'oco.agent', 'invalid JSON data');
}

$resdata = ['id' => $srcdata['id']];
$params = $srcdata['params'];
switch($srcdata['method']) {
	case 'oco.agent.update_deploy_status':
	case 'oco.update_deploy_status':
		$data = $params['data'];

		// check parameter
		if(!isset($params['hostname'])
			|| !isset($params['agent-key'])
			|| !isset($data['job-id'])
			|| !isset($data['state'])
			|| !isset($data['return-code'])
			|| !isset($data['message'])) {
			errorExit('400 Parameter Mismatch', null, null, $srcdata['method'], 'invalid JSON data');
		}

		// check authorization
		$computer = $db->getComputerByName($params['hostname']);
		if($computer === null) {
			errorExit('404 Computer Not Found', $params['hostname'], null, $srcdata['method'], 'computer not found');
		}
		if($params['agent-key'] !== $computer->agent_key) {
			errorExit('401 Client Not Authorized', $params['hostname'], $computer, $srcdata['method'],
				'computer found but agent key mismatch: '.$params['agent-key']
			);
		}

		// get job details
		$state = intval($data['state']);
		$job = $db->getJob($data['job-id']);
		if($job === null) {
			errorExit('404 Job Not Found', $params['hostname'], $computer, $srcdata['method'],
				'job »'.$params['job-id'].'« not found'
			);
		}
		if($job->computer_id !== $computer->id) {
			errorExit('403 Forbidden', $params['hostname'], $computer, $srcdata['method'],
				'computer not allowed to update job »'.$job->id.'«'
			);
		}

		// if job finished, we need to check the return code
		if($state === Models\Job::STATUS_SUCCEEDED) {
			$successCodes = [];
			foreach(explode(',', $job->success_return_codes) as $successCode) {
				if(trim($successCode) === '') continue;
				$successCodes[] = intval(trim($successCode));
			}
			// check if return code is a success return code if any valid return code found
			if(count($successCodes) > 0) {
				$state = Models\Job::STATUS_FAILED;
				foreach($successCodes as $successCode) {
					if(intval($data['return-code']) === intval($successCode)) {
						$state = Models\Job::STATUS_SUCCEEDED;
						break;
					}
				}
			}
		}

		// update job state in database
		$db->updateComputerPing($computer->id);
		$db->updateJobState($data['job-id'], $state, intval($data['return-code']), $data['message']);
		$db->addLogEntry(Models\Log::LEVEL_INFO, $params['hostname'], $computer->id, $srcdata['method'],
			['job_id'=>$data['job-id'], 'return_code'=>intval($data['return-code']), 'message'=>$data['message']]
		);
		// update computer-package assignment if job was successful
		if($state === Models\Job::STATUS_SUCCEEDED) {
			if($job->is_uninstall == 0) {
				$db->addPackageToComputer($job->package_id, $job->computer_id, $job->job_container_author, $job->package_procedure);
			} elseif($job->is_uninstall == 1) {
				$db->removeComputerAssignedPackageByIds($job->computer_id, $job->package_id);
			}
		}
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true,
			'params' => [
				'server-key' => $computer->server_key,
				'job-succeeded' => ($state === Models\Job::STATUS_SUCCEEDED),
			]
		];
		break;

	case 'oco.agent.hello':
	case 'oco.agent_hello':
		$data = $params['data'];

		// check parameter
		if(!isset($params['hostname']) || !isset($params['agent-key'])) {
			errorExit('400 Parameter Mismatch', null, null, $srcdata['method'],
				'invalid JSON data'
			);
		}

		$computer = $db->getComputerByName($params['hostname']);
		$jobs = []; $update = 0; $server_key = null; $agent_key = null; $success = false;

		if($computer == null) {
			if($params['agent-key'] !== AGENT_REGISTRATION_KEY) {
				errorExit('401 Client Not Authorized', $params['hostname'], null, $srcdata['method'],
					'computer not found and agent registration key mismatch: '.$params['agent-key']
				);
			}

			if(AGENT_SELF_REGISTRATION_ENABLED) {
				$server_key = randomString();
				$agent_key = randomString();
				$update = 1;
				if($db->addComputer(
					$params['hostname'],
					$data['agent_version'],
					$data['networks'],
					LANG('self_registration').' '.date('Y-m-d H:i:s'),
					$agent_key,
					$server_key
				)) {
					$success = true;
				}
			} else {
				errorExit('403 Client Self-Registration Disabled', $params['hostname'], null, $srcdata['method'],
					'computer not found and agent self-registration disabled'
				);
			}
		} else {
			if(empty($computer->agent_key)) {
				// computer was pre-registered in the web frontend: check global key and generate individual key
				if($params['agent-key'] !== AGENT_REGISTRATION_KEY) {
					errorExit('401 Client Not Authorized', $params['hostname'], $computer, $srcdata['method'],
						'computer is pre-registered but agent registration key mismatch: '.$params['agent-key']
					);
				} else {
					$agent_key = randomString();
					$db->updateComputerAgentkey($computer->id, $agent_key);
				}
			} else {
				// check individual agent key
				if($params['agent-key'] !== $computer->agent_key) {
					errorExit('401 Client Not Authorized', $params['hostname'], $computer, $srcdata['method'],
						'computer found but agent key mismatch: '.$params['agent-key']
					);
				}
			}

			if(empty($computer->server_key)) {
				// generate a server key if empty
				$server_key = randomString();
				$db->updateComputerServerkey($computer->id, $server_key);
			} else {
				// get the current server key for the agent
				$server_key = $computer->server_key;
			}

			// update last seen date
			$db->updateComputerPing($computer->id);

			// check if agent should update inventory data
			if(time() - strtotime($computer->last_update) > AGENT_UPDATE_INTERVAL
			|| !empty($computer->force_update)) {
				$update = 1;
			}

			// get pending jobs
			foreach($db->getPendingJobsForComputer($computer->id) as $pj) {
				// constraint check
				if(!empty($pj['agent_ip_ranges'])) {
					$continue = true;
					foreach(explode(',', $pj['agent_ip_ranges']) as $range) {
						if(startsWith($range, '!')) {
							if(isIpInRange($_SERVER['REMOTE_ADDR'], ltrim($range, '!'))) {
								// agent IP is in that range but should not be - ignore this job
								continue 2;
							}
						} else {
							if(isIpInRange($_SERVER['REMOTE_ADDR'], $range)) {
								// agent IP is in desired range - abort check and send job to agent
								$continue = false;
								break;
							}
						}
					}
					// continue if agent is not in one of the desired IP ranges
					if($continue) continue;
				}
				// set post action
				$restart = null; $shutdown = null; $exit = null;
				if($pj['post_action'] == Models\Package::POST_ACTION_RESTART)
					$restart = intval($pj['post_action_timeout'] ?? 1);
				if($pj['post_action'] == Models\Package::POST_ACTION_SHUTDOWN)
					$shutdown = intval($pj['post_action_timeout'] ?? 1);
				if($pj['post_action'] == Models\Package::POST_ACTION_EXIT)
					$exit = intval($pj['post_action_timeout'] ?? 1);
				// add job to list
				$jobs[] = [
					'id' => $pj['id'],
					'container-id' => $pj['job_container_id'],
					'package-id' => $pj['package_id'],
					'download' => $pj['download']==0 ? False : True,
					'procedure' => $pj['procedure'],
					'sequence-mode' => intval($pj['sequence_mode']),
					'restart' => $restart,
					'shutdown' => $shutdown,
					'exit' => $exit,
				];
			}

			$db->addLogEntry(Models\Log::LEVEL_DEBUG, $params['hostname'], $computer->id, $srcdata['method'],
				['update'=>$update, 'software_jobs'=>$jobs]
			);
			$success = true;
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => $success,
			'params' => [
				'server-key' => $server_key, // tell the agent our server key, so it can validate the server
				'agent-key' => $agent_key,   // tell the agent that it should save a new agent key for further requests
				'update' => $update,         // tell the agent that it should update the inventory data
				'software-jobs' => $jobs,    // tell the agent all pending software jobs
			]
		];

		break;

	case 'oco.agent.update':
	case 'oco.agent_update':
		$data = $params['data'];

		// check parameter
		if(!isset($params['hostname']) || !isset($params['agent-key'])) {
			errorExit('400 Parameter Mismatch', null, null, $srcdata['method'],
				'invalid JSON data'
			);
		}

		// check authorization
		$computer = $db->getComputerByName($params['hostname']);
		if($computer === null) {
			errorExit('404 Computer Not Found', $params['hostname'], null, $srcdata['method'],
				'computer not found'
			);
		}
		if($params['agent-key'] !== $computer->agent_key) {
			errorExit('401 Client Not Authorized', $params['hostname'], $computer, $srcdata['method'],
				'computer found but agent key mismatch: '.$params['agent-key']
			);
		}

		// execute update
		$success = false;
		$db->updateComputerPing($computer->id);
		if((time() - strtotime($computer->last_update) > AGENT_UPDATE_INTERVAL || !empty($computer->force_update))
		&& !empty($data)) {
			// convert login timestamps to local time,
			// because other timestamps in the database are also in local time
			$logins = [];
			if(!empty($data['logins'])) {
				$logins = $data['logins'];
			}
			foreach($logins as $key => $login) {
				try {
					if(empty($login['timestamp'])) continue;
					$date = new DateTime($login['timestamp'], new DateTimeZone('UTC'));
					$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
					$logins[$key]['timestamp'] = $date->format('Y-m-d H:i:s');
				} catch(Exception $e) {}
			}
			// update inventory data now
			$success = $db->updateComputerInventoryValues(
				$computer->id,
				$params['hostname'],
				$data['os'],
				$data['os_version'],
				$data['os_license'] ?? '-',
				$data['os_language'] ?? '-',
				$data['kernel_version'],
				$data['architecture'],
				$data['cpu'],
				$data['gpu'],
				$data['ram'],
				$data['agent_version'],
				$_SERVER['REMOTE_ADDR'],
				$data['serial'],
				$data['manufacturer'],
				$data['model'],
				$data['bios_version'],
				intval($data['uptime'] ?? 0),
				$data['boot_type'],
				$data['secure_boot'],
				$data['domain'] ?? '',
				$data['networks'] ?? [],
				$data['screens'] ?? [],
				$data['printers'] ?? [],
				$data['partitions'] ?? [],
				$data['software'] ?? [],
				$logins
			);
			$db->addLogEntry(Models\Log::LEVEL_INFO, $params['hostname'], $computer->id, $srcdata['method'],
				['updated'=>true]
			);
		} else {
			errorExit('400 Update Not Necessary', $params['hostname'], $computer, $srcdata['method'],
				'computer should not update now'
			);
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => $success,
			'params' => [
				'server-key' => $computer->server_key,
			]
		];

		break;

	default:
		errorExit('400 Unknown Method', null, null, $srcdata['method'],
			'unknown method'
		);
}

// return response
header('Content-Type: application/json');
echo json_encode($resdata);

}

else {
	errorExit('400 Content Type Mismatch', null, null, 'oco.agent', 'invalid content type: '.($_SERVER['CONTENT_TYPE']??''));
}


function errorExit($httpCode, $hostname, $computer, $action, $message) {
	global $db;

	// log into webserver log for fail2ban
	error_log('api-agent: authentication failure');

	// log into database
	$db->addLogEntry(Models\Log::LEVEL_WARNING, $hostname, $computer ? $computer->id : null, $action, json_encode(['error'=>$message]));

	// exit with error code
	header('HTTP/1.1 '.$httpCode);
	die();
}
