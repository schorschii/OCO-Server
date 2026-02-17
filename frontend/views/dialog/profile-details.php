<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$profile = $cl->getProfile($_GET['id'] ?? -1);
	try {
		$requestPlist = new CFPropertyList\CFPropertyList();
		$requestPlist->parse($profile->payload);
		$payloadData = $requestPlist->toArray();
	} catch(DOMException|TypeError $e) {
		$payloadData = json_decode($profile->payload, true);
		if($payloadData === null)
			throw new Exception('Payload is no valid XML or JSON');
	}
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('permission_denied'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('not_found'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
} catch(Exception $e) {
	http_response_code(500);
	die($e->getMessage());
}

Html::dictTable($payloadData);
