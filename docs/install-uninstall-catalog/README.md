# Install/Uninstall Command Catalog
This directory contains software-specific commands (examples) for silent installation and unstallation via OCO.

Contributions welcome.


## Desktop Shortcuts
If you do not like the desktop shortcuts of installed applications, you can simply append a `del` command after the installation command. Example: `msiexec /i inkscape-x.x.x-x64.msi ALLUSERS=1 /qn && del "C:\Users\Public\Desktop\Inkscape.lnk"`

## MSI Uninstallation
Most MSI uninstallation commands uses the "ProductCode" placeholder for automatic MSI GUID extraction (e.g. `msiexec /quiet /x $$ProductCode$$`), which requires Wine installed on your server. If you don't have Wine installed, read the Windows Installer chapter of [Packages.md](../Packages.md) how to find the Product GUID manually.

## Linux Software
Most software on Linux systems can be easily installed/uninstalled using the system package manager. E.g. to install GIMP on Ubuntu, just use the command line `apt install -y gimp`.
