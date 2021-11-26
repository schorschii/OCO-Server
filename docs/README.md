# OCO Documentation
This document gives an overview of the available documentation for the OCO project.

## Wording
| Term               | Description        |
| ------------------ | ------------------ |
| **OCO Server**     | the database server & PHP server scripts |
| **OCO Client**     | the web interface, used by administrators to view computer information and to create/control packages, software jobs, reports etc. |
| **OCO Agent**      | the service installed on every managed client computer (communicates with the server and executes jobs) |
| **Client API**     | the API used by administrators or other applications to automate workflows |
| **Agent API**      | the API used by the agent to communicate with the OCO server |
| **Self Service Portal**  | web interface for non-admin users which allows to install admin-approved packages on their own computers |

## Installation/Upgrade Instructions & System Requirements
Please refer to [README.md in the repo root](../README.md).

## Agent Specific Documentation
Please refer to the [README.md in the OCO Agent repo](https://github.com/schorschii/OCO-Agent).

## Detailed Server Documents
- [Client API](Client-API.md)
- [Computers](Computers.md)
- [Extensions](Extensions.md)
- [Automated OS Installation](OS-Installation.md)
- [Packages](Packages.md)
- [Reports](Reports.md)
- [Web Application ("OCO Client")](WebApplication.md)
