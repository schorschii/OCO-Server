<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$profile = $cl->getProfile($_GET['id'] ?? -1);
	try {
		$requestPlist = new CFPropertyList\CFPropertyList();
		$requestPlist->parse($profile->payload);
		$payloadData = $requestPlist->toArray();
	} catch(DOMException|TypeError $e) {
		$payloadData = json_decode($profile->payload, true);
		if($payloadData === null)
			throw new InvalidRequestException('Payload is no valid XML or JSON');
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

echoDictTable($payloadData);
