<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$tab = 'simple';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$default_job_container_name = '';
if(!empty($_GET['computer_id']) && is_array($_GET['computer_id'])) {
	foreach($_GET['computer_id'] as $id) {
		$c = $db->getComputer($id);
		if(!$currentSystemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$c->hostname;
		else $default_job_container_name .= ', '.$c->hostname;
	}
}
if(!empty($_GET['package_id']) && is_array($_GET['package_id'])) {
	foreach($_GET['package_id'] as $id) {
		$p = $db->getPackage($id);
		if(!$currentSystemUser->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$p->package_family_name;
		else $default_job_container_name .= ', '.$p->package_family_name;
	}
}
if(!empty($_GET['computer_group_id']) && is_array($_GET['computer_group_id'])) {
	foreach($_GET['computer_group_id'] as $id) {
		$cg = $db->getComputerGroup($id);
		if(!$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_DEPLOY, false)) continue;
		if(empty($default_job_container_name)) $default_job_container_name = LANG['install'].' '.$cg->name;
		else $default_job_container_name .= ', '.$cg->name;
	}
}
if(!empty($_GET['package_group_id']) && is_array($_GET['package_group_id'])) {
	foreach($_GET['package_group_id'] as $id) {
		$pg = $db->getPackageGroup($id);
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;
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

<div id='tabControlDeploy' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='simple' class='<?php if($tab=='simple') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlDeploy,this.getAttribute("name"))'><?php echo LANG['default_view']; ?></a>
		<a href='#' name='advanced' class='<?php if($tab=='advanced') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlDeploy,this.getAttribute("name"))'><?php echo LANG['advanced_view']; ?></a>
	</div>
	<div>

		<table class='form margintop'>
			<tr>
				<th><?php echo LANG['name']; ?></th>
				<td>
					<input type='text' id='txtName' value='<?php echo htmlspecialchars($default_job_container_name); ?>' autofocus='true'></input>
				</td>
			</tr>
			<tr class='nospace'>
				<th><?php echo LANG['start']; ?></th>
				<td class='dualInput'>
					<input type='date' id='dteStart' value='<?php echo date('Y-m-d'); ?>'></input>
					<input type='time' id='tmeStart' value='<?php echo date('H:i'); ?>'></input>
				</td>

				<th><?php echo LANG['end']; ?></th>
				<td class='dualInput'>
					<input type='date' id='dteEnd' value=''></input>
					<input type='time' id='tmeEnd' value=''></input>
					<button class='small' title='<?php echo LANG['remove_end_time']; ?>' onclick='dteEnd.value="";tmeEnd.value=""'><img src='img/close.dyn.svg'></button>
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
				<td></td>
			</tr>
			<tr class='tabadditionals <?php if($tab!='advanced') echo 'hidden'; ?>' tab='advanced'>
				<th><?php echo LANG['description']; ?></th>
				<td>
					<textarea id='txtDescription'></textarea>
				</td>
				<th><?php echo LANG['agent_ip_range']; ?></th>
				<td>
					<input type='text' id='txtConstraintIpRange' placeholder='<?php echo LANG['example'].':'; ?> 192.168.2.0/24, 10.0.0.0/8'></input>
				</td>
			</tr>
			<tr class='tabadditionals <?php if($tab!='advanced') echo 'hidden'; ?>' tab='advanced'>
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
				<th class='top'><?php echo LANG['installation_behaviour']; ?></th>
				<td colspan='3'>
					<div>
						<div class='checkboxWithText'>
							<input type='checkbox' id='chkAutoCreateUninstallJobs' <?php if(!empty(DEFAULTS['default-auto-create-uninstall-jobs'])) echo 'checked'; ?>>
							<label for='chkAutoCreateUninstallJobs'>
								<div><?php echo LANG['uninstall_old_package_versions']; ?></div>
								<div class='hint'><?php echo LANG['auto_create_uninstall_jobs']; ?></div>
							</label>
						</div>
						<div class='checkboxWithText'>
							<input type='checkbox' id='chkForceInstallSameVersion' <?php if(!empty(DEFAULTS['default-force-install-same-version'])) echo 'checked'; ?>>
							<label for='chkForceInstallSameVersion'>
								<div><?php echo LANG['reinstall']; ?></div>
								<div class='hint'><?php echo LANG['force_installation_of_same_version']; ?></div>
							</label>
						</div>
						<div class='checkboxWithText'>
							<input type='hidden' name='sequence_mode' value='<?php echo JobContainer::SEQUENCE_MODE_IGNORE_FAILED; ?>' checked='true'>
							<input type='checkbox' id='chkAbortAfterError' name='sequence_mode' value='<?php echo JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED; ?>' <?php if(!empty(DEFAULTS['default-abort-after-error'])) echo 'checked'; ?>>
							<label for='chkAbortAfterError'>
								<div><?php echo LANG['abort_after_failed']; ?></div>
								<div class='hint'><?php echo LANG['abort_after_error_description']; ?></div>
							</label>
						</div>
					</div>
				</td>
			</tr>
		</table>

	</div>
	<div class='tabcontents'>

		<div id='tabSimple' name='simple' class='<?php if($tab=='simple') echo 'active'; ?>'>
		</div>

		<div id='tabAdvanced' name='advanced' class='<?php if($tab=='advanced') echo 'active'; ?>'>
		</div>

	</div>

	<div class='gallery margintop'>
		<div>
			<h2><div><?php echo LANG['computer_selection']; ?> (<span id='spnSelectedComputers'>0</span>/<span id='spnTotalComputers'>0</span>)</div></h2>
			<div class='listSearch'>
				<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divComputerList, this.checked);refreshDeployComputerCount()'>
				<input type='text' id='txtDeploySearchComputers' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divComputerList, this.value)'>
			</div>
			<div id='divComputerList' class='box listSearchList withContextButton'>
				<a class='blockListItem noSearch big' onclick='refreshDeployComputerList(-1)'><?php echo LANG['all_computer']; ?><img src='img/arrow-forward.dyn.svg' class='dragicon'></a>
				<div class='headline bold'>
					<?php echo LANG['computer_groups']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetComputerGroupOptions(); ?>
				<div class='headline bold'>
					<?php echo LANG['reports']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetComputerReportOptions(); ?>
			</div>
			<div id='divComputerListHome' class='box listSearchList hidden'>
				<a class='blockListItem noSearch big' onclick='refreshDeployComputerList(-1)'><?php echo LANG['all_computer']; ?><img src='img/arrow-forward.dyn.svg' class='dragicon'></a>
				<div class='headline bold'>
					<?php echo LANG['computer_groups']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetComputerGroupOptions(); ?>
				<div class='headline bold'>
					<?php echo LANG['reports']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetComputerReportOptions(); ?>
			</div>
			<button class='small listSearchButton' onclick='addSelectedComputersToDeployTarget()'><?php echo LANG['add_selected']; ?>&nbsp;<img src='img/add2.dyn.svg'></button>
		</div>
		<img src='img/arrow-right.dyn.svg'>
		<div>
			<h2><img src='img/computer.dyn.svg'><div><?php echo LANG['target_computer']; ?> (<span id='spnTotalTargetComputers'>0</span>)</div></h2>
			<div class='listSearch'>
				<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divTargetComputerList, this.checked)'>
				<input type='text' id='txtDeploySearchTargetComputers' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divTargetComputerList, this.value)'>
			</div>
			<div id='divTargetComputerList' class='box listSearchList withContextButton'>
				<!-- filled by user -->
			</div>
			<button class='small listSearchButton' onclick='removeSelectedTargets(divTargetComputerList)'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG['remove_selected']; ?></button>
		</div>
	</div>
	<div class='gallery margintop'>
		<div>
			<h2><div><?php echo LANG['package_selection']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</div></h2>
			<div class='listSearch'>
				<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageList, this.checked);refreshDeployPackageCount()'>
				<input type='text' id='txtDeploySearchPackages' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageList, this.value)'>
			</div>
			<div id='divPackageList' class='box listSearchList withContextButton'>
				<a class='blockListItem noSearch big' onclick='refreshDeployPackageList(-1)'><?php echo LANG['all_packages']; ?><img src='img/arrow-forward.dyn.svg' class='dragicon'></a>
				<div class='headline bold'>
					<?php echo LANG['package_groups']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetPackageGroupOptions(); ?>
				<div class='headline bold'>
					<?php echo LANG['reports']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetPackageReportOptions(); ?>
			</div>
			<div id='divPackageListHome' class='box listSearchList hidden'>
				<a class='blockListItem noSearch big' onclick='refreshDeployPackageList(-1)'><?php echo LANG['all_packages']; ?><img src='img/arrow-forward.dyn.svg' class='dragicon'></a>
				<div class='headline bold'>
					<?php echo LANG['package_groups']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetPackageGroupOptions(); ?>
				<div class='headline bold'>
					<?php echo LANG['reports']; ?>
					<div class='filler'></div>
				</div>
				<?php echoTargetPackageReportOptions(); ?>
			</div>
			<button class='small listSearchButton' onclick='addSelectedPackagesToDeployTarget()'><?php echo LANG['add_selected']; ?>&nbsp;<img src='img/add2.dyn.svg'></button>
		</div>
		<img src='img/arrow-right.dyn.svg'>
		<div>
			<h2><img src='img/package.dyn.svg'><div><?php echo LANG['packages_to_deploy']; ?> (<span id='spnTotalTargetPackages'>0</span>)</div></h2>
			<div class='listSearch'>
				<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divTargetPackageList, this.checked)'>
				<input type='text' id='txtDeploySearchTargetPackages' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divTargetPackageList, this.value)'>
			</div>
			<div id='divTargetPackageList' class='box listSearchList withContextButton'>
				<!-- filled by user -->
			</div>
			<button class='small listSearchButton' onclick='removeSelectedTargets(divTargetPackageList)'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG['remove_selected']; ?></button>
		</div>
	</div>

