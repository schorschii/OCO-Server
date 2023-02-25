# Mozilla Firefox
Download MSI/DMG: https://www.mozilla.org/de/firefox/all/#product-desktop-release

## Windows

**Installation:**
```
msiexec /quiet /i FirefoxSetup109.0.msi
```

**Uninstallation:**
```
"C:\Program Files\Mozilla Firefox\uninstall\helper.exe" /S
```
(in contrast to `msiexec /x <GUID>`, this works for all versions including self-updated versions)

### Update Note
Firefox updates itself automatically. This is very useful as new security vulnerabilities are getting known frequently in modern browsers, so do not disable this behaviour.

Neverthless, you want to update the package in OCO from time to time, so that newly installed computers do not get a very old Firefox version which must then be updated first by the auto updater before the browser can be used safely. But: you do not want to loose the information on which computers the package is installed, so that Firefox can be easily uninstalled via OCO.

Thats why, do not create a new package version but update the existing Firefox package: replace the installer file in the package archive and update the package version.

## Linux
Recommendation: use your distro's package amnager.

**Installation:**
```
apt install -y firefox firefox-locale-de
```
(replace locale package with your language)

**Uninstallation:**
```
apt remove -y firefox firefox-locale-de
```
(replace locale package with your language)

## macOS
**Installation:**
```
hdiutil attach Firefox.dmg && cp -R /Volumes/Firefox/Firefox.app /Applications && hdiutil detach /Volumes/Firefox
```

**Uninstallation:**
```
rm -r /Applications/Firefox.app
```
