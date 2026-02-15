<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	if(empty($_GET['id']) || !is_array($_GET['id']))
		throw new Exception('GET id[] missing');
} catch(Exception $e) {
	die($e->getMessage());
}
?>

<input type='hidden' name='ids' value='<?php echo htmlspecialchars(implode(',',$_GET['id'])); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('computer_groups'); ?></th>
		<th><?php echo LANG('domain_user_groups'); ?></th>
	</tr>
	<tr>
		<td>
			<select name='computer_group_id' class='fullwidth' size='10' multiple='true' autofocus='true'>
				<option value=''><?php echo LANG('default_domain_policy'); ?></option>
				<?php Html::buildGroupOptions($cl, new Models\ComputerGroup()); ?>
			</select>
		</td>
		<td>
			<select name='domain_user_group_id' class='fullwidth' size='10' multiple='true' autofocus='true'>
				<option value=''><?php echo LANG('default_domain_policy'); ?></option>
				<?php Html::buildGroupOptions($cl, new Models\DomainUserGroup()); ?>
			</select>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='assign'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
