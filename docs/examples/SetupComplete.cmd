@echo off

echo "OCO-Agent-Installation..." >> C:\Windows\installation.log
"%WINDIR%\Setup\Files\oco-agent.exe" /LOADINF=%WINDIR%\Setup\Files\oco-agent-setup.inf /SILENT >> C:\Windows\installation.log

echo "Temporäre Setup-Dateien löschen..." >> C:\Windows\installation.log
rd /q /s "%WINDIR%\Setup\Files" >> C:\Windows\installation.log

echo "Reboot tut gut..." >> C:\Windows\installation.log
shutdown /r /f /t 5 >> C:\Windows\installation.log
del /q /f "%0" >> C:\Windows\installation.log
