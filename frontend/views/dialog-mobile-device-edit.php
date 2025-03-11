<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$md = $cl->getMobileDevice($_GET['id'] ?? -1);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<input type='hidden' id='txtEditMobileDeviceId' value='<?php echo htmlspecialchars($md->id??-1); ?>'>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('device_name'); ?></th>
		<td>
			<input type='text' id='txtEditMobileDeviceName' class='fullwidth' value='<?php echo htmlspecialchars($md->device_name??''); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td>
			<textarea id='txtEditMobileDeviceNotes' class='fullwidth' autofocus='true'><?php echo htmlspecialchars($md->notes??''); ?></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditMobileDevice' class='primary' onclick='editMobileDevice(
	txtEditMobileDeviceId.value,
	txtEditMobileDeviceName.value,
	txtEditMobileDeviceNotes.value
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
</div>
