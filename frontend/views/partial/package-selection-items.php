<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');
?>

<label class='blockListItem noSearch big loadSubList' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><img src='img/arrow-back.dyn.svg'><?php echo LANG('back'); ?></label>

<?php if(isset($_GET['package_group_id'])) {
	$group = $db->selectPackageGroup($_GET['package_group_id']);
	$packages = [];
	if(empty($group)) $packages = $db->selectAllPackage(true);
	else $packages = $db->selectAllPackageByPackageGroupId($group->id);

	foreach($packages as $p) {
		if(!$cl->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;
	?>
		<label class='blockListItem item' item_id='<?php echo htmlspecialchars($p->id,ENT_QUOTES); ?>' item_name='<?php echo htmlspecialchars($p->getFullName(),ENT_QUOTES); ?>'>
			<input type='checkbox' name='packages' value='<?php echo htmlspecialchars($p->id,ENT_QUOTES); ?>' />
			<?php echo htmlspecialchars($p->getFullName()); ?>
		</label>
	<?php
	}
	die();
} ?>

<?php if(isset($_GET['package_report_id'])) {
	$reportResult = $cl->executeReport($_GET['package_report_id']);

	foreach($reportResult as $row) {
		if(empty($row['package_id'])) continue;
		$p = $db->selectPackage($row['package_id']);
		if(!$cl->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;
	?>
		<label class='blockListItem item' item_id='<?php echo htmlspecialchars($p->id,ENT_QUOTES); ?>' item_name='<?php echo htmlspecialchars($p->getFullName(),ENT_QUOTES); ?>'>
			<input type='checkbox' name='packages' value='<?php echo htmlspecialchars($p->id,ENT_QUOTES); ?>' />
			<?php echo htmlspecialchars($p->getFullName()); ?>
		</label>
	<?php
	}
	die();
} ?>
