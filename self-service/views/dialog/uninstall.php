<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<p style='max-width:450px'><?php echo LANG('uninstall_job_container_will_be_created'); ?></p>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo LANG('uninstall').' '.date('Y-m-d H:i:s'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('settings'); ?></th>
		<td>
			<div>
				<label><input type='checkbox' name='wol' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
				<br/>
				<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' name='shutdown_waked' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='uninstall'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
</div>
