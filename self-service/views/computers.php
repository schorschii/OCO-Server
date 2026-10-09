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

    <div class="m3-computer-detail-container" style="padding-bottom: 0;">
        <div class='m3-computer-header' style="margin-bottom: 24px;">
            <div class='m3-computer-identity'>
                <h1><img src='img/computer.dyn.svg' style="height: 36px; vertical-align: middle; margin-right: 12px;"><span id='page-title'><?php echo LANG('portal_redesign_nav_computers'); ?></span></h1>
            </div>
        </div>

        <?php $computers = $cl->getMyComputers();
        if(count($computers) == 0) { ?>
            <div class='m3-empty-state' style="text-align: center; padding: 48px; background-color: var(--m3-surface); border-radius: var(--m3-shape-corner-large); border: 1px dashed var(--m3-surface-variant);">
                <div style="font-size: 3rem; margin-bottom: 16px;">💻</div>
                <h3><?php echo LANG('portal_redesign_no_computers_found'); ?></h3>
                <p style="color: var(--m3-on-surface-variant); font-size: 0.9rem; margin-top: 8px;"><?php echo LANG('portal_redesign_offline_help'); ?></p>
            </div>
        <?php } else { ?>
            <div class='m3-cards-grid' style="margin-top: 20px;">
            <?php foreach($computers as $c) { 
                $compOnline = $c->isOnline($db);
            ?>
                <a class='m3-cta-card m3-card-install' <?php echo Html::explorerLink('views/computers.php?id='.$c->id); ?>>
                    <div class='m3-card-icon-container' style="background-color: var(--m3-secondary-container);">
                        <img src='<?php echo $c->getIcon(); ?>' onerror="this.src='img/computer.dyn.svg'" alt='Computer Icon'>
                        <span class='m3-heartbeat-badge <?php echo $compOnline ? 'online' : 'offline'; ?>' style="position: absolute; bottom: -4px; right: -4px; width: 14px; height: 14px; border-width: 2px;"></span>
                    </div>
                    <div class='m3-card-text'>
                        <h2 class='m3-card-title'><?php echo htmlspecialchars($c->hostname); ?></h2>
                        <p class='m3-card-description'><?php echo htmlspecialchars($c->os . ' ' . $c->os_version); ?></p>
                        <span style="font-size: 0.75rem; color: var(--m3-on-surface-variant);"><?php echo LANG('portal_redesign_last_contact') . ' : '; ?><?php echo htmlspecialchars($c->last_ping); ?></span>
                    </div>
                    <span class='m3-card-action-arrow'>&rarr;</span>
                </a>
            <?php } ?>
            </div>
        <?php } ?>
    </div>

