# LibreOffice
Download: https://de.libreoffice.org/download/download/

## Windows
**Installation:**
```
msiexec /quiet /i LibreOffice_7.0.5_Win_x64.msi /norestart REGISTER_NO_MSO_TYPES=1 UI_LANGS=de CREATEDESKTOPLINK=0 ISCHECKFORPRODUCTUPDATES=0 REBOOTYESNO=No QUICKSTART=0 ADDLOCAL=ALL VC_REDIST=1 REMOVE=gm_o_Onlineupdate
```
(replace UI_LANGS according to your needs)

**Uninstallation:**
```
msiexec /quiet /x $$ProductCode$$
```

## Linux
Recommendation: use your distro's package manager.
