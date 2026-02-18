<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$tab = 'simple';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

// compile job name
$default_job_container_name = LANG('install').' '.date('y-m-d H:i:s');
?>

<h1><img src='img/deploy.dyn.svg'><span id='page-title'><?php echo LANG('deployment_assistant'); ?></span></h1>

<div id='tabControlDeploy' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='simple' class='<?php if($tab=='simple') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlDeploy,this.getAttribute("name"))'><?php echo LANG('default_view'); ?></a>
		<a href='#' name='advanced' class='<?php if($tab=='advanced') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlDeploy,this.getAttribute("name"))'><?php echo LANG('advanced_view'); ?></a>
	</div>
	<div>

		<table class='form margintop'>
			<tr>
				<th><?php echo LANG('name'); ?></th>
				<td>
					<input type='text' id='txtName' value='<?php echo htmlspecialchars($default_job_container_name); ?>' autofocus='true'></input>
				</td>
			</tr>
			<tr class='nospace'>
				<th><?php echo LANG('start'); ?></th>
				<td class='dualInput'>
					<input type='date' id='dteStart' value='<?php echo date('Y-m-d'); ?>'></input>
					<input type='time' id='tmeStart' value='<?php echo date('H:i'); ?>'></input>
				</td>

				<th><?php echo LANG('end'); ?></th>
				<td class='dualInput'>
					<input type='date' id='dteEnd' value=''></input>
					<input type='time' id='tmeEnd' value=''></input>
					<button class='small' title='<?php echo LANG('remove_end_time'); ?>' onclick='dteEnd.value="";tmeEnd.value=""'><img src='img/close.dyn.svg'></button>
				</td>
			</tr>
			<tr>
				<th></th>
				<td>
					<label><input type='checkbox' id='chkWol' onclick='if(this.checked) {chkShutdownWakedAfterCompletion.disabled=false;} else {chkShutdownWakedAfterCompletion.checked=false; chkShutdownWakedAfterCompletion.disabled=true;}' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
					<br/>
					<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' id='chkShutdownWakedAfterCompletion' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
				</td>
				<th></th>
				<td></td>
			</tr>
			<tr class='tabadditionals <?php if($tab!='advanced') echo 'hidden'; ?>' tab='advanced'>
				<th><?php echo LANG('description'); ?></th>
				<td colspan='3'>
					<textarea id='txtDescription'></textarea>
				</td>
			</tr>
			<tr class='tabadditionals <?php if($tab!='advanced') echo 'hidden'; ?>' tab='advanced'>
				<th><?php echo LANG('agent_ip_range'); ?></th>
				<td>
					<input type='text' id='txtConstraintIpRange' placeholder='<?php echo LANG('example').':'; ?> 192.168.2.0/24, CAFF:EECA:FFEE:0000::/64'></input>
				</td>
				<th><?php echo LANG('time_frame'); ?></th>
				<td>
					<input type='text' id='txtConstraintTimeRange' placeholder='<?php echo LANG('example').':'; ?> 6:00-8:00, SUN 0:00-23:59'></input>
				</td>
			</tr>
			<tr class='tabadditionals <?php if($tab!='advanced') echo 'hidden'; ?>' tab='advanced'>
				<th><?php echo LANG('priority'); ?></th>
				<td>
					<div class='inputWithLabel' title='<?php echo LANG('priority_description'); ?>'>
						<input id='sldPriority' type='range' min='-10' max='10' value='0' oninput='lblPriorityPreview.innerText=this.value' onchange='lblPriorityPreview.innerText=this.value'>
						<div id='lblPriorityPreview'>0</div>
					</div>
				</td>
				<th><?php echo LANG('timeout_for_reboot'); ?></th>
				<td>
					<div class='inputWithLabel' title='<?php echo LANG('timeout_for_reboot_description'); ?>'>
						<input type='number' id='txtRestartTimeout' value='<?php echo htmlspecialchars($db->settings->get('default-restart-timeout')); ?>' min='-1'></input>
						<div><?php echo LANG('minutes'); ?></div>
					</div>
				</td>
			</tr>
			<tr>
				<th class='top'><?php echo LANG('installation_behaviour'); ?></th>
				<td colspan='3'>
					<div>
						<div class='checkboxWithText'>
							<input type='checkbox' id='chkForceInstallSameVersion' <?php if(!empty($db->settings->get('default-force-install-same-version'))) echo 'checked'; ?>>
							<label for='chkForceInstallSameVersion'>
								<div><?php echo LANG('reinstall'); ?></div>
								<div class='hint'><?php echo LANG('force_installation_of_same_version'); ?></div>
							</label>
						</div>
						<div class='checkboxWithText'>
							<input type='hidden' name='sequence_mode' value='<?php echo Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED; ?>' checked='true'>
							<input type='checkbox' id='chkAbortAfterError' name='sequence_mode' value='<?php echo Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED; ?>' <?php if(!empty($db->settings->get('default-abort-after-error'))) echo 'checked'; ?>>
							<label for='chkAbortAfterError'>
								<div><?php echo LANG('abort_after_failed'); ?></div>
								<div class='hint'><?php echo LANG('abort_after_error_description'); ?></div>
							</label>
						</div>
					</div>
				</td>
			</tr>
		</table>

	</div>
	<div class='tabcontents'>
		<!-- dummy tab contents -->
		<div id='tabSimple' name='simple' class='<?php if($tab=='simple') echo 'active'; ?>'></div>
		<div id='tabAdvanced' name='advanced' class='<?php if($tab=='advanced') echo 'active'; ?>'></div>
	</div>

	<div class='gallery margintop'>
		<div id='divComputerSelection'>
			<?php $CONTAINER_SELECTION=true; require('partial/computer-selection.php'); ?>
			<button class='small listSearchButton' onclick='addSelectedComputersToDeployTarget(divComputerSelection)'><?php echo LANG('add_selected'); ?>&nbsp;<img src='img/add2.dyn.svg'></button>
		</div>
		<img src='img/arrow-right.dyn.svg'>
		<div>
			<h3><div><?php echo LANG('target_computer'); ?> (<span id='spnTotalTargetComputers'>0</span>)</div></h3>
			<div class='listSearch'>
				<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divTargetComputerList, this.checked)'>
				<input type='search' id='txtDeploySearchTargetComputers' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divTargetComputerList, this.value)'>
			</div>
			<div id='divTargetComputerList' class='box listItems withContextButton'>
				<!-- filled by user -->
			</div>
			<button class='small listSearchButton' onclick='removeSelectedTargets(divTargetComputerList)'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('remove_selected'); ?></button>
		</div>
	</div>
	<div class='gallery margintop'>
		<div id='divPackageSelection'>
			<?php $CONTAINER_SELECTION=true; require('partial/package-selection.php'); ?>
			<button class='small listSearchButton' onclick='addSelectedPackagesToDeployTarget(divPackageSelection)'><?php echo LANG('add_selected'); ?>&nbsp;<img src='img/add2.dyn.svg'></button>
		</div>
		<img src='img/arrow-right.dyn.svg'>
		<div>
			<h3><div><?php echo LANG('packages_to_deploy'); ?> (<span id='spnTotalTargetPackages'>0</span>)</div></h3>
			<div class='listSearch'>
				<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divTargetPackageList, this.checked)'>
				<input type='search' id='txtDeploySearchTargetPackages' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divTargetPackageList, this.value)'>
			</div>
			<div id='divTargetPackageList' class='box listItems withContextButton'>
				<!-- filled by user -->
			</div>
			<button class='small listSearchButton' onclick='removeSelectedTargets(divTargetPackageList)'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('remove_selected'); ?></button>
		</div>
	</div>

</div>

<script>
	initSelectionBox(divComputerSelection, divTargetComputerList, 'target_computers');
	initSelectionBox(divPackageSelection, divTargetPackageList, 'target_packages');
</script>

<div class='content-foot'>
	<div class='filler'></div>
	<?php echo Html::progressBar(100, 'prgDeploy', 'prgDeployText', 'hidden animated big'); ?>
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
			chkForceInstallSameVersion.checked,
			txtRestartTimeout.value,
			getCheckedRadioValue("sequence_mode"),
			sldPriority.value,
			txtConstraintIpRange.value,
			txtConstraintTimeRange.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
</div>
