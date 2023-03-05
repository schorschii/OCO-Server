<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

// compile job name
$default_job_container_name = LANG('install').' '.date('y-m-d H:i:s');
?>

<h1><img src='img/deploy.dyn.svg'><span id='page-title'><?php echo LANG('deployment_assistant'); ?></span></h1>

<table class='form margintop'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td>
			<input type='text' id='txtName' value='<?php echo htmlspecialchars($default_job_container_name); ?>' autofocus='true'></input>
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
</table>

<div class='gallery margintop'>
	<div>
		<h2><img src='img/computer.dyn.svg'><div><?php echo LANG('computer_selection'); ?> (<span id='spnSelectedComputers'>0</span>/<span id='spnTotalComputers'>0</span>)</div></h2>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divComputerList, this.checked);refreshDeployComputerCount()'>
			<input type='text' id='txtDeploySearchComputers' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divComputerList, this.value)'>
		</div>
		<div id='divComputerList' class='box listSearchList withContextButton'>
			<?php foreach($db->selectAllComputer() as $c) {
				if(!$cl->checkPermission($c, SelfService\PermissionManager::METHOD_DEPLOY, false)) continue;
				echo "<label class='blockListItem' ondblclick='addToDeployTarget({\"id\":".$c->id.",\"name\":this.innerText}, divTargetComputerList, \"target_computers\")'><input type='checkbox' name='computers' onclick='refreshDeployComputerCount()' value='".$c->id."' />".htmlspecialchars($c->hostname)."</label>";
			} ?>
		</div>
		<button class='small listSearchButton' onclick='addSelectedComputersToDeployTarget()'><?php echo LANG('add_selected'); ?>&nbsp;<img src='img/add2.dyn.svg'></button>
	</div>
	<img src='img/arrow-right.dyn.svg'>
	<div>
		<h2><div><?php echo LANG('target_computer'); ?> (<span id='spnTotalTargetComputers'>0</span>)</div></h2>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divTargetComputerList, this.checked)'>
			<input type='text' id='txtDeploySearchTargetComputers' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divTargetComputerList, this.value)'>
		</div>
		<div id='divTargetComputerList' class='box listSearchList withContextButton'>
			<!-- filled by user -->
		</div>
		<button class='small listSearchButton' onclick='removeSelectedTargets(divTargetComputerList)'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('remove_selected'); ?></button>
	</div>
</div>
<div class='gallery margintop'>
	<div>
		<h2><img src='img/package.dyn.svg'><div><?php echo LANG('package_selection'); ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</div></h2>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divPackageList, this.checked);refreshDeployPackageCount()'>
			<input type='text' id='txtDeploySearchPackages' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divPackageList, this.value)'>
		</div>
		<div id='divPackageList' class='box listSearchList withContextButton'>
			<?php foreach($db->selectAllPackage() as $p) {
				if(!$cl->checkPermission($p, SelfService\PermissionManager::METHOD_DEPLOY, false)) continue;
				echo "<label class='blockListItem' ondblclick='addToDeployTarget({\"id\":".$p->id.",\"name\":this.innerText}, divTargetPackageList, \"target_packages\")'><input type='checkbox' name='packages' onclick='refreshDeployPackageCount()' value='".$p->id."' />".htmlspecialchars($p->getFullName())."</label>";
			} ?>
		</div>
		<button class='small listSearchButton' onclick='addSelectedPackagesToDeployTarget()'><?php echo LANG('add_selected'); ?>&nbsp;<img src='img/add2.dyn.svg'></button>
	</div>
	<img src='img/arrow-right.dyn.svg'>
	<div>
		<h2><div><?php echo LANG('packages_to_deploy'); ?> (<span id='spnTotalTargetPackages'>0</span>)</div></h2>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divTargetPackageList, this.checked)'>
			<input type='text' id='txtDeploySearchTargetPackages' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divTargetPackageList, this.value)'>
		</div>
		<div id='divTargetPackageList' class='box listSearchList withContextButton'>
			<!-- filled by user -->
		</div>
		<button class='small listSearchButton' onclick='removeSelectedTargets(divTargetPackageList)'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('remove_selected'); ?></button>
	</div>
</div>

<div class='content-foot'>
	<div class='filler'></div>
	<?php echo progressBar(100, 'prgDeploy', 'prgDeployText', 'hidden animated big'); ?>
	<button id='btnDeploy' class='primary' onclick='deploySelfService(
			txtName.value,
			getAllCheckBoxValues("target_computers", null, false, divTargetComputerList),
			getAllCheckBoxValues("target_packages", null, false, divTargetPackageList),
			chkWol.checked,
			chkShutdownWakedAfterCompletion.checked
			)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
</div>
