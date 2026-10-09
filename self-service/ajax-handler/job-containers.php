<?php
// self-service/ajax-handler/job-containers.php

ini_set('display_errors', '0');

require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

if (!isset($_SESSION['oco_self_service_user_id']) || empty($_SESSION['oco_self_service_user_id'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => LANG('portal_redesign_api_unauthorized')]);
    exit();
}

session_write_close();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $name = isset($input['name']) ? trim($input['name']) : '';
    $computers = isset($input['computers']) ? $input['computers'] : [];
    $packages = isset($input['packages']) ? $input['packages'] : [];
    $wol = isset($input['wol']) ? (bool)$input['wol'] : false;
    $shutdown = isset($input['shutdown']) ? (bool)$input['shutdown'] : false;
    $forceInstall = true;

    // Safety guard: if sockets extension is missing, disable Wake-on-LAN to prevent fatal crash
    if ($wol && !function_exists('socket_create')) {
        $wol = false;
    }

    if (empty($name)) {
        $name = LANG('portal_redesign_api_default_job_name') . ' ' . date('d/m/Y H:i');
    }

    if (is_string($computers)) {
        $computers = array_filter(array_map('intval', explode(',', $computers)));
    }
    if (is_string($packages)) {
        $packages = array_filter(array_map('intval', explode(',', $packages)));
    }

    if (empty($computers) || empty($packages)) {
        throw new Exception(LANG('portal_redesign_api_missing_params'));
    }

    // Execute deployment using OCO CoreLogic
    $containerId = $cl->deploySelfService(
        $name,
        $computers,
        $packages,
        date('Y-m-d H:i:s'),
        null, // dateEnd
        $wol ? 1 : 0,
        $shutdown ? 1 : 0,
        5, // restart_timeout
        $forceInstall ? 1 : 0,
        0  // sequence_mode
    );

    if (!$containerId) {
        throw new Exception(LANG('portal_redesign_api_creation_failed'));
    }

    echo json_encode([
        'success' => true,
        'message' => LANG('portal_redesign_api_success'),
        'job_container_id' => intval($containerId)
    ]);

} catch (\PermissionException $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => LANG('portal_redesign_api_permission_denied')
    ]);
} catch (\Throwable $e) {
    error_log('OCO Self-Service API Error (job-containers.php): ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() ?: LANG('portal_redesign_api_deploy_error')
    ]);
}
