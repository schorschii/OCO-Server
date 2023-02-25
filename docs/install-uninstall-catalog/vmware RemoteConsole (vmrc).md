# vmware RemoteConsole (vmrc)

## Windows

**Installation:**
```
VMware-VMRC-12.0.1-18113358.exe /s /v "/qn EULAS_AGREED=1 AUTOSOFTWAREUPDATE=0 DATACOLLECTION=0"
```

**Uninstallation:**
```
msiexec /quiet /x {0FBB958D-A777-4D04-A8AA-ADF11224FE06}
```

## Linux
While VMware documents the silent options for the Windows setup file [1], there is (currently) no official documentation how to silently install the Linux version with options for data collection and auto update. VMware docs currently only describes the manual installation of the Linux version [2].

The necessary parameters were figured out by manually extracting the bundle and reverse-engineering the python installer scripts.

```
./VMware-Remote-Console-12.0.1-18113358.x86_64.bundle --console --eulas-agreed --required -s vmware-vmrc-app dataCollectionEnabled no -s vmware-vmrc-app softwareUpdateEnabled no
```

**Parameter description:**
```
  --console      : do not use the GUI installer
  --eulas-agreed : do not show the EULA
  --required     : hide all questions, use default values
  -s vmware-vmrc-app dataCollectionEnabled no : disable telemetry
  -s vmware-vmrc-app softwareUpdateEnabled no : disable auto updater
```

# Links
[1] https://docs.vmware.com/en/VMware-Remote-Console/12.0/com.vmware.vmrc.vra.doc/GUID-5E84D36C-5676-4CB6-BA4E-970051F72E43.html

[2] https://docs.vmware.com/en/VMware-Remote-Console/12.0/com.vmware.vmrc.vra.doc/GUID-121D9620-3036-4CF5-B19B-BBD629406430.html
