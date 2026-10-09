<?php
// self-service/ajax-handler/computers.php

ini_set('display_errors', '0');
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

session_write_close();

header('Content-Type: application/json');

try {
    $computers = $cl->getMyComputers();
    $data = [];
    foreach ($computers as $c) {
        $installed = [];
        foreach ($db->selectAllComputerPackageByComputerId($c->id) as $cp) {
            $installed[] = intval($cp->package_id);
        }
        $data[] = [
            'id' => intval($c->id),
            'hostname' => $c->hostname,
            'os' => $c->os,
            'os_version' => $c->os_version,
            'serial' => $c->serial,
            'icon' => $c->getIcon(),
            'isOnline' => $c->isOnline($db),
            'last_ping' => $c->last_ping,
            'installed_packages' => $installed
        ];
    }
    echo json_encode($data);
} catch (\Throwable $e) {
    error_log('OCO Self-Service API Error (computers.php): ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => LANG('portal_redesign_api_internal_error')]);
}
