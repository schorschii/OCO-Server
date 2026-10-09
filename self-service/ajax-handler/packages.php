<?php
// self-service/ajax-handler/packages.php

ini_set('display_errors', '0');
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

session_write_close();

header('Content-Type: application/json');

try {
    $packages = $cl->getMyPackages();
    $data = [];
    foreach ($packages as $p) {
        $data[] = [
            'id' => intval($p->id),
            'fullName' => $p->getFullName(),
            'name' => $p->package_family_name,
            'version' => $p->version,
            'compatible_os' => $p->compatible_os,
            'compatible_os_version' => $p->compatible_os_version,
            'compatible_architecture' => $p->compatible_architecture,
            'size' => $p->getSize(),
            'icon' => $p->getIcon(),
            'package_family_id' => intval($p->package_family_id),
            'package_family_name' => $p->package_family_name
        ];
    }
    echo json_encode($data);
} catch (\Throwable $e) {
    error_log('OCO Self-Service API Error (packages.php): ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => LANG('portal_redesign_api_internal_error')]);
}
