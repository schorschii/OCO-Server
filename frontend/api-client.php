<?php
require_once('../loader.inc.php');

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

// log complete request
$db->insertLogEntry(Models\Log::LEVEL_DEBUG, null, null, Models\Log::ACTION_CLIENT_API_RAW, $body);

// validate JSON-RPC
if($srcdata === null || !isset($srcdata['jsonrpc']) || $srcdata['jsonrpc'] != '2.0' || !isset($srcdata['method']) || !isset($srcdata['params']) || !isset($srcdata['id'])) {
	header('HTTP/1.1 400 Payload Corrupt'); die();
}

// login
$cl = null;
try {
	if(empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
		throw new AuthenticationException(LANG('username_cannot_be_empty'));
	}
	$authenticator = new AuthenticationController($db);
	$user = $authenticator->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	if($user == null || !$user instanceof Models\SystemUser) {
		throw new AuthenticationException(LANG('unknown_error'));
	}

	if(!$user->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_CLIENT_API, false)) {
		throw new AuthenticationException(LANG('api_login_not_allowed'));
	}

	// login successful
	$cl = new CoreLogic($db, $user);
	$db->insertLogEntry(Models\Log::LEVEL_INFO, $_SERVER['PHP_AUTH_USER'], null, Models\Log::ACTION_CLIENT_API, ['authenticated'=>true]);
} catch(AuthenticationException $e) {
	$db->insertLogEntry(Models\Log::LEVEL_WARNING, $_SERVER['PHP_AUTH_USER'] ?? '', null, Models\Log::ACTION_CLIENT_API, ['authenticated'=>false]);

	header('HTTP/1.1 401 Client Not Authorized');
	error_log('api-agent: authentication failure');
	die('HTTP Basic Auth: '.$e->getMessage());
}

// handle request
$resdata = ['id' => $srcdata['id']];
$params = $srcdata['params'] ?? [];
$data = $params['data'] ?? [];

// check API key
if(!empty(CLIENT_API_KEY) && CLIENT_API_KEY !== ($params['api_key'] ?? '')) {
	header('Content-Type: application/json');
	$resdata['error'] = LANG('invalid_api_key');
	$resdata['result'] = [ 'success' => false, 'data' => [] ];
	echo json_encode($resdata);
	die();
}

