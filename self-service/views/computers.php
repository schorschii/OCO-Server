<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// ----- prepare view -----
$tab = 'general';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$computer = null;
try {
	if(!empty($_GET['id'])) {
		$computer = $cl->getMyComputer($_GET['id']);
		$permissionDeploy = $cl->checkPermission($computer, SelfService\PermissionManager::METHOD_DEPLOY, false);
		$permissionWol    = $cl->checkPermission($computer, SelfService\PermissionManager::METHOD_WOL, false);
	}

	$computerHistoryLimit = null;
	$permissionEntry = $cl->getPermissionEntry(PermissionManager::SPECIAL_PERMISSION_DOMAIN_USER, PermissionManager::METHOD_READ);
	if(isset($permissionEntry['computer_history_limit'])) $computerHistoryLimit = intval($permissionEntry['computer_history_limit']);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$commands = Models\Computer::getCommands($ext);
?>

<?php if(empty($computer)) { ?>

	<div class='details-header'>
		<h1><img src='img/computer.dyn.svg'><span id='page-title'><?php echo LANG('my_computers'); ?></span></h1>
		<div class='controls'></div>
	</div>
	<?php $computers = $cl->getMyComputers();
	if(count($computers) == 0) { ?>
		<div class='alert info'>
			<h3><?php echo LANG('no_computers_found'); ?></h3>
			<p><?php echo LANG('self_service_offline_help'); ?></p>
		</div>
	<?php } else { ?>
		<div class='gallery gap'>
		<?php foreach($computers as $c) { ?>
			<a class='item box' <?php echo Html::explorerLink('views/computers.php?id='.$c->id); ?>>
				<img src='<?php echo $c->getIcon(); ?>' class='<?php if(!$c->isOnline($db)) echo 'offline'; ?>'>
				<h3><?php echo htmlspecialchars($c->hostname); ?></h3>
				<div class='description'><?php echo htmlspecialchars($c->os . ' ' . $c->os_version); ?></div>
			</a>
		<?php } ?>
		</div>
	<?php } ?>

<?php } else { 
	$isOnline = $computer->isOnline($db);
?>

	<!-- Contextual Back Button -->
	<div class='tabbuttons'>
		<a <?php echo Html::explorerLink('views/computers.php'); ?> class='back'>
			<span>&larr;</span> <?php echo LANG('back'); ?>
		</a>
	</div>

	<!-- Toolbar -->
	<div class='details-header'>
		<h1><img src='<?php echo $computer->getIcon(); ?>' class='<?php echo($computer->isOnline($db) ? 'online' : 'offline'); ?>' title='<?php echo($computer->isOnline($db) ? LANG('online') : LANG('offline')); ?>'><span id='page-title'><span id='spnComputerName'><?php echo htmlspecialchars($computer->hostname); ?></span></span></h1>
		<div class='controls'>
			<button onclick='refreshContentExplorer("views/job-container-new.php?computer_id=<?php echo $computer->id; ?>")' <?php if(!$permissionDeploy) echo 'disabled'; ?>>
				<img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('install_packages'); ?>
			</button>
			<button onclick='confirmWolComputer([<?php echo $computer->id; ?>])' <?php if(!$permissionWol) echo 'disabled'; ?>><img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG('wol'); ?></button>
			<span class='filler'></span>
			<?php
			foreach($commands as $command) {
				Html::commandButton($command, $computer->hostname);
			}
			?>
		</div>
	</div>

	<!-- Offline Alert Banner -->
	<?php if(!$isOnline) { ?>
		<div class='alert warning'>
			<p><b><?php echo LANG('self_service_offline_warning'); ?></b></p>
			<p><?php echo LANG('self_service_offline_help'); ?></p>
		</div>
	<?php } ?>

	<!-- Metadata and Info Grid -->
	<div class='details-abreast'>
		<div>
			<h2><?php echo LANG('information'); ?></h2>
			<table class='list metadata'>
				<tr>
					<th><?php echo LANG('os'); ?></th>
					<td><?php echo htmlspecialchars($computer->os . ' ' . $computer->os_version); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('architechture'); ?></th>
					<td><?php echo htmlspecialchars($computer->architecture); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('cpu'); ?></th>
					<td><?php echo htmlspecialchars($computer->cpu); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('gpu'); ?></th>
					<td><?php echo htmlspecialchars($computer->gpu); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('ram'); ?></th>
					<td><?php echo niceSize($computer->ram, true, 0); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('serial_no'); ?></th>
					<td class='monospace'><?php echo htmlspecialchars(!empty($computer->serial) ? $computer->serial : LANG('portal_redesign_unknown')); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('vendor'); ?></th>
					<td><?php echo htmlspecialchars($computer->manufacturer); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('model'); ?></th>
					<td><?php echo htmlspecialchars($computer->model); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('uptime'); ?></th>
					<td><?php if(!empty($computer->uptime)) echo niceTime($computer->uptime); ?></td>
				</tr>
				<tr>
					<th><?php echo LANG('last_seen'); ?></th>
					<td><?php echo htmlspecialchars($computer->last_ping); ?></td>
				</tr>
			</table>
		</div>

		<!-- Jobs and Installed Packages -->
		<div class='stickytable'>
			<h2><?php echo LANG('pending_jobs'); ?></h2>
			<?php 
			$pendingJobs = $db->selectAllPendingJobByComputerId($computer->id);
			if(empty($pendingJobs)) {
				echo "<div class='alert info'>" . LANG('no_jobs_in_progress') . "</div>";
			} else {
			?>
			<table id='tblPendingComputerJobsData' class='list searchable sortable savesort'>
				<thead>
					<tr>
						<th class='searchable sortable'><?php echo LANG('package'); ?></th>
						<th class='searchable sortable'><?php echo LANG('container'); ?></th>
						<th class='searchable sortable'><?php echo LANG('status'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach($pendingJobs as $j) {
						if(!$cl->checkPermission($db->selectPackage($j->package_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
						echo '<tr class="'.(!$j->isEnabled()?'offline':'').'">';
						echo '<td>';
						if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG('install')."'>&nbsp;";
						else echo "<img src='img/delete.dyn.svg' title='".LANG('uninstall')."'>&nbsp;";
						echo  '<a '.Html::explorerLink('views/packages.php?id='.$j->package_id).'>'.htmlspecialchars($j->package_family_name).' ('.htmlspecialchars($j->package_version).')</a>';
						echo '</td>';
						if($j instanceof Models\DynamicJob) {
							echo '<td><img src="'.$j->getContainerIcon().'" title="'.LANG('deployment_rule').'">&nbsp;<a '.Html::explorerLink('views/deployment-rules.php?id='.$j->deployment_rule_id).'>'.htmlspecialchars($j->deployment_rule_name).'</a></td>';
						} elseif($j instanceof Models\StaticJob) {
							echo '<td><img src="'.$j->getContainerIcon().'" title="'.LANG('job_container').'">&nbsp;<a '.Html::explorerLink('views/job-containers.php?id='.$j->job_container_id).'>'.htmlspecialchars($j->job_container_name).'</a></td>';
						}
						echo '<td class="middle"><img src="'.$j->getIcon().'">&nbsp;'.$j->getStateString().'</td>';
						echo '</tr>';
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan='999'>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
						</td>
					</tr>
				</tfoot>
			</table>
			<?php } ?>

			<h2><?php echo LANG('installed_packages'); ?></h2>
			<?php 
			$installedPackages = $db->selectAllComputerPackageByComputerId($computer->id);
			if(empty($installedPackages)) {
				echo "<div class='alert info'>" . LANG('no_packages_installed') . "</div>";
			} else {
			?>
			<table class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('package'); ?></th>
					<th class='searchable sortable'><?php echo LANG('installation_date'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($installedPackages as $p) {
					if(!$cl->checkPermission($db->selectPackage($p->package_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
					echo "<tr>";
					echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" package_id="'.$p->package_id.'"></td>';
					echo "<td>"
						."<a ".Html::explorerLink('views/packages.php?id='.$p->package_id).">"
						.htmlspecialchars($p->package_family_name)." (".htmlspecialchars($p->package_version).")"
						."</a>"
						."</td>";
					echo "<td>".htmlspecialchars($p->installed)."</td>";
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
								<button onclick='refreshContentExplorer("views/job-container-new.php?computer_id=<?php echo $computer->id; ?>" + Array.from(this.closest("table").querySelectorAll("tbody input[type=checkbox][package_id]:checked"), checkbox => "&package_id[]=" + encodeURIComponent(checkbox.getAttribute("package_id"))).join(""))' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
								<button onclick='showDialogUninstallSelfService()'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
			<?php } ?>
			</table>
		</div>

	</div>

<?php } ?>
