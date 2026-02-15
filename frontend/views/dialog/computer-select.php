<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

function echoTargetComputerGroupOptions($parent=null) {
	global $db;
	global $cl;

	foreach($db->selectAllComputerGroupByParentComputerGroupId($parent) as $cg) {
		if(!$cl->checkPermission($cg, PermissionManager::METHOD_READ, false)
		&& !$cl->checkPermission($cg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<label class='blockListItem' onclick='refreshDeployComputerList(".$cg->id.");return false'>";
		echo htmlspecialchars($cg->name);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</label>";
		echo "<div class='subgroup'>";
		echoTargetComputerGroupOptions($cg->id);
		echo "</div>";
	}
}
?>

<input type='hidden' name='subject_id' value='<?php echo htmlspecialchars($_GET['subject_id']??'',ENT_QUOTES); ?>'></input>
<div class='gallery'>
	<div>
		<h3><?php echo LANG('computer_selection'); ?> (<span id='spnSelectedComputers'>0</span>/<span id='spnTotalComputers'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divComputerList, this.checked);refreshDeployComputerCount()'>
			<input type='search' id='txtDeploySearchComputers' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divComputerList, this.value)'>
		</div>
		<div id='divComputerList' class='box listSearchList'>
			<a href='#' class='blockListItem big noSearch' onclick='refreshDeployComputerList(-1);return false'><?php echo LANG('all_computers'); ?><img src='img/eye.dyn.svg' class='dragicon'></a>
			<?php echoTargetComputerGroupOptions(); ?>
		</div>
		<div id='divComputerListHome' class='box listSearchList hidden'>
			<a href='#' class='blockListItem big noSearch' onclick='refreshDeployComputerList(-1);return false'><?php echo LANG('all_computers'); ?><img src='img/eye.dyn.svg' class='dragicon'></a>
			<?php echoTargetComputerGroupOptions(); ?>
		</div>
	</div>
</div>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='assign'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
