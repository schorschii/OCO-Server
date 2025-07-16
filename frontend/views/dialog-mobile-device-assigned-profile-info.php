<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$profile = null;
	$group = $cl->getMobileDeviceGroup($_GET['mobile_device_group_id'] ?? -1);
	foreach($db->selectAllProfileByMobileDeviceGroupId($group->id) as $p) {
		if($p->id == ($_GET['profile_id'] ?? -1))
			$profile = $p;
	}
	if(!$profile) throw new NotFoundException();
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
		<th><?php echo LANG('profile_policy'); ?></th>
		<td>
			<?php echo htmlspecialchars($profile->name); ?>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='removeProfileFromGroup([<?php echo $profile->id; ?>],<?php echo $group->id; ?>)'><img src='img/remove.dyn.svg'>&nbsp;<?php echo LANG('remove_assignment'); ?></button>
</div>
