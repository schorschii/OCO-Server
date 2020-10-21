<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

$package = null;
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
}
if($package === null) die();
?>

<h1><?php echo htmlspecialchars($package->name); ?></h1>

<h2>Allgemein</h2>
<table class='list'>
	<tr>
		<th>Version</th>
		<td><?php echo htmlspecialchars($package->version); ?></td>
	</tr>
	<tr>
		<th>Autor</th>
		<td><?php echo htmlspecialchars($package->author); ?></td>
	</tr>
	<tr>
		<th>Beschreibung</th>
		<td><?php echo htmlspecialchars($package->notes); ?></td>
	</tr>
	<tr>
		<th>ZIP-Archiv</th>
		<td><?php echo htmlspecialchars($package->filename); ?></td>
	</tr>
	<tr>
		<th>Installations-Prozedur</th>
		<td><?php echo htmlspecialchars($package->install_procedure); ?></td>
	</tr>
	<tr>
		<th>Deinstallations-Prozedur</th>
		<td><?php echo htmlspecialchars($package->uninstall_procedure); ?></td>
	</tr>
	<tr>
		<th>Erstellt</th>
		<td><?php echo htmlspecialchars($package->created); ?></td>
	</tr>
</table>

<h2>Installiert auf</h2>
<table class='list'>
	<tr><th>Computer</th><th>Prozedur</th><th>Installationszeitpunkt</th></tr>
	<?php
	foreach($db->getPackageComputerInstallation($package->id) as $p) {
		echo '<tr>';
		echo '<td><a href="#" onclick="refreshContentComputerDetail('.$p->computer_id.')">'.htmlspecialchars($p->computer_hostname).'</a></td>';
		echo '<td>'.htmlspecialchars($p->installed_procedure).'</td>';
		echo '<td>'.htmlspecialchars($p->installed).'</td>';
		echo '</tr>';
	}
	?>
</table>
