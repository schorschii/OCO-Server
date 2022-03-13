<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

function echoTargetPackageGroupOptions($parent=null, $indent=0) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllPackageGroup($parent) as $pg) {
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<a class='blockListItem' onclick='refreshDeployPackageList(".$pg->id.")'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($pg->name))."</a>";
		echoTargetPackageGroupOptions($pg->id, $indent+1);
	}
}
?>

<input type='hidden' id='txtEditPackageId'></input>
<input type='hidden' id='txtSetAsDependentPackage' value='0'></input>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<span id='spnTotalPackageGroups'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageGroupList, this.checked);refreshDeployPackageList()'>
			<input type='text' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageGroupList, this.value)'>
		</div>
		<div id='divPackageGroupList' class='box'>
			<a class='blockListItem' onclick='refreshDeployPackageList(-1)'><?php echo LANG['all_packages']; ?></a>
			<?php echoTargetPackageGroupOptions(); ?>
		</div>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageList, this.checked)'>
			<input type='text' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageList, this.value)'>
		</div>
		<div id='divPackageList' class='box'>
			<!-- filled by JS -->
		</div>
	</div>
</div>

<div class='controls'>
	<button class='fullwidth' onclick='if(txtSetAsDependentPackage.value=="1") addPackageDependency(getSelectedCheckBoxValues("packages"), [txtEditPackageId.value]); else addPackageDependency([txtEditPackageId.value], getSelectedCheckBoxValues("packages"));'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
