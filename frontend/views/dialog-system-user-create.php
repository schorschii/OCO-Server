<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['username']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtCreateSystemUserUsername' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['display_name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtCreateSystemUserDisplayName'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtCreateSystemUserDescription'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['role']; ?></th>
		<td>
			<select class='fullwidth' id='sltCreateSystemUserRole'>
				<?php foreach($cl->getSystemUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>'><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtCreateSystemUserPasswordPassword'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['confirm_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtCreateSystemUserConfirmPassword'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick="hideDialog();showLoader(false);showLoader2(false);"><img src="img/close.dyn.svg">&nbsp;<?php echo LANG['close']; ?></button>
	<button id='btnCreateUser' class='primary' onclick='if(txtCreateSystemUserPasswordPassword.value!=txtCreateSystemUserConfirmPassword.value){emitMessage(L__PASSWORDS_DO_NOT_MATCH, "", MESSAGE_TYPE_WARNING);return false;} createSystemUser(txtCreateSystemUserUsername.value, txtCreateSystemUserDisplayName.value, txtCreateSystemUserDescription.value, txtCreateSystemUserPasswordPassword.value, sltCreateSystemUserRole.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
