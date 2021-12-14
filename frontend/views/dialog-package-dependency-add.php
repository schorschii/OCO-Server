<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

function echoTargetPackageGroupOptions($db, $select_package_group_ids, $parent=null, $indent=0) {
	foreach($db->getAllPackageGroup($parent) as $pg) {
		$selected = '';
		if(in_array($pg->id, $select_package_group_ids)) $selected = 'selected';
		echo "<option value='".htmlspecialchars($pg->id)."' ".$selected.">".trim(str_repeat("‒",$indent)." ".htmlspecialchars($pg->name))."</option>";
		echoTargetPackageGroupOptions($db, $select_package_group_ids, $pg->id, $indent+1);
	}
}
?>

<input type='hidden' id='txtEditPackageId'></input>
<input type='hidden' id='txtSetAsDependentPackage' value='0'></input>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?> (<span id='spnSelectedPackageGroups'>0</span>/<span id='spnTotalPackageGroups'>0</span>)</h3>
		<select id='sltPackageGroup' size='10' onchange='refreshDeployComputerAndPackages(null, this.value)'>
			<option value='-1' selected='true'><?php echo LANG['all_packages']; ?></option>
			<?php echoTargetPackageGroupOptions($db, []); ?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<select id='sltPackage' size='10' onchange='refreshDeployCount()'>
			<!-- filled by JS -->
		</select>
	</div>
</div>

<div class='controls'>
	<button class='fullwidth' onclick='if(txtSetAsDependentPackage.value=="1") addPackageDependency(sltPackage.value, txtEditPackageId.value); else addPackageDependency(txtEditPackageId.value, sltPackage.value);'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>
