<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

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
		<td>
			<select id='txtComputerGroups' class='fullwidth' size='10' multiple='true' autofocus='true'>
				<?php Html::buildGroupOptions($cl, new Models\ComputerGroup()); ?>
			</select>
		</td>
	</tr>
</table>

<script>
btnDoAssignPolicyObject.addEventListener('click', function(e){
	var params = [];
	let groupIds = getSelectedSelectBoxValues('txtComputerGroups', true);
	if(!groupIds) return;
	groupIds.forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	txtPolicyObjects.value.split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_policy_object_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/policy-objects.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(LANG['assigned'], '', MESSAGE_TYPE_SUCCESS);
	});
});
</script>

<div class='controls right'>
	<button class='closeDialog'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' id='btnDoAssignPolicyObject'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
