# Microsoft Office

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
