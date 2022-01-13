# OCO: Permissions
Every OCO system user has one role assigned. The role defines which actions he is allowed to do. Roles are described as a JSON object in the database table `system_user_role`. By default, there is one role called "Superadmin" which allows everything.

## Create Own R{o|u}les
You can create your own roles by adding a new role record in the database with a JSON-ACL (Access Control List) string like the following.

**Please note:**
- for managing group memberships, the user needs `write` permissions on both the computer/package/report **and** the group.
- to create new packages, the user needs `create` permissions for packages in general and for the corresponding package family
- to create deployment jobs, the user needs `create` permissions for job containers in general and `deploy` permissions for the corresponding computer and package
- item (allow) permissions are inherited recursively through the group tree
  - example: a user can see all computers under a specific group he has `"item":{"read":true}` permissions for, even if the computer is inside a subgroup where he has no `read` permissions for

```
{
    "client_api_allowed": true,            <-- allows the user to use the Client API
    "client_web_frontend_allowed": true,   <-- allows the user to use the Web Frontend
    "system_user_management": true,        <-- allows the user to edit system users
    "recognised_software_view": true,      <-- allows the user to view recognised software over all computers

    "computer_management": {
        "create": true,                    <-- allows the user to create new computers
        "*": {                             <-- * refers to all computers
            "read": true,
            "write": false,
            "deploy": false,
            "delete": false
        },
        "110": {                           <-- refers to computer with id 110
            "read": true,                  <-- allow/disallow to view details of this computer
            "write": true,                 <-- allow/disallow to modify this computer
            "deploy": true,                <-- allow/disallow to deploy to this computer
            "delete": true                 <-- allow/disallow to delete to this computer
        }
    },

    "computer_group_management": {
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
        "*": {
            "create": true,
            "read": true,
            "write": true,
            "delete": true
        }
    },

    "package_management": {
        "create": true                     <-- allows the user to create new packages
    },

    "package_family_management": {
        "create": false,                   <-- disallows the user to create new package families
        "delete": false,
        "*": {
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

    "package_group_management": {
        "create": true,
        "*": {
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

    "report_management": {
        "create": true,
        "*": {
            "create": true,
            "read": true,
            "write": true,
            "delete": true
        }
    },

    "report_group_management": {
        "create": true,
        "*": {
            "read": true,
            "write": true,
            "delete": true
        }
    },

    "job_container_management": {
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
}
```
