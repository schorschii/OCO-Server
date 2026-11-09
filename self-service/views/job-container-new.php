<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// Check for target computer in URL
$preselectedComputerId = isset($_GET['computer_id']) ? intval($_GET['computer_id']) : null;

// Check for target packages in URL (id or package_id)
$preselectedPackageIds = array_map('intval', (array)($_GET['id'] ?? $_GET['package_id'] ?? []));
?>

<div class='details-header'>
	<h1><img src='img/deploy.dyn.svg'><span id='page-title'><?php echo LANG('deployment_assistant'); ?></span></h1>
	<div class='controls'></div>
</div>

<div class='controls box filter-bar'>
	<div>
		<span>
			<?php echo LANG('target_computer'); ?>:
			<select id='sltTargetComputer'>
				<option value=''>-</option>
				<?php foreach($cl->getMyComputers() as $c) {
					$installed = [];
					$installedFamilies = [];
					foreach($db->selectAllComputerPackageByComputerId($c->id) as $cp) {
						$installed[] = intval($cp->package_id);
						$installedFamilies[] = intval($cp->package_family_id);
					}
				?>
					<option value='<?php echo htmlspecialchars($c->id); ?>'
					<?php if($preselectedComputerId === intval($c->id)) echo 'selected'; ?>
					data_os='<?php echo htmlspecialchars($c->os); ?>'
					data_os_version='<?php echo htmlspecialchars($c->os_version); ?>'
					data_installed_packages='<?php echo json_encode($installed); ?>'
					data_installed_package_families='<?php echo json_encode(array_values(array_unique($installedFamilies))); ?>'>
						<?php echo htmlspecialchars($c->hostname); ?></option>
				<?php } ?>
			</select>
		</span>
	</div>
	<input type='search' class='filler' id='txtPackageSearch' placeholder='<?php echo LANG('search_placeholder'); ?>'>
	<label>
		<input type='checkbox' id='chkNonInstalledOnly'>
		<span><?php echo LANG('not_installed_only'); ?></span>
	</label>
	<label>
		<input type='checkbox' id='chkUpdatesOnly'>
		<span><?php echo LANG('updates_only'); ?></span>
	</label>
</div>

<!-- Packages Grid -->
<div id='divStoreGrid' class='gallery gap'>
	<?php foreach($cl->getMyPackages() as $p) { ?>
		<a class='item box<?php if(in_array(intval($p->id), $preselectedPackageIds, true)) echo ' selected'; ?>' href='#'
		data_id='<?php echo htmlspecialchars($p->id,ENT_QUOTES); ?>'
		data_package_family_id='<?php echo htmlspecialchars($p->package_family_id,ENT_QUOTES); ?>'
		data_name='<?php echo htmlspecialchars($p->package_family_name,ENT_QUOTES); ?>'
		data_version='<?php echo htmlspecialchars($p->version,ENT_QUOTES); ?>'
		data_compatible_os='<?php echo htmlspecialchars($p->compatible_os,ENT_QUOTES); ?>'
		data_compatible_os_version='<?php echo htmlspecialchars($p->compatible_os_version,ENT_QUOTES); ?>'
		data_compatible_architecture='<?php echo htmlspecialchars($p->compatible_architecture,ENT_QUOTES); ?>'>
			<img src='<?php echo $p->getIcon(); ?>'>
			<h3><?php echo htmlspecialchars($p->package_family_name); ?></h3>
			<div class='description'><?php echo htmlspecialchars($p->version); ?></div>
		</a>
	<?php } ?>
</div>

<div id='divNoPackagesFound' class='alert warning hidden'><?php echo htmlspecialchars(LANG('filter_no_packages_found')); ?></div>

<!-- Sticky Bottom Drawer / Shopping Cart -->
<div class='box' id='divBottomDrawer'>
	<div class='drawer-content'>
		<div class='drawer-left'>
			<button id='btnCartSummary'>
				<img src='img/eye.dyn.svg'>
				<span id='spnCartCount'><?php echo LANG('packages_selected_placeholder'); ?></span>
			</button>
		</div>
		<div class='deploy-tooltip-wrapper'>
			<button class='primary' id='btnDeploy' disabled>
				<img src='img/send.white.svg'>&nbsp;<?php echo LANG('install'); ?>
			</button>
		</div>
	</div>

	<!-- Collapsible Cart Recap Panel -->
	<div class='box' id='divCartRecapPanel'>
		<ul id='ulCartRecapList'>
			<!-- filled dynamically -->
		</ul>
	</div>
</div>

<script>initSelfServicePackages();</script>
