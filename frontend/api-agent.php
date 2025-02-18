<?php
require_once(__DIR__.'/../loader.inc.php');


try {

///// handle package download requests
# TODO: remove old download method in future version
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
		throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API);
	}

	// apply extension filters
	foreach($ext->getAggregatedConf('agent-request-filter') as $filterMethod) {
		$srcdata = call_user_func($filterMethod, $srcdata, null/*computer object*/);
	}

	// handle requested method
	$resdata = ['id' => $srcdata['id']];
	$params = $srcdata['params'];
	switch($srcdata['method']) {
		case 'oco.agent.hello':
			// check parameter
			if(!isset($params['hostname'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_HELLO);
			}

			$data = $params['data'] ?? [];

			$computer = $db->selectComputerByHostname($params['hostname']);
			$jobs = []; $update = 0; $success = false;
			$updateServerKey = null;
			$updateAgentKey = null;

			if($computer === null) {
				// create new computer if self-registration is enabled and agent key matches
				if($db->settings->get('agent-self-registration-enabled')) {
					$registrationKey = $db->settings->get('agent-registration-key');
					if(version_compare($data['agent_version']??'1.1.7', '1.1.7', '<')) {
						// TODO: remove insecure plaintext auth
						if(!isset($params['agent-key']) || $params['agent-key'] !== $registrationKey) {
							throw new PermissionException('Agent registration key mismatch', Models\Log::ACTION_AGENT_API_HELLO, $params['hostname']);
						}
					} else {
						$headers = getallheaders();
						if(!isset($headers['x-oco-agent-signature']))
							throw new PermissionException('Signature header missing', Models\Log::ACTION_AGENT_API_HELLO, $params['hostname']);
						$computedSignature = hash_hmac('sha256', file_get_contents('php://input'), $registrationKey);
						if($headers['x-oco-agent-signature'] !== $computedSignature)
							throw new PermissionException('Invalid agent signature', Models\Log::ACTION_AGENT_API_HELLO, $params['hostname']);
					}

					$updateServerKey = randomString();
					$updateAgentKey = randomString();
					$update = 1;
					if($insert_id = $db->insertComputer(
						$params['hostname'],
						$data['agent_version'] ?? '?',
						$data['networks'] ?? [],
						'self_registration',
						$updateAgentKey,
						$updateServerKey,
						null /*created_by_system_user_id*/
					)) {
						$success = true;
						$computer = $db->selectComputer($insert_id);
					}
				} else {
					throw new PermissionException('Client self-registration is disabled', Models\Log::ACTION_AGENT_API_HELLO, $params['hostname']);
				}
			} else {
				checkAuth($params, empty($computer->agent_key) ? $db->settings->get('agent-registration-key') : $computer->agent_key);

				if(version_compare($computer->agent_version, '1.1.7', '>=')) {
					# TODO: remove agent version check
					// check the timestamp of the client - it must be greater than the last_ping value
					// using a different timestamp for every request is a security feature
					// without this changing timestamp value in the request, the signature of the agent_hello is always the same
					// which may be used by an attacker who captured one agent request
					$agentTimestamp = $params['timestamp'] ?? 0;
					if(!is_numeric($agentTimestamp) || strtotime($computer->last_ping) >= floatval($agentTimestamp))
						throw new PermissionException('Agent timestamp too old', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
				}

				if(empty($computer->agent_key)) {
					// computer was pre-registered in the web frontend: generate individual key
					$updateAgentKey = randomString();
					$db->updateComputerAgentKey($computer->id, $updateAgentKey);
					$computer = $db->selectComputer($computer->id);
				}
				if(empty($computer->server_key)) {
					// generate a server key if empty
					$updateServerKey = randomString();
					$db->updateComputerServerKey($computer->id, $updateServerKey);
					$computer = $db->selectComputer($computer->id);
				}

				// update common computer metadata and service status
				$db->updateComputerPing($computer->id, $data['agent_version']??'?', $data['networks']??[], $data['battery_level']??null, $data['battery_status']??null, $data['uptime']??null, $_SERVER['REMOTE_ADDR']??null);
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
					$passwordUpdateRequest = [
						'username'=>$rule->username, 'alphabet'=>$rule->alphabet, 'length'=>$rule->length
					];
					if($computer->os === 'macOS') {
						$passwordUpdateRequest['old_password'] = $rule->default_password;
						foreach($db->selectAllComputerPasswordByComputerId($computer->id) as $p) {
							if($p->username === $rule->username) {
								$passwordUpdateRequest['old_password'] = $p->password;
								break;
							}
						}
					}
					$passwords[] = $passwordUpdateRequest;
				}
			}

			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => $success,
				'params' => [ // tell the agent...
					'server-key' => $updateServerKey,// ...our server key, so it can validate the server
					'agent-key' => $updateAgentKey,  // ...that it should save a new agent key for further requests
					'update' => $update,             // ...that it should update the inventory data
					'logins-since' => $loginsSince,  // ...to only send logins since the last login date
					'software-jobs' => $jobs,        // ...all pending software jobs
					'events' => $events,             // ...to send specific events from local log files
					'update-passwords' => $passwords // ...to update local admin passwords
				]
			];
			break;

		case 'oco.agent.update':
			// check parameter
			if(!isset($params['hostname']) || empty($params['data'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_UPDATE);
			}
			$computer = checkAuth($params);
			$data = $params['data'];
		
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
					$data['battery_level'] ?? null,
					$data['battery_status'] ?? null,
					intval($data['uptime'] ?? 0),
					$data['boot_type'] ?? '',
					$data['secure_boot'] ?? '',
					$data['domain'] ?? '',
					$data['networks'] ?? [],
					$data['screens'] ?? [],
					$data['printers'] ?? [],
					$data['partitions'] ?? [],
					$data['software'] ?? [],
					$logins,
					$data['devices'] ?? [],
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
					'battery_level' => $data['battery_level']??null,
					'battery_status' => $data['battery_status']??null,
					'uptime' => intval($data['uptime'] ?? 0),
					'boot_type' => $data['boot_type'] ?? '',
					'secure_boot' => $data['secure_boot'] ?? '',
					'domain' => $data['domain'] ?? '',
					'network' => $data['networks'] ?? [],
					'screens' => $data['screens'] ?? [],
					'printers' => $data['printers'] ?? [],
					'partitions' => $data['partitions'] ?? [],
					'software' => $data['software'] ?? [],
					'logins' => $logins,
					'devices' => $data['devices'] ?? [],
				]);
			} else {
				throw new InvalidRequestException('Update not necessary', Models\Log::ACTION_AGENT_API_UPDATE, $params['hostname'], $computer->id);
			}
		
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => $success,
				'params' => []
			];
			break;

		case 'oco.agent.download':
			// check parameter
			if(!isset($params['hostname'])
				|| !isset($params['package-id'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API);
			}
			$computer = checkAuth($params);

			// get package
			$package = $db->selectPackage($params['package-id']);
			if($package === null || !$package->getFilePath()) {
				throw new InvalidRequestException('Package not found', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
			}
			// allow download only if a job is active
			if(!$db->selectPendingAndActiveJobForAgentByComputerIdAndPackageId($computer->id, $package->id)) {
				throw new InvalidRequestException('No active job for this package', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
			}
			// start download
			$package->download();
			die();

		case 'oco.agent.update_job_state':
			$data = $params['data'] ?? [];

			// check parameter
			if(!isset($params['hostname'])
				|| !isset($data['job-id'])
				|| !isset($data['state'])
				|| !array_key_exists('return-code', $data) // null is allowed here
				|| !isset($data['message'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE);
			}
			$computer = checkAuth($params);

			// get job details
			$state = intval($data['state']);
			$jobId = $data['job-id'] ?? -1;
			if(startsWith($jobId, Models\DynamicJob::PREFIX_DYNAMIC_ID)) {
				$job = $db->selectDynamicJob(str_replace(Models\DynamicJob::PREFIX_DYNAMIC_ID, '', $jobId));
			} else {
				$job = $db->selectStaticJob($jobId);
			}
			if($job === null) {
				throw new InvalidRequestException('Job »'.$jobId.'« not found', Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE, $params['hostname'], $computer->id);
			}
			if($job->computer_id !== $computer->id) {
				throw new PermissionException('Computer not allowed to update job »'.$job->id.'«', Models\Log::ACTION_AGENT_API_UPDATE_JOB_STATE, $params['hostname'], $computer->id);
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
					'job-succeeded' => ($state === Models\Job::STATE_SUCCEEDED),
				]
			];
			break;

		case 'oco.agent.events':
			// check parameter
			if(!isset($params['hostname']) || empty($params['data'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_UPDATE);
			}
			$computer = checkAuth($params);
			$data = $params['data'];
			if(!isset($data['events']) || !is_array($data['events'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_UPDATE, $params['hostname'], $computer->id);
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
				'params' => []
			];
			break;

		case 'oco.agent.passwords':
			// check parameter
			if(!isset($params['hostname']) || empty($params['data'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_UPDATE);
			}
			$computer = checkAuth($params);
			$data = $params['data'];
			if(!isset($data['passwords']) || !is_array($data['passwords'])) {
				throw new InvalidRequestException('Invalid JSON data', Models\Log::ACTION_AGENT_API_UPDATE, $params['hostname'], $computer->id);
			}

			$success = false;
			foreach($data['passwords'] as $password) {
				if(empty($password['username']) || empty($password['password'])) continue;
				if(is_array($password['password'])) {
					$decryptedPassword = openssl_decrypt(
						$password['password']['data'],
						'aes-256-cbc',
						$computer->agent_key,
						0,
						base64_decode($password['password']['iv']),
					);
					if(!$decryptedPassword)
						throw new InvalidRequestException('Invalid password data', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
				} else {
					$decryptedPassword = $password['password'];
				}

				if(!empty($password['revoke'])) {
					foreach($db->selectAllComputerPasswordByComputerId($computer->id) as $p) {
						if($p->username === $password['username']
						&& $p->password === $decryptedPassword
						// revoking is only allowed in the first 5 minutes
						&& time() - strtotime($p->created) < 60*5) {
							$success = $db->deleteComputerPassword($p->id);
						}
					}
					continue;
				}
				foreach($db->selectAllPasswordRotationRuleByComputerId($computer->id) as $rule) {
					if($rule->username === $password['username']) {
						$success = $db->insertComputerPassword(
							$computer->id,
							$password['username'],
							$decryptedPassword,
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
				'params' => []
			];
			break;

		default:
			throw new InvalidRequestException('Unknown method');
	}

	// apply extension filters
	foreach($ext->getAggregatedConf('agent-response-filter') as $filterMethod) {
		$resdata = call_user_func($filterMethod, $resdata, empty($computer)?null:$computer);
	}

	// return response
	header('content-type: application/json');
	if(!empty($computer)) {
		if(version_compare($computer->agent_version, '1.1.7', '<')) {
			// TODO: remove insecure plaintext auth
			$resdata['result']['params']['server-key'] = $computer->server_key;
			if(empty($resdata['result']['params']['agent-key']))
				$resdata['result']['params']['agent-key'] = null;
		} else {
			header('x-oco-server-signature: '.hash_hmac('sha256', json_encode($resdata), $computer->server_key));
		}
	}
	echo json_encode($resdata);

}

else {
	throw new InvalidRequestException('Content type mismatch');
}

} catch(PermissionException $e) {
	// log into webserver log for fail2ban
	error_log('api-agent: authentication failure');

	// log into database
	$db->insertLogEntry(Models\Log::LEVEL_WARNING, $e->user??'', $e->objectId, $e->action??Models\Log::ACTION_AGENT_API, json_encode(['error'=>$e->getMessage()]));

	http_response_code(401);
	die($e->getMessage());

} catch(InvalidRequestException $e) {
	// log into database
	$db->insertLogEntry(Models\Log::LEVEL_WARNING, $e->user??'', $e->objectId, $e->action??Models\Log::ACTION_AGENT_API, json_encode(['error'=>$e->getMessage()]));

	http_response_code(400);
	die($e->getMessage());
}


function checkAuth(array $params, string $checkKey=null) {
	global $db;

	$computer = $db->selectComputerByHostname($params['hostname']);
	if($computer === null)
		throw new PermissionException('Computer not found', Models\Log::ACTION_AGENT_API, $params['hostname']);

	if($checkKey === null) $checkKey = $computer->agent_key;

	if(version_compare($computer->agent_version, '1.1.7', '<')
	// use new HMAC authentication for newly upgraded agents
	&& version_compare($params['data']['agent_version']??'1.0.0', '1.1.7', '<')) {
		// TODO: remove insecure plaintext auth
		if(!isset($params['agent-key']) || $params['agent-key'] !== $checkKey)
			throw new PermissionException('Agent key mismatch', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
	} else {
		$headers = getallheaders();
		if(!isset($headers['x-oco-agent-signature']))
			throw new PermissionException('Signature header missing', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
		$computedSignature = hash_hmac('sha256', file_get_contents('php://input'), $checkKey);
		if($headers['x-oco-agent-signature'] !== $computedSignature)
			throw new PermissionException('Invalid agent signature', Models\Log::ACTION_AGENT_API, $params['hostname'], $computer->id);
	}
	return $computer;
}
