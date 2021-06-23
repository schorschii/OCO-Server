<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_POST['move_in_group']) && !empty($_POST['move_from_pos']) && !empty($_POST['move_to_pos'])) {
	$db->reorderPackageInGroup($_POST['move_in_group'], $_POST['move_from_pos'], $_POST['move_to_pos']);
	die();
}
if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		try {
			$cl->removePackage($id);
		} catch(Exception $e) {
			header('HTTP/1.1 400 Invalid Request');
			die($e->getMessage());
		}
	}
	die();
}
if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
	foreach($_POST['remove_group_id'] as $id) {
		$db->removePackageGroup($id);
	}
	die();
}
if(!empty($_POST['remove_from_group_id']) && !empty($_POST['remove_from_group_package_id']) && is_array($_POST['remove_from_group_package_id'])) {
	foreach($_POST['remove_from_group_package_id'] as $pid) {
		$db->removePackageFromGroup($pid, $_POST['remove_from_group_id']);
	}
	die();
}
if(!empty($_POST['add_group'])) {
	$insertId = -1;
	if(empty($_POST['parent_id'])) $insertId = $db->addPackageGroup($_POST['add_group']);
	else $insertId = $db->addPackageGroup($_POST['add_group'], intval($_POST['parent_id']));
	die(strval(intval($insertId)));
}
if(!empty($_POST['rename_group']) && !empty($_POST['new_name'])) {
	$db->renamePackageGroup($_POST['rename_group'], $_POST['new_name']);
	die();
}
if(!empty($_POST['add_to_group_id']) && !empty($_POST['add_to_group_package_id']) && is_array($_POST['add_to_group_package_id'])) {
	foreach($_POST['add_to_group_package_id'] as $pid) {
		if(count($db->getPackageByPackageAndGroup($pid, $_POST['add_to_group_id'])) == 0) {
			$db->addPackageToGroup($pid, $_POST['add_to_group_id']);
		}
	}
	die();
}

$group = null;
$packages = [];
if(empty($_GET['id'])) {
	$packages = $db->getAllPackage();
	echo "<h1><img src='img/package.dyn.svg'>".LANG['complete_package_library']."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='refreshContentPackageDetail()'><img src='img/add.svg'>&nbsp;".LANG['new_package']."</button> ";
	echo "<button onclick='newPackageGroup()'><img src='img/folder-new.svg'>&nbsp;".LANG['new_group']."</button> ";
	echo "</div>";
} else {
	$packages = $db->getPackageByGroup($_GET['id']);
	$group = $db->getPackageGroup($_GET['id']);
	if($group === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
	echo "<h1><img src='img/folder.dyn.svg'>".htmlspecialchars($db->getPackageGroupBreadcrumbString($group->id))."</h1>";

	echo "<div class='controls'><span>".LANG['group'].":&nbsp;</span>";
	echo "<button onclick='newPackageGroup(".$group->id.")'><img src='img/folder-new.svg'>&nbsp;".LANG['new_subgroup']."</button> ";
	echo "<button onclick='refreshContentDeploy([],[".$group->id."])'><img src='img/deploy.svg'>&nbsp;".LANG['deploy_all']."</button> ";
	echo "<button onclick='renamePackageGroup(".$group->id.", this.getAttribute(\"oldName\"))' oldName='".htmlspecialchars($group->name,ENT_QUOTES)."'><img src='img/edit.svg'>&nbsp;".LANG['rename_group']."</button> ";
	echo "<button onclick='confirmRemovePackageGroup([".$group->id."])'><img src='img/delete.svg'>&nbsp;".LANG['delete_group']."</button> ";
	echo "</div>";
}
?>

<table id='tblPackageData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageData, this.checked)'></th>
		<th class='searchable sortable'><?php echo LANG['name']; ?></th>
		<th class='searchable sortable'><?php echo LANG['version']; ?></th>
		<th class='searchable sortable'><?php echo LANG['author']; ?></th>
		<th class='searchable sortable'><?php echo LANG['description']; ?></th>
		<th class='searchable sortable'><?php echo LANG['size']; ?></th>
		<th class='searchable sortable'><?php echo LANG['order']; ?></th>
		<th class='searchable sortable'><?php echo LANG['created']; ?></th>
		<th><?php echo LANG['action']; ?></th>
	</tr>
</thead>

<tbody>
<?php
$counter = 0;
foreach($packages as $p) {
	$counter ++;
	$size = $p->getSize();
	echo "<tr>";
	echo "<td><input type='checkbox' name='package_id[]' value='".$p->id."' onchange='refreshCheckedCounter(tblPackageData)'></td>";
	echo "<td><a href='".explorerLink('views/package-detail.php?id='.$p->id)."' onclick='event.preventDefault();refreshContentPackageDetail(".$p->id.")'>".htmlspecialchars($p->name)."</a></td>";
	echo "<td>".htmlspecialchars($p->version)."</td>";
	echo "<td>".htmlspecialchars($p->author)."</td>";
	echo "<td>".htmlspecialchars(shorter($p->notes))."</td>";
	echo "<td sort_key='".htmlspecialchars($size)."'>".($size ? htmlspecialchars(niceSize($size)) : LANG['not_found'])."</td>";
	echo "<td>".htmlspecialchars($p->package_group_member_sequence ?? '-')."</td>";
	echo "<td>".htmlspecialchars($p->created)."</td>";
	echo "<td class='updown'>";
	if($group !== null) {
		echo "<button title='".LANG['move_up']."' class='updown' onclick='reorderPackageInGroup(".$group->id.", ".$p->package_group_member_sequence.", ".($p->package_group_member_sequence-1).")'><img src='img/up.svg'></button>";
		echo "<button title='".LANG['move_down']."' class='updown' onclick='reorderPackageInGroup(".$group->id.", ".$p->package_group_member_sequence.", ".($p->package_group_member_sequence+1).")'><img src='img/down.svg'></button>";
	}
	echo "</td>";
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
	<button onclick='removeSelectedPackage("package_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
