<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<input type='hidden' id='txtRenewJobContainerId' value=''></input>
<input type='hidden' id='txtRenewJobContainerJobId' value=''></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('mode'); ?></th>
		<td>
			<label><input type='checkbox' id='chkCreateNewJobContainer' autofocus='true' onclick='if(this.checked) tbNewJobContainer.style.display="table-row-group"; else tbNewJobContainer.style.display="none";'><?php echo LANG('create_new_job_container'); ?></label>
			<div style='max-width:400px' class='hint'><?php echo LANG('renew_jobs_description'); ?></div>
		</td>
	</tr>
	<tbody id='tbNewJobContainer' style='display:none'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtRenewJobContainerName' value='<?php echo LANG('renew').' '.date('Y-m-d H:i:s'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' rows='4' id='txtRenewJobContainerNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('start'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' id='txtRenewJobContainerStartDate' value='<?php echo date('Y-m-d'); ?>'></input>
				<input type='time' class='' id='txtRenewJobContainerStartTime' value='<?php echo date('H:i:s'); ?>'></input>
			</div>
			<div>
				<label><input type='checkbox' id='chkRenewWol' onclick='if(this.checked) {chkRenewShutdownWakedAfterCompletion.disabled=false;} else {chkRenewShutdownWakedAfterCompletion.checked=false; chkRenewShutdownWakedAfterCompletion.disabled=true;}' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
				<br/>
				<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' id='chkRenewShutdownWakedAfterCompletion' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('end'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' id='txtRenewJobContainerEndDate'></input>
				<input type='time' class='' id='txtRenewJobContainerEndTime'></input>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel'>
				<input id='sldRenewPriority' type='range' min='-10' max='10' value='0' oninput='lblPriorityPreview.innerText=this.value' onchange='lblPriorityPreview.innerText=this.value'>
				<span id='lblPriorityPreview'>0</span>
			</div>
		</td>
	</tr>
	</tbody>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='renewFailedStaticJobs(
		txtRenewJobContainerId.value,
		txtRenewJobContainerJobId.value,
		chkCreateNewJobContainer.checked,
		txtRenewJobContainerName.value,
		txtRenewJobContainerNotes.value,
		txtRenewJobContainerStartDate.value+" "+txtRenewJobContainerStartTime.value,
		(txtRenewJobContainerEndDate.value+" "+txtRenewJobContainerEndTime.value).trim(),
		chkRenewWol.checked,
		chkRenewShutdownWakedAfterCompletion.checked,
		sldRenewPriority.value
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('renew'); ?></button>
</div>
