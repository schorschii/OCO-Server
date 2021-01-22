#!/usr/bin/python3

from urllib.parse import unquote
import sys
import os

def main():

	for arg in sys.argv:

		if(arg.startswith('ping://')):
			protocolPayload = unquote(arg).replace('ping://', '').split('/')[0]
			os.system('start cmd.exe @cmd /c "ping /t '+protocolPayload+'"')
			sys.exit(0)

		if(arg.startswith('nmap://')):
			protocolPayload = unquote(arg).replace('nmap://', '').split('/')[0]
			os.chdir('C:\\Program Files (x86)\\Nmap')
			os.system('start cmd.exe @cmd /c ".\\nmap.exe -Pn '+protocolPayload+' && pause"')
			sys.exit(0)

		if(arg.startswith('vnc://')):
			protocolPayload = unquote(arg).replace('vnc://', '').split('/')[0]
			os.chdir('C:\\Program Files\\TightVNC')
			os.system('.\\tvnviewer.exe '+protocolPayload)
			sys.exit(0)

		if(arg.startswith('rdp://')):
			protocolPayload = unquote(arg).replace('rdp://', '').split('/')[0]
			os.system('mstsc.exe /v:'+protocolPayload)
			sys.exit(0)

		if(arg.startswith('ssh://')):
			protocolPayload = unquote(arg).replace('ssh://', '').split('/')[0]
			os.system('start cmd.exe @cmd /c "ssh '+protocolPayload+'"')
			sys.exit(0)

	print('Error: no valid protocol scheme parameter found.')
	sys.exit(1)


if __name__ == '__main__':
	main()
