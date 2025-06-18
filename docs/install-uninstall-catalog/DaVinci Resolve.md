# BlackMagic DaVinci Resolve (Studio)
Download: https://www.blackmagicdesign.com/de/products/davinciresolve

## Windows

**Installation:**
```
DaVinci_Resolve_20.0.0_Windows.exe /i /q /noreboot
```

**Uninstallation:**
```
DaVinci_Resolve_20.0.0_Windows.exe /x /q /noreboot
```

## Linux

**Installation:**
For Ubuntu 24.04 with library adjustments:
```
apt install -y libapr1 libaprutil1 libasound2 libglib2.0-0
bash -c 'SKIP_PACKAGE_CHECK=1 ./DaVinci_Resolve_20.0_Linux.run --install --noconfirm'

mkdir /opt/resolve/libs/obsolete
mv /opt/resolve/libs/libgio* /opt/resolve/libs/obsolete/
mv /opt/resolve/libs/libglib* /opt/resolve/libs/obsolete/
mv /opt/resolve/libs/libgmodule* /opt/resolve/libs/obsolete/
```

**Uninstallation:**
```
/opt/resolve/installer --uninstall --noconfirm /opt/resolve
```
