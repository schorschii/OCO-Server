<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

if(!empty($_GET['download_profile'])) {
	try {
		$md = $cl->getMobileDevice($_GET['download_profile'] ?? -1);
	} catch(PermissionException $e) {
		die();
	}
	header('Content-Disposition: attachment; filename=oco-mdm-enrollment-profile-'.urlencode($md->device_name).'.mobileconfig');
	$ade = new Apple\AutomatedDeviceEnrollment($db);
	echo $ade->generateEnrollmentProfile($_GET['download_profile']);
	die();
}
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td>
			<input type='text' class='fullwidth' id='txtCreateMobileDeviceName' autofocus='true'>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('serial_no'); ?></th>
		<td>
			<input type='text' class='fullwidth' id='txtCreateMobileDeviceSerial'>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td>
			<textarea class='fullwidth' id='txtCreateMobileDeviceNotes'></textarea>
		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<div class='alert info' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('new_ios_device_info'); ?>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnCreateMobileDevice' class='primary' onclick='createMobileDeviceIos(
		txtCreateMobileDeviceName.value,
		txtCreateMobileDeviceSerial.value,
		txtCreateMobileDeviceNotes.value
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create'); ?></button>
</div>
