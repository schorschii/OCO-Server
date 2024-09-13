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

	if(!empty($_POST['send_command_to_mobile_device_id'])
	&& !empty($_POST['command'])) {
		if($_POST['command'] == 'DeviceLock') {
			$parameter = json_encode([
				'RequestType' => 'DeviceLock',
				#'Message' => '',
				#'PhoneNumber' => '',
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
			if(empty($_POST['message']))
				throw new InvalidRequestException('A message is required for EnableLostMode command');
			$parameter = json_encode([
				'RequestType' => 'EnableLostMode',
				'Message' => $_POST['message'],
				#'Footnote' => '',
				#'PhoneNumber' => '',
			]);
		} elseif($_POST['command'] == 'DisableLostMode') {
			$parameter = json_encode([
				'RequestType' => 'DisableLostMode',
			]);
		} else {
			throw new InvalidRequestException('Unknown command');
		}
		$cl->createMobileDeviceCommand($_POST['send_command_to_mobile_device_id'], $_POST['command'], $parameter);
		die();
	}

	if(!empty($_POST['remove_mobile_device_command_id']) && is_array($_POST['remove_mobile_device_command_id'])) {
		foreach($_POST['remove_mobile_device_command_id'] as $id) {
			// TODO
			$cl->removeMobileDeviceCommand($id);
		}
		die();
	}

	if(isset($_POST['edit_profile_id'])) {
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
		if($_POST['edit_profile_id'] == '-1') {
			die(
				$cl->createProfile($_POST['name']??null, $payload, $_POST['notes']??'')
			);
		}
	}

	if(isset($_POST['add_to_group_id']) && is_array($_POST['add_to_group_id']) && isset($_POST['add_to_group_profile_id']) && is_array($_POST['add_to_group_profile_id'])) {
		foreach($_POST['add_to_group_profile_id'] as $pid) {
			foreach($_POST['add_to_group_id'] as $gid) {
				$cl->assignProfileToMobileDeviceGroup($pid, $gid);
			}
		}
		die();
	}

	if(!empty($_POST['remove_profile_id']) && is_array($_POST['remove_profile_id'])) {
		foreach($_POST['remove_profile_id'] as $id) {
			$cl->removeProfile($id, !empty($_POST['force']));
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
