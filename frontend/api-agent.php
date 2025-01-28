<?php
require_once(__DIR__.'/../loader.inc.php');


///// handle package download requests
if(empty($_SERVER['CONTENT_TYPE']) && !empty($_GET['id'])) {
	// get package
	$package = $db->selectPackage($_GET['id']);
	if($package === null || !$package->getFilePath()) {
		header('HTTP/1.1 404 Not Found'); die();
	}
	// check if agent key is correct
	$computer = $db->selectComputerByHostname($_GET['hostname']??'');
	if($computer == null || ($_GET['agent-key']??'') !== $computer->agent_key) {
		header('HTTP/1.1 401 Client Not Authorized'); die();
	}
	// allow download only if a job is active
	if(!$db->selectPendingAndActiveJobForAgentByComputerIdAndPackageId($computer->id, $package->id)) {
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
$db->insertLogEntry(Models\Log::LEVEL_DEBUG, null, null, Models\Log::ACTION_AGENT_API_RAW, $body);

// validate JSON-RPC
if($srcdata === null || !isset($srcdata['jsonrpc']) || $srcdata['jsonrpc'] != '2.0' || !isset($srcdata['method']) || !isset($srcdata['params']) || !isset($srcdata['id'])) {
	errorExit('400 Payload Corrupt', null, null, Models\Log::ACTION_AGENT_API, 'invalid JSON data');
}

// apply extension filters
foreach($ext->getAggregatedConf('agent-request-filter') as $filterMethod) {
	$srcdata = call_user_func($filterMethod, $srcdata, null/*computer object*/);
}

$resdata = ['id' => $srcdata['id']];
$params = $srcdata['params'];
switch($srcdata['method']) {
	case 'oco.agent.update_job_state':
		$data = $params['data'] ?? [];

		// check parameter
		if(!isset($params['hostname'])
			|| !isset($params['agent-key'])
			|| !isset($data['job-id'])
			|| !isset($data['state'])
			|| !array_key_exists('return-code', $data) // null is allowed here
			|| !isset($data['message'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE, 'invalid JSON data');
		}

		// check authorization
		$computer = $db->selectComputerByHostname($params['hostname']);
		if($computer === null) {
			errorExit('404 Computer Not Found', $params['hostname'], null, Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE, 'computer not found');
		}
		if($params['agent-key'] !== $computer->agent_key) {
			errorExit('401 Client Not Authorized', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE,
				'computer found but agent key mismatch: '.$params['agent-key']
			);
		}

		// get job details
		$state = intval($data['state']);
		$jobId = $data['job-id'] ?? -1;
		if(startsWith($jobId, Models\DynamicJob::PREFIX_DYNAMIC_ID)) {
			$job = $db->selectDynamicJob(str_replace(Models\DynamicJob::PREFIX_DYNAMIC_ID, '', $jobId));
		} else {
			$job = $db->selectStaticJob($jobId);
		}
		if($job === null) {
			errorExit('404 Job Not Found', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE,
				'job »'.$jobId.'« not found'
			);
		}
		if($job->computer_id !== $computer->id) {
			errorExit('403 Forbidden', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE,
				'computer not allowed to update job »'.$job->id.'«'
			);
		}

		// if job finished, we need to check the return code
		if($state === Models\Job::STATE_SUCCEEDED) {
			$successCodes = [];
			foreach(explode(',', $job->success_return_codes) as $successCode) {
				if(trim($successCode) === '') continue;
				$successCodes[] = intval(trim($successCode));
			}
			// check if return code is a success return code if any valid return code found
			if(count($successCodes) > 0) {
				$state = Models\Job::STATE_FAILED;
				foreach($successCodes as $successCode) {
					if(intval($data['return-code']) === intval($successCode)) {
						$state = Models\Job::STATE_SUCCEEDED;
						break;
					}
				}
			}
		}

		// update job execution state in database
		$job->state = $state;
		$job->return_code = ($data['return-code']===null) ? null : intval($data['return-code']);
		$job->message = $data['message'];
		if(isset($data['download-progress']) && is_numeric($data['download-progress'])) {
			$job->download_progress = $data['download-progress']; // new in >= 1.1.0
		}
		$db->updateComputerPing($computer->id);
		$db->updateJobExecutionState($job);
		$db->insertLogEntry(Models\Log::LEVEL_INFO, $params['hostname'], $computer->id, Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE,
			['job_id'=>$data['job-id'], 'return_code'=>intval($data['return-code']), 'message'=>$data['message']]
		);
		// update computer-package assignment if job was successful
		if($state === Models\Job::STATE_SUCCEEDED) {
			if($job->is_uninstall == 0) {
				if($job->upgrade_behavior ==  Models\Package::UPGRADE_BEHAVIOR_IMPLICIT_REMOVES_PREV_VERSION) {
					// the installer implicitly removed previous versions - remove all computer-package assignments of older versions
					$db->deleteComputerPackageByComputerIdAndPackageFamilyId($job->computer_id, $db->selectPackage($job->package_id)->package_family_id);
				}
				$db->insertComputerPackage($job->package_id, $job->computer_id, $job->getSystemUserId(), $job->getDomainUserId(), $job->procedure);
			} elseif($job->is_uninstall == 1) {
				$db->deleteComputerPackageByComputerIdAndPackageId($job->computer_id, $job->package_id);
			}
		}
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true,
			'params' => [
				'server-key' => $computer->server_key,
				'job-succeeded' => ($state === Models\Job::STATE_SUCCEEDED),
			]
		];
		break;

	case 'oco.agent.hello':
		// check parameter
		if(!isset($params['hostname']) || !isset($params['agent-key'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_HELLO,
				'invalid JSON data'
			);
		}

		$data = $params['data'] ?? [];

		$computer = $db->selectComputerByHostname($params['hostname']);
		$jobs = []; $update = 0; $server_key = null; $agent_key = null; $success = false;

		if($computer == null) {
			if($params['agent-key'] !== $db->settings->get('agent-registration-key')) {
				errorExit('401 Client Not Authorized', $params['hostname'], null, Models\Log::ACTION_AGENT_API_HELLO,
					'computer not found and agent registration key mismatch: '.$params['agent-key']
				);
			}

			if($db->settings->get('agent-self-registration-enabled')) {
				$server_key = randomString();
				$agent_key = randomString();
				$update = 1;
				if($insert_id = $db->insertComputer(
					$params['hostname'],
					$data['agent_version'] ?? '?',
					$data['networks'] ?? [],
					'self_registration',
					$agent_key,
					$server_key,
					null /*created_by_system_user_id*/
				)) {
					$success = true;
					$computer = $db->selectComputer($insert_id);
				}
			} else {
				errorExit('403 Client Self-Registration Disabled', $params['hostname'], null, Models\Log::ACTION_AGENT_API_HELLO,
					'computer not found and agent self-registration disabled'
				);
			}
		} else {
			if(empty($computer->agent_key)) {
				// computer was pre-registered in the web frontend: check global key and generate individual key
				if($params['agent-key'] !== $db->settings->get('agent-registration-key')) {
					errorExit('401 Client Not Authorized', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_HELLO,
						'computer is pre-registered but agent registration key mismatch: '.$params['agent-key']
					);
				} else {
					$agent_key = randomString();
					$db->updateComputerAgentKey($computer->id, $agent_key);
				}
			} else {
				// check individual agent key
				if($params['agent-key'] !== $computer->agent_key) {
					errorExit('401 Client Not Authorized', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_HELLO,
						'computer found but agent key mismatch: '.$params['agent-key']
					);
				}
			}

			if(empty($computer->server_key)) {
				// generate a server key if empty
				$server_key = randomString();
				$db->updateComputerServerKey($computer->id, $server_key);
			} else {
				// get the current server key for the agent
				$server_key = $computer->server_key;
			}

			// update common computer metadata and service status
			$db->updateComputerPing($computer->id, $data['agent_version']??'?', $data['networks']??[], $data['uptime']??null, $_SERVER['REMOTE_ADDR']??null);
			if(!empty($data['services'])) foreach($data['services'] as $s) {
				if(empty($s['name']) || !isset($s['status']) || !is_numeric($s['status'])) continue;
				$db->insertOrUpdateComputerService($computer->id, $s['status'], $s['name'], $s['metrics'] ?? '-', $s['details'] ?? '');
			}

			// check if agent should update inventory data
			if(time() - strtotime($computer->last_update??'') > intval($db->settings->get('agent-update-interval'))
			|| !empty($computer->force_update)) {
				$update = 1;
			}

			// get pending jobs
			foreach($db->selectAllPendingAndActiveJobForAgentByComputerId($computer->id) as $pj) {
				// IP constraint check
				if(!empty($pj->job_container_agent_ip_ranges)) {
					$ignore = true;
					foreach(explode(',', $pj->job_container_agent_ip_ranges) as $rangex) {
						$range = trim($rangex);
						if(startsWith($range, '!')) {
							if(isIpInRange($_SERVER['REMOTE_ADDR'], ltrim($range, '!'))) {
								// agent IP is in illegal range - ignore this job
								continue 2;
							}
						} else {
							if(isIpInRange($_SERVER['REMOTE_ADDR'], $range)) {
								// agent IP is in desired range - abort check and send job to agent
								$ignore = false;
								break;
							}
						}
					}
					// continue if agent is not in one of the desired IP ranges
					if($ignore) continue;
				}
				// time frame constraint check
				if(!empty($pj->job_container_time_frames)) {
					$ignore = true;
					foreach(explode(',', $pj->job_container_time_frames) as $rangex) {
						$range = trim($rangex);
						if(startsWith($range, '!')) {
							if(isTimeInRange(ltrim($range, '!'))) {
								// current time is in illegal range - ignore this job
								continue 2;
							}
						} else {
							if(isTimeInRange($range)) {
								// current time is in desired range - abort check and send job to agent
								$ignore = false;
								break;
							}
						}
					}
					// continue if agent is not in one of the desired IP ranges
					if($ignore) continue;
				}
				// set post action
				$restart = null; $shutdown = null; $exit = null;
				if($pj->post_action == Models\Package::POST_ACTION_RESTART)
					$restart = intval($pj->post_action_timeout ?? 1);
				if($pj->post_action == Models\Package::POST_ACTION_SHUTDOWN)
					$shutdown = intval($pj->post_action_timeout ?? 1);
				if($pj->post_action == Models\Package::POST_ACTION_EXIT)
					$exit = intval($pj->post_action_timeout ?? 1);
				// add job to list
				$jobs[] = [
					'id' => $pj->getIdForAgent(),
					'container-id' => $pj->getContainerId(),
					'package-id' => $pj->package_id,
					'download' => $pj->download==0 ? False : True,
					'procedure' => $pj->procedure,
					'sequence-mode' => intval($pj->getSequenceMode()),
					'restart' => $restart,
					'shutdown' => $shutdown,
					'exit' => $exit,
				];
			}

			$db->insertLogEntry(Models\Log::LEVEL_DEBUG, $params['hostname'], $computer->id, Models\Log::ACTION_AGENT_API_HELLO,
				['update'=>$update, 'software_jobs'=>$jobs]
			);
			$success = true;
		}

		// get event query rules
		$events = [];
		foreach($db->selectAllEventQueryRule() as $rule) {
			// get and convert timestamp to utc
			$timestamp = date('Y-m-d H:i:s', 0);
			try {
				$tmpEvent = $db->selectLastComputerEventByComputerId($computer->id);
				if(!$tmpEvent) throw new Exception();
				$timestamp = localTimeToUtc($tmpEvent->timestamp);
			} catch(Exception $e) {}
			$events[] = [
				'since' => $timestamp, 'log' => $rule->log, 'query' => $rule->query
			];
		}

		// get the last login date
		$loginsSince = date('Y-m-d H:i:s', 0);
		try {
			$tmpLogon = $db->selectLastDomainUserLogonByComputerId($computer->id);
			if(!$tmpLogon) throw new Exception();
			$loginsSince = localTimeToUtc($tmpLogon->timestamp);
		} catch(Exception $e) {}

		// get password update rules
		$passwords = [];
		foreach($db->selectAllPasswordRotationRuleByComputerId($computer->id) as $rule) {
			$lastUpdateTime = 0;
			foreach($db->selectAllComputerPasswordByComputerId($computer->id) as $password) {
				if($password->username === $rule->username) {
					$lastUpdateTime = strtotime($password->created);
					break;
				}
			}
			if(time() - $lastUpdateTime > $rule->valid_seconds) {
				$passwords[] = ['username'=>$rule->username, 'alphabet'=>$rule->alphabet, 'length'=>$rule->length];
			}
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => $success,
			'params' => [ // tell the agent...
				'server-key' => $server_key,     // ...our server key, so it can validate the server
				'agent-key' => $agent_key,       // ...that it should save a new agent key for further requests
				'update' => $update,             // ...that it should update the inventory data
				'logins-since' => $loginsSince,  // ...to only send logins since the last login date
				'software-jobs' => $jobs,        // ...all pending software jobs
				'events' => $events,             // ...to send specific events from local log files
				'update-passwords' => $passwords // ...to update local admin passwords
			]
		];

		break;

	case 'oco.agent.events':
		// check parameter
		if(!isset($params['hostname']) || !isset($params['agent-key']) || empty($params['data'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_UPDATE,
				'invalid JSON data'
			);
		}

		$data = $params['data'];
		if(!isset($data['events']) || !is_array($data['events'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_UPDATE,
				'invalid JSON data'
			);
		}

		// check authorization
		$computer = $db->selectComputerByHostname($params['hostname']);
		if($computer === null) {
			errorExit('404 Computer Not Found', $params['hostname'], null, Models\Log::ACTION_AGENT_API_UPDATE,
				'computer not found'
			);
		}
		if($params['agent-key'] !== $computer->agent_key) {
			errorExit('401 Client Not Authorized', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE,
				'computer found but agent key mismatch: '.$params['agent-key']
			);
		}

		// execute insert
		$success = true;
		$db->updateComputerPing($computer->id);
		foreach($data['events'] as $event) {
			if(empty($event['log'])) continue;

			// convert timestamp to local time
			$timestamp = null;
			try {
				if(empty($event['timestamp'])) continue;
				$timestamp = utcTimeToLocal($event['timestamp']);
			} catch(Exception $e) {
				continue;
			}

			$success = $db->insertOrUpdateComputerEvent(
				$computer->id,
				$event['log'],
				$timestamp,
				$event['provider'] ?? '',
				$event['level'] ?? -1,
				$event['event_id'] ?? -1,
				json_encode($event['data'])
			);
			if(!$success) throw new Exception('Error while inserting event into database');
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => $success,
			'params' => [
				'server-key' => $computer->server_key,
			]
		];

		break;

	case 'oco.agent.passwords':
		// check parameter
		if(!isset($params['hostname']) || !isset($params['agent-key']) || empty($params['data'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_UPDATE,
				'invalid JSON data'
			);
		}

		$data = $params['data'];
		if(!isset($data['passwords']) || !is_array($data['passwords'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_UPDATE,
				'invalid JSON data'
			);
		}

		// check authorization
		$computer = $db->selectComputerByHostname($params['hostname']);
		if($computer === null) {
			errorExit('404 Computer Not Found', $params['hostname'], null, Models\Log::ACTION_AGENT_API_UPDATE,
				'computer not found'
			);
		}
		if($params['agent-key'] !== $computer->agent_key) {
			errorExit('401 Client Not Authorized', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE,
				'computer found but agent key mismatch: '.$params['agent-key']
			);
		}

		$success = false;
		foreach($data['passwords'] as $password) {
			if(empty($password['username']) || empty($password['password'])) continue;
			if(!empty($password['revoke'])) {
				foreach($db->selectAllComputerPasswordByComputerId($computer->id) as $p) {
					if($p->username === $password['username']
					&& $p->password === $password['password']
					// revoking is only allowed in the first 5 minutes
					&& time() - strtotime($p->created) < 60*5) {
						$db->deleteComputerPassword($p->id);
					}
				}
				continue;
			}
			foreach($db->selectAllPasswordRotationRuleByComputerId($computer->id) as $rule) {
				if($rule->username === $password['username']) {
					$success = $db->insertComputerPassword(
						$computer->id,
						$password['username'],
						$password['password'],
						$rule->history
					);
					if(!$success) throw new Exception('Error while inserting password into database');
					break;
				}
			}
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => $success,
			'params' => [
				'server-key' => $computer->server_key,
			]
		];

		break;

	case 'oco.agent.update':
		// check parameter
		if(!isset($params['hostname']) || !isset($params['agent-key']) || empty($params['data'])) {
			errorExit('400 Parameter Mismatch', null, null, Models\Log::ACTION_AGENT_API_UPDATE,
				'invalid JSON data'
			);
		}

		$data = $params['data'];

		// check authorization
		$computer = $db->selectComputerByHostname($params['hostname']);
		if($computer === null) {
			errorExit('404 Computer Not Found', $params['hostname'], null, Models\Log::ACTION_AGENT_API_UPDATE,
				'computer not found'
			);
		}
		if($params['agent-key'] !== $computer->agent_key) {
			errorExit('401 Client Not Authorized', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE,
				'computer found but agent key mismatch: '.$params['agent-key']
			);
		}

		// execute update
		$success = false;
		$db->updateComputerPing($computer->id);
		if((time() - strtotime($computer->last_update??'') > intval($db->settings->get('agent-update-interval')) || !empty($computer->force_update))
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
					$logins[$key]['timestamp'] = utcTimeToLocal($login['timestamp']);
				} catch(Exception $e) {}
			}
			// update inventory data now
			$success = $db->updateComputerInventoryValues(
				$computer->id,
				$params['uid'] ?? null,
				$params['hostname'],
				$data['os'] ?? '',
				$data['os_version'] ?? '',
				$data['os_license'] ?? '-',
				$data['os_language'] ?? '-',
				$data['kernel_version'] ?? '',
				$data['architecture'] ?? '',
				$data['cpu'] ?? '',
				$data['gpu'] ?? '',
				$data['ram'] ?? '',
				$data['agent_version'] ?? '?',
				$_SERVER['REMOTE_ADDR'],
				$data['serial'] ?? '',
				$data['manufacturer'] ?? '',
				$data['model'] ?? '',
				$data['bios_version'] ?? '',
				intval($data['uptime'] ?? 0),
				$data['boot_type'] ?? '',
				$data['secure_boot'] ?? '',
				$data['domain'] ?? '',
				$data['networks'] ?? [],
				$data['screens'] ?? [],
				$data['printers'] ?? [],
				$data['partitions'] ?? [],
				$data['software'] ?? [],
				$logins
			);
			$db->insertLogEntry(Models\Log::LEVEL_INFO, $params['hostname'], $computer->id, Models\Log::ACTION_AGENT_API_UPDATE, [
				'hostname' => $params['hostname'],
				'os' => $data['os'] ?? '',
				'os_version' => $data['os_version'] ?? '',
				'os_license' => $data['os_license'] ?? '-',
				'os_language' => $data['os_language'] ?? '-',
				'kernel_version' => $data['kernel_version'] ?? '',
				'architecture' => $data['architecture'] ?? '',
				'cpu' => $data['cpu'] ?? '',
				'gpu' => $data['gpu'] ?? '',
				'ram' => $data['ram'] ?? '',
				'agent_version' => $data['agent_version'] ?? '?',
				'serial' => $data['serial'] ?? '',
				'manufacturer' => $data['manufacturer'] ?? '',
				'model' => $data['model'] ?? '',
				'bios_version' => $data['bios_version'] ?? '',
				'uptime' => intval($data['uptime'] ?? 0),
				'boot_type' => $data['boot_type'] ?? '',
				'secure_boot' => $data['secure_boot'] ?? '',
				'domain' => $data['domain'] ?? '',
				'network' => $data['networks'] ?? [],
				'screens' => $data['screens'] ?? [],
				'printers' => $data['printers'] ?? [],
				'partitions' => $data['partitions'] ?? [],
				'software' => $data['software'] ?? [],
				'logins' => $logins
			]);
		} else {
			errorExit('400 Update Not Necessary', $params['hostname'], $computer, Models\Log::ACTION_AGENT_API_UPDATE,
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
		errorExit('400 Unknown Method', null, null, Models\Log::ACTION_AGENT_API,
			'unknown method'
		);
}

// apply extension filters
foreach($ext->getAggregatedConf('agent-response-filter') as $filterMethod) {
	$resdata = call_user_func($filterMethod, $resdata, empty($computer)?null:$computer);
}

// return response
header('Content-Type: application/json');
echo json_encode($resdata);

}

else {
	errorExit('400 Content Type Mismatch', null, null, Models\Log::ACTION_AGENT_API, 'invalid content type: '.($_SERVER['CONTENT_TYPE']??''));
}


function errorExit($httpStatus, $hostname, $computer, $action, $message) {
	global $db;

	// log into webserver log for fail2ban
	if(startsWith($httpStatus, '401')) {
		error_log('api-agent: authentication failure');
	}

	// log into database
	$db->insertLogEntry(Models\Log::LEVEL_WARNING, $hostname, $computer ? $computer->id : null, $action, json_encode(['error'=>$message]));

	// exit with error code
	header('HTTP/1.1 '.$httpStatus);
	die();
}
