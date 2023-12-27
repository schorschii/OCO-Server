# Zoom

## Windows
Use the MSI installer: https://support.zoom.com/hc/de/article?id=zm_kb&sysparm_article=KB0060417

**Installation:**
```
msiexec /quiet /i ZoomInstallerFull.msi
```

**Uninstallation:**
```
msiexec /quiet /x {GUID}
```

## Linux
Use the .deb package: https://zoom.us/download?os=linux

**Installation:**
```
apt install -y ./zoom_amd64.deb
```

**Uninstallation:**
```
apt remove -y zoom
```
