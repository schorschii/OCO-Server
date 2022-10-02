<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

// ----- prepare view -----
$tab = 'general';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

try {
	$package = $cl->getPackage($_GET['id'] ?? -1);
	$packageFamily = $db->selectPackageFamily($package->package_family_id);
	if($packageFamily === null) throw new NotFoundException();

	$permissionCreate   = $cl->checkPermission(new Models\Package(), PermissionManager::METHOD_CREATE, false) && $cl->checkPermission($packageFamily, PermissionManager::METHOD_CREATE, false);
	$permissionDeploy   = $cl->checkPermission($package, PermissionManager::METHOD_DEPLOY, false);
	$permissionDownload = $cl->checkPermission($package, PermissionManager::METHOD_DOWNLOAD, false);
	$permissionWrite    = $cl->checkPermission($package, PermissionManager::METHOD_WRITE, false);
	$permissionDelete   = $cl->checkPermission($package, PermissionManager::METHOD_DELETE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='<?php echo $package->getIcon(); ?>'><span id='page-title'><?php echo htmlspecialchars($package->getFullName()); ?></span><span id='spnPackageFamilyName' class='rawvalue'><?php echo htmlspecialchars($package->package_family_name); ?></span></h1>
	<div class='controls'>
		<button onclick='refreshContentDeploy({<?php echo $package->id; ?>:obj("page-title").innerText});' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
		<button onclick='window.open("package-download.php?id=<?php echo intval($package->id) ?>","_blank")' <?php if(!$package->getSize() || !$permissionDownload) echo "disabled"; ?>><img src='img/download.dyn.svg'>&nbsp;<?php echo LANG('download'); ?></button>
		<button onclick='showDialogEditPackage(<?php echo $package->id; ?>, <?php echo $package->package_family_id; ?>, spnPackageVersion.innerText, spnPackageCompatibleOs.innerText, spnPackageCompatibleOsVersion.innerText, spnPackageNotes.innerText, spnPackageInstallProcedure.innerText, spnPackageInstallProcedureSuccessReturnCodes.innerText, spnPackageInstallProcedurePostAction.innerText, spnPackageUninstallProcedure.innerText, spnPackageUninstallProcedureSuccessReturnCodes.innerText, spnPackageUninstallProcedurePostAction.innerText, spnPackageDownloadForUninstall.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
		<button onclick='showDialogAddPackageToGroup("<?php echo $package->id; ?>")' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
		<button onclick='currentExplorerContentUrl="views/packages.php?package_family_id="+encodeURIComponent("<?php echo $package->package_family_id; ?>");confirmRemovePackage([<?php echo $package->id; ?>], event, spnPackageFamilyName.innerText+" ("+spnPackageVersion.innerText+")")' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
		<span class='filler'></span>
	</div>
</div>

<div id='tabControlPackage' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='general' class='<?php if($tab=='general') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPackage,this.getAttribute("name"))'><?php echo LANG('general_and_dependencies'); ?></a>
		<a href='#' name='archive-contents' class='<?php if($tab=='archive-contents') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPackage,this.getAttribute("name"))'><?php echo LANG('archive_contents'); ?></a>
		<a href='#' name='computers' class='<?php if($tab=='computers') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPackage,this.getAttribute("name"))'><?php echo LANG('computer_and_jobs'); ?></a>
		<a href='#' name='history' class='<?php if($tab=='history') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPackage,this.getAttribute("name"),true)'><?php echo LANG('history'); ?></a>
	</div>
	<div class='tabcontents'>
		<div name='general' class='<?php if($tab=='general') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('general'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('id'); ?></th>
							<td><?php echo htmlspecialchars($package->id); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('version'); ?></th>
							<td>
								<span id='spnPackageVersion'><?php echo htmlspecialchars($package->version); ?></span>
							</td>
						</tr>
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
							<th><?php echo LANG('zip_archive'); ?></th>
							<td>
								<?php
								$size = $package->getSize();
								if($size) echo niceSize($size, true).', '.niceSize($size, false).' ';
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
						<tr>
							<th><?php echo LANG('assigned_groups'); ?></th>
							<td>
								<?php
								$res = $db->selectAllPackageGroupByPackageId($package->id);
								$i = 0;
								foreach($res as $group) {
									echo "<a class='subbuttons' ".explorerLink('views/packages.php?id='.$group->id).">".wrapInSpanIfNotEmpty($db->getPackageGroupBreadcrumbString($group->id));
									echo "<button onclick='event.stopPropagation();removePackageFromGroup([".$package->id."], ".$group->id.");return false'><img class='small' src='img/folder-remove-from.dyn.svg' title='".LANG('remove_from_group')."'></button>";
									echo "</a>";
									if(++$i != count($res)) { echo "<br>"; }
								}
								?>
							</td>
						</tr>
						<tr>
							<th><?php echo LANG('description'); ?></th>
							<td>
								<span id='spnPackageNotes'><?php echo nl2br(htmlspecialchars($package->notes)); ?></span>
							</td>
						</tr>
					</table>
					<h2><?php echo LANG('installation'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('install_procedure'); ?></th>
							<td>
								<span id='spnPackageInstallProcedure' class='monospace'><?php echo nl2br(htmlspecialchars($package->install_procedure)); ?></span>
							</td>
						</tr>
						<tr>
							<th><?php echo LANG('success_return_codes'); ?></th>
							<td>
								<span id='spnPackageInstallProcedureSuccessReturnCodes'><?php echo htmlspecialchars($package->install_procedure_success_return_codes); ?></span>
							</td>
						</tr>
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
							<th><?php echo LANG('uninstall_procedure'); ?></th>
							<td>
								<span id='spnPackageUninstallProcedure' class='monospace'><?php echo nl2br(htmlspecialchars($package->uninstall_procedure)); ?></span>
							</td>
						</tr>
						<tr>
							<th><?php echo LANG('success_return_codes'); ?></th>
							<td>
								<span id='spnPackageUninstallProcedureSuccessReturnCodes'><?php echo htmlspecialchars($package->uninstall_procedure_success_return_codes); ?></span>
							</td>
						</tr>
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
					<div class='controls'>
						<button onclick='refreshContentPackageNew(spnPackageFamilyName.innerText, spnPackageVersion.innerText, spnPackageNotes.innerText, spnPackageInstallProcedure.innerText, spnPackageInstallProcedureSuccessReturnCodes.innerText, spnPackageInstallProcedurePostAction.innerText, spnPackageUninstallProcedure.innerText, spnPackageUninstallProcedureSuccessReturnCodes.innerText, spnPackageUninstallProcedurePostAction.innerText, spnPackageDownloadForUninstall.innerText, spnPackageCompatibleOs.innerText, spnPackageCompatibleOsVersion.innerText)' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_version'); ?></button>
						<button onclick='refreshContentExplorer("views/packages.php?package_family_id=<?php echo $packageFamily->id; ?>")'><img src='img/list.dyn.svg'>&nbsp;<?php echo LANG('details'); ?></button>
					</div>
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
					<div class='controls'>
						<button onclick='showDialogAddPackageDependency("<?php echo $package->id; ?>")' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
					</div>
					<table id='tblDependencyPackageData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDependencyPackageData, this.checked)'></th>
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
								echo '<td><input type="checkbox" name="dependency_package_id[]" value="'.$dp->id.'" onchange="refreshCheckedCounter(tblDependencyPackageData)"></td>';
								echo '<td><a '.explorerLink('views/packages.php?package_family_id='.$dp->package_family_id).'>'.htmlspecialchars($dp->package_family_name).'</a></td>';
								echo '<td><a '.explorerLink('views/package-details.php?id='.$dp->id).'>'.htmlspecialchars($dp->version).'</a></td>';
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
											<button onclick='removeSelectedPackageDependency("dependency_package_id[]", <?php echo $package->id; ?>)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/remove.dyn.svg'>&nbsp;<?php echo LANG('remove_assignment'); ?></button>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
				<div>
					<h2><?php echo LANG('dependent_packages'); ?></h2>
					<div class='controls'>
						<button onclick='showDialogAddDependentPackage("<?php echo $package->id; ?>")' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
					</div>
					<table id='tblDependentPackageData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDependentPackageData, this.checked)'></th>
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
								echo '<td><input type="checkbox" name="dependent_package_id[]" value="'.$dp->id.'" onchange="refreshCheckedCounter(tblDependentPackageData)"></td>';
								echo '<td><a '.explorerLink('views/packages.php?package_family_id='.$dp->package_family_id).'>'.htmlspecialchars($dp->package_family_name).'</a></td>';
								echo '<td><a '.explorerLink('views/package-details.php?id='.$dp->id).'>'.htmlspecialchars($dp->version).'</a></td>';
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
											<button onclick='removeSelectedDependentPackages("dependent_package_id[]", <?php echo $package->id; ?>)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/remove.dyn.svg'>&nbsp;<?php echo LANG('remove_assignment'); ?></button>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>

		<div name='archive-contents' class='<?php if($tab=='archive-contents') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<?php $contents = $package->getContentListing(); if($contents) { ?>
					<table id='tblArchiveContents' class='list searchable sortable savesort margintop'>
						<thead>
							<tr>
								<th class='searchable sortable'><?php echo LANG('name'); ?></th>
								<th class='searchable sortable'><?php echo LANG('size'); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						$totalSize = 0;
						$counter = 0;
						foreach($contents as $filename => $size) {
							$counter ++;
							$totalSize += $size;
							echo "<tr>";
							echo "<td>".htmlspecialchars($filename)."</td>";
							echo "<td sort_key='".$size."'>".niceSize($size)."</td>";
							echo "</tr>";
						} ?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<span class='counter'><?php echo $counter; ?></span>&nbsp;<?php echo LANG('elements'); ?>;
									<?php echo LANG('total').': '.$counter.' '.LANG('elements').', '.niceSize($totalSize, true).', '.niceSize($totalSize, false); ?>
								</td>
							</tr>
						</tfoot>
					</table>
					<?php } else { ?>
						<div class='alert warning margintop'><?php echo LANG('this_is_an_empty_package_without_archive'); ?></div>
					<?php } ?>
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
								$counter ++;
								echo '<tr>';
								echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" computer_id="'.$p->computer_id.'" onchange="refreshCheckedCounter(tblPackageAssignedComputersData)"></td>';
								echo '<td><a '.explorerLink('views/computer-details.php?id='.$p->computer_id).'>'.htmlspecialchars($p->computer_hostname).'</a></td>';
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
											<button onclick='showDialogAddComputerToGroup(getSelectedCheckBoxValues("package_id[]", "computer_id", true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
											<button onclick='confirmRemovePackageComputerAssignment("package_id[]")' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/remove.dyn.svg'>&nbsp;<?php echo LANG('remove_assignment'); ?></button>
											<button onclick='showDialogUninstall()' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
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
								$counter ++;
								echo '<tr class="'.(!$j->isEnabled()?'inactive':'').'">';
								echo '<td>';
								if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG('install')."'>&nbsp;";
								else echo "<img src='img/delete.dyn.svg' title='".LANG('uninstall')."'>&nbsp;";
								echo  '<a '.explorerLink('views/computer-details.php?id='.$j->computer_id).'>'.htmlspecialchars($j->computer_hostname).'</a>';
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

		<div name='history' class='<?php if($tab=='history') echo 'active'; ?>'>
			<?php if($tab == 'history') { ?>
			<div class='details-abreast'>
				<div class='stickytable'>
					<table id='tblPackageHistoryData' class='list searchable sortable savesort margintop'>
						<thead>
							<tr>
								<th class='searchable sortable'><?php echo LANG('timestamp'); ?></th>
								<th class='searchable sortable'><?php echo LANG('ip_address'); ?></th>
								<th class='searchable sortable'><?php echo LANG('user'); ?></th>
								<th class='searchable sortable'><?php echo LANG('action'); ?></th>
								<th class='searchable sortable'><?php echo LANG('data'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$counter = 0;
							foreach($db->selectAllLogEntryByObjectIdAndActions($package->id, 'oco.package', empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								$counter ++;
								echo "<tr>";
								echo "<td>".htmlspecialchars($l->timestamp)."</td>";
								echo "<td>".htmlspecialchars($l->host)."</td>";
								echo "<td>".htmlspecialchars($l->user)."</td>";
								echo "<td>".htmlspecialchars($l->action)."</td>";
								echo "<td class='subbuttons'>".htmlspecialchars(shorter($l->data, 100))." <button onclick='event.preventDefault();showDialog(\"".htmlspecialchars($l->action,ENT_QUOTES)."\",this.getAttribute(\"data\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' data='".htmlspecialchars(prettyJson($l->data),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
								echo "</tr>";
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
										</div>
										<div class='controls'>
											<button onclick='event.preventDefault();downloadTableCsv("tblSoftwareInventoryData")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
											<?php if(empty($_GET['nolimit'])) { ?>
												<button onclick='rewriteUrlContentParameter(currentExplorerContentUrl, {"nolimit":1});refreshContent()'><img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('show_all'); ?></button>
											<?php } ?>
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

	</div>
</div>
