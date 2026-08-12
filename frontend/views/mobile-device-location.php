<?php
// iOS device location view. Apple only returns DeviceLocation while Managed Lost Mode is active.
$locationInfo = json_decode($md->info ?? '', true) ?? [];

// OCO currently maps macOS to OS_TYPE_IOS as well. DeviceLocation is only available on iOS/Shared iPad.
$productName = $locationInfo['ProductName'] ?? '';
$isMacOs = stripos($md->os ?? '', 'macOS') !== false || stripos($productName, 'Mac') !== false;
if($isMacOs || !$permissionDeploy) return;

$isSupervised = array_key_exists('IsSupervised', $locationInfo) ? (bool)$locationInfo['IsSupervised'] : null;
$isLostModeEnabled = array_key_exists('IsMDMLostModeEnabled', $locationInfo) ? (bool)$locationInfo['IsMDMLostModeEnabled'] : null;
$deviceLocation = null;
$latestLocationCommand = null;
$latestLostModeCommand = null;
$latestSuccessfulLostModeCommand = null;

$commands = $db->selectAllMobileDeviceCommandByMobileDevice($md->id, false);
foreach($commands as $command) {
	if($latestLostModeCommand === null
	&& in_array($command->name, ['EnableLostMode', 'DisableLostMode'], true)) {
		$latestLostModeCommand = $command;
	}
	if($latestSuccessfulLostModeCommand === null
	&& $command->state == Models\MobileDeviceCommand::STATE_SUCCESS
	&& in_array($command->name, ['EnableLostMode', 'DisableLostMode'], true)) {
		$latestSuccessfulLostModeCommand = $command;
	}

	if($command->name !== 'DeviceLocation') continue;
	if($latestLocationCommand === null) $latestLocationCommand = $command;
	if($deviceLocation !== null
	|| $command->state != Models\MobileDeviceCommand::STATE_SUCCESS
	|| empty($command->message)) {
		continue;
	}

	try {
		$locationPlist = new CFPropertyList\CFPropertyList();
		$locationPlist->parse($command->message);
		$locationResponse = $locationPlist->toArray();
		$latitude = $locationResponse['Latitude'] ?? null;
		$longitude = $locationResponse['Longitude'] ?? null;
		if(is_numeric($latitude) && is_numeric($longitude)) {
			$latitude = (float)$latitude;
			$longitude = (float)$longitude;
			if($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
				$locationResponse['Latitude'] = $latitude;
				$locationResponse['Longitude'] = $longitude;
				$deviceLocation = $locationResponse;
			}
		}
	} catch(Exception $e) {}
}

// The inventory may still contain the previous Lost Mode state immediately after a successful command.
if($latestSuccessfulLostModeCommand !== null
&& !empty($latestSuccessfulLostModeCommand->finished)
&& (empty($md->last_update) || strtotime($latestSuccessfulLostModeCommand->finished) > strtotime($md->last_update))) {
	$isLostModeEnabled = ($latestSuccessfulLostModeCommand->name === 'EnableLostMode');
}

$locationRequestPending = $latestLocationCommand !== null
	&& in_array($latestLocationCommand->state, [Models\MobileDeviceCommand::STATE_QUEUED, Models\MobileDeviceCommand::STATE_SENT], true);
$locationRequestFailed = $latestLocationCommand !== null
	&& $latestLocationCommand->state == Models\MobileDeviceCommand::STATE_FAILED;
