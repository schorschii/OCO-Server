<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$profile = null;
	foreach($cl->getProfiles() as $p) {
		if($p->id == ($_GET['id'] ?? -1)) {
			$profile = $p;
		}
	}
	if(!$profile) {
		throw new NotFoundException();
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$requestPlist = new CFPropertyList\CFPropertyList();
$requestPlist->parse($profile->payload);
echoDictTable($requestPlist->toArray());
