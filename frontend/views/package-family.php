<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
	foreach($_POST['remove_id'] as $id) {
		try {
			$db->removePackageFamily($id);
		} catch(Exception $e) {
			header('HTTP/1.1 400 Invalid Request');
			die($e->getMessage());
		}
	}
	die();
}

$families = $db->getAllPackageFamily();
echo "<h1><img src='img/package.dyn.svg'>".LANG['package_families']."</h1>";

echo "<div class='controls'>";
echo "<button onclick='refreshContentPackageDetail()'><img src='img/add.svg'>&nbsp;".LANG['new_package']."</button> ";
echo "<span><a href='".explorerLink('views/package.php')."' onclick='event.preventDefault();refreshContentPackage()'>".LANG['all_packages']."</a></span>";
echo "</div>";
?>

<table id='tblPackageFamilyData' class='list searchable sortable savesort'>
<thead>
	<tr>
		<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageFamilyData, this.checked)'></th>
		<th class='searchable sortable'><?php echo LANG['name']; ?></th>
		<!--<th class='searchable sortable'><?php echo LANG['description']; ?></th>-->
		<th class='searchable sortable'><?php echo LANG['count']; ?></th>
	</tr>
</thead>

<tbody>
<?php
$counter = 0;
foreach($families as $p) {
	$counter ++;
	echo "<tr>";
	echo "<td><input type='checkbox' name='package_family_id[]' value='".$p->id."' onchange='refreshCheckedCounter(tblPackageFamilyData)'></td>";
	echo "<td><a href='".explorerLink('views/package.php?package_family_id='.$p->id)."' onclick='event.preventDefault();refreshContentPackage(\"\", ".$p->id.")' ondragstart='return false'>".htmlspecialchars($p->name)."</a></td>";
	#echo "<td>".htmlspecialchars(shorter($p->notes))."</td>";
	echo "<td>".htmlspecialchars($p->package_count)."</td>";
	echo "</tr>";
}
?>
</tbody>

<tfoot>
	<tr>
		<td colspan='999'>
			<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
			<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
			<a href='#' onclick='event.preventDefault();downloadTableCsv("tblPackageFamilyData")'><?php echo LANG['csv']; ?></a>
		</td>
	</tr>
</tfoot>
</table>

<div class='controls'>
	<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
	<button onclick='removeSelectedPackageFamily("package_family_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>