$lostModeCommandPending = $latestLostModeCommand !== null
	&& in_array($latestLostModeCommand->state, [Models\MobileDeviceCommand::STATE_QUEUED, Models\MobileDeviceCommand::STATE_SENT], true);

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
		'supervision_unknown' => 'The supervision status is not available yet. Refresh the device information first.',
		'lost_mode_unknown' => 'The Lost Mode status is not available yet. Refresh the device information first.',
		'none' => 'No device location has been received yet.',
		'pending' => 'The location request is waiting for the device.',
		'failed' => 'The last location request failed. The last successful location is shown if available.',
		'lost_mode_pending' => 'A Lost Mode change is still waiting for the device.',
		'requested' => 'Location request queued',
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
		'supervision_unknown' => 'Der Supervised-Status ist noch nicht verfügbar. Bitte aktualisieren Sie zuerst die Geräteinformationen.',
		'lost_mode_unknown' => 'Der Status des Verloren-Modus ist noch nicht verfügbar. Bitte aktualisieren Sie zuerst die Geräteinformationen.',
		'none' => 'Es wurde noch kein Standort vom Gerät übermittelt.',
		'pending' => 'Die Standortabfrage wartet auf eine Antwort des Geräts.',
		'failed' => 'Die letzte Standortabfrage ist fehlgeschlagen. Falls vorhanden, wird der letzte erfolgreiche Standort angezeigt.',
		'lost_mode_pending' => 'Eine Änderung des Verloren-Modus wartet noch auf das Gerät.',
		'requested' => 'Standortabfrage wurde eingereiht',
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
		'supervision_unknown' => 'L’état de supervision n’est pas encore disponible. Actualisez d’abord les informations de l’appareil.',
		'lost_mode_unknown' => 'L’état du mode perdu n’est pas encore disponible. Actualisez d’abord les informations de l’appareil.',
		'none' => 'Aucune localisation de l’appareil n’a encore été reçue.',
		'pending' => 'La demande de localisation attend une réponse de l’appareil.',
		'failed' => 'La dernière demande de localisation a échoué. La dernière localisation réussie est affichée si elle est disponible.',
		'lost_mode_pending' => 'Une modification du mode perdu attend toujours l’appareil.',
		'requested' => 'Demande de localisation mise en file d’attente',
	],
];
$locationLang = $labels[$langCode] ?? $labels['en'];

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
	<h2><?php echo htmlspecialchars($locationLang['title']); ?></h2>
	<div class='filler invisible'></div>
	<button id='<?php echo htmlspecialchars($buttonId, ENT_QUOTES); ?>' <?php if($locationButtonDisabled) echo 'disabled'; ?>>
		<img src='img/refresh.dyn.svg'>&nbsp;<?php echo htmlspecialchars($locationLang['refresh']); ?>
	</button>
</div>

<?php if($deviceLocation !== null) { ?>
<table class='list metadata'>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['last']); ?></th>
		<td><?php echo htmlspecialchars($deviceLocation['Timestamp'] ?? ''); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['latitude']); ?></th>
		<td><?php echo htmlspecialchars(number_format($deviceLocation['Latitude'], 6, '.', '')); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['longitude']); ?></th>
		<td><?php echo htmlspecialchars(number_format($deviceLocation['Longitude'], 6, '.', '')); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars($locationLang['accuracy']); ?></th>
		<td><?php
			$horizontalAccuracy = $deviceLocation['HorizontalAccuracy'] ?? null;
			echo ($horizontalAccuracy !== null && is_numeric($horizontalAccuracy) && (float)$horizontalAccuracy >= 0)
				? htmlspecialchars(round((float)$horizontalAccuracy, 1).' m')
				: '-';
		?></td>
	</tr>
</table>
<?php } ?>

<?php if($isSupervised === false) { ?>
	<div class='alert warning'><?php echo htmlspecialchars($locationLang['requires_supervision']); ?></div>
<?php } elseif($isSupervised === null) { ?>
	<div class='alert info'><?php echo htmlspecialchars($locationLang['supervision_unknown']); ?></div>
<?php } elseif($lostModeCommandPending) { ?>
	<div class='alert info'><?php echo htmlspecialchars($locationLang['lost_mode_pending']); ?></div>
<?php } elseif($isLostModeEnabled === false) { ?>
	<div class='alert warning'><?php echo htmlspecialchars($locationLang['requires_lost_mode']); ?></div>
<?php } elseif($isLostModeEnabled === null) { ?>
	<div class='alert info'><?php echo htmlspecialchars($locationLang['lost_mode_unknown']); ?></div>
<?php } elseif($locationRequestPending) { ?>
	<div class='alert info'><?php echo htmlspecialchars($locationLang['pending']); ?></div>
<?php } elseif($locationRequestFailed) { ?>
	<div class='alert warning'><?php echo htmlspecialchars($locationLang['failed']); ?></div>
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
			emitMessage(<?php echo json_encode($locationLang['requested']); ?>, '', MESSAGE_TYPE_SUCCESS);
		}, function() {
			button.disabled = false;
		});
	});
})();
</script>
