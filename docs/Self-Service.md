# OCO: Self Service Portal
The OCO Self Service Portal is a web interface for non-admin users which allows to install admin-approved packages on their own computers (this means computers where the user has logged in recently).

## Setup
In order to provide the Self Service Portal to your users please create a new virtual host on your webserver, using the `self-service` folder as webroot (similar to the `frontend` directory).

The portal must then be enabled by setting the config option "Self Service Enabled" on the settings page.

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
    "create_priority": -1,
    "own": {
      "read": true,
      "delete": true
    }
  }
}
```
In this example, the domain user can read, deploy and send WOL packets to computers on which the last login is not longer than 604800 seconds (7 days) ago. This time frame is the mechanism which determines which domain users have access on which computers in the portal.

Furthermore, the domain user is allowed to deploy packages from the package group with ID "20". OCO admins should add packages into this group to allow the self service deployment.

Last, the self service user is allowed to create job containers with a priority of -1 and to read and delete his own jobs.

### Password
The password and role can be manually set in the settings of the OCO admin web frontend. System user management permissions are required in order to do this.

### LDAP Sync
The second way to authorize a domain user for the portal is to set the LDAP flag and role through the LDAP sync.

Please configure the domain user LDAP sync by adjusting the config on the "Settings" > "Self Service" page in the web frontend. The syntax for the JSON configuration string is exactly the same as for the system users. Please have a look at [Server Installation Guide](Server-Installation.md) for more information. The role ID can be viewed in the admin web interface on the self service roles settings page.

After setting up the LDAP sync, start the first domain user sync manually in the OCO web frontend and check its output for errors.

The LDAP sync will assign the ldap flag and role with the given ID to the domain user if the UUID in the `domain_user` table (as reported by the agent) matches with the LDAP GUID attribute (`objectGUID`).

Please note that **no** user attributes will be synced via LDAP. The OCO agent is the only data source for domain user information.

## Self Service Jobs
Self service jobs can be monitored and changed by admins in the admin web frontend. Self service job containers are displayed separately besides normal job containers, created by system users.

## Custom Help
You can provide custom (help) HTML pages for your self service users. If you want to do this, please create a file called `index.html` inside the `self-service/views/help` directory. You can link to other pages in this directory by adding links like `<a href="?view=help&page=page2.html">PAGE 2</a>` into your HTML file.
