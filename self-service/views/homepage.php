<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// 1. Retrieve user's display name or username
$userName = !empty($currentDomainUser->display_name) ? $currentDomainUser->display_name : (!empty($currentDomainUser->username) ? $currentDomainUser->username : LANG('user'));

$firstName = $userName;
if (strpos($userName, ' ') !== false) {
    $parts = explode(' ', $userName);
    $firstName = $parts[0];
} elseif (strpos($userName, ',') !== false) {
    $parts = explode(',', $userName);
    $firstName = trim(end($parts));
}

// 2. Count active jobs
$activeJobsCount = 0;
if (isset($cl) && isset($db)) {
    $containers = $cl->getMyJobContainers();
    foreach ($containers as $jc) {
        $jobs = $db->selectAllStaticJobByJobContainer($jc->id);
        $status = $jc->getStatus($jobs);
        if ($status !== Models\JobContainer::STATUS_SUCCEEDED && $status !== Models\JobContainer::STATUS_FAILED) {
            $activeJobsCount++;
        }
    }
}

// 3. Retrieve user's recent installations (Self-Service filtered with permission check)
$recentInstalls = [];
if (isset($cl) && isset($db) && isset($_SESSION['oco_self_service_user_id'])) {
    $userId = intval($_SESSION['oco_self_service_user_id']);
    try {
        $myComputers = $cl->getMyComputers();
        if (!empty($myComputers)) {
            $computerIds = [];
            foreach ($myComputers as $comp) {
                $computerIds[] = intval($comp->id);
            }
            
            $pdo = $db->getDbHandle();
            $placeholders = implode(',', array_fill(0, count($computerIds), '?'));
            
            $sql = "SELECT cp.id, cp.package_id, p.package_family_id, pf.name AS package_family_name, c.hostname AS computer_name, c.id AS computer_id, cp.installed
                    FROM computer_package cp
                    JOIN package p ON cp.package_id = p.id
                    JOIN package_family pf ON p.package_family_id = pf.id
                    JOIN computer c ON cp.computer_id = c.id
                    WHERE cp.computer_id IN ($placeholders)
                      AND cp.installed_by_domain_user_id = ?
                    ORDER BY cp.installed DESC, cp.id DESC";
            
            $params = $computerIds;
            $params[] = $userId;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $dedupedAndPermitted = [];
            $seenFamilies = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                if (count($dedupedAndPermitted) >= 5) break;
                
                $famId = $row->package_family_id;
                
                // Deduplicate by package family
                if (!isset($seenFamilies[$famId])) {
                    $package = $db->selectPackage($row->package_id);
                    if ($package && $cl->checkPermission($package, SelfService\PermissionManager::METHOD_READ, false)) {
                        $row->package_name = $row->package_family_name;
                        $dedupedAndPermitted[] = $row;
                        $seenFamilies[$famId] = true;
                    }
                }
            }
            
            $recentInstalls = $dedupedAndPermitted;
        }
    } catch (\Throwable $e) {
        error_log('Error fetching recent installs on homepage: ' . $e->getMessage());
    }
}
?>

<div id='homepage' class='m3-homepage-container'>
    <!-- Hero Section -->
    <div class='m3-hero-section'>
        <div class='m3-hero-content'>
            <h1 class='m3-hero-title'>
                <?php echo sprintf(LANG('portal_redesign_welcome_title'), htmlspecialchars($firstName)); ?>
            </h1>
            <p class='m3-hero-subtitle'>
                <?php echo LANG('portal_redesign_welcome_subtitle'); ?>
            </p>
        </div>
    </div>

    <!-- Action Cards (Cards / CTA) -->
    <div class='m3-cards-grid'>
        <!-- Card A : Install software -->
        <a <?php echo Html::explorerLink('views/packages.php'); ?> class='m3-cta-card m3-card-install'>
            <div class='m3-card-icon-container'>
                <img src='img/package.dyn.svg' alt='Package icon'>
            </div>
            <div class='m3-card-text'>
                <h2 class='m3-card-title'><?php echo LANG('portal_redesign_install_software'); ?></h2>
                <p class='m3-card-description'><?php echo LANG('portal_redesign_install_software_desc'); ?></p>
            </div>
            <span class='m3-card-action-arrow'>&rarr;</span>
        </a>

        <!-- Card B : Track installations -->
        <a <?php echo Html::explorerLink('views/job-containers.php'); ?> class='m3-cta-card m3-card-jobs'>
            <div class='m3-card-icon-container'>
                <img src='img/job.dyn.svg' alt='Job icon'>
                <?php if ($activeJobsCount > 0) { ?>
                    <span class='m3-badge'><?php echo $activeJobsCount; ?></span>
                <?php } ?>
            </div>
            <div class='m3-card-text'>
                <h2 class='m3-card-title'><?php echo LANG('portal_redesign_track_installations'); ?></h2>
                <p class='m3-card-description'><?php echo LANG('portal_redesign_track_installations_desc'); ?></p>
            </div>
            <span class='m3-card-action-arrow'>&rarr;</span>
        </a>

        <!-- Card C : Update computer -->
        <a <?php echo Html::explorerLink('views/packages.php?updates_only=1'); ?> class='m3-cta-card m3-card-updates'>
            <div class='m3-card-icon-container'>
                <img src='img/refresh.dyn.svg' alt='Refresh icon'>
            </div>
            <div class='m3-card-text'>
                <h2 class='m3-card-title'><?php echo LANG('portal_redesign_update_computer'); ?></h2>
                <p class='m3-card-description'><?php echo LANG('portal_redesign_update_computer_desc'); ?></p>
            </div>
            <span class='m3-card-action-arrow'>&rarr;</span>
        </a>
    </div>

    <!-- Recent installations -->
    <?php if (!empty($recentInstalls)): ?>
    <div class='m3-recent-installs-section'>
        <h2 class='m3-recent-installs-title'><?php echo LANG('portal_redesign_recent_installs'); ?></h2>
        <div class='m3-table-container'>
            <table class='m3-recent-installs-table'>
                <thead>
                    <tr>
                        <th><?php echo LANG('portal_redesign_table_package'); ?></th>
                        <th><?php echo LANG('portal_redesign_table_computer'); ?></th>
                        <th><?php echo LANG('portal_redesign_table_date'); ?></th>
                        <th class='text-right'><?php echo LANG('portal_redesign_table_action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentInstalls as $install): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($install->package_name); ?></strong></td>
                        <td><?php echo htmlspecialchars($install->computer_name); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($install->installed))); ?></td>
                        <td class='text-right'>
                            <button type="button" class="m3-button m3-button-tonal m3-btn-relaunch" 
                                    onclick='refreshContentExplorer("views/packages.php?computer_id=<?php echo $install->computer_id; ?>&id=<?php echo $install->package_id; ?>");'>
                                <?php echo LANG('portal_redesign_relaunch'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class='footer'>
        <?php require('partial/copyright.php'); ?>
    </div>
</div>
