<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

function echoCommandButton($c, $target, $link=false) {
	if(empty($c) || !isset($c['command']) || !isset($c['name'])) return;
	$actionUrl = str_replace('$$TARGET$$', $target, $c['command']);
	$description = $c['description'];
	if(array_key_exists($c['description'], LANG)) $description = LANG[$c['description']];
	if($c['new_tab']) {
		if($link) {
			echo "<a title='".htmlspecialchars($description)."' href='".htmlspecialchars($actionUrl)."' target='_blank'>";
			echo htmlspecialchars($c['name']);
			echo "</a>";
		} else {
			echo "<button title='".htmlspecialchars($description)."' onclick='window.open(\"".htmlspecialchars($actionUrl)."\")'>";
			if(!empty($c['icon'])) echo "<img src='".$c['icon']."'>&nbsp;"; echo htmlspecialchars($c['name']);
			echo "</button>";
		}
	} else {
		if($link) {
			echo "<a title='".htmlspecialchars($description)."' href='".htmlspecialchars($actionUrl)."'>";
			echo htmlspecialchars($c['name']);
			echo "</a>";
		} else {
			echo "<button title='".htmlspecialchars($description)."' onclick='window.location=\"".htmlspecialchars($actionUrl)."\"'>";
			if(!empty($c['icon'])) echo "<img src='".$c['icon']."'>&nbsp;"; echo htmlspecialchars($c['name']);
			echo "</button>";
		}
	}
}

// ----- prepare view -----
$computer = null;
if(!empty($_GET['id']))
	$computer = $db->getComputer($_GET['id']);

if($computer === null) die("<div class='alert warning'>".LANG['not_found']."</div>");

$online = $computer->isOnline();
?>

<div class='details-header'>
	<h1><img src='<?php echo $computer->getIcon(); ?>' class='<?php echo($online ? 'online' : 'offline'); ?>' title='<?php echo($online ? LANG['online'] : LANG['offline']); ?>'><span id='page-title'><span id='spnComputerName'><?php echo htmlspecialchars($computer->hostname); ?></span></span></h1>
	<div class='controls'>
		<button onclick='refreshContentDeploy([],[],[<?php echo $computer->id; ?>]);'><img src='img/deploy.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
		<button onclick='confirmWolComputer([<?php echo $computer->id; ?>])'><img src='img/wol.svg'>&nbsp;<?php echo LANG['wol']; ?></button>
		<button onclick='renameComputer(<?php echo $computer->id; ?>, spnComputerName.innerText)'><img src='img/edit.svg'>&nbsp;<?php echo LANG['rename']; ?></button>
		<button onclick='addComputerToGroup(<?php echo $computer->id; ?>, sltNewGroup.value)'><img src='img/folder-insert-into.svg'>
			&nbsp;<?php echo LANG['add_to']; ?>
			<select id='sltNewGroup' onclick='event.stopPropagation()'>
				<?php echoComputerGroupOptions($db); ?>
			</select>
		</button>
		<button onclick='currentExplorerContentUrl="views/computers.php";confirmRemoveComputer([<?php echo $computer->id; ?>], event, spnComputerName.innerText)'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		<?php
		if(count(COMPUTER_COMMANDS) > 0) echo "<span class='vl'></span>";
		foreach(COMPUTER_COMMANDS as $c) {
			echoCommandButton($c, $computer->hostname);
		}
		?>
	</div>
</div>

