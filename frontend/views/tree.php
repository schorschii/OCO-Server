<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<div class='node'>
	<a <?php echo explorerLink('views/domain-users.php'); ?>><img src='img/users.dyn.svg'><?php echo LANG['users']; ?></a>
</div>

<div class='node'>
	<a <?php echo explorerLink('views/computers.php'); ?>><img src='img/computer.dyn.svg'><?php echo LANG['computer']; ?></a>
	<?php echoComputerGroups($cl); ?>
</div>

<?php if($currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SOFTWARE_VIEW, false)) { ?>
<div class='node'>
	<a <?php echo explorerLink('views/software.php'); ?>><img src='img/software.dyn.svg'><?php echo LANG['recognised_software']; ?></a>
	<div class='subnode'>
		<a <?php echo explorerLink('views/software.php?os=other'); ?>><img src='img/linux.dyn.svg'><?php echo LANG['linux']; ?></a>
		<a <?php echo explorerLink('views/software.php?os=macos'); ?>><img src='img/apple.dyn.svg'><?php echo LANG['macos']; ?></a>
		<a <?php echo explorerLink('views/software.php?os=windows'); ?>><img src='img/windows.dyn.svg'><?php echo LANG['windows']; ?></a>
	</div>
</div>
<?php } ?>

<div class='node'>
	<a <?php echo explorerLink('views/package-families.php'); ?>><img src='img/package.dyn.svg'><?php echo LANG['packages']; ?></a>
	<?php echoPackageGroups($cl); ?>
</div>

<div class='node'>
	<a <?php echo explorerLink('views/job-containers.php'); ?>><img src='img/job.dyn.svg'><?php echo LANG['jobs']; ?></a>
	<div class='subnode'>
		<?php
		foreach($cl->getJobContainers() as $container) {
			echo "<a ".explorerLink('views/job-containers.php?id='.$container->id)."><img src='img/".$db->getJobContainerIcon($container->id).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		?>
	</div>
</div>

<div class='node'>
	<a <?php echo explorerLink('views/reports.php'); ?>><img src='img/report.dyn.svg'><?php echo LANG['reports']; ?></a>
	<?php echoReportGroups($cl); ?>
</div>

<?php
// include add-ons
foreach(glob(__DIR__.'/tree.d/*.php') as $filename) {
	require_once($filename);
}
?>

<?php
function echoComputerGroups(CoreLogic $cl, $parent=null) {
	echo "<div class='subnode'>";
	foreach($cl->getComputerGroups($parent) as $group) {
		echo "<a ".explorerLink('views/computers.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		echoComputerGroups($cl, $group->id);
	}
	echo "</div>";
}
function echoPackageGroups(CoreLogic $cl, $parent=null) {
	echo "<div class='subnode'>";
	foreach($cl->getPackageGroups($parent) as $group) {
		echo "<a ".explorerLink('views/packages.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		echoPackageGroups($cl, $group->id);
	}
	echo "</div>";
}
function echoReportGroups(CoreLogic $cl, $parent=null) {
	echo "<div class='subnode'>";
	foreach($cl->getReportGroups($parent) as $group) {
		echo "<a ".explorerLink('views/reports.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		echoReportGroups($cl, $group->id);
	}
	echo "</div>";
}
