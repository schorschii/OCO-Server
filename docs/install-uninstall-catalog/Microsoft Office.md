# Microsoft Office

## 2016 and older
**Preparation:**
Create a MSP file with the desired setup settings.
```
setup.exe /admin
```

Create a ZIP file from the installation sources and upload it in OCO.

**Installation:**
```
Office\setup.exe /adminfile office2016_64.msp
```

**Uninstallation:**
```
Office\setup.exe /uninstall ProPlus /config silent.xml
```

**silent.xml**
```
<Configuration Product="ProPlus">
    <Display CompletionNotice="no" SuppressModal="yes" AcceptEula="yes" />
    <Setting Id="SETUP_REBOOT" Value="Never" />
</Configuration>
```

## 2019 and newer (Click-to-Run Installer)
**Installation:**
```
cd \ && setup.exe /configure config.xml && reg add "HKLM\software\policies\microsoft\office\16.0\common\officeupdate" /v vltosubscription /t REG_DWORD /d 0 /f
```
- `cd \` changes the working dir. After the main setup process exited, there are still sub-processes running holding the working dir locked. Without this, it would produce an OCO agent error because the default working dir `C:\Windows\Temp\oco-staging` can not be removed after the job finished.
- `setup.exe /configure config.xml` start the installation with your config file. The setup can be on a server too, e.g. `\\SERVERNAME\office\setup.exe /configure \\SERVERNAME\office\config.xml`. In this case, leave the OCO archive empty. Create a config file [here](https://config.office.com/).
- `reg add` creates a registry value to avoid Office to [automatically migrate to Office 365](https://www.borncity.com/blog/2023/04/13/aktualisiert-microsoft-office-2016-2019-auf-office-365/).

**Uninstallation:**
```
setup.exe /configure uninstall.xml
```

**uninstall.xml**
```
<Configuration>
<Remove OfficeClientEdition="64">
<Product ID="ProPlus2024Volume">
<Language ID="de-de"/>
</Product>
</Remove>
<Display Level="Full" AcceptEULA="TRUE"/>
</Configuration>
```
