<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('old_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='old_password' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('new_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='new_password'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('confirm_password'); ?></th>
		<td><input type='password' class='fullwidth' autocomplete='new-password' name='new_password_confirm'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
