<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['old_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditOwnSystemUserOldPassword' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['new_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditOwnSystemUserNewPassword'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['confirm_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtEditOwnSystemUserConfirmNewPassword'></input></td>
	</tr>
	<tr>
		<th></th>
		<td><button id='btnEditUser' class='fullwidth' onclick='if(txtEditOwnSystemUserNewPassword.value!=txtEditOwnSystemUserConfirmNewPassword.value){emitMessage(L__PASSWORDS_DO_NOT_MATCH, "", MESSAGE_TYPE_WARNING);return false;} editOwnSystemUserPassword(txtEditOwnSystemUserOldPassword.value, txtEditOwnSystemUserNewPassword.value)'><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['change']; ?></button></td>
	</tr>
</table>
