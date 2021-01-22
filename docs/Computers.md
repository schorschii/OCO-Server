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

When clicking on a Computer Command button, a custom URL will be opened in your browser. In case of the pre-defined commands, this will be `vnc://HOSTNAME`, `rdp://HOSTNAME`, `ssh://HOSTNAME`, `ping://HOSTNAME` and `nmap://HOSTNAME`. You need an appropriate counterpart on your computer to handle this URLs. For the pre-defined commands, this will be the OCO Client Extension, found in `/lib/client-extension`. Please install it on your computer. After that, the Client Extension will handle the VNC, RDP and SSH URLs and open an appropriate program like Remmina to start the remote access.

It is recommended to visit the Github Releases Page to download a ready-to-use package which automatically installs the OCO Client Extension on your system. Alternatively, you can configure the Client Extension by yourself using the follwing information.

### Linux XDG Configuration
Copy `oco-client-extension-linux.desktop` into `/usr/share/applications` and execute `update-desktop-database`. Copy `oco-client-extension-linux.py` into `/usr/bin`, make sure it is executable and check if Python 3 is working properly.

It is possible that another application already registered the `ssh://` protocol. Firefox lets you select the application which should be used to open these URLs but Chrome always uses the default application. In this case, you can set the OCO Client Extensions as default with: `xdg-settings set default-url-scheme-handler ssh oco-client-extension-linux.desktop`.

### Windows Configuration
Compile the Windows Client extension into an `.exe` using pyinstaller. Then, move the binary to `C:\Program Files\OCO Client Extension\oco-client-extension-windows.exe` and execute `oco-client-extension-windows.reg` to register the URL Schemes.

Now, install TightVNC and Nmap if you want to use VNC and Nmap. The Client Extensions expecting that those programs are installed in the default directories, so please do not change the installation directory.

## Remote Screen Access
OCO does not contain a remote access solution as found in some commercial client management systems. OCO doesn't want to reinvent the wheel. Please use a VNC server/client for this.
