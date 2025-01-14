# Install Windows Upgrades
It is possible to even update your Windows installation to a newer build using a OCO software job!

1. Extract the contents of the new Windows `.iso` file and add them into a `.zip` file. Upload this `.zip` file on the OCO web console.
2. Choose the following command line as installation procedure: `setup.exe /auto upgrade /showoobe None /eula accept /noreboot /quiet`
   - optional parameter: `/dynamicupdate disable` to disable the setup self-update
   - optional parameter: `/compat ignorewarning` to disable warnings for incompatible hardware
3. Choose "Reboot" as action after package installation.

Note: when searching for the return code on error, convert the number to hex. Chances are higher to find answers since Windows status codes are often represented as hexadecimal.

Maybe you wish to apply some hacks to bypass the silly hardware restriction from Microsoft so that a Windows 11 upgrade runs on older hardware too. For example, you can import the necessary registry keys by prepending `reg import bypasscpu.reg &&` before the `setup.exe` call, while `bypasscpu.reg` in your .zip package contains the following:
```
Windows Registry Editor Version 5.00

[HKEY_LOCAL_MACHINE\SYSTEM\Setup\MoSetup]
"AllowUpgradesWithUnsupportedTPMOrCPU"=dword:00000001
```
