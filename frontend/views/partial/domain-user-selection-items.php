<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');
?>

<label class='blockListItem noSearch big loadSubList' tabindex='0' onkeypress='if(event.key=="Enter"){this.click()}'><img src='img/arrow-back.dyn.svg'><?php echo LANG('back'); ?></label>

<?php if(isset($_GET['domain_user_group_id'])) {
	$group = $db->selectDomainUserGroup($_GET['domain_user_group_id']);
	$domainUsers = [];
	if(empty($group)) $domainUsers = $db->selectAllDomainUser();
	else $domainUsers = $db->selectAllDomainUserByDomainUserGroup($group->id);

	foreach($domainUsers as $du) {
		if(!$cl->checkPermission($du, PermissionManager::METHOD_READ, false)) continue;
	?>
		<label class='blockListItem item' item_id='<?php echo htmlspecialchars($du->id,ENT_QUOTES); ?>' item_name='<?php echo htmlspecialchars($du->displayNameWithUsername(),ENT_QUOTES); ?>'>
			<input type='checkbox' name='domain_users' value='<?php echo htmlspecialchars($du->id,ENT_QUOTES); ?>' />
			<?php echo htmlspecialchars($du->displayNameWithUsername()); ?>
		</label>
	<?php
	}
	die();
} ?>

<?php if(isset($_GET['domain_user_report_id'])) {
	$reportResult = $cl->executeReport($_GET['domain_user_report_id']);

	foreach($reportResult as $row) {
		if(empty($row['domain_user_id'])) continue;
		$du = $db->selectDomainUser($row['domain_user_id']);
		if(!$cl->checkPermission($du, PermissionManager::METHOD_READ, false)) continue;
	?>
		<label class='blockListItem item' item_id='<?php echo htmlspecialchars($du->id,ENT_QUOTES); ?>' item_name='<?php echo htmlspecialchars($du->displayNameWithUsername(),ENT_QUOTES); ?>'>
			<input type='checkbox' name='domain_users' value='<?php echo htmlspecialchars($du->id,ENT_QUOTES); ?>' />
			<?php echo htmlspecialchars($du->displayNameWithUsername()); ?>
		</label>
	<?php
	}
	die();
} ?>
