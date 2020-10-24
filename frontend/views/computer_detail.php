<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['remove_package_assignment_id'])) {
	$db->removeComputerAssignedPackage($_POST['remove_package_assignment_id']);
	die();
}
if(!empty($_POST['uninstall_package_assignment_id'])) {
	$ap = $db->getComputerAssignedPackage($_POST['uninstall_package_assignment_id']);
	$p = $db->getPackage($ap->package_id);
	$jcid = $db->addJobContainer(
		'Uninstall '.date('y-m-d H:i:s'),
		date('Y-m-d H:i:s'), null, ''
	);
	$db->addJob($jcid, $ap->computer_id, $ap->package_id, $p->uninstall_procedure, 1, 0);
	die();
}

$computer = null;
if(!empty($_GET['id']))
	$computer = $db->getComputer($_GET['id']);

if($computer === null) die();
?>

<h1><?php echo htmlspecialchars($computer->hostname); ?></h1>

<h2><?php echo LANG['general']; ?></h2>
<table class='list'>
	<tr>
		<th><?php echo LANG['os']; ?></th>
		<td><?php echo htmlspecialchars($computer->os); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><?php echo htmlspecialchars($computer->os_version); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['kernel_version']; ?></th>
		<td><?php echo htmlspecialchars($computer->kernel_version); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['architecture']; ?></th>
		<td><?php echo htmlspecialchars($computer->architecture); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['cpu']; ?></th>
		<td><?php echo htmlspecialchars($computer->cpu); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['ram']; ?></th>
		<td><?php echo htmlspecialchars($computer->ram); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['serial_no']; ?></th>
		<td><?php echo htmlspecialchars($computer->serial); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['vendor']; ?></th>
		<td><?php echo htmlspecialchars($computer->manufacturer); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['model']; ?></th>
		<td><?php echo htmlspecialchars($computer->model); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['bios_version']; ?></th>
		<td><?php echo htmlspecialchars($computer->bios_version); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['boot_type']; ?></th>
		<td><?php echo htmlspecialchars($computer->boot_type); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['secure_boot']; ?></th>
		<td><?php echo htmlspecialchars($computer->secure_boot); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['agent_version']; ?></th>
		<td><?php echo htmlspecialchars($computer->agent_version); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['last_seen']; ?></th>
		<td><?php echo htmlspecialchars($computer->last_ping); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['last_updated']; ?></th>
		<td><?php echo htmlspecialchars($computer->last_update); ?></td>
	</tr>
</table>

<h2><?php echo LANG['logins']; ?></h2>
<table class='list'>
	<tr><th><?php echo LANG['computer']; ?></th><th><?php echo LANG['count']; ?></th><th><?php echo LANG['last_login']; ?></th></tr>
	<?php
	foreach($db->getDomainuserLogonByComputer($computer->id) as $logon) {
		echo "<tr>";
		echo "<td><a href='#' onclick='refreshContentDomainuserDetail(".$logon->domainuser_id.")'>".htmlspecialchars($logon->username)."</a></td>";
		echo "<td>".htmlspecialchars($logon->amount)."</td>";
		echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
		echo "</tr>";
	}
	?>
</table>

<h2><?php echo LANG['network']; ?></h2>
<table class='list'>
	<tr><th><?php echo LANG['ip_address']; ?></th><th><?php echo LANG['netmask']; ?></th><th><?php echo LANG['broadcast']; ?></th><th><?php echo LANG['mac_address']; ?></th><th><?php echo LANG['domain']; ?></th></tr>
	<?php
	foreach($db->getComputerNetwork($computer->id) as $n) {
		echo '<tr>';
		echo '<td>'.htmlspecialchars($n->addr).'</td>';
		echo '<td>'.htmlspecialchars($n->netmask).'</td>';
		echo '<td>'.htmlspecialchars($n->broadcast).'</td>';
		echo '<td>'.htmlspecialchars($n->mac).'</td>';
		echo '<td>'.htmlspecialchars($n->domain).'</td>';
		echo '</tr>';
	}
	?>
</table>

<h2><?php echo LANG['screens']; ?></h2>
<table class='list'>
	<tr><th><?php echo LANG['name']; ?></th><th><?php echo LANG['vendor']; ?></th><th>Typ</th><th><?php echo LANG['resolution']; ?></th></tr>
	<?php
	foreach($db->getComputerScreen($computer->id) as $s) {
		echo '<tr>';
		echo '<td>'.htmlspecialchars($s->name).'</a></td>';
		echo '<td>'.htmlspecialchars($s->manufacturer).'</td>';
		echo '<td>'.htmlspecialchars($s->type).'</td>';
		echo '<td>'.htmlspecialchars($s->resolution).'</td>';
		echo '</tr>';
	}
	?>
</table>

<h2><?php echo LANG['installed_packages']; ?></h2>
<table id='tblInstalledPackageData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['package']; ?></th>
			<th class='searchable sortable'><?php echo LANG['procedure']; ?></th>
			<th class='searchable sortable'><?php echo LANG['installation_date']; ?></th>
			<th><?php echo LANG['action']; ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$counter = 0;
	foreach($db->getComputerPackage($computer->id) as $p) {
		$counter ++;
		echo '<tr>';
		echo '<td><a href="#" onclick="refreshContentPackageDetail('.$p->package_id.')">'.htmlspecialchars($p->package_name).'</a></td>';
		echo '<td>'.htmlspecialchars($p->installed_procedure).'</td>';
		echo '<td>'.htmlspecialchars($p->installed).'</td>';
		echo '<td>';
		echo ' <button title="'.LANG['remove_assignment'].'" onclick="confirmRemovePackageComputerAssignment('.$p->id.')"><img src="img/remove.svg"></button>';
		echo ' <button title="'.LANG['uninstall_package'].'" onclick="confirmUninstallPackage('.$p->id.')"><img src="img/delete.svg"></button>';
		echo '</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='999'><span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?></td>
		</tr>
	</tfoot>
</table>

<h2><?php echo LANG['recognised_software']; ?></h2>
<table id='tblSoftwareInventoryData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['name']; ?></th>
			<th class='searchable sortable'><?php echo LANG['version']; ?></th>
			<th class='searchable sortable'><?php echo LANG['description']; ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$counter = 0;
	foreach($db->getComputerSoftware($computer->id) as $s) {
		$counter ++;
		echo '<tr>';
		echo '<td>'.htmlspecialchars($s->name).'</a></td>';
		echo '<td>'.htmlspecialchars($s->version).'</td>';
		echo '<td>'.htmlspecialchars($s->description).'</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='999'><span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?></td>
		</tr>
	</tfoot>
</table>
