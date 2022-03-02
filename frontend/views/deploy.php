<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$default_job_container_name = '';
$select_computer_group_ids = [];
$select_package_group_ids = [];
if(!empty($_GET['computer_id']) && is_array($_GET['computer_id'])) {
	// compile job name
	foreach($_GET['computer_id'] as $id) {
		$c = $db->getComputer($id);
		if(!$currentSystemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$c->hostname;
		else $default_job_container_name .= ', '.$c->hostname;
	}
}
if(!empty($_GET['package_id']) && is_array($_GET['package_id'])) {
	// compile job name
	foreach($_GET['package_id'] as $id) {
		$p = $db->getPackage($id);
		if(!$currentSystemUser->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$p->package_family_name;
		else $default_job_container_name .= ', '.$p->package_family_name;
	}
}
if(!empty($_GET['computer_group_id']) && is_array($_GET['computer_group_id'])) {
	$select_computer_group_ids = $_GET['computer_group_id'];
	// compile job name
	foreach($select_computer_group_ids as $id) {
		$cg = $db->getComputerGroup($id);
		if(!$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$cg->name;
		else $default_job_container_name .= ', '.$cg->name;
	}
}
if(!empty($_GET['package_group_id']) && is_array($_GET['package_group_id'])) {
	$select_package_group_ids = $_GET['package_group_id'];
	// compile job name
	foreach($select_package_group_ids as $id) {
		$pg = $db->getPackageGroup($id);
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$pg->name;
		else $default_job_container_name .= ', '.$pg->name;
	}
}

// compile job name
if(empty($default_job_container_name)) {
	$default_job_container_name = LANG['install'].' '.date('y-m-d H:i:s');
}
?>

<h1><img src='img/deploy.dyn.svg'><span id='page-title'><?php echo LANG['deployment_assistant']; ?></span></h1>

<div id='frmDeploy'>

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
			<label><input type='checkbox' id='chkWol' onclick='if(this.checked) {chkShutdownWakedAfterCompletion.disabled=false;} else {chkShutdownWakedAfterCompletion.checked=false; chkShutdownWakedAfterCompletion.disabled=true;}' <?php if(!empty(DEFAULTS['default-use-wol'])) echo 'checked'; ?>><?php echo LANG['send_wol']; ?></label>
			<br/>
			<label title='<?php echo LANG['shutdown_waked_after_completion']; ?>'><input type='checkbox' id='chkShutdownWakedAfterCompletion' <?php if(!empty(DEFAULTS['default-shutdown-waked-after-completion'])) echo 'checked'; else echo 'disabled' ?>><?php echo LANG['shutdown_waked_computers']; ?></label>
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
				<input type='number' id='txtRestartTimeout' value='<?php echo htmlspecialchars(DEFAULTS['default-restart-timeout']); ?>' min='-1'></input>
				<div><?php echo LANG['minutes']; ?></div>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['agent_ip_range']; ?></th>
		<td>
			<input type='text' id='txtConstraintIpRange' placeholder='<?php echo LANG['example'].':'; ?> 192.168.2.0/24, 10.0.0.0/8'></input>
		</td>
	</tr>
</table>

<h2><img src='img/computer.dyn.svg'><?php echo LANG['target_computer']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['computer_groups']; ?> (<span id='spnSelectedComputerGroups'>0</span>/<span id='spnTotalComputerGroups'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divComputerGroupList, this.checked);refreshDeployComputerList()'>
			<input type='text' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divComputerGroupList, this.value)'>
		</div>
		<div id='divComputerGroupList' class='box'>
			<a class='blockListItem' onclick='refreshDeployComputerList(-1)'><input type='checkbox' style='visibility:hidden' /><?php echo LANG['all_computer']; ?></a>
			<?php echoTargetComputerGroupOptions($select_computer_group_ids); ?>
		</div>
	</div>
	<div>
		<h3><?php echo LANG['computer']; ?> (<span id='spnSelectedComputers'>0</span>/<span id='spnTotalComputers'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divComputerList, this.checked)'>
			<input type='text' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divComputerList, this.value)'>
		</div>
		<div id='divComputerList' class='box'>
			<!-- filled by JS -->
		</div>
	</div>
</div>

<h2><img src='img/package.dyn.svg'><?php echo LANG['packages_to_deploy']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<span id='spnTotalPackageGroups'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageGroupList, this.checked);refreshDeployPackageList()'>
			<input type='text' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageGroupList, this.value)'>
		</div>
		<div id='divPackageGroupList' class='box'>
			<a class='blockListItem' onclick='refreshDeployPackageList(-1)'><input type='checkbox' style='visibility:hidden' /><?php echo LANG['all_packages']; ?></a>
			<?php echoTargetPackageGroupOptions($select_package_group_ids); ?>
		</div>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageList, this.checked)'>
			<input type='text' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageList, this.value)'>
		</div>
		<div id='divPackageList' class='box'>
			<!-- filled by JS -->
		</div>
	</div>
