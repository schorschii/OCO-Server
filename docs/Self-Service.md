# OCO: Self Service Portal
The OCO Self Service Portal is a web interface for non-admin users which allows to install admin-approved packages on their own computers (this means computers where the user has logged in recently).

## Setup
In order to provide the Self Service Portal to your users please create a new virtual host on your webserver, using the `self-service` folder as webroot (similar to the `frontend` directory).

The portal must then be enabled by setting the config option `SELF_SERVICE_ENABLED` to true.

## Access And Permissions
To log in into the Self Service Portal, accounts from the table `domain_user` are used. These accounts are created and assigned to computers by the [OCO agent](https://github.com/schorschii/oco-agent) only. Manual creation of domain users is not supported.

To be able to log in, a role must be assigned and either a password or the LDAP flag must be set on the domain user account.

### Role Assignment
A domain user role is defined by a JSON string as known from the [system user roles](Permissions.md). A domain user role looks like the following:
```
{
  "Models\\Computer": {
    "*": {
      "read": 604800,
      "wol": 604800,
      "deploy": 604800
    }
  },
  "Models\\PackageGroup": {
    "20": {
      "items": {
        "read": true,
        "deploy": true
      }
    }
  },
  "Models\\JobContainer": {
    "create": true,
    "own": {
      "read": true,
      "delete": true
    }
  }
}
```
In this example, the domain user can read, deploy and send WOL packets to computers on which the last login is not longer than 604800 seconds (7 days) ago. This time frame is the mechanism which determines which domain users have access on which computers in the portal.

Furthermore, the domain user is allowed to deploy packages from the package group with ID "20". OCO admins should add packages into this group to allow the self service deployment.

Last, the self service user is allowed to create, read and delete own jobs.

### Password
The password and role can be manually set in the settings of the OCO admin web frontend. System user management permissions are required in order to do this.

### LDAP Sync
The second way to authorize a domain user for the portal is to set the LDAP flag and role through the LDAP sync. It requires that the config options `LDAP_SERVER`, `LDAP_USER`, `LDAP_PASS`, `LDAP_DOMAIN`, `LDAP_QUERY_ROOT` are set. Please have a look at the [server installation guide](Server-Installation.md) for more information.

In addition to that, please configure the domain user LDAP sync by setting the following values. The role ID can be viewed in the admin web interface on the self service roles settings page.
```
const SELF_SERVICE_LDAP_GROUPS = [
	// 'LDÁP Path' => OCO Role ID
	'CN=OcoSelfService,OU=Benutzer,DC=sieber,DC=systems' => 1,
];
const SELF_SERVICE_DEFAULT_ROLE_ID = 1;
```
After that, start the first domain user LDAP sync manually in the OCO web frontend and check its output for errors.

The LDAP sync will assign the ldap flag and role with the given ID to the domain user if the UUID in the `domain_user` table (as reported by the agent) matches with the LDAP GUID attribute (`objectGUID`).

## Self Service Jobs
Self service jobs can be monitored and changed by admins in the admin web frontend. Self service job containers are displayed separately besides normal job containers, created by system users.
