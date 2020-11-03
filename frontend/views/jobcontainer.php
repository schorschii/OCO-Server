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
	$jobs = $db->getAllJobByContainer($_GET['id']);
	if($container === null) die('not found');

	$icon = $db->getJobContainerIcon($container->id);
	echo "<h1><img src='img/$icon.dyn.svg'>".htmlspecialchars($container->name)."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='confirmRemoveJobContainer(".htmlspecialchars($container->id).")'><img src='img/delete.svg'>&nbsp;".LANG['delete_container']."</button>";
	echo "</div>";

	echo "<p>";
	echo "<table class='list'>";
	echo "<tr><th>".LANG['start']."</th><td>".htmlspecialchars($container->start_time)."</td></tr>";
	echo "<tr><th>".LANG['end']."</th><td>".htmlspecialchars($container->end_time ?? "-")."</td></tr>";
	echo "<tr><th>".LANG['description']."</th><td>".htmlspecialchars($container->notes)."</td></tr>";
	echo "</table>";
	echo "</p>";

	echo "<table class='list'>";
	echo "<tr><th>".LANG['computer']."</th><th>".LANG['package']."</th><th>".LANG['procedure']."</th><th>".LANG['order']."</th><th>".LANG['status']."</th><th>".LANG['last_change']."</th></tr>";
	foreach($jobs as $job) {
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(".$job->computer_id.")'>".htmlspecialchars($job->computer)."</a></td>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentPackageDetail(".$job->package_id.")'>".htmlspecialchars($job->package)."</a></td>";
		echo "<td>".htmlspecialchars($job->package_procedure)."</td>";
		echo "<td>".htmlspecialchars($job->sequence)."</td>";
		if(!empty($job->message)) {
			echo "<td><a href='#' onclick='event.preventDefault();alert(this.getAttribute(\"message\"))' message='".addslashes(trim($job->message))."'>".getJobStateString($job->state)."</a></td>";
		} else {
			echo "<td>".getJobStateString($job->state)."</td>";
		}
		echo "<td>".htmlspecialchars($job->last_update);
		echo "</tr>";
	}
	echo "</table>";

} else {

	echo "<h1>".LANG['jobs']."</h1>";

	echo "<div class='controls'>";
	echo "<button onclick='refreshContentDeploy()'><img src='img/add.svg'>&nbsp;".LANG['new_deployment_job']."</button>";
	echo "</div>";

	echo "<table class='list'>";
	echo "<tr><th></th><th>".LANG['name']."</th><th>".LANG['start']."</th><th>".LANG['end']."</th><th>".LANG['created']."</th></tr>";
	foreach($db->getAllJobContainer() as $jc) {
		echo "<tr>";
		echo "<td><img src='img/".$db->getJobContainerIcon($jc->id).".dyn.svg'></td>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentJobContainer(".$jc->id.")'>".htmlspecialchars($jc->name)."</a></td>";
		echo "<td>".htmlspecialchars($jc->start_time)."</td>";
		echo "<td>".htmlspecialchars($jc->end_time ?? "-")."</td>";
		echo "<td>".htmlspecialchars($jc->created);
		echo "</tr>";
	}
	echo "</table>";

}

function getJobStateString($state) {
	if($state == 0) return LANG['waiting_for_client'];
	elseif($state == -1) return LANG['failed'];
	elseif($state == 1) return LANG['execution_started'];
	elseif($state == 2) return LANG['succeeded'];
	else return $state;
}
?>
