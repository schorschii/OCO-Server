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
		if($params['client-key'] !== $db->getSettingByName('client-key')) {
			header('HTTP/1.1 401 Client Not Authorized'); die();
		}
		$data = $params['data'];
		$db->updateJobState($data['job-id'], $data['state'], $data['message']);
		if($data['state'] === 1) {
			$job = $db->getJob($data['job-id']);
			if($job !== null) {
				$db->addPackageToComputer($job->package_id, $job->computer_id, $job->package_procedure);
			}
		}
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true,
			'params' => []
		];
		break;

	case 'oco.client_hello':
		if($params['client-key'] !== $db->getSettingByName('client-key')) {
			header('HTTP/1.1 401 Client Not Authorized'); die();
		}

		$data = $params['data'];
		$computer = $db->getComputerByName($data['hostname']);
		$jobs = [];
		$update = 0;
		if($computer == null) {
			if($db->getSettingByName('client-registration-enabled') == '1') {
				$update = 1;
				$db->addComputer(
					$data['hostname'],
					$data['agent_version'],
					$data['networks']
				);
			}
		} else {
			$db->updateComputerPing($computer->id);
			if(time() - strtotime($computer->last_update) > $db->getSettingByName('client-update-interval')) {
				$update = 1;
			}
			$jobs = $db->getPendingJobsForComputer($computer->id);
		}

		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true,
			'params' => [
				'update' => $update,
				'software-jobs' => $jobs
			]
		];

		break;

		case 'oco.client_update':
			if($params['client-key'] !== $db->getSettingByName('client-key')) {
				header('HTTP/1.1 401 Client Not Authorized'); die();
			}

			$data = $params['data'];
			$computer = $db->getComputerByName($data['hostname']);
			if($computer !== null) {
				$db->updateComputerPing($computer->id);
				if(time() - strtotime($computer->last_update) > $db->getSettingByName('client-update-interval')) {
					$db->updateComputer(
						$computer->id,
						$data['hostname'],
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
		$resdata['error'] = LANG['unknown_method'];
}

// return response
header('Content-Type: application/json');
echo json_encode($resdata);
