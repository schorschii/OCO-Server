# OCO: Packages

## Package Families
A package is always subordinate to a package family.
- The package family holds the name and the icon of a package.
- A package family can contain multiple versions.

## Packages For Multiple Operating Systems
There are mainly two strategies how to deal with software for multiple operating systems:

- **One Package Family For Each Operating System**
  ```
  Family: L_VLC Media Player
  └──> Version: 2.3.3

  Family: M_VLC Media Player
  └──> Version: 2.3.3

  Family: W_VLC Media Player
  ├──> Version: 2.3.1
  └──> Version: 2.3.3
  ```
- **Multiple Versions For Each Operating System In One Package Family**
  ```
  Family: VLC Media Player
  ├──> Version: 2.3.3-Linux
  ├──> Version: 2.3.3-macOS
  ├──> Version: 2.3.1-Windows
  └──> Version: 2.3.3-Windows
  ```

## Create Packages
Packages can be created in the web frontend or via the [API](Client-API.md).

Since OCO supports Linux, macOS and Windows, a package is a simple `.zip` archive containing the necessary (operating system specific) installer files. For example, an OCO package can contain `.deb` files for Debian-based Linux Distros, `.dmg` files for MacOS and `.exe` or `.msi` files for Windows.

If you upload a file type other than a `.zip` archive, a `.zip` archive is automatically created with the uploaded file(s).

On the web frontend, you can toggle the upload input between multi-file or single-directory selection. Due to the (current) limitations of the HTML file input element, it is not possible to select files and directory or multiple directories at once. Please organize your setup files into a single directory before selecting it on the web frontend.

Please note that creating an archive with the directory structure of the original directory is only available when using PHP 8.1 or newer. Only PHP 8.1 delivers the full path of the uploaded files to the PHP script.

You can also decide to not upload any file. Then, no archive will be downloaded on the target machine when deploying the package. This can be useful if you just want to execute a command that installs software from a network share or via `apt` package manager from software repositories on Linux.

### Continuous Integration/Delivery/Deployment
The OCO API can for example be used to automatically create packages from a Continuous Integration service like GitLab CI/CD. Your CI pipeline will then call the OCO API after builing the binaries, and create a package with them. OCO can act as Continuous Delivery or Continuous Deployment service, which automatically deploys your binaries on tester's or user's computers.

### Compatible OS And Version
You can define on which operating systems (and versions) your package can be deployed. Simply add the string of the target operating system as can be found on the computer detail page. Multiple compatible operating systems can be defined comma-separated.

If you leave the fields "compatible operating system" and "compatible operating system version" empty the package can be deployed on every computer, no matter what operating system is detected by the agent.

### Procedure And Return Codes
In the 'Procedure' fields, you define which commands should be executed when installing or uninstalling a package.

