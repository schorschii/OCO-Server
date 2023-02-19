# OCO Agent Update
Please keep the agent on your managed computers always up-to-date. It can be easily updated with regular installation procedures.

Always set the action after installation to "Restart Agent" in order to instantly use the new version. (After a manual agent update installation, a restart is required in order to load the new agent binary.)

The agent installer does not overwrite an existing config file.

## Windows
```
oco-agent.exe /SILENT /SUPPRESSMSGBOXES
```

## Linux
(Debian/Ubuntu)
```
gdebi -n oco-agent.deb
```

## macOS
```
installer -pkg oco-agent.pkg -target /
```
