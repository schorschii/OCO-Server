<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');


if(!empty($_POST['update_package_family_id']) && !empty($_POST['update_name'])) {
	$db->updatePackageFamilyName($_POST['update_package_family_id'], $_POST['update_name']);
	die();
}
if(!empty($_POST['update_package_family_id']) && !empty($_POST['update_notes'])) {
	$db->updatePackageFamilyNotes($_POST['update_package_family_id'], $_POST['update_notes']);
	die();
}
if(!empty($_POST['update_package_family_id']) && !empty($_FILES['update_icon']['tmp_name'])) {
	if(!exif_imagetype($_FILES['update_icon']['tmp_name'])) {
		header('HTTP/1.1 400 Invalid Value');
		die();
	}
	$db->updatePackageFamilyIcon($_POST['update_package_family_id'], file_get_contents($_FILES['update_icon']['tmp_name']));
	die();
}
if(!empty($_POST['update_package_family_id']) && !empty($_POST['remove_icon'])) {
	$db->updatePackageFamilyIcon($_POST['update_package_family_id'], null);
	die();
}
if(!empty($_POST['update_package_id']) && !empty($_POST['add_dependency_package_id'])) {
	$db->addPackageDependency($_POST['update_package_id'], $_POST['add_dependency_package_id']);
	die();
}
if(!empty($_POST['update_package_id']) && !empty($_POST['remove_dependency_package_id']) && is_array($_POST['remove_dependency_package_id'])) {
	foreach($_POST['remove_dependency_package_id'] as $dpid) {
		$db->removePackageDependency($_POST['update_package_id'], $dpid);
	}
	die();
}
if(!empty($_POST['update_package_id']) && !empty($_POST['remove_dependent_package_id']) && is_array($_POST['remove_dependent_package_id'])) {
	foreach($_POST['remove_dependent_package_id'] as $dpid) {
		$db->removePackageDependency($dpid, $_POST['update_package_id']);
	}
	die();
}
if(!empty($_POST['update_package_id']) && !empty($_POST['update_version'])) {
	$db->updatePackageVersion($_POST['update_package_id'], $_POST['update_version']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_note'])) {
	$db->updatePackageNote($_POST['update_package_id'], $_POST['update_note']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure'])) {
	$db->updatePackageInstallProcedure($_POST['update_package_id'], $_POST['update_install_procedure']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure_success_return_codes'])) {
	$db->updatePackageInstallProcedureSuccessReturnCodes($_POST['update_package_id'], $_POST['update_install_procedure_success_return_codes']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_install_procedure_action'])) {
	if(is_numeric($_POST['update_install_procedure_action'])
	&& in_array($_POST['update_install_procedure_action'], [Package::POST_ACTION_NONE, Package::POST_ACTION_RESTART, Package::POST_ACTION_SHUTDOWN, Package::POST_ACTION_EXIT])) {
		$db->updatePackageInstallProcedurePostAction($_POST['update_package_id'], $_POST['update_install_procedure_action']);
	} else {
		header('HTTP/1.1 400 Invalid Value');
	}
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure'])) {
	$db->updatePackageUninstallProcedure($_POST['update_package_id'], $_POST['update_uninstall_procedure']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure_success_return_codes'])) {
	$db->updatePackageUninstallProcedureSuccessReturnCodes($_POST['update_package_id'], $_POST['update_uninstall_procedure_success_return_codes']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_uninstall_procedure_action'])) {
	if(is_numeric($_POST['update_uninstall_procedure_action'])
	&& in_array($_POST['update_uninstall_procedure_action'], [Package::POST_ACTION_NONE, Package::POST_ACTION_RESTART, Package::POST_ACTION_SHUTDOWN])) {
		$db->updatePackageUninstallProcedurePostAction($_POST['update_package_id'], $_POST['update_uninstall_procedure_action']);
	} else {
		header('HTTP/1.1 400 Invalid Value');
	}
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_download_for_uninstall'])) {
	if($_POST['update_download_for_uninstall'] == '0') {
		$db->updatePackageDownloadForUninstall($_POST['update_package_id'], 0);
	} elseif($_POST['update_download_for_uninstall'] == '1') {
		$db->updatePackageDownloadForUninstall($_POST['update_package_id'], 1);
	} else {
		header('HTTP/1.1 400 Invalid Value');
	}
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_compatible_os'])) {
	$db->updatePackageCompatibleOs($_POST['update_package_id'], $_POST['update_compatible_os']);
	die();
}
if(!empty($_POST['update_package_id']) && isset($_POST['update_compatible_os_version'])) {
	$db->updatePackageCompatibleOsVersion($_POST['update_package_id'], $_POST['update_compatible_os_version']);
	die();
}

$package = null;
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
}
if($package === null) die("<div class='alert warning'>".LANG['not_found']."</div>");

$packageFamily = $db->getPackageFamily($package->package_family_id);
?>

<div class='details-header'>
	<h1><img src='<?php echo $package->getIcon(); ?>'><span id='page-title'><?php echo htmlspecialchars($package->package_family_name)." (".htmlspecialchars($package->version).")"; ?></span><span id='spnPackageFamilyName' class='rawvalue'><?php echo htmlspecialchars($package->package_family_name); ?></span></h1>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['general']; ?></h2>
		<div class='controls'>
			<button onclick='refreshContentDeploy([<?php echo $package->id; ?>]);'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
			<button onclick='window.open("payloadprovider.php?id=<?php echo intval($package->id) ?>","_blank")' <?php if(!$package->getSize()) echo "disabled"; ?>><img src='img/download.svg'>&nbsp;<?php echo LANG['download']; ?></button>
			<button onclick='addPackageToGroup(<?php echo $package->id; ?>, sltNewPackageGroup.value)'><img src='img/folder-insert-into.svg'>
				&nbsp;<?php echo LANG['add_to']; ?>
				<select id='sltNewPackageGroup' onclick='event.stopPropagation()'>
					<?php echoPackageGroupOptions($db); ?>
				</select>
			</button>
			<button onclick='currentExplorerContentUrl="views/packages.php?package_family_id="+encodeURIComponent("<?php echo $package->package_family_id; ?>");confirmRemovePackage([<?php echo $package->id; ?>], event)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG['id']; ?></th>
				<td><?php echo htmlspecialchars($package->id); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['version']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageVersion'><?php echo htmlspecialchars($package->version); ?></span>
					<button onclick='event.stopPropagation();editPackageVersion(<?php echo $package->id; ?>, spnPackageVersion.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['author']; ?></th>
				<td><?php echo htmlspecialchars($package->author); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['install_procedure']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageInstallProcedure'><?php echo htmlspecialchars($package->install_procedure); ?></span>
					<button onclick='event.stopPropagation();editPackageInstallProcedure(<?php echo $package->id; ?>, spnPackageInstallProcedure.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['success_return_codes']; ?></th>
				<td class='subbuttons'>
				<span id='spnPackageInstallProcedureSuccessReturnCodes'><?php echo htmlspecialchars($package->install_procedure_success_return_codes); ?></span>
					<button onclick='event.stopPropagation();editPackageInstallProcedureSuccessReturnCodes(<?php echo $package->id; ?>, spnPackageInstallProcedureSuccessReturnCodes.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['after_completion']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageInstallProcedurePostAction' class='rawvalue'><?php echo htmlspecialchars($package->install_procedure_post_action); ?></span>
					<?php $info = '';
					switch($package->install_procedure_post_action) {
						case Package::POST_ACTION_RESTART: $info = LANG['restart']; break;
						case Package::POST_ACTION_SHUTDOWN: $info = LANG['shutdown']; break;
						case Package::POST_ACTION_EXIT: $info = LANG['restart_agent']; break;
						default: $info = LANG['no_action']; break;
					}
					echo htmlspecialchars($info);
					?>
					<button onclick='event.stopPropagation();editPackageInstallProcedureAction(<?php echo $package->id; ?>, spnPackageInstallProcedurePostAction.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['uninstall_procedure']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageUninstallProcedure'><?php echo htmlspecialchars($package->uninstall_procedure); ?></span>
					<button onclick='event.stopPropagation();editPackageUninstallProcedure(<?php echo $package->id; ?>, spnPackageUninstallProcedure.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['success_return_codes']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageUninstallProcedureSuccessReturnCodes'><?php echo htmlspecialchars($package->uninstall_procedure_success_return_codes); ?></span>
					<button onclick='event.stopPropagation();editPackageUninstallProcedureSuccessReturnCodes(<?php echo $package->id; ?>, spnPackageUninstallProcedureSuccessReturnCodes.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['after_completion']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageUninstallProcedurePostAction' class='rawvalue'><?php echo htmlspecialchars($package->uninstall_procedure_post_action); ?></span>
					<?php $info = '';
					switch($package->uninstall_procedure_post_action) {
						case Package::POST_ACTION_RESTART: $info = LANG['restart']; break;
						case Package::POST_ACTION_SHUTDOWN: $info = LANG['shutdown']; break;
						default: $info = LANG['no_action']; break;
					}
					echo htmlspecialchars($info);
					?>
					<button onclick='event.stopPropagation();editPackageUninstallProcedureAction(<?php echo $package->id; ?>, spnPackageUninstallProcedurePostAction.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['download_for_uninstall']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageDownloadForUninstall' class='rawvalue'><?php echo htmlspecialchars($package->download_for_uninstall); ?></span>
					<?php $info = ''; if($package->download_for_uninstall) $info = LANG['yes']; else $info = LANG['no']; echo htmlspecialchars($info); ?>
					<button onclick='event.stopPropagation();editPackageDownloadForUninstall(<?php echo $package->id; ?>, spnPackageDownloadForUninstall.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['compatible_os']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageCompatibleOs'><?php echo htmlspecialchars($package->compatible_os); ?></span>
					<button onclick='event.stopPropagation();editPackageCompatibleOs(<?php echo $package->id; ?>, spnPackageCompatibleOs.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['compatible_os_version']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageCompatibleOsVersion'><?php echo htmlspecialchars($package->compatible_os_version); ?></span>
					<button onclick='event.stopPropagation();editPackageCompatibleOsVersion(<?php echo $package->id; ?>, spnPackageCompatibleOsVersion.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['zip_archive']; ?></th>
				<td>
					<span id='spnArchiveContents' class='rawvalue'><?php echo $package->getContentListing(); ?></span>
					<?php
					$size = $package->getSize();
					if($size) {
						echo niceSize($size, true).', '.niceSize($size, false).' ';
						echo "(<a href='#' onclick='event.preventDefault();showDialog(\"".LANG['show_contents']."\",spnArchiveContents.innerText,DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)'>".LANG['show_contents']."</a>)";
					} else echo LANG['not_found'];
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['created']; ?></th>
				<td><?php echo htmlspecialchars($package->created); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['last_updated']; ?></th>
				<td><?php echo htmlspecialchars($package->last_update); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['assigned_groups']; ?></th>
				<td>
					<?php
					$res = $db->getGroupByPackage($package->id);
					$i = 0;
					foreach($res as $group) {
						echo "<a class='subbuttons' ".explorerLink('views/packages.php?id='.$group->id).">".wrapInSpanIfNotEmpty($db->getPackageGroupBreadcrumbString($group->id));
						echo "<button onclick='event.stopPropagation();removePackageFromGroup([".$package->id."], ".$group->id.");return false'><img class='small' src='img/folder-remove-from.dyn.svg' title='".LANG['remove_from_group']."'></button>";
						echo "</a>";
						if(++$i != count($res)) { echo "<br>"; }
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['description']; ?></th>
				<td class='subbuttons'>
					<span id='spnPackageDescription'><?php echo htmlspecialchars($package->notes); ?></span>
					<button onclick='event.stopPropagation();editPackageNotes(<?php echo $package->id; ?>, spnPackageDescription.innerText)'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
		</table>
	</div>

	<div>
		<h2><?php echo LANG['other_packages_from_this_family']; ?></h2>
		<div class='controls'>
			<button onclick='refreshContentPackageNew(spnPackageFamilyName.innerText, spnPackageVersion.innerText, spnPackageDescription.innerText, spnPackageInstallProcedure.innerText, spnPackageInstallProcedureSuccessReturnCodes.innerText, spnPackageInstallProcedurePostAction.innerText, spnPackageUninstallProcedure.innerText, spnPackageUninstallProcedureSuccessReturnCodes.innerText, spnPackageUninstallProcedurePostAction.innerText, spnPackageDownloadForUninstall.innerText, spnPackageCompatibleOs.innerText, spnPackageCompatibleOsVersion.innerText)'><img src='img/add.svg'>&nbsp;<?php echo LANG['new_version']; ?></button>
			<button onclick='refreshContentExplorer("views/packages.php?package_family_id=<?php echo $packageFamily->id; ?>")'><img src='img/list.svg'>&nbsp;<?php echo LANG['details']; ?></button>
		</div>
		<input type='file' id='fleIcon' style='display:none' onchange='editPackageFamilyIcon(<?php echo $package->package_family_id; ?>, this.files[0])'></input>
		<?php if(!empty($packageFamily->notes)) echo "<p class='quote'>".nl2br(htmlspecialchars($packageFamily->notes))."</p>"; ?>
		<table id='tblOtherPackagesData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG['version']; ?></th>
					<th class='searchable sortable'><?php echo LANG['size']; ?></th>
					<th class='searchable sortable'><?php echo LANG['created']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getPackageByFamily($package->package_family_id) as $p) {
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
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['depends_on']; ?></h2>
		<div class='controls'>
			<button class='fullwidth' onclick='addPackageDependency(<?php echo $package->id; ?>, sltNewPackageDependency.value)'><img src='img/add.svg'>
				&nbsp;<?php echo LANG['add']; ?>
				<select id='sltNewPackageDependency' onclick='event.stopPropagation()'>
					<?php foreach($db->getAllPackage() as $p) { ?>
						<option value='<?php echo $p->id; ?>'><?php echo htmlspecialchars($p->name.' ('.$p->version.')'); ?></option>
					<?php } ?>
				</select>
			</button>
		</div>
		<table id='tblDependencyPackageData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDependencyPackageData, this.checked)'></th>
					<th class='sortable'><?php echo LANG['name']; ?></th>
					<th class='sortable'><?php echo LANG['version']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getDependentPackages($package->id) as $dp) {
					$counter ++;
					echo '<tr>';
					echo '<td><input type="checkbox" name="dependency_package_id[]" value="'.$dp->id.'" onchange="refreshCheckedCounter(tblDependencyPackageData)"></td>';
					echo '<td><a '.explorerLink('views/packages.php?package_family_id='.$dp->package_family_id).'>'.htmlspecialchars($dp->name).'</a></td>';
					echo '<td><a '.explorerLink('views/package-details.php?id='.$dp->id).'>'.htmlspecialchars($dp->version).'</a></td>';
					echo '</tr>';
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
						<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
					</td>
				</tr>
			</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='removeSelectedPackageDependency("dependency_package_id[]", <?php echo $package->id; ?>)'><img src='img/remove.svg'>&nbsp;<?php echo LANG['remove_assignment']; ?></button>
		</div>
	</div>

	<div>
		<h2><?php echo LANG['dependent_packages']; ?></h2>
		<div class='controls'>
			<button class='fullwidth' onclick='addPackageDependency(sltNewDependentPackage.value, <?php echo $package->id; ?>)'><img src='img/add.svg'>
				&nbsp;<?php echo LANG['add']; ?>
				<select id='sltNewDependentPackage' onclick='event.stopPropagation()'>
				<?php foreach($db->getAllPackage() as $p) { ?>
						<option value='<?php echo $p->id; ?>'><?php echo htmlspecialchars($p->name.' ('.$p->version.')'); ?></option>
					<?php } ?>
				</select>
			</button>
		</div>
		<table id='tblDependentPackageData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDependentPackageData, this.checked)'></th>
					<th class='sortable'><?php echo LANG['name']; ?></th>
					<th class='sortable'><?php echo LANG['version']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getDependentForPackages($package->id) as $dp) {
					$counter ++;
					echo '<tr>';
					echo '<td><input type="checkbox" name="dependent_package_id[]" value="'.$dp->id.'" onchange="refreshCheckedCounter(tblDependentPackageData)"></td>';
					echo '<td><a '.explorerLink('views/packages.php?package_family_id='.$dp->package_family_id).'>'.htmlspecialchars($dp->name).'</a></td>';
					echo '<td><a '.explorerLink('views/package-details.php?id='.$dp->id).'>'.htmlspecialchars($dp->version).'</a></td>';
					echo '</tr>';
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
						<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
					</td>
				</tr>
			</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='removeSelectedDependentPackages("dependent_package_id[]", <?php echo $package->id; ?>)'><img src='img/remove.svg'>&nbsp;<?php echo LANG['remove_assignment']; ?></button>
		</div>
	</div>
</div>

<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['installed_on']; ?></h2>
		<table id='tblPackageAssignedComputersData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPackageAssignedComputersData, this.checked)'></th>
					<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
					<th class='searchable sortable'><?php echo LANG['procedure']; ?></th>
					<th class='searchable sortable'><?php echo LANG['installation_date']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getPackageComputer($package->id) as $p) {
					$counter ++;
					echo '<tr>';
					echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" computer_id="'.$p->computer_id.'" onchange="refreshCheckedCounter(tblPackageAssignedComputersData)"></td>';
					echo '<td><a '.explorerLink('views/computer-details.php?id='.$p->computer_id).'>'.htmlspecialchars($p->computer_hostname).'</a></td>';
					echo '<td title="'.htmlspecialchars($p->installed_procedure, ENT_QUOTES).'">'.htmlspecialchars(shorter($p->installed_procedure)).'</td>';
					echo '<td>'.htmlspecialchars($p->installed).'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
						<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>
					</td>
				</tr>
			</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='deploySelectedComputer("package_id[]", "computer_id");'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
			<button onclick='addSelectedComputerToGroup("package_id[]", sltNewComputerGroup.value, "computer_id")'><img src='img/folder-insert-into.svg'>
				&nbsp;<?php echo LANG['add_to']; ?>
				<select id='sltNewComputerGroup' onclick='event.stopPropagation()'>
					<?php echoComputerGroupOptions($db); ?>
				</select>
			</button>
			<button onclick='confirmRemovePackageComputerAssignment("package_id[]")'><img src='img/remove.svg'>&nbsp;<?php echo LANG['remove_assignment']; ?></button>
			<button onclick='confirmUninstallPackage("package_id[]", "<?php echo date('Y-m-d H:i:s'); ?>")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['uninstall_package']; ?></button>
		</div>
	</div>

	<div>
		<h2><?php echo LANG['pending_jobs']; ?></h2>
		<table id='tblPendingPackageJobsData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<!--<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPendingPackageJobsData, this.checked)'></th>-->
					<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
					<th class='searchable sortable'><?php echo LANG['job_container']; ?></th>
					<th class='searchable sortable'><?php echo LANG['status']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getPendingJobsForPackageDetailPage($package->id) as $j) {
					$counter ++;
					echo '<tr>';
					//echo '<td><input type="checkbox" name="job_id[]" value="'.$j->id.'" onchange="refreshCheckedCounter(tblPendingPackageJobsData)"></td>';
					echo '<td>';
					if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG['install']."'>&nbsp;";
					else echo "<img src='img/delete.dyn.svg' title='".LANG['uninstall']."'>&nbsp;";
					echo  '<a '.explorerLink('views/computer-details.php?id='.$j->computer_id).'>'.htmlspecialchars($j->computer_hostname).'</a>';
					echo '</td>';
					echo '<td><a '.explorerLink('views/job-containers.php?id='.$j->job_container_id).'>'.htmlspecialchars($j->job_container_name).'</a></td>';
					echo '<td class="middle"><img src="'.$j->getIcon().'">&nbsp;'.$j->getStateString().'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
