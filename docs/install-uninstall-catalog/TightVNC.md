# TightVNC
You can use TightVNC as remote control solution for your clients.

## Installation
You can install it with all necessary settings for remote access, e.g. a pre-defined password and allowed IP ranges.
```
msiexec /quiet /i tightvnc-x.x.x-gpl-setup-64bit.msi ADD_FIREWALL_EXCEPTION=0 SERVER_ADD_FIREWALL_EXCEPTION=0 VALUE_OF_USEVNCAUTHENTICATION=1 SET_PASSWORD=1 SET_USECONTROLAUTHENTICATION=1 VALUE_OF_USECONTROLAUTHENTICATION=1 SET_CONTROLPASSWORD=1 SET_IPACCESSCONTROL=1 SET_ACCEPTHTTPCONNECTIONS=1 VALUE_OF_ACCEPTHTTPCONNECTIONS=0 VALUE_OF_PASSWORD="YOUR_PASSWORD_HERE" VALUE_OF_CONTROLPASSWORD="YOUR_PASSWORD_HERE" VALUE_OF_IPACCESSCONTROL="192.168.2.1-192.168.2.254:2,0.0.0.0-255.255.255.255:1"
```
View all possible installation options [here](https://www.tightvnc.com/doc/win/TightVNC-installer-2.5.2.pdf).

## Uninstallation
```
msiexec /quiet /x tightvnc-x.x.x-gpl-setup-64bit.msi
```
(Download for uninstall)
