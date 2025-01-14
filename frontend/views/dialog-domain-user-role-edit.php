<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$domainUserRole = null;
try {
	$domainUserRole = $cl->getDomainUserRole($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' id='txtEditDomainUserRoleId' value='<?php echo $domainUserRole->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditDomainUserRoleName' autofocus='true' value='<?php echo $domainUserRole->name??''; ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('permission_json'); ?></th>
		<td><textarea class='fullwidth monospace' autocomplete='new-password' id='txtEditDomainUserRolePermissions' rows='8'><?php echo $domainUserRole->permissions??''; ?></textarea></td>
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
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateDomainUserRole' class='primary' onclick='editDomainUserRole(
		txtEditDomainUserRoleId.value,
		txtEditDomainUserRoleName.value,
		txtEditDomainUserRolePermissions.value,
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateDomainUserRole'><?php echo $domainUserRole ? LANG('change') : LANG('create'); ?></span></button>
</div>
