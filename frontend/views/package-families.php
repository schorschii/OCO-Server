<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$families = [];
try {
	$families = $cl->getPackageFamilies();
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG['not_found']."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG['permission_denied']."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1><img src='img/package.dyn.svg'><span id='page-title'><?php echo LANG['package_families']; ?></span></h1>
<div class='controls'>
	<button onclick='refreshContentPackageNew()' <?php if(!$currentSystemUser->checkPermission(new Package(), PermissionManager::METHOD_CREATE, false)) echo 'disabled'; ?>><img src='img/add.svg'>&nbsp;<?php echo LANG['new_package']; ?></button>
	<button onclick='createPackageGroup()' <?php if(!$currentSystemUser->checkPermission(new PackageGroup(), PermissionManager::METHOD_CREATE, false)) echo 'disabled'; ?>><img src='img/folder-new.svg'>&nbsp;<?php echo LANG['new_group']; ?></button>
	<span class='fillwidth'></span>
	<span><a <?php echo explorerLink('views/packages.php'); ?>><?php echo LANG['all_packages']; ?></a></span>
</div>

<div class='details-abreast'>
	<div>
		<table id='tblPackageFamilyData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageFamilyData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['description']; ?></th>
				<th class='searchable sortable'><?php echo LANG['count']; ?></th>
				<th class='searchable sortable'><?php echo LANG['newest']; ?></th>
				<th class='searchable sortable'><?php echo LANG['oldest']; ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		$counter = 0;
		foreach($families as $p) {
			$counter ++;
			echo "<tr>";
			echo "<td><input type='checkbox' name='package_family_id[]' value='".$p->id."' onchange='refreshCheckedCounter(tblPackageFamilyData)'></td>";
			echo "<td><a ".explorerLink('views/packages.php?package_family_id='.$p->id)." ondragstart='return false'>".htmlspecialchars($p->name)."</a></td>";
			echo "<td>".htmlspecialchars(shorter($p->notes))."</td>";
			echo "<td>".htmlspecialchars($p->package_count)."</td>";
			echo "<td>".htmlspecialchars($p->newest_package_created)."</td>";
			echo "<td>".htmlspecialchars($p->oldest_package_created)."</td>";
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
	</div>
</div>
