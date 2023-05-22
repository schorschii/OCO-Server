# Open Computer Orchestration
**Self Hosted / On Premise Desktop and Server Inventory, Management & Software Deployment**

The Open Computer Orchestration (OCO) project enables IT administrators to centrally manage Linux, macOS and Windows machines using a comfortable web interface. It provides software deployment features, a user-computer logon overview, lists software packages installed on each computer ("recognised software") and features a fine-grained permission/role system.

It focuses on easy usability (UI/UX), simplicity (assessable code with minimal external dependencies) and performance (you can manage many computers with minimal server resources).

You can rollout new software or updates (patch management) for any software you like. Just create a OCO package and distribute it to your clients. You can track the progress and view the results (exit code and command output) of every software job. Custom reports allow you to get even more out of your data.

- [OCO Server](https://github.com/schorschii/oco-server)
- [OCO Server Extensions](https://github.com/schorschii/oco-server-extensions)
- [OCO Agent](https://github.com/schorschii/oco-agent)

## About OCO Server
The OCO server provides the Agent API (used to communicate with the OCO agent), the [Client API](docs/Client-API.md) (can be used by admins to automate workflows, e.g. for Continuous Integration/Delivery/Deployment) and the admin web frontend for the OCO project. On the web frontend you can view computer details and configure software deployment jobs. It can be installed on any Linux distribution. Data is stored in a MySQL database.

![Schematic](.github/oco-schematic.png)

### Screenshots
![Computers](.github/1.png)
![Deployment Wizard](.github/2.png)
![Dark Mode](.github/3.png)

## System Requirements
### Server
- Software
  - any Linux Distribution
  - MySQL/MariaDB Database Server
  - Apache2 Web Server
  - PHP 7.3 or newer
- Hardware Recommendations for ~600 Managed Computers
  - 4 CPU cores
  - 8GB RAM
  - 20GB HDD + storage for your packages, depending on what you want to deploy
  - at least Gigabit Ethernet, 10G recommended
  - for WOL & OS installation via network: network card in every network where your client computers are

### (Admin) Client
- Chromium-based Web Browser (Chrome/Chromium v80 or newer, Opera etc.)
- Firefox (v80 or newer)
- (optional) OCO Client Extensions (for opening RDP, VNC, SSH sessions from the web interface)

### Agent (For Managed Computers)
- please refer to [OCO Agent](https://github.com/schorschii/oco-agent)

## Translations & Contributions Welcome!
Please open a pull request for any improvements you like!

For translators: the language files are in `lib/Language/<langcode>.php`. There you can insert new files with your translations or correct existing ones. Thank you very much!

## Information, Manual, Documentation
**Please read the documentation in the [`/docs`](docs/README.md) folder.**

Quick Links:
- [Overview](docs/README.md)
- [Installation Guide](docs/Server-Installation.md)

If you like this project, please do not forget to star the GitHub repo.

## License
The Open Computer Orchestration Project is open source, which means you have the freedom to view the source code, report issues and submit improvements on GitHub, which are very welcome. However, a license is required if you want to manage more than 20 computers with this system. Please buy the appropriate licenses [here](https://georg-sieber.de/?page=oco).

## Support & Specific Adjustments
You need support or specific adjustments for your environment? You can hire me to extend OCO to your needs or to write custom reports etc. Please [contact me](https://georg-sieber.de/?page=impressum) if you are interested.
