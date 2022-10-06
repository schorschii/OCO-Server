<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

$tab = 'users';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SYSTEM_USER_MANAGEMENT);
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('system_user_management'); ?></span></h1>
</div>

<div id='tabControlSettings' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='users' class='<?php if($tab=='users') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('system_users'); ?></a>
		<a href='#' name='roles' class='<?php if($tab=='roles') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('roles'); ?></a>
		<a href='#' name='log' class='<?php if($tab=='log') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSettings,this.getAttribute("name"))'><?php echo LANG('user_log'); ?></a>
	</div>
	<div class='tabcontents'>

		<div name='users' class='<?php if($tab=='users') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<button onclick='showDialogEditSystemUser()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_system_user'); ?></button>
						<span class='filler'></span>
						<button onclick='ldapSyncSystemUsers()' <?php if(empty(LDAP_SERVER)) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('ldap_sync'); ?></button>
					</div>
					<table id='tblSystemUserData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemUserData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('login_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('display_name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('role'); ?></th>
							<th class='searchable sortable'><?php echo LANG('last_login'); ?></th>
							<th class='searchable sortable'><?php echo LANG('created'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<?php
					$counter = 0;
					foreach($cl->getSystemUsers() as $u) {
						$counter ++;
						echo "<tr>";
						echo "<td><input type='checkbox' name='system_user_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblSystemUserData)'></td>";
						echo "<td>".htmlspecialchars($u->id)."</td>";
						echo "<td sort_key='".htmlspecialchars($u->username,ENT_QUOTES)."'>";
						if($u->ldap) echo "<img src='img/ldap-directory.dyn.svg' title='".LANG('ldap_account')."'>&nbsp;";
						if($u->locked) echo "<img src='img/lock.dyn.svg' title='".LANG('locked')."'>&nbsp;";
						echo  "<span id='spnSystemUserUid".$u->id."' style='display:none'>".htmlspecialchars($u->uid)."</span>";
						echo  "<span id='spnSystemUserDescription".$u->id."' style='display:none'>".htmlspecialchars($u->description)."</span>";
						echo  "<span id='spnSystemUserUsername".$u->id."'>".htmlspecialchars($u->username)."</span>";
						echo "</td>";
						echo "<td id='spnSystemUserDisplayName".$u->id."'>".htmlspecialchars($u->display_name)."</td>";
						echo "<td id='spnSystemUserRole".$u->id."' rawvalue='".$u->system_user_role_id."'>".htmlspecialchars($u->system_user_role_name)."</td>";
						echo "<td>".htmlspecialchars($u->last_login)."</td>";
						echo "<td>".htmlspecialchars($u->created)."</td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditSystemUser(".$u->id.", spnSystemUserUid".$u->id.".innerText, spnSystemUserUsername".$u->id.".innerText, spnSystemUserDisplayName".$u->id.".innerText, spnSystemUserDescription".$u->id.".innerText, spnSystemUserRole".$u->id.".getAttribute(\"rawvalue\"), ".$u->ldap.")'><img src='img/edit.dyn.svg'></button></td>";
						echo "</tr>";
					}
					?>
					<tfoot>
						<tr>
							<td colspan='999'>
								<div class='spread'>
									<div>
										<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>,
										<span class='counter-checked'>0</span>&nbsp;<?php echo LANG('elements_checked'); ?>
									</div>
									<div class='controls'>
										<button onclick='lockSelectedSystemUser("system_user_id[]")'><img src='img/lock.dyn.svg'>&nbsp;<?php echo LANG('lock'); ?></button>
										<button onclick='unlockSelectedSystemUser("system_user_id[]")'><img src='img/unlock.dyn.svg'>&nbsp;<?php echo LANG('unlock'); ?></button>
										<button onclick='confirmRemoveSelectedSystemUser("system_user_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
									</div>
								</div>
							</td>
						</tr>
					</tfoot>
					</table>
				</div>
			</div>
		</div>

		<div name='roles' class='<?php if($tab=='roles') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<div class='controls'>
						<button onclick='showDialogEditSystemUserRole()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create_system_user_role'); ?></button>
						<span class='filler'></span>
					</div>
					<table id='tblSystemUserRoleData' class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblSystemUserRoleData, this.checked)'></th>
							<th class='searchable sortable'><?php echo LANG('id'); ?></th>
							<th class='searchable sortable'><?php echo LANG('name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('permission_json'); ?></th>
							<th class='searchable sortable'><?php echo LANG('system_users'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<?php
					$counter = 0;
					foreach($cl->getSystemUserRoles() as $r) {
						$counter ++;
						echo "<tr>";
						echo "<td><input type='checkbox' name='system_user_role_id[]' value='".$r->id."' onchange='refreshCheckedCounter(tblSystemUserRoleData)'></td>";
						echo "<td>".htmlspecialchars($r->id)."</td>";
						echo "<td><span id='spnSystemUserRoleName".$r->id."'>".htmlspecialchars($r->name)."</span></td>";
						echo "<td class='subbuttons'>".htmlspecialchars(shorter($r->permissions, 100))." <button id='btnSystemUserRolePermissions".$r->id."' onclick='showDialog(\"".htmlspecialchars($r->name,ENT_QUOTES)."\",this.getAttribute(\"permissions\"),DIALOG_BUTTONS_CLOSE,DIALOG_SIZE_LARGE,true)' permissions='".htmlspecialchars(prettyJson($r->permissions),ENT_QUOTES)."'><img class='small' src='img/eye.dyn.svg'></button></td>";
						echo "<td>".htmlspecialchars($r->system_user_count)."</span></td>";
						echo "<td><button title='".LANG('edit')."' onclick='showDialogEditSystemUserRole(".$r->id.", spnSystemUserRoleName".$r->id.".innerText, btnSystemUserRolePermissions".$r->id.".getAttribute(\"permissions\"))'><img src='img/edit.dyn.svg'></button></td>";
						echo "</tr>";
					}
					?>
					<tfoot>
						<tr>
							<td colspan='999'>
								<div class='spread'>
									<div>
										<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>,
										<span class='counter-checked'>0</span>&nbsp;<?php echo LANG('elements_checked'); ?>
									</div>
									<div class='controls'>
										<button onclick='confirmRemoveSelectedSystemUserRole("system_user_role_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
									</div>
								</div>
							</td>
						</tr>
					</tfoot>
					</table>
				</div>
			</div>
		</div>

		<div name='log' class='<?php if($tab=='log') echo 'active'; ?>'>
			<div class='details-abreast'>
				<div class='stickytable'>
					<table id='tblUserLogData' class='list searchable sortable savesort margintop'>
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
							foreach($db->selectAllLogEntryByObjectIdAndActions(null, 'oco.client', empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
								$counter ++;
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
											<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
										</div>
										<div class='controls'>
											<button onclick='downloadTableCsv("tblSoftwareInventoryData")'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
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
		</div>

	</div>
</div>

