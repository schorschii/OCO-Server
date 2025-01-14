<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$domainUser = null;
try {
	$domainUser = $cl->getDomainUser($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' id='txtEditDomainUserId' value='<?php echo $domainUser->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('uid'); ?></th>
		<td><input type='text' class='fullwidth' id='txtEditDomainUserUid' disabled='true' value='<?php echo $domainUser->uid??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserUsername' disabled='true' value='<?php echo $domainUser->username??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('display_name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserDisplayName' disabled='true' value='<?php echo $domainUser->display_name??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('role'); ?></th>
		<td>
			<select class='fullwidth' id='sltEditDomainUserRole' autofocus='true' <?php if($domainUser&&!empty($domainUser->ldap)) echo 'disabled'; ?>>
				<option value=''>=== <?php echo LANG('no_role'); ?> ===</option>
				<?php foreach($cl->getDomainUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>' <?php if($domainUser && $role->id==$domainUser->domain_user_role_id) echo 'selected'; ?>><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserNewPassword' placeholder='<?php if($domainUser) echo LANG('optional'); ?>' <?php if($domainUser&&!empty($domainUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserConfirmNewPassword' placeholder='<?php if($domainUser) echo LANG('optional'); ?>' <?php if($domainUser&&!empty($domainUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditDomainUser' class='primary' onclick='
	if(txtEditDomainUserNewPassword.value!=txtEditDomainUserConfirmNewPassword.value)
	{emitMessage(LANG["passwords_do_not_match"], "", MESSAGE_TYPE_WARNING);return false;}
	editDomainUser(
		txtEditDomainUserId.value,
		txtEditDomainUserUsername.value,
		txtEditDomainUserNewPassword.value,
		sltEditDomainUserRole.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnEditDomainUser'><?php echo LANG('change'); ?></span></button>
</div>
