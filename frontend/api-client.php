<?php
require_once('../lib/Loader.php');

// check API enabled
if(!CLIENT_API_ENABLED) {
	header('HTTP/1.1 405 API Disabled'); die();
}

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

// login
try {
	if(empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
		throw new Exception(LANG['username_cannot_be_empty']);
	}
	if(empty($cl->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))) {
		throw new Exception(LANG['unknown_error']);
	}
} catch(Exception $e) {
	header('HTTP/1.1 401 Client Not Authorized');
	error_log('api-agent: authentication failure');
	die('HTTP Basic Auth: '.$e->getMessage());
}

// handle request
$resdata = ['id' => $srcdata['id']];
$params = $srcdata['params'] ?? [];
$data = $params['data'] ?? [];
switch($srcdata['method']) {
	case 'oco.computer.list':
		try {
			$result = $db->getAllComputer();
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => $result
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.computer.get':
		try {
			$computer = $db->getComputer($data['id'] ?? 0);
			if($computer == null) throw new Exception(LANG['not_found']);
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true,
				'data' => [
					'general' => $computer,
					'logins' => $db->getDomainuserLogonByComputer($computer->id),
					'networks' => $db->getComputerNetwork($computer->id),
					'screens' => $db->getComputerScreen($computer->id),
					'printers' => $db->getComputerPrinter($computer->id),
					'filesystems' => $db->getComputerPartition($computer->id),
					'recognised_software' => $db->getComputerSoftware($computer->id),
					'installed_packages' => $db->getComputerPackage($computer->id),
					'pending_jobs' => $db->getPendingJobsForComputerDetailPage($computer->id),
				]
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.computer.create':
		try {
			$insertId = $cl->createComputer($data['hostname'] ?? '', $data['notes'] ?? '');
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => [ 'id' => $insertId ]
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.computer.wol':
		try {
			$cl->wolComputers([intval($data['id'] ?? 0)], false);
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => []
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.computer.remove':
		try {
			$cl->removeComputer(intval($data['id'] ?? 0));
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => []
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;

	case 'oco.package_family.list':
		try {
			$result = $db->getAllPackageFamily();
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => $result
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.package.list':
		try {
			$pf = $db->getPackageFamily($data['id'] ?? 0);
			if($pf == null) throw new Exception(LANG['not_found']);
			$result = $db->getPackageByFamily($data['id'] ?? 0);
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => $result
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.package.get':
		try {
			$package = $db->getPackage($data['id'] ?? 0);
			if($package == null) throw new Exception(LANG['not_found']);
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => [
					'general' => $package,
					'installations' => $db->getPackageComputer($package->id),
					'pending_jobs' => $db->getPendingJobsForPackageDetailPage($package->id),
				]
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;

	case 'oco.job_container.list':
		try {
			$result = $db->getAllJobContainer();
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => $result
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.job.list':
		try {
			$jc = $db->getJobContainer($data['id'] ?? 0);
			if($jc == null) throw new Exception(LANG['not_found']);
			$result = $db->getAllJobByContainer($data['id'] ?? 0);
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => $result
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;
	case 'oco.deploy':
		try {
			$insertId = $cl->deploy(
				$data['name'], $data['description'] ?? '', $_SERVER['PHP_AUTH_USER'],
				$data['computer_ids'] ?? [], $data['computer_group_ids'] ?? [], $data['package_ids'] ?? [], $data['package_group_ids'] ?? [],
				$data['date_start'] ?? date('Y-m-d H:i:s'), $data['date_end'] ?? null, $data['use_wol'] ?? 1, $data['restart_timeout'] ?? 5, $data['auto_create_uninstall_jobs'] ?? 1
			);
			$resdata['error'] = null;
			$resdata['result'] = [
				'success' => true, 'data' => [ 'id' => $insertId ]
			];
		} catch(Exception $e) {
			$resdata['error'] = $e->getMessage();
			$resdata['result'] = [
				'success' => false, 'data' => []
			];
		}
		break;

	default:
		$resdata['error'] = 'Unknown Method';
		$resdata['result'] = [
			'success' => false,
		];
}

// return response
header('Content-Type: application/json');
echo json_encode($resdata);
