<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$computer = $cl->getComputer($_GET['id'] ?? -1);
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

<input type='hidden' name='id' value='<?php echo htmlspecialchars($computer->id,ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('hostname'); ?></th>
		<td>
			<input type='text' name='hostname' class='fullwidth' autocomplete='new-password' autofocus='true' value='<?php echo htmlspecialchars($computer->hostname,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert warning' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('new_hostname_warning'); ?>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td>
			<textarea name='notes' class='fullwidth' autocomplete='new-password' rows='5'><?php echo htmlspecialchars($computer->notes); ?></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
