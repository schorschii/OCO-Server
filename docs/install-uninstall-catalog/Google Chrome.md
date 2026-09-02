# Google Chrome
Download MSI/PKG: https://chromeenterprise.google/browser/download/?hl=de#windows-tab  
Download DEB: https://www.google.com/intl/de_de/chrome/

## Windows

**Installation:**
```
msiexec /quiet /i GoogleChromeStandaloneEnterprise64.msi
```

**Uninstallation:**
```
powershell.exe -NoProfile -NonInteractive -Command "$app = 'Google Chrome'; $uninstall = Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*','HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*' -ErrorAction SilentlyContinue | Where-Object { $_.DisplayName -eq $app } | Select-Object -First 1; if (-not $uninstall) { Write-Host $app + ' ist nicht installiert.'; exit 0 }; if ($uninstall.UninstallString -match '\{[0-9A-Fa-f-]+\}') { $ProductCode = $Matches[0]; Write-Host ('Deinstalliere ' + $app + ' ' + $uninstall.DisplayVersion + ' (' + $ProductCode + ')'); $Process = Start-Process -FilePath 'msiexec.exe' -ArgumentList ('/x ' + $ProductCode + ' /qn /norestart') -Wait -PassThru; Write-Host ('msiexec ExitCode: ' + $Process.ExitCode); exit $Process.ExitCode } else { Write-Error 'Kein MSI ProductCode für ' + $app + ' gefunden.'; exit 1 }"
```
(in contrast to static command `msiexec /x <GUID>`, this works for all versions including self-updated versions)

### Update Note
Chrome updates itself automatically. This is very useful as new security vulnerabilities are getting known frequently in modern browsers, so do not disable this behaviour.

Neverthless, you want to update the package in OCO from time to time, so that newly installed computers do not get a very old Chrome version which must then be updated first by the auto updater before the browser can be used safely. But: you do not want to loose the information on which computers the package is installed, so that Chrome can be easily uninstalled via OCO.

Thats why, do not create a new package version but update the existing Chrome package: replace the installer file in the package archive and update the package version.

## Linux

**Installation:**
```
gdebi -n google-chrome-stable_current_amd64.deb
```

**Uninstallation:**
```
apt remove -y google-chrome-stable
```

## macOS
**Installation:**
```
installer -pkg GoogleChrome.pkg -target /
```

**Uninstallation:**
```
rm -r /Applications/Google\ Chrome.app
```
