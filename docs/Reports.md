# OCO: Reports
The OCO web frontend allows you to view reports which are basically individual views for the database.

OCO comes with a hand full of sample reports which you could use to copy and modify to fit your comapny-specific needs.

## Create Reports
Currently, the report SQL text has to be inserted/edtited manually in the database (table `reports`).

Create a new record in the table `report` (e.g. using mysql command line or PHPmyadmin), containing a SQL text for your own specific report. It will instantly appear on the web frontend.

If your report contains one or more of the following special columns, it will be automatically displayed as link, so you can navigate to the target object with one click on the web frontend: computer_id, package_id, software_id, domainuser_id, jobcontainer_id
