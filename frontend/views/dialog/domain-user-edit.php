<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$domainUser = null;
try {
	$domainUser = $cl->getDomainUser($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' name='id' value='<?php echo $domainUser->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('uid'); ?></th>
		<td><input type='text' class='fullwidth' name='uid' disabled='true' value='<?php echo htmlspecialchars($domainUser->uid??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='username' disabled='true' value='<?php echo htmlspecialchars($domainUser->username??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('display_name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='display_name' disabled='true' value='<?php echo htmlspecialchars($domainUser->display_name??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('role'); ?></th>
		<td>
			<select class='fullwidth' name='domain_user_role_id' autofocus='true' <?php if($domainUser&&!empty($domainUser->ldap)) echo 'disabled'; ?>>
				<option value=''>=== <?php echo LANG('no_role'); ?> ===</option>
				<?php foreach($cl->getDomainUserRoles() as $role) { ?>
					<option value='<?php echo htmlspecialchars($role->id); ?>' <?php if($domainUser && $role->id==$domainUser->domain_user_role_id) echo 'selected'; ?>><?php echo htmlspecialchars($role->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='password' placeholder='<?php if($domainUser) echo LANG('optional'); ?>' <?php if($domainUser&&!empty($domainUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='password_confirm' placeholder='<?php if($domainUser) echo LANG('optional'); ?>' <?php if($domainUser&&!empty($domainUser->ldap)) echo 'readonly'; ?>></input></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<span><?php echo LANG('change'); ?></span></button>
</div>
