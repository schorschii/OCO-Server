<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<div class='node'>
	<a href='<?php echo explorerLink('views/domainuser.php'); ?>' onclick='event.preventDefault();refreshContentDomainuser()'><img src='img/users.dyn.svg'><?php echo LANG['users']; ?></a>
</div>

<div class='node'>
	<a href='<?php echo explorerLink('views/computer.php'); ?>' onclick='event.preventDefault();refreshContentComputer()'><img src='img/computer.dyn.svg'><?php echo LANG['computer']; ?></a>
	<?php echoComputerGroups($db); ?>
</div>

<div class='node'>
	<a href='<?php echo explorerLink('views/software.php'); ?>' onclick='event.preventDefault();refreshContentSoftware()'><img src='img/software.dyn.svg'><?php echo LANG['recognised_software']; ?></a>
	<div class='subnode'>
		<a href='<?php echo explorerLink('views/software.php?os=other'); ?>' onclick='event.preventDefault();refreshContentSoftware("","","other")'><img src='img/linux.dyn.svg'><?php echo LANG['linux']; ?></a>
		<a href='<?php echo explorerLink('views/software.php?os=macos'); ?>' onclick='event.preventDefault();refreshContentSoftware("","","macos")'><img src='img/apple.dyn.svg'><?php echo LANG['macos']; ?></a>
		<a href='<?php echo explorerLink('views/software.php?os=windows'); ?>' onclick='event.preventDefault();refreshContentSoftware("","","windows")'><img src='img/windows.dyn.svg'><?php echo LANG['windows']; ?></a>
	</div>
</div>

<div class='node'>
	<a href='<?php echo explorerLink('views/package.php'); ?>' onclick='event.preventDefault();refreshContentPackage()'><img src='img/package.dyn.svg'><?php echo LANG['packages']; ?></a>
	<?php echoPackageGroups($db); ?>
</div>

<div class='node'>
	<a href='<?php echo explorerLink('views/job-container.php'); ?>' onclick='event.preventDefault();refreshContentJobContainer()'><img src='img/job.dyn.svg'><?php echo LANG['jobs']; ?></a>
	<div class='subnode'>
		<?php
		foreach($db->getAllJobContainer() as $container) {
			echo "<a href='".explorerLink('views/job-container.php?id='.$container->id)."' onclick='event.preventDefault();refreshContentJobContainer(".$container->id.")'><img src='img/".$db->getJobContainerIcon($container->id).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a href='<?php echo explorerLink('views/report.php'); ?>' onclick='event.preventDefault();refreshContentReport()'><img src='img/report.dyn.svg'><?php echo LANG['reports']; ?></a>
	<?php echoReportGroups($db); ?>
</div>

<?php
function echoComputerGroups($db, $parent=null) {
	echo "<div class='subnode'>";
	foreach($db->getAllComputerGroup($parent) as $group) {
		echo "<a href='".explorerLink('views/computer.php?id='.$group->id)."' onclick='event.preventDefault();refreshContentComputer(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		echoComputerGroups($db, $group->id);
	}
	echo "</div>";
}
function echoPackageGroups($db, $parent=null) {
	echo "<div class='subnode'>";
	foreach($db->getAllPackageGroup($parent) as $group) {
		echo "<a href='".explorerLink('views/package.php?id='.$group->id)."' onclick='event.preventDefault();refreshContentPackage(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		echoPackageGroups($db, $group->id);
	}
	echo "</div>";
}
function echoReportGroups($db, $parent=null) {
	echo "<div class='subnode'>";
	foreach($db->getAllReportGroup($parent) as $group) {
		echo "<a href='".explorerLink('views/report.php?id='.$group->id)."' onclick='event.preventDefault();refreshContentReport(".$group->id.")'><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		echoReportGroups($db, $group->id);
	}
	echo "</div>";
}
