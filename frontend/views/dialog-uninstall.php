<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<p style='max-width:450px'><?php echo LANG('uninstall_job_container_will_be_created'); ?></p>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtUninstallJobContainerName' autofocus='true' value='<?php echo LANG('uninstall').' '.date('Y-m-d H:i:s'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' rows='4' id='txtUninstallJobContainerNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('start'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' id='txtUninstallJobContainerStartDate' value='<?php echo date('Y-m-d'); ?>'></input>
				<input type='time' class='' id='txtUninstallJobContainerStartTime' value='<?php echo date('H:i:s'); ?>'></input>
			</div>
			<div>
				<label><input type='checkbox' id='chkUninstallWol' onclick='if(this.checked) {chkUninstallShutdownWakedAfterCompletion.disabled=false;} else {chkUninstallShutdownWakedAfterCompletion.checked=false; chkUninstallShutdownWakedAfterCompletion.disabled=true;}' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
				<br/>
				<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' id='chkUninstallShutdownWakedAfterCompletion' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('end'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' id='txtUninstallJobContainerEndDate'></input>
				<input type='time' class='' id='txtUninstallJobContainerEndTime'></input>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel'>
				<input id='sldUninstallPriority' type='range' min='-10' max='10' value='0' oninput='lblPriorityPreview.innerText=this.value' onchange='lblPriorityPreview.innerText=this.value'>
				<span id='lblPriorityPreview'>0</span>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('timeout_for_reboot'); ?></th>
		<td>
			<div class='inputWithLabel'>
				<input type='number' id='txtUninstallRestartTimeout' value='<?php echo htmlspecialchars($db->settings->get('default-restart-timeout')); ?>' min='-1'></input>
				<span><?php echo LANG('minutes'); ?></span>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='uninstall("package_id[]", txtUninstallJobContainerName.value, txtUninstallJobContainerNotes.value, txtUninstallJobContainerStartDate.value+" "+txtUninstallJobContainerStartTime.value, (txtUninstallJobContainerEndDate.value+" "+txtUninstallJobContainerEndTime.value).trim(), chkUninstallWol.checked, chkUninstallShutdownWakedAfterCompletion.checked, txtUninstallRestartTimeout.value, sldUninstallPriority.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
</div>
