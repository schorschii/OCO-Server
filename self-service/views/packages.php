<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.php');

// ----- prepare view -----
$tab = 'general';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$package = null;
try {
	if(!empty($_GET['id'])) {
		$package = $cl->getMyPackage($_GET['id']);

		$permissionDeploy   = $cl->checkPermission($package, SelfService\PermissionManager::METHOD_DEPLOY, false);
		$permissionDownload = $cl->checkPermission($package, SelfService\PermissionManager::METHOD_DOWNLOAD, false);
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<?php if(empty($package)) { ?>

	<div class='details-header'>
		<h1><img src='img/package.dyn.svg'><span id='page-title'><?php echo LANG('available_packages'); ?></span></h1>
		<div class='controls'></div>
	</div>
	<?php $packages = $cl->getMyPackages();
	if(count($packages) == 0) { ?>
		<div class='alert info'><?php echo LANG('no_packages_found'); ?></div>
	<?php } else { ?>
		<div class='gallery gap'>
		<?php foreach($packages as $p) { ?>
			<a class='item orange' <?php echo explorerLink('views/packages.php?id='.$p->id); ?>>
				<img src='<?php echo $p->getIcon(); ?>'>
				<h3><?php echo htmlspecialchars($p->getFullName()); ?></h3>
			</a>
		<?php } ?>
		</div>
	<?php } ?>

<?php } else { ?>

<div class='details-header'>
	<h1><img src='<?php echo $package->getIcon(); ?>'><span id='page-title'><?php echo htmlspecialchars($package->getFullName()); ?></span><span id='spnPackageFamilyName' class='rawvalue'><?php echo htmlspecialchars($package->package_family_name); ?></span></h1>
	<div class='controls'>
		<button onclick='refreshContentDeploy({<?php echo $package->id; ?>:obj("page-title").innerText});' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
		<button onclick='window.open("package-download.php?id=<?php echo intval($package->id) ?>","_blank")' <?php if(!$package->getSize() || !$permissionDownload) echo "disabled"; ?>><img src='img/download.dyn.svg'>&nbsp;<?php echo LANG('download'); ?></button>
		<span class='filler'></span>
	</div>
</div>

<div id='tabControlPackage' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='general' class='<?php if($tab=='general') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPackage,this.getAttribute("name"))'><?php echo LANG('general_and_dependencies'); ?></a>
		<a href='#' name='computers' class='<?php if($tab=='computers') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPackage,this.getAttribute("name"))'><?php echo LANG('computer_and_jobs'); ?></a>
	</div>
	<div class='tabcontents'>
		<div name='general' class='<?php if($tab=='general') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('general'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('compatible_os'); ?></th>
							<td>
								<span id='spnPackageCompatibleOs'><?php echo htmlspecialchars($package->compatible_os); ?></span>
							</td>
						</tr>
						<tr>
							<th><?php echo LANG('compatible_os_version'); ?></th>
							<td>
								<span id='spnPackageCompatibleOsVersion'><?php echo htmlspecialchars($package->compatible_os_version); ?></span>
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
							<th><?php echo LANG('author'); ?></th>
							<td><?php echo htmlspecialchars($package->author); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('created'); ?></th>
							<td><?php echo htmlspecialchars($package->created); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('last_updated'); ?></th>
							<td><?php echo htmlspecialchars($package->last_update); ?></td>
						</tr>
					</table>
					<h2><?php echo LANG('installation'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('after_completion'); ?></th>
							<td>
								<span id='spnPackageInstallProcedurePostAction' class='rawvalue'><?php echo htmlspecialchars($package->install_procedure_post_action); ?></span>
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
					</table>
					<h2><?php echo LANG('uninstallation'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('after_completion'); ?></th>
							<td>
								<span id='spnPackageUninstallProcedurePostAction' class='rawvalue'><?php echo htmlspecialchars($package->uninstall_procedure_post_action); ?></span>
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
							<th><?php echo LANG('download_for_uninstall'); ?></th>
							<td>
								<span id='spnPackageDownloadForUninstall' class='rawvalue'><?php echo htmlspecialchars($package->download_for_uninstall); ?></span>
								<?php $info = ''; if($package->download_for_uninstall) $info = LANG('yes'); else $info = LANG('no'); echo htmlspecialchars($info); ?>
							</td>
						</tr>
					</table>
				</div>
				<div>
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
							$counter = 0;
							foreach($db->selectAllPackageByPackageFamilyId($package->package_family_id) as $p) {
								if($p->id === $package->id) continue; // do not show this package
								$counter ++;
								echo '<tr>';
								echo '<td><a '.explorerLink('views/package-details.php?id='.$p->id).'>'.htmlspecialchars($p->version).'</a></td>';
								echo '<td>'.htmlspecialchars(niceSize($p->getSize())).'</td>';
								echo '<td>'.$p->created.'</td>';
								echo '</tr>';
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>

			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('depends_on'); ?></h2>
					<table id='tblDependencyPackageData' class='list sortable savesort'>
						<thead>
							<tr>
								<th class='sortable'><?php echo LANG('name'); ?></th>
								<th class='sortable'><?php echo LANG('version'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$counter = 0;
							foreach($db->selectAllPackageDependencyByPackageId($package->id) as $dp) {
								$counter ++;
								echo '<tr>';
								echo '<td><a '.explorerLink('views/packages.php?id='.$dp->id).'>'.htmlspecialchars($dp->package_family_name).'</a></td>';
								echo '<td><a '.explorerLink('views/packages.php?id='.$dp->id).'>'.htmlspecialchars($dp->version).'</a></td>';
								echo '</tr>';
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>,
											<span class='counter-checked'>0</span>&nbsp;<?php echo LANG('elements_checked'); ?>
										</div>
										<div class='controls'>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
				<div>
					<h2><?php echo LANG('dependent_packages'); ?></h2>
					<table id='tblDependentPackageData' class='list sortable savesort'>
						<thead>
							<tr>
								<th class='sortable'><?php echo LANG('name'); ?></th>
								<th class='sortable'><?php echo LANG('version'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$counter = 0;
							foreach($db->selectAllPackageDependencyByDependentPackageId($package->id) as $dp) {
								$counter ++;
								echo '<tr>';
								echo '<td><a '.explorerLink('views/packages.php?id='.$dp->id).'>'.htmlspecialchars($dp->package_family_name).'</a></td>';
								echo '<td><a '.explorerLink('views/packages.php?id='.$dp->id).'>'.htmlspecialchars($dp->version).'</a></td>';
								echo '</tr>';
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>,
											<span class='counter-checked'>0</span>&nbsp;<?php echo LANG('elements_checked'); ?>
										</div>
										<div class='controls'>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>

		<div name='computers' class='<?php if($tab=='computers') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<h2><?php echo LANG('installed_on'); ?></h2>
					<table id='tblPackageAssignedComputersData' class='list searchable sortable savesort'>
						<thead>
							<tr>
								<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageAssignedComputersData, this.checked)'></th>
								<th class='searchable sortable'><?php echo LANG('computer'); ?></th>
								<th class='searchable sortable'><?php echo LANG('initiator'); ?></th>
								<th class='searchable sortable'><?php echo LANG('installation_date'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$counter = 0;
							foreach($db->selectAllComputerPackageByPackageId($package->id) as $p) {
								if(!$cl->checkPermission($db->selectComputer($p->computer_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
								$counter ++;
								echo '<tr>';
								echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" computer_id="'.$p->computer_id.'" onchange="refreshCheckedCounter(tblPackageAssignedComputersData)"></td>';
								echo '<td><a '.explorerLink('views/computers.php?id='.$p->computer_id).'>'.htmlspecialchars($p->computer_hostname).'</a></td>';
								echo '<td>'.htmlspecialchars($p->installed_by).'</td>';
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
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>,
											<span class='counter-checked'>0</span>&nbsp;<?php echo LANG('elements_checked'); ?>
										</div>
										<div class='controls'>
											<button onclick='deploySelectedComputer("package_id[]", "computer_id");'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
				<div class='stickytable'>
					<h2><?php echo LANG('pending_jobs'); ?></h2>
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
							$counter = 0;
							foreach($db->selectAllPendingJobByPackageId($package->id) as $j) {
								if(!$cl->checkPermission($db->selectComputer($j->computer_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
								$counter ++;
								echo '<tr class="'.(!$j->isEnabled()?'inactive':'').'">';
								echo '<td>';
								if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG('install')."'>&nbsp;";
								else echo "<img src='img/delete.dyn.svg' title='".LANG('uninstall')."'>&nbsp;";
								echo  '<a '.explorerLink('views/computers.php?id='.$j->computer_id).'>'.htmlspecialchars($j->computer_hostname).'</a>';
								echo '</td>';
								if($j instanceof Models\DynamicJob) {
									echo '<td><img src="'.$j->getContainerIcon().'" title="'.LANG('deployment_rule').'">&nbsp;<a '.explorerLink('views/deployment-rules.php?id='.$j->deployment_rule_id).'>'.htmlspecialchars($j->deployment_rule_name).'</a></td>';
								} elseif($j instanceof Models\StaticJob) {
									echo '<td><img src="'.$j->getContainerIcon().'" title="'.LANG('job_container').'">&nbsp;<a '.explorerLink('views/job-containers.php?id='.$j->job_container_id).'>'.htmlspecialchars($j->job_container_name).'</a></td>';
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
									<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>

	</div>
</div>

<?php } ?>