<div class="details-abreast">
	<div>
		<h2><?php echo LANG['general']; ?></h2>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG['id']; ?></th>
				<td><?php echo htmlspecialchars($computer->id); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['os']; ?></th>
				<td><?php echo htmlspecialchars($computer->os); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['version']; ?></th>
				<td><?php echo htmlspecialchars($computer->os_version); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['license']; ?></th>
				<td><?php if($computer->os_license=='1') echo LANG['activated']; elseif($computer->os_license=='0') echo LANG['not_activated']; else echo htmlspecialchars($computer->os_license); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['locale']; ?></th>
				<td><?php echo htmlspecialchars(getLocaleNameByLcid($computer->os_locale)); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['kernel_version']; ?></th>
				<td><?php echo htmlspecialchars($computer->kernel_version); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['architecture']; ?></th>
				<td><?php echo htmlspecialchars($computer->architecture); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['cpu']; ?></th>
				<td><?php echo htmlspecialchars($computer->cpu); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['ram']; ?></th>
				<td><?php echo niceSize($computer->ram, true, 0); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['serial_no']; ?></th>
				<td><?php echo htmlspecialchars($computer->serial); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['vendor']; ?></th>
				<td><?php echo htmlspecialchars($computer->manufacturer); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['model']; ?></th>
				<td><?php echo htmlspecialchars($computer->model); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['bios_version']; ?></th>
				<td><?php echo htmlspecialchars($computer->bios_version); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['boot_type']; ?></th>
				<td><?php echo htmlspecialchars($computer->boot_type); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['secure_boot']; ?></th>
				<td><?php if($computer->secure_boot=='1') echo LANG['yes']; elseif($computer->secure_boot=='0') echo LANG['no']; else echo htmlspecialchars($computer->secure_boot); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['domain']; ?></th>
				<td><?php echo htmlspecialchars($computer->domain); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['agent_version']; ?></th>
				<td><?php echo htmlspecialchars($computer->agent_version); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['remote_address']; ?></th>
				<td><?php echo htmlspecialchars($computer->remote_address); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['uptime']; ?></th>
				<td><?php if(!empty($computer->uptime)) echo niceTime($computer->uptime); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['created']; ?></th>
				<td><?php echo htmlspecialchars($computer->created); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['last_seen']; ?></th>
				<td><?php echo htmlspecialchars($computer->last_ping); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG['last_updated']; ?></th>
				<td class='subbuttons'>
					<?php echo htmlspecialchars($computer->last_update.($computer->force_update ? ' ('.LANG['force_update'].')' : '')); ?>
					<button onclick='event.stopPropagation();setComputerForceUpdate(<?php echo $computer->id; ?>, 1);return false'><img class='small' src='img/force-update.dyn.svg' title='<?php echo LANG['force_update']; ?>'></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['assigned_groups']; ?></th>
				<td>
					<?php
					$res = $db->getGroupByComputer($computer->id);
					$i = 0;
					foreach($res as $group) {
						echo "<a class='subbuttons' ".explorerLink('views/computers.php?id='.$group->id).">".wrapInSpanIfNotEmpty($db->getComputerGroupBreadcrumbString($group->id));
						echo "<button onclick='event.stopPropagation();removeComputerFromGroup([".$computer->id."], ".$group->id.");return false'><img class='small' src='img/folder-remove-from.dyn.svg' title='".LANG['remove_from_group']."'></button>";
						echo "</a>";
						if(++$i != count($res)) { echo "<br>"; }
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG['notes']; ?></th>
				<td class='subbuttons'>
					<span id='spnComputerNotes'><?php echo htmlspecialchars($computer->notes); ?></span>
					<button onclick='event.stopPropagation();editComputerNotes(<?php echo $computer->id; ?>, spnComputerNotes.innerText);return false'><img class='small' src='img/edit.dyn.svg' title='<?php echo LANG['edit']; ?>'></button>
				</td>
			</tr>
		</table>
	</div>

	<div>
		<h2><?php echo LANG['logins']; ?></h2>
		<table id='tblLoginsData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><?php echo LANG['login_name']; ?></th>
					<th><?php echo LANG['count']; ?></th>
					<th><?php echo LANG['last_login']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->getDomainuserLogonByComputer($computer->id) as $logon) {
					echo "<tr>";
					echo "<td><a ".explorerLink('views/domain-users.php?id='.$logon->domainuser_id).">".htmlspecialchars($logon->domainuser_username)."</a></td>";
					echo "<td>".htmlspecialchars($logon->logon_amount)."</td>";
					echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
					echo "</tr>";
				}
				?>
			</tbody>
		</table>
	</div>
</div>

