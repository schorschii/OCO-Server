# General
This document describes the JSON-REST-API provided by the OCO server. By implementing this protocol you can automate your workflows and integrate OCO into your environment to make it more convenient.

You first need to activate the API and set an individual API key in the OCO config file:
```
const CLIENT_API_ENABLED = true; # this is false by default
const CLIENT_API_KEY     = 'your custom key here...';
```

# The JSON-RPC Package
A valid JSON-RPC request is sent via HTTP(S) with the HTTP header `Content-Type: application/json` to the API endpoint `api-client.php`.

HTTP Basic Authentication is used for client authentication. Please provide the HTTP header `Authorization: Basic <Base64-Auth-String>` with all API requests.

Localized error messages are available if you set the HTTP header `Accept-Language: de`.

Within the `params` object, please send the correct `api_key` value and all required additional parameters for the method you are calling inside a `data` object.

Please have a look at the following API method documentation for JSON-RPC request/response examples.

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
	"params": {
		"api_key": "🌈💜👆🚧🛸💩"
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
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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
				"os_locale": "0407",
				"domain": "vm2-PC"
			},
			"logins": [
				{
					"id": null,
					"domain_user_id": "8",
					"console": null,
					"timestamp": "2020-11-17 12:33:31",
					"logon_amount": "5",
					"computer_hostname": null,
					"domain_user_username": "hulk"
				}
			],
			"networks": [
				{
					"id": "1838",
					"nic_number": "0",
					"address": "10.0.2.15",
					"netmask": "255.255.255.0",
					"broadcast": "10.0.2.255",
					"mac": "08:00:27:31:5b:c4",
					"interface": "Ethernet0"
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
					"package_family_name": "CDBurnerXP",
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
					"procedure": null,
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
					"package_family_name": "Test Package",
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
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"hostname": "PC01",
			"notes": "My new computer."
		}
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
			"id": 123
		]
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
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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
- `force` - (optional - default true) do not abort if there are pending jobs
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.computer.remove",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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
- `show_icons` - (optional - default false) output the package family icons as base64 string (attention: can produce large output, depending on how many icons you have set and on your icon size)
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.package_family.list",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"show_icons": true
		}
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
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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
				"package_family_name": "CDBurnerXP",
				"version": "4.5.8.7128",
				"notes": "",
				"author": "root",
				"install_procedure": "msiexec /quiet /i cdbxp_setup_x64_4.5.8.7128.msi",
				"install_procedure_success_return_codes": "0",
				"install_procedure_post_action": "0",
				"uninstall_procedure": "msiexec /quiet /x cdbxp_setup_x64_4.5.8.7128.msi",
				"uninstall_procedure_success_return_codes": "1",
				"download_for_uninstall": "1",
				"uninstall_procedure_post_action": "0",
				"created": "2021-04-24 18:13:12",
				"last_update": "2021-06-15 23:44:02",
				"package_group_member_sequence": null
			},
			{
				"id": "109",
				"package_family_id": "3",
				"package_family_name": "CDBurnerXP",
				"version": "4.5.0.3661",
				"notes": "",
				"author": "root",
				"install_procedure": "msiexec /quiet /i cdbxp_setup_x64_4.5.0.3661.msi",
				"install_procedure_success_return_codes": "0",
				"install_procedure_post_action": "0",
				"uninstall_procedure": "msiexec /quiet /x cdbxp_setup_x64_4.5.0.3661.msi",
				"uninstall_procedure_success_return_codes": "0",
				"download_for_uninstall": "1",
				"uninstall_procedure_post_action": "0",
				"created": "2021-04-24 18:15:51",
				"last_update": "2021-04-24 18:15:51",
				"package_group_member_sequence": null
			}
		]
	}
}
```

## `oco.package.get` - Get Package Details
### Parameters
- `ìd` - package ID
- `show_icons` - (optional - default false) output the package family icon as base64 string (attention: can produce large output, depending on your icon size)
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.package.get",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123,
			"show_icons": true
		}
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
				"id": "117",
				"package_family_id": "64",
				"version": "89",
				"notes": "",
				"author": "hanswurst",
				"install_procedure": "gdebi -n google-chrome-stable_current_amd64.deb",
				"install_procedure_success_return_codes": "0",
				"install_procedure_post_action": "0",
				"uninstall_procedure": "apt remove -y google-chrome",
				"uninstall_procedure_success_return_codes": "0",
				"download_for_uninstall": "0",
				"uninstall_procedure_post_action": "0",
				"compatible_os": "",
				"compatible_os_version": "",
				"created": "2021-03-03 15:52:22",
				"last_update": "2021-04-21 13:55:14",
				"package_group_member_sequence": null,
				"package_family_id": "13",
				"package_family_name": "Test Package 222",
				"package_family_icon": null,
				"self_service_enabled": "0"
			},
			"installations": [
				{
					"id": "289",
					"computer_id": "696",
					"installed_procedure": "gdebi -n google-chrome-stable_current_amd64.deb",
					"installed": "2021-03-22 10:13:56",
					"computer_hostname": "PC001",
					"package_family_name": null,
					"package_version": null
				}
			],
			"pending_jobs": [
				{
					"id": "3423",
					"job_container_id": "1049",
					"computer_id": "114",
					"success_return_codes": null,
					"is_uninstall": "0",
					"download": "1",
					"restart": "-1",
					"shutdown": "-1",
					"sequence": null,
					"state": "0",
					"return_code": null,
					"message": null,
					"wol_shutdown_set": null,
					"last_update": null,
					"computer_hostname": "PC002",
					"package_family_name": null,
					"package_version": null,
					"job_container_start_time": "2021-06-24 10:36:00",
					"job_container_name": "Installieren L_Chrome",
					"procedure": "gdebi -n google-chrome-stable_current_amd64.deb"
				}
			]
		}
	}
}
```

