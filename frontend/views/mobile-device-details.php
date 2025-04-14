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
?>

<div class='details-header'>
	<h1><img src='<?php echo $md->getIcon(); ?>' class='<?php echo($md->isOnline() ? 'online' : 'offline'); ?>' title='<?php echo($md->isOnline() ? LANG('enrolled') : LANG('not_enrolled')); ?>'><span id='page-title'><span id='spnMobileDeviceSerial'><?php echo htmlspecialchars($md->getDisplayName()); ?></span></span></h1>
	<div class='controls'>
		<button onclick='showDialogMobileDeviceCommand(<?php echo $md->id; ?>)' <?php if(!$permissionDeploy) echo 'disabled'; ?>><img src='img/command.dyn.svg'>&nbsp;<?php echo LANG('send_command'); ?></button>
		<button onclick='showDialogEditMobileDevice(<?php echo $md->id; ?>, spnMobileDeviceNotes.innerText)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
		<button onclick='showDialogAddMobileDeviceToGroup(<?php echo $md->id; ?>)' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('add_to'); ?></button>
		<button onclick='confirmRemoveMobileDevice([<?php echo $md->id; ?>], event, spnMobileDeviceSerial.innerText, "views/mobile-devices.php")' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
		<span class='filler'></span>
	</div>
</div>

