<?php

namespace Apple;

class MdmCommand {

	const DEVICE_INFO = [
		'RequestType' => 'DeviceInformation',
		'Queries' => [ // https://developer.apple.com/documentation/devicemanagement/deviceinformationcommand/command/queries
			'AccessibilitySettings','ActiveManagedUsers','AppAnalyticsEnabled','AutoSetupAdminAccounts','AvailableDeviceCapacity',
			'AwaitingConfiguration','BatteryLevel','BluetoothMAC','BuildVersion','CellularTechnology','DataRoamingEnabled',
			'DeviceCapacity','DeviceID','DeviceName','DiagnosticSubmissionEnabled','EACSPreflight',
			'EASDeviceIdentifier','EstimatedResidentUsers','EthernetMAC','HasBattery','HostName','IsActivationLockSupported','IsAppleSilicon',
			'IsCloudBackupEnabled','IsDeviceLocatorServiceEnabled','IsDoNotDisturbInEffect','IsMDMLostModeEnabled','IsMultiUser','IsNetworkTethered',
			'IsRoaming','IsSupervised','iTunesStoreAccountHash','iTunesStoreAccountIsActive','LastCloudBackupDate','LocalHostName','ManagedAppleIDDefaultDomains',
			'MaximumResidentUsers','MDMOptions','Model','ModelName','ModemFirmwareVersion','ModelNumber','OnlineAuthenticationGracePeriod','OrganizationInfo',
			'OSUpdateSettings','OSVersion','PersonalHotspotEnabled','PINRequiredForDeviceLock','PINRequiredForEraseDevice','ProductName','ProvisioningUDID',
			'PushToken','QuotaSize','ResidentUsers','SerialNumber','ServiceSubscriptions','SkipLanguageAndLocaleSetupForNewUsers','SoftwareUpdateDeviceID',
			'SoftwareUpdateSettings','SupplementalBuildVersion','SupplementalOSVersionExtra','SupportsiOSAppInstalls','SupportsLOMDevice',
			'SystemIntegrityProtectionEnabled','TemporarySessionOnly','TemporarySessionTimeout','TimeZone','UDID','UserSessionTimeout','WiFiMAC',
			// 'IMEI','ICCID','IsActivationLockEnabled','MEID','PhoneNumber' // deprecated
			// 'DevicePropertiesAttestation' is excluded since it is not of interest for now and needs to be handled specially (binary data is not json encodable)
		],
	];

	const APPS_INFO = [
		'RequestType' => 'InstalledApplicationList',
	];

}
