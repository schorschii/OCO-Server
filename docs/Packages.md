# OCO: Packages

## How To Create Packages In OCO Web Console
Packages are created in the web frontend. Since OCO supports Linux, MacOS and Windows, a package is a simple `.zip` archive containing the necessary (operating system specific) installer files.

An OCO package can therefore contains `.deb` files for Debian-based Linux Distros, `.dmg` files for MacOS and `.exe` or `.msi` files for Windows.

### Procedure And Return Codes
In the 'Procedure' fields, you define which commands should be executed when deploying or uninstalling a package. After that, you can enter the exit/return codes which should be considered as success (multiple return codes have to be separated by a comma `,`). If you leave the return code field blank, all return codes are considered as success (this is not recommended - keep `0` if you are unsure, this is normally the return code for success).

### Action After Procedure
Below the procedure you can specify if the computer should be restartet or shut down after the procedure was executed. The restart/shutdown timeout is specified in the deployment assistant. This timeout allows the user to save his work before the computer gets restartet. If no user is logged in on the target computer, it will be restartet immediately, ignoring the timeout value. If you specify a negative timeout value in the deployment assistant, no restart/shutdown will be executed (in other words, this overrides the package restart/shutdown setting).

### Deployment Process
When deploying, the `.zip` archive is unpacked into a temporary directory. Then a command (the procedure) is executed to start the installation. Longer commands should be stored in a script (`.bat` or `.sh`) you have written yourself.

### General Example Procedures
- EXE setup for Windows: `installer.exe /S`
  - It depends on the programm which parameters are available. Most `.exe` setups do **not** have uninstallation support. Please check which parameters are available by checking `installer.exe /?` output or consult the software manufacturer for more information.
- Own Batch file for Windows: `myscript.bat`
- MSI setup for Windows: `msiexec /quiet /i package.msi`
- MSI uninstallation for Windows: `msiexec /quiet /x package.msi` or `{PRODUCT-GUID}`
  - It is easier to uninstall `.msi` packages using the original installation file - but this means that the package must be downloaded again for uninstallation. That's why, for bigger packages, you should use the GUID in the uninstallation command. You can find it out by using a method described [here](https://stackoverflow.com/questions/29937568/how-can-i-find-the-product-guid-of-an-installed-msi-setup).
- DEB package for Linux: `gdebi -n package.deb`
- DEB package for Linux uninstallation: `apt remove -y packagename`
- Own Shell script for Linux/macOS: `myscript.sh`
- PKG package on macOS: `sudo installer -pkg package.pkg -target /`
  - The macOS `.pkg` package format does not have uninstallation support. Yes, this is no joke. WTF, Apple.
- APP directory on macOS: `cp -R GIMP-2.10.app /Applications ; chmod -R +x /Applications/GIMP-2.10.app`
- APP directory on macOS uninstallation: `rm -R /Applications/GIMP-2.10.app`

### Example: Create OCO Windows Agent Update Packages
Please install the agent package regularily, e.g. with this procedure for Windows: `oco-agent.exe /SILENT`.

The agent installer does not overwrite an existing config file. After agent update installation, a restart is required in order to load the new agent binary.

### Example: Windows-Upgrade
It is also possible to update your Windows-Installation to a newer build using a OCO software job.
1. Extract the contents of the new Windows `.iso` file and add them into a `.zip` file. Upload this `.zip` file on the OCO web console.
2. Choose the following command line as installation procedure: `setup.exe /auto upgrade /showoobe None /noreboot`
3. Choose `Reboot` as action after package installation.

## How To Create OS Specific Packages
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
