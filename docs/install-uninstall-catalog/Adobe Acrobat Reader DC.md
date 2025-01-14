# Adobe Acrobat Reader DC

## Windows
**Installation:**
```
AcroRdrDC2100520048_de_DE.exe /sAll /rs /msi EULA_ACCEPT=YES DISABLEDESKTOPSHORTCUT=1
```

**Uninstallation:**
```
wmic product where "name like 'Adobe Acrobat Reader%'" call uninstall /nointeractive
```
