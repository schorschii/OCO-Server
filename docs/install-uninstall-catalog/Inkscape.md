# Inkscape
Download MSI/DMG: https://inkscape.org/release/

## Windows

**Installation:**
```
msiexec /i inkscape-1.2-x64.msi ALLUSERS=1 /qn
```

**Uninstallation:**
```
msiexec /quiet /x $$ProductCode$$
```

## Linux
Recommendation: use your distro's package manager.

**Installation:**
```
apt install -y inkscape
```

**Uninstallation:**
```
apt remove -y inkscape
```

## macOS
**Installation:**
```
hdiutil attach inkscape.dmg && cp -R /Volumes/Inkscape/Inkscape.app /Applications && hdiutil detach /Volumes/Inkscape
```

**Uninstallation:**
```
rm -r /Applications/Inkscape.app
```
