<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(!empty($_POST['wol_id']) && is_array($_POST['wol_id'])) {
		$cl->wolMyComputers($_POST['wol_id']);
		die();
	}

	if(!empty($_POST['get_computer_names']) && is_array($_POST['get_computer_names'])) {
		$finalArray = [];
		foreach($_POST['get_computer_names'] as $id) {
			$c = $cl->getMyComputer($id);
			if(!empty($c)) $finalArray[] = ['id'=>$c->id, 'name'=>$c->hostname];
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
