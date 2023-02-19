# Java

## Windows
Oracle provides an EXE setup for installing Java. This EXE contains a MSI file, which is automatically extracted into `C:\Users\%username%\AppData\LocalLow\Oracle\Java\` when starting the EXE file. You should use this MSI file for creating a package in OCO because of the easy uninstallation with `msiexec /x`. Use the standard MSI install/install commands.
