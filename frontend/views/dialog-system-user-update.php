<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditSystemuserId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['username']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditSystemuserUsername' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['full_name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditSystemuserFullname'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditSystemuserDescription'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['new_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditSystemuserNewPassword'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['confirm_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditSystemuserConfirmNewPassword'></input></td>
	</tr>
	<tr>
		<th></th>
		<td><button id='btnCreateUser' class='fullwidth' onclick='if(txtEditSystemuserNewPassword.value!=txtEditSystemuserConfirmNewPassword.value){emitMessage(L__PASSWORDS_DO_NOT_MATCH, "", MESSAGE_TYPE_WARNING);return false;} editSystemuser(txtEditSystemuserId.value, txtEditSystemuserUsername.value, txtEditSystemuserFullname.value, txtEditSystemuserDescription.value, txtEditSystemuserNewPassword.value)'><img src='img/edit.svg'>&nbsp;<?php echo LANG['change']; ?></button></td>
	</tr>
</table>
