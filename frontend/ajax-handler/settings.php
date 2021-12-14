<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(isset($_POST['create_systemuser'])
	&& isset($_POST['fullname'])
	&& isset($_POST['description'])
	&& isset($_POST['password'])) {
		die(strval(intval(
			$cl->createSystemuser(
				$_POST['create_systemuser'], $_POST['fullname'], $_POST['description'], $_POST['password']
			)
		)));
	}

	if(!empty($_POST['update_systemuser_id'])
	&& isset($_POST['username'])
	&& isset($_POST['fullname'])
	&& isset($_POST['description'])
	&& isset($_POST['password'])) {
		$cl->updateSystemuser(
			$_POST['update_systemuser_id'], $_POST['username'], $_POST['fullname'], $_POST['description'], $_POST['password']
		);
		die();
	}

	if(!empty($_POST['remove_systemuser_id'])
	&& is_array($_POST['remove_systemuser_id'])) {
		foreach($_POST['remove_systemuser_id'] as $id) {
			$cl->removeSystemuser($id);
		}
		die();
	}

	if(!empty($_POST['lock_systemuser_id'])
	&& is_array($_POST['lock_systemuser_id'])) {
		foreach($_POST['lock_systemuser_id'] as $id) {
			$cl->updateSystemuserLocked($id, 1);
		}
		die();
	}

	if(!empty($_POST['unlock_systemuser_id'])
	&& is_array($_POST['unlock_systemuser_id'])) {
		foreach($_POST['unlock_systemuser_id'] as $id) {
			$cl->updateSystemuserLocked($id, 0);
		}
		die();
	}

} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
