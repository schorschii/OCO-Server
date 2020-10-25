<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

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


<h2><?php echo LANG['system_users']; ?></h2>
<table id='tblSystemuserData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th></th>
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
	echo "<td><input type='checkbox' name='systemuser_id[]' value='".$u->id."'></td>";
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
		<td colspan='999'><span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?></td>
	</tr>
</tfoot>
</table>

<p><?php echo LANG['selected_elements']; ?>:&nbsp;
	<button onclick='lockSelectedSystemuser("systemuser_id[]")'><img src='img/lock.svg'>&nbsp;<?php echo LANG['lock']; ?></button>
	<button onclick='unlockSelectedSystemuser("systemuser_id[]")'><img src='img/unlock.svg'>&nbsp;<?php echo LANG['unlock']; ?></button>
	<button onclick='confirmRemoveSelectedSystemuser("systemuser_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</p>
