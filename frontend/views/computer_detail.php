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
	$db->addJob($jcid, $ap->computer_id, $ap->package_id, $p->uninstall_procedure, 0);
	die();
}

$computer = null;
if(!empty($_GET['id']))
  $computer = $db->getComputer($_GET['id']);

if($computer === null) die();
?>

<h1><?php echo htmlspecialchars($computer->hostname); ?></h1>

<h2>Allgemein</h2>
<table class='list'>
	<tr>
		<th>Betriebssystem</th>
		<td><?php echo htmlspecialchars($computer->os); ?></td>
	</tr>
	<tr>
		<th>Version</th>
		<td><?php echo htmlspecialchars($computer->os_version); ?></td>
	</tr>
	<tr>
		<th>Kernel-Version</th>
		<td><?php echo htmlspecialchars($computer->kernel_version); ?></td>
	</tr>
	<tr>
		<th>Architektur</th>
		<td><?php echo htmlspecialchars($computer->architecture); ?></td>
	</tr>
	<tr>
		<th>CPU</th>
		<td><?php echo htmlspecialchars($computer->cpu); ?></td>
	</tr>
	<tr>
		<th>RAM</th>
		<td><?php echo htmlspecialchars($computer->ram); ?></td>
	</tr>
	<tr>
		<th>Seriennummer</th>
		<td><?php echo htmlspecialchars($computer->serial); ?></td>
	</tr>
	<tr>
		<th>Hersteller</th>
		<td><?php echo htmlspecialchars($computer->manufacturer); ?></td>
	</tr>
	<tr>
		<th>Modell</th>
		<td><?php echo htmlspecialchars($computer->model); ?></td>
	</tr>
	<tr>
		<th>BIOS-Version</th>
		<td><?php echo htmlspecialchars($computer->bios_version); ?></td>
	</tr>
	<tr>
		<th>Boot-Typ</th>
		<td><?php echo htmlspecialchars($computer->boot_type); ?></td>
	</tr>
	<tr>
		<th>Secure-Boot</th>
		<td><?php echo htmlspecialchars($computer->secure_boot); ?></td>
	</tr>
	<tr>
		<th>Agent-Version</th>
		<td><?php echo htmlspecialchars($computer->agent_version); ?></td>
	</tr>
	<tr>
		<th>Zuletzt gesehen</th>
		<td><?php echo htmlspecialchars($computer->last_ping); ?></td>
	</tr>
	<tr>
		<th>Zuletzt aktualisiert</th>
		<td><?php echo htmlspecialchars($computer->last_update); ?></td>
	</tr>
</table>

<h2>Anmeldungen</h2>
<table class='list'>
	<tr><th>Computer</th><th>Anzahl</th><th>Letzte Anmeldung</th></tr>
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

<h2>Netzwerk</h2>
<table class='list'>
	<tr><th>IP-Adresse</th><th>Netzmaske</th><th>Broadcast</th><th>MAC-Adresse</th><th>Domain</th></tr>
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

<h2>Bildschirme</h2>
<table class='list'>
	<tr><th>Name</th><th>Hersteller</th><th>Typ</th><th>Auflösung</th></tr>
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

<h2>Installierte Pakete</h2>
<table id='tblInstalledPackageData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'>Paket</th>
			<th class='searchable sortable'>Prozedur</th>
			<th class='searchable sortable'>Installationszeitpunkt</th>
			<th>Aktion</th>
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
		echo ' <button title="Zuweisung entfernen" onclick="confirmRemovePackageComputerAssignment('.$p->id.')"><img src="img/remove.svg"></button>';
		echo ' <button title="Paket deinstallieren" onclick="confirmUninstallPackage('.$p->id.')"><img src="img/delete.svg"></button>';
		echo '</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='999'><span class='counter'><?php echo $counter; ?></span> Element(e)</td>
		</tr>
	</tfoot>
</table>

<h2>Erkannte Software</h2>
<table id='tblSoftwareInventoryData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'>Name</th>
			<th class='searchable sortable'>Version</th>
			<th class='searchable sortable'>Beschreibung</th>
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
			<td colspan='999'><span class='counter'><?php echo $counter; ?></span> Element(e)</td>
		</tr>
	</tfoot>
</table>
