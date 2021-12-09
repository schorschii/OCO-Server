<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['username']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtUsername' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['full_name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtFullname'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtPassword'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['confirm_password']; ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' id='txtConfirmPassword'></input></td>
	</tr>
	<tr>
		<th></th>
		<td><button id='btnCreateUser' class='fullwidth' onclick='if(txtPassword.value!=txtConfirmPassword.value){emitMessage(L__PASSWORDS_DO_NOT_MATCH, "", MESSAGE_TYPE_WARNING);return false;} createSystemuser(txtUsername.value, txtFullname.value, txtPassword.value)'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button></td>
	</tr>
</table>
