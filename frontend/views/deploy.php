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
		if($_POST['use_wol']) {
			foreach($db->getComputerNetwork($computer_id) as $n) {
				wol($n->mac);
			}
		}
		foreach($packages as $package) {
			$db->addJob($jcid, $computer_id, $package['id'], $package['procedure'], 0, $package['sequence']);
		}
	}

	die();
}

$computers = $db->getAllComputer();
$computerGroups = $db->getAllComputerGroup();
$packages = $db->getAllPackage();
$packageGroups = $db->getAllPackageGroup();
?>

<h1><?php echo LANG['deployment_assistant']; ?></h1>

<table class='form'>
	<tr>
		<th><?php echo LANG['name']; ?>:</th>
		<td>
			<input type='text' id='txtName' value='Job <?php echo date('y-m-d H:i:s'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['start']; ?>:<br><label><input type='checkbox' id='chkWol'><?php echo LANG['wol']; ?></label></th>
		<td>
			<input type='date' id='dteStart' value='<?php echo date('Y-m-d'); ?>'></input>
			<input type='time' id='tmeStart' value='<?php echo date('H:i:s'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><label><input type='checkbox' id='chkDateEndEnabled'><?php echo LANG['end']; ?>:</label></th>
		<td>
			<input type='date' id='dteEnd' value=''></input>
			<input type='time' id='tmeEnd' value=''></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?>:</th>
		<td>
			<textarea id='txtDescription'></textarea>
		</td>
	</tr>
</table>

<h2><?php echo LANG['target_computer']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['computer']; ?> (<span id='spnSelectedComputers'>0</span>/<?php echo count($computers); ?>)</h3>
		<select id='sltComputer' size='10' multiple='true' onchange='refreshDeployCount()'>
			<?php
			foreach($computers as $c) {
				$selected = '';
				if(in_array($c->id, $select_computer_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($c->id)."' ".$selected.">".htmlspecialchars($c->hostname)."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['computer_groups']; ?> (<span id='spnSelectedComputerGroups'>0</span>/<?php echo count($computerGroups); ?>)</h3>
		<select id='sltComputerGroup' size='10' multiple='true' onchange='refreshDeployCount()'>
			<?php
			foreach($computerGroups as $cg) {
				$selected = '';
				if(in_array($cg->id, $select_computer_group_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($cg->id)."' ".$selected.">".htmlspecialchars($cg->name)."</option>";
			}
			?>
		</select>
	</div>
</div>

<h2><?php echo LANG['packages_to_deploy']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<?php echo count($packages); ?>)</h3>
		<select id='sltPackage' size='10' multiple='true' onchange='refreshDeployCount()'>
			<?php
			foreach($packages as $package) {
				$selected = '';
				if(in_array($package->id, $select_package_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($package->id)."' ".$selected.">".htmlspecialchars($package->name)."</option>";
			}
			?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<?php echo count($packageGroups); ?>)</h3>
		<select id='sltPackageGroup' size='10' multiple='true' onchange='refreshDeployCount()'>
			<?php
			foreach($packageGroups as $group) {
				$selected = '';
				if(in_array($group->id, $select_package_group_ids)) $selected = 'selected';
				echo "<option value='".htmlspecialchars($group->id)."' ".$selected.">".htmlspecialchars($group->name)."</option>";
			}
			?>
		</select>
	</div>
</div>

<div class='controls'>
	<button onclick='deploy(txtName.value, dteStart.value+" "+tmeStart.value, chkDateEndEnabled.checked ? dteEnd.value+" "+tmeEnd.value : "", txtDescription.value, sltComputer, sltComputerGroup, sltPackage, sltPackageGroup, chkWol.checked)'><img src='img/send.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
</div>
