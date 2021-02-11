# OCO: Packages

## Create Packages
Packages are created in the web frontend. Since OCO supports Linux, MacOS and Windows, a package is a simple `.zip` archive containing the necessary (operating system specific) installer files.

An OCO package can therefore contains `.deb` files for Debian-based Linux Distros, `.dmg` files for MacOS and `.exe` or `.msi` files for Windows.

In the 'Procedure' fields, you define which commands should be executed when deploying or uninstalling a package.

When deploying, the `.zip` archive is unpacked into a temporary directory. Then a command (the procedure) is executed to start the installation. Longer commands should be stored in a script (`.bat` or `.sh`) you have written yourself.

Example Procedures:
- EXE setup for Windows: `installer.exe /S` (no uninstallation support)
- Own Batch file for Windows: `myscript.bat`
- MSI setup for Windows: `msiexec /quiet /i package.msi`
- MSI uninstallation for Windows: `msiexec /quiet /x package.msi` or `{PRODUCT-GUID}`
- DEB package for Linux: `gdebi -n package.deb`
- DEB package for Linux uninstallation: `apt remove -y packagename`
- Own Shell script for Linux/macOS: `myscript.sh`
- PKG package on macOS: `sudo installer -pkg package.pkg -target /`

### Creating Own DEB Packages For Linux
- http://dbalakirev.github.io/2015/08/21/deb-pkg/

### Crating Own PKG Packages For MacOS
- https://medium.com/swlh/the-easiest-way-to-build-macos-installer-for-your-application-34a11dd08744

### Creating Own MSI Packages For Windows
- https://wixtoolset.org/
- https://www.advancedinstaller.com/
- https://www.masterpackager.com/  
  (use the "Repackager" to create MSIs for software which is shipped as `.exe` only to get uninstall support)
- https://www.heise.de/download/product/scalable-smart-packager-ce-89948

## Create OCO Agent Update Packages
Please install the agent package regularily, e.g. with this procedure for Windows: `oco-agent.exe /SILENT`.

The agent installer does not overwrite an existing config file. After agent update installation, a restart is required in order to load the new agent binary.
