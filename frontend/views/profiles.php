<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$profiles = $cl->getProfiles();
	$permissionCreateProfile = $cl->checkPermission(new Models\Profile(), PermissionManager::METHOD_CREATE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1><img src='img/profile.dyn.svg'><span id='page-title'><?php echo LANG('profiles'); ?></span></h1>
<div class='controls'>
	<button onclick='showDialogEditProfile()' <?php if(!$permissionCreateProfile) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_profile'); ?></button>
	<span class='filler'></span>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblProfileData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('notes'); ?></th>
				<th class='searchable sortable'><?php echo LANG('created'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($profiles as $p) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='profile_id[]' value='".$p->id."'></td>";
			echo "<td>".htmlspecialchars($p->name)."</td>";
			echo "<td>".htmlspecialchars(shorter(LANG($p->notes)))."</td>";
			echo "<td>".htmlspecialchars($p->created)."</td>";
			echo "</tr>";
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
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<button onclick='showDialogAssignProfileToGroup(getSelectedCheckBoxValues("profile_id[]", null, true))'><img src='img/folder-insert-into.dyn.svg'>&nbsp;<?php echo LANG('assign'); ?></button>
							<button onclick='removeSelectedProfile("profile_id[]", null, event)'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>
