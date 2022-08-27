<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

if(empty($_GET['query'])) {
	echo LANG['no_search_results'];
	die();
}

$counter = 0;
?>

<?php foreach($db->getAllComputerByName($_GET['query'], 5) as $c) {
	if(!$currentSystemUser->checkPermission($c, PermissionManager::METHOD_READ, false)) continue;
	$counter ++;
?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/computer-details.php?id='.$c->id, 'closeSearchResults()'); ?>><img src='img/computer.dyn.svg'><?php echo htmlspecialchars($c->hostname); ?></a>
	</div>
<?php } ?>

<?php foreach($db->getAllPackageFamilyByName($_GET['query'], 5) as $pf) {
	if(!$currentSystemUser->checkPermission($pf, PermissionManager::METHOD_READ, false)) continue;
	$counter ++;
?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/packages.php?package_family_id='.$pf->id, 'closeSearchResults()'); ?>><img src='img/package.dyn.svg'><?php echo htmlspecialchars($pf->name); ?></a>
	</div>
<?php } ?>

<?php foreach($db->getAllJobContainerByName($_GET['query'], 5) as $jc) {
	if(!$currentSystemUser->checkPermission($jc, PermissionManager::METHOD_READ, false)) continue;
	$counter ++;
?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/job-containers.php?id='.$jc->id, 'closeSearchResults()'); ?>><img src='img/job.dyn.svg'><?php echo htmlspecialchars($jc->name); ?></a>
	</div>
<?php } ?>

<?php foreach($db->getAllDomainUserByName($_GET['query'], 5) as $u) {
	if(!$currentSystemUser->checkPermission($u, PermissionManager::METHOD_READ, false)) continue;
	$counter ++;
?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/domain-users.php?id='.$u->id, 'closeSearchResults()'); ?>><img src='img/user.dyn.svg'><?php echo htmlspecialchars($u->displayNameWithUsername()); ?></a>
	</div>
<?php } ?>

<?php foreach($db->getAllReportByName($_GET['query'], 5) as $r) {
	if(!$currentSystemUser->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	$counter ++;
?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/report-details.php?id='.$r->id, 'closeSearchResults()'); ?>><img src='img/report.dyn.svg'><?php echo htmlspecialchars($r->name); ?></a>
	</div>
<?php } ?>

<?php
if($counter == 0) {
	die(LANG['no_search_results']);
}
?>
