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
		<table id='tblPolicyData' class='list searchable sortable savesort actioncolumn'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('created'); ?></th>
				<th class='searchable sortable'><?php echo LANG('updated'); ?></th>
				<th class='searchable sortable'><?php echo LANG('groups'); ?></th>
				<th class=''><?php echo LANG('action'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($policyObjects as $p) {
			$computerGroups = $db->selectAllComputerGroupByPolicyObject($p->id);
			$domainUserGroups = $db->selectAllDomainUserGroupByPolicyObject($p->id);
		?>
			<tr>
				<td><input type='checkbox' name='policy_object_id[]' value='<?php echo $p->id; ?>'></td>
				<td>
					<a <?php echo Html::explorerLink('views/policy-object.php?id='.$p->id); ?>>
						<?php echo htmlspecialchars($p->name); ?>
					</a>
				</td>
				<td><?php echo htmlspecialchars($p->created.(empty($p->created_by_system_user_username) ? '' : ' ('.$p->created_by_system_user_username.')')); ?></td>
				<td><?php echo htmlspecialchars($p->updated.(empty($p->updated_by_system_user_username) ? '' : ' ('.$p->updated_by_system_user_username.')')); ?></td>
				<td>
					<ul>
					<?php
					foreach($computerGroups as $group) {
						echo "<li class='subbuttons'>";
						echo "<a ".Html::explorerLink('views/computers.php?id='.$group->id).">"
						.Html::wrapInSpanIfNotEmpty($group->id?$group->getBreadcrumbString():LANG('default_domain_policy'))."</a>";
						echo "<button class='removeFromComputerGroup' policy_object_id='".$p->id."' group_id='".$group->id."' title='".LANG('remove_from_group',ENT_QUOTES)."'><img class='small' src='img/folder-remove-from.dyn.svg'></button>";
						echo "</li>";
					}
					?>
					</ul>
					<?php if(!empty($computerGroups) && !empty($domainUserGroups)) { ?><hr><?php } ?>
					<ul>
					<?php
					foreach($domainUserGroups as $group) {
						echo "<li class='subbuttons'>";
						echo "<a ".Html::explorerLink('views/domain-users.php?id='.$group->id).">"
							.Html::wrapInSpanIfNotEmpty($group->id?$group->getBreadcrumbString():LANG('default_domain_policy'))."</a>";
						echo "<button class='removeFromDomainUserGroup' policy_object_id='".$p->id."' group_id='".$group->id."' title='".LANG('remove_from_group',ENT_QUOTES)."'><img class='small' src='img/folder-remove-from.dyn.svg'></button>";
						echo "</li>";
					}
					?>
					</ul>
				</td>
				<td>
					<button title='<?php echo LANG('overview'); ?>' onclick='showDialogPolicyObjectOverview(<?php echo $p->id; ?>)'><img src='img/eye.dyn.svg'></button>
				</td>
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
							<button onclick='showDialogAssignPolicyObject(getSelectedCheckBoxValues("policy_object_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button onclick='confirmRemoveObject(getSelectedCheckBoxValues("policy_object_id[]",null,true), "remove_policy_object_id", "ajax-handler/policy-objects.php", e)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>

<script>
// init remove from group buttons
let removeButtons = tblPolicyData.querySelectorAll('button.removeFromComputerGroup');
for(let i=0; i<removeButtons.length; i++) {
	removeButtons[i].addEventListener('click', (e) => {
		e.preventDefault(); e.stopPropagation();
		if(!confirm(LANG['are_you_sure'])) return;
		var params = [];
		params.push({'key':'remove_from_computer_group_id', 'value':e.srcElement.getAttribute('group_id')});
		params.push({'key':'policy_object_id', 'value':e.srcElement.getAttribute('policy_object_id')});
		var paramString = urlencodeArray(params);
		ajaxRequestPost('ajax-handler/policy-objects.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
		});
	});
}
let removeButtons2 = tblPolicyData.querySelectorAll('button.removeFromDomainUserGroup');
for(let i=0; i<removeButtons2.length; i++) {
	removeButtons2[i].addEventListener('click', (e) => {
		e.preventDefault(); e.stopPropagation();
		if(!confirm(LANG['are_you_sure'])) return;
		var params = [];
		params.push({'key':'remove_from_domain_user_group_id', 'value':e.srcElement.getAttribute('group_id')});
		params.push({'key':'policy_object_id', 'value':e.srcElement.getAttribute('policy_object_id')});
		var paramString = urlencodeArray(params);
		ajaxRequestPost('ajax-handler/policy-objects.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
		});
	});
}
</script>