<div id='tabControlMobileDevice' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='general' class='<?php if($tab=='general') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"))'><?php echo LANG('general_and_hardware'); ?></a>
		<a href='#' name='profiles' class='<?php if($tab=='profiles') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"))'><?php echo LANG('profiles_and_commands'); ?></a>
		<a href='#' name='apps' class='<?php if($tab=='apps') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlMobileDevice,this.getAttribute("name"))'><?php echo LANG('installed_apps'); ?></a>
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
							<td><?php echo htmlspecialchars($md->udid ?? ''); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('serial_no'); ?></th>
							<td><?php echo htmlspecialchars($md->serial); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('model'); ?></th>
							<td><?php echo htmlspecialchars($md->model ?? $md->device_family); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('os'); ?></th>
							<td><?php echo htmlspecialchars($md->os); ?></td>
						</tr>
						<?php if($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) { ?>
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
						<?php } ?>
						<tr>
							<th><?php echo LANG('created'); ?></th>
							<td><?php echo htmlspecialchars($md->created); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('last_updated'); ?></th>
							<td class='subbuttons'>
								<?php echo htmlspecialchars($md->last_update.($md->force_update ? ' ('.LANG('force_update').')' : '')); ?>
								<?php if($permissionWrite) { ?>
									<button onclick='event.stopPropagation();setMobileDeviceForceUpdate(<?php echo $md->id; ?>, 1);return false'><img class='small' src='img/force-update.dyn.svg' title='<?php echo LANG('force_update'); ?>'></button>
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
									echo "<a class='subbuttons' ".explorerLink('views/mobile-devices.php?id='.$group->id).">".wrapInSpanIfNotEmpty($group->getBreadcrumbString());
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
								<span id='spnMobileDeviceNotes'><?php echo nl2br(htmlspecialchars(LANG($md->notes))); ?></span>
							</td>
						</tr>
					</table>
				</div>
				<div>
					<h2><?php echo LANG('information'); ?></h2>
					<?php
					if(empty($md->info)) { ?>
						<div class='alert info'><?php echo LANG('device_does_not_delivered_info_yet'); ?></div>
					<?php } elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) {
						$info = json_decode($md->info, true); ?>
						<table class='list metadata'>
							<tr>
								<th><?php echo LANG('build'); ?></th>
								<td><?php echo htmlspecialchars($info['BuildVersion']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('model'); ?></th>
								<td><?php echo htmlspecialchars($info['ModelNumber']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('eas_device_identifier'); ?></th>
								<td><?php echo htmlspecialchars($info['EASDeviceIdentifier']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('time_zone'); ?></th>
								<td><?php echo htmlspecialchars($info['TimeZone']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('supervised'); ?></th>
								<td><?php echoDictTable($info['IsSupervised']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('app_analytics'); ?></th>
								<td><?php echoDictTable($info['AppAnalyticsEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('diagnostic_submission'); ?></th>
								<td><?php echoDictTable($info['DiagnosticSubmissionEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('cloud_backup'); ?></th>
								<td><?php echoDictTable($info['IsCloudBackupEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('device_locator_service'); ?></th>
								<td><?php echoDictTable($info['IsDeviceLocatorServiceEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('do_not_disturb'); ?></th>
								<td><?php echoDictTable($info['IsDoNotDisturbInEffect']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('lost_mode'); ?></th>
								<td><?php echoDictTable($info['IsMDMLostModeEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('multi_user'); ?></th>
								<td><?php echoDictTable($info['IsMultiUser']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('personal_hotspot'); ?></th>
								<td><?php echoDictTable($info['PersonalHotspotEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('data_roaming'); ?></th>
								<td><?php echoDictTable($info['DataRoamingEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('cellular_technology'); ?></th>
								<td><?php
									if(($info['CellularTechnology']??'-') == '0') echo '-';
									elseif(($info['CellularTechnology']??'-') == '1') echo 'GSM';
									elseif(($info['CellularTechnology']??'-') == '2') echo 'CDMA';
									elseif(($info['CellularTechnology']??'-') == '3') echo 'GSM+CDMA';
									else echo htmlspecialchars($info['CellularTechnology']??'');
								?></td>
							</tr>
							<tr>
								<th><?php echo LANG('modem_firmware'); ?></th>
								<td><?php echo htmlspecialchars($info['ModemFirmwareVersion']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('sim_slots'); ?></th>
								<td><?php echoDictTable($info['ServiceSubscriptions']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('bluetooth_mac'); ?></th>
								<td><?php echo htmlspecialchars($info['BluetoothMAC']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('wifi_mac'); ?></th>
								<td><?php echo htmlspecialchars($info['WiFiMAC']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('itunes_store_account_hash'); ?></th>
								<td><?php echo htmlspecialchars($info['iTunesStoreAccountHash']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('battery_status'); ?></th>
								<td><?php if($info['BatteryLevel']??0) echo progressBar(($info['BatteryLevel']??0)*100, null, null, 'stretch'); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('storage'); ?></th>
								<td><?php
								$total = $info['DeviceCapacity'] ?? 0;
								if($total) {
									$free = $info['AvailableDeviceCapacity'] ?? 0;
									$used = $total - $free;
									echo progressBar($used*100/$total, null, null, 'stretch', '', round($used,2).' / '.round($total,2).' GB');
								}
								?></td>
							</tr>
						</table>
					<?php } elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_ANDROID) {
						$info = json_decode($md->info, true); ?>
						<table class='list metadata'>
							<tr>
								<th><?php echo LANG('identifier'); ?></th>
								<td><?php echo htmlspecialchars($info['name']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('management_mode'); ?></th>
								<td><?php echo htmlspecialchars($info['managementMode']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('ownership'); ?></th>
								<td><?php echo htmlspecialchars($info['ownership']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('status'); ?></th>
								<td><?php echo htmlspecialchars($info['state']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('policy_compliant'); ?></th>
								<td><?php echoDictTable($info['policyCompliant']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('api_level'); ?></th>
								<td><?php echo htmlspecialchars($info['apiLevel']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('device_policy_version'); ?></th>
								<td><?php echo htmlspecialchars($info['softwareInfo']['androidDevicePolicyVersionName']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('kernel_version'); ?></th>
								<td><?php echo htmlspecialchars($info['softwareInfo']['deviceKernelVersion']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('bootloader_version'); ?></th>
								<td><?php echo htmlspecialchars($info['softwareInfo']['bootloaderVersion']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('system_update'); ?></th>
								<td><?php $updateStatus = $info['softwareInfo']['systemUpdateInfo']['updateStatus']??'';
								if($updateStatus === 'UP_TO_DATE') echo LANG('up_to_date');
								elseif($updateStatus === 'UNKNOWN_UPDATE_AVAILABLE') echo '<span class="badge">'.LANG('update_available').'</span>';
								elseif($updateStatus === 'SECURITY_UPDATE_AVAILABLE') echo '<span class="badge">'.LANG('security_update_available').'</span>';
								elseif($updateStatus === 'OS_UPDATE_AVAILABLE') echo '<span class="badge">'.LANG('os_update_available').'</span>';
								elseif($updateStatus === 'UPDATE_STATUS_UNKNOWN') echo '?';
								else echo htmlspecialchars($updateStatus);
								?></td>
							</tr>
							<tr>
								<th><?php echo LANG('screens'); ?></th>
								<td><?php foreach($info['displays']??[] as $display) {
									echo '<div>'.htmlspecialchars(
										($display['name']??'').' '.
										($display['width']??'').'x'.($display['height']??'').' '.
										($display['density']??'').'ppi '.
										($display['refreshRate']??'').'Hz '.
										($display['state']??'')
									);
								} ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('imei'); ?></th>
								<td><?php echo htmlspecialchars($info['networkInfo']['imei']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('meid'); ?></th>
								<td><?php echo htmlspecialchars($info['networkInfo']['meid']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('sim_slots'); ?></th>
								<td><?php echoDictTable($info['networkInfo']['telephonyInfos']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('wifi_mac'); ?></th>
								<td><?php echo htmlspecialchars($info['networkInfo']['wifiMacAddress']??''); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('ram'); ?></th>
								<td><?php if($info['memoryInfo']['totalRam']??null) echo niceSize($info['memoryInfo']['totalRam'], false); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('storage'); ?></th>
								<td><?php if($info['memoryInfo']['totalInternalStorage']??null) echo niceSize($info['memoryInfo']['totalInternalStorage'], false); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('encrypted'); ?></th>
								<td><?php echoDictTable($info['deviceSettings']['isEncrypted']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('app_verification_enabled'); ?></th>
								<td><?php echoDictTable($info['deviceSettings']['verifyAppsEnabled']??null); ?></td>
							</tr>
							<tr>
								<th><?php echo LANG('common_criteria_mode'); ?></th>
								<td><?php $ccm = $info['commonCriteriaModeInfo']['commonCriteriaModeStatus']??null;
								if($ccm === 'COMMON_CRITERIA_MODE_ENABLED') {
									echoDictTable(true);
									echo htmlspecialchars($info['commonCriteriaModeInfo']['policySignatureVerificationStatus']??'');
								} elseif($ccm !== null) echoDictTable(false);
								?></td>
							</tr>
						</table>
					<?php } ?>
				</div>
			</div>
		</div>

		<div name='profiles' class='<?php if($tab=='profiles') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div>
					<?php if($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) { ?>
					<h2><?php echo LANG('installed_profiles'); ?></h2>
					<div class='stickytable'>
						<table id='tblMobileDeviceProfileData' class='list searchable sortable savesort margintop fullwidth'>
							<thead>
								<tr>
									<th class='searchable sortable'><?php echo LANG('display_name'); ?></th>
									<th class='searchable sortable'><?php echo LANG('identifier'); ?></th>
									<th class='searchable sortable'><?php echo LANG('uid'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($db->selectAllMobileDeviceProfileUuidByMobileDeviceId($md->id) as $ip) { ?>
									<tr>
										<td><?php echo htmlspecialchars($ip->display_name); ?></td>
										<td><?php echo htmlspecialchars($ip->identifier); ?></td>
										<td><?php echo htmlspecialchars($ip->uuid); ?></td>
									</tr>
								<?php } ?>
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
											</div>
										</div>
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_ANDROID) { ?>
					<h2><?php echo LANG('applied_policies'); ?></h2>
					<div class='stickytable'>
						<table id='tblMobileDevicePolicyData' class='list searchable sortable savesort margintop fullwidth'>
							<thead>
								<tr>
									<th class='searchable sortable'><?php echo LANG('policy'); ?></th>
									<th class='searchable sortable'><?php echo LANG('value'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach(json_decode($md->policy??'', true) ?? [] as $key => $value) { ?>
									<tr>
										<td><?php echo htmlspecialchars($key); ?></td>
										<td><?php echo echoDictTable($value); ?></td>
									</tr>
								<?php } ?>
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
											</div>
										</div>
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } ?>
				</div>
				<div>
					<h2><?php echo LANG('commands'); ?></h2>
					<div class='stickytable'>
						<table id='tblMobileDeviceProfileData' class='list searchable sortable savesort margintop fullwidth'>
							<thead>
								<tr>
									<th class='searchable sortable'><?php echo LANG('state'); ?></th>
									<th class='searchable sortable'><?php echo LANG('name'); ?></th>
									<th class='searchable sortable'><?php echo LANG('finished'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($db->selectAllMobileDeviceCommandByMobileDevice($md->id, false) as $c) { ?>
									<tr>
										<td>
											<img src='img/<?php echo $c->getStatus() ?>.dyn.svg'>
											<?php if(empty($c->message)) { ?>
												<?php echo htmlspecialchars($c->getStateString()); ?>
											<?php } else { ?>
												<a href='#' onclick='event.preventDefault();showDialog(this.getAttribute("summary"), this.getAttribute("message"), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_LARGE, true)' summary='<?php echo htmlspecialchars($c->name, ENT_QUOTES); ?>'
												message='<?php echo htmlspecialchars(json_decode($c->message) ? json_encode(json_decode($c->message),JSON_PRETTY_PRINT) : $c->message, ENT_QUOTES); ?>'>
													<?php echo htmlspecialchars($c->getStateString()); ?>
												</a>
											<?php } ?>
										</td>
										<td><?php echo htmlspecialchars($c->name); ?></td>
										<td><?php echo htmlspecialchars($c->finished); ?></td>
									</tr>
								<?php } ?>
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
											</div>
										</div>
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>
		</div>

		<div name='apps' class='<?php if($tab=='apps') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
				<table id='tblMobileDeviceAppData' class='list searchable sortable savesort margintop'>
						<thead>
							<tr>
								<th class='searchable sortable'><?php echo LANG('name'); ?></th>
								<th class='searchable sortable'><?php echo LANG('identifier'); ?></th>
								<th class='searchable sortable'><?php echo LANG('version'); ?></th>
								<th class='searchable sortable'><?php echo LANG('version_code'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($db->selectAllMobileDeviceAppIdentifierByMobileDeviceId($md->id) as $a) { ?>
								<tr>
									<td><?php echo htmlspecialchars($a->name); ?></td>
									<td><?php echo htmlspecialchars($a->identifier); ?></td>
									<td><?php echo htmlspecialchars($a->display_version); ?></td>
									<td><?php echo htmlspecialchars($a->version); ?></td>
								</tr>
							<?php } ?>
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
										</div>
									</div>
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
					<table id='tblMobileDeviceHistoryData' class='list searchable sortable savesort margintop'>
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
							foreach($db->selectAllLogEntryByObjectIdAndActions($md->id, 'oco.mobile_device', empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								echo "<tr>";
								echo "<td>".htmlspecialchars($l->timestamp)."</td>";
								echo "<td>".htmlspecialchars($l->host)."</td>";
								echo "<td>".htmlspecialchars($l->user)."</td>";
								echo "<td>".htmlspecialchars($l->action)."</td>";
								echo "<td class='subbuttons'>".htmlspecialchars(shorter($l->data, 100))." <button onclick='showDialog(\"".htmlspecialchars($l->action,ENT_QUOTES)."\",this.getAttribute(\"data\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' data='".htmlspecialchars(prettyJson($l->data),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
								echo "</tr>";
							}
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
