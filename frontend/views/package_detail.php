<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');


if(!empty($_POST['update_package_family_id']) && !empty($_POST['update_name'])) {
	$db->updatePackageFamilyName($_POST['update_package_family_id'], $_POST['update_name']);
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
	if($_POST['update_install_procedure_action'] == '0') {
		$db->updatePackageInstallProcedureRestart($_POST['update_package_id'], 0);
		$db->updatePackageInstallProcedureShutdown($_POST['update_package_id'], 0);
	} elseif($_POST['update_install_procedure_action'] == '1') {
		$db->updatePackageInstallProcedureRestart($_POST['update_package_id'], 1);
		$db->updatePackageInstallProcedureShutdown($_POST['update_package_id'], 0);
	} elseif($_POST['update_install_procedure_action'] == '2') {
		$db->updatePackageInstallProcedureRestart($_POST['update_package_id'], 0);
		$db->updatePackageInstallProcedureShutdown($_POST['update_package_id'], 1);
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
	if($_POST['update_uninstall_procedure_action'] == '0') {
		$db->updatePackageUninstallProcedureRestart($_POST['update_package_id'], 0);
		$db->updatePackageUninstallProcedureShutdown($_POST['update_package_id'], 0);
	} elseif($_POST['update_uninstall_procedure_action'] == '1') {
		$db->updatePackageUninstallProcedureRestart($_POST['update_package_id'], 1);
		$db->updatePackageUninstallProcedureShutdown($_POST['update_package_id'], 0);
	} elseif($_POST['update_uninstall_procedure_action'] == '2') {
		$db->updatePackageUninstallProcedureRestart($_POST['update_package_id'], 0);
		$db->updatePackageUninstallProcedureShutdown($_POST['update_package_id'], 1);
	} else {
		header('HTTP/1.1 400 Invalid Value');
	}
	die();
}

$package = null;
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
}
if($package === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
?>

<h1><img src='img/<?php echo $package->getIcon(); ?>.dyn.svg'><?php echo htmlspecialchars($package->name); ?></h1>
<div class='controls'>
	<button onclick='refreshContentDeploy([<?php echo $package->id; ?>]);'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<button onclick='renamePackageFamily(<?php echo $package->package_family_id; ?>, this.getAttribute("oldName"))' oldName='<?php echo htmlspecialchars($package->name,ENT_QUOTES); ?>'><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename']; ?></button>
	<button onclick='addPackageToGroup(<?php echo $package->id; ?>, sltNewPackageGroup.value)'><img src='img/folder-insert-into.svg'>
		&nbsp;<?php echo LANG['add_to']; ?>
		<select id='sltNewPackageGroup' onclick='event.stopPropagation()'>
			<?php echoPackageGroupOptions($db); ?>
		</select>
	</button>
	<button onclick='currentExplorerContentUrl="views/package.php";confirmRemovePackage([<?php echo $package->id; ?>])'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
</div>

<div class="details-abreast">
	<div>
		<h2><?php echo LANG['general']; ?></h2>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG['id']; ?></th>
				<td><?php echo htmlspecialchars($package->id); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['version']; ?></th>
				<td class='subbuttons'>
					<?php echo wrapInSpanIfNotEmpty($package->version); ?><!--
					--><button onclick='event.stopPropagation();editPackageVersion(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='<?php echo htmlspecialchars($package->version,ENT_QUOTES); ?>'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['author']; ?></th>
				<td><?php echo htmlspecialchars($package->author); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['install_procedure']; ?></th>
				<td class='subbuttons'>
					<?php echo wrapInSpanIfNotEmpty($package->install_procedure); ?><!--
					--><button onclick='event.stopPropagation();editPackageInstallProcedure(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='<?php echo htmlspecialchars($package->install_procedure,ENT_QUOTES); ?>'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['success_return_codes']; ?></th>
				<td class='subbuttons'>
					<?php echo wrapInSpanIfNotEmpty($package->install_procedure_success_return_codes); ?><!--
					--><button onclick='event.stopPropagation();editPackageInstallProcedureSuccessReturnCodes(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='<?php echo htmlspecialchars($package->install_procedure_success_return_codes,ENT_QUOTES); ?>'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['after_completion']; ?></th>
				<td class='subbuttons'>
					<?php
					if(!$package->install_procedure_restart && !$package->install_procedure_shutdown) echo LANG['no_action'];
					if($package->install_procedure_restart) echo LANG['restart'];
					if($package->install_procedure_shutdown) echo LANG['shutdown'];
					?><!--
					--><button onclick='event.stopPropagation();editPackageInstallProcedureAction(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='0'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['uninstall_procedure']; ?></th>
				<td class='subbuttons'>
					<?php echo wrapInSpanIfNotEmpty($package->uninstall_procedure); ?><!--
					--><button onclick='event.stopPropagation();editPackageUninstallProcedure(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='<?php echo htmlspecialchars($package->uninstall_procedure,ENT_QUOTES); ?>'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['success_return_codes']; ?></th>
				<td class='subbuttons'>
					<?php echo wrapInSpanIfNotEmpty($package->uninstall_procedure_success_return_codes); ?><!--
					--><button onclick='event.stopPropagation();editPackageUninstallProcedureSuccessReturnCodes(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='<?php echo htmlspecialchars($package->uninstall_procedure_success_return_codes,ENT_QUOTES); ?>'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['after_completion']; ?></th>
				<td class='subbuttons'>
					<?php
					if(!$package->uninstall_procedure_restart && !$package->uninstall_procedure_shutdown) echo LANG['no_action'];
					if($package->uninstall_procedure_restart) echo LANG['restart'];
					if($package->uninstall_procedure_shutdown) echo LANG['shutdown'];
					?><!--
					--><button onclick='event.stopPropagation();editPackageUninstallProcedureAction(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='0'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['download_for_uninstall']; ?></th>
				<td>
					<?php if($package->download_for_uninstall) echo LANG['yes']; else echo LANG['no']; ?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['zip_archive']; ?></th>
				<td>
					<?php
					$size = $package->getSize();
					if($size) {
						?>
						<a href='payloadprovider.php?id=<?php echo intval($package->id) ?>' target='_blank'><?php echo LANG['download']; ?></a>
						(<?php echo niceSize($size, true).', '.niceSize($size, false); ?>)
					<?php } else { ?>
						<?php echo LANG['not_found']; ?>
					<?php } ?>
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
						echo "<a class='subbuttons' href='".explorerLink('views/package.php?id='.$group->id)."' onclick='event.preventDefault();refreshContentPackage(".$group->id.")'>".wrapInSpanIfNotEmpty($db->getPackageGroupBreadcrumbString($group->id));
						echo "<button onclick='event.stopPropagation();removePackageFromGroup([".$package->id."], ".$group->id.");return false'><img class='small' src='img/folder-remove-from.svg' title='".LANG['remove_from_group']."'></button>";
						echo "</a>";
						if(++$i != count($res)) { echo "<br>"; }
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['description']; ?></th>
				<td class='subbuttons'>
					<?php echo wrapInSpanIfNotEmpty($package->notes); ?><!--
					--><button onclick='event.stopPropagation();editPackageNotes(<?php echo $package->id; ?>, this.getAttribute("oldValue"));return false' oldValue='<?php echo htmlspecialchars($package->notes,ENT_QUOTES); ?>'><img class='small' src='img/edit.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
		</table>
	</div>

	<div>
		<h2><?php echo LANG['other_packages_from_this_family']; ?></h2>
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
					echo '<td><a href="'.explorerLink('views/package_detail.php?id='.$p->id).'" onclick="event.preventDefault();refreshContentPackageDetail('.$p->id.')">'.htmlspecialchars($p->version).'</a></td>';
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

<div class="details-abreast">
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
					echo '<td><a href="'.explorerLink('views/computer_detail.php?id='.$p->computer_id).'" onclick="event.preventDefault();refreshContentComputerDetail('.$p->computer_id.')">'.htmlspecialchars($p->computer_hostname).'</a></td>';
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
			<button onclick='confirmUninstallPackage("package_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['uninstall_package']; ?></button>
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
					echo '<td><a href="'.explorerLink('views/computer_detail.php?id='.$j->computer_id).'" onclick="event.preventDefault();refreshContentComputerDetail('.$j->computer_id.')">'.htmlspecialchars($j->computer_hostname).'</a></td>';
					echo '<td><a href="'.explorerLink('views/job_container.php?id='.$j->job_container_id).'" onclick="event.preventDefault();refreshContentJobContainer('.$j->job_container_id.')">'.htmlspecialchars($j->job_container_name).'</a></td>';
					echo '<td class="middle"><img src="img/'.$j->getIcon().'.dyn.svg">&nbsp;'.$j->getStateString().'</td>';
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
