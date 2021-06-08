<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(empty($_GET['query'])) {
	echo LANG['no_search_results'];
	die();
}

$counter = 0;
?>

<?php foreach($db->getAllComputerByName($_GET['query'], 10) as $c) { $counter ++; ?>
	<div class='node'>
		<a onclick='event.preventDefault();closeSearchResults();refreshContentComputerDetail("<?php echo $c->id; ?>")' href='<?php echo explorerLink('views/computer_detail.php?id='.$c->id); ?>'><img src='img/computer.dyn.svg'><?php echo htmlspecialchars($c->hostname); ?></a>
	</div>
<?php } ?>

<?php foreach($db->getAllPackageByName($_GET['query'], 10) as $p) { $counter ++; ?>
	<div class='node'>
		<a onclick='event.preventDefault();closeSearchResults();refreshContentPackageDetail("<?php echo $p->id; ?>")' href='<?php echo explorerLink('views/package_detail.php?id='.$p->id); ?>'><img src='img/package.dyn.svg'><?php echo htmlspecialchars($p->name).' ('.htmlspecialchars($p->version).')'; ?></a>
	</div>
<?php } ?>

<?php foreach($db->getAllJobContainerByName($_GET['query'], 10) as $jc) { $counter ++; ?>
	<div class='node'>
		<a onclick='event.preventDefault();closeSearchResults();refreshContentJobContainer("<?php echo $jc->id; ?>")' href='<?php echo explorerLink('views/job_container.php?id='.$jc->id); ?>'><img src='img/job.dyn.svg'><?php echo htmlspecialchars($jc->name); ?></a>
	</div>
<?php } ?>

<?php
if($counter == 0) {
	echo LANG['no_search_results'];
	die();
}
?>
