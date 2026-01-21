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

<input type='hidden' id='txtPolicyObjects' value='<?php echo htmlspecialchars(implode(',',$_GET['id'])); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('computer_groups'); ?></th>
		<th><?php echo LANG('domain_user_groups'); ?></th>
	</tr>
	<tr>
		<td>
			<select id='txtComputerGroups' class='fullwidth' size='10' multiple='true' autofocus='true'>
				<option value=''><?php echo LANG('default_domain_policy'); ?></option>
				<?php Html::buildGroupOptions($cl, new Models\ComputerGroup()); ?>
			</select>
		</td>
		<td>
			<select id='txtDomainUserGroups' class='fullwidth' size='10' multiple='true' autofocus='true'>
				<option value=''><?php echo LANG('default_domain_policy'); ?></option>
				<?php Html::buildGroupOptions($cl, new Models\DomainUserGroup()); ?>
			</select>
		</td>
	</tr>
</table>

<script>
btnDoAssignPolicyObject.addEventListener('click', function(e){
	var params = [];
	let computerGroupIds = getSelectedSelectBoxValues('txtComputerGroups', false);
	let domainUserGroupIds = getSelectedSelectBoxValues('txtDomainUserGroups', false);
	if(!computerGroupIds && !domainUserGroupIds) return;
	computerGroupIds.forEach(function(entry) {
		params.push({'key':'add_to_computer_group_id[]', 'value':entry});
	});
	domainUserGroupIds.forEach(function(entry) {
		params.push({'key':'add_to_domain_user_group_id[]', 'value':entry});
	});
	txtPolicyObjects.value.split(',').forEach(function(entry) {
		params.push({'key':'policy_object_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/policy-objects.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
});
</script>

<div class='controls right'>
	<button class='closeDialog'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' id='btnDoAssignPolicyObject'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
