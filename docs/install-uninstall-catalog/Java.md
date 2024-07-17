# Java

## Windows
Oracle provides an EXE setup for installing Java. This EXE contains a MSI file, which is automatically extracted into `C:\Users\%username%\AppData\LocalLow\Oracle\Java\` when starting the EXE file. You should use this MSI file for creating a package in OCO (with installation `msiexec /i java.msi`) because of the easy uninstallation with `msiexec /x`. Use the standard MSI install/install commands.

**Installation:**
```
msiexec /i java.msi ALLUSERS=1 /qn
```

**Uninstallation:**
```
msiexec /quiet /x $$ProductCode$$
```

## Linux
Recommendation: use your distro's package manager.

**Installation:**
```
apt install -y openjdk-17-jre
```

**Uninstallation:**
```
apt remove -y openjdk-17-jre
```
