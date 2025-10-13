#!/usr/bin/python3

from urllib.parse import unquote
from pathlib import Path
import subprocess
import sys
import os

def main():

	CONFIG_PATH  = str(Path.home())+'/.config/oco-client'
	if(not os.path.isdir(CONFIG_PATH)):
		os.makedirs(CONFIG_PATH, exist_ok=True)

	REMMINA_FILE = CONFIG_PATH+'/oco-client.remmina'
	TERMINAL_EMULATORS = [
		['/usr/bin/gnome-terminal', '--wait', '--', 'bash', '-c'],
		['/usr/bin/xfce4-terminal', '-x', 'bash', '-c'],
		['/usr/bin/konsole', '-e', 'bash', '-c'],
		['/usr/bin/xterm', '-e', 'bash', '-c'],
	]
	guiTerminalArgs = TERMINAL_EMULATORS[0]
	for emulator in TERMINAL_EMULATORS:
		if(os.path.isfile(emulator[0])):
			guiTerminalArgs = emulator
			break

	for arg in sys.argv:

		if(arg.startswith('ping://')):
			guiTerminalArgs.append('ping '+shellQuote(getProtocolPayload(arg))+'; read wait')
			subprocess.run(guiTerminalArgs)
			sys.exit(0)

		if(arg.startswith('nmap://')):
			guiTerminalArgs.append('nmap '+shellQuote(getProtocolPayload(arg))+'; read wait')
			subprocess.run(guiTerminalArgs)
			sys.exit(0)

		if(arg.startswith('vnc://')):
			f = open(REMMINA_FILE, "w")
			f.write(
				"[remmina]\n"+
				"name="+getProtocolPayload(arg)+"\n"+
				"server="+getProtocolPayload(arg)+"\n"+
				"protocol=VNC\n"
			)
			f.close()
			subprocess.run(['remmina', '-c', REMMINA_FILE])
			sys.exit(0)

		if(arg.startswith('rdp://')):
			f = open(REMMINA_FILE, "w")
			f.write(
				"[remmina]\n"+
				"name="+getProtocolPayload(arg)+"\n"+
				"server="+getProtocolPayload(arg)+"\n"+
				"protocol=RDP\n"+
				"colordepth=0\n"
			)
			f.close()
			subprocess.run(['remmina', '-c', REMMINA_FILE])
			sys.exit(0)

		if(arg.startswith('ssh://')):
			f = open(REMMINA_FILE, "w")
			f.write(
				"[remmina]\n"+
				"name="+getProtocolPayload(arg)+"\n"+
				"server="+getProtocolPayload(arg)+"\n"+
				"protocol=SSH\n"
			)
			f.close()
			subprocess.run(['remmina', '-c', REMMINA_FILE])
			sys.exit(0)

	print('Error: no valid protocol scheme parameter found.')
	sys.exit(1)

def getProtocolPayload(protocolString):
	splitter = unquote(protocolString).split('://', 1)
	if(len(splitter) > 1):
		return splitter[1].strip('/')
	else:
		return protocolString

def shellQuote(s):
	return "'" + s.replace("'", "") + "'"


if __name__ == '__main__':
	main()
