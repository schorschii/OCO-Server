<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// ----- prepare view -----
$tab = 'general';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$package = null;
try {
	if(!empty($_GET['id'])) {
		$package = $cl->getMyPackage($_GET['id']);
		$permissionDeploy   = $cl->checkPermission($package, SelfService\PermissionManager::METHOD_DEPLOY, false);
		$permissionDownload = $cl->checkPermission($package, SelfService\PermissionManager::METHOD_DOWNLOAD, false);

		// ----- download if requested -----
		if(!empty($_GET['download'])) {
			// do not block other frontend requests
			session_write_close();
			// get package file
			if(!$package->getFilePath()) {
				header('HTTP/1.1 404 Not Found'); die();
			}
			// check if domain user is allowed to download
			$cl->checkPermission($package, SelfService\PermissionManager::METHOD_DOWNLOAD);
			$package->download();
			die();
		}
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
} catch(Exception $e) {
	header('HTTP/1.1 500 Internal Server Error');
	die();
}
?>

<div id='self-service-container'>

<?php if(empty($package)) {
$preselectedComputerId = isset($_GET['computer_id']) ? intval($_GET['computer_id']) : null;
$preselectedPackageIds = array_map('intval', (array)($_GET['package_id'] ?? []));
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
	<input type='search' class='filler tableSearch' id='txtPackageSearch' placeholder='<?php echo LANG('search_placeholder'); ?>'>
	<label>
		<input type='checkbox' id='chkNonInstalledOnly'>
		<span><?php echo LANG('not_installed_only'); ?></span>
	</label>
	<label>
		<input type='checkbox' id='chkUpdatesOnly' <?php if(isset($_GET['updates_only'])) echo 'checked'; ?>>
		<span><?php echo LANG('updates_only'); ?></span>
	</label>
</div>

<!-- Packages Grid -->
<div id='divNoPackagesFound' class='alert warning hidden'>
	<?php echo htmlspecialchars(LANG('filter_no_packages_found')); ?>
</div>
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
			<button type='button' class='small' title='<?php echo LANG('details'); ?>' onclick='event.preventDefault();event.stopPropagation();refreshContentExplorer("views/packages.php?id=<?php echo intval($p->id); ?>")'><img src='img/list.dyn.svg'></button>
		</a>
	<?php } ?>
</div>

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
	<div class='drawer-content'>
		<!-- Collapsible Cart Recap Panel -->
		<div class='box' id='divCartRecapPanel'>
			<ul id='ulCartRecapList'>
				<!-- filled dynamically -->
			</ul>
		</div>
	</div>
</div>

<script>initSelfServicePackages();</script>

<?php } else { ?>

<div class='details-header'>
	<h1><img src='<?php echo $package->getIcon(); ?>'><span id='page-title'><?php echo htmlspecialchars($package->getFullName()); ?></span><span id='spnPackageFamilyName' class='rawvalue'><?php echo htmlspecialchars($package->package_family_name); ?></span></h1>
	<div class='controls'>
		<button onclick='refreshContentExplorer("views/packages.php?package_id=<?php echo $package->id; ?>")' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
		<button onclick='window.open("views/packages.php?download=1&id=<?php echo intval($package->id) ?>","_blank")' <?php if(!$package->getSize() || !$permissionDownload) echo "disabled"; ?>><img src='img/download.dyn.svg'>&nbsp;<?php echo LANG('download'); ?></button>
		<span class='filler'></span>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG('general'); ?></h2>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG('compatible_os'); ?></th>
				<td>
					<span id='spnPackageCompatibleOs'><?php echo nl2br(htmlspecialchars($package->compatible_os)); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('compatible_os_version'); ?></th>
				<td>
					<span id='spnPackageCompatibleOsVersion'><?php echo nl2br(htmlspecialchars($package->compatible_os_version)); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('compatible_architecture'); ?></th>
				<td>
					<span id='spnPackageCompatibleArchitecture'><?php echo nl2br(htmlspecialchars($package->compatible_architecture)); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('size'); ?></th>
				<td>
					<?php
					$size = $package->getSize();
					if($size) echo niceSize($size, true).' ';
					else echo LANG('not_found');
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('installation').' - '.LANG('after_completion'); ?></th>
				<td>
					<?php $info = '';
					switch($package->install_procedure_post_action) {
						case Models\Package::POST_ACTION_RESTART: $info = LANG('restart'); break;
						case Models\Package::POST_ACTION_SHUTDOWN: $info = LANG('shutdown'); break;
						case Models\Package::POST_ACTION_EXIT: $info = LANG('restart_agent'); break;
						default: $info = LANG('no_action'); break;
					}
					echo htmlspecialchars($info);
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('uninstallation').' - '.LANG('after_completion'); ?></th>
				<td>
					<?php $info = '';
					switch($package->uninstall_procedure_post_action) {
						case Models\Package::POST_ACTION_RESTART: $info = LANG('restart'); break;
						case Models\Package::POST_ACTION_SHUTDOWN: $info = LANG('shutdown'); break;
						default: $info = LANG('no_action'); break;
					}
					echo htmlspecialchars($info);
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('created'); ?></th>
				<td><?php echo htmlspecialchars($package->created); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('last_updated'); ?></th>
				<td><?php echo htmlspecialchars($package->updated); ?></td>
			</tr>
		</table>

		<h2><?php echo LANG('other_packages_from_this_family'); ?></h2>
		<?php if(!empty($packageFamily->notes)) echo "<p class='quote'>".nl2br(htmlspecialchars($packageFamily->notes))."</p>"; ?>
		<table id='tblOtherPackagesData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('version'); ?></th>
					<th class='searchable sortable'><?php echo LANG('size'); ?></th>
					<th class='searchable sortable'><?php echo LANG('created'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->selectAllPackageByPackageFamilyId($package->package_family_id) as $p) {
					if($p->id === $package->id) continue; // do not show this package
					if(!$cl->checkPermission($p, SelfService\PermissionManager::METHOD_READ, false)) continue;
					echo '<tr>';
					echo '<td><a '.Html::explorerLink('views/packages.php?id='.$p->id).'>'.htmlspecialchars($p->version).'</a></td>';
					echo '<td>'.htmlspecialchars(niceSize($p->getSize())).'</td>';
					echo '<td>'.$p->created.'</td>';
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
	</div>
	<div class='stickytable'>
	<h2><?php echo LANG('pending_jobs'); ?></h2>
	<?php
	$pendingJobs = [];
	foreach($db->selectAllPendingJobByPackageId($package->id) as $j) {
		if(!$cl->checkPermission($db->selectComputer($j->computer_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
		$pendingJobs[] = $j;
	}
	if(empty($pendingJobs)) {
		echo "<div class='alert info'>" . LANG('no_jobs_in_progress') . "</div>";
	} else {
	?>
		<table id='tblPendingPackageJobsData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('computer'); ?></th>
					<th class='searchable sortable'><?php echo LANG('container'); ?></th>
					<th class='searchable sortable'><?php echo LANG('status'); ?></th>
					<th class='searchable sortable'><?php echo LANG('priority').'/'.LANG('sequence'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($pendingJobs as $j) {
					echo '<tr class="'.(!$j->isEnabled()?'offline':'').'">';
					echo '<td>';
					if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG('install')."'>&nbsp;";
					else echo "<img src='img/delete.dyn.svg' title='".LANG('uninstall')."'>&nbsp;";
					echo  '<a '.Html::explorerLink('views/computers.php?id='.$j->computer_id).'>'.htmlspecialchars($j->computer_hostname).'</a>';
					echo '</td>';
					if($j instanceof Models\DynamicJob) {
						echo '<td><img src="'.$j->getContainerIcon().'" title="'.LANG('deployment_rule').'">&nbsp;<a '.Html::explorerLink('views/deployment-rules.php?id='.$j->deployment_rule_id).'>'.htmlspecialchars($j->deployment_rule_name).'</a></td>';
					} elseif($j instanceof Models\StaticJob) {
						echo '<td><img src="'.$j->getContainerIcon().'" title="'.LANG('job_container').'">&nbsp;<a '.Html::explorerLink('views/job-containers.php?id='.$j->job_container_id).'>'.htmlspecialchars($j->job_container_name).'</a></td>';
					}
					echo '<td class="middle"><img src="'.$j->getIcon().'">&nbsp;'.$j->getStateString().'</td>';
					echo '<td sort_key="'.htmlspecialchars($j->getSortKey()).'">'.htmlspecialchars($j->getPriority().'-'.$j->sequence).'</td>';
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

		<h2><?php echo LANG('installed_on'); ?></h2>
		<table id='tblPackageAssignedComputersData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('computer'); ?></th>
					<th class='searchable sortable'><?php echo LANG('initiator'); ?></th>
					<th class='searchable sortable'><?php echo LANG('installation_date'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->selectAllComputerPackageByPackageId($package->id) as $p) {
					if(!$cl->checkPermission($db->selectComputer($p->computer_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
					echo '<tr>';
					echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" computer_id="'.$p->computer_id.'"></td>';
					echo '<td><a '.Html::explorerLink('views/computers.php?id='.$p->computer_id).'>'.htmlspecialchars($p->computer_hostname).'</a></td>';
					echo '<td>'.htmlspecialchars($p->installed_by_system_user_username??$p->installed_by_domain_user_username??'').'</td>';
					echo '<td>'.htmlspecialchars($p->installed).'</td>';
					echo '</tr>';
				}
				?>
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
								<button onclick='showDialogUninstallSelfService()'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
<?php } ?>

</div>
