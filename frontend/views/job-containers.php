<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_POST['remove_job_id']) && is_array($_POST['remove_job_id'])) {
	foreach($_POST['remove_job_id'] as $id) {
		try {
			$cl->removeJob($id);
		} catch(Exception $e) {
			header('HTTP/1.1 400 Invalid Request');
			die($e->getMessage());
		}
	}
	die();
}
if(!empty($_POST['edit_container_id']) && !empty($_POST['new_name'])) {
	$db->renameJobContainer($_POST['edit_container_id'], $_POST['new_name']);
	die();
}
if(!empty($_POST['edit_container_id']) && !empty($_POST['new_start'])) {
	if(DateTime::createFromFormat('Y-m-d H:i:s', $_POST['new_start']) === false) {
		header('HTTP/1.1 400 Invalid Date');
		die(LANG['date_parse_error']);
	}
	$db->editJobContainerStart($_POST['edit_container_id'], $_POST['new_start']);
	die();
}
if(!empty($_POST['edit_container_id']) && isset($_POST['new_end'])) {
	if(!empty($_POST['new_end']) && DateTime::createFromFormat('Y-m-d H:i:s', $_POST['new_end']) === false) {
		header('HTTP/1.1 400 Invalid Date');
		die(LANG['date_parse_error']);
	}
	$container = $db->getJobContainer($_POST['edit_container_id']);
	if($container == null) {
		header('HTTP/1.1 404 Not Found');
		die(LANG['not_found']);
	}
	if(empty($_POST['new_end'])) {
		$db->editJobContainerEnd($_POST['edit_container_id'], null);
		die();
	} else {
		if(strtotime($container->start_time) > strtotime($_POST['new_end'])) {
			header('HTTP/1.1 400 Invalid Request');
			die(LANG['end_time_before_start_time']);
		}
		$db->editJobContainerEnd($_POST['edit_container_id'], $_POST['new_end']);
		die();
	}
}
if(!empty($_POST['edit_container_id']) && isset($_POST['new_sequence_mode'])) {
	if(is_numeric($_POST['new_sequence_mode']) && in_array($_POST['new_sequence_mode'], [JobContainer::SEQUENCE_MODE_IGNORE_FAILED, JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED])) {
		$db->editJobContainerSequenceMode($_POST['edit_container_id'], $_POST['new_sequence_mode']);
	} else {
		header('HTTP/1.1 400 Invalid Request');
	}
	die();
}
if(!empty($_POST['edit_container_id']) && isset($_POST['new_priority'])) {
	if(is_numeric($_POST['new_priority']) && intval($_POST['new_priority']) > -100 && intval($_POST['new_priority']) < 100) {
		$db->editJobContainerPriority($_POST['edit_container_id'], $_POST['new_priority']);
	} else {
		header('HTTP/1.1 400 Invalid Request');
	}
	die();
}
if(!empty($_POST['edit_container_id']) && isset($_POST['new_notes'])) {
	$db->editJobContainerNotes($_POST['edit_container_id'], $_POST['new_notes']);
	die();
}
if(!empty($_POST['remove_container_id']) && is_array($_POST['remove_container_id'])) {
	foreach($_POST['remove_container_id'] as $id) {
		try {
			$cl->removeJobContainer($id);
		} catch(Exception $e) {
			header('HTTP/1.1 400 Invalid Request');
			die($e->getMessage());
		}
	}
	die();
}
if(!empty($_POST['renew_container_id']) && !empty($_POST['renew_start_time'])) {
	if(DateTime::createFromFormat('Y-m-d H:i:s', $_POST['renew_start_time']) === false) {
		header('HTTP/1.1 400 Invalid Date');
		die(LANG['date_parse_error']);
	}
	$container = $db->getJobContainer($_POST['renew_container_id']);
	if($container === null) {
		header('HTTP/1.1 404 Not Found');
		die(LANG['not_found']);
	}
	if($jcid = $db->addJobContainer(
		$container->name.' - '.LANG['renew'], $_SESSION['um_username'],
		$_POST['renew_start_time'], null /*end time*/,
		'' /*description*/, 0 /*wol sent*/, 0 /*shutdown waked after completion*/, $container->sequence_mode, $container->priority
	)) {
		$count = 0;
		foreach($db->getAllJobByContainer($container->id) as $job) {
			if($job->state == Job::STATUS_FAILED || $job->state == Job::STATUS_EXPIRED || $job->state == Job::STATUS_OS_INCOMPATIBLE || $job->state == Job::STATUS_PACKAGE_CONFLICT) {
				if($db->addJob($jcid, $job->computer_id,
					$job->package_id, $job->package_procedure, $job->success_return_codes,
					$job->is_uninstall, $job->download,
					$job->post_action,
					$job->post_action_timeout,
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
	if($container === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
	$jobs = $db->getAllJobByContainer($container->id);

	$done = 0;
	$failed = 0;
	$percent = 0;
	if(count($jobs) > 0) {
		foreach($jobs as $job) {
			if($job->state == Job::STATUS_SUCCEEDED) $done ++;
			if($job->state == Job::STATUS_FAILED || $job->state == Job::STATUS_EXPIRED || $job->state == Job::STATUS_OS_INCOMPATIBLE || $job->state == Job::STATUS_PACKAGE_CONFLICT) $failed ++;
		}
		$percent = $done/count($jobs)*100;
	}

	$icon = $db->getJobContainerIcon($container->id);
?>

	<h1><img src='img/<?php echo $icon; ?>.dyn.svg'><span id='page-title'><span id='spnJobContainerName'><?php echo htmlspecialchars($container->name); ?></span></span></h1>

	<div class='controls'>
		<button onclick='renameJobContainer(<?php echo $container->id; ?>, spnJobContainerName.innerText)'><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename']; ?></button>
		<button onclick='confirmRenewFailedJobsInContainer(<?php echo $container->id; ?>, "<?php echo date('Y-m-d H:i:s'); ?>")' <?php echo ($failed>0 ? '' : 'disabled'); ?>><img src='img/refresh.svg'>&nbsp;<?php echo LANG['renew_failed_jobs']; ?></button>
		<button onclick='confirmRemoveJobContainer([<?php echo $container->id; ?>], spnJobContainerName.innerText)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
	</div>

	<div class='details-abreast margintop marginbottom'>
	<div>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG['id']; ?></th>
				<td><?php echo htmlspecialchars($container->id); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['created']; ?></th>
				<td><?php echo htmlspecialchars($container->created); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['start']; ?></th>
				<td class='subbuttons'>
					<span id='spnJobContainerStartTime'><?php echo htmlspecialchars($container->start_time); ?></span>
					<?php if($container->wol_sent >= 0) echo ' ('.LANG['wol'].')'; if($container->shutdown_waked_after_completion > 0) echo ' ('.LANG['shutdown_waked_computers'].')'; ?>
					<button onclick='event.stopPropagation();editJobContainerStart(<?php echo $container->id; ?>, spnJobContainerStartTime.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['end']; ?></th>
				<td class='subbuttons'>
					<?php echo htmlspecialchars($container->end_time ?? "-"); ?>
					<span id='spnJobContainerEndTime' class='rawvalue'><?php echo htmlspecialchars($container->end_time ?? ""); ?></span>
					<button onclick='event.stopPropagation();editJobContainerEnd(<?php echo $container->id; ?>, spnJobContainerEndTime.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['author']; ?></th>
				<td><?php echo htmlspecialchars($container->author); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['sequence_mode']; ?></th>
				<td class='subbuttons'>
					<span id='spnJobContainerSequenceMode' class='rawvalue'><?php echo htmlspecialchars($container->sequence_mode); ?></span>
					<?php switch($container->sequence_mode) {
						case(JobContainer::SEQUENCE_MODE_IGNORE_FAILED): echo LANG['ignore_failed']; break;
						case(JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED): echo LANG['abort_after_failed']; break;
						default: echo htmlspecialchars($container->sequence_mode);
					} ?>
					<button onclick='event.stopPropagation();editJobContainerSequenceMode(<?php echo $container->id; ?>, spnJobContainerSequenceMode.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['priority']; ?></th>
				<td class='subbuttons'>
					<span id='spnJobContainerPriority'><?php echo htmlspecialchars($container->priority); ?></span>
					<button onclick='event.stopPropagation();editJobContainerPriority(<?php echo $container->id; ?>, spnJobContainerPriority.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['description']; ?></th>
				<td class='subbuttons'>
					<span id='spnJobContainerDescription'><?php echo htmlspecialchars($container->notes); ?></span>
					<button onclick='event.stopPropagation();editJobContainerNotes(<?php echo $container->id; ?>, spnJobContainerDescription.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['progress']; ?></th>
				<td title='<?php echo htmlspecialchars($done.' / '.count($jobs)); ?>'><?php echo progressBar($percent, null, null, null, null, true); ?></td>
			</tr>
		</table>
	</div>
	<div></div>
	</div>

	<div class='details-abreast'>
	<div>
		<table id='tblJobData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblJobData, this.checked)'></th>
					<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
					<th class='searchable sortable'><?php echo LANG['package']; ?></th>
					<th class='searchable sortable'><?php echo LANG['procedure']; ?></th>
					<th class='searchable sortable'><?php echo LANG['order']; ?></th>
					<th class='searchable sortable'><?php echo LANG['status']; ?></th>
					<th class='searchable sortable'><?php echo LANG['last_change']; ?></th>
				</tr>
			</thead>
			<tbody>
			<?php $counter = 0;
			foreach($jobs as $job) {
				$counter ++;
				echo "<tr>";
				echo "<td><input type='checkbox' name='job_id[]' value='".$job->id."' onchange='refreshCheckedCounter(tblJobData)'></td>";
				echo "<td><a ".explorerLink('views/computer-details.php?id='.$job->computer_id).">".htmlspecialchars($job->computer_hostname)."</a></td>";
				echo "<td><a ".explorerLink('views/package-details.php?id='.$job->package_id).">".htmlspecialchars($job->package_family_name)." (".htmlspecialchars($job->package_version).")</a></td>";
				echo "<td class='middle' title='".htmlspecialchars($job->package_procedure, ENT_QUOTES)."'>";
				if($job->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG['install']."'>&nbsp;";
				else echo "<img src='img/delete.dyn.svg' title='".LANG['uninstall']."'>&nbsp;";
				echo htmlspecialchars(shorter($job->package_procedure));
				if($job->post_action == Package::POST_ACTION_RESTART) echo ' ('.LANG['restart_after'].' '.intval($job->post_action_timeout).' '.LANG['minutes'].')';
				if($job->post_action == Package::POST_ACTION_SHUTDOWN) echo ' ('.LANG['shutdown_after'].' '.intval($job->post_action_timeout).' '.LANG['minutes'].')';
				if($job->post_action == Package::POST_ACTION_EXIT) echo ' ('.LANG['restart_agent'].')';
				echo "</td>";
				echo "<td>".htmlspecialchars($job->sequence)."</td>";
				if(!empty($job->message)) {
					echo "<td class='middle'>";
					echo "<img src='".$job->getIcon()."'>&nbsp;";
					echo "<a href='#' onclick='event.preventDefault();showDialog(\"".$job->getStateString()."\",this.getAttribute(\"message\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' message='".htmlspecialchars(str_replace(chr(0x00),'',trim($job->message)),ENT_QUOTES)."'>".$job->getStateString()."</a>";
					echo "</td>";
				} else {
					echo "<td class='middle'><img src='".$job->getIcon()."'>&nbsp;".$job->getStateString()."</td>";
				}
				echo "<td>".htmlspecialchars($job->last_update);
				echo "</tr>";
			} ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
						<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
						<a href='#' onclick='event.preventDefault();downloadTableCsv("tblJobData")'><?php echo LANG['csv']; ?></a>
					</td>
				</tr>
			</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='removeSelectedJob("job_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
	</div>

<?php } else { ?>

	<h1><img src='img/job.dyn.svg'><span id='page-title'><?php echo LANG['job_container']; ?></span></h1>

	<div class='controls'>
		<button onclick='refreshContentDeploy()'><img src='img/add.svg'>&nbsp;<?php echo LANG['new_deployment_job']; ?></button>
	</div>

	<div class='details-abreast'>
	<div>
		<table id='tblJobcontainerData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblJobcontainerData, this.checked)'></th>
					<th class='searchable sortable'><?php echo LANG['name']; ?></th>
					<th class='searchable sortable'><?php echo LANG['author']; ?></th>
					<th class='searchable sortable'><?php echo LANG['created']; ?></th>
					<th class='searchable sortable'><?php echo LANG['start']; ?></th>
					<th class='searchable sortable'><?php echo LANG['end']; ?></th>
					<th class='searchable sortable'><?php echo LANG['priority']; ?></th>
					<th class='searchable sortable'><?php echo LANG['description']; ?></th>
					<th class='searchable sortable'><?php echo LANG['progress']; ?></th>
				</tr>
			</thead>
			<tbody>
			<?php $counter = 0;
			foreach($db->getAllJobContainer() as $jc) {
				$counter ++;
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
				echo "<td><input type='checkbox' name='job_container_id[]' value='".$jc->id."' onchange='refreshCheckedCounter(tblJobcontainerData)'></td>";
				echo "<td class='middle'>";
				echo  "<img src='img/".$db->getJobContainerIcon($jc->id).".dyn.svg'>&nbsp;";
				echo  "<a ".explorerLink('views/job-containers.php?id='.$jc->id).">".htmlspecialchars($jc->name)."</a>";
				echo "</td>";
				echo "<td>".htmlspecialchars($jc->author)."</td>";
				echo "<td>".htmlspecialchars($jc->created)."</td>";
				echo "<td>".htmlspecialchars($jc->start_time)."</td>";
				echo "<td>".htmlspecialchars($jc->end_time ?? "-")."</td>";
				echo "<td>".htmlspecialchars($jc->priority)."</td>";
				echo "<td>".htmlspecialchars(shorter($jc->notes))."</td>";
				echo "<td sort_key='".$percent."' title='".htmlspecialchars($done.' / '.count($jobs))."'>".progressBar($percent, null, null, null, null, true)."</td>";
				echo "</tr>";
			} ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG['elements']; ?>,
						<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
						<a href='#' onclick='event.preventDefault();downloadTableCsv("tblJobcontainerData")'><?php echo LANG['csv']; ?></a>
					</td>
				</tr>
			</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='removeSelectedJobContainer("job_container_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
	</div>

<?php } ?>