## `oco.package.create` - Create New Package
### Parameters
- `package_family_name` - package family name (will be created if not exists)
- `version` - package version
- `description` - package description (optional)
- `install_procedure` - install procedure (install command)
- `install_procedure_success_return_codes` - install procedure success return codes (comma separated, leave empty to disable return code check)
- `install_procedure_post_action` - enable/disable restart, shutdown or agent restart after installation (0, 1, 2 or 3)
- `uninstall_procedure` - uninstall procedure (uninstall command) (optional)
- `uninstall_procedure_success_return_codes` - uninstall procedure success return codes (comma separated, leave empty to disable return code check)
- `download_for_uninstall` - enable/disable download for uninstallation (0 or 1)
- `uninstall_procedure_post_action` - enable/disable restart or shutdown after uninstallation (0, 1 or 2)
- `compatible_os` - compatible operating system name (optional)
- `compatible_os_version` - compatible operating systen version (optional)
- `file` - package payload: base64 encoded ZIP file content (optional - leave empty to create a package without payload)
- `file_name` - file name for usage in (un)install procedure (if base64 encoded content is NOT a ZIP file) (optional)
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.package.create",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"package_family_name": "My Test App",
			"version": "1.1",
			"description": "For internal tests only",
			"install_procedure": "msiexec /quiet /i test.msi",
			"install_procedure_success_return_codes": "0,1,2",
			"install_procedure_post_action": 1,
			"uninstall_procedure": "msiexec /quiet /x test.msi",
			"uninstall_procedure_success_return_codes": "0",
			"download_for_uninstall": 1,
			"uninstall_procedure_post_action": 0,
			"compatible_os": "Windows 10 Home",
			"compatible_os_version": "10.0.18363",
			"file":"base64 string .....",
			"file_name":"test.msi"
		}
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
			"id": 123
		}
	}
}
```

## `oco.package.remove` - Remove Package
This will also delete the package payload (ZIP file) from the server.
### Parameters
- `id` - package ID
- `force` - (optional - default true) do not abort if there are pending jobs
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.package.remove",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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

## `oco.job_container.list` - List All Job Containers
### Parameters
no parameters
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.job_container.list",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩"
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

## `oco.job_container.job.list` - List All Jobs Of A Job Container
### Parameters
- `ìd` - job container ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.job.list",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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
				"procedure": "msiexec /quiet /i test.msi",
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
				"package_family_name": "Test Package",
				"package_version": "1.0",
				"job_container_start_time": "2021-06-22 21:27:00"
			}
		]
	}
}
```

