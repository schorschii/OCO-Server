<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// ----- prepare view -----
$tab = 'general';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$computer = null;
try {
	if(!empty($_GET['id'])) {
		$computer = $cl->getMyComputer($_GET['id']);
		$permissionDeploy = $cl->checkPermission($computer, SelfService\PermissionManager::METHOD_DEPLOY, false);
		$permissionWol    = $cl->checkPermission($computer, SelfService\PermissionManager::METHOD_WOL, false);
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$commands = Models\Computer::getCommands($ext);
?>

<?php if(empty($computer)) { ?>

	<div class='details-header'>
		<h1><img src='img/computer.dyn.svg'><span id='page-title'><?php echo LANG('my_computers'); ?></span></h1>
		<div class='controls'></div>
	</div>
	<?php $computers = $cl->getMyComputers();
	if(count($computers) == 0) { ?>
		<div class='alert info'><?php echo LANG('no_computers_found'); ?></div>
	<?php } else { ?>
		<div class='gallery gap'>
		<?php foreach($computers as $c) { ?>
			<a class='item blue' <?php echo explorerLink('views/computers.php?id='.$c->id); ?>>
				<img src='<?php echo $c->getIcon(); ?>' class='<?php if(!$c->isOnline($db)) echo 'offline'; ?>'>
				<h3><?php echo htmlspecialchars($c->hostname); ?></h3>
			</a>
		<?php } ?>
		</div>
	<?php } ?>

<?php } else { ?>

	<div class='details-header'>
	<h1><img src='<?php echo $computer->getIcon(); ?>' class='<?php echo($computer->isOnline($db) ? 'online' : 'offline'); ?>' title='<?php echo($computer->isOnline($db) ? LANG('online') : LANG('offline')); ?>'><span id='page-title'><span id='spnComputerName'><?php echo htmlspecialchars($computer->hostname); ?></span></span></h1>
	<div class='controls'>
		<button onclick='refreshContentDeploy([],[],{"id":<?php echo $computer->id; ?>,"name":spnComputerName.innerText});' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
		<button onclick='confirmWolComputer([<?php echo $computer->id; ?>])' <?php if(!$permissionWol) echo 'disabled'; ?>><img src='img/wol.dyn.svg'>&nbsp;<?php echo LANG('wol'); ?></button>
		<span class='filler'></span>
		<?php
		foreach($commands as $command) {
			echoCommandButton($command, $computer->hostname);
		}
		?>
	</div>
</div>

<div id='tabControlComputer' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='general' class='<?php if($tab=='general') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlComputer,this.getAttribute("name"))'><?php echo LANG('general_and_hardware'); ?></a>
		<a href='#' name='packages' class='<?php if($tab=='packages') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlComputer,this.getAttribute("name"))'><?php echo LANG('packages_and_jobs'); ?></a>
	</div>
	<div class='tabcontents'>

		<div name='general' class='<?php if($tab=='general') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('general'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('id'); ?></th>
							<td><?php echo htmlspecialchars($computer->id); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('os'); ?></th>
							<td><?php echo htmlspecialchars($computer->os); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('version'); ?></th>
							<td><?php echo htmlspecialchars($computer->os_version); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('license'); ?></th>
							<td><?php if($computer->os_license=='1') echo LANG('activated'); elseif($computer->os_license=='0') echo LANG('not_activated'); else echo htmlspecialchars($computer->os_license); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('locale'); ?></th>
							<td><?php if(empty($computer->os_locale) || $computer->os_locale == '-' || $computer->os_locale == '?') echo htmlspecialchars($computer->os_locale); else echo htmlspecialchars(LanguageCodes::getLocaleNameByLcid($computer->os_locale)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('kernel_version'); ?></th>
							<td><?php echo htmlspecialchars($computer->kernel_version); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('architecture'); ?></th>
							<td><?php echo htmlspecialchars($computer->architecture); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('cpu'); ?></th>
							<td><?php echo htmlspecialchars($computer->cpu); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('gpu'); ?></th>
							<td><?php echo htmlspecialchars($computer->gpu); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('ram'); ?></th>
							<td><?php echo niceSize($computer->ram, true, 0); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('serial_no'); ?></th>
							<td><?php echo htmlspecialchars($computer->serial); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('vendor'); ?></th>
							<td><?php echo htmlspecialchars($computer->manufacturer); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('model'); ?></th>
							<td><?php echo htmlspecialchars($computer->model); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('bios_version'); ?></th>
							<td><?php echo htmlspecialchars($computer->bios_version); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('boot_type'); ?></th>
							<td><?php echo htmlspecialchars($computer->boot_type); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('secure_boot'); ?></th>
							<td><?php if($computer->secure_boot=='1') echo LANG('yes'); elseif($computer->secure_boot=='0') echo LANG('no'); else echo htmlspecialchars($computer->secure_boot); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('domain'); ?></th>
							<td><?php echo htmlspecialchars($computer->domain); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('agent_version'); ?></th>
							<td><?php echo htmlspecialchars($computer->agent_version); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('remote_address'); ?></th>
							<td><?php echo htmlspecialchars($computer->remote_address); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('uptime'); ?></th>
							<td><?php if(!empty($computer->uptime)) echo niceTime($computer->uptime); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('last_seen'); ?></th>
							<td><?php echo htmlspecialchars($computer->last_ping); ?></td>
						</tr>
					</table>
				</div>
				<div>
					<h2><?php echo LANG('logins'); ?></h2>
					<table id='tblLoginsData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><?php echo LANG('login_name'); ?></th>
								<th><?php echo LANG('display_name'); ?></th>
								<th><?php echo LANG('count'); ?></th>
								<th><?php echo LANG('last_login'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllDomainUserLogonByComputerId($computer->id) as $logon) {
								echo "<tr>";
								echo "<td>".htmlspecialchars($logon->domain_user_username)."</td>";
								echo "<td>".htmlspecialchars($logon->domain_user_display_name)."</td>";
								echo "<td>".htmlspecialchars($logon->logon_amount)."</td>";
								echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
								echo "</tr>";
							}
							?>
						</tbody>
					</table>
				</div>
			</div>

			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('network'); ?></h2>
					<table id='tblNetworkData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><?php echo LANG('ip_address'); ?></th>
								<th><?php echo LANG('netmask'); ?></th>
								<th><?php echo LANG('broadcast'); ?></th>
								<th><?php echo LANG('mac_address'); ?></th>
								<th><?php echo LANG('interface'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllComputerNetworkByComputerId($computer->id) as $n) {
								echo '<tr>';
								echo '<td class="flyout-container" tabindex="0">';
								echo  htmlspecialchars($n->address);
								if(count($commands) > 0) {
									echo '<div class="flyout box">';
									foreach($commands as $c) { echoCommandButton($c, $n->address, true); echo ' '; }
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
					<h2><?php echo LANG('screens'); ?></h2>
					<table id='tblScreensData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><?php echo LANG('name'); ?></th>
								<th><?php echo LANG('vendor'); ?></th>
								<th><?php echo LANG('type'); ?></th>
								<th><?php echo LANG('resolution'); ?></th>
								<th><?php echo LANG('size'); ?></th>
								<th><?php echo LANG('manufactured'); ?></th>
								<th><?php echo LANG('serial_no'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllComputerScreenByComputerId($computer->id) as $s) {
								echo '<tr class="'.(empty($s->active)?'inactive':'').'">';
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

			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('printers'); ?></h2>
					<table id='tblPrinterData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><?php echo LANG('name'); ?></th>
								<th><?php echo LANG('driver'); ?></th>
								<th><?php echo LANG('address'); ?></th>
								<th><?php echo LANG('status'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllComputerPrinterByComputerId($computer->id) as $p) {
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
					<h2><?php echo LANG('file_systems'); ?></h2>
					<table id='tblFileSystemsData' class='list sortable savesort'>
						<thead>
							<tr>
								<th><?php echo LANG('device'); ?></th>
								<th><?php echo LANG('mountpoint'); ?></th>
								<th><?php echo LANG('file_system'); ?></th>
								<th><?php echo LANG('size'); ?></th>
								<th><?php echo LANG('used'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllComputerPartitionByComputerId($computer->id) as $p) {
								$percent = 0;
								if(!empty($p->free) && !empty($p->size))
								$percent = round(100 - ($p->free / $p->size * 100));
								echo '<tr>';
								echo '<td>'.htmlspecialchars($p->device).'</a></td>';
								echo '<td>'.htmlspecialchars($p->mountpoint).'</td>';
								echo '<td>'.htmlspecialchars($p->filesystem).'</td>';
								echo '<td sort_key="'.htmlspecialchars($p->size).'">'.htmlspecialchars(niceSize($p->size)).'</td>';
								echo '<td sort_key="'.htmlspecialchars($percent).'" title="'.LANG('used').': '.htmlspecialchars(niceSize($p->size-$p->free,true,1,true)).', '.LANG('free').': '.htmlspecialchars(niceSize($p->free,true,1,true)).'">'.progressBar($percent, null, null, 'stretch', '').'</td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div name='packages' class='<?php if($tab=='packages') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<h2><?php echo LANG('installed_packages'); ?></h2>
					<table id='tblInstalledPackageData' class='list searchable sortable savesort'>
						<thead>
							<tr>
								<th><input type='checkbox' class='toggleAllChecked'></th>
								<th class='searchable sortable'><?php echo LANG('package'); ?></th>
								<th class='searchable sortable'><?php echo LANG('initiator'); ?></th>
								<th class='searchable sortable'><?php echo LANG('installation_date'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllComputerPackageByComputerId($computer->id) as $p) {
								if(!$cl->checkPermission($db->selectPackage($p->package_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
								echo '<tr>';
								echo '<td><input type="checkbox" name="package_id[]" value="'.$p->id.'" package_id="'.$p->package_id.'"></td>';
								echo '<td><a '.explorerLink('views/packages.php?id='.$p->package_id).'>'.htmlspecialchars($p->package_family_name).' ('.htmlspecialchars($p->package_version).')</a></td>';
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
											<button onclick='deploySelectedPackage("package_id[]", "package_id");'><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
											<button onclick='showDialogUninstall()'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
										</div>
									</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
				<div class='stickytable'>
					<h2><?php echo LANG('pending_jobs'); ?></h2>
					<table id='tblPendingComputerJobsData' class='list searchable sortable savesort'>
						<thead>
							<tr>
								<th class='searchable sortable'><?php echo LANG('package'); ?></th>
								<th class='searchable sortable'><?php echo LANG('container'); ?></th>
								<th class='searchable sortable'><?php echo LANG('status'); ?></th>
								<th class='searchable sortable'><?php echo LANG('priority').'/'.LANG('sequence'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($db->selectAllPendingJobByComputerId($computer->id) as $j) {
								if(!$cl->checkPermission($db->selectPackage($j->package_id), SelfService\PermissionManager::METHOD_READ, false)) continue;
								echo '<tr class="'.(!$j->isEnabled()?'inactive':'').'">';
								echo '<td>';
								if($j->is_uninstall == 0) echo "<img src='img/install.dyn.svg' title='".LANG('install')."'>&nbsp;";
								else echo "<img src='img/delete.dyn.svg' title='".LANG('uninstall')."'>&nbsp;";
								echo  '<a '.explorerLink('views/packages.php?id='.$j->package_id).'>'.htmlspecialchars($j->package_family_name).' ('.htmlspecialchars($j->package_version).')</a>';
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
									<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
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
