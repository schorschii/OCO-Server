<?php
// iOS device location view. Apple only returns DeviceLocation while Managed Lost Mode is active.
$locationInfo = json_decode($md->info ?? '', true) ?? [];
$isSupervised = !empty($locationInfo['IsSupervised']);
$isLostModeEnabled = !empty($locationInfo['IsMDMLostModeEnabled']);
$locationServiceEnabled = $locationInfo['IsDeviceLocatorServiceEnabled'] ?? null;
$deviceLocation = null;
$lastLostModeCommand = null;

foreach($db->selectAllMobileDeviceCommandByMobileDevice($md->id, false) as $command) {
	if($lastLostModeCommand === null
	&& $command->state == Models\MobileDeviceCommand::STATE_SUCCESS
	&& in_array($command->name, ['EnableLostMode', 'DisableLostMode'])) {
		$lastLostModeCommand = $command;
	}

	if($deviceLocation !== null
	|| $command->name !== 'DeviceLocation'
	|| $command->state != Models\MobileDeviceCommand::STATE_SUCCESS
	|| empty($command->message)) {
		continue;
	}

	try {
		$locationPlist = new CFPropertyList\CFPropertyList();
		$locationPlist->parse($command->message);
		$locationResponse = $locationPlist->toArray();
		if(isset($locationResponse['Latitude'], $locationResponse['Longitude'])) {
			$deviceLocation = $locationResponse;
		}
	} catch(Exception $e) {}
}

// The inventory may still contain the previous Lost Mode state immediately after a successful command.
if($lastLostModeCommand !== null
&& !empty($lastLostModeCommand->finished)
&& (empty($md->last_update) || strtotime($lastLostModeCommand->finished) > strtotime($md->last_update))) {
	$isLostModeEnabled = ($lastLostModeCommand->name === 'EnableLostMode');
}

$langCode = strtolower(substr(LanguageController::getSingleton()->getCurrentLangCode() ?? 'en', 0, 2));
$labels = [
	'en' => [
		'title' => 'Location',
		'refresh' => 'Refresh Location',
		'last' => 'Last Located',
		'latitude' => 'Latitude',
		'longitude' => 'Longitude',
		'accuracy' => 'Accuracy',
		'google_maps' => 'Open in Google Maps',
		'requires_lost_mode' => 'The device must be in Lost Mode before its location can be requested.',
		'requires_supervision' => 'Location requests require a supervised iOS device.',
		'none' => 'No device location has been received yet.',
	],
	'de' => [
		'title' => 'Standort',
		'refresh' => 'Standort aktualisieren',
		'last' => 'Zuletzt geortet',
		'latitude' => 'Breitengrad',
		'longitude' => 'Längengrad',
		'accuracy' => 'Genauigkeit',
		'google_maps' => 'In Google Maps öffnen',
		'requires_lost_mode' => 'Für die Standortbestimmung muss sich das Gerät im Verloren-Modus befinden.',
		'requires_supervision' => 'Die Standortbestimmung erfordert ein betreutes (Supervised) iOS-Gerät.',
		'none' => 'Es wurde noch kein Standort vom Gerät übermittelt.',
	],
	'fr' => [
		'title' => 'Localisation',
		'refresh' => 'Actualiser la localisation',
		'last' => 'Dernière localisation',
		'latitude' => 'Latitude',
		'longitude' => 'Longitude',
		'accuracy' => 'Précision',
		'google_maps' => 'Ouvrir dans Google Maps',
		'requires_lost_mode' => 'L’appareil doit être en mode perdu avant que sa localisation puisse être demandée.',
		'requires_supervision' => 'La demande de localisation nécessite un appareil iOS supervisé.',
		'none' => 'Aucune localisation de l’appareil n’a encore été reçue.',
	],
];
$locationLang = $labels[$langCode] ?? $labels['en'];
$googleMapsUrl = null;
if($deviceLocation !== null) {
	$googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query='.
		rawurlencode($deviceLocation['Latitude'].','.$deviceLocation['Longitude']);
}
?>

<div class='controls heading'>
	<h2><?php echo htmlspecialchars($locationLang['title']); ?></h2>
	<div class='filler invisible'></div>
	<button onclick='requestIosDeviceLocation(<?php echo $md->id; ?>);return false'
		<?php if(!$permissionDeploy || !$isSupervised || !$isLostModeEnabled) echo 'disabled'; ?>>
		<img src='img/refresh.dyn.svg'>&nbsp;<?php echo htmlspecialchars($locationLang['refresh']); ?>
	</button>
</div>
<table class='list metadata'>
	<tr>
		<th><?php echo LANG('supervised'); ?></th>
		<td><?php Html::dictTable($isSupervised); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG('lost_mode'); ?></th>
		<td><?php Html::dictTable($isLostModeEnabled); ?></td>
	</tr>
	<tr>
		<th><?php echo LANG('device_locator_service'); ?></th>
		<td><?php Html::dictTable($locationServiceEnabled); ?></td>
	</tr>
	<?php if($deviceLocation !== null) { ?>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['last']); ?></th>
		<td><?php echo htmlspecialchars($deviceLocation['Timestamp'] ?? ''); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['latitude']); ?></th>
		<td><?php echo htmlspecialchars(number_format((float)$deviceLocation['Latitude'], 6, '.', '')); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['longitude']); ?></th>
		<td><?php echo htmlspecialchars(number_format((float)$deviceLocation['Longitude'], 6, '.', '')); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['accuracy']); ?></th>
		<td><?php
			$horizontalAccuracy = $deviceLocation['HorizontalAccuracy'] ?? null;
			echo ($horizontalAccuracy !== null && (float)$horizontalAccuracy >= 0)
				? htmlspecialchars(round((float)$horizontalAccuracy, 1).' m')
				: '-';
		?></td>
	</tr>
	<?php } ?>
</table>

<?php if(!$isSupervised) { ?>
	<div class='alert warning'><?php echo htmlspecialchars($locationLang['requires_supervision']); ?></div>
<?php } elseif(!$isLostModeEnabled) { ?>
	<div class='alert warning'><?php echo htmlspecialchars($locationLang['requires_lost_mode']); ?></div>
<?php } elseif($deviceLocation === null) { ?>
	<div class='alert info'><?php echo htmlspecialchars($locationLang['none']); ?></div>
<?php } ?>

<?php if($googleMapsUrl !== null) { ?>
	<div class='controls'>
		<button onclick='window.open(this.getAttribute("data-url"), "_blank", "noopener,noreferrer");return false'
			data-url='<?php echo htmlspecialchars($googleMapsUrl, ENT_QUOTES); ?>'>
			<img src='img/eye.dyn.svg'>&nbsp;<?php echo htmlspecialchars($locationLang['google_maps']); ?>
		</button>
	</div>
<?php } ?>

<script>
function requestIosDeviceLocation(mobileDeviceId) {
	var params = [];
	params.push({'key':'send_command_to_mobile_device_id', 'value':mobileDeviceId});
	params.push({'key':'command', 'value':'DeviceLocation'});
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
</script>