</div>

<div class='content-foot'>
	<div class='filler'></div>
	<?php echo progressBar(100, 'prgDeploy', 'prgDeployText', 'hidden animated big'); ?>
	<button id='btnDeploy' class='primary' onclick='deploy(
			txtName.value,
			dteStart.value+" "+tmeStart.value,
			dteEnd.value!=""&&tmeEnd.value!="" ? dteEnd.value+" "+tmeEnd.value : "",
			txtDescription.value,
			getAllCheckBoxValues("target_computers", null, false, divTargetComputerList),
			getAllCheckBoxValues("target_computer_groups", null, false, divTargetComputerList),
			getAllCheckBoxValues("target_computer_reports", null, false, divTargetComputerList),
			getAllCheckBoxValues("target_packages", null, false, divTargetPackageList),
			getAllCheckBoxValues("target_package_groups", null, false, divTargetPackageList),
			getAllCheckBoxValues("target_package_reports", null, false, divTargetPackageList),
			chkWol.checked,
			chkShutdownWakedAfterCompletion.checked,
			chkAutoCreateUninstallJobs.checked,
			chkForceInstallSameVersion.checked,
			txtRestartTimeout.value,
			getCheckedRadioValue("sequence_mode"),
			sldPriority.value,
			txtConstraintIpRange.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
