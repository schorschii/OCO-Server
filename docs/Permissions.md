# OCO: Permissions
Every OCO system user has one role assigned. The role defines which actions he is allowed to do. Roles are described as a JSON object in the database table `system_user_role`. By default, there is one role called "Superadmin" which allows everything.

## Create Own R{o|u}les
You can create your own roles by adding a new role record in the database with a JSON-ACL (Access Control List) string like the following.

**Please note:**
- for managing group memberships, the user needs `write` permissions on both the computer/package/report **and** the group.
- to create new packages, the user needs `create` permissions for packages in general and for the corresponding package family
- to create deployment jobs, the user needs `create` permissions for job containers in general and `deploy` permissions for the corresponding computer and package
- item (allow) permissions are inherited recursively through the group tree
  - example: a user can see all computers below a group he has `"item":{"read":true}` permissions for, even if the computer is inside a subgroup where he has no `read` permissions for (unless `"item":{"read":false}` is set)

```
{
    "Special\\ClientApi": true,            <-- allows the user to use the Client API
    "Special\\WebFrontend": true,          <-- allows the user to use the Web Frontend
    "Models\\SystemUser": true,            <-- allows the user to edit system users
    "Models\\Software": true,              <-- allows the user to view recognised software of all computers

    "Models\\Computer": {
        "create": true,                    <-- allows the user to create new computers
        "*": {                             <-- * refers to all computers
            "read": true,
            "write": false,
            "deploy": false,
            "wol": true,
            "delete": false
        },
        "110": {                           <-- refers to computer with id 110
            "read": true,                  <-- allow/disallow to view details of this computer
            "write": true,                 <-- allow/disallow to modify this computer
            "deploy": true,                <-- allow/disallow to deploy to this computer
            "delete": true                 <-- allow/disallow to delete to this computer
        }
    },

    "Models\\ComputerGroup": {
        "create": true,                    <-- allows the user to create new root computer groups
        "3": {
            "create": true,                <-- allows the user to create sub groups of the group with ID 3
            "read": true,                  <-- allows the user to view the contents of the group
            "write": true,                 <-- allows the user to add/remove member to the group and to change the name
            "delete": true,                <-- allows the user to delete the group
            "items": {                     <-- specifies permissions to assigned group members (assigned computers)
                "read": true,
                "write": true,
                "deploy": true,
                "delete": true
            }
        },
        "*": {                             <-- note: "items" is not valid inside "*" - use "Models\\Computer" for general computer permissions
            "create": true,
            "read": true,
            "write": true,
            "delete": true
        }
    },

    "Models\\Package": {
        "create": true,                    <-- allows the user to create new packages
        "*": {
            "read": true,
            "write": false,                <-- do not allow the user to edit all packages
            "download": false,             <-- do not allow the user to download all packages (via the web frontend)
            "deploy": false,               <-- do not allow the user to deploy all packages
            "delete": false                <-- do not allow the user to delete all packages
        }
    },

    "Models\\PackageFamily": {
        "create": false,                   <-- disallows the user to create new package families
        "*": {                             <-- note: "items" is not valid inside "*" - use "Models\\Package" for general package permissions
            "create": false,               <-- disallows the user to create new versions for all package families
            "read": true,
            "write": false,
            "delete": false,
            "deploy": true
        },
        "3": {
            "items": {                      <-- refers to the packages inside this package family
                "read": true,               <-- allows the user to read all packages of this package family
                "write": true,              <-- allows the user to edit all packages of this package family
                "delete": true,             <-- allows the user to delete all packages of this package family
                "deploy": true              <-- allows the user to deploy all packages of this package family
            },
            "write": true,                  <-- allows the user to edit this package family name and description
            "create": true,                 <-- allows the user to create new versions for this package family
            "delete": false                 <-- allows the user to delete this package family
        },
    },

    "Models\\PackageGroup": {
        "create": true,
        "*": {                              <-- note: "items" is not valid inside "*" - use "Models\\Package" for general package permissions
            "create": true,
            "read": true,
            "write": true,
            "deploy": true,
            "delete": true
        },
        "3": {
            "items": {                      <-- refers to the packages inside this package group
                "read": true,               <-- allows the user to read all packages of this package group
                "write": true,              <-- allows the user to edit all packages of this package group
                "delete": true,             <-- allows the user to delete all packages of this package group
                "deploy": true              <-- allows the user to deploy all packages of this package group
            }
        },
    },

    "Models\\Report": {
        "create": true,
        "*": {
            "create": true,
            "read": true,
            "write": true,
            "delete": true
        }
    },

    "Models\\ReportGroup": {
        "create": true,
        "*": {
            "read": true,
            "write": true,
            "delete": true
        }
    },

    "Models\\JobContainer": {
        "create": true,
        "own": {                    <-- refers to all job containers created by the corresponding user
            "read": true,
            "write": true,
            "delete": true          <-- allows the user to delete his own jobs
        },
        "*": {
            "read": true,           <-- allows the user to view other jobs but not to modify/delete them
            "write": false,         <-´
            "delete": false         <-´
        }
    },

    "Models\\DeploymentRule": {
        "create": true,
        "own": {
            "read": true,
            "write": true,
            "delete": true
        },
        "*": {
            "read": true,
            "write": false,
            "delete": false
        }
    }
}
```
