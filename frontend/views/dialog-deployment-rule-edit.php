<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditDeploymentRuleId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditDeploymentRuleName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('state'); ?></th>
		<td><label><input type='checkbox' id='chkEditDeploymentRuleEnabled'></input>&nbsp;<?php echo LANG('enabled'); ?></label></td>
	</tr>
	<tr>
		<th><?php echo LANG('computer_group'); ?></th>
		<td>
			<select id='sltEditDeploymentRuleComputerGroupId' class='fullwidth'>
				<?php echoComputerGroupOptions($cl); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('package_group'); ?></th>
		<td>
			<select id='sltEditDeploymentRulePackageGroupId' class='fullwidth'>
				<?php echoPackageGroupOptions($cl); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditDeploymentRuleNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel' title='<?php echo LANG('priority_description'); ?>'>
				<input id='sldEditDeploymentRulePriority' type='range' min='-10' max='10' value='0' oninput='lblEditDeploymentRulePriorityPreview.innerText=this.value' onchange='lblEditDeploymentRulePriorityPreview.innerText=this.value'>
				<div id='lblEditDeploymentRulePriorityPreview'>0</div>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('uninstall'); ?></th>
		<td><label><input type='checkbox' id='chkEditDeploymentRuleAutoUninstall'></input>&nbsp;<?php echo LANG('uninstall_old_package_versions'); ?></label></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateDeploymentRule' class='primary' onclick='editDeploymentRule(
		txtEditDeploymentRuleId.value,
		txtEditDeploymentRuleName.value,
		txtEditDeploymentRuleNotes.value,
		chkEditDeploymentRuleEnabled.checked,
		sltEditDeploymentRuleComputerGroupId.value,
		sltEditDeploymentRulePackageGroupId.value,
		sldEditDeploymentRulePriority.value,
		chkEditDeploymentRuleAutoUninstall.checked,
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateDeploymentRule'><?php echo LANG('change'); ?></span></button>
</div>
