# OCO Documentation
Cette page donne un aperçu des documentations disponibles du projet OCO.

Celle-ci est actuellement disponible qu\'en Anglais.

## Wording
| Terme               | Description        |
| ------------------ | ------------------ |
| **OCO Server**     | Le serveur de base de données & le serveur de scripts PHP |
| **OCO Client**     | L\'interface web, utilisée par les administrateurs pour visualiser les informations ordinateurs et créer/gérer les paquets, tâches, rapports etc. |
| **OCO Agent**      | Le service installé sur chaque ordinateur à superviser (il communique avec le serveur et exécute les tâches) |
| **Client API**     | La JSON-REST-API utilisée par les administrateurs ou d\'autres applications pour automatiser des workflows |
| **Agent API**      | La JSON-REST-API utilisée par l\'agent pour communiquer avec le serveur OCO |
| **Self Service Portal**  | L\'interface web pour les utilisateurs non-admin leur permettant d\'installer sur leur ordinateur des paquets déjà approuvés par un admin |

## Configuration requise
Veuillez consulter le [README.md à la racine du repo](../README.md).

## Installation de l\'agent: Guide & Documentation
Veuillez consulter le [README.md dans le repo OCO Agent](https://github.com/schorschii/OCO-Agent).

## Installation/Mise à jour
- [Guide d\'installation](Server-Installation.md)
- [Guide de mise à jour](Server-Upgrade.md)

## Instructions des opération serveur
- [Ordinateurs](Computers.md)
- [Paquets](Packages.md)
- [Déploiment](Deploy-Install-Uninstall.md)
- [Installation de l\'OS automatisée](OS-Installation.md)
- [Rapports](Reports.md)
- [Application Web ("OCO Client")](WebApplication.md)
- [Permissions](Permissions.md)
- [Journalisation](Logging.md)
- [Extensions](Extensions.md)
- [Portail self-service](Self-Service.md)
- [Liste de commandes pour Install/Désinstall silencieuse](install-uninstall-catalog)

## Documentation pour Développeur
- [Client API](Client-API.md)
- [Agent API](Agent-API.md)
- [Architecture Decision Records](decisions)

### Architecture Webapp 
![Architecture de la WebApp](../.github/oco-architecture.png)
