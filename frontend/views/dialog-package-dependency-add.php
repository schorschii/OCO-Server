<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

function echoTargetPackageGroupOptions($parent=null) {
	global $db;
	global $currentSystemUser;

	foreach($db->getAllPackageGroup($parent) as $pg) {
		if(!$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$currentSystemUser->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<a class='blockListItem' onclick='refreshDeployPackageList(".$pg->id.")'>".htmlspecialchars($pg->name)."</a>";
		echo "<div class='subgroup'>";
		echoTargetPackageGroupOptions($pg->id);
		echo "</div>";
	}
}
?>

<input type='hidden' id='txtEditPackageId'></input>
<input type='hidden' id='txtSetAsDependentPackage' value='0'></input>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<span id='spnTotalPackageGroups'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' disabled='true' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageGroupList, this.checked);refreshDeployPackageList()'>
			<input type='text' id='txtDeploySearchPackageGroups' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageGroupList, this.value)'>
		</div>
		<div id='divPackageGroupList' class='box listSearchList'>
			<a class='blockListItem' onclick='refreshDeployPackageList(-1)'><?php echo LANG['all_packages']; ?></a>
			<?php echoTargetPackageGroupOptions(); ?>
		</div>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG['select_all']; ?>' onchange='toggleCheckboxesInContainer(divPackageList, this.checked)'>
			<input type='text' id='txtDeploySearchPackages' placeholder='<?php echo LANG['search_placeholder']; ?>' oninput='searchItems(divPackageList, this.value)'>
		</div>
		<div id='divPackageList' class='box listSearchList'>
			<!-- filled by JS -->
		</div>
	</div>
</div>

<div class='controls right'>
	<button onclick="hideDialog();showLoader(false);showLoader2(false);"><img src="img/close.dyn.svg">&nbsp;<?php echo LANG['close']; ?></button>
	<button class='primary' onclick='if(txtSetAsDependentPackage.value=="1") addPackageDependency(getSelectedCheckBoxValues("packages"), [txtEditPackageId.value]); else addPackageDependency([txtEditPackageId.value], getSelectedCheckBoxValues("packages"));'><img src='img/send.white.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
