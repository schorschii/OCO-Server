# General
This document describes the JSON-REST-API for the OCO agent provided by the OCO server. By implementing this protocol you can create your own agents.

# The JSON-RPC Package
A valid JSON-RPC request is sent via HTTP(S) with the HTTP header `Content-Type: application/json` to the API endpoint `api-agent.php`.

Within the `params` object, please send the `hostname` with the correct `agent-key` value and all required additional parameters for the method you are calling inside a `data` object. The server will trust the agent only if the `agent-key` matches the one saved in the database.

Please have a look at the following API method documentation for JSON-RPC request/response examples.

# Authentication
Before the first agent request, both `agent-key` and `server-key` are empty by default (in the server database and the agent config file). In this case, the server will generate new random keys and send it to the agent during the normal `agent_hello` response. The agent should then save the keys into it's config file. On the next request, the agent only trusts the server if it sends the same `server-key` again. If the `{agent|server}-key` is not empty in the agent config file, the agent should not allow a key update!

# Methods
## `oco.agent_hello` - Agent Contact Approach
### Parameters
- `agent_version`: the agent version
- `networks`: the network interface information of the managed computer
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.agent_hello",
	"params": {
		"agent-key": "🌈💜👆🚧🛸💩",
		"hostname": "mypc",
		"data": {
			"agent_version": "1.0.0",
			"networks": []
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
		"params": {
			"server-key": "abc123",
			"agent-key": "xyz456",
			"update": 1,
			"software-jobs": {
				"id": 123,
				"container-id": 12,
				"package-id": 1234,
				"download": true,
				"procedure": "msiexec /quiet /i mypackage.msi",
				"sequence-mode": 0,
				"restart": null,
				"shutdown": null,
				"exit": null
			}
		}
	}
}
```

## `oco.agent_update` - Update Agent Inventory Values
### Parameters
- `os`: operating system
- `os_version`: operating system version (including build number)
- `os_license`: operating system activation status (0, 1 or "-" if not applicable)
- `os_language`: operating system (default) language (format depends on the system, e.g. 0407 on Windows)
- `kernel_version`: kernel version (only for Linux systems, "-" otherwise)
- `architecture`: operating system architecture (e.g. amd64)
- `cpu`: CPU model name
- `gpu`: GPU model name
- `ram`: RAM (bytes)
- `agent_version`: agent version
- `serial`: serial number
- `manufacturer`: computer manufacturer name
- `model`: computer model name
- `bios_version`: bios version
- `uptime`: uptime in seconds
- `boot_type`: boot type (UEFI or legacy)
- `secure_boot`: secure boot status (0 or 1)
- `domain`: domain name
- `networks`: network information (array of objects)
- `screens`: screen information (array of objects)
- `printers`: printer information (array of objects)
- `partitions`: partition information (array of objects)
- `software`: installed software (array of objects)
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.agent_update",
	"params": {
		"agent-key": "🌈💜👆🚧🛸💩",
		"hostname": "mypc",
		"data": {
			"os": "Windows 10 Education",
			"os_version": "	10.0.19042",
			"os_license": "1",
			"os_language": "0407",
			"kernel_version": "-",
			"architecture": "amd64",
			"cpu": "Intel64 Family 6 Model 140 Stepping 1",
			"gpu": "NVIDIA GeForce GT 630",
			"ram": "17118322688",
			"agent_version": "1.0.0",
			"serial": "ABCD1234",
			"manufacturer": "FUJITSU CLIENT COMPUTING LIMITED",
			"model": "LIFEBOOK U7411",
			"bios_version": "2.180",
			"uptime": "681867",
			"boot_type": "UEFI",
			"secure_boot": 1,
			"domain": "example.com",
			"networks": [
				{
					"addr": "10.1.2.3",
					"netmask": "255.0.0.0",
					"broadcast": "10.255.255.255",
					"mac": "00:00:00:00:00:00",
					"interface": "{00000000-0000-0000-0000-000000000000}"
				}
			],
			"screens": [
				{
					"name": "DELL U2412M",
					"manufacturer": "Dell Inc.",
					"manufactured": "2013",
					"resolution": "1920 x 1080",
					"size": "52.0 x 32.0",
					"type": "31392",
					"serialno": "ABCD1234",
					"technology": "-"
				}
			],
			"printers": [
				{
					"name": "Microsoft XPS Document Writer",
					"driver": "Microsoft XPS Document Writer v4",
					"paper": "Letter, Tabloid, Ledger, Legal, Executive, A3, A4, A5, Tabloid Extra, Super A, A2, A6, 11x14, 11x17, 13x19, 16x20, 16x24, 2A, 4A, 8x10, 8x12, A0, A1, A4 Small, A7, A8, A9, A10, ANSI A, ANSI B, ANSI C, ANSI D, ANSI E, Arch A, Arch B, Arch C, Arch D, Arch E, Arch E1, Envelope C0, Envelope C1, Envelope C2, Envelope C3, Envelope C4, Envelope C5, Envelope C6, Envelope 10, Envelope DL, Envelope Monarch, ISO B0, ISO B1, ISO B2, ISO B3, ISO B4, ISO B5, ISO B6, JIS B0, JIS B1, JIS B2, JIS B3, JIS B4, JIS B5, JIS B6, Letter Small, RA0, RA1, RA2, RA3, RA4, SRA0, SRA1, SRA2, SRA3, SRA4",
					"dpi": "600",
					"uri": "PORTPROMPT:",
					"status": "Idle"
				}
			],
			"partitions": [
				{
					"device": "\\?\Volume{00000000-0000-0000-0000-000000000000}\",
					"mountpoint": "C:",
					"filesystem": "NTFS",
					"size": 249532772352,
					"free": 79128662016,
					"name": "",
					"serial": ""
				}
			],
			"software": [
				{
					"name": "7-Zip",
					"version": "21.06.00.0",
					"description": "Igor Pavlov"
				},
				............
			],
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
		"params": {
			"server-key": "abc123"
		}
	}
}
```

## `oco.update_deploy_status` - Update Job Deployment Status
### Parameters
- `job-id`: ID of the job to update
- `state`: state of the job, e.g. downloading, executing, finished (integer) - see lib/Models.php for constant definition
- `return-code`: procedure command return code
- `message`: procedure command output (stdout & stderr)
### Example
```
{
	"jsonrpc": "2.0",
	"id": 1,
	"method": "oco.update_deploy_status",
	"params": {
		"agent-key": "🌈💜👆🚧🛸💩",
		"hostname": "mypc",
		"data": {
			"job-id": 123,
			"state": 3,
			"return-code": 0,
			"message": ""
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
		"params": {
			"server-key": "abc123",
			"job-succeeded": true
		}
	}
}
```
