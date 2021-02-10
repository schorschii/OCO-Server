<?php
require_once('../lib/loader.php');

// check content type
if(!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] != 'application/json') {
	header('HTTP/1.1 400 Content Type Mismatch'); die();
}

// get body
$body = file_get_contents('php://input');
$srcdata = json_decode($body, true);

// validate JSON-RPC
if($srcdata === null || !isset($srcdata['jsonrpc']) || $srcdata['jsonrpc'] != '2.0' || !isset($srcdata['method']) || !isset($srcdata['params']) || !isset($srcdata['id'])) {
	header('HTTP/1.1 400 Payload Corrupt'); die();
}

$resdata = ['id' => $srcdata['id']];
$params = $srcdata['params'];
switch($srcdata['method']) {
	case 'oco.update_deploy_status':
		$data = $params['data'];

		// check parameter
		if(!isset($data['job-id']) || !isset($data['state']) || !isset($data['return-code']) || !isset($data['message'])) {
			header('HTTP/1.1 400 Parameter Mismatch'); die();
		}

		// check authorization
		$computer = $db->getComputerByName($params['hostname']);
		if($params['agent-key'] !== $computer->agent_key) {
			header('HTTP/1.1 401 Client Not Authorized'); die();
		}

		// get job details
		$state = $data['state'];
		$job = $db->getJob($data['job-id']);
		if($job === null) {
			header('HTTP/1.1 400 Job Not Found'); die();
		}

		// if job finished, we need to check the return code
		if($state == Job::STATUS_SUCCEEDED) {
			$successCodes = [];
			foreach(explode(',', $job->success_return_codes) as $successCode) {
				if(trim($successCode) === '') continue;
				$successCodes[] = intval(trim($successCode));
			}
			// check if return code is a success return code if any valid return code found
			if(count($successCodes) > 0) {
				$state = Job::STATUS_FAILED;
				foreach($successCodes as $successCode) {
					if(intval($data['return-code']) === intval($successCode)) {
						$state = Job::STATUS_SUCCEEDED;
						break;
					}
				}
			}
		}

		// update job state in database
		$db->updateJobState($data['job-id'], $state, intval($data['return-code']), $data['message']);
		// update computer-package assignment if job was successful
		if($state === Job::STATUS_SUCCEEDED) {
			if($job->is_uninstall == 0) {
				$db->addPackageToComputer($job->package_id, $job->computer_id, $job->package_procedure);
			} elseif($job->is_uninstall == 1) {
				$db->removeComputerAssignedPackageByIds($job->computer_id, $job->package_id);
			}
		}
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true,
			'params' => []
		];
		break;

	case 'oco.agent_hello':
		$data = $params['data'];
		$computer = $db->getComputerByName($params['hostname']);
		$jobs = []; $update = 0; $agent_key = null; $success = false;

		if($computer == null) {
			if($params['agent-key'] !== $db->getSettingByName('agent-key')) {
				header('HTTP/1.1 401 Client Not Authorized'); die();
			}

			if($db->getSettingByName('agent-registration-enabled') == '1') {
				$agent_key = randomString();
				$update = 1;
				if($db->addComputer(
					$params['hostname'],
					$data['agent_version'],
					$data['networks'],
					$agent_key
				)) {
					$success = true;
				}
			} else {
				header('HTTP/1.1 403 Client Self-Registration Disabled'); die();
			}
		} else {
			if(empty($computer->agent_key)) {
				// computer was pre-registered in the web frontend: check global key and generate individual key
				if($params['agent-key'] !== $db->getSettingByName('agent-key')) {
					header('HTTP/1.1 401 Client Not Authorized'); die();
				} else {
					$agent_key = randomString();
					$db->updateComputerAgentkey($computer->id, $agent_key);
				}
			} else {
				// check individual agent key
				if($params['agent-key'] !== $computer->agent_key) {
					header('HTTP/1.1 401 Client Not Authorized'); die();
				}
			}

			// update last seen date
			$db->updateComputerPing($computer->id);

			// check if agent should update inventory data
			if(time() - strtotime($computer->last_update) > $db->getSettingByName('agent-update-interval')) {
				$update = 1;
			}

			// get pending jobs
			$jobs = [];
			foreach($db->getPendingJobsForComputer($computer->id) as $pj) {
				$jobs[] = [
					'id' => $pj['id'],
					'package-id' => $pj['package_id'],
					'download' => $pj['download']==0 ? False : True,
					'procedure' => $pj['procedure'],
				];
			}

			$success = true;
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => $success,
			'params' => [
				'agent-key' => $agent_key,
				'update' => $update,
				'software-jobs' => $jobs,
			]
		];

		break;

		case 'oco.agent_update':
			$data = $params['data'];
			$computer = $db->getComputerByName($params['hostname']);
			if($params['agent-key'] !== $computer->agent_key) {
				header('HTTP/1.1 401 Client Not Authorized'); die();
			}

			if($computer !== null) {
				$db->updateComputerPing($computer->id);
				if(time() - strtotime($computer->last_update) > $db->getSettingByName('agent-update-interval')
				&& !empty($data)) {
					$db->updateComputer(
						$computer->id,
						$params['hostname'],
						$data['os'],
						$data['os_version'],
						$data['kernel_version'],
						$data['architecture'],
						$data['cpu'],
						$data['gpu'],
						$data['ram'],
						$data['agent_version'],
						$data['serial'],
						$data['manufacturer'],
						$data['model'],
						$data['bios_version'],
						$data['boot_type'],
						$data['secure_boot'],
						$data['networks'] ?? [],
						$data['screens'] ?? [],
						$data['printers'] ?? [],
						$data['partitions'] ?? [],
						$data['software'] ?? [],
						$data['logins'] ?? []
					);
				}
			}

			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true,
				'params' => []
			];

			break;

	default:
		$resdata['result'] = null;
		$resdata['error'] = 'Unknown Method';
}

// return response
header('Content-Type: application/json');
echo json_encode($resdata);
