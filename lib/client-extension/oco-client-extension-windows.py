#!/usr/bin/python3

from urllib.parse import unquote
import sys
import os
import ctypes

def main():

	APP_TITLE = 'OCO Client Extension for Windows'
	msgBox = ctypes.windll.user32.MessageBoxW

	for arg in sys.argv:

		if(arg.startswith('ping://')):
			try:
				os.system('start cmd.exe @cmd /c "ping /t '+shellQuote(getProtocolPayload(arg))+' & pause"')
				sys.exit(0)
			except Exception as e:
				msgBox(None, 'Unable to start CMD with ping command. Strange.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('nmap://')):
			try:
				os.chdir('C:\\Program Files (x86)\\Nmap')
				os.system('start cmd.exe @cmd /c ".\\nmap.exe -Pn '+shellQuote(getProtocolPayload(arg))+' & pause"')
				sys.exit(0)
			except Exception as e:
				msgBox(None, 'Unable to start Nmap. Please check if it is installed correctly.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('vnc://')):
			try:
				os.chdir('C:\\Program Files\\TightVNC')
				os.system('.\\tvnviewer.exe '+shellQuote(getProtocolPayload(arg)))
				sys.exit(0)
			except Exception as e:
				msgBox(None, 'Unable to start TightVNC Viewer. Please check if it is installed correctly.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('rdp://')):
			try:
				os.system('mstsc.exe /v:'+shellQuote(getProtocolPayload(arg)))
				sys.exit(0)
			except Exception as e:
				msgBox(None, 'Unable to start Windows RDP Viewer. Please check if mstsc.exe exists in PATH.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('ssh://')):
			try:
				returnCode = os.system('start cmd.exe @cmd /c "ssh '+shellQuote(getProtocolPayload(arg))+' & pause"')
				sys.exit(0)
			except Exception as e:
				msgBox(None, 'Unable to start SSH session. Please check if ssh.exe is in PATH.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

	print('Error: no valid protocol scheme parameter found.')
	msgBox(None, 'No valid protocol scheme parameter found.', APP_TITLE, 48)
	sys.exit(1)

def getProtocolPayload(protocolString):
	splitter = unquote(protocolString).split('://')
	if(len(splitter) > 1):
		return splitter[1].strip('/')
	else:
		return protocolString

def shellQuote(s):
	return '"' + s.replace('"', '') + '"'


if __name__ == '__main__':
	main()
