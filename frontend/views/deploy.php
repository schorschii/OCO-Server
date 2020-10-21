<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

$select_computer_ids = [];
$select_computer_group_ids = [];
$select_package_ids = [];
$select_package_group_ids = [];
if(!empty($_GET['computer_id']) && is_array($_GET['computer_id'])) {
	$select_computer_ids = $_GET['computer_id'];
}
if(!empty($_GET['computer_group_id']) && is_array($_GET['computer_group_id'])) {
	$select_computer_group_ids = $_GET['computer_group_id'];
}
if(!empty($_GET['package_id']) && is_array($_GET['package_id'])) {
	$select_package_ids = $_GET['package_id'];
}
if(!empty($_GET['package_group_id']) && is_array($_GET['package_group_id'])) {
	$select_package_group_ids = $_GET['package_group_id'];
}

if(!empty($_POST['add_jobcontainer'])) {
	$computer_ids = [];
	$computer_group_ids = [];
	$package_group_ids = [];
	$packages = [];

	if(!empty($_POST['computer_id'])) foreach($_POST['computer_id'] as $computer_id) {
		if($db->getComputer($computer_id) !== null) $computer_ids[] = $computer_id;
	}
	if(!empty($_POST['computer_group_id'])) foreach($_POST['computer_group_id'] as $computer_group_id) {
		if($db->getComputerGroup($computer_group_id) !== null) $computer_group_ids[] = $computer_group_id;
	}
	if(!empty($_POST['package_id'])) foreach($_POST['package_id'] as $package_id) {
		$p = $db->getPackage($package_id);
		if($p !== null) $packages[] = ['id'=>$p->id, 'sequence'=>0, 'procedure'=>$p->install_procedure];
	}
	if(!empty($_POST['package_group_id'])) foreach($_POST['package_group_id'] as $package_group_id) {
		if($db->getPackageGroup($package_group_id) !== null) $package_group_ids[] = $package_group_id;
	}

	foreach($computer_group_ids as $computer_group_id) {
		foreach($db->getComputerByGroup($computer_group_id) as $computer) {
			$computer_ids[] = $computer->id;
		}
	}
	foreach($package_group_ids as $package_group_id) {
		foreach($db->getPackageByGroup($package_group_id) as $package) {
			$packages[] = ['id'=>$package->id, 'sequence'=>$package->sequence, 'procedure'=>$package->procedure];
		}
	}

	$jcid = $db->addJobContainer(
		$_POST['add_jobcontainer'],
		empty($_POST['date_start']) ? date('Y-m-d H:i:s') : $_POST['date_start'],
		empty($_POST['date_end']) ? null : $_POST['date_end'],
		$_POST['description']
	);
	foreach($computer_ids as $computer_id) {
		foreach($packages as $package) {
			$db->addJob($jcid, $computer_id, $package['id'], $package['procedure'], $package['sequence']);
		}
	}

	die();
}
?>

<h1>Bereitstellungs-Assistent</h1>

<table>
	<tr>
		<th>Name:</th>
		<td>
			<input type='text' id='txtName' value='Job <?php echo date('y-m-d H:i:s'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th>Start:</th>
		<td>
			<input type='date' id='dteStart' value='<?php echo date('Y-m-d'); ?>'></input>
			<input type='time' id='tmeStart' value='<?php echo date('H:i:s'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><label><input type='checkbox' id='chkDateEndEnabled'>Ende:</label></th>
		<td>
			<input type='date' id='dteEnd' value=''></input>
			<input type='time' id='tmeEnd' value=''></input>
		</td>
	</tr>
	<tr>
		<th>Beschreibung:</th>
		<td>
			<textarea id='txtDescription'></textarea>
		</td>
	</tr>
</table>

<h2>Zielcomputer</h2>
<div class='gallery'>
	<div>
		<h3>Computer</h3>
		<select id='computer' size='10' multiple='true'>
			<?php
			foreach($db->getAllComputer() as $computer) {
				$selected = '';
				if(in_array($computer->id, $select_computer_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($computer->id)."' ".$selected.">".htmlspecialchars($computer->hostname)."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<h3>Computergruppen</h3>
		<select id='computer_group' size='10' multiple='true'>
			<?php
			foreach($db->getAllComputerGroup() as $group) {
				$selected = '';
				if(in_array($group->id, $select_computer_group_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($group->id)."' ".$selected.">".htmlspecialchars($group->name)."</option>";
			}
			?>
		</select>
	</div>
</div>

<h2>Zu verteilende Pakete</h2>
<div class='gallery'>
	<div>
		<h3>Pakete</h3>
		<select id='package' size='10' multiple='true'>
			<?php
			foreach($db->getAllPackage() as $package) {
				$selected = '';
				if(in_array($package->id, $select_package_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($package->id)."' ".$selected.">".htmlspecialchars($package->name)."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<h3>Paketgruppen</h3>
		<select id='package_group' size='10' multiple='true'>
			<?php
			foreach($db->getAllPackageGroup() as $group) {
				$selected = '';
				if(in_array($group->id, $select_package_group_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($group->id)."' ".$selected.">".htmlspecialchars($group->name)."</option>";
			}
			?>
		</select>
	</div>
</div>

<p>
	<button onclick='deploy(txtName.value, dteStart.value+" "+tmeStart.value, chkDateEndEnabled.checked ? dteEnd.value+" "+tmeEnd.value : "", txtDescription.value, computer, computer_group, package, package_group)'><img src='img/send.svg'>&nbsp;Bereitstellen</button>
</p>
