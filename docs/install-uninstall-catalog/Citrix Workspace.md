# Citrix Workspace
Download: https://www.citrix.com/downloads/citrix-workspace/

## Windows
**Installation:**
```
CitrixWorkspaceApp.exe /silent
```

**Uninstallation:**
```
msiexec /quiet /x {GUID}
```
(find the correct GUID for your version from Registry: `HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall`)
