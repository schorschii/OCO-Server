# Adobe Acrobat Reader DC

## Windows
**Installation:**
```
AcroRdrDC2100520048_de_DE.exe /sAll /rs /msi EULA_ACCEPT=YES DISABLEDESKTOPSHORTCUT=1
```

**Uninstallation:**
```
powershell.exe -NoProfile -NonInteractive -Command "$app = 'Adobe Acrobat (64-bit)'; $uninstall = Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*','HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*' -ErrorAction SilentlyContinue | Where-Object { $_.DisplayName -eq $app } | Select-Object -First 1; if (-not $uninstall) { Write-Host $app + ' ist nicht installiert.'; exit 0 }; if ($uninstall.UninstallString -match '\{[0-9A-Fa-f-]+\}') { $ProductCode = $Matches[0]; Write-Host ('Deinstalliere ' + $app + ' ' + $uninstall.DisplayVersion + ' (' + $ProductCode + ')'); $Process = Start-Process -FilePath 'msiexec.exe' -ArgumentList ('/x ' + $ProductCode + ' /qn /norestart') -Wait -PassThru; Write-Host ('msiexec ExitCode: ' + $Process.ExitCode); exit $Process.ExitCode } else { Write-Error 'Kein MSI ProductCode für ' + $app + ' gefunden.'; exit 1 }"
```
(in contrast to static command `msiexec /x <GUID>`, this works for all versions including self-updated versions)
