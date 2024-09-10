<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

// ----- prepare view -----
$tab = 'general';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

try {
	$md = $cl->getMobileDevice($_GET['id'] ?? -1);
	$permissionDeploy = $cl->checkPermission($md, PermissionManager::METHOD_DEPLOY, false);
	$permissionWrite  = $cl->checkPermission($md, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($md, PermissionManager::METHOD_DELETE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$isOnline = $md->isOnline($db);
?>

<div class='details-header'>
	<h1><img src='<?php echo $md->getIcon(); ?>' class='<?php echo($isOnline ? 'online' : 'offline'); ?>' title='<?php echo($isOnline ? LANG('online') : LANG('offline')); ?>'><span id='page-title'><span id='spnMobileDeviceSerial'><?php echo htmlspecialchars($md->serial); ?></span></span></h1>
	<div class='controls'>
		<button onclick='refreshContentDeploy([],[],{"id":<?php echo $md->id; ?>,"name":spnMobileDeviceSerial.innerText});' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('deploy'); ?></button>
		<button onclick='showDialogAddMobileDeviceToGroup(<?php echo $md->id; ?>)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
		<button onclick='confirmRemoveMobileDevice([<?php echo $md->id; ?>], event, spnMobileDeviceSerial.innerText, "views/mobile-devices.php")' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
		<span class='filler'></span>
	</div>
</div>

<div id='tabControlMobileDevice' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='general' class='<?php if($tab=='general') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"))'><?php echo LANG('general_and_hardware'); ?></a>
		<a href='#' name='packages' class='<?php if($tab=='packages') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"))'><?php echo LANG('packages_and_jobs'); ?></a>
		<a href='#' name='software' class='<?php if($tab=='software') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"))'><?php echo LANG('recognised_software'); ?></a>
		<a href='#' name='history' class='<?php if($tab=='history') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"),true)'><?php echo LANG('history'); ?></a>
	</div>
	<div class='tabcontents'>

		<div name='general' class='<?php if($tab=='general') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('general'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('id'); ?></th>
							<td><?php echo htmlspecialchars($md->id); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('uid'); ?></th>
							<td><?php echo htmlspecialchars($md->udid); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('serial_no'); ?></th>
							<td><?php echo htmlspecialchars($md->serial); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('device_family'); ?></th>
							<td><?php echo htmlspecialchars($md->device_family); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('model'); ?></th>
							<td><?php echo htmlspecialchars($md->model); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('os'); ?></th>
							<td><?php echo htmlspecialchars($md->os); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('vendor_description'); ?></th>
							<td><?php echo htmlspecialchars($md->vendor_description); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('color'); ?></th>
							<td><?php echo htmlspecialchars($md->color); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('activation_profile'); ?></th>
							<td><?php echo empty($md->profile_uuid) ? '<img src="img/close.opacity.svg">' : '<img src="img/success.dyn.svg">'; ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('push_token'); ?></th>
							<td><?php echo empty($md->push_token) ? '<img src="img/close.opacity.svg">' : '<img src="img/success.dyn.svg">'; ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('created'); ?></th>
							<td><?php echo htmlspecialchars($md->created); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('last_updated'); ?></th>
							<td class='subbuttons'>
								<?php echo htmlspecialchars($md->last_update.($md->force_update ? ' ('.LANG('force_update').')' : '')); ?>
								<?php if($permissionWrite) { ?>
									<button onclick='event.stopPropagation();setComputerForceUpdate(<?php echo $md->id; ?>, 1);return false'><img class='small' src='img/force-update.dyn.svg' title='<?php echo LANG('force_update'); ?>'></button>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<th><?php echo LANG('assigned_groups'); ?></th>
							<td>
								<?php
								$res = $db->selectAllMobileDeviceGroupByMobileDeviceId($md->id);
								$i = 0;
								foreach($res as $group) {
									echo "<a class='subbuttons' ".explorerLink('views/mobile-devices.php?id='.$group->id).">".wrapInSpanIfNotEmpty($db->getMobileDeviceGroupBreadcrumbString($group->id));
									echo "<button onclick='event.stopPropagation();removeMobileDeviceFromGroup([".$md->id."], ".$group->id.");return false'><img class='small' src='img/folder-remove-from.dyn.svg' title='".LANG('remove_from_group')."'></button>";
									echo "</a>";
									if(++$i != count($res)) { echo "<br>"; }
								}
								?>
							</td>
						</tr>
						<tr>
							<th><?php echo LANG('notes'); ?></th>
							<td>
								<span><?php echo nl2br(htmlspecialchars(LANG($md->notes))); ?></span>
							</td>
						</tr>
					</table>
				</div>
				<div>
					<h2><?php echo LANG('about'); ?></h2>
					<?php
					function echoInfoRow($value) {
						if($value === true) echo '<img src="img/success.dyn.svg">';
						elseif($value === false) echo '<img src="img/close.opacity.svg">';
						elseif(is_array($value)) {
							echo '<table class="list metadata"><tbody>';
							foreach($value as $subkey => $subvalue) {
								echo '<tr>'
									.'<th>'.htmlspecialchars(LANG($subkey)).'</th>'
									.'<td>';
								echoInfoRow($subvalue);
								echo '</td>'
									.'</tr>';
							}
							echo '</tbody></table>';
						}
						else echo htmlspecialchars($value);
					}
					if(!empty($md->info)) echoInfoRow(json_decode($md->info,true));
					?>
				</div>
			</div>
		</div>

		<div name='packages' class='<?php if($tab=='packages') echo 'active'; ?>'>
			<div class='details-abreast'>

			</div>
		</div>

		<div name='software' class='<?php if($tab=='software') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>

				</div>
			</div>
		</div>

		<div name='history' class='<?php if($tab=='history') echo 'active'; ?>'>
			<?php if($tab == 'history') { ?>
			<div class='details-abreast'>
				<div class='stickytable'>
					<table id='tblComputerHistoryData' class='list searchable sortable savesort margintop'>
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
							/*foreach($db->selectAllLogEntryByObjectIdAndActions($computer->id, 'oco.computer', empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								echo "<tr>";
								echo "<td>".htmlspecialchars($l->timestamp)."</td>";
								echo "<td>".htmlspecialchars($l->host)."</td>";
								echo "<td>".htmlspecialchars($l->user)."</td>";
								echo "<td>".htmlspecialchars($l->action)."</td>";
								echo "<td class='subbuttons'>".htmlspecialchars(shorter($l->data, 100))." <button onclick='showDialog(\"".htmlspecialchars($l->action,ENT_QUOTES)."\",this.getAttribute(\"data\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' data='".htmlspecialchars(prettyJson($l->data),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
								echo "</tr>";
							}*/
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan='999'>
									<div class='spread'>
										<div>
											<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
										</div>
										<div class='controls'>
											<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
											<?php if(empty($_GET['nolimit'])) { ?>
												<button onclick='rewriteUrlContentParameter({"nolimit":1}, true)'><img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('show_all'); ?></button>
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
