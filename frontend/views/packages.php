<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$group = null;
$family = null;
$packages = [];
$subGroups = [];
try {
	if(!empty($_GET['id'])) {
		$group = $cl->getPackageGroup($_GET['id']);
		$packages = $cl->getPackages($group);
	} elseif(!empty($_GET['package_family_id'])) {
		$family = $cl->getPackageFamily($_GET['package_family_id']);
		$packages = $cl->getPackages($family);
	} else {
		$packages = $cl->getPackages();
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG['not_found']."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG['permission_denied']."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if($group !== null) {
	$subGroups = $cl->getPackageGroups($group->id);
	$permissionCreate = $currentSystemUser->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionDeploy = !empty($packages) && $currentSystemUser->checkPermission($packages[0], PermissionManager::METHOD_DEPLOY, false);
	$permissionWrite  = $currentSystemUser->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $currentSystemUser->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($db->getPackageGroupBreadcrumbString($group->id)); ?></span><span id='spnPackageGroupName' class='rawvalue'><?php echo htmlspecialchars($group->name); ?></span></h1>
	<div class='controls'>
		<button onclick='createPackageGroup(<?php echo $group->id; ?>)' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG['new_subgroup']; ?></button>
		<button onclick='refreshContentDeploy([],[<?php echo $group->id; ?>])' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG['deploy_all']; ?></button>
		<button onclick='renamePackageGroup(<?php echo $group->id; ?>, spnPackageGroupName.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['rename_group']; ?></button>
		<button onclick='confirmRemovePackageGroup([<?php echo $group->id; ?>], event, spnPackageGroupName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete_group']; ?></button>
	</div>
	<div class='controls subfolders'>
		<?php if($group->parent_package_group_id == null) { ?>
			<a class='box' <?php echo explorerLink('views/package-families.php'); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo LANG['package_families']; ?></a>
		<?php } else { $subGroup = $cl->getPackageGroup($group->parent_package_group_id); ?>
			<a class='box' <?php echo explorerLink('views/packages.php?id='.$group->parent_package_group_id); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo htmlspecialchars($subGroup->name); ?></a>
		<?php } ?>
		<?php foreach($subGroups as $g) { ?>
			<a class='box' <?php echo explorerLink('views/packages.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
		<?php } ?>
	</div>
<?php } elseif($family !== null) {
	$permissionCreate = $currentSystemUser->checkPermission(new Package(), PermissionManager::METHOD_CREATE, false) && $currentSystemUser->checkPermission($family, PermissionManager::METHOD_CREATE, false);
	$permissionWrite  = $currentSystemUser->checkPermission($family, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $currentSystemUser->checkPermission($family, PermissionManager::METHOD_DELETE, false);
?>
	<h1><img src='<?php echo $family->getIcon(); ?>'><span id='page-title'><span id='spnPackageFamilyName'><?php echo htmlspecialchars($family->name); ?></span></span></h1>
	<div class='controls'>
		<button onclick='refreshContentPackageNew("<?php echo htmlspecialchars($family->name,ENT_QUOTES); ?>")' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG['new_version']; ?></button>
		<button onclick='renamePackageFamily(<?php echo $family->id; ?>, spnPackageFamilyName.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['rename']; ?></button>
		<button onclick='editPackageFamilyNotes(<?php echo $family->id; ?>, spnPackageFamilyNotes.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['edit_description']; ?></button>
		<button class='<?php echo (!empty($family->icon)?'nomarginright':''); ?>' onclick='fleIcon.click()' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/image-add.dyn.svg'>&nbsp;<?php echo LANG['change_icon']; ?></button>
		<?php if(!empty($family->icon)) { ?>
			<button onclick='removePackageFamilyIcon(<?php echo $family->id; ?>)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/image-remove.dyn.svg'>&nbsp;<?php echo LANG['remove_icon']; ?></button>
		<?php } ?>
		<button onclick='currentExplorerContentUrl="views/package-families.php";confirmRemovePackageFamily([<?php echo htmlspecialchars($family->id,ENT_QUOTES); ?>], spnPackageFamilyName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete_package_family']; ?></button>
		<span class='fillwidth'></span>
		<span><a <?php echo explorerLink('views/package-families.php'); ?>><?php echo LANG['package_families']; ?></a></span>
	</div>
	<input type='file' id='fleIcon' style='display:none' onchange='editPackageFamilyIcon(<?php echo $family->id; ?>, this.files[0])'></input>
	<span id='spnPackageFamilyNotes'>
	<?php if(!empty($family->notes)) { ?>
		<p class='quote'><?php echo nl2br(htmlspecialchars($family->notes)); ?></p>
	<?php } ?>
	</span>
<?php } else {
	$subGroups = $cl->getPackageGroups(null);
	$permissionCreatePackage = $currentSystemUser->checkPermission(new Package(), PermissionManager::METHOD_CREATE, false) && $currentSystemUser->checkPermission(new PackageFamily(), PermissionManager::METHOD_CREATE, false);
	$permissionCreateGroup   = $currentSystemUser->checkPermission(new PackageGroup(), PermissionManager::METHOD_CREATE, false);
?>
	<h1><img src='img/package.dyn.svg'><span id='page-title'><?php echo LANG['complete_package_library']; ?></span></h1>
	<div class='controls'>
		<button onclick='refreshContentPackageNew()' <?php if(!$permissionCreatePackage) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG['new_package']; ?></button>
		<button onclick='createPackageGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG['new_group']; ?></button>
		<span class='fillwidth'></span>
		<span><a <?php echo explorerLink('views/package-families.php'); ?>><?php echo LANG['package_families']; ?></a></span>
	</div>
	<?php if(!empty($subGroups)) { ?>
	<div class='controls subfolders'>
		<?php foreach($subGroups as $g) { ?>
			<a class='box' <?php echo explorerLink('views/packages.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
		<?php } ?>
	</div>
	<?php } ?>
<?php } ?>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblPackageData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['version']; ?></th>
				<th class='searchable sortable'><?php echo LANG['author']; ?></th>
				<th class='searchable sortable'><?php echo LANG['size']; ?></th>
				<th class='searchable sortable'><?php echo LANG['description']; ?></th>
				<th class='searchable sortable'><?php echo LANG['created']; ?></th>
				<?php if($group !== null) { ?>
					<th class='searchable sortable'><?php echo LANG['order']; ?></th>
					<th><?php echo LANG['move']; ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
		<?php
		$counter = 0;
		foreach($packages as $p) {
			$counter ++;
			$size = $p->getSize();
			if($group !== null) echo "<tr class='draggable nodrag' ondragstart='return dragStartPackageTable(event)' ondragover='dragOverPackageTable(event)' ondragend='return dragEndPackageTable(event, ".$group->id.")'>"; else echo "<tr>";

			if($group !== null) echo "<td><input type='checkbox' name='package_id[]' value='".$p->id."' onchange='refreshCheckedCounter(tblPackageData)' onkeyup='handlePackageReorderByKeyboard(event, ".$group->id.", ".$p->package_group_member_sequence.")'></td>";
			else echo "<td><input type='checkbox' name='package_id[]' value='".$p->id."' onchange='refreshCheckedCounter(tblPackageData)'></td>";

			echo "<td><a ".explorerLink('views/package-details.php?id='.$p->id)." ondragstart='return false'>".htmlspecialchars($p->package_family_name)."</a></td>";
			echo "<td><a ".explorerLink('views/package-details.php?id='.$p->id)." ondragstart='return false'>".htmlspecialchars($p->version)."</a></td>";
			echo "<td>".htmlspecialchars($p->author)."</td>";
			echo "<td sort_key='".htmlspecialchars($size)."'>".($size ? htmlspecialchars(niceSize($size)) : LANG['not_found'])."</td>";
			echo "<td>".htmlspecialchars(shorter($p->notes))."</td>";
			echo "<td>".htmlspecialchars($p->created)."</td>";

			if($group !== null) {
				echo "<td>".htmlspecialchars($p->package_group_member_sequence ?? '-')."</td>";
				echo "<td class='drag' title='".LANG['reorder_drag_drop_description']."'><img src='img/reorder.dyn.svg'></td>";
			}
			echo "</tr>";
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
							<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
						</div>
						<div class='controls'>
							<button onclick='event.preventDefault();downloadTableCsv("tblPackageData")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG['csv']; ?></button>
							<button onclick='deploySelectedPackage("package_id[]")'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
							<button onclick='showDialogAddPackageToGroup(getSelectedCheckBoxValues("package_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG['add_to']; ?></button>
							<?php if($group !== null) { ?>
								<button onclick='removeSelectedPackageFromGroup("package_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.dyn.svg'>&nbsp;<?php echo LANG['remove_from_group']; ?></button>
							<?php } ?>
							<button onclick='removeSelectedPackage("package_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
