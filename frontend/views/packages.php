<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$group = null;
$packages = [];
if(!empty($_GET['id'])) {
	$packages = $db->getPackageByGroup($_GET['id']);
	$group = $db->getPackageGroup($_GET['id']);
	if($group === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
	echo "<h1><img src='img/folder.dyn.svg'><span id='page-title'>".htmlspecialchars($db->getPackageGroupBreadcrumbString($group->id))."</span><span id='spnPackageGroupName' class='rawvalue'>".htmlspecialchars($group->name)."</span></h1>";

	echo "<div class='controls'><span>".LANG['group'].":&nbsp;</span>";
	echo "<button onclick='newPackageGroup(".$group->id.")'><img src='img/folder-new.svg'>&nbsp;".LANG['new_subgroup']."</button> ";
	echo "<button onclick='refreshContentDeploy([],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;".LANG['deploy_all']."</button> ";
	echo "<button onclick='renamePackageGroup(".$group->id.", spnPackageGroupName.innerText)'><img src='img/edit.svg'>&nbsp;".LANG['rename_group']."</button> ";
	echo "<button onclick='confirmRemovePackageGroup([".$group->id."], event, spnPackageGroupName.innerText)'><img src='img/delete.svg'>&nbsp;".LANG['delete_group']."</button> ";
	echo "</div>";
} elseif(!empty($_GET['package_family_id'])) {
	$packages = $db->getPackageByFamily($_GET['package_family_id']);
	$family = $db->getPackageFamily($_GET['package_family_id']);
	if($family === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
	echo "<h1><img src='".$family->getIcon()."'><span id='page-title'><span id='spnPackageFamilyName'>".htmlspecialchars($family->name)."</span></span></h1>";

	echo "<div class='controls'>";
	echo "<button onclick='refreshContentPackageNew(\"".htmlspecialchars($family->name,ENT_QUOTES)."\")'><img src='img/add.svg'>&nbsp;".LANG['new_version']."</button> ";
	echo "<button onclick='renamePackageFamily(".$family->id.", spnPackageFamilyName.innerText)'><img src='img/edit.svg'>&nbsp;".LANG['rename']."</button>";
	echo "<button onclick='editPackageFamilyNotes(".$family->id.", spnPackageFamilyNotes.innerText)'><img src='img/edit.svg'>&nbsp;".LANG['edit_description']."</button>";
	echo "<button class='".(!empty($family->icon)?'nomarginright':'')."' onclick='fleIcon.click()'><img src='img/image-add.svg'>&nbsp;".LANG['change_icon']."</button>";
	if(!empty($family->icon)) echo "<button onclick='removePackageFamilyIcon(".$family->id.")'><img src='img/image-remove.svg'>&nbsp;".LANG['remove_icon']."</button>";
	echo "<button onclick='currentExplorerContentUrl=\"views/package-families.php\";confirmRemovePackageFamily([".htmlspecialchars($family->id,ENT_QUOTES)."], spnPackageFamilyName.innerText)'><img src='img/delete.svg'>&nbsp;".LANG['delete_package_family']."</button> ";
	echo "<span class='fillwidth'></span> ";
	echo "<span><a ".explorerLink('views/package-families.php').">".LANG['package_families']."</a></span>";
	echo "</div>";
	echo "<input type='file' id='fleIcon' style='display:none' onchange='editPackageFamilyIcon(".$family->id.", this.files[0])'></input>";
	echo "<span id='spnPackageFamilyNotes'>";
	if(!empty($family->notes)) echo "<p class='quote'>".nl2br(htmlspecialchars($family->notes))."</p>";
	echo "</span>";
} else {
	$packages = $db->getAllPackage();
	echo "<h1><img src='img/package.dyn.svg'><span id='page-title'>".LANG['complete_package_library']."</span></h1>";

	echo "<div class='controls'>";
	echo "<button onclick='refreshContentPackageNew()'><img src='img/add.svg'>&nbsp;".LANG['new_package']."</button> ";
	echo "<button onclick='newPackageGroup()'><img src='img/folder-new.svg'>&nbsp;".LANG['new_group']."</button> ";
	echo "<span class='fillwidth'></span> ";
	echo "<span><a ".explorerLink('views/package-families.php').">".LANG['package_families']."</a></span>";
	echo "</div>";
}
?>

<div class='details-abreast'>
	<div>
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

			echo "<td><a ".explorerLink('views/package-details.php?id='.$p->id)." ondragstart='return false'>".htmlspecialchars($p->name)."</a></td>";
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
					<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
					<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
					<a href='#' onclick='event.preventDefault();downloadTableCsv("tblPackageData")'><?php echo LANG['csv']; ?></a>
				</td>
			</tr>
		</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='deploySelectedPackage("package_id[]")'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
			<button onclick='addSelectedPackageToGroup("package_id[]", sltNewGroup.value)'><img src='img/folder-insert-into.svg'>
				&nbsp;<?php echo LANG['add_to']; ?>
				<select id='sltNewGroup' onclick='event.stopPropagation()'>
					<?php echoPackageGroupOptions($db); ?>
				</select>
			</button>
			<?php if($group !== null) { ?>
				<button onclick='removeSelectedPackageFromGroup("package_id[]", <?php echo $group->id; ?>)'><img src='img/folder-remove-from.svg'>&nbsp;<?php echo LANG['remove_from_group']; ?></button>
			<?php } ?>
			<button onclick='removeSelectedPackage("package_id[]", null, event)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
</div>