<?php } else { 
	$isOnline = $computer->isOnline($db);
	
	// Human-readable uptime calculation
	$uptimeText = LANG('portal_redesign_unavailable');
	if (!empty($computer->uptime)) {
		$seconds = intval($computer->uptime);
		$hours = floor($seconds / 3600);
		$days = floor($hours / 24);
		
		if ($days > 0) {
			$uptimeText = sprintf(LANG('portal_redesign_uptime_days'), $days);
		} elseif ($hours > 0) {
			$uptimeText = sprintf(LANG('portal_redesign_uptime_hours'), $hours);
		} else {
			$minutes = floor($seconds / 60);
			$uptimeText = sprintf(LANG('portal_redesign_uptime_minutes'), $minutes);
		}
	}
	?>

	<!-- M3 Computer Details Header -->
	<div class='m3-computer-detail-container'>
		
		<!-- Contextual Back Button -->
		<div style="margin-bottom: 16px;">
			<a <?php echo Html::explorerLink('views/computers.php'); ?> class="m3-btn text-btn" style="padding: 6px 12px; margin-left: -12px; font-weight: 500; font-size: 0.95rem;">
				<span style="font-size: 1.2rem; margin-right: 6px; vertical-align: sub;">&larr;</span> <?php echo LANG('portal_redesign_back_to_computers'); ?>
			</a>
		</div>
		
		<!-- Offline Alert Banner -->
		<?php if (!$isOnline) { ?>
			<div class='m3-alert-banner danger'>
				<div class='m3-alert-icon'>⚠️</div>
				<div class='m3-alert-content'>
					<h3><?php echo LANG('portal_redesign_offline_warning'); ?></h3>
					<p><?php echo LANG('portal_redesign_offline_help'); ?></p>
				</div>
			</div>
		<?php } ?>

		<div class='m3-computer-header'>
			<div class='m3-computer-identity'>
				<div class='m3-heartbeat-badge <?php echo $isOnline ? 'online' : 'offline'; ?>' title='<?php echo $isOnline ? 'En ligne' : 'Hors ligne'; ?>'></div>
				<h1 id='page-title'><span id='spnComputerName'><?php echo htmlspecialchars($computer->hostname); ?></span></h1>
			</div>
			
			<div class='m3-header-controls'>
				<!-- Direct deployment link to packages.php with preselected computer -->
				<a <?php echo Html::explorerLink('views/packages.php?computer_id='.$computer->id); ?> class='m3-btn primary' <?php if(!$permissionDeploy) echo 'style="pointer-events: none; opacity: 0.6;"'; ?>>
					<img src='img/deploy.dyn.svg' class='m3-btn-icon'>&nbsp;<?php echo LANG('portal_redesign_install_on_this_computer'); ?>
				</a>
			</div>
		</div>

		<!-- Metadata and Info Grid -->
		<div class='m3-grid-abreast'>
			<!-- Metadata Card -->
			<div class='m3-details-card'>
				<h2 class='m3-card-section-title'><?php echo LANG('portal_redesign_machine_specs'); ?></h2>
				<div class='m3-metadata-flex'>
					<div class='m3-metadata-icon-wrapper'>
						<img src='<?php echo $computer->getIcon(); ?>' alt='OS Logo' class='m3-os-display-logo'>
					</div>
					<table class='m3-metadata-table'>
						<tr>
							<th><?php echo LANG('portal_redesign_os_label'); ?></th>
							<td><?php echo htmlspecialchars($computer->os . ' ' . $computer->os_version); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('portal_redesign_serial_number'); ?></th>
							<td class='monospace'><?php echo htmlspecialchars(!empty($computer->serial) ? $computer->serial : LANG('portal_redesign_unknown')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('portal_redesign_last_boot'); ?></th>
							<td><?php echo htmlspecialchars($uptimeText); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('portal_redesign_last_contact'); ?></th>
							<td><?php echo htmlspecialchars($computer->last_ping); ?></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Tasks and Installed Software -->
			<div class='m3-details-card'>
				<h2 class='m3-card-section-title'><?php echo LANG('portal_redesign_install_status'); ?></h2>
				
				<!-- Pending Installations -->
				<div class='m3-sub-section'>
					<h3><?php echo LANG('portal_redesign_installs_in_progress'); ?></h3>
					<?php 
					$pendingJobs = $db->selectAllPendingJobByComputerId($computer->id);
					if (count($pendingJobs) == 0) {
						echo "<p class='m3-empty-state'>" . LANG('portal_redesign_no_downloads') . "</p>";
					} else {
						// Deduplicate by package_id and is_uninstall
						$dedupedPending = [];
						foreach ($pendingJobs as $j) {
							$key = $j->package_id . '-' . $j->is_uninstall;
							$dedupedPending[$key] = $j;
						}

						echo "<div class='m3-pending-list'>";
						foreach($dedupedPending as $j) {
							if(!$cl->checkPermission($db->selectPackage($j->package_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
							?>
							<a <?php echo Html::explorerLink('views/job-containers.php?id=' . $j->getContainerId() . '&return_to=computer&computer_id=' . $computer->id); ?> class='m3-pending-item-link'>
								<div class='m3-pending-item clickable' title='Cliquer pour suivre cette tâche'>
									<div class='m3-pending-info'>
										<span class='m3-pending-title'>
											<?php if ($j->is_uninstall) { ?>
												<span style="font-size: 0.7rem; font-weight: bold; text-transform: uppercase; color: var(--m3-on-error-container); background-color: var(--m3-error-container); padding: 2px 6px; border-radius: 4px; margin-right: 6px; vertical-align: middle;"><?php echo LANG('portal_redesign_uninstall_badge'); ?></span>
											<?php } ?>
											<?php echo htmlspecialchars($j->package_family_name); ?>
										</span>
										<span class='m3-pending-status'><?php echo htmlspecialchars($j->getStateString()); ?></span>
									</div>
									<div class='m3-progress-container'>
										<div class='m3-progress-bar-animated'></div>
									</div>
								</div>
							</a>
							<?php
						}
						echo "</div>";
					}
					?>
				</div>

				<!-- Installed Software -->
				<div class='m3-sub-section margintop-24'>
					<h3><?php echo LANG('portal_redesign_installed_software'); ?></h3>
					<?php 
					$installedPackages = $db->selectAllComputerPackageByComputerId($computer->id);
					if (count($installedPackages) == 0) {
						echo "<p class='m3-empty-state'>" . LANG('portal_redesign_no_packages_installed') . "</p>";
					} else {
						// Deduplicate by package family (keep most recent)
						$dedupedInstalled = [];
						foreach ($installedPackages as $p) {
							$famId = $p->package_family_id;
							if (!isset($dedupedInstalled[$famId]) || strtotime($p->installed) > strtotime($dedupedInstalled[$famId]->installed)) {
								$dedupedInstalled[$famId] = $p;
							}
						}

						echo "<table class='m3-installed-table'>";
						echo "<thead><tr><th>" . LANG('portal_redesign_table_package') . "</th><th>" . LANG('portal_redesign_table_date') . "</th><th>" . LANG('portal_redesign_table_action') . "</th></tr></thead>";
						echo "<tbody>";
						foreach($dedupedInstalled as $p) {
							if(!$cl->checkPermission($db->selectPackage($p->package_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
							echo "<tr>";
							echo "<td><strong>".htmlspecialchars($p->package_family_name)."</strong> (".htmlspecialchars($p->package_version).")</td>";
							echo "<td>".htmlspecialchars(date('d/m/Y H:i', strtotime($p->installed)))."</td>";
							echo "<td>";
							echo "<button class='m3-btn text-btn' onclick='refreshContentExplorer(\"views/packages.php?computer_id=" . $computer->id . "&id=" . $p->package_id . "\");'><img src='img/deploy.dyn.svg' style='height:16px;'> " . LANG('portal_redesign_relaunch') . "</button>";
							echo "</td>";
							echo "</tr>";
						}
						echo "</tbody></table>";
					}
					?>
				</div>

			</div>
		</div>

	</div>

<?php } ?>
