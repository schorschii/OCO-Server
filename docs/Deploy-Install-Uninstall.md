# OCO: Deploy

# Job Containers
Deployment jobs (installation or uninstallation of a OCO package) are always subordinate of a job container. The job container holds various meta data about the jobs, e.g. a description, start and end date, priority etc.

# Install Packages
Open the 'Deployment Assistant' on the 'Jobs' page in the web console. The assistant can also be opened by clicking 'Deploy' on the computer detail page or the package detail page.

## Start/End Date
You can select a start date in the future. Then, the jobs are schedules and executed when the start date is reached. You can also select an end date after which the jobs will no longer be executed. This can be helpful if you do not want to interrupt your user and only execute updates within a specific time range.

## WOL/Shutdown
You can enable sending Wake On Lan packets on the job start time and automatic shutdown after completion. This will only shutdown computers, which could be waked up - all computers, which are already powered on will not be shutted down (in order to not shut down computers which have to be always on, like machine control computers).

Please also have a look at the WOL section in [Computers.md](Computers.md).

## Priority
Job containers with higher priority will be executed first, even if they are created later in time.

## Timeout For Restart
If a user is logged in and a package requires a restart, the OCO agent will wait that long so that the user can save his work.

# Uninstall Packages
You can create uninstall jobs from within the computer or package detail page. Check the checkbox of the package you want to uninstall (on the table 'Installed Packages') and click on 'Uninstall Package'.

# Remove Package Assignments
In some cases, it is necessary to manually remove a computer-package assignment (e.g. if an uninstall command returns a status code which you not listed as successful in the package - then the software was maybe sucessfully removed but the package is still assigned to the computer in the OCO database). You can do this from within the computer or package detail page. Check the checkbox of the package assignment you want to remove (on the table 'Installed Packages') and click on 'Remove Assignment'.

# Automatically Remove Completed Job Containers
You can specify in the OCO config file when succeeded or failed job containers should be automatically purged.

# Update Packages
Software is often updated regularily and you want to rollout these updates on your clients to ensure users use the newest version to profit from new features and avoid security risks of older versions.

Rolling out a new version is normally done by opening the detail page of the old package, selecting all computers on which it is installed, and then deploying the new version to all this computers.

"Automatically create uninstall jobs" is set by default and ensures that the old version is correctly uninstalled, so that no files from the old package will be left behind.

After the old packages was uninstalled from every computer, you can delete the old version to free up some space on the server.
