<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');

if(!isset($CONTAINER_SELECTION))
	$CONTAINER_SELECTION = false;

function echoTargetPackageGroupOptions($parent=null) {
	global $db, $cl, $CONTAINER_SELECTION;

	foreach($db->selectAllPackageGroupByParentPackageGroupId($parent) as $pg) {
		if(!$cl->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$cl->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<label class='blockListItem loadSubList' target='views/partial/package-selection-items.php?package_group_id=".$pg->id."'>";
		if($CONTAINER_SELECTION)
			echo "<input type='checkbox' name='package_groups' value='".$pg->id."' onclick='event.stopPropagation()' onkeypress='if(event.key==\"Enter\"){this.parentNode.click()}' />";
		echo htmlspecialchars($pg->name);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
		echo "<div class='subgroup'>";
		echoTargetPackageGroupOptions($pg->id);
		echo "</div>";
	}
}
function echoTargetPackageReportOptions($parent=null) {
	global $db, $cl, $CONTAINER_SELECTION;

	foreach($db->selectAllReport($parent) as $r) {
		if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;

		$displayName = LANG($r->name);
		echo "<label class='blockListItem loadSubList' target='views/partial/package-selection-items.php?package_report_id=".$r->id."'>";
		if($CONTAINER_SELECTION)
			echo "<input type='checkbox' name='package_reports' value='".$r->id."' onclick='event.stopPropagation()' onkeypress='if(event.key==\"Enter\"){this.parentNode.click()}' />";
		echo htmlspecialchars($displayName);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
	}
}
?>

<h3><?php echo LANG('package_selection'); ?> (<span class='selectedItems'>0</span>/<span class='totalItems'>0</span>)</h3>
<div class='listSearch'>
	<input type='checkbox' class='toggleAll' title='<?php echo LANG('select_all'); ?>'>
	<input type='search' class='searchItems' placeholder='<?php echo LANG('search_placeholder'); ?>'>
</div>
<div class='box listItems'>
	<label class='blockListItem big noSearch loadSubList' target='views/partial/package-selection-items.php?package_group_id=-1' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><?php echo LANG('all_packages'); ?><img src='img/eye.dyn.svg' class='dragicon'></label>
	<div class='headline bold'><?php echo LANG('package_groups'); ?><div class='filler'></div></div>
	<?php echoTargetPackageGroupOptions(); ?>
	<div class='headline bold'><?php echo LANG('reports'); ?><div class='filler'></div></div>
	<?php echoTargetPackageReportOptions(); ?>
</div>
<div class='box listHome hidden'>
	<label class='blockListItem big noSearch loadSubList' target='views/partial/package-selection-items.php?package_group_id=-1' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><?php echo LANG('all_packages'); ?><img src='img/eye.dyn.svg' class='dragicon'></label>
	<div class='headline bold'><?php echo LANG('package_groups'); ?><div class='filler'></div></div>
	<?php echoTargetPackageGroupOptions(); ?>
	<div class='headline bold'><?php echo LANG('reports'); ?><div class='filler'></div></div>
	<?php echoTargetPackageReportOptions(); ?>
</div>
