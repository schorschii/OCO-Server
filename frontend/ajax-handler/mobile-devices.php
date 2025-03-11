<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(isset($_POST['create_mobile_device']) && isset($_POST['type'])) {
		$os = '';
		if($_POST['type'] == 'ios') {
			$os = 'iOS';
		} else throw new Exception('Unknown type');
		die(
			$cl->createMobileDevice($_POST['create_mobile_device'], $os, $_POST['notes']??'')
		);
	}

	if(!empty($_POST['edit_mobile_device_id'])
	&& isset($_POST['device_name'])
	&& isset($_POST['notes'])) {
		$md = $cl->getMobileDevice($_POST['edit_mobile_device_id']);
		$cl->editMobileDevice($md->id, $_POST['device_name'], $_POST['notes'], $md->force_update);
		die();
	}

	if(!empty($_POST['edit_mobile_device_id'])
	&& isset($_POST['force_update'])) {
		$md = $cl->getMobileDevice($_POST['edit_mobile_device_id']);
		$cl->editMobileDevice($md->id, $md->device_name, $md->notes, $_POST['force_update']);
		die();
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
		$md = $cl->getMobileDevice($_POST['send_command_to_mobile_device_id']);
		if($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) {

			if(!in_array($_POST['command'], [
				'ScheduleOSUpdateScan', 'AvailableOSUpdates', 'ScheduleOSUpdate', 'OSUpdateStatus',
				'DeviceLock', 'EraseDevice', 'ClearPasscode', 'EnableLostMode', 'PlayLostModeSound', 'DisableLostMode'
			])) throw new InvalidRequestException('Unknown command');
			$parameter = [
				'RequestType' => $_POST['command'],
			];
			if($_POST['command'] == 'ScheduleOSUpdateScan') {
				$parameter['Force'] = true;
			} elseif($_POST['command'] == 'ScheduleOSUpdate') {
				$parameter['Updates'] = [
					[ 'InstallAction' => 'Default' ]
				];
			} elseif($_POST['command'] == 'DeviceLock') {
				#'Message' => '',
				#'PhoneNumber' => '',
				#'PIN' => '', // six-character PIN for Find My
			} elseif($_POST['command'] == 'EraseDevice') {
				#'PIN' => '', // six-character PIN for Find My
			} elseif($_POST['command'] == 'ClearPasscode') {
				$parameter['UnlockToken'] = base64_encode($md->unlock_token);
				$parameter['_data'] = ['UnlockToken'];
			} elseif($_POST['command'] == 'EnableLostMode') {
				if(empty($_POST['message']))
					throw new InvalidRequestException('A message is required for EnableLostMode command');
				$parameter['Message'] = $_POST['message'];
				#'Footnote' => '',
				#'PhoneNumber' => '',
			}
			$cl->createMobileDeviceCommand($_POST['send_command_to_mobile_device_id'], $_POST['command'], json_encode($parameter), null);
			die();

		} elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_ANDROID) {

			// manual permission check because CoreLogic->insertCommand is called after sending it to the Google API
			$cl->checkPermission($md, PermissionManager::METHOD_DEPLOY);

			if(!in_array($_POST['command'], [
				'LOCK', 'RESET_PASSWORD', 'REBOOT', 'RELINQUISH_OWNERSHIP',
				'CLEAR_APP_DATA', 'START_LOST_MODE', 'STOP_LOST_MODE'
			])) throw new InvalidRequestException('Unknown command');
			$parameter = [];
			if($_POST['command'] == 'RESET_PASSWORD') {
				if(empty($_POST['password']))
					throw new InvalidRequestException('A password is required for RESET_PASSWORD command');
				if(strlen($_POST['password']) < 6)
					throw new InvalidRequestException('The password must be at least 6 chars long');
				$parameter = [
					'newPassword' => $_POST['password'],
					'resetPasswordFlags' => []
				];
			} elseif($_POST['command'] == 'START_LOST_MODE') {
				if(empty($_POST['message']))
					throw new InvalidRequestException('A message is required for START_LOST_MODE command');
				$parameter = [
					'startLostModeParams' => [
						'lostMessage' => [ 'defaultMessage' => $_POST['message'] ]
					]
				];
			} elseif($_POST['command'] == 'STOP_LOST_MODE') {
				$parameter = [
					'stopLostModeParams' => []
				];
			}
			$ae = new Android\AndroidEnrollment($db);
			$commandId = $ae->issueCommand($md->udid, $_POST['command'], $parameter);
			$cl->createMobileDeviceCommand($md->id, $_POST['command'], json_encode($parameter), $commandId);
			die();

		}

	}

	if(!empty($_POST['remove_mobile_device_command_id']) && is_array($_POST['remove_mobile_device_command_id'])) {
		foreach($_POST['remove_mobile_device_command_id'] as $id) {
			// TODO
			$cl->removeMobileDeviceCommand($id);
		}
		die();
	}

	if(isset($_POST['edit_profile_id'])
	&& isset($_POST['name'])
	&& isset($_POST['type'])) {
		// no payload change by default
		$payload = null;
		if(!empty($_POST['update_payload'])) {
			if(!empty($_POST['payload_text'])) {
				$payload = $_POST['payload_text'];
			} elseif(!empty($_FILES['payload']) && is_array($_FILES['payload']['tmp_name'])) {
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
				$cl->createProfile($_POST['type'], $_POST['name'], $payload, $_POST['notes']??'')
			);
		} else {
			$cl->editProfile($_POST['edit_profile_id'], $_POST['type'], $_POST['name'], $payload, $_POST['notes']??'');
			die();
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

	if(isset($_POST['remove_from_group_id']) && is_array($_POST['remove_from_group_id']) && isset($_POST['remove_from_group_profile_id']) && is_array($_POST['remove_from_group_profile_id'])) {
		foreach($_POST['remove_from_group_profile_id'] as $pid) {
			foreach($_POST['remove_from_group_id'] as $gid) {
				$cl->removeProfileFromMobileDeviceGroup($pid, $gid);
			}
		}
		die();
	}

	if(isset($_POST['add_to_group_id']) && is_array($_POST['add_to_group_id']) && isset($_POST['add_to_group_managed_app_id']) && is_array($_POST['add_to_group_managed_app_id'])) {
		foreach($_POST['add_to_group_managed_app_id'] as $pid) {
			foreach($_POST['add_to_group_id'] as $gid) {
				$cl->assignManagedAppToMobileDeviceGroup($pid, $gid,
					($_POST['removable']??1) ? 1 : 0,
					($_POST['disable_cloud_backup']??0) ? 1 : 0,
					($_POST['remove_on_mdm_remove']??1) ? 1 : 0,
					empty($_POST['install_type']) ? null : $_POST['install_type'],
					$_POST['config'] ?? null,
				);
			}
		}
		die();
	}

	if(isset($_POST['remove_from_group_id']) && is_array($_POST['remove_from_group_id']) && isset($_POST['remove_from_group_managed_app_id']) && is_array($_POST['remove_from_group_managed_app_id'])) {
		foreach($_POST['remove_from_group_managed_app_id'] as $pid) {
			foreach($_POST['remove_from_group_id'] as $gid) {
				$cl->removeManagedAppFromMobileDeviceGroup($pid, $gid);
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

	if(isset($_POST['playstore_onproductselect'])
	&& !empty($_POST['package_name'])
	&& !empty($_POST['product_id'])) {
		die(
			$cl->createOrEditManagedApp('android', $_POST['package_name'], $_POST['product_id'], $_POST['app_name']??'?', null)
		);
	}

	if(!empty($_POST['remove_managed_app_id']) && is_array($_POST['remove_managed_app_id'])) {
		foreach($_POST['remove_managed_app_id'] as $id) {
			$cl->removeManagedApp($id);
		}
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die(htmlspecialchars($e->getMessage()));
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
