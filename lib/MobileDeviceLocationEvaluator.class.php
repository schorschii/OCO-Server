<?php

class MobileDeviceLocationEvaluator {
	public static function getHorizontalAccuracy(?array $deviceLocation): ?float {
		$accuracy = $deviceLocation['HorizontalAccuracy'] ?? null;
		return is_numeric($accuracy) && (float)$accuracy >= 0 ? (float)$accuracy : null;
	}

	public static function evaluate(array $deviceInformation, array $commands, $lastInventoryUpdate, callable $parseResponse): array {
		$isSupervised = array_key_exists('IsSupervised', $deviceInformation) ? (bool)$deviceInformation['IsSupervised'] : null;
		$isLostModeEnabled = array_key_exists('IsMDMLostModeEnabled', $deviceInformation) ? (bool)$deviceInformation['IsMDMLostModeEnabled'] : null;
		$deviceLocation = null;
		$latestLocationCommand = null;
		$latestLostModeCommand = null;
		$latestSuccessfulLostModeCommand = null;

		foreach($commands as $command) {
			$commandState = (int)$command->state;
			if($latestLostModeCommand === null && in_array($command->name, ['EnableLostMode', 'DisableLostMode'], true)) {
				$latestLostModeCommand = $command;
			}
			if($latestSuccessfulLostModeCommand === null
			&& $commandState === Models\MobileDeviceCommand::STATE_SUCCESS
			&& in_array($command->name, ['EnableLostMode', 'DisableLostMode'], true)) {
				$latestSuccessfulLostModeCommand = $command;
			}

			if($command->name !== 'DeviceLocation') continue;
			if($latestLocationCommand === null) $latestLocationCommand = $command;
			if($deviceLocation !== null
			|| $commandState !== Models\MobileDeviceCommand::STATE_SUCCESS
			|| empty($command->message)) continue;

			try {
				$locationResponse = $parseResponse($command->message);
				if(!is_array($locationResponse)) continue;
				$latitude = $locationResponse['Latitude'] ?? null;
				$longitude = $locationResponse['Longitude'] ?? null;
				if(!is_numeric($latitude) || !is_numeric($longitude)) continue;
				$latitude = (float)$latitude;
				$longitude = (float)$longitude;
				if($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) continue;
				$locationResponse['Latitude'] = $latitude;
				$locationResponse['Longitude'] = $longitude;
				$deviceLocation = $locationResponse;
			} catch(Exception $e) {}
		}

		if($latestSuccessfulLostModeCommand !== null
		&& !empty($latestSuccessfulLostModeCommand->finished)
		&& (empty($lastInventoryUpdate) || strtotime($latestSuccessfulLostModeCommand->finished) > strtotime($lastInventoryUpdate))) {
			$isLostModeEnabled = ($latestSuccessfulLostModeCommand->name === 'EnableLostMode');
		}

		return [
			'isSupervised' => $isSupervised,
			'isLostModeEnabled' => $isLostModeEnabled,
			'deviceLocation' => $deviceLocation,
			'locationRequestPending' => $latestLocationCommand !== null && in_array((int)$latestLocationCommand->state, [Models\MobileDeviceCommand::STATE_QUEUED, Models\MobileDeviceCommand::STATE_SENT], true),
			'locationRequestFailed' => $latestLocationCommand !== null && (int)$latestLocationCommand->state === Models\MobileDeviceCommand::STATE_FAILED,
			'lostModeCommandPending' => $latestLostModeCommand !== null && in_array((int)$latestLostModeCommand->state, [Models\MobileDeviceCommand::STATE_QUEUED, Models\MobileDeviceCommand::STATE_SENT], true),
		];
	}
}
