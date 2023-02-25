# Adobe Acrobat Reader DC

## Windows
**Installation:**
```
AcroRdrDC2100520048_de_DE.exe /sAll /rs /msi EULA_ACCEPT=YES DISABLEDESKTOPSHORTCUT=1
```

**Uninstallation:**
```
msiexec /quiet /x {AC76BA86-7AD7-1031-7B44-AC0F074E4100}
```
(find the correct GUID for your version from Registry: `HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall`)
