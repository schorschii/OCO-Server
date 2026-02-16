<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_PASSWORD_ROTATION_RULES);
	$prr = $db->selectPasswordRotationRule($_GET['id']??-1);
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('not_found'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('permission_denied'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($prr->id??-1,ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('computer_group'); ?></th>
		<td>
			<select class='fullwidth' name='computer_group_id'>
				<option value=''><?php echo htmlspecialchars('* '.LANG('all_computers').' *'); ?></option>
				<?php Html::buildGroupOptions($cl, new Models\ComputerGroup(), 0, $prr->computer_group_id??-1); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='username' autofocus='true' value='<?php echo htmlspecialchars($prr->username??'administrator',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('alphabet'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='alphabet' value='<?php echo htmlspecialchars($prr->alphabet??'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_+*.#=!',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('length'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' name='length' value='<?php echo htmlspecialchars($prr->length??'15',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('valid_for'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' name='valid_seconds' value='<?php echo htmlspecialchars($prr->valid_seconds??'2592000',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('history_count'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' name='history' value='<?php echo htmlspecialchars($prr->history??'5',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('initial_password_macos'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='default_password' value='<?php echo htmlspecialchars($prr->default_password??'',ENT_QUOTES); ?>'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo $prr ? LANG('change') : LANG('create'); ?></button>
</div>
