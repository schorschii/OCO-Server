# OCO: Server Upgrade
Always upgrade from release to release and do not jump directly to the latest version.

0. Have a look at the release notes on Github for important notes and breaking changes.
1. Shut down your web server and backup your database using `mysqldump`.
2. Update all files to the new version but keep your `conf.php`, e.g. by using `git pull && git checkout vX.X.X` (valid tag/release names can be found by executing `git tag`).
3. Upgrade the database schema by executing `php console.php upgradeschema`. 
4. Check `conf.php.example` and compare it with your `conf.php` for new/updated configuration values.
5. Start your webserver. Have a look at the error logs to check if everything is OK (`/srv/log/apache2/error.log`).
