<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditJobContainerId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditJobContainerName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('state'); ?></th>
		<td><label><input type='checkbox' id='chkEditJobContainerEnabled'></input>&nbsp;<?php echo LANG('enabled'); ?></label></td>
	</tr>
	<tr>
		<th><?php echo LANG('start'); ?></th>
		<td class='dualInput'>
			<input type='date' class='fullwidth' id='dteEditJobContainerStart'></input>
			<input type='time' class='fullwidth' id='tmeEditJobContainerStart' step='1'></input>
			<button class='small invisible' disabled='true' title='<?php echo LANG('remove_end_time'); ?>' onclick='dteEditJobContainerStart.value="";tmeEditJobContainerStart.value=""'><img src='img/close.dyn.svg'></button>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('end'); ?></th>
		<td class='dualInput'>
			<input type='date' class='fullwidth' id='dteEditJobContainerEnd'></input>
			<input type='time' class='fullwidth' id='tmeEditJobContainerEnd' step='1'></input>
			<button class='small' title='<?php echo LANG('remove_end_time'); ?>' onclick='dteEditJobContainerEnd.value="";tmeEditJobContainerEnd.value=""'><img src='img/close.dyn.svg'></button>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditJobContainerNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_ip_range'); ?></th>
		<td>
			<input type='text' class='fullwidth' id='txtEditJobContainerAgentIpRanges' placeholder='<?php echo LANG('example').':'; ?> 192.168.2.0/24, 10.0.0.0/8'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel' title='<?php echo LANG('priority_description'); ?>'>
				<input id='sldEditJobContainerPriority' type='range' min='-10' max='10' value='0' oninput='lblEditJobContainerPriorityPreview.innerText=this.value' onchange='lblEditJobContainerPriorityPreview.innerText=this.value'>
				<div id='lblEditJobContainerPriorityPreview'>0</div>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('sequence_mode'); ?></th>
		<td>
			<div class='checkboxWithText'>
				<input type='checkbox' id='chkEditJobContainerSequenceMode' name='sequence_mode' value='<?php echo Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED; ?>' <?php if(!empty(DEFAULTS['default-abort-after-error'])) echo 'checked'; ?>>
				<label for='chkEditJobContainerSequenceMode'>
					<div><?php echo LANG('abort_after_failed'); ?></div>
				</label>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateComputer' class='primary' onclick='editJobContainer(
		txtEditJobContainerId.value,
		txtEditJobContainerName.value,
		chkEditJobContainerEnabled.checked,
		dteEditJobContainerStart.value+" "+tmeEditJobContainerStart.value,
		dteEditJobContainerEnd.value!=""&&tmeEditJobContainerEnd.value!="" ? dteEditJobContainerEnd.value+" "+tmeEditJobContainerEnd.value : "",
		chkEditJobContainerSequenceMode.checked,
		sldEditJobContainerPriority.value,
		txtEditJobContainerAgentIpRanges.value,
		txtEditJobContainerNotes.value,
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
