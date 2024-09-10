<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(isset($_POST['create_mobile_device'])) {
		die(
			$cl->createMobileDevice($_POST['create_mobile_device'], $_POST['notes']??'')
		);
	}

	if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
		foreach($_POST['remove_id'] as $id) {
			$cl->removeMobileDevice($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
		foreach($_POST['remove_group_id'] as $id) {
			$cl->removeMobileDeviceGroup($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_mobile_device_id']) && is_array($_POST['remove_from_group_mobile_device_id'])) {
		foreach($_POST['remove_from_group_mobile_device_id'] as $cid) {
			$cl->removeMobileDeviceFromGroup($cid, $_POST['remove_from_group_id']);
		}
		die();
	}

	if(isset($_POST['create_group'])) {
		die(
			$cl->createMobileDeviceGroup($_POST['create_group'], empty($_POST['parent_id']) ? null : intval($_POST['parent_id']))
		);
	}

	if(!empty($_POST['rename_group_id']) && isset($_POST['new_name'])) {
		$cl->renameMobileDeviceGroup($_POST['rename_group_id'], $_POST['new_name']);
		die();
	}

	if(isset($_POST['add_to_group_id']) && is_array($_POST['add_to_group_id']) && isset($_POST['add_to_group_mobile_device_id']) && is_array($_POST['add_to_group_mobile_device_id'])) {
		foreach($_POST['add_to_group_mobile_device_id'] as $cid) {
			foreach($_POST['add_to_group_id'] as $gid) {
				$cl->addMobileDeviceToGroup($cid, $gid);
			}
		}
		die();
	}

	if(isset($_POST['name'])
	&& isset($_POST['mobile_device_id'])
	&& isset($_POST['command'])
	&& isset($_POST['notes'])
	&& isset($_POST['payload'])) {
		// no payload change by default
		$payload = null;
		if(!empty($_POST['update_payload'])) {
			// no payload by default
			$payload = [];
			if(!empty($_FILES['payload']) && is_array($_FILES['payload']['tmp_name'])) {
				// use files from user upload
				for($i=0; $i < count($_FILES['payload']['tmp_name']); $i++) {
					if(isset($_FILES['payload']['name'][$i]) && file_exists($_FILES['payload']['tmp_name'][$i])) {
						$payload = file_get_contents($_FILES['payload']['tmp_name'][$i]);
					}
				}
			}
		}
		if($_POST['command'] == 'InstallProfile') {
			$parameter = json_encode([
				'RequestType' => 'InstallProfile',
				'Payload' => base64_encode($payload),
				'_data' => ['Payload'],
			]);
		} elseif($_POST['command'] == 'DeviceLock') {
			$parameter = json_encode([
				'RequestType' => 'DeviceLock',
				'Message' => '',
				'PhoneNumber' => '',
				#'PIN' => '', // six-character PIN for Find My
			]);
		} elseif($_POST['command'] == 'EraseDevice') {
			$parameter = json_encode([
				'RequestType' => 'EraseDevice',
				#'PIN' => '', // six-character PIN for Find My
			]);
		} elseif($_POST['command'] == 'ClearPasscode') {
			$md = $cl->getMobileDevice($_POST['mobile_device_id']);
			$parameter = json_encode([
				'RequestType' => 'ClearPasscode',
				'UnlockToken' => $md->unlock_token,
				'_data' => ['UnlockToken'],
			]);
		} elseif($_POST['command'] == 'EnableLostMode') {
			$parameter = json_encode([
				'RequestType' => 'EnableLostMode',
				#'Footnote' => '',
				#'Message' => '',
				#'PhoneNumber' => '',
			]);
		} elseif($_POST['command'] == 'DisableLostMode') {
			$parameter = json_encode([
				'RequestType' => 'DisableLostMode',
			]);
		}
		$cl->createMobileDeviceCommand($_POST['mobile_device_id'], $_POST['name'], $parameter, $_POST['notes']);
		$mdcc = new MobileDeviceCommandController($db);
		$mdcc->mdmCron();
		die();
	}

	if(!empty($_POST['remove_mobile_device_command_id']) && is_array($_POST['remove_mobile_device_command_id'])) {
		foreach($_POST['remove_mobile_device_command_id'] as $id) {
			$cl->removeMobileDeviceCommand($id);
		}
		die();
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
