<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	// todo: permission check
	$commands = $db->selectAllMobileDeviceCommand();
	$permissionCreate = $cl->checkPermission(new Models\MobileDeviceCommand(), PermissionManager::METHOD_CREATE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

	<h1><img src='img/container.dyn.svg'><span id='page-title'><?php echo LANG('mobile_device_commands'); ?></span></h1>

	<div class='controls'>
		<button onclick='showDialogEditMobileDeviceCommand()' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new_command'); ?></button>
		<span class='filler'></span>
	</div>

	<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblMobileDeviceCommandData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('name'); ?></th>
					<th class='searchable sortable'><?php echo LANG('author'); ?></th>
					<th class='searchable sortable'><?php echo LANG('created'); ?></th>
					<th class='searchable sortable'><?php echo LANG('status'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($commands as $jc) {
				echo "<tr>";
				echo "<td><input type='checkbox' name='mobile_device_command_id[]' value='".$jc->id."'></td>";
				echo "<td class='middle'>";
				echo  "<img src='img/".$jc->getStatus().".dyn.svg'>&nbsp;";
				echo  "<a>".htmlspecialchars($jc->name)."</a>";
				echo "</td>";
				echo "<td></td>";
				echo "<td>".htmlspecialchars($jc->created)."</td>";
				echo "<td></td>";
				echo "</tr>";
			} ?>
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
								<button onclick='removeSelectedMobileDeviceCommand("mobile_device_command_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	</div>
