<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$default_job_container_name = '';
$select_computer_ids = [];
$select_computer_group_ids = [];
$select_package_ids = [];
$select_package_group_ids = [];
if(!empty($_GET['computer_id']) && is_array($_GET['computer_id'])) {
	$select_computer_ids = $_GET['computer_id'];
	// compile job name
	foreach($select_computer_ids as $id) {
		$c = $db->getComputer($id);
		if($c == null) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$c->hostname;
		else $default_job_container_name .= ', '.$c->hostname;
	}
}
if(!empty($_GET['computer_group_id']) && is_array($_GET['computer_group_id'])) {
	$select_computer_group_ids = $_GET['computer_group_id'];
	// compile job name
	foreach($select_computer_group_ids as $id) {
		$cg = $db->getComputerGroup($id);
		if($cg == null) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$cg->name;
		else $default_job_container_name .= ', '.$cg->name;
	}
}
if(!empty($_GET['package_id']) && is_array($_GET['package_id'])) {
	$select_package_ids = $_GET['package_id'];
	// compile job name
	foreach($select_package_ids as $id) {
		$p = $db->getPackage($id);
		if($p == null) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$p->name;
		else $default_job_container_name .= ', '.$p->name;
	}
}
if(!empty($_GET['package_group_id']) && is_array($_GET['package_group_id'])) {
	$select_package_group_ids = $_GET['package_group_id'];
	// compile job name
	foreach($select_package_group_ids as $id) {
		$pg = $db->getPackageGroup($id);
		if($pg == null) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$pg->name;
		else $default_job_container_name .= ', '.$pg->name;
	}
}

// compile job name
if(empty($default_job_container_name)) {
	$default_job_container_name = LANG['install'].' '.date('y-m-d H:i:s');
}

// ----- refresh list content if requested -----
if(isset($_GET['get_computer_group_members'])) {
	$group = $db->getComputerGroup($_GET['get_computer_group_members']);
	$computers = [];
	if(empty($group)) $computers = $db->getAllComputer();
	else $computers = $db->getComputerByGroup($group->id);
	foreach($computers as $c) {
		$selected = '';
		if(!empty($group) || in_array($c->id, $select_computer_ids)) $selected = 'selected';
		echo "<option value='".$c->id."' ".$selected.">".htmlspecialchars($c->hostname)."</option>";
	}
	die();
}
if(isset($_GET['get_package_group_members'])) {
	$group = $db->getPackageGroup($_GET['get_package_group_members']);
	$packages = [];
	if(empty($group)) $packages = $db->getAllPackage(true);
	else $packages = $db->getPackageByGroup($group->id);
	foreach($packages as $p) {
		$selected = '';
		if(!empty($group) || in_array($p->id, $select_package_ids)) $selected = 'selected';
		echo "<option value='".$p->id."' ".$selected.">".htmlspecialchars($p->name)." (".htmlspecialchars($p->version).")"."</option>";
	}
	die();
}

// ----- create jobs if requested -----
if(isset($_POST['add_jobcontainer'])) {
	try {
		$jcid = $cl->deploy(
			$_POST['add_jobcontainer'], $_POST['description'], $_SESSION['um_username'],
			$_POST['computer_id'] ?? [], $_POST['computer_group_id'] ?? [], $_POST['package_id'] ?? [], $_POST['package_group_id'] ?? [],
			$_POST['date_start'], $_POST['date_end'] ?? null,
			$_POST['use_wol'] ?? 1, $_POST['shutdown_waked_after_completion'] ?? 0, $_POST['restart_timeout'] ?? 5,
			$_POST['auto_create_uninstall_jobs'] ?? 1, $_POST['auto_create_uninstall_jobs_same_version'] ?? 0,
			$_POST['sequence_mode'] ?? 0, $_POST['priority'] ?? 0
		);
		die(strval(intval($jcid)));
	} catch(Exception $e) {
		header('HTTP/1.1 400 Invalid Request');
		die($e->getMessage());
	}
}
?>

<h1><img src='img/deploy.dyn.svg'><?php echo LANG['deployment_assistant']; ?></h1>

