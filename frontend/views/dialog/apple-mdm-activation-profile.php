<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
	$settingValue = $db->settings->get('apple-mdm-activation-profile') ?? '';
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('permission_denied'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('not_found'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<div class='tabcontainer'>
	<div class='tabbuttons marginbottom'>
		<a href='#' name='form' class='active' onclick='event.preventDefault();openTab(this.parentElement.parentElement,this.getAttribute("name"))'>
			<?php echo LANG('form'); ?>
		</a>
		<a href='#' name='json' class='' onclick='event.preventDefault();openTab(this.parentElement.parentElement,this.getAttribute("name"))'>
			<?php echo LANG('json'); ?>
		</a>
	</div>
	<div class='tabcontents'>

		<div name='form' class='active'>
			<table>
			<tr>
				<th><?php echo LANG('name'); ?></th>
				<td><input type='text' class='fullwidth' name='profile_name' autofocus='true'></input></td>
			</tr>
			<tr>
				<th><?php echo LANG('url'); ?></th>
				<td><input type='text' class='fullwidth' name='url' placeholder='https://oco.example.com/api-mdm.php/profile' style='min-width:330px'></input></td>
			</tr>
			<tr>
				<th><?php echo LANG('support_email'); ?></th>
				<td><input type='text' class='fullwidth' name='support_email_address'></input></td>
			</tr>
			<tr>
				<th><?php echo LANG('is_supervised'); ?></th>
				<td><input type='checkbox' class='' name='is_supervised' checked></input></td>
			</tr>
			<tr>
				<th><?php echo LANG('is_mdm_removable'); ?></th>
				<td><input type='checkbox' class='' name='is_mdm_removable'></input></td>
			</tr>
			<tr>
				<th><?php echo LANG('language'); ?></th>
				<td><input type='text' class='fullwidth' name='language' placeholder='en'></input></td>
			</tr>
			<tr>
				<th><?php echo LANG('region'); ?></th>
				<td><input type='text' class='fullwidth' name='region' placeholder='US'></input></td>
			</tr>
			<tr>
				<th><a href='https://developer.apple.com/documentation/devicemanagement/skipkeys' target='_blank'><?php echo LANG('skip_setup_items'); ?></a></th>
				<td>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Accessibility'></input>Accessibility</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='AccessibilityAppearance'></input>AccessibilityAppearance</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='ActionButton'></input>ActionButton</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Android'></input>Android</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Appearance'></input>Appearance</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='AppleID'></input>AppleID</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='AppStore'></input>AppStore</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Biometric'></input>Biometric</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='CameraButton'></input>CameraButton</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='DeviceToDeviceMigration'></input>DeviceToDeviceMigration</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Diagnostics'></input>Diagnostics</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='EnableLockdownMode'></input>EnableLockdownMode</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='FileVault'></input>FileVault</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='iCloudDiagnostics'></input>iCloudDiagnostics</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='iCloudStorage'></input>iCloudStorage</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='iMessageAndFaceTime'></input>iMessageAndFaceTime</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Intelligence'></input>Intelligence</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='LiquidGlass'></input>LiquidGlass</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Location'></input>Location</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='MessagingActivationUsingPhoneNumber'></input>MessagingActivationUsingPhoneNumber</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Multitasking'></input>Multitasking</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='OSShowcase'></input>OSShowcase</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Passcode'></input>Passcode</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Payment'></input>Payment</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Privacy'></input>Privacy</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Restore'></input>Restore</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='RestoreCompleted'></input>RestoreCompleted</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Safety'></input>Safety</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='ScreenSaver'></input>ScreenSaver</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='ScreenTime'></input>ScreenTime</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='SIMSetup'></input>SIMSetup</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Siri'></input>Siri</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='SoftwareUpdate'></input>SoftwareUpdate</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='SpokenLanguage'></input>SpokenLanguage</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='TapToSetup'></input>TapToSetup</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Tips'></input>Tips</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='TOS'></input>TOS</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='TVHomeScreenSync'></input>TVHomeScreenSync</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='TVProviderSignIn'></input>TVProviderSignIn</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='TVRoom'></input>TVRoom</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='UnlockWithWatch'></input>UnlockWithWatch</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='UpdateCompleted'></input>UpdateCompleted</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='WatchMigration'></input>WatchMigration</label>
					<label class='block'><input type='checkbox' name='skip_setup_items' value='Welcome'></input>Welcome</label>
				</td>
			</tr>
			</table>
		</div>
		<div name='json' class=''>
			<textarea class='fullwidth monospace' name='json' rows='12' cols='45'><?php echo htmlspecialchars($settingValue); ?></textarea>
		</div>

	</div>
</div>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('save'); ?></span></button>
</div>