try {

// handle BREW requests
if(isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'BREW') {
	header("HTTP/1.1 418 I'm a teapot");
	die(
		'This is a teapot! But luckily it can handle coffee too.'."\n".
		'The local brewing personnel was informed about your coffee request.'."\n".
		'Please be patient while the CPU is heating up to brew your coffee.'."\n".
		'Meanwhile, here is an ASCII cup for you. Attention: Hot!'."\n".
		"\n".
		"         {"."\n".
		"      {   }"."\n".
		"       }_{ __{"."\n".
		"    .-{   }   }-."."\n".
		"   (   }     {   )"."\n".
		"   |`-.._____..-'|"."\n".
		"   |             ;--."."\n".
		"   |            (__  \\"."\n".
		"   |             | )  )"."\n".
		"   |             |/  /"."\n".
		"   |             /  /    -Felix Lee-"."\n".
		"   |            (  /"."\n".
		"   \             y'"."\n".
		"    `-.._____..-'"."\n".
		"\n"
	);
}

// handle method
switch($srcdata['method']) {
	case 'oco.computer.list':
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => $cl->getComputers()
		];
		break;

	case 'oco.computer.get':
		$computer = $cl->getComputer($data['id'] ?? 0);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true,
			'data' => [
				'general' => $computer,
				'logins' => $db->selectAllDomainUserLogonByComputerId($computer->id),
				'networks' => $db->selectAllComputerNetworkByComputerId($computer->id),
				'screens' => $db->selectAllComputerScreenByComputerId($computer->id),
				'printers' => $db->selectAllComputerPrinterByComputerId($computer->id),
				'filesystems' => $db->selectAllComputerPartitionByComputerId($computer->id),
				'recognised_software' => $db->selectAllComputerSoftwareByComputerId($computer->id),
				'installed_packages' => $db->selectAllComputerPackageByComputerId($computer->id),
				'pending_jobs' => $db->selectAllPendingJobByComputerId($computer->id),
			]
		];
		break;

	case 'oco.computer.create':
		$insertId = $cl->createComputer($data['hostname'] ?? '', $data['notes'] ?? '');
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => [ 'id' => $insertId ]
		];
		break;

	case 'oco.computer.wol':
		$cl->wolComputers([intval($data['id'] ?? 0)], false);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => []
		];
		break;

	case 'oco.computer.remove':
		$cl->removeComputer(intval($data['id'] ?? 0), boolval($data['force'] ?? 1));
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => []
		];
		break;

	case 'oco.package_family.list':
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => $cl->getPackageFamilies(null, $data['show_icons'] ?? false)
		];
		break;

	case 'oco.package.list':
		$pf = $cl->getPackageFamily($data['id'] ?? 0);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => $db->selectAllPackageByPackageFamilyId($data['id'] ?? 0)
		];
		break;

	case 'oco.package.get':
		$package = $cl->getPackage($data['id'] ?? 0, $data['show_icons'] ?? false);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => [
				'general' => $package,
				'installations' => $db->selectAllComputerPackageByPackageId($package->id),
				'pending_jobs' => $db->selectAllPendingJobByPackageId($package->id),
			]
		];
		break;

	case 'oco.package.create':
		// prepare file (extract content from base64 encoded JSON)
		$tmpFilePath = null;
		if(!empty($data['file'])) {
			$tmpFilePath = '/tmp/ocotmp';
			$fileContent = base64_decode($data['file'], true);
			if(!$fileContent) {
				throw new InvalidRequestException(LANG('payload_corrupt'));
			}
			file_put_contents($tmpFilePath, $fileContent);
		}
		// insert into database
		$insertId = $cl->createPackage($data['package_family_name'] ?? '', $data['version'] ?? '', $data['description'] ?? '', $_SERVER['PHP_AUTH_USER'],
			$data['install_procedure'] ?? '', $data['install_procedure_success_return_codes'] ?? '0', $data['install_procedure_post_action'] ?? 0,
			$data['uninstall_procedure'] ?? '', $data['uninstall_procedure_success_return_codes'] ?? '0', $data['download_for_uninstall'] ?? 0, $data['uninstall_procedure_post_action'] ?? 0,
			$data['compatible_os'] ?? '', $data['compatible_os_version'] ?? '', $tmpFilePath, $data['file_name'] ?? 'file'
		);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => [ 'id' => $insertId ]
		];
		break;

	case 'oco.package.remove':
		$cl->removePackage(intval($data['id'] ?? 0), boolval($data['force'] ?? 1));
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => []
		];
		break;

	case 'oco.job_container.list':
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => $cl->getJobContainers()
		];
		break;

	case 'oco.job_container.job.list':
		$jc = $cl->getJobContainer($data['id'] ?? 0);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => $db->selectAllStaticJobByJobContainer($data['id'] ?? 0)
		];
		break;

	case 'oco.job_container.deploy':
		$insertId = $cl->deploy(
			$data['name'] ?? '', $data['description'] ?? '', $_SERVER['PHP_AUTH_USER'],
			$data['computer_ids'] ?? [], $data['computer_group_ids'] ?? [], $data['computer_report_ids'] ?? [],
			$data['package_ids'] ?? [], $data['package_group_ids'] ?? [], $data['package_report_ids'] ?? [],
			$data['date_start'] ?? date('Y-m-d H:i:s'), $data['date_end'] ?? null,
			$data['use_wol'] ?? 1, $data['shutdown_waked_after_completion'] ?? 0, $data['restart_timeout'] ?? 5,
			$data['auto_create_uninstall_jobs'] ?? 1, $data['force_install_same_version'] ?? 0,
			$data['sequence_mode'] ?? 0, $data['priority'] ?? 0, $data['constraint_ip_ranges'] ?? []
		);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => [ 'id' => $insertId ]
		];
		break;

	case 'oco.job_container.uninstall':
		$insertId = $cl->uninstall(
			$data['name'] ?? '', $data['description'] ?? '', $_SERVER['PHP_AUTH_USER'],
			$data['installation_ids'] ?? [],
			$data['date_start'] ?? date('Y-m-d H:i:s'), $data['date_end'] ?? null,
			$data['use_wol'] ?? 1, $data['shutdown_waked_after_completion'] ?? 0, $data['restart_timeout'] ?? 5,
			$data['sequence_mode'] ?? 0, $data['priority'] ?? 0, $data['constraint_ip_ranges'] ?? []
		);
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => [ 'id' => $insertId ]
		];
		break;

	case 'oco.remove_installation_assignment':
		$cl->removeComputerAssignedPackage(intval($data['id'] ?? 0));
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => []
		];
		break;

	case 'oco.job_container.remove':
		$cl->removeJobContainer(intval($data['id'] ?? 0));
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => []
		];
		break;

	case 'oco.job_container.job.remove':
		$cl->removeStaticJob(intval($data['id'] ?? 0));
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => []
		];
		break;

	case 'oco.report.execute':
		$resdata['error'] = null;
		$resdata['result'] = [
			'success' => true, 'data' => $cl->executeReport(intval($data['id'] ?? 0))
		];
		break;

	default:
		throw new InvalidRequestException(LANG('unknown_method'));
}

} catch(NotFoundException $e) {
	header('HTTP/1.1 404 Not Found');
	$resdata['error'] = LANG('not_found');
	$resdata['result'] = [
		'success' => false
	];
} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	$resdata['error'] = LANG('permission_denied');
	$resdata['result'] = [
		'success' => false
	];
} catch(InvalidRequestException $e) {
	header('HTTP/1.1 400 Invalid Request');
	$resdata['error'] = $e->getMessage();
	$resdata['result'] = [
		'success' => false
	];
} catch(Exception $e) {
	$resdata['error'] = $e->getMessage();
	$resdata['result'] = [
		'success' => false
	];
}

// return response
header('Content-Type: application/json');
echo json_encode($resdata, JSON_PARTIAL_OUTPUT_ON_ERROR);
