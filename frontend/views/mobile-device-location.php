<?php
// iOS device location view. Apple only returns DeviceLocation while Managed Lost Mode is active.
if(empty($showDeviceLocationTab) || !$permissionDeploy) return;

$locationState = MobileDeviceLocationEvaluator::evaluate($info, $mobileDeviceCommands, $md->last_update ?? null, function($message) {
	$locationPlist = new CFPropertyList\CFPropertyList();
	$locationPlist->parse($message);
	return $locationPlist->toArray();
});
$isSupervised = $locationState['isSupervised'];
$isLostModeEnabled = $locationState['isLostModeEnabled'];
$deviceLocation = $locationState['deviceLocation'];
$locationRequestPending = $locationState['locationRequestPending'];
$locationRequestFailed = $locationState['locationRequestFailed'];
$lostModeCommandPending = $locationState['lostModeCommandPending'];

$openStreetMapUrl = null;
if($deviceLocation !== null) {
	$latitude = number_format($deviceLocation['Latitude'], 6, '.', '');
	$longitude = number_format($deviceLocation['Longitude'], 6, '.', '');
	$openStreetMapUrl = 'https://www.openstreetmap.org/?mlat='.rawurlencode($latitude).
		'&mlon='.rawurlencode($longitude).'#map=18/'.rawurlencode($latitude).'/'.rawurlencode($longitude);
}

$locationButtonDisabled = $isSupervised !== true
	|| $isLostModeEnabled !== true
	|| $locationRequestPending
	|| $lostModeCommandPending;
?>

<div class='controls heading'>
	<h2><?php echo LANG('device_location_title'); ?></h2>
	<div class='filler invisible'></div>
	<button onclick='requestMobileDeviceLocation(this, <?php echo intval($md->id); ?>);return false' <?php if($locationButtonDisabled) echo 'disabled'; ?>>
		<img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('device_location_refresh'); ?>
	</button>
</div>

<?php if($deviceLocation !== null) { ?>
<table class='list metadata'>
	<tr>
		<th><?php echo LANG('device_location_last'); ?></th>
		<td><?php echo htmlspecialchars($deviceLocation['Timestamp'] ?? ''); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG('latitude'); ?></th>
		<td><?php echo htmlspecialchars(number_format($deviceLocation['Latitude'], 6, '.', '')); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG('longitude'); ?></th>
		<td><?php echo htmlspecialchars(number_format($deviceLocation['Longitude'], 6, '.', '')); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG('accuracy'); ?></th>
		<td><?php
			$horizontalAccuracy = MobileDeviceLocationEvaluator::getHorizontalAccuracy($deviceLocation);
			echo $horizontalAccuracy !== null
				? htmlspecialchars(round($horizontalAccuracy, 1).' m')
				: '-';
		?></td>
	</tr>
</table>
<?php } ?>

<?php if($isSupervised === false) { ?>
	<div class='alert warning'><?php echo LANG('device_location_requires_supervision'); ?></div>
<?php } elseif($isSupervised === null) { ?>
	<div class='alert info'><?php echo LANG('device_location_supervision_unknown'); ?></div>
<?php } elseif($lostModeCommandPending) { ?>
	<div class='alert info'><?php echo LANG('device_location_lost_mode_pending'); ?></div>
<?php } elseif($isLostModeEnabled === false) { ?>
	<div class='alert warning'><?php echo LANG('device_location_requires_lost_mode'); ?></div>
<?php } elseif($isLostModeEnabled === null) { ?>
	<div class='alert info'><?php echo LANG('device_location_lost_mode_unknown'); ?></div>
<?php } elseif($locationRequestPending) { ?>
	<div class='alert info'><?php echo LANG('device_location_pending'); ?></div>
<?php } elseif($locationRequestFailed) { ?>
	<div class='alert warning'><?php echo LANG('device_location_failed'); ?></div>
<?php } elseif($deviceLocation === null) { ?>
	<div class='alert info'><?php echo LANG('device_location_none'); ?></div>
<?php } ?>

<?php if($openStreetMapUrl !== null) { ?>
	<div class='controls'>
		<button onclick='window.open(this.getAttribute("data-url"), "_blank", "noopener,noreferrer");return false'
			data-url='<?php echo htmlspecialchars($openStreetMapUrl, ENT_QUOTES); ?>'>
			<img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('device_location_openstreetmap'); ?>
		</button>
	</div>
<?php } ?>
