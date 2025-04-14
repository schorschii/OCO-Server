<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_VIEW_DELETED_OBJECTS);
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('deleted_objects_history'); ?></span></h1>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblUserLogData' class='list searchable sortable savesort margintop'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('timestamp'); ?></th>
					<th class='searchable sortable'><?php echo LANG('ip_address'); ?></th>
					<th class='searchable sortable'><?php echo LANG('user'); ?></th>
					<th class='searchable sortable'><?php echo LANG('action'); ?></th>
					<th class='searchable sortable'><?php echo LANG('object_id'); ?></th>
					<th class='searchable sortable'><?php echo LANG('data'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->selectAllLogEntryByObjectIdAndActions(false, ['oco.computer.delete', 'oco.package.delete', 'oco.package_family.delete', 'oco.job_container.delete', 'oco.deployment_rule.delete', 'oco.domain_user.delete', 'oco.report.delete', 'oco.mobile_device.delete', 'oco.managed_app.delete', 'oco.profile.delete'], empty($_GET['nolimit'])?Models\Log::DEFAULT_VIEW_LIMIT:false) as $l) {
					echo "<tr>";
					echo "<td>".htmlspecialchars($l->timestamp)."</td>";
					echo "<td>".htmlspecialchars($l->host)."</td>";
					echo "<td>".htmlspecialchars($l->user)."</td>";
					echo "<td>".htmlspecialchars($l->action)."</td>";
					echo "<td>".htmlspecialchars($l->object_id)."</td>";
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

