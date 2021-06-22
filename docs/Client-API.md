# General
This document describes the JSON-REST-API provided by the OCO server. By implementing this protocol you can automate your workflows and integrate OCO into your environment to make it more convenient.

You first need to activate the API in the OCO config file:
```
const CLIENT_API_ENABLED = true; # this is false by default
```

# The JSON-RPC Package
A valid JSON-RPC request is sent via HTTP(S) with the HTTP header `Content-Type: application/json` to the API endpoint `api-client.php`.

HTTP Basic Authentication is used for client authentication. Please provide the HTTP header `Authorization: Basic <Base64-Auth-String>` with all API requests.

Localized error messages are available if you set the HTTP header `Accept-Language: de`.

Please have a look at the following API method documentation for JSON-RPC examples.

# Methods
## `oco.computer.list` - List All Computers
### Parameters
no parameters
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.computer.list",
	"params": {}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": [
			{
				"id": "164",
				"hostname": "PC01",
				"os": "Windows 10 Home",
				"os_version": "10.0.19042",
				"kernel_version": "-",
				"architecture": "AMD64",
				"cpu": "Intel64 Family 6 Model 158 Stepping 9, GenuineIntel",
				"gpu": "Intel(R) HD Graphics 630",
				"ram": "17011314688",
				"agent_version": "1.0.0",
				"serial": "ABC123",
				"manufacturer": "FUJITSU // American Megatrends Inc.",
				"model": "ESPRIMO Q556/2",
				"bios_version": "11700000",
				"boot_type": "Legacy",
				"secure_boot": "0",
				"last_ping": "2021-06-22 14:56:36",
				"last_update": "2021-06-22 14:56:36",
				"notes": "",
				"agent_key": "abc123",
				"server_key": "abc123",
				"software_version": null,
				"computer_network_mac": null,
				"os_license": "1",
				"os_locale": "0407"
			},
			.........................
		]
	}
}
```

## `oco.computer.get` - Get Computer Details
### Parameters
- `id` - computer ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.computer.get",
	"params": {
		"id": 123
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": {
			"general": {
				"id": "116",
				"hostname": "VM002",
				"os": "Windows 7 Home Premium",
				"os_version": "6.1.7601",
				"kernel_version": "-",
				"architecture": "AMD64",
				"cpu": "AMD64 Family 23 Model 8 Stepping 2, AuthenticAMD",
				"gpu": "VirtualBox Graphics Adapter (WDDM)",
				"ram": "4294500352",
				"agent_version": "0.8.0",
				"serial": "0",
				"manufacturer": "innotek GmbH",
				"model": "VirtualBox",
				"bios_version": "VBOX   - 1",
				"boot_type": "Legacy",
				"secure_boot": "0",
				"last_ping": "2021-04-25 17:01:09",
				"last_update": "2021-04-25 17:01:09",
				"notes": "",
				"agent_key": "abc123",
				"server_key": "123abc",
				"os_license": "0",
				"os_locale": "0407"
			},
			"logins": [
				{
					"id": null,
					"domainuser_id": "8",
					"console": null,
					"timestamp": "2020-11-17 12:33:31",
					"logon_amount": "5",
					"computer_hostname": null,
					"domainuser_username": "hulk"
				}
			],
			"networks": [
				{
					"id": "1838",
					"nic_number": "0",
					"addr": "10.0.2.15",
					"netmask": "255.255.255.0",
					"broadcast": "10.0.2.255",
					"mac": "08:00:27:31:5b:c4",
					"domain": "vm2-PC"
				}
			],
			"screens": [],
			"printers": [
				{
					"id": "20",
					"computer_id": "116",
					"name": "Microsoft XPS Document Writer",
					"driver": "Microsoft XPS Document Writer",
					"paper": "A3, A4, A5, B4 (JIS), B5 (JIS)",
					"dpi": "600",
					"uri": "XPSPort:",
					"status": "Idle"
				}
			],
			"filesystems": [
				{
					"id": "51",
					"computer_id": "116",
					"device": "\\\\?\\Volume{2c2baaea-a0ae-11e9-84ab-806e6f6e6963}\\",
					"mountpoint": "C:",
					"filesystem": "NTFS",
					"size": "34252779520",
					"free": "600092672"
				}
			],
			"recognised_software": [
				{
					"id": "86519",
					"software_id": "2",
					"version": "2.2.1",
					"installed": null,
					"software_name": "VLC media player",
					"software_description": "Copyright © 1996-2020 the VideoLAN team"
				}
			],
			"installed_packages": [
				{
					"id": "104",
					"computer_id": null,
					"package_id": "109",
					"installed_procedure": "msiexec /quiet /i cdbxp_setup_x64_4.5.0.3661.msi",
					"installed": "2021-04-25 17:01:13",
					"package_name": "CDBurnerXP",
					"package_version": "1.0",
					"package_family_id": "3"
				}
			],
			"pending_jobs": [
				{
					"id": "988",
					"job_container_id": "203",
					"computer_id": null,
					"package_id": "113",
					"package_procedure": null,
					"success_return_codes": null,
					"is_uninstall": "0",
					"download": "0",
					"restart": "-1",
					"shutdown": "-1",
					"sequence": null,
					"state": "0",
					"return_code": null,
					"message": null,
					"last_update": null,
					"package_name": "Test Package",
					"package_version": "1",
					"job_container_start_time": "2021-06-22 21:27:00",
					"job_container_name": "Install VM002",
					"procedure": "msiexec /quiet /i test.msi"
				}
			]
		}
	}
}
```

