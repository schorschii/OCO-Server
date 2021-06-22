<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		$db->removeDomainuser($id);
	}
	die();
}

$domainuser = $db->getAllDomainuser();
?>

<h1><img src='img/users.dyn.svg'><?php echo LANG['all_domain_user']; ?></h1>


<table id='tblDomainuserData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDomainuserData, this.checked)'></th>
		<th class='searchable sortable'><?php echo LANG['login_name']; ?></th>
		<th class='searchable sortable'><?php echo LANG['logons']; ?></th>
		<th class='searchable sortable'><?php echo LANG['computers']; ?></th>
		<th class='searchable sortable'><?php echo LANG['last_login']; ?></th>
	</tr>
</thead>

<?php
$counter = 0;
foreach($domainuser as $u) {
	$counter ++;
	echo "<tr>";
	echo "<td><input type='checkbox' name='domainuser_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblDomainuserData)'></td>";
	echo "<td><a href='".explorerLink('views/domainuser-detail.php?id='.$u->id)."' onclick='event.preventDefault();refreshContentDomainuserDetail(\"".$u->id."\")'>".htmlspecialchars($u->username)."</a></td>";
	echo "<td>".htmlspecialchars($u->logon_amount)."</td>";
	echo "<td>".htmlspecialchars($u->computer_amount)."</td>";
	echo "<td>".htmlspecialchars($u->timestamp)."</td>";
	echo "</tr>";
}
?>

<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
			<a href='#' onclick='event.preventDefault();downloadTableCsv("tblDomainuserData")'><?php echo LANG['csv']; ?></a>
		</td>
	</tr>
</tfoot>
</table>


<div class='controls'>
	<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
	<button onclick='confirmRemoveSelectedDomainuser("domainuser_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
