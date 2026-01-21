<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$managedApp = null;
	$group = $cl->getMobileDeviceGroup($_GET['mobile_device_group_id'] ?? -1);
	foreach($db->selectAllManagedAppByMobileDeviceGroupId($group->id) as $ma) {
		if($ma->id == ($_GET['managed_app_id'] ?? -1))
			$managedApp = $ma;
	}
	if(!$managedApp) throw new NotFoundException();
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('group'); ?></th>
		<td>
			<?php echo htmlspecialchars($group->name); ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('app'); ?></th>
		<td>
			<?php echo htmlspecialchars($managedApp->name); ?>
		</td>
	</tr>

	<?php if($managedApp->type == Models\ManagedApp::TYPE_IOS) { ?>
	<tr>
		<th><?php echo LANG('removable'); ?></th>
		<td>
			<?php Html::dictTable(boolval($managedApp->removable)); ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('disable_cloud_backup'); ?></th>
		<td>
			<?php Html::dictTable(boolval($managedApp->disable_cloud_backup)); ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('remove_when_leaving_mdm'); ?></th>
		<td>
			<?php Html::dictTable(boolval($managedApp->remove_on_mdm_remove)); ?>
		</td>
	</tr>
	<?php } elseif($managedApp->type == Models\ManagedApp::TYPE_ANDROID) { ?>
	<tr>
		<th><?php echo LANG('installation'); ?></th>
		<td>
			<?php echo LANG(Models\ManagedApp::ANDROID_INSTALL_TYPES[$managedApp->install_type] ?? ''); ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('delegated_scopes'); ?></th>
		<td>
			<ul>
			<?php
			foreach(explode("\n", $managedApp->delegated_scopes) as $scope)
				if(!empty($scope))
					echo '<li>'.LANG(Models\ManagedApp::ANDROID_DELEGATED_SCOPES[$scope]).'</li>';
			?>
			</ul>
		</td>
	</tr>
	<?php } ?>

	<tr>
		<th><?php echo LANG('app_config'); ?></th>
		<td>
			<div>
			<?php if(!empty($managedApp->config_id)) {
				$configs = json_decode($managedApp->configurations ?? '{}', true);
				echo htmlspecialchars($configs[$managedApp->config_id] ?? '?');
			} ?>
			</div>
			<?php Html::dictTable(json_decode($managedApp->config ?? '{}', true)); ?>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='removeManagedAppFromGroup([<?php echo $managedApp->id; ?>],<?php echo $group->id; ?>)'><img src='img/remove.dyn.svg'>&nbsp;<?php echo LANG('remove_assignment'); ?></button>
</div>
