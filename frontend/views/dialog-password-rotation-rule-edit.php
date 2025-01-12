<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtEditPasswordRotationRuleId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('computer_group'); ?></th>
		<td>
			<select class='fullwidth' id='sltEditPasswordRotationRuleComputerGroupId'>
				<option value='' selected>-</option>
				<?php echoComputerGroupOptions($cl); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('username'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditPasswordRotationRuleUsername' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('alphabet'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditPasswordRotationRuleAlphabet'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('length'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' id='txtEditPasswordRotationRuleLength'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('valid_for'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' id='txtEditPasswordRotationRuleValidSeconds'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('history_count'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' id='txtEditPasswordRotationRuleHistory'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdatePasswordRotationRule' class='primary' onclick='editPasswordRotationRule(
		txtEditPasswordRotationRuleId.value,
		sltEditPasswordRotationRuleComputerGroupId.value,
		txtEditPasswordRotationRuleUsername.value,
		txtEditPasswordRotationRuleAlphabet.value,
		txtEditPasswordRotationRuleLength.value,
		txtEditPasswordRotationRuleValidSeconds.value,
		txtEditPasswordRotationRuleHistory.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdatePasswordRotationRule'><?php echo LANG('change'); ?></span></button>
</div>
