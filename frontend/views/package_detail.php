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

<h2><?php echo LANG['general']; ?></h2>
<table class='list'>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><?php echo htmlspecialchars($package->version); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['author']; ?></th>
		<td><?php echo htmlspecialchars($package->author); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><?php echo htmlspecialchars($package->notes); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['zip_archive']; ?></th>
		<td><?php echo htmlspecialchars($package->filename); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><?php echo htmlspecialchars($package->install_procedure); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><?php echo htmlspecialchars($package->uninstall_procedure); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG['created']; ?></th>
		<td><?php echo htmlspecialchars($package->created); ?></td>
	</tr>
</table>

<h2><?php echo LANG['installed_on']; ?></h2>
<table class='list'>
	<tr><th><?php echo LANG['computer']; ?></th><th><?php echo LANG['procedure']; ?></th><th><?php echo LANG['installation_date']; ?></th></tr>
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