<div class="details-abreast">
	<div>
		<h2><?php echo LANG['network']; ?></h2>
		<table id='tblNetworkData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><?php echo LANG['ip_address']; ?></th>
					<th><?php echo LANG['netmask']; ?></th>
					<th><?php echo LANG['broadcast']; ?></th>
					<th><?php echo LANG['mac_address']; ?></th>
					<th><?php echo LANG['interface']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->getComputerNetwork($computer->id) as $n) {
					echo '<tr>';
					echo '<td class="subbuttons">';
					echo  htmlspecialchars($n->addr);
					if(count(COMPUTER_COMMANDS) > 0) {
						echo '<div class="subbutton small">';
						foreach(COMPUTER_COMMANDS as $c) { echoCommandButton($c, $n->addr, true); echo ' '; }
						echo '</div>';
					}
					echo '</td>';
					echo '<td>'.htmlspecialchars($n->netmask).'</td>';
					echo '<td>'.htmlspecialchars($n->broadcast).'</td>';
					echo '<td>'.htmlspecialchars($n->mac).'</td>';
					echo '<td>'.htmlspecialchars($n->interface).'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>

	<div>
		<h2><?php echo LANG['screens']; ?></h2>
		<table id='tblScreensData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><?php echo LANG['name']; ?></th>
					<th><?php echo LANG['vendor']; ?></th>
					<th><?php echo LANG['type']; ?></th>
					<th><?php echo LANG['resolution']; ?></th>
					<th><?php echo LANG['size']; ?></th>
					<th><?php echo LANG['manufactured']; ?></th>
					<th><?php echo LANG['serial_no']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->getComputerScreen($computer->id) as $s) {
					echo '<tr>';
					echo '<td>'.htmlspecialchars($s->name).'</a></td>';
					echo '<td>'.htmlspecialchars($s->manufacturer).'</td>';
					echo '<td>'.htmlspecialchars($s->type).'</td>';
					echo '<td>'.htmlspecialchars($s->resolution).'</td>';
					echo '<td>'.htmlspecialchars($s->size).'</td>';
					echo '<td>'.htmlspecialchars($s->manufactured).'</td>';
					echo '<td>'.htmlspecialchars($s->serialno).'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
</div>

<div class="details-abreast">
	<div>
		<h2><?php echo LANG['printers']; ?></h2>
		<table id='tblPrinterData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><?php echo LANG['name']; ?></th>
					<th><?php echo LANG['driver']; ?></th>
					<th><?php echo LANG['address']; ?></th>
					<th><?php echo LANG['status']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->getComputerPrinter($computer->id) as $p) {
					echo '<tr>';
					echo '<td>'.htmlspecialchars($p->name).'</a></td>';
					echo '<td>'.htmlspecialchars($p->driver).'</td>';
					echo '<td>'.htmlspecialchars($p->uri).'</td>';
					echo '<td>'.htmlspecialchars($p->status).'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>

	<div>
		<h2><?php echo LANG['file_systems']; ?></h2>
		<table id='tblFileSystemsData' class='list sortable savesort'>
			<thead>
				<tr>
					<th><?php echo LANG['device']; ?></th>
					<th><?php echo LANG['mountpoint']; ?></th>
					<th><?php echo LANG['file_system']; ?></th>
					<th><?php echo LANG['size']; ?></th>
					<th><?php echo LANG['free']; ?></th>
					<th><?php echo LANG['used']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->getComputerPartition($computer->id) as $p) {
					$percent = 0;
					if(!empty($p->free) && !empty($p->size))
					$percent = round(100 - ($p->free / $p->size * 100));
					echo '<tr>';
					echo '<td>'.htmlspecialchars($p->device).'</a></td>';
					echo '<td>'.htmlspecialchars($p->mountpoint).'</td>';
					echo '<td>'.htmlspecialchars($p->filesystem).'</td>';
					echo '<td sort_key="'.htmlspecialchars($p->size).'">'.htmlspecialchars(niceSize($p->size)).'</td>';
					echo '<td sort_key="'.htmlspecialchars($p->free).'">'.htmlspecialchars(niceSize($p->free)).'</td>';
					echo '<td sort_key="'.htmlspecialchars($percent).'">'.progressBar($percent, null, null, null, null, true).'</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
</div>

<div class="details-abreast">
	<div>
		<h2><?php echo LANG['installed_packages']; ?></h2>
		<table id='tblInstalledPackageData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblInstalledPackageData, this.checked)'></th>
					<th class='searchable sortable'><?php echo LANG['package']; ?></th>
					<th class='searchable sortable'><?php echo LANG['procedure']; ?></th>
					<th class='searchable sortable'><?php echo LANG['installation_date']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getComputerPackage($computer->id) as $p) {
					$counter ++;
					echo '<tr>';
					echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" onchange="refreshCheckedCounter(tblInstalledPackageData)"></td>';
					echo '<td><a '.explorerLink('views/package-details.php?id='.$p->package_id).'>'.htmlspecialchars($p->package_family_name).' ('.htmlspecialchars($p->package_version).')</a></td>';
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
			<button onclick='confirmRemovePackageComputerAssignment("package_id[]")'><img src='img/remove.svg'>&nbsp;<?php echo LANG['remove_assignment']; ?></button>
			<button onclick='confirmUninstallPackage("package_id[]", "<?php echo date('Y-m-d H:i:s'); ?>")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['uninstall_package']; ?></button>
		</div>
	</div>

	<div>
		<h2><?php echo LANG['pending_jobs']; ?></h2>
		<table id='tblPendingComputerJobsData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<!--<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblPendingComputerJobsData, this.checked)'></th>-->
					<th class='searchable sortable'><?php echo LANG['package']; ?></th>
					<th class='searchable sortable'><?php echo LANG['job_container']; ?></th>
					<th class='searchable sortable'><?php echo LANG['status']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getPendingJobsForComputerDetailPage($computer->id) as $j) {
					$counter ++;
					echo '<tr>';
					//echo '<td><input type="checkbox" name="job_id[]" value="'.$j->id.'" onchange="refreshCheckedCounter(tblPendingComputerJobsData)"></td>';
					echo '<td>';
					if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG['install']."'>&nbsp;";
					else echo "<img src='img/delete.dyn.svg' title='".LANG['uninstall']."'>&nbsp;";
					echo  '<a '.explorerLink('views/package-details.php?id='.$j->package_id).'>'.htmlspecialchars($j->package_family_name).' ('.htmlspecialchars($j->package_version).')</a>';
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

<div class="details-abreast">
	<div>
		<h2><?php echo LANG['recognised_software']; ?></h2>
		<table id='tblSoftwareInventoryData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG['name']; ?></th>
					<th class='searchable sortable'><?php echo LANG['version']; ?></th>
					<th class='searchable sortable'><?php echo LANG['description']; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->getComputerSoftware($computer->id) as $s) {
					$counter ++;
					echo "<tr>";
					echo "<td><a ".explorerLink('views/software.php?id='.$s->software_id).">".htmlspecialchars($s->software_name)."</a></td>";
					echo "<td><a ".explorerLink('views/software.php?id='.$s->software_id.'&version='.$s->version).">".htmlspecialchars($s->version)."</a></td>";
					echo "<td>".htmlspecialchars($s->software_description)."</td>";
					echo "</tr>";
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'><span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
