<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['remove_container_id'])) {
	$db->removeJobContainer($_POST['remove_container_id']);
	die();
}

if(!empty($_GET['id'])) {

	$container = $db->getJobContainer($_GET['id']);
	if($container === null) die(LANG['not_found']);
	$jobs = $db->getAllJobByContainer($_GET['id']);

	$percent = 0;
	if(count($jobs) > 0) {
		$done = 0;
		foreach($jobs as $job) {
			if($job->state == 2) $done ++;
		}
		$percent = $done/count($jobs)*100;
	}

	$icon = $db->getJobContainerIcon($container->id);
	echo "<h1><img src='img/".$icon.".dyn.svg'>".htmlspecialchars($container->name)."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='confirmRemoveJobContainer(".htmlspecialchars($container->id).")'><img src='img/delete.svg'>&nbsp;".LANG['delete_container']."</button>";
	echo "</div>";

	echo "<p>";
	echo "<table class='list'>";
	echo "<tr><th>".LANG['start']."</th><td>".htmlspecialchars($container->start_time)."</td></tr>";
	echo "<tr><th>".LANG['end']."</th><td>".htmlspecialchars($container->end_time ?? "-")."</td></tr>";
	echo "<tr><th>".LANG['description']."</th><td>".htmlspecialchars($container->notes)."</td></tr>";
	echo "<tr><th>".LANG['progress']."</th><td>".progressBar($percent)."</td></tr>";
	echo "</table>";
	echo "</p>";

	echo "<table id='tblJobData' class='list sortable savesort'>";
	echo "<thead>";
	echo "<tr><th>".LANG['computer']."</th><th>".LANG['package']."</th><th>".LANG['procedure']."</th><th>".LANG['order']."</th><th>".LANG['status']."</th><th>".LANG['last_change']."</th></tr>";
	echo "</thead>";
	echo "<tbody>";
	foreach($jobs as $job) {
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(".$job->computer_id.")'>".htmlspecialchars($job->computer_hostname)."</a></td>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentPackageDetail(".$job->package_id.")'>".htmlspecialchars($job->package_name)."</a></td>";
		echo "<td>".htmlspecialchars($job->package_procedure)."</td>";
		echo "<td>".htmlspecialchars($job->sequence)."</td>";
		if(!empty($job->message)) {
			echo "<td class='middle'><img src='img/".$job->getIcon().".dyn.svg'><a href='#' onclick='event.preventDefault();alert(this.getAttribute(\"message\"))' message='".addslashes(trim($job->message))."'>".getJobStateString($job->state)."</a></td>";
		} else {
			echo "<td class='middle'><img src='img/".$job->getIcon().".dyn.svg'>".getJobStateString($job->state)."</td>";
		}
		echo "<td>".htmlspecialchars($job->last_update);
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";

} else {

	echo "<h1>".LANG['job_container']."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='refreshContentDeploy()'><img src='img/add.svg'>&nbsp;".LANG['new_deployment_job']."</button>";
	echo "</div>";

	echo "<table id='tblJobcontainerData' class='list sortable savesort'>";
	echo "<thead>";
	echo "<tr><th></th><th>".LANG['name']."</th><th>".LANG['start']."</th><th>".LANG['end']."</th><th>".LANG['created']."</th><th>".LANG['progress']."</th></tr>";
	echo "</thead>";
	echo "<tbody>";
	foreach($db->getAllJobContainer() as $jc) {
		$percent = 0;
		$jobs = $db->getAllJobByContainer($jc->id);
		if(count($jobs) > 0) {
			$done = 0;
			foreach($jobs as $job) {
				if($job->state == 2) $done ++;
			}
			$percent = $done/count($jobs)*100;
		}
		echo "<tr>";
		echo "<td class='middle'><img src='img/".$db->getJobContainerIcon($jc->id).".dyn.svg'></td>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentJobContainer(".$jc->id.")'>".htmlspecialchars($jc->name)."</a></td>";
		echo "<td>".htmlspecialchars($jc->start_time)."</td>";
		echo "<td>".htmlspecialchars($jc->end_time ?? "-")."</td>";
		echo "<td>".htmlspecialchars($jc->created)."</td>";
		echo "<td sort_key='".$percent."'>".progressBar($percent)."</td>";
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";

}

function getJobStateString($state) {
	if($state == Job::STATUS_WAITING_FOR_CLIENT)
		return LANG['waiting_for_client'];
	elseif($state == Job::STATUS_FAILED)
		return LANG['failed'];
	elseif($state == Job::STATUS_EXPIRED)
		return LANG['expired'];
	elseif($state == Job::STATUS_EXECUTION_STARTED)
		return LANG['execution_started'];
	elseif($state == Job::STATUS_SUCCEEDED)
		return LANG['succeeded'];
	else return $state;
}
?>
