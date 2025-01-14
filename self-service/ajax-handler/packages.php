<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(!empty($_POST['get_package_names']) && is_array($_POST['get_package_names'])) {
		$finalArray = [];
		foreach($_POST['get_package_names'] as $id) {
			$p = $cl->getMyPackage($id);
			if(!empty($p)) $finalArray[] = ['id'=>$p->id, 'name'=>$p->getFullName()];
		}
		die(json_encode($finalArray));
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
