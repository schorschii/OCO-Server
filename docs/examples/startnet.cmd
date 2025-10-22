@echo off

REM ==============================
REM OPEN COMPUTER ORCHESTRATION
REM startnet.cmd
REM Windows Setup Bootstrap Script
REM ------------------------------
REM (c) 2020-2025 Georg Sieber
REM ==============================

REM ==============================
REM Variables
REM ------------------------------
set IMAGE_SHARE=\\YOUROCOSERVER.example.com\images
set SETUP_EXE=Windows10\setup.exe
set PRESEED_DIR=preseed
REM ==============================

echo OPEN COMPUTER ORCHESTRATION (OCO)
echo Initializing Windows Setup. Please wait...

REM init setup environment
wpeinit

REM wait for NICs to come up
wpeutil waitfornetwork
ping 127.0.0.1 -n 6 > nul

REM print ipconfig for debugging
ipconfig
echo.

REM get mac address, replace : with -
SET mac=?
for /F "tokens=*" %%a in ('wmic nic where "NetConnectionStatus=2" list /format') DO (
	for /F "usebackq tokens=1,2 delims==" %%b in ('%%a') DO (
		if "%%b" == "MACAddress" (
			SET mac=%%c
		)
	)
)
SET "mac=%mac::=-%"
echo My MAC    : %mac%

REM get UUID and serial number, remove whitespaces
SET uuid=?
SET serial=?
for /F "tokens=*" %%a in ('wmic csproduct list /format') DO (
	for /F "usebackq tokens=1,2 delims==" %%b in ('%%a') DO (
		if "%%b" == "UUID" (
			SET uuid=%%c
		)
		if "%%b" == "IdentifyingNumber" (
			SET serial=%%c
		)
	)
)
SET "uuid=%uuid: =%"
SET "serial=%serial: =%"
echo My UUID   : %uuid%
echo My Serial : %serial%

REM mount SMB share with windows sources
echo.
echo Mount I: -^> %IMAGE_SHARE%
net use I: %IMAGE_SHARE% /user:dummy dummy

REM have a break to give the poor Windows admin a chance to install missing drivers
if %ERRORLEVEL% NEQ 0 (
	echo Unable to mount the network share with installation sources.
	echo You may need to load a driver in the WinPE environment for your network card.
	echo.
	echo OPTION 1: Do this now by executing "drvload E:\drivers\FILENAME.inf"
	echo Then, check your connectivity with "ipconfig /all" and execute
	echo "%0 /InstallDrivers E:\drivers" to restart the setup with the
	echo option to install the driver in the new Windows installation too.
	echo.
	echo OPTION 2: Include your driver into the boot.wim and install.wim image using "dism".
	echo.
	cmd.exe
	pause
)

REM bypass silly Win 11 restrictions
reg add HKLM\SYSTEM\Setup\LabConfig /v BypassTPMCheck /t REG_DWORD /d 1 /f
reg add HKLM\SYSTEM\Setup\LabConfig /v BypassSecureBootCheck /t REG_DWORD /d 1 /f
reg add HKLM\SYSTEM\Setup\LabConfig /v BypassRAMCheck /t REG_DWORD /d 1 /f
reg add HKLM\SYSTEM\Setup\LabConfig /v BypassCPUCheck /t REG_DWORD /d 1 /f
reg add HKLM\SYSTEM\Setup\LabConfig /v BypassStorageCheck /t REG_DWORD /d 1 /f

REM start setup with specific unattended config if available
if exist "I:\%PRESEED_DIR%\%serial%.xml" (
	echo Starting setup with config file: I:\%PRESEED_DIR%\%serial%.xml ...
	I:\%SETUP_EXE% /unattend:"I:\%PRESEED_DIR%\%serial%.xml" %*
) else (
	if exist "I:\%PRESEED_DIR%\%uuid%.xml" (
		echo Starting setup with config file: I:\%PRESEED_DIR%\%uuid%.xml ...
		I:\%SETUP_EXE% /unattend:"I:\%PRESEED_DIR%\%uuid%.xml" %*
	) else (
		if exist "I:\%PRESEED_DIR%\%mac%.xml" (
			echo Starting setup with config file: I:\%PRESEED_DIR%\%mac%.xml ...
			I:\%SETUP_EXE% /unattend:"I:\%PRESEED_DIR%\%mac%.xml" %*
		) else (
			echo Could not find an unattended installation answer file. We recommend a Linux installation instead.
			I:\%SETUP_EXE% %*
		)
	)
)

REM fallback: start shell for debugging if setup could not be started
cmd.exe
pause
