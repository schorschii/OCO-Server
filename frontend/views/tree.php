<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<div id='divNodeDomainUsers' class='node'>
	<a <?php echo explorerLink('views/domain-users.php'); ?>><img src='img/users.dyn.svg'><?php echo LANG('domain_users'); ?></a>
</div>

<?php $computerGroupsHtml = getComputerGroupsHtml($cl); ?>
<div class='node <?php if($computerGroupsHtml) echo 'expandable'; ?>'>
	<a <?php echo explorerLink('views/computers.php'); ?>><img src='img/computer.dyn.svg'><?php echo LANG('computer'); ?></a>
	<div id='divNodeComputers' class='subitems'>
		<?php echo $computerGroupsHtml; ?>
	</div>
</div>

<?php if($cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SOFTWARE_VIEW, false)) { ?>
<div class='node expandable'>
	<a <?php echo explorerLink('views/software.php'); ?>><img src='img/software.dyn.svg'><?php echo LANG('recognised_software'); ?></a>
	<div id='divSubnodeSoftware' class='subitems'>
		<a <?php echo explorerLink('views/software.php?os=other'); ?>><img src='img/linux.dyn.svg'><?php echo LANG('linux'); ?></a>
		<a <?php echo explorerLink('views/software.php?os=macos'); ?>><img src='img/apple.dyn.svg'><?php echo LANG('macos'); ?></a>
		<a <?php echo explorerLink('views/software.php?os=windows'); ?>><img src='img/windows.dyn.svg'><?php echo LANG('windows'); ?></a>
	</div>
</div>
<?php } ?>

<?php $packageGroupsHtml = getPackageGroupsHtml($cl); ?>
<div class='node <?php if($packageGroupsHtml) echo 'expandable'; ?>'>
	<a <?php echo explorerLink('views/package-families.php'); ?>><img src='img/package.dyn.svg'><?php echo LANG('packages'); ?></a>
	<div id='divNodePackages' class='subitems'>
		<?php echo $packageGroupsHtml; ?>
	</div>
</div>

<div id='divNodeJobs' class='node expandable'>
	<a <?php echo explorerLink('views/jobs.php'); ?>><img src='img/job.dyn.svg'><?php echo LANG('jobs'); ?></a>
	<div id='divSubnodeJobs' class='subitems'>
		<?php
		$jobContainers = $cl->getJobContainers(true);
		echo "<div class='subnode ".(empty($jobContainers) ? '' : 'expandable')."'>";
		echo "<a ".explorerLink('views/job-containers.php?selfservice=1')."><img src='img/self-service.dyn.svg'>".LANG('self_service_job_containers')."</a>";
		echo "<div id='divNodeSelfServiceJobs' class='subitems'>";
		foreach($jobContainers as $container) {
			echo "<a ".explorerLink('views/job-containers.php?id='.$container->id)."><img src='img/".$container->getStatus($db->selectAllStaticJobByJobContainer($container->id)).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		echo "</div>";
		echo "</div>";
		?>
		<?php
		$jobContainers = $cl->getJobContainers(false);
		echo "<div class='subnode ".(empty($jobContainers) ? '' : 'expandable')."'>";
		echo "<a ".explorerLink('views/job-containers.php')."><img src='img/container.dyn.svg'>".LANG('system_users_job_containers')."</a>";
		echo "<div id='divNodeStaticJobs' class='subitems'>";
		foreach($jobContainers as $container) {
			echo "<a ".explorerLink('views/job-containers.php?id='.$container->id)."><img src='img/".$container->getStatus($db->selectAllStaticJobByJobContainer($container->id)).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		echo "</div>";
		echo "</div>";
		?>
		<?php
		$deploymentRules = $cl->getDeploymentRules();
		echo "<div class='subnode ".(empty($deploymentRules) ? '' : 'expandable')."'>";
		echo "<a ".explorerLink('views/deployment-rules.php')."><img src='img/rule.dyn.svg'>".LANG('deployment_rules')."</a>";
		echo "<div id='divNodeDynamicJobs' class='subitems'>";
		foreach($deploymentRules as $container) {
			echo "<a ".explorerLink('views/deployment-rules.php?id='.$container->id)."><img src='img/".$container->getStatus($db->selectAllDynamicJobByDeploymentRuleId($container->id)).".dyn.svg'>".htmlspecialchars($container->name)."</a>";
		}
		echo "</div>";
		echo "</div>";
		?>
	</div>
</div>

<?php $reportGroupsHtml = getReportGroupsHtml($cl); ?>
<div class='node <?php if($reportGroupsHtml) echo 'expandable'; ?>'>
	<a <?php echo explorerLink('views/reports.php'); ?>><img src='img/report.dyn.svg'><?php echo LANG('reports'); ?></a>
	<div id='divSubnodeReports' class='subitems'>
		<?php echo $reportGroupsHtml; ?>
	</div>
</div>

<?php
// include extensions
foreach($ext->getAggregatedConf('frontend-tree') as $treeExtension) {
	require($treeExtension);
}
?>

<?php
function getComputerGroupsHtml(CoreLogic $cl, $parentId=null) {
	$html = '';
	$subgroups = $cl->getComputerGroups($parentId);
	if(count($subgroups) == 0) return false;
	foreach($subgroups as $group) {
		$subHtml = getComputerGroupsHtml($cl, $group->id);
		$html .= "<div class='subnode ".(empty($subHtml) ? '' : 'expandable')."'>";
		$html .= "<a ".explorerLink('views/computers.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= "<div id='divNodeComputerGroup".($group->id)."' class='subitems'>";
		$html .= $subHtml;
		$html .= "</div>";
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
		$html .= "<div class='subnode ".(empty($subHtml) ? '' : 'expandable')."'>";
		$html .= "<a ".explorerLink('views/packages.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= "<div id='divNodePackageGroup".($group->id)."' class='subitems'>";
		$html .= $subHtml;
		$html .= "</div>";
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
		$html .= "<div class='subnode ".(empty($subHtml) ? '' : 'expandable')."'>";
		$html .= "<a ".explorerLink('views/reports.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= "<div id='divNodeReportGroup".($group->id)."' class='subitems'>";
		$html .= $subHtml;
		$html .= "</div>";
		$html .= "</div>";
	}
	return $html;
}
