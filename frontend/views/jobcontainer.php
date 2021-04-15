<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['remove_container_id'])) {
	$db->removeJobContainer($_POST['remove_container_id']);
	die();
}
if(!empty($_POST['renew_container_id'])) {
	$container = $db->getJobContainer($_POST['renew_container_id']);
	if($container === null) {
		header('HTTP/1.1 404 Not Found');
		die(LANG['not_found']);
	}
	if($jcid = $db->addJobContainer(
		$container->name.' - '.LANG['renew'], $_SESSION['um_username'],
		date('Y-m-d H:i:s'), null,
		'' /*description*/, 0 /*wol sent*/
	)) {
		$count = 0;
		foreach($db->getAllJobByContainer($container->id) as $job) {
			if($job->state == Job::STATUS_FAILED || $job->state == Job::STATUS_EXPIRED) {
				if($db->addJob($jcid, $job->computer_id,
					$job->package_id, $job->package_procedure, $job->success_return_codes,
					$job->is_uninstall, $job->download,
					$job->restart,
					$job->shutdown,
					$job->sequence
				)) {
					if($db->removeJob($job->id)) {
						$count ++;
					}
				}
			}
		}
	}
	die();
}

if(!empty($_GET['id'])) {

	$container = $db->getJobContainer($_GET['id']);
	if($container === null) die(LANG['not_found']);
	$jobs = $db->getAllJobByContainer($container->id);

	$done = 0;
	$failed = 0;
	$percent = 0;
	if(count($jobs) > 0) {
		foreach($jobs as $job) {
			if($job->state == Job::STATUS_SUCCEEDED) $done ++;
			if($job->state == Job::STATUS_FAILED || $job->state == Job::STATUS_EXPIRED) $failed ++;
		}
		$percent = $done/count($jobs)*100;
	}

	$icon = $db->getJobContainerIcon($container->id);
	echo "<h1><img src='img/".$icon.".dyn.svg'>".htmlspecialchars($container->name)."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='confirmRemoveJobContainer(".htmlspecialchars($container->id).")'><img src='img/delete.svg'>&nbsp;".LANG['delete_container']."</button>";
	echo "<button onclick='confirmRenewFailedJobsInContainer(".htmlspecialchars($container->id).")' ".($failed>0 ? '' : 'disabled')."><img src='img/refresh.svg'>&nbsp;".LANG['renew_failed_jobs']."</button>";
	echo "</div>";

	echo "<div class='details-abreast margintop marginbottom'>";
	echo "<div>";
	echo "<table class='list'>";
	echo "<tr><th>".LANG['created']."</th><td>".htmlspecialchars($container->created)."</td></tr>";
	echo "<tr><th>".LANG['start']."</th><td>".htmlspecialchars($container->start_time)."</td></tr>";
	echo "<tr><th>".LANG['end']."</th><td>".htmlspecialchars($container->end_time ?? "-")."</td></tr>";
	echo "<tr><th>".LANG['author']."</th><td>".htmlspecialchars($container->author)."</td></tr>";
	echo "<tr><th>".LANG['description']."</th><td>".htmlspecialchars($container->notes)."</td></tr>";
	echo "<tr><th>".LANG['progress']."</th><td title='".htmlspecialchars($done.' / '.count($jobs))."'>".progressBar($percent, null, null, null, null, true)."</td></tr>";
	echo "</table>";
	echo "</div>";
	echo "<div></div>";
	echo "</div>";

	echo "<div class='details-abreast'>";
	echo "<div>";
	echo "<table id='tblJobData' class='list sortable savesort'>";
	echo "<thead>";
	echo "<tr><th>".LANG['computer']."</th><th>".LANG['package']."</th><th>".LANG['procedure']."</th><th>".LANG['order']."</th><th>".LANG['status']."</th><th>".LANG['last_change']."</th></tr>";
	echo "</thead>";
	echo "<tbody>";
	foreach($jobs as $job) {
		echo "<tr>";
		echo "<td><a href='".explorerLink('views/computer_detail.php?id='.$job->computer_id)."' onclick='event.preventDefault();refreshContentComputerDetail(".$job->computer_id.")'>".htmlspecialchars($job->computer_hostname)."</a></td>";
		echo "<td><a href='".explorerLink('views/package_detail.php?id='.$job->package_id)."' onclick='event.preventDefault();refreshContentPackageDetail(".$job->package_id.")'>".htmlspecialchars($job->package_name)." (".htmlspecialchars($job->package_version).")</a></td>";
		echo "<td>";
		echo htmlspecialchars(shorter($job->package_procedure));
		if($job->restart > 0) echo ' ('.LANG['restart_after'].' '.intval($job->restart).' '.LANG['minutes'].')';
		if($job->shutdown > 0) echo ' ('.LANG['shutdown_after'].' '.intval($job->shutdown).' '.LANG['minutes'].')';
		echo "</td>";
		echo "<td>".htmlspecialchars($job->sequence)."</td>";
		if(!empty($job->message)) {
			echo "<td class='middle'>";
			echo "<img src='img/".$job->getIcon().".dyn.svg'>";
			echo "<a href='#' onclick='event.preventDefault();showErrorDialog(true,\"".getJobStateString($job->state, $job->return_code)."\",this.getAttribute(\"message\"),false)' message='".htmlspecialchars(trim($job->message),ENT_QUOTES)."'>".getJobStateString($job->state, $job->return_code)."</a>";
			echo "</td>";
		} else {
			echo "<td class='middle'><img src='img/".$job->getIcon().".dyn.svg'>".getJobStateString($job->state, $job->return_code)."</td>";
		}
		echo "<td>".htmlspecialchars($job->last_update);
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
	echo "</div>";
	echo "</div>";

} else {

	echo "<h1>".LANG['job_container']."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='refreshContentDeploy()'><img src='img/add.svg'>&nbsp;".LANG['new_deployment_job']."</button>";
	echo "</div>";

	echo "<div class='details-abreast'>";
	echo "<div>";
	echo "<table id='tblJobcontainerData' class='list sortable savesort'>";
	echo "<thead>";
	echo "<tr><th></th><th>".LANG['name']."</th><th>".LANG['author']."</th><th>".LANG['start']."</th><th>".LANG['end']."</th><th>".LANG['created']."</th><th>".LANG['progress']."</th></tr>";
	echo "</thead>";
	echo "<tbody>";
	foreach($db->getAllJobContainer() as $jc) {
		$percent = 0;
		$done = 0;
		$jobs = $db->getAllJobByContainer($jc->id);
		if(count($jobs) > 0) {
			foreach($jobs as $job) {
				if($job->state == Job::STATUS_SUCCEEDED) $done ++;
			}
			$percent = $done/count($jobs)*100;
		}
		echo "<tr>";
		echo "<td class='middle'><img src='img/".$db->getJobContainerIcon($jc->id).".dyn.svg'></td>";
		echo "<td><a href='".explorerLink('views/jobcontainer.php?id='.$jc->id)."' onclick='event.preventDefault();refreshContentJobContainer(".$jc->id.")'>".htmlspecialchars($jc->name)."</a></td>";
		echo "<td>".htmlspecialchars($jc->author)."</td>";
		echo "<td>".htmlspecialchars($jc->start_time)."</td>";
		echo "<td>".htmlspecialchars($jc->end_time ?? "-")."</td>";
		echo "<td>".htmlspecialchars($jc->created)."</td>";
		echo "<td sort_key='".$percent."' title='".htmlspecialchars($done.' / '.count($jobs))."'>".progressBar($percent, null, null, null, null, true)."</td>";
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
	echo "</div>";
	echo "</div>";

}

function getJobStateString($state, $returnCode) {
	$returnCodeString = '';
	if($returnCode != null) {
		$returnCodeString = ' ('.htmlspecialchars($returnCode).')';
	}
	if($state == Job::STATUS_WAITING_FOR_CLIENT)
		return LANG['waiting_for_client'];
	elseif($state == Job::STATUS_FAILED)
		return LANG['failed'].$returnCodeString;
	elseif($state == Job::STATUS_EXPIRED)
		return LANG['expired'];
	elseif($state == Job::STATUS_DOWNLOAD_STARTED)
		return LANG['download_started'];
	elseif($state == Job::STATUS_EXECUTION_STARTED)
		return LANG['execution_started'];
	elseif($state == Job::STATUS_SUCCEEDED)
		return LANG['succeeded'].$returnCodeString;
	else return $state;
}
?>
