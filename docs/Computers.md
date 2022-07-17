# OCO: Computers

## Register New Computers
After you have installed the agent on the client computer, you have to set the server URL and agent key in the agent config file (`oco-agent.ini`). If you do not specify a server URL in the agent config file, the agent will query your DNS for the SRV record `_oco._tcp` to get the server address (DNS auto discovery). If you want to set your server address manually, please note that the server address is defined twice in the agent config file: the setting `api-url` should point to `api-agent.php` on your server and `payload-url` to `payloadprovider.php`.

There are 2 methods for registering new computers:

### 1. Agent Self-Registration
This feature must be activated first in the config file `conf.php` by setting `AGENT_SELF_REGISTRATION_ENABLED` to `true`. Then, every agent knowing the correct agent key (defined in the server config file) can registrate itself on the server. During the first communication with the server, a unique agent key will be set for the new computer.

### 2. Manual (Pre-)Registration
For this method, a new computer object must be created first in the web frontend or using the client API. The name which you enter on the dialog must exactly match the new computers hostname. Then, the computer is able update its inventory values using the global agent key (defined in the server config file; key can also be set to an empty string - this allows you to install and use the agent without further configuration on the client computer). During the first communication with the server, a unique agent key will be set for the new computer.

## Group Computers
You can create computer groups e.g. to group all computers of specific locations inside your company, or to group computers which should get special software packages installed.

These are static, manually filled groups. In contrast to that, you can create a report if you want a "dynamic group" whose contents is automatically filled/updated based on various criteria (e.g. "all computers of a certain model").

## Server Hardening
While it is technically possible, **never** let the agent commuicate in plaintext HTTP with the server! Attackers can do a man-in-the-middle attack to send any malicious software package to your agent. **Always** configure your (Apache) web server to use HTTPS with a valid certificate. Redirect **all** HTTP requests to HTTPS using appropriate rewrite rules. It is also possible to use a self-signed certificate if necessary. Then, you have to import your own CA certificate into the trust store of your agent's operating system.

It is recommended to **not** make the OCO server available on the internet to prevent brute force attacks. Make the server only available on your internal company network and use a VPN connection for mobile devices.

## Updating Computer Inventory Values
The agent will only send updated inventory data to the server if the last inventory data update is older than the time span defined in `AGENT_UPDATE_INTERVAL` in the config file.

## Wake On Lan (WOL)
OCO supports sending WOL magic packets. WOL in general only works via Ethernet (not via WiFi!) and if the server has a network card in the same subnet as the target computer because WOL packets are UDP broadcast packets. If you have multiple subnets, you can add a new network card to the server for each subnet or configure "Satellite WOL".

When using the satellite WOL technology, the OCO server connects to another server via SSH which is located in the foreign network and then executes the `wakeonlan` command. Please make sure that the remote server can be accessed with the defined SSH key and that `wakeonlan` is installed. Please read the instructions in the `conf.example.php` file for example configurations.

## Remote (Screen) Access
OCO does not contain a remote access solution as found in some commercial client management systems. OCO doesn't want to reinvent the wheel. Please use a VNC server/client for this and also have a look at the section "Computer Commands" in [WebApplication.md](WebApplication.md).
