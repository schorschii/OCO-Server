<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(!empty($_GET['id'])) {

	try {
		$container = $cl->getJobContainer($_GET['id'] ?? -1);
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG['not_found']."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG['permission_denied']."</div>");
	} catch(InvalidRequestException $e) {
		die("<div class='alert error'>".$e->getMessage()."</div>");
	}

	$jobs = $db->getAllJobByContainer($container->id);
	$done = 0; $failed = 0; $percent = 0;
	if(count($jobs) > 0) {
		foreach($jobs as $job) {
			if($job->state == Job::STATUS_SUCCEEDED || $job->state == Job::STATUS_ALREADY_INSTALLED) $done ++;
			if($job->state == Job::STATUS_FAILED || $job->state == Job::STATUS_EXPIRED || $job->state == Job::STATUS_OS_INCOMPATIBLE || $job->state == Job::STATUS_PACKAGE_CONFLICT) $failed ++;
		}
		$percent = $done/count($jobs)*100;
	}

	$icon = $db->getJobContainerIcon($container->id);
?>

	<h1><img src='img/<?php echo $icon; ?>.dyn.svg'><span id='page-title'><span id='spnJobContainerName'><?php echo htmlspecialchars($container->name); ?></span></span></h1>

	<div class='controls'>
		<button onclick='renameJobContainer(<?php echo $container->id; ?>, spnJobContainerName.innerText)' <?php if(!$currentSystemUser->checkPermission($container, PermissionManager::METHOD_WRITE, false)) echo 'disabled'; ?>><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename']; ?></button>
		<button onclick='showDialogRenewFailedJobs("<?php echo $container->id; ?>", spnJobContainerName.innerText+" - <?php echo LANG['renew']; ?>")' <?php if($failed==0 || !$currentSystemUser->checkPermission(new JobContainer(), PermissionManager::METHOD_CREATE, false) || !$currentSystemUser->checkPermission($container, PermissionManager::METHOD_WRITE, false)) echo 'disabled'; ?>><img src='img/refresh.svg'>&nbsp;<?php echo LANG['renew_failed_jobs']; ?></button>
		<button onclick='confirmRemoveJobContainer([<?php echo $container->id; ?>], spnJobContainerName.innerText)' <?php if(!$currentSystemUser->checkPermission($container, PermissionManager::METHOD_DELETE, false)) echo 'disabled'; ?>><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
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

<?php
} else {
	try {
		$containers = $cl->getJobContainers();
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG['not_found']."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG['permission_denied']."</div>");
	} catch(InvalidRequestException $e) {
		die("<div class='alert error'>".$e->getMessage()."</div>");
	}
?>

	<h1><img src='img/job.dyn.svg'><span id='page-title'><?php echo LANG['job_container']; ?></span></h1>

	<div class='controls'>
		<button onclick='refreshContentDeploy()' <?php if(!$currentSystemUser->checkPermission(new JobContainer(), PermissionManager::METHOD_CREATE, false)) echo 'disabled'; ?>><img src='img/add.svg'>&nbsp;<?php echo LANG['new_deployment_job']; ?></button>
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
			foreach($cl->getJobContainers() as $jc) {
				$counter ++;
				$percent = 0;
				$done = 0;
				$jobs = $db->getAllJobByContainer($jc->id);
				if(count($jobs) > 0) {
					foreach($jobs as $job) {
						if($job->state == Job::STATUS_SUCCEEDED || $job->state == Job::STATUS_ALREADY_INSTALLED) $done ++;
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