</div>

<?php
function echoTargetComputerGroupOptions($parent=null) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllComputerGroup($parent) as $cg) {
		if(!$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($cg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<a class='blockListItem' onclick='refreshDeployComputerList(".$cg->id.")' ondblclick='addToDeployTarget({".$cg->id.": this.innerText}, divTargetComputerList, \"target_computer_groups\")'><input type='checkbox' name='computer_groups' value='".$cg->id."' onclick='event.stopPropagation();refreshDeployComputerCount()' />";
		echo htmlspecialchars($cg->name);
		echo "<img src='img/arrow-forward.dyn.svg' class='dragicon'>";
		echo "</a>";
		echo "<div class='subgroup'>";
		echoTargetComputerGroupOptions($cg->id);
		echo "</div>";
	}
}
function echoTargetComputerReportOptions($parent=null) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllReport($parent) as $r) {
		if(!$currentSystemUser->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;

		$displayName = $r->name;
		if(array_key_exists($displayName, LANG)) $displayName = LANG[$displayName];
		echo "<a class='blockListItem' onclick='refreshDeployComputerList(null, ".$r->id.")' ondblclick='addToDeployTarget({".$r->id.": this.innerText}, divTargetComputerList, \"target_computer_reports\")'><input type='checkbox' name='computer_reports' value='".$r->id."' onclick='event.stopPropagation();refreshDeployComputerCount()' />";
		echo htmlspecialchars($displayName);
		echo "<img src='img/arrow-forward.dyn.svg' class='dragicon'>";
		echo "</a>";
	}
}
function echoTargetPackageGroupOptions($parent=null) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllPackageGroup($parent) as $pg) {
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<a class='blockListItem' onclick='refreshDeployPackageList(".$pg->id.")' ondblclick='addToDeployTarget({".$pg->id.": this.innerText}, divTargetPackageList, \"target_package_groups\")'><input type='checkbox' name='package_groups' value='".$pg->id."' onclick='event.stopPropagation();refreshDeployPackageCount()' />";
		echo htmlspecialchars($pg->name);
		echo "<img src='img/arrow-forward.dyn.svg' class='dragicon'>";
		echo "</a>";
		echo "<div class='subgroup'>";
		echoTargetPackageGroupOptions($pg->id);
		echo "</div>";
	}
}
function echoTargetPackageReportOptions($parent=null) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllReport($parent) as $r) {
		if(!$currentSystemUser->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;

		$displayName = $r->name;
		if(array_key_exists($displayName, LANG)) $displayName = LANG[$displayName];
		echo "<a class='blockListItem' onclick='refreshDeployPackageList(null, ".$r->id.")' ondblclick='addToDeployTarget({".$r->id.": this.innerText}, divTargetPackageList, \"target_package_reports\")'><input type='checkbox' name='package_reports' value='".$r->id."' onclick='event.stopPropagation();refreshDeployPackageCount()' />";
		echo htmlspecialchars($displayName);
		echo "<img src='img/arrow-forward.dyn.svg' class='dragicon'>";
		echo "</a>";
	}
}
