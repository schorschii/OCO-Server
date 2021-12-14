<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<p style='max-width:450px'><?php echo LANG['renew_jobs_description']; ?></p>
<input type='hidden' id='txtRenewJobContainerId' value=''></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtRenewJobContainerName' autofocus='true' value='<?php echo LANG['renew'].' '.date('Y-m-d H:i:s'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' rows='4' id='txtRenewJobContainerNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['start']; ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' id='txtRenewJobContainerStartDate' value='<?php echo date('Y-m-d'); ?>'></input>
				<input type='time' class='' id='txtRenewJobContainerStartTime' value='<?php echo date('H:i:s'); ?>'></input>
			</div>
			<div>
				<label><input type='checkbox' id='chkRenewWol' onclick='if(this.checked) {chkRenewShutdownWakedAfterCompletion.disabled=false;} else {chkRenewShutdownWakedAfterCompletion.checked=false; chkRenewShutdownWakedAfterCompletion.disabled=true;}' <?php if(!empty(DEFAULTS['default-use-wol'])) echo 'checked'; ?>><?php echo LANG['send_wol']; ?></label>
				<br/>
				<label title='<?php echo LANG['shutdown_waked_after_completion']; ?>'><input type='checkbox' id='chkRenewShutdownWakedAfterCompletion' <?php if(!empty(DEFAULTS['default-shutdown-waked-after-completion'])) echo 'checked'; else echo 'disabled' ?>><?php echo LANG['shutdown_waked_computers']; ?></label>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['end']; ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' id='txtRenewJobContainerEndDate'></input>
				<input type='time' class='' id='txtRenewJobContainerEndTime'></input>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['priority']; ?></th>
		<td>
			<div class='inputWithLabel'>
				<input id='sldRenewPriority' type='range' min='-10' max='10' value='0' oninput='lblPriorityPreview.innerText=this.value' onchange='lblPriorityPreview.innerText=this.value'>
				<span id='lblPriorityPreview'>0</span>
			</div>
		</td>
	</tr>
	<tr>
		<th></th>
		<td><button class='fullwidth' onclick='renewFailedJobsInContainer(txtRenewJobContainerId.value, txtRenewJobContainerName.value, txtRenewJobContainerNotes.value, txtRenewJobContainerStartDate.value+" "+txtRenewJobContainerStartTime.value, (txtRenewJobContainerEndDate.value+" "+txtRenewJobContainerEndTime.value).trim(), chkRenewWol.checked, chkRenewShutdownWakedAfterCompletion.checked, sldRenewPriority.value)'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['renew']; ?></button></td>
	</tr>
</table>
