<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditSystemUserId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('uid'); ?></th>
		<td><input type='text' class='fullwidth' id='txtEditSystemUserUid' disabled='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserUsername' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('display_name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserDisplayName'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditSystemUserDescription'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('role'); ?></th>
		<td>
			<select class='fullwidth' id='sltEditSystemUserRole'>
				<?php foreach($cl->getSystemUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>'><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserNewPassword'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditSystemUserConfirmNewPassword'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditSystemUser' class='primary' onclick='
	if(txtEditSystemUserNewPassword.value!=txtEditSystemUserConfirmNewPassword.value)
	{emitMessage(L__PASSWORDS_DO_NOT_MATCH, "", MESSAGE_TYPE_WARNING);return false;}
	editSystemUser(
		txtEditSystemUserId.value,
		txtEditSystemUserUsername.value,
		txtEditSystemUserDisplayName.value,
		txtEditSystemUserDescription.value,
		txtEditSystemUserNewPassword.value,
		sltEditSystemUserRole.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnEditSystemUser'><?php echo LANG('change'); ?></span></button>
</div>
