#!/usr/bin/python3

from urllib.parse import unquote
import tempfile
import sys
import os

def main():

	for arg in sys.argv:

		if(arg.startswith('ping://')):
			protocolPayload = unquote(arg).replace('ping://', '')
			os.system('gnome-terminal --wait -- bash -c "ping '+protocolPayload+'; read wait"')
			exit(0)

		if(arg.startswith('nmap://')):
			protocolPayload = unquote(arg).replace('nmap://', '')
			os.system('gnome-terminal --wait -- bash -c "nmap -Pn '+protocolPayload+'; read wait"')
			exit(0)

		if(arg.startswith('vnc://')):
			protocolPayload = unquote(arg).replace('vnc://', '')
			remminaFile = tempfile.gettempdir()+'/oco.client.remmina'
			f = open(remminaFile, "w")
			f.write(
				"[remmina]\n"+
				"name="+protocolPayload+"\n"+
				"server="+protocolPayload+"\n"+
				"protocol=VNC\n"
			)
			f.close()
			os.system('remmina -c '+remminaFile)
			exit(0)

		if(arg.startswith('rdp://')):
			protocolPayload = unquote(arg).replace('rdp://', '')
			remminaFile = tempfile.gettempdir()+'/oco.client.remmina'
			f = open(remminaFile, "w")
			f.write(
				"[remmina]\n"+
				"name="+protocolPayload+"\n"+
				"server="+protocolPayload+"\n"+
				"protocol=RDP\n"+
				"colordepth=0\n"
			)
			f.close()
			os.system('remmina -c '+remminaFile)
			exit(0)

		if(arg.startswith('ssh://')):
			protocolPayload = unquote(arg).replace('ssh://', '')
			remminaFile = tempfile.gettempdir()+'/oco.client.remmina'
			f = open(remminaFile, "w")
			f.write(
				"[remmina]\n"+
				"name="+protocolPayload+"\n"+
				"server="+protocolPayload+"\n"+
				"protocol=SSH\n"
			)
			f.close()
			os.system('remmina -c '+remminaFile)
			exit(0)

	print('Error: no valid protocol scheme parameter found.')
	exit(1)


if __name__ == '__main__':
	main()
