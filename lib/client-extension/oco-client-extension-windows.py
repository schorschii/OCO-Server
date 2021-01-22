#!/usr/bin/python3

from urllib.parse import unquote
import sys
import os
import ctypes

def main():

	APP_TITLE = 'OCO Client Extension for Windows'
	mBox = ctypes.windll.user32.MessageBoxW

	for arg in sys.argv:

		if(arg.startswith('ping://')):
			try:
				protocolPayload = unquote(arg).replace('ping://', '').split('/')[0]
				os.system('start cmd.exe @cmd /c "ping /t '+protocolPayload+'"')
				sys.exit(0)
			except Exception as e:
				mBox(None, 'Unable to start CMD with ping command. Strange.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('nmap://')):
			try:
				protocolPayload = unquote(arg).replace('nmap://', '').split('/')[0]
				os.chdir('C:\\Program Files (x86)\\Nmap')
				os.system('start cmd.exe @cmd /c ".\\nmap.exe -Pn '+protocolPayload+' && pause"')
				sys.exit(0)
			except Exception as e:
				mBox(None, 'Unable to start Nmap. Please check if it is installed correctly.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('vnc://')):
			try:
				protocolPayload = unquote(arg).replace('vnc://', '').split('/')[0]
				os.chdir('C:\\Program Files\\TightVNC')
				os.system('.\\tvnviewer.exe '+protocolPayload)
				sys.exit(0)
			except Exception as e:
				mBox(None, 'Unable to start TightVNC Viewer. Please check if it is installed correctly.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)

		if(arg.startswith('rdp://')):
			try:
				protocolPayload = unquote(arg).replace('rdp://', '').split('/')[0]
				os.system('mstsc.exe /v:'+protocolPayload)
				sys.exit(0)
			except Exception as e:
				mBox(None, 'Unable to start Windows RDP Viewer. Please check if mstsc.exe exists in PATH.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)


		if(arg.startswith('ssh://')):
			try:
				protocolPayload = unquote(arg).replace('ssh://', '').split('/')[0]
				os.system('start cmd.exe @cmd /c "ssh '+protocolPayload+'"')
				sys.exit(0)
			except Exception as e:
				mBox(None, 'Unable to start SSH session. Please check if ssh.exe is in PATH.\n\n'+str(e), APP_TITLE, 48)
				sys.exit(2)


	print('Error: no valid protocol scheme parameter found.')
	mBox(None, 'No valid protocol scheme parameter found.', APP_TITLE, 48)
	sys.exit(1)


if __name__ == '__main__':
	main()
