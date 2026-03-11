<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');

if(!isset($CONTAINER_SELECTION))
	$CONTAINER_SELECTION = false;

function echoTargetDomainUserGroupOptions($parent=null) {
	global $db, $cl, $CONTAINER_SELECTION;

	foreach($db->selectAllDomainUserGroupByParentDomainUserGroupId($parent) as $pg) {
		if(!$cl->checkPermission($pg, PermissionManager::METHOD_READ, false)) continue;

		echo "<label class='blockListItem loadSubList' target='views/partial/domain-user-selection-items.php?domain_user_group_id=".$pg->id."'>";
		if($CONTAINER_SELECTION)
			echo "<input type='checkbox' name='domain_user_groups' value='".$pg->id."' onclick='event.stopPropagation()' onkeypress='if(event.key==\"Enter\"){this.parentNode.click()}' />";
		echo htmlspecialchars($pg->name);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
		echo "<div class='subgroup'>";
		echoTargetDomainUserGroupOptions($pg->id);
		echo "</div>";
	}
}
function echoTargetDomainUserReportOptions() {
	global $db, $cl, $CONTAINER_SELECTION;

	foreach($db->selectAllReport() as $r) {
		if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;

		$displayName = LANG($r->name);
		echo "<label class='blockListItem loadSubList' target='views/partial/domain-user-selection-items.php?domain_user_report_id=".$r->id."'>";
		if($CONTAINER_SELECTION)
			echo "<input type='checkbox' name='domain_user_reports' value='".$r->id."' onclick='event.stopPropagation()' onkeypress='if(event.key==\"Enter\"){this.parentNode.click()}' />";
		echo htmlspecialchars($displayName);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
	}
}
?>

<h3><?php echo LANG('domain_user_selection'); ?> (<span class='selectedItems'>0</span>/<span class='totalItems'>0</span>)</h3>
<div class='listSearch'>
	<input type='checkbox' title='<?php echo LANG('select_all'); ?>' class='toggleAll'>
	<input type='search' class='searchItems' placeholder='<?php echo LANG('search_placeholder'); ?>'>
</div>
<div class='box listItems'>
	<label class='blockListItem big noSearch loadSubList' target='views/partial/domain-user-selection-items.php?domain_user_group_id=-1' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><?php echo LANG('all_domain_users'); ?><img src='img/eye.dyn.svg' class='dragicon'></label>
	<div class='headline bold'><?php echo LANG('domain_user_groups'); ?><div class='filler'></div></div>
	<?php echoTargetDomainUserGroupOptions(); ?>
	<div class='headline bold'><?php echo LANG('reports'); ?><div class='filler'></div></div>
	<?php echoTargetDomainUserReportOptions(); ?>
</div>
<div class='box listHome hidden'>
	<label class='blockListItem big noSearch loadSubList' target='views/partial/domain-user-selection-items.php?domain_user_group_id=-1' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><?php echo LANG('all_domain_users'); ?><img src='img/eye.dyn.svg' class='dragicon'></label>
	<div class='headline bold'><?php echo LANG('domain_user_groups'); ?><div class='filler'></div></div>
	<?php echoTargetDomainUserGroupOptions(); ?>
	<div class='headline bold'><?php echo LANG('reports'); ?><div class='filler'></div></div>
	<?php echoTargetDomainUserReportOptions(); ?>
</div>
