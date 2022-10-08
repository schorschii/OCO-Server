<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

$ownSystemUser = $db->selectSystemUser($_SESSION['oco_user_id']);
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo htmlspecialchars(str_replace('%',$currentSystemUser->username,LANG('system_user_placeholder'))); ?></span></h1>
</div>

<div class='details-abreast'>
	<div>
		<div class='controls'>
			<button onclick='askNotificationPermission()'><img src='img/notification.dyn.svg'>&nbsp;<?php echo LANG('enable_notifications'); ?></button>
			<button onclick='showDialogEditOwnSystemUserPassword()' <?php if($currentSystemUser->ldap) echo 'disabled'; ?>><img src='img/password.dyn.svg'>&nbsp;<?php echo LANG('change_password'); ?></button>
			<span class='filler'></span>
		</div>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG('login_name'); ?>:</th>
				<td><?php echo htmlspecialchars($ownSystemUser->username); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('display_name'); ?>:</th>
				<td><?php echo htmlspecialchars($ownSystemUser->display_name); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('role'); ?>:</th>
				<td><?php echo htmlspecialchars($ownSystemUser->system_user_role_name); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('ldap_account'); ?>:</th>
				<td><?php if($ownSystemUser->ldap) echo LANG('yes'); else echo LANG('no'); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('locked'); ?>:</th>
				<td><?php if($ownSystemUser->locked) echo LANG('yes'); else echo LANG('no'); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('last_login'); ?>:</th>
				<td><?php echo htmlspecialchars($ownSystemUser->last_login); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('created'); ?>:</th>
				<td><?php echo htmlspecialchars($ownSystemUser->created); ?></td>
			</tr>
		</table>
	</div>
</div>

