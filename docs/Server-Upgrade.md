# OCO: Server Upgrade
0. Shut down your web server and backup your data using `mysqldump`.
1. Upgrade the database schema (if necessary) using the corresponding SQL upgrade script (e.g. `lib/sql/v1.1_upgrade.sql`).
   Please also have a look at the comments inside the upgrade script. Maybe some additional steps are required to migrate your data.
2. Update all files (but keep your `conf.php`), e.g. by using `git pull`.
3. Clear your browser cache.
4. Start your webserver. Have a look at the error logs to check if everything is OK (`/var/log/apache2/error.log`).
