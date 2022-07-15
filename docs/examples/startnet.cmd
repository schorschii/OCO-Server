@echo off

REM ==============================
REM OPEN COMPUTER ORCHESTRATION
REM startnet.cmd
REM Windows Setup Bootstrap Script
REM ------------------------------
REM (c) 2020-2022 Georg Sieber
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

REM wait for some NICs to come up (Fujitsu Qs)
ping 127.0.0.1 -n 6 > nul

REM print ipconfig for debugging
ipconfig

REM get mac address
for /F "tokens=*" %%a in ('ipconfig /all') DO (
	for /F "tokens=1,2 delims=:" %%b in ('echo %%a') DO (
		if "%%b" == "Physische Adresse . . . . . . . . " (
			SET mac=%%c
		)
	)
)
SET "mac=%mac: =%"

REM print mac address for debugging
echo.
echo Meine MAC-Adresse: %mac%

REM mount SMB share with windows sources
echo.
echo Mount I: -^> %IMAGE_SHARE%
net use I: %IMAGE_SHARE% /user:dummy dummy

REM start setup with specific unattended config if available
if exist I:\%PRESEED_DIR%\%mac%.xml (
	echo Starting setup with config file: I:\\%PRESEED_DIR%\\%mac%.xml ...
	I:\%SETUP_EXE% /unattend:I:\%PRESEED_DIR%\%mac%.xml
) else (
	echo Could not find an unattended installation answer file. We recommend a Linux installation instead.
	I:\%SETUP_EXE%
)

REM fallback: start shell if setup could not be started
cmd.exe
pause
