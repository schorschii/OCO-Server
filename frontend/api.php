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
		$computer = $db->getComputerByName($params['hostname']);
		if($params['agent-key'] !== $computer->agent_key) {
			header('HTTP/1.1 401 Client Not Authorized'); die();
		}

		$db->updateJobState($data['job-id'], $data['state'], $data['message']);
		if($data['state'] === 2) { // 2 = installation successful
			$job = $db->getJob($data['job-id']);
			if($job !== null) {
				if($job->is_uninstall == 0) {
					$db->addPackageToComputer($job->package_id, $job->computer_id, $job->package_procedure);
				} elseif($job->is_uninstall == 1) {
					$db->removeComputerAssignedPackageByIds($job->computer_id, $job->package_id);
				}
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

			$db->updateComputerPing($computer->id);
			if(time() - strtotime($computer->last_update) > $db->getSettingByName('agent-update-interval')) {
				$update = 1;
			}
			$jobs = $db->getPendingJobsForComputer($computer->id);
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
				if(time() - strtotime($computer->last_update) > $db->getSettingByName('agent-update-interval')) {
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
						$data['networks'],
						$data['screens'],
						$data['software'],
						$data['logins']
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
