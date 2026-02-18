<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');

if(!isset($CONTAINER_SELECTION))
	$CONTAINER_SELECTION = false;

function echoTargetComputerGroupOptions($parent=null) {
	global $db, $cl, $CONTAINER_SELECTION;

	foreach($db->selectAllComputerGroupByParentComputerGroupId($parent) as $cg) {
		if(!$cl->checkPermission($cg, PermissionManager::METHOD_READ, false)
		&& !$cl->checkPermission($cg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<label class='blockListItem loadSubList' target='views/partial/computer-selection-items.php?computer_group_id=".$cg->id."'>";
		if($CONTAINER_SELECTION)
			echo "<input type='checkbox' name='computer_groups' value='".$cg->id."' onclick='event.stopPropagation()' onkeypress='if(event.key==\"Enter\"){this.parentNode.click()}' />";
		echo htmlspecialchars($cg->name);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
		echo "<div class='subgroup'>";
		echoTargetComputerGroupOptions($cg->id);
		echo "</div>";
	}
}
function echoTargetComputerReportOptions($parent=null) {
	global $db, $cl, $CONTAINER_SELECTION;

	foreach($db->selectAllReport($parent) as $r) {
		if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;

		$displayName = LANG($r->name);
		echo "<label class='blockListItem loadSubList' target='views/partial/computer-selection-items.php?computer_group_id=".$r->id."'>";
		if($CONTAINER_SELECTION)
			echo "<input type='checkbox' name='computer_reports' value='".$r->id."' onclick='event.stopPropagation()' onkeypress='if(event.key==\"Enter\"){this.parentNode.click()}' />";
		echo htmlspecialchars($displayName);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
	}
}
?>

<h3><?php echo LANG('computer_selection'); ?> (<span class='selectedItems'>0</span>/<span class='totalItems'>0</span>)</h3>
<div class='listSearch'>
	<input type='checkbox' class='toggleAll' title='<?php echo LANG('select_all'); ?>'>
	<input type='search' class='searchItems' placeholder='<?php echo LANG('search_placeholder'); ?>'>
</div>
<div class='box listItems'>
	<label class='blockListItem big noSearch loadSubList' target='views/partial/computer-selection-items.php?computer_group_id=-1' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><?php echo LANG('all_computers'); ?><img src='img/eye.dyn.svg' class='dragicon'></label>
	<div class='headline bold'><?php echo LANG('computer_groups'); ?><div class='filler'></div></div>
	<?php echoTargetComputerGroupOptions(); ?>
	<div class='headline bold'><?php echo LANG('reports'); ?><div class='filler'></div></div>
	<?php echoTargetComputerReportOptions(); ?>
</div>
<div class='box listHome hidden'>
	<label class='blockListItem big noSearch loadSubList' target='views/partial/computer-selection-items.php?computer_group_id=-1' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><?php echo LANG('all_computers'); ?><img src='img/eye.dyn.svg' class='dragicon'></label>
	<div class='headline bold'><?php echo LANG('computer_groups'); ?><div class='filler'></div></div>
	<?php echoTargetComputerGroupOptions(); ?>
	<div class='headline bold'><?php echo LANG('reports'); ?><div class='filler'></div></div>
	<?php echoTargetComputerReportOptions(); ?>
</div>
