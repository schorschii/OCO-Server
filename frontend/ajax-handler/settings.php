<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	if(isset($_POST['add_systemuser_username'])) {
		if(empty(trim($_POST['add_systemuser_username']))
		|| empty(trim($_POST['add_systemuser_fullname']))) {
			throw new Exception(LANG['name_cannot_be_empty']);
		}
		if(empty(trim($_POST['add_systemuser_password']))) {
			throw new Exception(LANG['password_cannot_be_empty']);
		}
		if($db->getSystemuserByLogin($_POST['add_systemuser_username']) !== null) {
			throw new Exception(LANG['username_already_exists']);
		}
		$db->addSystemuser(
			$_POST['add_systemuser_username'],
			$_POST['add_systemuser_fullname'],
			password_hash($_POST['add_systemuser_password'], PASSWORD_DEFAULT),
			0/*ldap*/, ''/*email*/, ''/*mobile*/, ''/*phone*/, ''/*description*/, 0
		);
		die();
	}

	if(!empty($_POST['change_systemuser_id']) && isset($_POST['change_systemuser_password'])) {
		if(empty(trim($_POST['change_systemuser_password']))) {
			throw new Exception(LANG['password_cannot_be_empty']);
		}
		$u = $db->getSystemuser($_POST['change_systemuser_id']);
		if($u == null) throw new Exception(LANG['not_found']);
		$db->updateSystemuser(
			$u->id, $u->username, $u->fullname,
			password_hash($_POST['change_systemuser_password'], PASSWORD_DEFAULT),
			$u->ldap, $u->email, $u->phone, $u->mobile, $u->description, $u->locked
		);
		die();
	}

	if(!empty($_POST['remove_systemuser_id']) && is_array($_POST['remove_systemuser_id'])) {
		foreach($_POST['remove_systemuser_id'] as $id) {
			$db->removeSystemuser($id);
		}
		die();
	}

	if(!empty($_POST['lock_systemuser_id']) && is_array($_POST['lock_systemuser_id'])) {
		foreach($_POST['lock_systemuser_id'] as $id) {
			$u = $db->getSystemuser($id);
			if($u == null) throw new Exception(LANG['not_found']);
			$db->updateSystemuser(
				$u->id, $u->username, $u->fullname, $u->password, $u->ldap, $u->email, $u->phone, $u->mobile, $u->description, 1
			);
		}
		die();
	}

	if(!empty($_POST['unlock_systemuser_id']) && is_array($_POST['unlock_systemuser_id'])) {
		foreach($_POST['unlock_systemuser_id'] as $id) {
			$u = $db->getSystemuser($id);
			if($u == null) throw new Exception(LANG['not_found']);
			$db->updateSystemuser(
				$u->id, $u->username, $u->fullname, $u->password, $u->ldap, $u->email, $u->phone, $u->mobile, $u->description, 0
			);
		}
		die();
	}

} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
