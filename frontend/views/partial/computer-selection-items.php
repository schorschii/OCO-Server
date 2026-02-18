<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');
?>

<label class='blockListItem noSearch big loadSubList' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><img src='img/arrow-back.dyn.svg'><?php echo LANG('back'); ?></label>

<?php if(isset($_GET['computer_group_id'])) {
	$group = $db->selectComputerGroup($_GET['computer_group_id']);
	$computers = [];
	if(empty($group)) $computers = $db->selectAllComputer();
	else $computers = $db->selectAllComputerByComputerGroupId($group->id);

	foreach($computers as $c) {
		if(!$cl->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
	?>
		<label class='blockListItem item' item_id='<?php echo htmlspecialchars($c->id,ENT_QUOTES); ?>' item_name='<?php echo htmlspecialchars($c->hostname,ENT_QUOTES); ?>'>
			<input type='checkbox' name='computers' value='<?php echo htmlspecialchars($c->id,ENT_QUOTES); ?>' />
			<?php echo htmlspecialchars($c->hostname); ?>
		</label>
	<?php
	}
	die();
} ?>

<?php if(isset($_GET['computer_report_id'])) {
	$reportResult = $cl->executeReport($_GET['computer_report_id']);

	foreach($reportResult as $row) {
		if(empty($row['computer_id'])) continue;
		$c = $db->selectComputer($row['computer_id']);
		if(!$cl->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;
	?>
		<label class='blockListItem item' item_id='<?php echo htmlspecialchars($c->id,ENT_QUOTES); ?>' item_name='<?php echo htmlspecialchars($c->hostname,ENT_QUOTES); ?>'>
			<input type='checkbox' name='computers' value='<?php echo htmlspecialchars($c->id,ENT_QUOTES); ?>' />
			<?php echo htmlspecialchars($c->hostname); ?>
		</label>
	<?php
	}
	die();
} ?>