<table class='form'>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td>
			<input type='text' id='txtName' value='<?php echo htmlspecialchars($default_job_container_name); ?>'></input>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG['start']; ?></th>
		<td>
			<input type='date' id='dteStart' value='<?php echo date('Y-m-d'); ?>'></input>
			<input type='time' id='tmeStart' value='<?php echo date('H:i'); ?>'></input>
		</td>

		<th><?php echo LANG['end']; ?></th>
		<td>
			<input type='date' id='dteEnd' value='' onchange='chkDateEndEnabled.checked=true'></input>
			<input type='time' id='tmeEnd' value='' onchange='chkDateEndEnabled.checked=true'></input>
		</td>
	</tr>
	<tr>
		<th></th>
		<td>
			<label><input type='checkbox' id='chkWol' onclick='if(this.checked) {chkShutdownWakedAfterCompletion.disabled=false;} else {chkShutdownWakedAfterCompletion.checked=false; chkShutdownWakedAfterCompletion.disabled=true;}' <?php if(!empty($db->getSettingByName('default-use-wol'))) echo 'checked'; ?>><?php echo LANG['send_wol']; ?></label>
			<br/>
			<label title='<?php echo LANG['shutdown_waked_after_completion']; ?>'><input type='checkbox' id='chkShutdownWakedAfterCompletion' <?php if(!empty($db->getSettingByName('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG['shutdown_waked_computers']; ?></label>
		</td>
		<th></th>
		<td>
			<label><input type='checkbox' id='chkDateEndEnabled'><?php echo LANG['set_end']; ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td>
			<textarea id='txtDescription'></textarea>
		</td>
		<th><?php echo LANG['sequence_mode']; ?></th>
		<td>
			<label><input type='radio' name='sequence_mode' value='<?php echo JobContainer::SEQUENCE_MODE_IGNORE_FAILED; ?>' checked='true'>&nbsp;<?php echo LANG['ignore_failed']; ?></label><br>
			<label><input type='radio' name='sequence_mode' value='<?php echo JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED; ?>'>&nbsp;<?php echo LANG['abort_after_failed']; ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['priority']; ?></th>
		<td>
			<div class='inputWithLabel' title='<?php echo LANG['priority_description']; ?>'>
				<input id='sldPriority' type='range' min='-10' max='10' value='0' oninput='lblPriorityPreview.innerText=this.value' onchange='lblPriorityPreview.innerText=this.value'>
				<div id='lblPriorityPreview'>0</div>
			</div>
		</td>
		<th><?php echo LANG['timeout_for_reboot']; ?></th>
		<td>
			<div class='inputWithLabel' title='<?php echo LANG['timeout_for_reboot_description']; ?>'>
				<input type='number' id='txtRestartTimeout' value='<?php echo htmlspecialchars($db->getSettingByName('default-restart-timeout')); ?>' min='-1'></input>
				<div><?php echo LANG['minutes']; ?></div>
			</div>
		</td>
	</tr>
</table>

<h2><img src='img/computer.dyn.svg'><?php echo LANG['target_computer']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['computer_groups']; ?> (<span id='spnSelectedComputerGroups'>0</span>/<span id='spnTotalComputerGroups'>0</span>)</h3>
		<select id='sltComputerGroup' size='10' multiple='true' onchange='if(getSelectValues(this).length > 1) { sltComputer.innerHTML="";sltComputer.disabled=true;refreshDeployCount(); }else{ sltComputer.disabled=false;refreshDeployComputerAndPackages(this.value, null); }'>
			<option value='-1'><?php echo LANG['all_computer']; ?></option>
			<?php echoTargetComputerGroupOptions($db, $select_computer_group_ids); ?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['computer']; ?> (<span id='spnSelectedComputers'>0</span>/<span id='spnTotalComputers'>0</span>)</h3>
		<select id='sltComputer' size='10' multiple='true' onchange='refreshDeployCount()'>
			<!-- filled by JS -->
		</select>
	</div>
</div>

<h2><img src='img/package.dyn.svg'><?php echo LANG['packages_to_deploy']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<span id='spnTotalPackageGroups'>0</span>)</h3>
		<select id='sltPackageGroup' size='10' multiple='true' onchange='if(getSelectValues(this).length > 1) { sltPackage.innerHTML="";sltPackage.disabled=true;refreshDeployCount(); }else{ sltPackage.disabled=false;refreshDeployComputerAndPackages(null, this.value) }'>
			<option value='-1'><?php echo LANG['all_packages']; ?></option>
			<?php echoTargetPackageGroupOptions($db, $select_package_group_ids); ?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<select id='sltPackage' size='10' multiple='true' onchange='refreshDeployCount()'>
			<!-- filled by JS -->
		</select>
	</div>
</div>

<div class='margintop'>
	<div><label><input type='checkbox' id='chkAutoCreateUninstallJobsForeignVersion' <?php if(!empty($db->getSettingByName('default-auto-create-uninstall-jobs'))) echo 'checked'; ?>>&nbsp;<?php echo LANG['auto_create_uninstall_jobs']; ?></label></div>
	<div><label><input type='checkbox' id='chkAutoCreateUninstallJobsSameVersion' <?php if(!empty($db->getSettingByName('default-auto-create-uninstall-jobs-same-version'))) echo 'checked'; ?>>&nbsp;<?php echo LANG['auto_create_uninstall_jobs_for_same_version']; ?></label></div>
</div>
<div class='controls'>
	<button id='btnDeploy' onclick='deploy(txtName.value, dteStart.value+" "+tmeStart.value, chkDateEndEnabled.checked ? dteEnd.value+" "+tmeEnd.value : "", txtDescription.value, sltComputer, sltComputerGroup, sltPackage, sltPackageGroup, chkWol.checked, chkShutdownWakedAfterCompletion.checked, chkAutoCreateUninstallJobsForeignVersion.checked, chkAutoCreateUninstallJobsSameVersion.checked, txtRestartTimeout.value, getCheckedRadioValue("sequence_mode"), sldPriority.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
</div>

<?php
function echoTargetComputerGroupOptions($db, $select_computer_group_ids, $parent=null, $indent=0) {
	foreach($db->getAllComputerGroup($parent) as $cg) {
		$selected = '';
		if(in_array($cg->id, $select_computer_group_ids)) $selected = 'selected';
		echo "<option value='".htmlspecialchars($cg->id)."' ".$selected.">".trim(str_repeat("‒",$indent)." ".htmlspecialchars($cg->name))."</option>";
		echoTargetComputerGroupOptions($db, $select_computer_group_ids, $cg->id, $indent+1);
	}
}
function echoTargetPackageGroupOptions($db, $select_package_group_ids, $parent=null, $indent=0) {
	foreach($db->getAllPackageGroup($parent) as $pg) {
		$selected = '';
		if(in_array($pg->id, $select_package_group_ids)) $selected = 'selected';
		echo "<option value='".htmlspecialchars($pg->id)."' ".$selected.">".trim(str_repeat("‒",$indent)." ".htmlspecialchars($pg->name))."</option>";
		echoTargetPackageGroupOptions($db, $select_package_group_ids, $pg->id, $indent+1);
	}
}
