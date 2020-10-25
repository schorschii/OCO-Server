<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');
?>

<div class='node'>
	<a href='#' onclick='refreshContentDomainuser()'><img src='img/users.dyn.svg'><?php echo LANG['users']; ?></a>
</div>

<div class='node'>
	<a href='#' onclick='refreshContentComputer()'><img src='img/computer.dyn.svg'><?php echo LANG['computer']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllComputerGroup() as $group) {
			echo "<a href='#' onclick='refreshContentComputer(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='refreshContentPackage()'><img src='img/software.dyn.svg'><?php echo LANG['packages']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllPackageGroup() as $group) {
			echo "<a href='#' onclick='refreshContentPackage(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='refreshContentJobContainer()'><img src='img/job.dyn.svg'><?php echo LANG['jobs']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllJobContainer() as $container) {
			echo "<a href='#' onclick='refreshContentJobContainer(".$container->id.")'><img src='img/".$db->getJobContainerIcon($container->id).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		?>
	</div>
</div>
