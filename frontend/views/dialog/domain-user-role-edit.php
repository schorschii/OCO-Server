<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$domainUserRole = null;
try {
	$domainUserRole = $cl->getDomainUserRole($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' name='id' value='<?php echo $domainUserRole->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo $domainUserRole->name??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('permission_json'); ?></th>
		<td><textarea class='fullwidth monospace' autocomplete='new-password' name='permissions' rows='8'><?php echo htmlspecialchars($domainUserRole->permissions)??''; ?></textarea></td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert warning' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('permission_json_docs'); ?>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<span><?php echo $domainUserRole ? LANG('change') : LANG('create'); ?></span></button>
</div>
