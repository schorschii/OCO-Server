<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$policyObjects = $cl->getPolicyObjects();
	$permissionCreate = $cl->checkPermission(new Models\PolicyObject(), PermissionManager::METHOD_CREATE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1><img src='img/policy.dyn.svg'><span id='page-title'><?php echo LANG('policies'); ?></span></h1>
<div class='controls'>
	<button onclick='showDialogEditPolicyObject()' <?php if(!$permissionCreate) echo 'disabled'; ?>>
		<img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?>
	</button>
	<span class='filler'></span>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblPolicyData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('created'); ?></th>
				<th class='searchable sortable'><?php echo LANG('groups'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($policyObjects as $p) {
			$groupLinks = [];
			foreach($db->selectAllComputerGroupByPolicyObject($p->id) as $group)
				$groupLinks[] = "<a ".Html::explorerLink('views/computers.php?id='.$group->id).">".htmlspecialchars($group->name)."</a>";
		?>
			<tr>
				<td><input type='checkbox' name='policy_object_id[]' value='<?php echo $p->id; ?>'></td>
				<td>
					<a <?php echo Html::explorerLink('views/policy-object.php?id='.$p->id); ?>>
						<?php echo htmlspecialchars($p->name); ?>
					</a>
				</td>
				<td><?php echo htmlspecialchars($p->created); ?></td>
				<td><?php echo implode("<br>", $groupLinks); ?></td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
							<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
						</div>
						<div class='controls'>
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<button id='btnAssignPolicyObject'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button id='btnRemovePolicyObject'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>

<script>
btnAssignPolicyObject.addEventListener('click', function(e) {
	let params = [];
	let values = getSelectedCheckBoxValues('policy_object_id[]', null, true);
	if(values) {
		values.forEach(function(entry) {
			params.push({'key':'id[]', 'value':entry});
		});
		showDialogAjax(LANG['assign'], 'views/dialog-policy-object-assign.php?'+urlencodeArray(params), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
	}
});
btnRemovePolicyObject.addEventListener('click', function(e) {
	confirmRemoveObject(
		getSelectedCheckBoxValues('policy_object_id[]', null, true),
		'remove_policy_object_id', 'ajax-handler/policy-objects.php',
		event
	);
});
</script>
