<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

const GENERAL_SETTING_KEYS = [
	'client-key',
	'client-update-interval',
	'client-registration-enabled',
	'purge-succeeded-jobs',
	'purge-failed-jobs'
];
foreach(GENERAL_SETTING_KEYS as $key) {
	if(isset($_POST[$key])) {
		$db->updateSetting($key, $_POST[$key]);
	}
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

<h1><?php echo LANG['settings']; ?></h1>


<h2><?php echo LANG['general']; ?></h2>
<table class='form'>
	<tr>
		<th><?php echo LANG['client_registration_enabled']; ?>:</th>
		<td>
			<input type='checkbox' id='chkClientRegistrationEnabled' <?php if($db->getSettingByName('client-registration-enabled')) echo 'checked'; ?>></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['client_key']; ?>:</th>
		<td>
			<input type='text' id='txtClientKey' value='<?php echo $db->getSettingByName('client-key'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['client_update_interval']; ?>:</th>
		<td>
			<input type='number' min='1' id='txtClientUpdateInterval' value='<?php echo $db->getSettingByName('client-update-interval'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['purge_succeeded_jobs_after']; ?>:</th>
		<td>
			<input type='number' min='1' id='txtPurgeSucceededJobsAfter' value='<?php echo $db->getSettingByName('purge-succeeded-jobs'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['purge_failed_jobs_after']; ?>:</th>
		<td>
			<input type='number' min='1' id='txtPurgeFailedJobsAfter' value='<?php echo $db->getSettingByName('purge-failed-jobs'); ?>'></input>
		</td>
	</tr>
</table>
<div class='controls'>
	<button onclick='saveGeneralSettings()'><img src='img/send.svg'>&nbsp;<?php echo LANG['save']; ?></button>
</div>

<h2><?php echo LANG['system_users']; ?></h2>
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
	<button onclick='lockSelectedSystemuser("systemuser_id[]")'><img src='img/lock.svg'>&nbsp;<?php echo LANG['lock']; ?></button>
	<button onclick='unlockSelectedSystemuser("systemuser_id[]")'><img src='img/unlock.svg'>&nbsp;<?php echo LANG['unlock']; ?></button>
	<button onclick='confirmRemoveSelectedSystemuser("systemuser_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
