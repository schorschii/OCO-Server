# General
This document describes the JSON-REST-API provided by the OCO server. By implementing this protocol you can automate your workflows and integrate OCO into your environment to make it more convenient.

You first need to activate the API in the OCO config file:
```
const CLIENT_API_ENABLED = true; # this is false by default
```

# The JSON-RPC Package
A valid JSON-RPC request is sent via HTTP with the HTTP header `Content-Type: application/json` and looks like the following example.  
```
{
	"version": "2.0",
	"method": "oco.computer.get",
	"params": {
		...................
	},
	"id": 1
}
```
HTTP Basic Authentication is used for client authentication. Please provide the HTTP header `Authorization: Basic <Base64-Auth-String>` with all API requests.

# Methods
## `oco.computer.list` - List All Computers
### Parameters
- `hostname` - the host name of the new computer
- `notes` (optional) - a description
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

# Data Format
- Date String: `2020-01-01 18:00:00`
