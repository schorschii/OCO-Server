@echo off

SET LOG=%WINDIR%\Setup\oco-install.log

echo Install OCO Agent... >> %LOG%
"%WINDIR%\Setup\Files\oco-agent.exe" /LOADINF=%WINDIR%\Setup\Files\oco-agent-setup.inf /SILENT >> %LOG% 2>&1

echo. >> %LOG%
echo Cleanup temporary setup files... >> %LOG%
rd /q /s "%WINDIR%\Setup\Files" >> %LOG% 2>&1
del /q /f "%WINDIR%\Panther\unattend.xml" >> %LOG% 2>&1

echo. >> %LOG%
echo Reboot tut gut... >> %LOG%
shutdown /r /f /t 5 >> %LOG% 2>&1
del /q /f "%0" >> %LOG% 2>&1
