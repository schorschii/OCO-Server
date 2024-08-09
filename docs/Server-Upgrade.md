# OCO: Server Upgrade
Always upgrade from release to release and do not jump directly to the latest version.

0. Have a look at the release notes on Github for important notes and breaking changes.
1. Preparations
   1. Shut down your web server and disable all OCO cron jobs.
   1. Backup your database using `mysqldump`.
2. Upgrade
   1. Check your installed extensions for updates and install them.
   2. Update all files to the new version but keep your `conf.php`, e.g. by using `git fetch && git checkout vX.X.X` (valid tag/release names can be found via `git tag`).
   3. Upgrade the database schema by executing `php console.php upgradeschema`.
   4. Check `conf.php.example` and compare it with your `conf.php` for new/updated configuration values.
3. Finishing the upgrade
   1. Start your webserver and re-enable all cron jobs.
   2. Have a look at the error logs to check if everything is OK (`/srv/log/apache2/error.log`).
