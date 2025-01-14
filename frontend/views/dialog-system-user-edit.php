<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$systemUser = null;
try {
	$systemUser = $cl->getSystemUser($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' id='txtEditSystemUserId' value='<?php echo $systemUser->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('uid'); ?></th>
		<td><input type='text' class='fullwidth' id='txtEditSystemUserUid' disabled='true' value='<?php echo $systemUser->uid??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserUsername' autofocus='true' value='<?php echo $systemUser->username??''; ?>' <?php if($systemUser&&!empty($systemUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('display_name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserDisplayName' value='<?php echo $systemUser->display_name??''; ?>' <?php if($systemUser&&!empty($systemUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditSystemUserDescription' <?php if($systemUser&&!empty($systemUser->ldap)) echo 'readonly'; ?>><?php echo $systemUser->description??''; ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('role'); ?></th>
		<td>
			<select class='fullwidth' id='sltEditSystemUserRole' <?php if($systemUser&&!empty($systemUser->ldap)) echo 'disabled'; ?>>
				<?php foreach($cl->getSystemUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>' <?php if($systemUser && $role->id==$systemUser->system_user_role_id) echo 'selected'; ?>><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserNewPassword' placeholder='<?php if($systemUser) echo LANG('optional'); ?>' <?php if($systemUser&&!empty($systemUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserConfirmNewPassword' placeholder='<?php if($systemUser) echo LANG('optional'); ?>' <?php if($systemUser&&!empty($systemUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditSystemUser' class='primary' onclick='
	if(txtEditSystemUserNewPassword.value!=txtEditSystemUserConfirmNewPassword.value)
	{emitMessage(LANG["passwords_do_not_match"], "", MESSAGE_TYPE_WARNING);return false;}
	editSystemUser(
		txtEditSystemUserId.value,
		txtEditSystemUserUsername.value,
		txtEditSystemUserDisplayName.value,
		txtEditSystemUserDescription.value,
		txtEditSystemUserNewPassword.value,
		sltEditSystemUserRole.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnEditSystemUser'><?php echo $systemUser ? LANG('change') : LANG('create'); ?></span></button>
</div>
