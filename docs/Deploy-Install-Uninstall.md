# OCO: Deploy

Deployment jobs (installation or uninstallation of a OCO package) are always subordinate of a job container or deployment rule. The job container/deployment rule holds various meta data about the jobs, e.g. a description, start and end date, priority etc.

# Install Packages via Job Container (Static Jobs)
Open the 'Deployment Assistant' by clicking on 'New Job Container' on the 'Job Containers' page (below 'Jobs') in the web console. The assistant can also be opened by clicking 'Deploy' on the computer detail page or the package detail page.

## Start/End Date
You can select a start date in the future. Then, the jobs are schedules and executed when the start date is reached. You can also select an end date after which the jobs will no longer be executed. This can be helpful if you do not want to interrupt your user and only execute updates within a specific time range.

## WOL/Shutdown
You can enable sending Wake On Lan packets on the job start time and automatic shutdown after completion. This will only shutdown computers, which could be waked up - all computers, which are already powered on will not be shutted down (in order to not shut down computers which have to be always on, like machine control computers).

If a computer could be waked up for package installation is determined by a time frame defined in `WOL_SHUTDOWN_EXPIRY_SECONDS` in the config file. If a computer does not came up within this time frame, it is assumed that WOL did not work and the automatic shutdown will be removed from this job, so that the computer is not shutted down automatically if a user powered it on later manually.

Please also have a look at the WOL section in [Computers.md](Computers.md).

## Priority
Job containers with higher priority will be executed first, even if they are created later in time.

## Timeout For Restart
If a user is logged in and a package requires a restart, the OCO agent will wait that long so that the user can save his work.

## Agent IP Range Constraint
You can add an IP range condition to each job container, so that the related jobs will only be executed if the agents current remote address (the address which is used to contact the server) is inside the given range. This can be useful if you do not want to execute some jobs if the computer is in your VPN network (e.g. for very big software packages, which should only be installed if the employee is on his work place in your company, but not in home office).

You can specify multiple IPv4 or IPv6 ranges (comma separated) in the appropriate text field on the deployment assistant. Negations are also possible using `!` in front of the range. Please note that the order of the ranges is important. If a negated range matches, the job will instantly be ignored.

**Example: `10.2.0.0/16,!192.168.2.3/32,192.168.2.0/24,CAFF:EECA:FFEE:0000::/64`**
- allow all clients inside `10.2.0.0/16`
- allow all clients inside `192.168.2.0/24` except `!192.168.2.3/32`
- allow all clients inside `CAFF:EECA:FFEE:0000::/64`

# Install Packages via Deployment Rule (Dynamic Jobs)
Deployment rules can tell the OCO system how to automatically roll out packages to computers. In other words, these jobs are dynamically created based on computer and package group memberships.

Create a rule by clicking on 'New Deployment Rule' on the 'Deployment Rules' page (below 'Jobs') in the web console. Choose a computer group, on which this rule should apply. Then, select a package group with the packages which should be automatically deployed to the computers of the previously selected computer group. You can enable or disable rules at any time.

It is recommended to keep the rule disabled at creation time and check if all jobs are as desired. Then, you can enable the rule.

As soon as computer or package group members or computer-package assignments changed, the deployment rule will automatically be re-evaluated and adjust the dynamic jobs according to the new conditions. This means e.g. packages which are newly added to the package group will be instantly deployed on the computers of the selected computer group.

**Note:** be careful when using deployment rules. If your package group contains multiple versions of a package family and automatic uninstallation is enabled in your rule, you will get an endless loop of uninstallation and installation jobs.

# Uninstall Packages
You can create uninstall jobs from within the computer or package detail page. Check the checkbox of the package you want to uninstall (on the table 'Installed Packages') and click on 'Uninstall Package'.

# Remove Package Assignments
In some cases, it is necessary to manually remove a computer-package assignment (e.g. if an uninstall command returns a status code which you not listed as successful in the package - then the software was maybe sucessfully removed but the package is still assigned to the computer in the OCO database). You can do this from within the computer or package detail page. Check the checkbox of the package assignment you want to remove (on the table 'Installed Packages') and click on 'Remove Assignment'.

# Automatically Remove Completed Job Containers
You can specify in the OCO config file when succeeded or failed job containers should be automatically purged.

# Update Packages
Software is often updated regularily and you want to rollout these updates on your clients to ensure users use the newest version to profit from new features and avoid security risks of older versions.

After the old package is uninstalled from every computer by one of the following methods, you can delete the old package version to free up some space on the server.

## Static Jobs
Rolling out a new version is done by opening the detail page of the old package, selecting all computers on which it is installed, and then deploying the new version to all this computers.

"Automatically create uninstall jobs" is set by default and ensures that the old version is correctly uninstalled, so that no files from the old package will be left behind.

## Dynamic Jobs
Remove the old package version from the rules package group. Then, add the new version to the group. Make sure to do it in this order to avoid endless uninstall-install loops.

# Special Status Codes
The following status codes are not real return codes from your (un)installation command but generated by the OCO system.

## Job Fails With Status Code `-9999`
The status code `-9999` indicates an agent error. Possible reasons are:
- package download aborted because the network connection was lost
  - you can try to reschedule the job
- unable to execute installation command
  - check if your installation command works by executing it manually on the command line
- other unhandled agent errors
  - you can open an issue in the OCO Agent repository with detailed information

## Job Fails With Status Code `-8888`
The status code `-8888` indicates that a previous job failed and the 'sequence mode' is set to 'abort after failed job'. Then, all pending jobs are automatically set to `Failed (-8888)`. You can set the 'sequence mode' to 'ignore failed jobs' if you want to continue executing pending jobs after a failed job.
