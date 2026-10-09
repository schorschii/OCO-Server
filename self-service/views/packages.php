<?php
// self-service/views/packages.php
$SUBVIEW = 1;

require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// Check for target computer in URL
$preselectedComputerId = isset($_GET['computer_id']) ? intval($_GET['computer_id']) : null;
$preselectedUpdatesOnly = isset($_GET['updates_only']) ? 'true' : 'false';

// Check for target package in URL (id or package_id)
$preselectedPackageId = null;
if (isset($_GET['id'])) {
    $preselectedPackageId = intval($_GET['id']);
} elseif (isset($_GET['package_id'])) {
    $preselectedPackageId = intval($_GET['package_id']);
}
?>

<!-- Pass configuration and translations to JavaScript -->
<script>
    window.OcoConfig = {
        preselectedComputerId: <?php echo $preselectedComputerId ? $preselectedComputerId : 'null'; ?>,
        preselectedPackageId: <?php echo $preselectedPackageId ? $preselectedPackageId : 'null'; ?>,
        preselectedUpdatesOnly: <?php echo $preselectedUpdatesOnly; ?>,
        ajaxUrlComputers: 'ajax-handler/computers.php',
        ajaxUrlPackages: 'ajax-handler/packages.php',
        ajaxUrlDeploy: 'ajax-handler/job-containers.php'
    };
    
    window.OcoLang = {
        deploy: <?php echo json_encode(LANG('portal_redesign_deploy_btn')); ?>,
        cancel: <?php echo json_encode(LANG('portal_redesign_modal_cancel')); ?>,
        confirm: <?php echo json_encode(LANG('portal_redesign_modal_confirm')); ?>,
        search: <?php echo json_encode(LANG('portal_redesign_search_placeholder')); ?>,
        noPackages: <?php echo json_encode(LANG('portal_redesign_js_no_packages_found')); ?>,
        sendWol: <?php echo json_encode(LANG('send_wol')); ?>,
        shutdownWaked: <?php echo json_encode(LANG('shutdown_waked_computers')); ?>,
        selectedItems: <?php echo json_encode(LANG('portal_redesign_cart_count_text')); ?>,
        
        // Custom variables used by redesign-store.js
        errorLoading: <?php echo json_encode(LANG('portal_redesign_js_error_loading')); ?>,
        readyToInstall: <?php echo json_encode(LANG('portal_redesign_js_ready_to_install')); ?>,
        remove: <?php echo json_encode(LANG('portal_redesign_js_remove')); ?>,
        defaultJobName: <?php echo json_encode(LANG('portal_redesign_js_default_job_name')); ?>,
        recapTarget: <?php echo json_encode(LANG('portal_redesign_js_recap_target')); ?>,
        deploying: <?php echo json_encode(LANG('portal_redesign_js_deploying')); ?>,
        deployError: <?php echo json_encode(LANG('portal_redesign_js_deploy_error')); ?>,
        unknownError: <?php echo json_encode(LANG('portal_redesign_js_unknown_error')); ?>
    };

    // Initialize package store logic on view load
    if (typeof window.initOcoPortalRedesign === 'function') {
        window.initOcoPortalRedesign();
    }
</script>

