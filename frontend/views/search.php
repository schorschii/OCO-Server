<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(empty($_GET['query'])) {
	echo LANG['no_search_results'];
	die();
}

$counter = 0;
?>

<?php foreach($db->getAllComputerByName($_GET['query'], 5) as $c) { $counter ++; ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' onclick='event.preventDefault();closeSearchResults();refreshContentComputerDetail("<?php echo $c->id; ?>")' href='<?php echo explorerLink('views/computer-detail.php?id='.$c->id); ?>'><img src='img/computer.dyn.svg'><?php echo htmlspecialchars($c->hostname); ?></a>
	</div>
<?php } ?>
<?php foreach($db->getAllPackageFamilyByName($_GET['query'], 5) as $pf) { $counter ++; ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' onclick='event.preventDefault();closeSearchResults();refreshContentPackage("", "<?php echo $pf->id; ?>")' href='<?php echo explorerLink('views/packages.php?package_family_id='.$pf->id); ?>'><img src='img/package.dyn.svg'><?php echo htmlspecialchars($pf->name); ?></a>
	</div>
<?php } ?>
<?php foreach($db->getAllJobContainerByName($_GET['query'], 5) as $jc) { $counter ++; ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' onclick='event.preventDefault();closeSearchResults();refreshContentJobContainer("<?php echo $jc->id; ?>")' href='<?php echo explorerLink('views/job-containers.php?id='.$jc->id); ?>'><img src='img/job.dyn.svg'><?php echo htmlspecialchars($jc->name); ?></a>
	</div>
<?php } ?>
<?php foreach($db->getAllDomainuserByName($_GET['query'], 5) as $u) { $counter ++; ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' onclick='event.preventDefault();closeSearchResults();refreshContentDomainuser("<?php echo $u->id; ?>")' href='<?php echo explorerLink('views/domainuser-detail.php?id='.$u->id); ?>'><img src='img/users.dyn.svg'><?php echo htmlspecialchars($u->username); ?></a>
	</div>
<?php } ?>
<?php foreach($db->getAllReportByName($_GET['query'], 5) as $r) { $counter ++; ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' onclick='event.preventDefault();closeSearchResults();refreshContentReportDetail("<?php echo $r->id; ?>")' href='<?php echo explorerLink('views/report-detail.php?id='.$r->id); ?>'><img src='img/report.dyn.svg'><?php echo htmlspecialchars($r->name); ?></a>
	</div>
<?php } ?>

<?php
if($counter == 0) {
	echo LANG['no_search_results'];
	die();
}
?>
