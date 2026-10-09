<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

$container = null;
try {
	if(!empty($_GET['id'])) {
		$container = $cl->getMyJobContainer($_GET['id']);
		$permissionCreate = $cl->checkPermission(new Models\JobContainer(), SelfService\PermissionManager::METHOD_CREATE, false);
		$permissionWrite  = $cl->checkPermission($container, SelfService\PermissionManager::METHOD_WRITE, false);
		$permissionDelete = $cl->checkPermission($container, SelfService\PermissionManager::METHOD_DELETE, false);

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
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

// Determine precise job container status by checking individual jobs
function getCustomJobContainerStatus($jcStatus, $jobs) {
    if ($jcStatus === Models\JobContainer::STATUS_IN_PROGRESS) {
        $started = 0;
        $waitingForAgent = 0;
        foreach ($jobs as $job) {
            if ($job->state == Models\Job::STATE_DOWNLOAD_STARTED
             || $job->state == Models\Job::STATE_EXECUTION_STARTED) {
                $started++;
            }
            if ($job->state == Models\Job::STATE_WAITING_FOR_AGENT) {
                $waitingForAgent++;
            }
        }
        if ($started > 0) {
            return 'running';
        } elseif ($waitingForAgent > 0) {
            return 'waiting_for_agent';
        }
    }
    return $jcStatus;
}

// Helper to get M3 CSS class for a status
function getM3StatusClass($status) {
    switch ($status) {
        case Models\JobContainer::STATUS_SUCCEEDED:
            return 'success';
        case Models\JobContainer::STATUS_FAILED:
            return 'failed';
        case 'running':
        case Models\JobContainer::STATUS_IN_PROGRESS:
            return 'running';
        case 'waiting_for_agent':
            return 'waiting-for-agent';
        case Models\JobContainer::STATUS_WAITING_FOR_START:
        default:
            return 'waiting';
    }
}

// Helper to get localized status label
function getM3StatusLabel($status) {
    switch ($status) {
        case Models\JobContainer::STATUS_SUCCEEDED:
            return LANG('portal_redesign_status_success');
        case Models\JobContainer::STATUS_FAILED:
            return LANG('portal_redesign_status_failed');
        case 'running':
        case Models\JobContainer::STATUS_IN_PROGRESS:
            return LANG('portal_redesign_status_running');
        case 'waiting_for_agent':
            return LANG('portal_redesign_status_waiting_agent');
        case Models\JobContainer::STATUS_WAITING_FOR_START:
            return LANG('portal_redesign_status_scheduled');
        default:
            return LANG('portal_redesign_status_pending');
    }
}
?>

<div class="m3-jobs-container">

<?php if(empty($container)) { ?>

    <!-- ==========================================================================
         GLOBAL TASK LIST VIEW
         ========================================================================== -->
	<div class='details-header' style="margin-bottom: 24px;">
		<h1><img src='img/job.dyn.svg' style="height: 36px; vertical-align: middle; margin-right: 12px;"><span id='page-title'><?php echo LANG('my_jobs'); ?></span></h1>
	</div>

	<?php 
    $containers = $cl->getMyJobContainers();
    usort($containers, function($a, $b) {
        $dateA = !empty($a->created) ? strtotime($a->created) : 0;
        $dateB = !empty($b->created) ? strtotime($b->created) : 0;
        if ($dateA === $dateB) {
            return $b->id <=> $a->id;
        }
        return $dateB <=> $dateA;
    });
	if(count($containers) == 0) { ?>
		<div class='m3-empty-state' style="text-align: center; padding: 48px; background-color: var(--m3-surface); border-radius: var(--m3-shape-corner-large); border: 1px dashed var(--m3-surface-variant);">
            <div style="font-size: 3rem; margin-bottom: 16px;">📭</div>
            <h3><?php echo LANG('no_jobs_found'); ?></h3>
            <p style="color: var(--m3-on-surface-variant); font-size: 0.9rem; margin-top: 8px;"><?php echo LANG('portal_redesign_explore_catalog_first_install'); ?></p>
        </div>
	<?php } else { 
        // Dynamic status filter calculation
        $existingStatuses = [];
        foreach($containers as $jc) {
            $jcJobs = $db->selectAllStaticJobByJobContainer($jc->id);
            $jcStatus = $jc->getStatus($jcJobs);
            $customStatus = getCustomJobContainerStatus($jcStatus, $jcJobs);
            $statusClass = getM3StatusClass($customStatus);
            $statusLabel = getM3StatusLabel($customStatus);
            
            if (!isset($existingStatuses[$statusClass])) {
                $existingStatuses[$statusClass] = [
                    'label' => $statusLabel,
                    'count' => 0
                ];
            }
            $existingStatuses[$statusClass]['count']++;
        }
        ?>

        <!-- Dynamic Status Filters -->
        <div class="m3-filter-box">
            <span class="m3-filter-title"><?php echo LANG('portal_redesign_filter_by_status'); ?></span>
            <div class="m3-status-filters" id="m3StatusFilters">
                <?php foreach($existingStatuses as $statusClass => $info) { ?>
                    <div class="m3-filter-chip active <?php echo $statusClass; ?>" data-filter="<?php echo $statusClass; ?>">
                        <span class="m3-chip-dot"></span>
                        <span class="m3-chip-label"><?php echo htmlspecialchars($info['label']); ?></span>
                        <span class="m3-chip-count"><?php echo $info['count']; ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>

		<div class='m3-jobs-grid'>
		<?php foreach($containers as $jc) { 
            $jcJobs = $db->selectAllStaticJobByJobContainer($jc->id);
            $jcStatus = $jc->getStatus($jcJobs);
            $customStatus = getCustomJobContainerStatus($jcStatus, $jcJobs);
            $statusClass = getM3StatusClass($customStatus);
            $statusLabel = getM3StatusLabel($customStatus);
            $permissionDeleteJC = $cl->checkPermission($jc, SelfService\PermissionManager::METHOD_DELETE, false);
        ?>
            <a class='m3-job-card status-<?php echo $statusClass; ?>' data-status='<?php echo $statusClass; ?>' <?php echo Html::explorerLink('views/job-containers.php?id='.$jc->id); ?>>
                <div class="m3-job-card-left">
                    <div class="m3-job-icon-wrapper">
                        <img src='img/<?php echo $jcStatus; ?>.dyn.svg' onerror="this.src='img/job.dyn.svg'">
                    </div>
                    <div class="m3-job-details">
                        <h3 class='m3-job-title'><?php echo htmlspecialchars($jc->name); ?></h3>
                        <span class='m3-job-date'><?php echo "📅 " . LANG('portal_redesign_launched_on') . " "; ?><?php echo htmlspecialchars($jc->created); ?></span>
                    </div>
                </div>
                <div class="m3-job-card-right">
                    <div class="m3-status-badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($statusLabel); ?>
                    </div>
                    <button class="m3-btn secondary m3-job-card-delete" title="<?php echo LANG('portal_redesign_delete_task'); ?>" onclick='event.preventDefault(); event.stopPropagation(); removeSelectedJobContainer([<?php echo $jc->id; ?>], event, "<?php echo htmlspecialchars($jc->name, ENT_QUOTES); ?>", "views/job-containers.php")' <?php if(!$permissionDeleteJC) echo 'disabled'; ?>>
                        <img src="img/delete.dyn.svg" class="m3-btn-icon">
                    </button>
                </div>
            </a>
		<?php } ?>
		</div>

        <!-- Interactive Client-side Filter Script -->
        <script>
        (function() {
            const container = document.querySelector('.m3-jobs-container');
            if (!container) return;

            const chips = container.querySelectorAll('.m3-filter-chip');
            const cards = container.querySelectorAll('.m3-job-card');

            chips.forEach(chip => {
                chip.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    this.classList.toggle('active');
                    
                    const activeFilters = new Set();
                    chips.forEach(c => {
                        if (c.classList.contains('active')) {
                            activeFilters.add(c.getAttribute('data-filter'));
                        }
                    });
                    
                    let visibleCount = 0;
                    cards.forEach(card => {
                        const cardStatus = card.getAttribute('data-status');
                        if (activeFilters.has(cardStatus)) {
                            card.style.display = '';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    let emptyState = container.querySelector('#m3-filtered-empty-state');
                    if (visibleCount === 0) {
                        if (!emptyState) {
                            emptyState = document.createElement('div');
                            emptyState.id = 'm3-filtered-empty-state';
                            emptyState.className = 'm3-empty-state';
                            emptyState.style.textAlign = 'center';
                            emptyState.style.padding = '48px';
                            emptyState.style.backgroundColor = 'var(--m3-surface)';
                            emptyState.style.borderRadius = 'var(--m3-shape-corner-large)';
                            emptyState.style.border = '1px dashed var(--m3-surface-variant)';
                            emptyState.style.marginTop = '20px';
                            emptyState.innerHTML = `
                                <div style="font-size: 3rem; margin-bottom: 16px;">🔍</div>
                                <h3><?php echo LANG('portal_redesign_no_task_filter'); ?></h3>
                                <p style="color: var(--m3-on-surface-variant); font-size: 0.9rem; margin-top: 8px;"><?php echo LANG('portal_redesign_activate_other_status'); ?></p>
                            `;
                            const grid = container.querySelector('.m3-jobs-grid');
                            if (grid) {
                                grid.after(emptyState);
                            }
                        } else {
                            emptyState.style.display = '';
                        }
                    } else {
                        if (emptyState) {
                            emptyState.style.display = 'none';
                        }
                    }
                });
            });
        })();
        </script>
	<?php } ?>

<?php } else { 
    // ==========================================================================
    // TASK DETAILS VIEW
    // ==========================================================================
    $customStatus = getCustomJobContainerStatus($icon, $jobs);
    $statusClass = getM3StatusClass($customStatus);
    $statusLabel = getM3StatusLabel($customStatus);
    
    // Runtime calculation
    $realStartTime = strtotime($container->start_time);
    if(strtotime($container->start_time) < strtotime($container->created)) {
        $realStartTime = strtotime($container->created);
    }
    
    $totalRuntimeText = '-';
    if ($realStartTime <= time()) {
        if($icon == Models\JobContainer::STATUS_SUCCEEDED || $icon == Models\JobContainer::STATUS_FAILED) {
            $maxTimeJob = $db->selectMaxExecutionStaticJobByJobContainerId($container->id);
            $maxTime = time();
            if(!empty($maxTimeJob) && !empty($maxTimeJob->execution_finished)) {
                $maxTime = strtotime($maxTimeJob->execution_finished);
            }
            $timeDiff = $maxTime - $realStartTime;
            if($timeDiff >= 0) {
                $totalRuntimeText = niceTime($timeDiff);
            }
        } else {
            $timeDiff = time() - $realStartTime;
            $totalRuntimeText = '~ ' . niceTime($timeDiff);
        }
    }

    $effectiveRuntimeText = '-';
    $minTimeJob = $db->selectMinExecutionStaticJobByJobContainerId($container->id);
    $maxTimeJob = $db->selectMaxExecutionStaticJobByJobContainerId($container->id);
    if(!empty($minTimeJob) && !empty($maxTimeJob) && !empty($minTimeJob->execution_started) && !empty($maxTimeJob->execution_finished)) {
        $flag = ($icon == Models\JobContainer::STATUS_SUCCEEDED || $icon == Models\JobContainer::STATUS_FAILED) ? '' : '~ ';
        $minTime = strtotime($minTimeJob->execution_started);
        $maxTime = strtotime($maxTimeJob->execution_finished);
        $timeDiff = $maxTime - $minTime;
        if($timeDiff >= 0) {
            $effectiveRuntimeText = $flag . niceTime($timeDiff);
        }
    }

    // Contextual return button handling
    $returnUrl = 'views/job-containers.php';
    $returnText = LANG('portal_redesign_back_to_tasks');
    if (isset($_GET['return_to']) && $_GET['return_to'] === 'computer' && !empty($_GET['computer_id'])) {
        $returnUrl = 'views/computers.php?id=' . intval($_GET['computer_id']);
        $returnText = LANG('portal_redesign_back_to_computer_sheet');
    }
?>

    <div class="m3-job-detail-card">
        
        <!-- Contextual Back Button -->
        <div style="margin-bottom: 16px;">
            <a <?php echo Html::explorerLink($returnUrl); ?> class="m3-btn text-btn" style="padding: 6px 12px; margin-left: -12px; font-weight: 500; font-size: 0.95rem;">
                <span style="font-size: 1.2rem; margin-right: 6px; vertical-align: sub;">&larr;</span> <?php echo htmlspecialchars($returnText); ?>
            </a>
        </div>

        <!-- M3 Task Details Header -->
        <div class="m3-job-header-row">
            <div class="m3-job-header-left">
                <div class="m3-job-icon-wrapper" style="width: 56px; height: 56px;">
                    <img src='img/<?php echo $icon; ?>.dyn.svg' class='<?php echo($container->enabled ? 'online' : 'offline'); ?>' style="height: 32px; width: 32px;">
                </div>
                <div>
                    <h1 class="m3-job-headline" id="spnJobContainerName"><?php echo htmlspecialchars($container->name); ?></h1>
                    <span style="font-size: 0.85rem; color: var(--m3-on-surface-variant);"><?php echo "📅 " . LANG('portal_redesign_submitted_on') . " "; ?><?php echo htmlspecialchars($container->created); ?></span>
                </div>
            </div>
            
            <div class="m3-job-header-right">
                <!-- Global Status Badge -->
                <div class="m3-status-badge <?php echo $statusClass; ?>" style="font-size: 0.9rem; padding: 8px 18px;">
                    <?php echo htmlspecialchars($statusLabel); ?>
                </div>
                <!-- Action : Delete task history -->
                <button class="m3-btn secondary small" onclick='removeSelectedJobContainer([<?php echo $container->id; ?>], event, spnJobContainerName.innerText, "views/job-containers.php")' <?php if(!$permissionDelete) echo 'disabled'; ?>>
                    <img src="img/delete.dyn.svg" class="m3-btn-icon" style="margin-right: 6px;"> <?php echo LANG('delete'); ?>
                </button>
            </div>
        </div>

        <!-- Overview Grid -->
        <div class="m3-detail-recap-grid">
            <div class="m3-recap-item" style="grid-column: span 3;">
                <span class="m3-recap-label"><?php echo LANG('portal_redesign_overall_progress'); ?></span>
                <div style="margin-top: 8px;">
                    <?php echo Html::progressBar($percent, null, null, 'stretch', ''); ?>
                    <div style="font-size: 0.8rem; text-align: right; color: var(--m3-on-surface-variant); margin-top: 4px;">
                        <strong><?php echo $done; ?></strong> <?php echo LANG('portal_redesign_on'); ?> <strong><?php echo count($jobs); ?></strong> <?php echo LANG('portal_redesign_validated_packages'); ?>
                    </div>
                </div>
            </div>
            <div class="m3-recap-item" style="grid-column: span 1;">
                <span class="m3-recap-label"><?php echo LANG('portal_redesign_total_execution_time'); ?></span>
                <span class="m3-recap-value" style="margin-top: 10px; font-size: 1.2rem;">⏱️ <?php echo htmlspecialchars($totalRuntimeText); ?></span>
            </div>
        </div>

        <!-- Subjobs List (StaticJobs) -->
        <div>
            <h2 class="m3-card-section-title" style="margin-bottom: 16px;"><?php echo LANG('portal_redesign_software_jobs'); ?></h2>
            
            <div class="m3-subjobs-list" id="tblJobContainerJobData">
                <?php 
                // Deduplicate jobs by unique combination (computer_id, package_id, is_uninstall)
                $dedupedJobs = [];
                foreach ($jobs as $job) {
                    $key = $job->computer_id . '-' . $job->package_id . '-' . $job->is_uninstall;
                    if (!isset($dedupedJobs[$key])) {
                        $dedupedJobs[$key] = $job;
                    } else {
                        $stored = $dedupedJobs[$key];
                        $isStoredFinished = ($stored->state == Models\Job::STATE_SUCCEEDED || $stored->state == Models\Job::STATE_ALREADY_INSTALLED || $stored->state == Models\Job::STATE_FAILED);
                        $isCurrentFinished = ($job->state == Models\Job::STATE_SUCCEEDED || $job->state == Models\Job::STATE_ALREADY_INSTALLED || $job->state == Models\Job::STATE_FAILED);
                        if ($isCurrentFinished && !$isStoredFinished) {
                            $dedupedJobs[$key] = $job;
                        }
                    }
                }

                foreach($dedupedJobs as $job) { 
                    $subjobStatusClass = 'waiting';
                    if ($job->state == Models\Job::STATE_SUCCEEDED || $job->state == Models\Job::STATE_ALREADY_INSTALLED) {
                        $subjobStatusClass = 'success';
                    } elseif ($job->state == Models\Job::STATE_FAILED || $job->state == Models\Job::STATE_EXPIRED || $job->state == Models\Job::STATE_OS_INCOMPATIBLE || $job->state == Models\Job::STATE_PACKAGE_CONFLICT) {
                        $subjobStatusClass = 'failed';
                    } elseif ($job->isRunning()) {
                        $subjobStatusClass = 'running';
                    }

                    $jobComp = $db->selectComputer($job->computer_id);
                    $compIcon = 'img/computer.dyn.svg';
                    if ($jobComp) {
                        $compIcon = $jobComp->getIcon();
                    }
                ?>
                    <div class="m3-subjob-row">
                        <div class="m3-subjob-left">
                            <img src="<?php echo $compIcon; ?>" class="m3-subjob-computer-icon" title="Cible OS" onerror="this.src='img/computer.dyn.svg'">
                            <div class="m3-subjob-info">
                                <span class="m3-subjob-pkg-name">
                                    <a <?php echo Html::explorerLink('views/packages.php?computer_id='.$job->computer_id.'&id='.$job->package_id); ?> style="color: inherit; text-decoration: none;">
                                        <?php if ($job->is_uninstall) { ?>
                                            <span style="display: inline-block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--m3-on-error-container); background-color: var(--m3-error-container); padding: 2px 8px; border-radius: 8px; margin-right: 8px; vertical-align: middle;"><?php echo LANG('portal_redesign_uninstall_badge'); ?></span>
                                        <?php } else { ?>
                                            <span style="display: inline-block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--m3-on-primary-container); background-color: var(--m3-primary-container); padding: 2px 8px; border-radius: 8px; margin-right: 8px; vertical-align: middle;"><?php echo LANG('portal_redesign_install_badge'); ?></span>
                                        <?php } ?>
                                        <?php echo htmlspecialchars($job->package_family_name); ?> (v<?php echo htmlspecialchars($job->package_version); ?>)
                                    </a>
                                </span>
                                <span class="m3-subjob-target">
                                    <?php echo LANG('portal_redesign_computer_prefix'); ?><a <?php echo Html::explorerLink('views/computers.php?id='.$job->computer_id); ?> style="font-weight: 600; color: var(--m3-primary); text-decoration: none;">
                                        <?php echo htmlspecialchars($job->computer_hostname); ?>
                                    </a>
                                </span>
                            </div>
                        </div>
                        
                        <div class="m3-subjob-right">
                            <!-- Job runtime and timing -->
                            <?php if ($job->execution_finished != null) { ?>
                                <span class="m3-subjob-time" title="Date de fin"><?php echo "⏱️ " . sprintf(LANG('portal_redesign_finished_at'), htmlspecialchars(date('H:i:s', strtotime($job->execution_finished)))); ?></span>
                            <?php } elseif ($job->isRunning()) { ?>
                                <span class="m3-subjob-time" style="color: var(--m3-primary); font-weight: 600;">⚡ <?php echo $job->is_uninstall ? LANG('portal_redesign_uninstall_badge') : LANG('portal_redesign_install_badge'); ?> <?php echo LANG('portal_redesign_in_progress_suffix'); ?></span>
                            <?php } else { ?>
                                <span class="m3-subjob-time" style="color: var(--m3-on-surface-variant);"><?php echo LANG('portal_redesign_status_waiting_agent'); ?></span>
                            <?php } ?>

                            <!-- Individual status badge -->
                            <?php if(empty($job->message)) { ?>
                                <div class="m3-status-badge subjob <?php echo $subjobStatusClass; ?>">
                                    <?php echo htmlspecialchars($job->getStateString()); ?>
                                </div>
                            <?php } else { ?>
                                <button class="m3-status-badge subjob <?php echo $subjobStatusClass; ?>" style="cursor: pointer; border-style: dashed;"
                                    onclick='event.preventDefault();showDialog(this.getAttribute("summary"), this.getAttribute("message"), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_LARGE, true, <?php echo ($job->isRunning() ? "true" : "false"); ?>)'
                                    summary='<?php echo htmlspecialchars($job->computer_hostname." - ".$job->package_family_name." (".$job->package_version.") : ".$job->getStateString(), ENT_QUOTES); ?>'
                                    message='<?php echo htmlspecialchars(str_replace(chr(0x00),"",trim($job->message)), ENT_QUOTES); ?>'>
                                    💬 <?php echo htmlspecialchars($job->getStateString()); ?>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

<?php } ?>

</div>