## `oco.job_container.deploy` - Create Deployment Jobs
### Parameters
- `name` - name for the new job container
- `description` (optional) - name for the new job container
- `computer_ids` (int array) - IDs of the computers to deploy
- `computer_group_ids` (int array) - IDs of the computer groups to deploy
- `computer_report_ids` (int array) - IDs of the computer reports to deploy (`computer_id` column of the report result is used to determine the deployment targets)
- `package_ids` (int array) - IDs of the packages to deploy
- `package_group_ids` (int array) - IDs of the package groups to deploy
- `package_report_ids` (int array) - IDs of the package reports to deploy (`package_id` column of the report result is used to determine the deployment targets)
- `date_start` - deployment start date
- `date_end` (null) - deployment end date (unfinished jobs will set to "expired"), null means jobs do not expire
- `use_wol` - (optional - default 1) enable or disable WOL
- `shutdown_waked_after_completion` - (optional - default 0) decide if computers which were waked via WOL should be shutted down after jobs finished
- `restart_timeout` - restart/shutdown timeout in minutes, only for packages which require an restart/shutdown
- `auto_create_uninstall_jobs` - (optional - default 1) enable or disable automatic uninstall job creation if another version of this package family is already installed
- `force_install_same_version` - (optional - default 0) force installation even if the same package was already installed
- `sequence_mode` - (optional - default 0) sequence mode: 0 - ignore failed jobs, 1 - abort after failed job
- `priority` - (optional - default 0) job container with higher priority will be executed first
- `constraint_ip_ranges` - (optional - default empty - string array) list of IP ranges as condition for the agent for executing the jobs
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.deploy",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"name": "API-Test",
			"description": "Deploying a new package version.",
			"computer_ids": [1,2,3],
			"computer_group_ids": [],
			"package_ids": [],
			"package_group_ids": [4],
			"date_start": "2020-01-01 18:00:00",
			"date_end": null,
			"use_wol": 1,
			"shutdown_waked_after_completion": 0,
			"restart_timeout": 5,
			"auto_create_uninstall_jobs": 1,
			"force_install_same_version": 0,
			"sequence_mode": 0,
			"priority": 0,
			"constraint_ip_ranges": ["127.0.0.1\/24", "192.168.2.0\/24"]
		}
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
			"id": "1056"
		}
	}
}
```

## `oco.job_container.uninstall` - Create Uninstall Jobs
### Parameters
- `name` - name for the new job container
- `description` (optional) - name for the new job container
- `installation_ids` - IDs of the package installation assignment records (you can get them by executing `oco.computer.get` or `oco.package.get` from the section `installed_packages`)
- `date_start` - deployment start date
- `date_end` (null) - deployment end date (unfinished jobs will set to "expired"), null means jobs do not expire
- `use_wol` - (optional - default 1) enable or disable WOL
- `shutdown_waked_after_completion` - (optional - default 0) decide if computers which were waked via WOL should be shutted down after jobs finished
- `restart_timeout` - restart/shutdown timeout in minutes, only for packages which require an restart/shutdown
- `sequence_mode` - (optional - default 0) sequence mode: 0 - ignore failed jobs, 1 - abort after failed job
- `priority` - (optional - default 0) job container with higher priority will be executed first
- `constraint_ip_ranges` - (optional - default empty - string array) list of IP ranges as condition for the agent for executing the jobs
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.uninstall",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"name": "API-Test",
			"description": "Uninstalling a package.",
			"installation_ids": [1,2,3],
			"date_start": "2020-01-01 18:00:00",
			"date_end": null,
			"use_wol": 1,
			"shutdown_waked_after_completion": 0,
			"restart_timeout": 5,
			"sequence_mode": 0,
			"priority": 0,
			"constraint_ip_ranges": ["127.0.0.1\/24", "192.168.2.0\/24"]
		}
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
			"id": "1057"
		}
	}
}
```

## `oco.remove_installation_assignment` - Remove Package-Computer Installation Assignment
Manually removes an Package-Computer assignment. Normally, this assigment ist automatically removed when the uninstallation finished successfully. In some cases it is necessary to manually remove this assignment, e.g. because the package was uninstalled manually on the computer (without using OCO).
### Parameters
- `id` - ID of the package installation assignment record (you can get them by executing `oco.computer.get` or `oco.package.get` from the section `installed_packages`)
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.remove_installation_assignment",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
	}
}
```
```
{
	"id": 1,
	"error": null,
	"result": {
		"success": true,
		"data": {}
	}
}
```

## `oco.job_container.remove` - Remove Job Container
This will delete all jobs in the container and the container itself. Pending jobs are no longer executed.
### Parameters
- `id` - job container ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.job_container.remove",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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

## `oco.job_container.job.remove` - Remove Job
This removes a single job from a job container.
### Parameters
- `id` - job ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.job.remove",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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

## `oco.report.execute` - Execute A Report And Return The Result
Please note that the `data` output of the JSON response depends on the columns of your report.
### Parameters
- `id` - report ID
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.report.execute",
	"params": {
		"api_key": "🌈💜👆🚧🛸💩",
		"data": {
			"id": 123
		}
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
				"computer_id": "107",
				"hostname": "PC001",
				"os": "Windows 10 Enterprise 2016 LTSB",
				"os_version": "10.0.14393",
				"agent_version": "0.7.0"
			}
		]
	}
}
```

# Data Format
- Date String: `2020-01-01 18:00:00`
