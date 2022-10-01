<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<div class='node expandable'>
	<a <?php echo explorerLink('views/computers.php'); ?>><img src='img/computer.dyn.svg'><?php echo LANG('my_computers'); ?></a>
	<div id='divSubnodeComputers' class='subitems'>
	<?php
	$computers = $cl->getMyComputers();
	echo "<div class='subnode'>";
	foreach($computers as $c) {
		echo "<a ".explorerLink('views/computers.php?id='.$c->id)."><img src='".$c->getIcon()."'>".htmlspecialchars($c->hostname)."</a>";
	}
	echo "</div>";
	?>
	</div>
</div>

<div class='node expandable'>
	<a <?php echo explorerLink('views/packages.php'); ?>><img src='img/package.dyn.svg'><?php echo LANG('available_packages'); ?></a>
	<div id='divSubnodePackages' class='subitems'>
	<?php
	$packages = $cl->getMyPackages();
	echo "<div class='subnode'>";
	foreach($packages as $p) {
		echo "<a ".explorerLink('views/packages.php?id='.$p->id)."><img src='".$p->getIcon()."'>".htmlspecialchars($p->package_family_name)."</a>";
	}
	echo "</div>";
	?>
	</div>
</div>

<div class='node expandable'>
	<a <?php echo explorerLink('views/job-containers.php'); ?>><img src='img/job.dyn.svg'><?php echo LANG('my_jobs'); ?></a>
	<div id='divSubnodeJobs' class='subitems'>
	<?php
	$jobContainers = $cl->getMyJobContainers();
	echo "<div class='subnode'>";
	foreach($jobContainers as $jc) {
		echo "<a ".explorerLink('views/job-containers.php?id='.$jc->id)."><img src='img/".$jc->getStatus($db->selectAllStaticJobByJobContainer($jc->id)).".dyn.svg'>".htmlspecialchars($jc->name)."</a>";
	}
	echo "</div>";
	?>
	</div>
</div>
