<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

if(!empty($_GET['id'])) {

	try {
		$container = $cl->getJobContainer($_GET['id'] ?? -1);
		$permissionCreate = $cl->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE, false);
		$permissionWrite  = $cl->checkPermission($container, PermissionManager::METHOD_WRITE, false);
		$permissionDelete = $cl->checkPermission($container, PermissionManager::METHOD_DELETE, false);
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG('not_found')."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG('permission_denied')."</div>");
	} catch(InvalidRequestException $e) {
		die("<div class='alert error'>".$e->getMessage()."</div>");
	}

	$jobs = $db->selectAllStaticJobByJobContainer($container->id);
	$done = 0; $failed = 0; $percent = 0;
	if(count($jobs) > 0) {
		foreach($jobs as $job) {
			if($job->state == Models\Job::STATE_SUCCEEDED || $job->state == Models\Job::STATE_ALREADY_INSTALLED) $done ++;
			if($job->state == Models\Job::STATE_FAILED || $job->state == Models\Job::STATE_EXPIRED || $job->state == Models\Job::STATE_OS_INCOMPATIBLE || $job->state == Models\Job::STATE_PACKAGE_CONFLICT) $failed ++;
		}
		$percent = $done/count($jobs)*100;
	}

	$icon = $container->getStatus($jobs);
