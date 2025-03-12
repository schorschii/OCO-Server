# OCO Documentation
This page gives an overview of the available documentation for the OCO project.

The documentation is currently only available in English.

## Wording
| Term               | Description        |
| ------------------ | ------------------ |
| **OCO Server**     | the database server & PHP server scripts |
| **OCO Client**     | the web interface, used by administrators to view computer information and to create/control packages, software jobs, reports etc. |
| **OCO Agent**      | the service installed on every managed client computer (communicates with the server and executes jobs) |
| **Client API**     | the JSON-REST-API used by administrators or other applications to automate workflows |
| **Agent API**      | the JSON-REST-API used by the agent to communicate with the OCO server |
| **Self Service Portal**  | web interface for non-admin users which allows to install admin-approved packages on their own computers |

## System Requirements
Please refer to [README.md in the repo root](../README.md).

## Agent Installation Guide & Documentation
Please refer to the [README.md in the OCO Agent repo](https://github.com/schorschii/OCO-Agent).

## Installation/Upgrade
- [Installation Guide](Server-Installation.md)
- [Upgrade Guide](Server-Upgrade.md)

## Server Operation Instructions
- [Computers](Computers.md)
- [Packages](Packages.md)
- [Deployment](Deploy-Install-Uninstall.md)
- [Automated OS Installation](OS-Installation.md)
- [Reports](Reports.md)
- [Web Application ("OCO Client")](WebApplication.md)
- [Permissions](Permissions.md)
- [Logging](Logging.md)
- [Extensions](Extensions.md)
- [Self Service Portal](Self-Service.md)
- [Silent Install/Uninstall Commands Catalog](install-uninstall-catalog)
- [Mobile Device Management](Mobile-Device-Management.md)

## Developer Documentation
- [Client API](Client-API.md)
- [Agent API](Agent-API.md)
- [Architecture Decision Records](decisions)
- [Database Schema](Database-Schema.md)
- [WebApp Architecture](WebApp-Architecture.md)
