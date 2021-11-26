# OCO: Server Installation

## Basic Setup
0. Install PHP 7.x and a web sever of your choice.
1. Download the [latest release](https://github.com/schorschii/oco-server/releases), copy all files into `/var/www/oco` and configure your web sever to use the `frontend` directory as webroot.
1. Import the database schema (use the newest version, e.g. `lib/sql/v1.0.sql`) into an empty database.
2. Enter your MySQL credentials in `conf.php` (create this file by copying the template `conf.example.php`). Please create a separate user for the database which only has permission to read and write in this specific database. Do not use the root account.
3. Make sure the defined package path is writeable for the webserver user.
4. Important: please set up HTTPS with a valid certificate and configure your web server to redirect any HTTP request to HTTPS. It is very insecure to let the agent communicate via HTTP with your server because a man-in-the-middle attack can be used to send and install any software packages to your client!!!
5. Adjust your PHP config (`/etc/php/7.x/apache2/php.ini`) to allow uploading packages of larger size (pick a value that fit your needs for the settings `upload_max_filesize`, `post_max_size` and `max_execution_time`).
6. Use a web browser to open the web frontend. The setup page should appear which guides you through the rest of the installation process.
7. Set up a cron job executing `lib/HouseKeeping.php` every 10 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /var/www/oco/lib && php HouseKeeping.php
   ```

### LDAP Sync & Authentication
If you want to use LDAP to authenticate admin users on the web frontend, please follow this steps.

1. Enter your LDAP details in `conf.php`. Please read the comments in the example config file for more information.
2. Set up a cron job executing `lib/LdapSync.php` every 30 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /var/www/oco/lib && php LdapSync.php
   ```
3. Start the first sync manually by executing `cd /var/www/oco/lib && php LdapSync.php`. Now you can log in with the synced accounts on the web frontend.
