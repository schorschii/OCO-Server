<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$subGroups = [];
$permissionCreatePackage = false;
$permissionCreateGroup   = false;
try {
	$families = $cl->getPackageFamilies(null, false, true);
	$subGroups = $cl->getPackageGroups(null);
	$permissionCreatePackage = $cl->checkPermission(new Models\Package(), PermissionManager::METHOD_CREATE, false) && $cl->checkPermission(new Models\PackageFamily(), PermissionManager::METHOD_CREATE, false);
	$permissionCreateGroup   = $cl->checkPermission(new Models\PackageGroup(), PermissionManager::METHOD_CREATE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1><img src='img/package-family.dyn.svg'><span id='page-title'><?php echo LANG('package_families'); ?></span></h1>
<div class='controls'>
	<button onclick='refreshContentPackageNew()' <?php if(!$permissionCreatePackage) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_package'); ?></button>
	<button onclick='createPackageGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_group'); ?></button>
	<span class='filler'></span>
	<span><a <?php echo explorerLink('views/packages.php'); ?>><?php echo LANG('all_packages'); ?></a></span>
</div>

<?php if(!empty($subGroups)) { ?>
<div class='controls subfolders'>
	<?php foreach($subGroups as $g) { ?>
		<a class='box' <?php echo explorerLink('views/packages.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblPackageFamilyData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('description'); ?></th>
				<th class='searchable sortable'><?php echo LANG('count'); ?></th>
				<th class='searchable sortable'><?php echo LANG('newest'); ?></th>
				<th class='searchable sortable'><?php echo LANG('oldest'); ?></th>
				<th class='searchable sortable'><?php echo LANG('licenses'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($families as $p) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='package_family_id[]' value='".$p->id."'></td>";
			echo "<td><a ".explorerLink('views/packages.php?package_family_id='.$p->id)." ondragstart='return false'>".htmlspecialchars($p->name)."</a></td>";
			echo "<td>".htmlspecialchars(shorter($p->notes))."</td>";
			echo "<td>".htmlspecialchars($p->package_count)."</td>";
			echo "<td>".htmlspecialchars($p->newest_package_created??'')."</td>";
			echo "<td>".htmlspecialchars($p->oldest_package_created??'')."</td>";
			if($p->license_count !== null && $p->license_count >= 0) {
				$licenseUsed = $p->install_count;
				$licensePercent = $p->license_count==0 ? 100 : $licenseUsed * 100 / $p->license_count;
				echo "<td>".progressBar($licensePercent, null, null, 'stretch', '', '('.$licenseUsed.'/'.$p->license_count.')')."</td>";
			} else {
				echo "<td>-</td>";
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
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
							<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
						</div>
						<div class='controls'>
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<button onclick='removeSelectedPackageFamily("package_family_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
