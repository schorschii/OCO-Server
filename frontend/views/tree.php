<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<div id='divNodeDomainUsers' class='node'>
	<a <?php echo explorerLink('views/domain-users.php'); ?>><img src='img/users.dyn.svg'><?php echo LANG['users']; ?></a>
</div>

<div id='divNodeComputers' class='node expandable'>
	<a <?php echo explorerLink('views/computers.php'); ?>><img src='img/computer.dyn.svg'><?php echo LANG['computer']; ?></a>
	<?php echo getComputerGroupsHtml($cl); ?>
</div>

<?php if($currentSystemUser->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SOFTWARE_VIEW, false)) { ?>
<div id='divNodeSoftware' class='node expandable'>
	<a <?php echo explorerLink('views/software.php'); ?>><img src='img/software.dyn.svg'><?php echo LANG['recognised_software']; ?></a>
	<div id='divSubnodeSoftware' class='subnode'>
		<a <?php echo explorerLink('views/software.php?os=other'); ?>><img src='img/linux.dyn.svg'><?php echo LANG['linux']; ?></a>
		<a <?php echo explorerLink('views/software.php?os=macos'); ?>><img src='img/apple.dyn.svg'><?php echo LANG['macos']; ?></a>
		<a <?php echo explorerLink('views/software.php?os=windows'); ?>><img src='img/windows.dyn.svg'><?php echo LANG['windows']; ?></a>
	</div>
</div>
<?php } ?>

<div id='divNodePackages' class='node expandable'>
	<a <?php echo explorerLink('views/package-families.php'); ?>><img src='img/package.dyn.svg'><?php echo LANG['packages']; ?></a>
	<?php echo getPackageGroupsHtml($cl); ?>
</div>

<div id='divNodeJobs' class='node expandable'>
	<a <?php echo explorerLink('views/job-containers.php'); ?>><img src='img/job.dyn.svg'><?php echo LANG['jobs']; ?></a>
	<div id='divSubnodeJobs' class='subnode'>
		<?php
		foreach($cl->getJobContainers() as $container) {
			echo "<a ".explorerLink('views/job-containers.php?id='.$container->id)."><img src='img/".$db->getJobContainerIcon($container->id).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		?>
	</div>
</div>

<div id='divNodeReports' class='node expandable'>
	<a <?php echo explorerLink('views/reports.php'); ?>><img src='img/report.dyn.svg'><?php echo LANG['reports']; ?></a>
	<?php echo getReportGroupsHtml($cl); ?>
</div>

<?php
// include add-ons
foreach(glob(__DIR__.'/tree.d/*.php') as $filename) {
	require_once($filename);
}
?>

<?php
function getComputerGroupsHtml(CoreLogic $cl, $parentId=null) {
	$html = '';
	$subgroups = $cl->getComputerGroups($parentId);
	if(count($subgroups) == 0) return false;
	foreach($subgroups as $group) {
		$subHtml = getComputerGroupsHtml($cl, $group->id);
		$html .= "<div id='divNodeComputerGroup".($group->id)."' class='subnode ".($subHtml!==False ? 'expandable' : '')."'>";
		$html .= "<a ".explorerLink('views/computers.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= $subHtml;
		$html .= "</div>";
	}
	return $html;
}
function getPackageGroupsHtml(CoreLogic $cl, $parentId=null) {
	$html = '';
	$subgroups = $cl->getPackageGroups($parentId);
	if(count($subgroups) == 0) return false;
	foreach($subgroups as $group) {
		$subHtml = getPackageGroupsHtml($cl, $group->id);
		$html .= "<div id='divNodePackageGroup".($group->id)."' class='subnode ".($subHtml!==False ? 'expandable' : '')."'>";
		$html .= "<a ".explorerLink('views/packages.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= $subHtml;
		$html .= "</div>";
	}
	return $html;
}
function getReportGroupsHtml(CoreLogic $cl, $parentId=null) {
	$html = '';
	$subgroups = $cl->getReportGroups($parentId);
	if(count($subgroups) == 0) return false;
	foreach($subgroups as $group) {
		$subHtml = getReportGroupsHtml($cl, $group->id);
		$html .= "<div id='divNodeReportGroup".($group->id)."' class='subnode ".($subHtml!==False ? 'expandable' : '')."'>";
		$html .= "<a ".explorerLink('views/reports.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= $subHtml;
		$html .= "</div>";
	}
	return $html;
}