<div class="m3-store-container">
    <div class="details-header" style="margin-bottom: 24px;">
        <h1><img src="img/package.dyn.svg" style="height: 36px; vertical-align: middle; margin-right: 12px;"><span id="page-title"><?php echo LANG('portal_redesign_store_title'); ?></span></h1>
    </div>

    <!-- Sticky M3 Filter Bar -->
    <div class="m3-store-filter-bar">
        <!-- Target Machine Selector (Now in First Position) -->
        <div class="m3-filter-group">
            <span class="m3-filter-label"><?php echo LANG('portal_redesign_target_computer_label'); ?></span>
            <div class="m3-select-wrapper">
                <select class="m3-computer-select" id="m3TargetComputer">
                    <option value=""><?php echo LANG('portal_redesign_select_computer_placeholder'); ?></option>
                </select>
                <span class="m3-select-icon">▼</span>
            </div>
        </div>

        <!-- Search Bar and Switch Checkbox -->
        <div class="m3-filter-group" style="flex-grow: 1; justify-content: flex-end;">
            <div class="m3-search-wrapper">
                <span class="m3-search-icon">🔍</span>
                <input type="search" class="m3-store-search" id="m3StoreSearch" placeholder="<?php echo LANG('portal_redesign_search_placeholder'); ?>">
            </div>
            
            <label class="m3-switch-label">
                <input type="checkbox" class="m3-switch-input" id="m3InstalledOnly">
                <span><?php echo LANG('portal_redesign_installed_only'); ?></span>
            </label>

            <label class="m3-switch-label" style="margin-left: 16px;">
                <input type="checkbox" class="m3-switch-input" id="m3UpdatesOnly">
                <span><?php echo LANG('portal_redesign_updates_only'); ?></span>
            </label>
        </div>
    </div>

    <!-- App Store Packages Grid -->
    <div class="m3-store-grid" id="m3StoreGrid">
        <!-- Dynamically loaded packages cards go here -->
        <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-secondary);">
            <div class="m3-progress-container" style="max-width: 200px; margin: 0 auto 16px auto; height: 4px;">
                <div class="m3-progress-bar-animated" style="width: 100%;"></div>
            </div>
            <?php echo LANG('portal_redesign_loading_catalog'); ?>
        </div>
    </div>
</div>

<!-- Sticky Bottom Drawer / Shopping Cart -->
<div class="m3-bottom-drawer" id="m3BottomDrawer">
    <div class="m3-drawer-content">
        <div class="m3-drawer-left">
            <button class="m3-cart-summary-btn" id="m3CartSummaryBtn">
                🛒 <span id="m3CartCount">0</span> <?php echo LANG('portal_redesign_cart_count_suffix'); ?> <span>▼</span>
            </button>
            <span class="m3-cart-text-recap" id="m3CartTextRecap"><?php echo LANG('portal_redesign_cart_no_selection'); ?></span>
        </div>
        <div class="m3-deploy-tooltip-wrapper">
            <button class="m3-btn primary" id="m3DeployBtn" disabled>
                <img src="img/deploy.dyn.svg" style="height: 18px; filter: brightness(0) invert(1); margin-right: 8px;"> <?php echo LANG('portal_redesign_deploy_btn'); ?>
            </button>
            <span class="m3-deploy-tooltip" id="m3DeployTooltip"><?php echo LANG('portal_redesign_deploy_tooltip'); ?></span>
        </div>
    </div>
    
    <!-- Collapsible Cart Recap Panel -->
    <div class="m3-cart-recap-panel" id="m3CartRecapPanel">
        <ul class="m3-recap-list" id="m3CartRecapList">
            <!-- Filled dynamically -->
        </ul>
    </div>
</div>

<!-- Material Design 3 Confirmation Modal -->
<div class="m3-modal-overlay" id="m3ConfirmModal">
    <div class="m3-modal-card">
        <div class="m3-modal-header">
            <h2 class="m3-modal-title">
                <img src="img/deploy.dyn.svg" alt="Deploy Icon">
                <?php echo LANG('portal_redesign_modal_confirm_title'); ?>
            </h2>
        </div>
        <div class="m3-modal-body">
            <div class="m3-modal-form-row">
                <label style="font-weight: 600; font-size: 0.9rem; color: var(--m3-on-surface);"><?php echo LANG('portal_redesign_modal_job_name_label'); ?></label>
                <input type="text" class="m3-modal-input" id="m3JobName" placeholder="<?php echo LANG('portal_redesign_modal_job_name_placeholder'); ?>">
            </div>
            
            <div class="m3-modal-recap-box">
                <div class="m3-modal-recap-target" id="m3RecapTarget">
                    <!-- Target machine info with OS icon goes here -->
                </div>
                <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px;"><?php echo LANG('portal_redesign_modal_packages_to_install'); ?></div>
                <ul class="m3-modal-recap-list" id="m3ModalRecapList">
                    <!-- List of selected packages goes here -->
                </ul>
            </div>
        </div>
        <div class="m3-modal-actions">
            <button class="m3-btn secondary" id="m3CancelDeployBtn"><?php echo LANG('portal_redesign_modal_cancel'); ?></button>
            <button class="m3-btn primary" id="m3ConfirmDeployBtn"><?php echo LANG('portal_redesign_modal_confirm'); ?></button>
        </div>
    </div>
</div>
