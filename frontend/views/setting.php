<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

const GENERAL_SETTING_KEYS = [
	'agent-key',
	'agent-update-interval',
	'agent-registration-enabled',
	'purge-succeeded-jobs',
	'purge-failed-jobs'
];
foreach(GENERAL_SETTING_KEYS as $key) {
	if(isset($_POST[$key])) {
		$db->updateSetting($key, $_POST[$key]);
	}
}

if(!empty($_POST['add_systemuser_username'])) {
	$db->addSystemuser(
		$_POST['add_systemuser_username'],
		$_POST['add_systemuser_fullname'],
		password_hash($_POST['add_systemuser_password'], PASSWORD_DEFAULT),
		0/*ldap*/, ''/*email*/, ''/*mobile*/, ''/*phone*/, ''/*description*/, 0
	);
	die();
}
if(!empty($_POST['change_systemuser_id']) && !empty($_POST['change_systemuser_password'])) {
	$u = $db->getSystemuser($_POST['change_systemuser_id']);
	if($u != null) {
		$db->updateSystemuser(
			$u->id, $u->username, $u->fullname,
			password_hash($_POST['change_systemuser_password'], PASSWORD_DEFAULT),
			$u->ldap, $u->email, $u->phone, $u->mobile, $u->description, $u->locked
		);
	}
	die();
}
if(!empty($_POST['remove_systemuser_id']) && is_array($_POST['remove_systemuser_id'])) {
	foreach($_POST['remove_systemuser_id'] as $id) {
		$db->removeSystemuser($id);
	}
	die();
}
if(!empty($_POST['lock_systemuser_id']) && is_array($_POST['lock_systemuser_id'])) {
	foreach($_POST['lock_systemuser_id'] as $id) {
		$u = $db->getSystemuser($id);
		if($u != null) {
			$db->updateSystemuser(
				$u->id, $u->username, $u->fullname, $u->password, $u->ldap, $u->email, $u->phone, $u->mobile, $u->description, 1
			);
		}
	}
	die();
}
if(!empty($_POST['unlock_systemuser_id']) && is_array($_POST['unlock_systemuser_id'])) {
	foreach($_POST['unlock_systemuser_id'] as $id) {
		$u = $db->getSystemuser($id);
		if($u != null) {
			$db->updateSystemuser(
				$u->id, $u->username, $u->fullname, $u->password, $u->ldap, $u->email, $u->phone, $u->mobile, $u->description, 0
			);
		}
	}
	die();
}
?>

<h1><img src='img/settings.dyn.svg'><?php echo LANG['settings']; ?></h1>


<h2><?php echo LANG['general']; ?></h2>
<table class='form'>
	<tr>
		<th><?php echo LANG['agent_registration_enabled']; ?>:</th>
		<td>
			<input type='checkbox' id='chkAgentRegistrationEnabled' <?php if($db->getSettingByName('agent-registration-enabled')) echo 'checked'; ?>></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['agent_key']; ?>:</th>
		<td>
			<input type='text' autocomplete='new-password' id='txtAgentKey' value='<?php echo $db->getSettingByName('agent-key'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['agent_update_interval']; ?>:</th>
		<td>
			<input type='number' autocomplete='new-password' min='1' id='txtAgentUpdateInterval' value='<?php echo $db->getSettingByName('agent-update-interval'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['purge_succeeded_jobs_after']; ?>:</th>
		<td>
			<input type='number' autocomplete='new-password' min='1' id='txtPurgeSucceededJobsAfter' value='<?php echo $db->getSettingByName('purge-succeeded-jobs'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['purge_failed_jobs_after']; ?>:</th>
		<td>
			<input type='number' autocomplete='new-password' min='1' id='txtPurgeFailedJobsAfter' value='<?php echo $db->getSettingByName('purge-failed-jobs'); ?>'></input>
		</td>
	</tr>
</table>
<div class='controls'>
	<button id='btnSaveGeneralSettings' onclick='saveGeneralSettings()'><img src='img/send.svg'>&nbsp;<?php echo LANG['save']; ?></button>
</div>

<h2><?php echo LANG['system_users']; ?></h2>
<div class='controls'>
	<input type='text' autocomplete='new-password' id='txtUsername' placeholder='<?php echo LANG['username']; ?>'></input>
	<input type='text' autocomplete='new-password' id='txtFullname' placeholder='<?php echo LANG['full_name']; ?>'></input>
	<input type='password' autocomplete='new-password' id='txtPassword' placeholder='<?php echo LANG['password']; ?>'></input>
	<button id='btnCreateUser' onclick='createSystemuser(txtUsername.value, txtFullname.value, txtPassword.value)'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
<table id='tblSystemuserData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemuserData, this.checked)'></th>
		<th class='searchable sortable'><?php echo LANG['login_name']; ?></th>
		<th class='searchable sortable'><?php echo LANG['full_name']; ?></th>
		<th class='searchable sortable'><?php echo LANG['description']; ?></th>
	</tr>
</thead>
<?php
$counter = 0;
foreach($db->getAllSystemuser() as $u) {
	$counter ++;
	echo "<tr>";
	echo "<td><input type='checkbox' name='systemuser_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblSystemuserData)'></td>";
	echo "<td>";
	if($u->ldap) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG['ldap_account']."'>&nbsp;";
	if($u->locked) echo "<img src='img/lock.dyn.svg' title='".LANG['locked']."'>&nbsp;";
	echo  htmlspecialchars($u->username);
	echo "</td>";
	echo "<td>".htmlspecialchars($u->fullname)."</td>";
	echo "<td>".htmlspecialchars($u->description)."</td>";
	echo "</tr>";
}
?>
<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
		</td>
	</tr>
</tfoot>
</table>

<div class='controls'>
	<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
	<button id='btnChangePassword' onclick='changeSelectedSystemuserPassword("systemuser_id[]", txtNewPassword1.value, txtNewPassword2.value)'>
		<img src='img/edit.svg'>&nbsp;<?php echo LANG['change']; ?>
		<input type='password' autocomplete='new-password' onclick='event.stopPropagation()' placeholder='<?php echo LANG['new_password']; ?>' id='txtNewPassword1'>
		<input type='password' autocomplete='new-password' onclick='event.stopPropagation()' placeholder='<?php echo LANG['confirm_password']; ?>' id='txtNewPassword2'>
	</button>
	<button onclick='lockSelectedSystemuser("systemuser_id[]")'><img src='img/lock.svg'>&nbsp;<?php echo LANG['lock']; ?></button>
	<button onclick='unlockSelectedSystemuser("systemuser_id[]")'><img src='img/unlock.svg'>&nbsp;<?php echo LANG['unlock']; ?></button>
	<button onclick='confirmRemoveSelectedSystemuser("systemuser_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
