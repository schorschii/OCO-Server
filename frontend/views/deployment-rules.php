<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

if(!empty($_GET['id'])) {

	try {
		$container = $cl->getDeploymentRule($_GET['id'] ?? -1);
		$permissionCreate = $cl->checkPermission(new Models\DeploymentRule(), PermissionManager::METHOD_CREATE, false);
		$permissionWrite  = $cl->checkPermission($container, PermissionManager::METHOD_WRITE, false);
		$permissionDelete = $cl->checkPermission($container, PermissionManager::METHOD_DELETE, false);
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG('not_found')."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG('permission_denied')."</div>");
	} catch(InvalidRequestException $e) {
		die("<div class='alert error'>".$e->getMessage()."</div>");
	}

	$jobs = $db->selectAllDynamicJobByDeploymentRuleId($container->id);
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
		<h1><img src='img/<?php echo $icon; ?>.dyn.svg' class='<?php echo($container->enabled ? 'online' : 'offline'); ?>'><span id='page-title'><span id='spnDeploymentRuleName'><?php echo htmlspecialchars($container->name); ?></span></span></h1>
		<div class='controls'>
			<button onclick='showDialogEditDeploymentRule(<?php echo $container->id; ?>, spnDeploymentRuleName.innerText, spnDeploymentRuleNotes.innerText, spnDeploymentRuleEnabled.innerText, spnDeploymentRuleComputerGroupId.innerText, spnDeploymentRulePackageGroupId.innerText, spnDeploymentRulePriority.innerText, spnDeploymentRuleAutoUninstall.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
			<button onclick='reevaluateDeploymentRule(<?php echo $container->id; ?>)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('reevaluate'); ?></button>
			<button onclick='confirmRemoveDeploymentRule([<?php echo $container->id; ?>], spnDeploymentRuleName.innerText)' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
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
				<th><?php echo LANG('author'); ?></th>
				<td><?php echo htmlspecialchars($container->created_by_system_user_username??''); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('created'); ?></th>
				<td><?php echo htmlspecialchars($container->created); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('enabled'); ?></th>
				<td>
					<?php if($container->enabled=='1') echo LANG('yes'); else echo LANG('no'); ?>
					<span id='spnDeploymentRuleEnabled' class='rawvalue'><?php echo htmlspecialchars($container->enabled); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('computer_group'); ?></th>
				<td>
					<?php echo '<a '.explorerLink('views/computers.php?id='.$container->computer_group_id).'>'.htmlspecialchars($db->getComputerGroupBreadcrumbString($container->computer_group_id)).'</a>'; ?>
					<span id='spnDeploymentRuleComputerGroupId' class='rawvalue'><?php echo htmlspecialchars($container->computer_group_id); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('package_group'); ?></th>
				<td>
					<?php echo '<a '.explorerLink('views/packages.php?id='.$container->package_group_id).'>'.htmlspecialchars($db->getPackageGroupBreadcrumbString($container->package_group_id)).'</a>'; ?>
					<span id='spnDeploymentRulePackageGroupId' class='rawvalue'><?php echo htmlspecialchars($container->package_group_id); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('priority'); ?></th>
				<td>
					<span id='spnDeploymentRulePriority'><?php echo htmlspecialchars($container->priority); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('uninstall_old_package_versions'); ?></th>
				<td>
					<?php if($container->auto_uninstall=='1') echo LANG('yes'); else echo LANG('no'); ?>
					<span id='spnDeploymentRuleAutoUninstall' class='rawvalue'><?php echo htmlspecialchars($container->auto_uninstall); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('description'); ?></th>
				<td>
					<span id='spnDeploymentRuleNotes'><?php echo nl2br(htmlspecialchars($container->notes)); ?></span>
				</td>
			</tr>
		</table>
	</div>
	<div>
		<h2><?php echo LANG('state'); ?></h2>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG('enforcement'); ?></th>
				<td title='<?php echo htmlspecialchars($done.' / '.count($jobs)); ?>'><?php echo progressBar($percent, null, null, 'stretch', ''); ?></td>
			</tr>
		</table>
	</div>
	</div>

	<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('jobs'); ?></h2>
		<table id='tblDeploymentRuleJobData' class='list searchable sortable savesort'>
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
			<?php
			foreach($jobs as $job) {
				echo "<tr>";
				echo "<td><input type='checkbox' name='job_id[]' value='".$job->id."'></td>";
				echo "<td><a ".explorerLink('views/computer-details.php?id='.$job->computer_id).">".htmlspecialchars($job->computer_hostname)."</a></td>";
				echo "<td><a ".explorerLink('views/package-details.php?id='.$job->package_id).">".htmlspecialchars($job->package_family_name)." (".htmlspecialchars($job->package_version).")</a></td>";
				echo "<td class='middle monospace' title='".htmlspecialchars($job->procedure, ENT_QUOTES)."'>";
				if($job->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG('install')."'>&nbsp;";
				else echo "<img src='img/delete.dyn.svg' title='".LANG('uninstall')."'>&nbsp;";
				echo htmlspecialchars(shorter($job->procedure));
				if($job->post_action == Models\Package::POST_ACTION_RESTART) echo ' ('.LANG('restart_after').' '.intval($job->post_action_timeout).' '.LANG('minutes').')';
				if($job->post_action == Models\Package::POST_ACTION_SHUTDOWN) echo ' ('.LANG('shutdown_after').' '.intval($job->post_action_timeout).' '.LANG('minutes').')';
				if($job->post_action == Models\Package::POST_ACTION_EXIT) echo ' ('.LANG('restart_agent').')';
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
				$jobTitle = LANG('not_started');
				if($job->execution_finished != null) {
					$downloadTime = (!empty($job->download_started)&&!empty($job->execution_started)) ? strtotime($job->execution_started)-strtotime($job->download_started) : 0;
					$executionTime = (!empty($job->execution_started)&&!empty($job->execution_finished)) ? strtotime($job->execution_finished)-strtotime($job->execution_started) : 0;
					$jobTitle = LANG('execution_time').': '.niceTime($downloadTime+$executionTime);
				} elseif($job->download_started != null || $job->execution_started != null) {
					$jobTitle = LANG('download_started').': '.($job->download_started??'')
						."\n".LANG('execution_started').': '.($job->execution_started??'');
				}
				echo "<td title='".$jobTitle."'>".htmlspecialchars($job->execution_finished)."</td>";
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
								<button onclick='renewFailedDynamicJobs(<?php echo $container->id; ?>, getSelectedCheckBoxValues("job_id[]", null))' <?php if($failed==0 || !$permissionWrite) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('renew_failed'); ?></button>
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
		$rules = $cl->getDeploymentRules();
		$permissionCreate = $cl->checkPermission(new Models\DeploymentRule(), PermissionManager::METHOD_CREATE, false);
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG('not_found')."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG('permission_denied')."</div>");
	} catch(InvalidRequestException $e) {
		die("<div class='alert error'>".$e->getMessage()."</div>");
	}
?>

	<h1><img src='img/rule.dyn.svg'><span id='page-title'><?php echo LANG('deployment_rules'); ?></span></h1>

	<div class='controls'>
		<button onclick='showDialogEditDeploymentRule()' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_deployment_rule'); ?></button>
		<span class='filler'></span>
	</div>

	<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblDeploymentRuleData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('name'); ?></th>
					<th class='searchable sortable'><?php echo LANG('author'); ?></th>
					<th class='searchable sortable'><?php echo LANG('computer_group'); ?></th>
					<th class='searchable sortable'><?php echo LANG('package_group'); ?></th>
					<th class='searchable sortable'><?php echo LANG('created'); ?></th>
					<th class='searchable sortable'><?php echo LANG('priority'); ?></th>
					<th class='searchable sortable'><?php echo LANG('description'); ?></th>
					<th class='searchable sortable'><?php echo LANG('enforcement'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($rules as $dr) {
				$done = 0; $percent = 0;
				$jobs = $db->selectAllDynamicJobByDeploymentRuleId($dr->id);
				if(count($jobs) > 0) {
					foreach($jobs as $job) {
						if($job->state == Models\Job::STATE_SUCCEEDED || $job->state == Models\Job::STATE_ALREADY_INSTALLED) $done ++;
					}
					$percent = $done/count($jobs)*100;
				}
				echo "<tr>";
				echo "<td><input type='checkbox' name='deployment_rule_id[]' value='".$dr->id."'></td>";
				echo "<td class='middle'>";
				echo  "<img src='img/".$dr->getStatus($jobs).".dyn.svg' class='".($dr->enabled?'online':'offline')."'>&nbsp;";
				echo  "<a ".explorerLink('views/deployment-rules.php?id='.$dr->id).">".htmlspecialchars($dr->name)."</a>";
				echo "</td>";
				echo "<td>".htmlspecialchars($dr->created_by_system_user_username??'')."</td>";
				echo "<td><a ".explorerLink('views/computers.php?id='.$dr->computer_group_id).'>'.htmlspecialchars($db->getComputerGroupBreadcrumbString($dr->computer_group_id))."</a></td>";
				echo "<td><a ".explorerLink('views/packages.php?id='.$dr->package_group_id).'>'.htmlspecialchars($db->getPackageGroupBreadcrumbString($dr->package_group_id))."</a></td>";
				echo "<td>".htmlspecialchars($dr->created)."</td>";
				echo "<td>".htmlspecialchars($dr->priority)."</td>";
				echo "<td>".htmlspecialchars(shorter($dr->notes))."</td>";
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
								<button onclick='removeSelectedDeploymentRule("deployment_rule_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	</div>

<?php } ?>
