<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');


if(!empty($_POST['edit_id'])) {
	$package = $db->getPackage($_POST['edit_id']);
	if(empty($package)) {
		header('HTTP/1.1 404 Not Found');
		die(LANG['not_found']);
	}
	$db->updatePackage(
		$package->id,
		$package->name,
		$package->version,
		$package->author,
		$_POST['description'],
		$_POST['install_procedure'],
		$_POST['install_procedure_success_return_codes'],
		$_POST['install_procedure_restart'],
		$_POST['install_procedure_shutdown'],
		$_POST['uninstall_procedure'],
		$_POST['uninstall_procedure_success_return_codes'],
		$_POST['download_for_uninstall'],
		$_POST['uninstall_procedure_restart'],
		$_POST['uninstall_procedure_shutdown']
	);
	die();
}

$package = null;
if(!empty($_GET['id'])) {
	$package = $db->getPackage($_GET['id']);
}
if($package === null) die(LANG['not_found']);
?>

<h1><?php echo htmlspecialchars($package->name); ?></h1>
<div class='controls'>
	<button onclick='refreshContentDeploy([<?php echo $package->id; ?>]);'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
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
		<table class='list form'>
			<tr>
				<th><?php echo LANG['id']; ?></th>
				<td><?php echo htmlspecialchars($package->id); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['name']; ?></th>
				<td><?php echo htmlspecialchars($package->name); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['version']; ?></th>
				<td><?php echo htmlspecialchars($package->version); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['author']; ?></th>
				<td><?php echo htmlspecialchars($package->author); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['install_procedure']; ?></th>
				<td><input type='text' id='txtInstallProcedure' value='<?php echo htmlspecialchars($package->install_procedure); ?>'></td>
			</tr>
			<tr>
				<th><?php echo LANG['success_return_codes']; ?></th>
				<td><input type='text' id='txtInstallProcedureSucessReturnCodes' value='<?php echo htmlspecialchars($package->install_procedure_success_return_codes); ?>'></td>
			</tr>
			<tr>
				<th><?php echo LANG['after_completion']; ?></th>
				<td>
					<label class='inlineblock'><input type='radio' name='install_post_action' id='rdoInstallPostActionNone' <?php if(!$package->install_procedure_restart && !$package->install_procedure_shutdown) echo 'checked'; ?>>&nbsp;<?php echo LANG['no_action']; ?></label>
					<label class='inlineblock'><input type='radio' name='install_post_action' id='rdoInstallPostActionRestart' <?php if($package->install_procedure_restart) echo 'checked'; ?>>&nbsp;<?php echo LANG['restart']; ?></label>
					<label class='inlineblock'><input type='radio' name='install_post_action' id='rdoInstallPostActionShutdown' <?php if($package->install_procedure_shutdown) echo 'checked'; ?>>&nbsp;<?php echo LANG['shutdown']; ?></label>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['uninstall_procedure']; ?></th>
				<td><input type='text' id='txtUninstallProcedure' value='<?php echo htmlspecialchars($package->uninstall_procedure); ?>'></td>
			</tr>
			<tr>
				<th><?php echo LANG['success_return_codes']; ?></th>
				<td><input type='text' id='txtUninstallProcedureSuccessReturnCodes' value='<?php echo htmlspecialchars($package->uninstall_procedure_success_return_codes); ?>'></td>
			</tr>
			<tr>
				<th><?php echo LANG['after_completion']; ?></th>
				<td>
					<label class='inlineblock'><input type='radio' name='uninstall_post_action' id='rdoUninstallPostActionNone' <?php if(!$package->uninstall_procedure_restart && !$package->uninstall_procedure_shutdown) echo 'checked'; ?>>&nbsp;<?php echo LANG['no_action']; ?></label>
					<label class='inlineblock'><input type='radio' name='uninstall_post_action' id='rdoUninstallPostActionRestart' <?php if($package->uninstall_procedure_restart) echo 'checked'; ?>>&nbsp;<?php echo LANG['restart']; ?></label>
					<label class='inlineblock'><input type='radio' name='uninstall_post_action' id='rdoUninstallPostActionShutdown' <?php if($package->uninstall_procedure_shutdown) echo 'checked'; ?>>&nbsp;<?php echo LANG['shutdown']; ?></label>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['download_for_uninstall']; ?></th>
				<td>
					<label class='inlineblock'><input type='checkbox' id='chkDownloadForUninstall' <?php if($package->download_for_uninstall) echo 'checked'; ?>>&nbsp;<?php echo LANG['yes']; ?></label>
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
					$groups = [];
					$i = 0;
					foreach($res as $group) {
						echo "<a href='".explorerLink('views/package.php?id='.$group->id)."' onclick='event.preventDefault();refreshContentPackage(".$group->id.")'>".htmlspecialchars($group->name)."</a>";
						if(++$i != count($res)) { echo ", "; }
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['description']; ?></th>
				<td>
					<textarea id='txtDescription'><?php echo htmlspecialchars($package->notes); ?></textarea>
				</td>
			</tr>
			<tr>
				<th></th>
				<td><button id='btnEditPackage' onclick='updatePackage(<?php echo $package->id; ?>, txtDescription.value, txtInstallProcedure.value, txtInstallProcedureSucessReturnCodes.value, rdoInstallPostActionRestart.checked, rdoInstallPostActionShutdown.checked, txtUninstallProcedure.value, txtUninstallProcedureSuccessReturnCodes.value, chkDownloadForUninstall.checked, rdoUninstallPostActionRestart.checked, rdoUninstallPostActionShutdown.checked)'><img src='img/send.svg'>&nbsp;<?php echo LANG['save']; ?></button></td>
			</tr>
		</table>
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
					echo '<td class="middle"><img src="img/'.$j->getIcon().'.dyn.svg">'.$j->getStateString().'</td>';
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
					echo '<td>'.htmlspecialchars($p->installed_procedure).'</td>';
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
</div>
