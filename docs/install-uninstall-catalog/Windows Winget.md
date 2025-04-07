# Install Software Using Windows' Winget
Winget is a real mess when it comes to system-wide installations for all users and automated/scripted installations.

The following command works, but only if the newest Winget version is installed (it does not work with the version that comes pre-installed on Windows 10/11).

```
"C:\Program Files\WindowsApps\Microsoft.DesktopAppInstaller_1.25.339.0_x64__8wekyb3d8bbwe\winget.exe" install --id Google.Chrome --silent --accept-package-agreements --accept-source-agreements --source WinGet
```

But obviously, the version number in the directory name can change with the next update, so it's not reliable.

For a more general solution, I recommend this script: https://github.com/djust270/Intune-Scripts/blob/master/Winget-InstallPackage.ps1

Use it with this command in an OCO package:

```
powershell.exe -executionpolicy bypass -file Winget-InstallPackage.ps1 -PackageID "Google.Chrome" -Log "ChromeWingetInstall.log"
```

In the script, you may need to comment out the check if `msiexec` is already running. Otherwise, it may hang infinitely there.