## `oco.computer.create` - Create A New Computer (Pre-Registration)
### Parameters
- `hostname` - the host name of the new computer
- `notes` (optional) - a description
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.computer.create",
	"params": {
		"hostname": "PC01",
		"notes": "My new computer."
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": []
	}
}
```

## `oco.computer.wol` - Try To Start Computer Via Network (Wake On Lan)
### Parameters
- `id` - computer ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.computer.wol",
	"params": {
		"id": 123
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": []
	}
}
```

## `oco.computer.remove` - Remove Computer
### Parameters
- `id` - computer ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.computer.remove",
	"params": {
		"id": 123
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": []
	}
}
```

## `oco.package_family.list` - List All Package Families
### Parameters
no parameters
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.package_family.list",
	"params": {}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": [
			{
				"id": "3",
				"name": "CDBurnerXP",
				"notes": ""
			}
		]
	}
}
```

## `oco.package.list` - List All Packages Of A Package Family
### Parameters
- `ìd` - package family ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.package.list",
	"params": {
		"id": 3
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": [
			{
				"id": "108",
				"package_family_id": "3",
				"version": "4.5.8.7128",
				"notes": "",
				"author": "root",
				"install_procedure": "msiexec /quiet /i cdbxp_setup_x64_4.5.8.7128.msi",
				"install_procedure_success_return_codes": "0",
				"install_procedure_restart": "0",
				"install_procedure_shutdown": "0",
				"uninstall_procedure": "msiexec /quiet /x cdbxp_setup_x64_4.5.8.7128.msi",
				"uninstall_procedure_success_return_codes": "1",
				"download_for_uninstall": "1",
				"uninstall_procedure_restart": "0",
				"uninstall_procedure_shutdown": "0",
				"created": "2021-04-24 18:13:12",
				"last_update": "2021-06-15 23:44:02",
				"package_group_member_sequence": null,
				"name": "CDBurnerXP"
			},
			{
				"id": "109",
				"package_family_id": "3",
				"version": "4.5.0.3661",
				"notes": "",
				"author": "root",
				"install_procedure": "msiexec /quiet /i cdbxp_setup_x64_4.5.0.3661.msi",
				"install_procedure_success_return_codes": "0",
				"install_procedure_restart": "0",
				"install_procedure_shutdown": "0",
				"uninstall_procedure": "msiexec /quiet /x cdbxp_setup_x64_4.5.0.3661.msi",
				"uninstall_procedure_success_return_codes": "0",
				"download_for_uninstall": "1",
				"uninstall_procedure_restart": "0",
				"uninstall_procedure_shutdown": "0",
				"created": "2021-04-24 18:15:51",
				"last_update": "2021-04-24 18:15:51",
				"package_group_member_sequence": null,
				"name": "CDBurnerXP"
			}
		]
	}
}
```

## `oco.job_container.list` - List All Job Containers
### Parameters
no parameters
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.job_container.list",
	"params": {}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": [
			{
				"id": "203",
				"name": "Installieren VM002",
				"start_time": "2021-06-22 21:27:00",
				"end_time": null,
				"notes": "",
				"wol_sent": "-1",
				"created": "2021-06-22 21:27:35",
				"last_update": "2021-06-22 21:27:36",
				"author": "admin"
			}
		]
	}
}
```

## `oco.job.list` - List All Jobs Of A Job Container
### Parameters
- `ìd` - job container ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.job.list",
	"params": {
		"id": 203
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": [
			{
				"id": "988",
				"job_container_id": "203",
				"computer_id": "116",
				"package_id": "113",
				"package_procedure": "msiexec /quiet /i test.msi",
				"success_return_codes": "0",
				"is_uninstall": "0",
				"download": "0",
				"restart": "-1",
				"shutdown": "-1",
				"sequence": "1",
				"state": "0",
				"return_code": null,
				"message": "",
				"last_update": "2021-06-22 21:27:36",
				"computer_hostname": "VM002",
				"package_name": "Test Package",
				"package_version": "1.0",
				"job_container_start_time": "2021-06-22 21:27:00"
			}
		]
	}
}
```

# Data Format
- Date String: `2020-01-01 18:00:00`
