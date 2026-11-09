<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo $_GET['name'] ?? LANG('install'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('settings'); ?></th>
		<td>
			<div>
				<label><input type='checkbox' name='use_wol' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
				<br/>
				<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' name='shutdown_waked_after_completion' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='install'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('install'); ?></button>
</div>
