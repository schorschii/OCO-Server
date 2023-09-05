@echo off

REM ==============================
REM OPEN COMPUTER ORCHESTRATION
REM startnet.cmd
REM Windows Setup Bootstrap Script
REM ------------------------------
REM (c) 2020-2023 Georg Sieber
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

REM get mac address, UUID and serial number
for /F "tokens=*" %%a in ('ipconfig /all') DO (
	for /F "tokens=1,2 delims=:" %%b in ('echo %%a') DO (
		if "%%b" == "Physische Adresse . . . . . . . . " (
			SET mac=%%c
		)
	)
)
SET "mac=%mac: =%"
echo My MAC    : %mac%

for /F "tokens=*" %%a in ('wmic csproduct list /format') DO (
	for /F "tokens=1,2 delims==" %%b in ('echo %%a') DO (
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

REM start setup with specific unattended config if available
if exist I:\%PRESEED_DIR%\%serial%.xml (
	echo Starting setup with config file: I:\\%PRESEED_DIR%\\%serial%.xml ...
	I:\%SETUP_EXE% /unattend:I:\%PRESEED_DIR%\%serial%.xml
) else (
	if exist I:\%PRESEED_DIR%\%uuid%.xml (
		echo Starting setup with config file: I:\\%PRESEED_DIR%\\%uuid%.xml ...
		I:\%SETUP_EXE% /unattend:I:\%PRESEED_DIR%\%uuid%.xml
	) else (
		if exist I:\%PRESEED_DIR%\%mac%.xml (
			echo Starting setup with config file: I:\\%PRESEED_DIR%\\%mac%.xml ...
			I:\%SETUP_EXE% /unattend:I:\%PRESEED_DIR%\%mac%.xml
		) else (
			echo Could not find an unattended installation answer file. We recommend a Linux installation instead.
			I:\%SETUP_EXE%
		)
	)
)

REM fallback: start shell for debugging if setup could not be started
cmd.exe
pause