</div>

<div class='margintop'>
	<div><label><input type='checkbox' id='chkAutoCreateUninstallJobs' <?php if(!empty(DEFAULTS['default-auto-create-uninstall-jobs'])) echo 'checked'; ?>>&nbsp;<?php echo LANG['auto_create_uninstall_jobs']; ?></label></div>
	<div><label><input type='checkbox' id='chkForceInstallSameVersion' <?php if(!empty(DEFAULTS['default-force-install-same-version'])) echo 'checked'; ?>>&nbsp;<?php echo LANG['force_installation_of_same_version']; ?></label></div>
</div>
<div class='controls'>
	<button id='btnDeploy' onclick='deploy(txtName.value, dteStart.value+" "+tmeStart.value, chkDateEndEnabled.checked ? dteEnd.value+" "+tmeEnd.value : "", txtDescription.value, getSelectedCheckBoxValues("computers"), getSelectedCheckBoxValues("computer_groups"), getSelectedCheckBoxValues("packages"), getSelectedCheckBoxValues("package_groups"), chkWol.checked, chkShutdownWakedAfterCompletion.checked, chkAutoCreateUninstallJobs.checked, chkForceInstallSameVersion.checked, txtRestartTimeout.value, getCheckedRadioValue("sequence_mode"), sldPriority.value, txtConstraintIpRange.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<?php echo progressBar(100, 'prgDeploy', 'prgDeployContainer', 'prgDeployText', 'width:180px;display:none;', false, true); ?>
</div>

</div>

<?php
function echoTargetComputerGroupOptions($select_computer_group_ids, $parent=null, $indent=0) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllComputerGroup($parent) as $cg) {
		if(!$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_DEPLOY, false)) continue;

		$selected = '';
		if(in_array($cg->id, $select_computer_group_ids)) $selected = 'checked';
		echo "<a class='blockListItem' onclick='refreshDeployComputerList(".$cg->id.")'><input type='checkbox' name='computer_groups' value='".$cg->id."' ".$selected." />".trim(str_repeat("‒",$indent)." ".htmlspecialchars($cg->name))."</a>";
		echoTargetComputerGroupOptions($select_computer_group_ids, $cg->id, $indent+1);
	}
}
function echoTargetPackageGroupOptions($select_package_group_ids, $parent=null, $indent=0) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllPackageGroup($parent) as $pg) {
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		$selected = '';
		if(in_array($pg->id, $select_package_group_ids)) $selected = 'checked';
		echo "<a class='blockListItem' onclick='refreshDeployPackageList(".$pg->id.")'><input type='checkbox' name='package_groups' value='".$pg->id."' ".$selected." />".trim(str_repeat("‒",$indent)." ".htmlspecialchars($pg->name))."</a>";
		echoTargetPackageGroupOptions($select_package_group_ids, $pg->id, $indent+1);
	}
}
