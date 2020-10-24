<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');
?>

<div class='node'>
	<a href='#' onclick='refreshContentDomainuser()'><img src='img/users.dyn.svg'>Benutzer</a>
</div>

<div class='node'>
	<a href='#' onclick='refreshContentComputer()'><img src='img/computer.dyn.svg'>Computer</a>
	<div class='subnode'>
		<?php
		foreach($db->getAllComputerGroup() as $group) {
			echo "<a href='#' onclick='refreshContentComputer(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='refreshContentPackage()'><img src='img/software.dyn.svg'>Pakete</a>
	<div class='subnode'>
		<?php
		foreach($db->getAllPackageGroup() as $group) {
			echo "<a href='#' onclick='refreshContentPackage(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='refreshContentJobContainer()'><img src='img/job.dyn.svg'>Jobs</a>
	<div class='subnode'>
		<?php
		foreach($db->getAllJobContainer() as $container) {
			echo "<a href='#' onclick='refreshContentJobContainer(".$container->id.")'><img src='img/".$db->getJobContainerIcon($container->id).".svg'>".htmlspecialchars($container->name)."</a>";
		}
		?>
	</div>
</div>
