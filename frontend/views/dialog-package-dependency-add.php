<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

function echoTargetPackageGroupOptions($select_package_group_ids, $parent=null, $indent=0) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllPackageGroup($parent) as $pg) {
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		$selected = '';
		if(in_array($pg->id, $select_package_group_ids)) $selected = 'checked';
		echo "<label class='block'><input type='checkbox' onchange='refreshDeployPackageList()' name='package_groups' value='".htmlspecialchars($pg->id)."' ".$selected." />".trim(str_repeat("‒",$indent)." ".htmlspecialchars($pg->name))."</label>";
		echoTargetPackageGroupOptions($select_package_group_ids, $pg->id, $indent+1);
	}
}
?>

<input type='hidden' id='txtEditPackageId'></input>
<input type='hidden' id='txtSetAsDependentPackage' value='0'></input>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<span id='spnTotalPackageGroups'>0</span>)</h3>
		<div id='divPackageGroupList' class='box'>
			<?php echoTargetPackageGroupOptions([]); ?>
		</div>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<div id='divPackageList' class='box'>
			<!-- filled by JS -->
		</div>
	</div>
</div>

<div class='controls'>
	<button class='fullwidth' onclick='if(txtSetAsDependentPackage.value=="1") addPackageDependency(getSelectedCheckBoxValues("packages"), txtEditPackageId.value); else addPackageDependency(txtEditPackageId.value, getSelectedCheckBoxValues("packages"));'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
