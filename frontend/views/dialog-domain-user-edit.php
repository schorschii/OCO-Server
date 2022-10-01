<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditDomainUserId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('uid'); ?></th>
		<td><input type='text' class='fullwidth' id='txtEditDomainUserUid' disabled='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserUsername' disabled='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('display_name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserDisplayName' disabled='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('role'); ?></th>
		<td>
			<select class='fullwidth' id='sltEditDomainUserRole' autofocus='true'>
				<option value=''>=== <?php echo LANG('no_role'); ?> ===</option>
				<?php foreach($cl->getDomainUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>'><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserNewPassword'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserConfirmNewPassword'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditDomainUser' class='primary' onclick='
	if(txtEditDomainUserNewPassword.value!=txtEditDomainUserConfirmNewPassword.value)
	{emitMessage(L__PASSWORDS_DO_NOT_MATCH, "", MESSAGE_TYPE_WARNING);return false;}
	editDomainUser(
		txtEditDomainUserId.value,
		txtEditDomainUserUsername.value,
		txtEditDomainUserNewPassword.value,
		sltEditDomainUserRole.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnEditDomainUser'><?php echo LANG('change'); ?></span></button>
</div>
