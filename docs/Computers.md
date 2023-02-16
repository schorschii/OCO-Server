# OCO: Computers

## Register New Computers
After you have installed the agent on the client computer, you have to set the server URL and agent key in the agent config file (`oco-agent.ini`). If you do not specify a server URL in the agent config file, the agent will query your DNS for the SRV record `_oco._tcp` to get the server address (DNS auto discovery). You can also set your server address manually. For that, please let `api-url` point to the full URL to `api-agent.php` on your server, e.g. `https://oco.example.com/api-agent.php`.

There are 2 methods for registering new computers:

### 1. Agent Self-Registration
This feature must be activated first by enabling "Agent Self-Registration" on the config page. Then, every agent knowing the correct agent key (defined in the server config file) can registrate itself on the server. During the first communication with the server, a unique agent key will be set for the new computer.

### 2. Manual (Pre-)Registration
For this method, a new computer object must be created first in the web frontend or using the client API. The name which you enter on the dialog must exactly match the new computers hostname. Then, the computer is able update its inventory values using the global agent key (defined in the server config file; key can also be set to an empty string - this allows you to install and use the agent without further configuration on the client computer). During the first communication with the server, a unique agent key will be set for the new computer.

## Group Computers
You can create computer groups e.g. to group all computers of specific locations inside your company, or to group computers which should get special software packages installed.

These are static, manually filled groups. In contrast to that, you can create a report if you want a "dynamic group" whose contents is automatically filled/updated based on various criteria (e.g. "all computers of a certain model").

## Updating Computer Inventory Values
The agent will only send updated inventory data to the server if the last inventory data update is older than the time span defined in "Agent Update Interval" on the config page. The recommended default value is 2 hours. Do not make this time interval too short as the query of the inventory values can produce some CPU load.

## Event Log Query
You can monitor the Windows event log and journalctl on Linux by creating Event Query Rules on the OCO server. These rules are communicated with the agent and if an event matches the rule, the agent will send the event data to the server. This feature can be used as a simple central syslog functionality for your managed clients.

For Windows, the query syntax is the exact same XML format as you would enter it in the Windows event viewer.

For Linux, as log name, please enter `journalctl` - this is currently the only supported datasource on Linux. As query, a JSON string with the following filter options can be set:
```
{
    "unit":"udisks2.service",  <-- systemd unit to monitor (journalctl -u ...)
    "identifier":"",  <-- entries with the specified syslog identifier (journalctl -t ...)
    "priority":"0,1,2,3",  <-- entries with the specified priority (journalctl -p ...)
    "grep":"mounted"  <-- entries with matching pattern (journalctl -g ...)
}
```

Please note that the operating systems are producing many log entries. A meaningful filter should always be applied to not spam the database with unnecessary events.

### Example Rules
#### Windows: Get Defender Warning, Error and Critical Events
(including "Malware Detected" events with event ID 1116)

Log: `Microsoft-Windows-Windows Defender/Operational`  
Query: `<QueryList><Query><Select>*[System[(Level=1 or Level=2 or Level=3)]]</Select></Query></QueryList>`

#### Windows: Get All GPO Script Error Events (ID 1130)
Log: `System`  
Query: `<QueryList><Query><Select>*[System[(EventID=1130)]]</Select></Query></QueryList>`

#### Linux: Get Mount Attempts
Log: `journalctl`  
Query: `{"unit":"udisks2.service", "priority":"0,1,2,3,4,5", "grep":"mounted"}`

## Service Monitoring
OCO offers basic monitoring features. You can check anything by writing your own service check script and placing it into the agent's local check directory. Your script just have to produce standardised output in the CheckMK check format. For more information, please have a look at the documentation in the [agent repo](https://github.com/schorschii/oco-agent).

## Wake On Lan (WOL)
OCO supports sending WOL magic packets. WOL in general only works via Ethernet (not via WiFi!) and if the server has a network card in the same subnet as the target computer because WOL packets are UDP broadcast packets. If you have multiple subnets, you can add a new network card to the server for each subnet or configure "Satellite WOL".

When using the satellite WOL technology, the OCO server connects to another server via SSH which is located in the foreign network and then executes the `wakeonlan` command. Please make sure that the remote server can be accessed with the defined SSH key and that `wakeonlan` is installed. Please read the instructions in the `conf.example.php` file for example configurations.

## Remote (Screen) Access
OCO does not contain a remote access solution as found in some commercial client management systems. OCO doesn't want to reinvent the wheel. Please use a VNC server/client for this and also have a look at the section "Computer Commands" in [WebApplication.md](WebApplication.md).
