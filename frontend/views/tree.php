<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');
?>

<div class='node'>
	<a href='#' onclick='event.preventDefault();refreshContentDomainuser()'><img src='img/users.dyn.svg'><?php echo LANG['users']; ?></a>
</div>

<div class='node'>
	<a href='#' onclick='event.preventDefault();refreshContentComputer()'><img src='img/computer.dyn.svg'><?php echo LANG['computer']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllComputerGroup() as $group) {
			echo "<a href='#' onclick='event.preventDefault();refreshContentComputer(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='event.preventDefault();refreshContentSoftware()'><img src='img/software.dyn.svg'><?php echo LANG['recognised_software']; ?></a>
	<div class='subnode'>
		<a href='#' onclick='event.preventDefault();refreshContentSoftware("","","other")'><img src='img/linux.dyn.svg'><?php echo LANG['linux']; ?></a>
		<a href='#' onclick='event.preventDefault();refreshContentSoftware("","","macos")'><img src='img/apple.dyn.svg'><?php echo LANG['macos']; ?></a>
		<a href='#' onclick='event.preventDefault();refreshContentSoftware("","","windows")'><img src='img/windows.dyn.svg'><?php echo LANG['windows']; ?></a>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='event.preventDefault();refreshContentPackage()'><img src='img/package.dyn.svg'><?php echo LANG['packages']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllPackageGroup() as $group) {
			echo "<a href='#' onclick='event.preventDefault();refreshContentPackage(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='event.preventDefault();refreshContentJobContainer()'><img src='img/job.dyn.svg'><?php echo LANG['jobs']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllJobContainer() as $container) {
			echo "<a href='#' onclick='event.preventDefault();refreshContentJobContainer(".$container->id.")'><img src='img/".$db->getJobContainerIcon($container->id).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='#' onclick='event.preventDefault();refreshContentReport()'><img src='img/report.dyn.svg'><?php echo LANG['reports']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllReportGroup() as $group) {
			echo "<a href='#' onclick='event.preventDefault();refreshContentReport(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		}
		?>
	</div>
</div>
