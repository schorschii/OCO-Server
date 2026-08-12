<?php
// iOS device location view. Apple only returns DeviceLocation while Managed Lost Mode is active.
$locationInfo = json_decode($md->info ?? '', true) ?? [];

// OCO currently maps macOS to OS_TYPE_IOS as well. DeviceLocation is only available on iOS/Shared iPad.
$productName = $locationInfo['ProductName'] ?? '';
$isMacOs = stripos($md->os ?? '', 'macOS') !== false || stripos($productName, 'Mac') !== false;
if($isMacOs || !$permissionDeploy) return;

$locationState = MobileDeviceLocationEvaluator::evaluate($locationInfo, $mobileDeviceCommands, $md->last_update ?? null, function($message) {
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

$googleMapsUrl = null;
if($deviceLocation !== null) {
	$googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query='.
		rawurlencode($deviceLocation['Latitude'].','.$deviceLocation['Longitude']);
}

$buttonId = 'btnIosDeviceLocation-'.$md->id;
$locationButtonDisabled = $isSupervised !== true
	|| $isLostModeEnabled !== true
	|| $locationRequestPending
	|| $lostModeCommandPending;
?>

<div class='controls heading'>
	<h2><?php echo LANG('device_location_title'); ?></h2>
	<div class='filler invisible'></div>
	<button id='<?php echo htmlspecialchars($buttonId, ENT_QUOTES); ?>' <?php if($locationButtonDisabled) echo 'disabled'; ?>>
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

<?php if($googleMapsUrl !== null) { ?>
	<div class='controls'>
		<button onclick='window.open(this.getAttribute("data-url"), "_blank", "noopener,noreferrer");return false'
			data-url='<?php echo htmlspecialchars($googleMapsUrl, ENT_QUOTES); ?>'>
			<img src='img/eye.dyn.svg'>&nbsp;<?php echo LANG('device_location_google_maps'); ?>
		</button>
	</div>
<?php } ?>

<script>
(function() {
	var button = document.getElementById(<?php echo json_encode($buttonId); ?>);
	if(!button) return;
	button.addEventListener('click', function(event) {
		event.preventDefault();
		button.disabled = true;
		var params = [];
		params.push({'key':'send_command_to_mobile_device_id', 'value':<?php echo intval($md->id); ?>});
		params.push({'key':'command', 'value':'DeviceLocation'});
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
			refreshContent();
			emitMessage(<?php echo json_encode(LANG('device_location_requested')); ?>, '', MESSAGE_TYPE_SUCCESS);
		}, function() {
			button.disabled = false;
		});
	});
})();
</script>