After that, you can enter the exit/return codes which should be considered as success (multiple return codes have to be separated by a comma `,`). If you leave the return code field blank, all return codes are considered as success (this is not recommended - keep `0` if you are unsure, this is normally the return code for success). But for example, MSI packages can also return `3010` if the installation succeeded but a reboot is required (see [list of MSI return codes](https://docs.microsoft.com/de-de/windows/win32/msi/error-codes)).

### Upgrade Behavior
Choose the correct upgrade behavior for your installation procedure.

1. 'Create explicit uninstall jobs'  
   If you deploy this package to a computer which already has another version of this package family installed, OCO will automatically create separate uninstall jobs for the previously installed package version.
2. 'Installation automatically removes other versions'  
   If you choose this option, OCO assumes that your installation procedure automatically detects and removes/upgrades previous versions of this software. Many installers behave like this, e.g. the VLC installer, but this behavior is not always desired.
   - No automatic uninstall job is created when deploying the package and another version of the same package family is already installed.
   - If the installation succeeds, all computer-package assignments of other packages from this family will be removed from the OCO database.
3. 'Keep other versions'  
   No explicit uninstall jobs will be created and computer-package assignments will not be cleared when the installation finishes. For use with software where multiple versions can be installed at once.

### Action After Procedure
Below the procedure you can specify if the computer should be restartet or shut down after the procedure was executed. If your new package is an OCO agent update package, you can select 'restart agent' to instantly exit the old version and start the new agent.

The restart/shutdown timeout is specified later in the deployment assistant. This timeout allows the user to save his work before the computer gets restartet. If no user is logged in on the target computer, it will be restartet immediately, ignoring the timeout value. If you specify a negative timeout value in the deployment assistant, no restart/shutdown will be executed (in other words, this overrides the package restart/shutdown setting).

### Deployment Process
When deploying, the `.zip` archive is unpacked into a temporary directory. Then a command (the procedure) is executed to start the installation. Longer commands should be stored in a script (`.bat` or `.sh` inside the archive) you have written yourself.

### Widely Used Installer Systems
It depends on the platform and program which command can be used for (un)installation.

#### Windows
Several installer systems have become established under Windows. Please check which parameters are available by executing `installer.exe /?` or consult the software manufacturer for more information. Please also consider repacking EXE setups as MSI package, which can be uninstalled by the standardized command `msiexec /x` (see below).

In general, you should always use system-wide installations, that's what OCO is made for. You as the administrator want to ensure and enforce that every managed computer has the newest version installed with all security and bug fixes. In contrast, some software vendors offers installations in user context. These cannot be easily managed and updated in that way. Also, user-wide installations take up unnecessary space for each user separately on the computer. While the most software comes as system-wide installation (which needs admin rights), some software offers separate user-installation packages or parameters to switch the installation mode to system-wide (e.g. `ALLUSERS=1` (MSI) or `/ALLUSERS` (InnoSetup)).

##### Windows Installer
- silent MSI installation: `msiexec /quiet /i package.msi`
- silent MSI repair: `msiexec /quiet /f package.msi` or `msiexec /quiet /f {PRODUCT-GUID}`
- silent MSI uninstallation: `msiexec /quiet /x package.msi` or `msiexec /quiet /x {PRODUCT-GUID}`
  - It is easier to uninstall `.msi` packages using the original installation file - but this means that the package must be downloaded again for uninstallation. That's why, for bigger packages, you should use the MSI product GUID in the uninstallation command. OCO can automatically find the GUID if you enter the placeholder `$$ProductCode$$` into the uninstallation procedure. This requires that Wine with its `msidb` command is installed on your server. Alternatively, you can find the GUID by using a method described [here](https://stackoverflow.com/questions/29937568/how-can-i-find-the-product-guid-of-an-installed-msi-setup).
- additional parameters
  - `/norestart`: prevent automatic restart - if your product needs a restart, you should set this option and use the OCO restart feature ("post action") instead
  - `MY_PROP="myValue"`: custom software-specific properties - please contact the MSI package software vendor for a list of supported options
- detailed parameter documentation can be found in the [Microsoft docs](https://docs.microsoft.com/en-us/windows-server/administration/windows-commands/msiexec)

##### Inno Setup
- silent EXE installation: `installer.exe /SILENT /SUPPRESSMSGBOXES`
- silent EXE uninstallation: `C:\Program Files\MyProgram\unins000.exe /SILENT /SUPPRESSMSGBOXES`
- additional parameters
  - `/DIR="x:\dirname"`: install directory
  - `/NORESTART`: prevent automatic restart - if your product needs a restart, you should set this option and use the OCO restart feature ("post action") instead
  - `/SAVEINF="FILENAME"`: save installation settings to the specified file
  - `/LOADINF="FILENAME"`: use settings from the specified file
- detailed parameter documentation can be found in the [Inno Setup docs](https://jrsoftware.org/ishelp/index.php?topic=setupcmdline)

##### National Installer
- silent EXE installation: `installer.exe /q /AcceptLicenses yes`
- additional parameters
  - `/r:n` to prevent automatic restart or `/r:f` to force automatic restart

##### Nullsoft Install System
- silent EXE installation: `installer.exe /S`
- silent EXE uninstallation: `C:\Program Files\MyProgram\uninstall.exe /S`
- additional parameters
  - `/D=PATH`: install directory
  - `/DS=0`: exclude the desktop shortcut
  - `/SMS=0`: exclude start menu shortcut

##### Windows Driver Installation
You may want to deploy drivers for printers, scanners etc. They can be installed with the `pnputil` command line utility from windows. Example:
```
pnputil -i -a oemsetup.inf
```
Where `oemsetup.inf` is the name of your driver's `.inf` file. Important: the ZIP archive must contain all necessary driver files, not only the `.inf` metadata file!

##### Update Programs Which Are Currently Running
Under Windows, an executable file cannot be deleted if the program is currently open. This can cause problems (e.g. MSI exit code 3010) when installing a new version of a package.

To overcome this situation you can do two things:
- add a restart after the uninstallation procedure, so that the following installation procedure of the new package version can overwrite all files without errors
- add a kill command before the installation command, e.g. `taskkill /F /IM myprogram.exe /T & msiexec /quiet /i MyProgram.msi`

In both cases, make sure to inform the user that a reboot or program termination will be forced.

#### Debian/Ubuntu Linux: `apt`/`gdebi`
- package from official repository: `apt install -y gimp`
- package from official repository uninstallation: `apt remove -y gimp`
- DEB package: `apt install -y ./package.deb` or `gdebi -n package.deb`
- DEB package uninstallation: `apt remove -y packagename`

#### macOS
- PKG package on macOS: `installer -pkg package.pkg -target /`
  - The macOS `.pkg` package format does not have uninstallation support. Yes, this is no joke. WTF, Apple.
  - You can remove the corresponding .app directory using `rm -R /Applications/myapp.app`, but this may leave files installed in system directories behind.
- .app directory on macOS: `hdiutil attach program.dmg && cp -R /Volumes/program/program.app /Applications && hdiutil detach /Volumes/program`
- .app directory on macOS uninstallation: `rm -R /Applications/GIMP-2.10.app`

#### Own Scripts
You can run own scripts which may contain multiple commands or more complex logic (which cannot be put into the single OCO installation command).
- own Batch file for Windows: `myscript.bat`
- own Shell script for Linux/macOS: `myscript.sh`

### Specific Examples
Please have a look at the [Silent Install/Uninstall Commands Catalog](install-uninstall-catalog).

## Group Packages
You can group multiple package (versions) together - e.g. into a group called 'Base Packages' to easily install all basic software packages (which should be available on all computers in your domain) on new computers.

These are static, manually filled groups. In contrast to that, you can create a report if you want a "dynamic group" whose contents is automatically filled/updated based on various criteria (e.g. "all packages which are not installed on any computers").

## Dependencies
You can reference other packages to one package to make them dependent on one another.

Example: you have several applications which need Java installed. In this case, you would create one Java package which will be referenced from the Java-dependent packages. The Java package is then installed automatically when one of the Java-dependent package is deployed (if it is not already installed).

## How To Create OS Specific Installer Packages
### Creating Own DEB Packages For Linux
- http://dbalakirev.github.io/2015/08/21/deb-pkg/

### Creating Own PKG Packages For MacOS
- https://medium.com/swlh/the-easiest-way-to-build-macos-installer-for-your-application-34a11dd08744

### Creating Own MSI Packages For Windows
- https://wixtoolset.org/
- https://www.advancedinstaller.com/
- https://www.masterpackager.com/  
  (use the "Repackager" to create MSIs for software which is shipped as `.exe` only to get uninstall support)
- https://www.heise.de/download/product/scalable-smart-packager-ce-89948
