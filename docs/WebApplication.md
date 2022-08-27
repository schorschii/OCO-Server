# OCO: Web Application
The OCO web frontend allows you to manage computers & packages and view their details and relationships.

## Force Remove
When you try to remove a computer/package group with subgroups or a computer/package with pending jobs, the web frontend will abort the action and tell you that you first need to remove the subgroups/pending jobs.

You can force the deletion of the object by holding the shift key pressed when clicking on the "Remove" button. This will automatically delete all subgroups/pending jobs.

## Computer Commands / Client Extension
OCO has a feature called "Computer Commands" which allows you to seamlessly open VNC, RDP, SSH sessions with one click on the computer details page. These Commands can be defined by yourself by editing the array `COMPUTER_COMMANDS` in the config file.

When clicking on a Computer Command button, a custom URL will be opened in your browser. In case of the pre-defined commands, this will be `vnc://HOSTNAME`, `rdp://HOSTNAME`, `ssh://HOSTNAME`, `ping://HOSTNAME` and `nmap://HOSTNAME`. You need an appropriate counterpart on your computer to handle this URLs. For the pre-defined commands, this will be the OCO Client Extension, found in `/client-extension`. Please install it on your computer. After that, the Client Extension will handle the VNC, RDP and SSH URLs and open an appropriate program like Remmina to start the remote access.

It is recommended to visit the Github Releases Page to download a ready-to-use package which automatically installs the OCO Client Extension on your system. Alternatively, you can configure the Client Extension by yourself using the follwing information.

### Linux XDG Configuration
Copy `oco-client-extension-linux.desktop` into `/usr/share/applications` and execute `update-desktop-database`. Copy `oco-client-extension-linux.py` into `/usr/bin`, make sure it is executable and check if Python 3 is working properly.

It is possible that another application already registered the `ssh://` protocol. Firefox lets you select the application which should be used to open these URLs but Chrome always uses the default application. In this case, you can set the OCO Client Extensions as default with: `xdg-settings set default-url-scheme-handler ssh oco-client-extension-linux.desktop`.

### Windows Configuration
Compile the Windows Client extension into an `.exe` using pyinstaller. Then, move the binary to `C:\Program Files\OCO Client Extension\oco-client-extension-windows.exe` and execute `oco-client-extension-windows.reg` to register the URL Schemes.

Now, install TightVNC and Nmap if you want to use VNC and Nmap. The Client Extensions expecting that those programs are installed in the default directories, so please do not change the installation directory.

## Message Of The Day (MOTD)
The message of the day is displayed on the OCO homepage and can be modified in the `conf.php` file (constant with name `MOTD`).

Suggestions for your MOTD:
```
I know what you did steve.
```
```
WARNING: The consumption of alcohol may lead you to think people are laughing WITH you.
```
```
🌴 Yes, we can UTF8! 🌈
```
You can also insert some useful links (the MOTD is intentionally not HTML escaped).
```
WARNING: This device may contain Internet
<br><a href="/phpmyadmin" target="_blank">phpMyAdmin</a> ‧ <a href="https://bongo.cat/" target="_blank">BongoCat</a>
```
```
This server runs on Runlevel 7™.
```

## Web UI Default Values
You can customize the default values of the deployment, package creation and other forms by editing the `DEFAULTS` constant in the `config.php` file.

## Web Design Customization
You can customize the web design by creating a `*.css` file inside `/frontend/css/css.d` with your desired CSS rules. This ensures that your custom CSS is not overwritten with an update.

Why?
- to adapt your corporate design
- to be able to easily distinguish a test system from the production system

Example:
```
/* construction site header for test systems */
#header {
	background-image: repeating-linear-gradient(45deg, yellow, yellow 20px, black 20px, black 40px);
	font-weight: bold;
	text-shadow: 0px 0px 2px black, 0px 0px 2px black;
}
#login-bg {
	background-image: url('custombg.jpg');
}
```
