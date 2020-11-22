# OCO: Computers

## Register New Computers
There are 2 methods for registering new computers:

### 1. Agent Self-Registration
This feature must be activated first on the settings page in the web frontend. Then, every agent knowing the correct agent key (also defined in settings) can registrate itself on the server. During the first communication with the server, a unique agent key will be set for the new computer.

### 2. Manual (Pre-)Registration
For this method, a new computer object must be created first in the web frontend. The name which you have enter on the dialog must exactly match the new computers hostname. Then, the computer is able update its inventory values using the global agent key (defined on the settings page in the web frontend). During the first communication with the server, a unique agent key will be set for the new computer.

## Updating Computer Inventory Values
The agent contacts the server periodically as defined in the agent configuration. The computer will only send updated inventory data to the server if the last inventory data update is older than the time span defined in the server settings.

## Wake On Lan (WOL)
OCO supports sending WOL magic packets. This only works if the server is in the same subnet as the target computer, because WOL packets are UDP broadcast packets. If you have multiple subnets, you can simply add a new network card to the server for each subnet. Please note that WOL only works via Ethernet (not via WiFi!).

## Client Commands
OCO has a feature called "Client Commands" which allows you to seamlessly open VNC, RDP, SSH sessions with one click on the computer details page. Client Commands can be defined by yourself by editing the records in the table `computer_command`.

When clicking on a Computer Command button, a custom URL will be opened in your browser. In case of the pre-defined commands, this will be `vnc://HOSTNAME`, `rdp://HOSTNAME` and `ssh://hostname`. You need an appropriate counterpart on your computer to handle this URL. For the pre-defined commands, this will be the OCO Client Extension, found in `/lib/client-extension`. Please install it on your computer. After that, the Client Extension will handle the VNC, RDP and SSH URLs and open an appropriate program like Remmina to start the remote access.

## Remote Screen Access
OCO does not contain a remote access solution as found in some commercial client management systems. OCO doesn't want to reinvent the wheel. Please use a VNC server/client for this.
