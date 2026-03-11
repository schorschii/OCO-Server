<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$group = null;
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getDomainUserGroup($_GET['id']);
		$domainUsers = $cl->getDomainUsers($group);
	} else {
		$domainUsers = $cl->getDomainUsers();
	}
	$subGroups = $cl->getDomainUserGroups($group ? $group->id : null);
	$policyObjects = $db->selectAllPolicyObjectByDomainUserGroup($group ? $group->id : null);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group === null) {
	$permissionCreateGroup = $cl->checkPermission(new Models\DomainUserGroup(), PermissionManager::METHOD_CREATE, false);
?>
	<div class='details-header'>
		<h1><img src='img/users.dyn.svg'><span id='page-title'><?php echo LANG('all_domain_user'); ?></span></h1>
	</div>
	<div class='controls'>
		<button onclick='createDomainUserGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_group'); ?></button>
		<span class='filler'></span>
	</div>
<?php } else {
	$permissionCreate = $cl->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionWrite  = $cl->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($group->getBreadcrumbString()); ?></span></h1>
	<div class='controls'>
		<button onclick='createDomainUserGroup(
			<?php echo $group->id; ?>
			)'
			<?php if(!$permissionCreate) echo 'disabled'; ?>>
			<img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_subgroup'); ?>
		</button>
		<button onclick='renameDomainUserGroup(
			<?php echo $group->id; ?>,
			this.getAttribute("oldName")
			)'
			oldName='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>'
			<?php if(!$permissionWrite) echo 'disabled'; ?>>
			<img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('rename_group'); ?>
		</button>
		<button onclick='confirmRemoveDomainUserGroup(
			[<?php echo $group->id; ?>],
			event,
			this.getAttribute("name")
			)'
			name='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>'
			<?php if(!$permissionDelete) echo 'disabled'; ?>>
			<img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete_group'); ?>
		</button>
		<span class='filler'></span>
	</div>
<?php } ?>

<?php if(!empty($subGroups) || $group != null) { ?>
<div class='controls subfolders'>
	<?php if($group != null) { ?>
		<?php if($group->parent_domain_user_group_id == null) { ?>
			<a class='box' <?php echo Html::explorerLink('views/domain-users.php'); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo LANG('all_domain_users'); ?></a>
		<?php } else { $subGroup = $cl->getDomainUserGroup($group->parent_domain_user_group_id); ?>
			<a class='box' <?php echo Html::explorerLink('views/domain-users.php?id='.$group->parent_domain_user_group_id); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo htmlspecialchars($subGroup->name); ?></a>
		<?php } ?>
	<?php } ?>
	<?php foreach($subGroups as $g) { ?>
		<a class='box' <?php echo Html::explorerLink('views/domain-users.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<?php if(!empty($policyObjects)) { ?>
<div class='controls subfolders'>
	<?php foreach($policyObjects as $po) { ?>
		<a class='box' <?php echo Html::explorerLink('views/policy-object.php?id='.$po->id); ?>><img src='img/policy.dyn.svg'>&nbsp;<?php echo htmlspecialchars($po->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<div class='details-abreast margintop'>
	<div class='stickytable'>
		<table id='tblDomainUserData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('login_name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('display_name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('logons'); ?></th>
				<th class='searchable sortable'><?php echo LANG('computers'); ?></th>
				<th class='searchable sortable'><?php echo LANG('last_login'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($domainUsers as $u) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='domain_user_id[]' value='".$u->id."'></td>";
			echo "<td><a ".Html::explorerLink('views/domain-user-details.php?id='.$u->id).">".htmlspecialchars($u->username)."</a></td>";
			echo "<td>".htmlspecialchars($u->display_name)."</td>";
			echo "<td>".htmlspecialchars($u->logon_amount)."</td>";
			echo "<td>".htmlspecialchars($u->computer_amount)."</td>";
			echo "<td>".htmlspecialchars($u->timestamp?$cl->formatLoginDate($u->timestamp):'')."</td>";
			echo "</tr>";
		}
		?>
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
							<button onclick='showDialogAddDomainUserToGroup(getSelectedCheckBoxValues("domain_user_id[]", null, true))' title='<?php echo LANG('add_to_group',ENT_QUOTES); ?>'>
								<img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add'); ?>
							</button>
							<?php if($group !== null) { ?>
								<button onclick='removeSelectedDomainUserFromGroup("domain_user_id[]", <?php echo $group->id; ?>)' title='<?php echo LANG('remove_from_group',ENT_QUOTES); ?>'><img src='img/folder-remove-from.dyn.svg'>&nbsp;<?php echo LANG('remove'); ?></button>
							<?php } ?>
							<button onclick='confirmRemoveSelectedDomainUser("domain_user_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