?>

	<div class='details-header'>
		<h1><img src='img/<?php echo $icon; ?>.dyn.svg' class='<?php echo($container->enabled ? 'online' : 'offline'); ?>'><span id='page-title'><span id='spnJobContainerName'><?php echo htmlspecialchars($container->name); ?></span></span></h1>
		<div class='controls'>
			<button onclick='showDialogEditJobContainer(<?php echo $container->id; ?>, spnJobContainerName.innerText, spnJobContainerEnabled.innerText, spnJobContainerStartTime.innerText, spnJobContainerEndTime.innerText, spnJobContainerSequenceMode.innerText, spnJobContainerPriority.innerText, spnJobContainerAgentIpRanges.innerText, spnJobContainerTimeFrames.innerText, spnJobContainerNotes.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
			<button onclick='confirmRemoveJobContainer([<?php echo $container->id; ?>], spnJobContainerName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
			<span class='filler'></span>
		</div>
	</div>

	<div class='details-abreast'>
	<div>
		<h2><?php echo LANG('general'); ?></h2>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG('id'); ?></th>
				<td><?php echo htmlspecialchars($container->id); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('enabled'); ?></th>
				<td>
					<?php if($container->enabled=='1') echo LANG('yes'); else echo LANG('no'); ?>
					<span id='spnJobContainerEnabled' class='rawvalue'><?php echo htmlspecialchars($container->enabled); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('created'); ?></th>
				<td>
					<?php echo htmlspecialchars($container->created); ?>
					<?php
					$author = $container->created_by_system_user_username ?? $container->created_by_domain_user_username ?? null;
					if($author) echo htmlspecialchars('('.$author.')');
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('start'); ?></th>
				<td>
					<span id='spnJobContainerStartTime'><?php echo htmlspecialchars($container->start_time); ?></span>
					<?php if($container->wol_sent >= 0) echo ' ('.LANG('wol').')'; if($container->shutdown_waked_after_completion > 0) echo ' ('.LANG('shutdown_waked_computers').')'; ?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('end'); ?></th>
				<td>
					<?php echo htmlspecialchars($container->end_time ?? "-"); ?>
					<span id='spnJobContainerEndTime' class='rawvalue'><?php echo htmlspecialchars($container->end_time ?? ""); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('sequence_mode'); ?></th>
				<td>
					<span id='spnJobContainerSequenceMode' class='rawvalue'><?php echo htmlspecialchars($container->sequence_mode); ?></span>
					<?php switch($container->sequence_mode) {
						case(Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED): echo LANG('ignore_failed'); break;
						case(Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED): echo LANG('abort_after_failed'); break;
						default: echo htmlspecialchars($container->sequence_mode);
					} ?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('priority'); ?></th>
				<td>
					<span id='spnJobContainerPriority'><?php echo htmlspecialchars($container->priority); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('agent_ip_range'); ?></th>
				<td>
					<span id='spnJobContainerAgentIpRanges'><?php echo htmlspecialchars($container->agent_ip_ranges??''); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('time_frame'); ?></th>
				<td>
					<span id='spnJobContainerTimeFrames'><?php echo htmlspecialchars($container->time_frames??''); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('description'); ?></th>
				<td>
					<span id='spnJobContainerNotes'><?php echo nl2br(htmlspecialchars($container->notes)); ?></span>
				</td>
			</tr>
		</table>
	</div>
	<div>
		<h2><?php echo LANG('state'); ?></h2>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG('progress'); ?></th>
				<td title='<?php echo htmlspecialchars($done.' / '.count($jobs)); ?>'><?php echo progressBar($percent, null, null, 'stretch', ''); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('total_runtime'); ?></th>
				<td><?php
				$realStartTime = strtotime($container->start_time);
				if(strtotime($container->start_time) < strtotime($container->created)) {
					$realStartTime = strtotime($container->created);
				}
				if($realStartTime > time()) {
					echo htmlspecialchars('-');
				} else {
					if($icon == Models\JobContainer::STATUS_SUCCEEDED || $icon == Models\JobContainer::STATUS_FAILED) {
						$maxTimeJob = $db->selectMaxExecutionStaticJobByJobContainerId($container->id);
						$maxTime = time();
						if(!empty($maxTimeJob) && !empty($maxTimeJob->execution_finished)) {
							$maxTime = strtotime($maxTimeJob->execution_finished);
						}
						$timeDiff = $maxTime - $realStartTime;
						if($timeDiff < 0) {
							echo htmlspecialchars('-');
						} else {
							echo htmlspecialchars(niceTime($timeDiff));
						}
					} else {
						$timeDiff = time() - $realStartTime;
						echo htmlspecialchars('~ '.niceTime($timeDiff));
					}
				}
				?></td>
			</tr>
			<tr>
				<th><?php echo LANG('effective_runtime'); ?></th>
				<td><?php
				$minTimeJob = $db->selectMinExecutionStaticJobByJobContainerId($container->id);
				$maxTimeJob = $db->selectMaxExecutionStaticJobByJobContainerId($container->id);
				if(empty($minTimeJob) || empty($maxTimeJob) || empty($minTimeJob->execution_started) || empty($maxTimeJob->execution_finished)) {
					echo htmlspecialchars('-');
				} else {
					$flag = '~ ';
					if($icon == Models\JobContainer::STATUS_SUCCEEDED || $icon == Models\JobContainer::STATUS_FAILED) {
						$flag = '';
					}
					$minTime = strtotime($minTimeJob->execution_started);
					$maxTime = strtotime($maxTimeJob->execution_finished);
					$timeDiff = $maxTime - $minTime;
					echo htmlspecialchars($flag.niceTime($timeDiff));
				}
				?></td>
			</tr>
		</table>
	</div>
	</div>

	<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('software_jobs'); ?></h2>
		<table id='tblJobContainerJobData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('computer'); ?></th>
					<th class='searchable sortable'><?php echo LANG('package'); ?></th>
					<th class='searchable sortable'><?php echo LANG('procedure'); ?></th>
					<th class='searchable sortable'><?php echo LANG('order'); ?></th>
					<th class='searchable sortable'><?php echo LANG('status'); ?></th>
					<th class='searchable sortable'><?php echo LANG('finished'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($jobs as $job) { ?>
				<tr>
					<td><input type='checkbox' name='job_id[]' value='<?php echo $job->id; ?>'></td>
					<td><a <?php echo explorerLink('views/computer-details.php?id='.$job->computer_id); ?>><?php echo htmlspecialchars($job->computer_hostname); ?></a></td>
					<td><a <?php echo explorerLink('views/package-details.php?id='.$job->package_id); ?>><?php echo htmlspecialchars($job->package_family_name).' ('.htmlspecialchars($job->package_version).')'; ?></a></td>
					<td class='middle' title='<?php echo htmlspecialchars($job->procedure, ENT_QUOTES); ?>'>
						<div class='middle monospace'>
							<?php if($job->is_uninstall == 0) { ?>
								<img src='img/install.dyn.svg' title='<?php echo LANG('install'); ?>'>
							<?php } else { ?>
								<img src='img/delete.dyn.svg' title='<?php echo LANG('uninstall'); ?>'>
							<?php } ?>
						<?php echo htmlspecialchars(shorter($job->procedure)); ?>
						</div>
						<div>
							<?php if($job->post_action == Models\Package::POST_ACTION_RESTART) echo ' ('.LANG('restart_after').' '.intval($job->post_action_timeout).' '.LANG('minutes').')';
							elseif($job->post_action == Models\Package::POST_ACTION_SHUTDOWN) echo ' ('.LANG('shutdown_after').' '.intval($job->post_action_timeout).' '.LANG('minutes').')';
							elseif($job->post_action == Models\Package::POST_ACTION_EXIT) echo ' ('.LANG('restart_agent').')'; ?>
						</div>
					</td>
					<td><?php echo htmlspecialchars($job->sequence); ?></td>
					<td class='middle'>
						<img src='<?php echo $job->getIcon(); ?>'>
						<?php if(empty($job->message)) { ?>
							<?php echo htmlspecialchars($job->getStateString()); ?>
						<?php } else { ?>
							<a href='#' onclick='event.preventDefault();showDialog(this.getAttribute("summary"), this.getAttribute("message"), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_LARGE, true, <?php echo ($job->isRunning()?'true':'false'); ?>)'
								summary='<?php echo htmlspecialchars($job->computer_hostname.' - '.$job->package_family_name.' ('.$job->package_version.'): '.$job->getStateString(), ENT_QUOTES); ?>'
								message='<?php echo htmlspecialchars(str_replace(chr(0x00),'',trim($job->message)), ENT_QUOTES); ?>'>
								<?php echo htmlspecialchars($job->getStateString()); ?>
							</a>
						<?php } ?>
					</td>
					<?php $jobTitle = LANG('not_started');
					if($job->execution_finished != null) {
						$downloadTime = (!empty($job->download_started)&&!empty($job->execution_started)) ? strtotime($job->execution_started)-strtotime($job->download_started) : 0;
						$executionTime = (!empty($job->execution_started)&&!empty($job->execution_finished)) ? strtotime($job->execution_finished)-strtotime($job->execution_started) : 0;
						$jobTitle = LANG('execution_time').': '.niceTime($downloadTime+$executionTime);
					} elseif($job->download_started != null || $job->execution_started != null) {
						$jobTitle = LANG('download_started').': '.($job->download_started??'')
							."\n".LANG('execution_started').': '.($job->execution_started??'');
					} ?>
					<td title='<?php echo htmlspecialchars($jobTitle, ENT_QUOTES); ?>'><?php echo htmlspecialchars($job->execution_finished??''); ?></td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
								<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
								<button onclick='showDialogMoveStaticJobToJobContainer(getSelectedCheckBoxValues("job_id[]", null, true))' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/move.dyn.svg'>&nbsp;<?php echo LANG('move'); ?></button>
								<button onclick='showDialogRenewFailedStaticJobs(<?php echo $container->id; ?>, spnJobContainerName.innerText+" - <?php echo LANG('renew'); ?>", getSelectedCheckBoxValues("job_id[]", null))' <?php if($failed==0 || !$permissionCreate || !$permissionWrite) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('renew_failed'); ?></button>
								<button onclick='removeSelectedJob("job_id[]")' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	</div>

<?php
} else {

	try {
		$selfService = !empty($_GET['selfservice']);
		$containers = $cl->getJobContainers($selfService);
		$permissionCreate = $cl->checkPermission(new Models\JobContainer(), PermissionManager::METHOD_CREATE, false);
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG('not_found')."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG('permission_denied')."</div>");
	} catch(InvalidRequestException $e) {
		die("<div class='alert error'>".$e->getMessage()."</div>");
	}
?>

	<h1><img src='img/<?php echo $selfService ? 'self-service' : 'container'; ?>.dyn.svg'><span id='page-title'><?php echo $selfService ? LANG('self_service_job_containers') : LANG('system_users_job_containers'); ?></span></h1>

	<?php if(!$selfService) { ?>
	<div class='controls'>
		<button onclick='refreshContentDeploy()' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_job_container'); ?></button>
		<span class='filler'></span>
	</div>
	<?php } ?>

	<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblJobcontainerData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('name'); ?></th>
					<th class='searchable sortable'><?php echo LANG('author'); ?></th>
					<th class='searchable sortable'><?php echo LANG('created'); ?></th>
					<th class='searchable sortable'><?php echo LANG('start'); ?></th>
					<th class='searchable sortable'><?php echo LANG('end'); ?></th>
					<th class='searchable sortable'><?php echo LANG('priority'); ?></th>
					<th class='searchable sortable'><?php echo LANG('description'); ?></th>
					<th class='searchable sortable'><?php echo LANG('progress'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($containers as $jc) {
				$done = 0; $percent = 0;
				$jobs = $db->selectAllStaticJobByJobContainer($jc->id);
				if(count($jobs) > 0) {
					foreach($jobs as $job) {
						if($job->state == Models\Job::STATE_SUCCEEDED || $job->state == Models\Job::STATE_ALREADY_INSTALLED) $done ++;
					}
					$percent = $done/count($jobs)*100;
				}
				echo "<tr>";
				echo "<td><input type='checkbox' name='job_container_id[]' value='".$jc->id."'></td>";
				echo "<td class='middle'>";
				echo  "<img src='img/".$jc->getStatus($jobs).".dyn.svg' class='".($jc->enabled?'online':'offline')."'>&nbsp;";
				echo  "<a ".explorerLink('views/job-containers.php?id='.$jc->id).">".htmlspecialchars($jc->name)."</a>";
				echo "</td>";
				echo "<td>".htmlspecialchars($jc->created_by_system_user_username??$jc->created_by_domain_user_username??'')."</td>";
				echo "<td>".htmlspecialchars($jc->created)."</td>";
				echo "<td>".htmlspecialchars($jc->start_time)."</td>";
				echo "<td>".htmlspecialchars($jc->end_time ?? "-")."</td>";
				echo "<td>".htmlspecialchars($jc->priority)."</td>";
				echo "<td>".htmlspecialchars(shorter($jc->notes))."</td>";
				echo "<td sort_key='".$percent."' title='".htmlspecialchars($done.' / '.count($jobs))."'>".progressBar($percent, null, null, 'stretch', '')."</td>";
				echo "</tr>";
			} ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
								<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
								<button onclick='removeSelectedJobContainer("job_container_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	</div>

<?php } ?>
