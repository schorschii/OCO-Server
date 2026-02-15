<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$systemUser = null;
try {
	$systemUser = $cl->getSystemUser($_GET['id'] ?? -1);
	if($systemUser && !empty($systemUser->ldap))
		throw new InvalidRequestException(LANG('ldap_accounts_cannot_be_modified'));
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

<input type='hidden' name='id' value='<?php echo $systemUser->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('uid'); ?></th>
		<td><input type='text' class='fullwidth' name='uid' disabled='true' value='<?php echo $systemUser->uid??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='username' autofocus='true' value='<?php echo $systemUser->username??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('display_name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='display_name' value='<?php echo $systemUser->display_name??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' name='description'><?php echo $systemUser->description??''; ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('role'); ?></th>
		<td>
			<select class='fullwidth' name='system_user_role_id'>
				<?php foreach($cl->getSystemUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>' <?php if($systemUser && $role->id==$systemUser->system_user_role_id) echo 'selected'; ?>><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='new_password' placeholder='<?php if($systemUser) echo LANG('optional'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='new_password_confirm' placeholder='<?php if($systemUser) echo LANG('optional'); ?>'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo $systemUser ? LANG('change') : LANG('create'); ?></button>
</div>
