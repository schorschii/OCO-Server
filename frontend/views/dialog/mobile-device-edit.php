<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$md = $cl->getMobileDevice($_GET['id'] ?? -1);
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

<input type='hidden' name='id' value='<?php echo htmlspecialchars($md->id??-1); ?>'>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('device_name'); ?></th>
		<td>
			<input type='text' name='name' class='fullwidth' value='<?php echo htmlspecialchars($md->device_name??''); ?>'></input>
			<?php if($md->getOsType() === Models\MobileDevice::OS_TYPE_IOS) { ?>
				<div class='alert info' style='width:350px; min-width:100%'><?php echo LANG('ios_device_name_update_note'); ?></div>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td>
			<textarea name='notes' class='fullwidth' autofocus='true'><?php echo htmlspecialchars($md->notes??''); ?></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
