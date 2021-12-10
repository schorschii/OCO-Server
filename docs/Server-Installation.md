# OCO: Server Installation

## Basic Setup
0. Install PHP 7.x (with PHP-DOM module), MySQL/MariaDB and a web sever of your choice (Apache recommended).
   ```
   apt install php php-dom mariadb-server apache2 libapache2-mod-php
   ```
1. Download the [latest release](https://github.com/schorschii/oco-server/releases), copy all files into `/var/www/oco` and configure your web sever to use the `frontend` directory as webroot.
2. Import the database schema (use the newest version, e.g. `lib/sql/v1.0.sql`) into an empty database.
   ```
   root@ocoserver:/# mysql
   mysql> CREATE DATABASE oco DEFAULT CHARACTER SET utf8mb4;
   mysql> CREATE USER 'oco'@'localhost' IDENTIFIED BY 'choose_your_own_password';
   mysql> GRANT ALL PRIVILEGES ON oco.* TO 'oco'@'localhost';
   mysql> FLUSH PRIVILEGES;
   mysql> EXIT;
   root@ocoserver:/# mysql oco < /var/www/oco/lib/sql/v1.x.sql
   ```
3. Enter your MySQL credentials in `conf.php` (create this file by copying the template `conf.example.php`).  
   (Use a separate user for the database connection which only has permission to read and write in the specific OCO database. Do not use the root account.)
4. Make sure the in `conf.php` defined `PACKAGE_PATH` (where to save the software packages) is writeable for the webserver user.
5. **Important:** please set up HTTPS with a valid certificate and configure your web server to redirect any HTTP request to HTTPS.
   - It is very insecure to let the agent communicate via HTTP with your server because a man-in-the-middle attack can be used to send and install any software packages to your client!!!
   - Redirect all HTTP requests to HTTPS using appropriate rewrite rules.  
     <details>
     <summary>/etc/apache2/sites-enabled/000-default.conf</summary>

     ```
     <VirtualHost *:80>
        .....
        DocumentRoot /var/www/oco
        ## Redirect to HTTPS
        RewriteEngine On
        RewriteCond %{HTTPS} !=on
        RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]
        .....
     </VirtualHost>

     <VirtualHost *:443>
      .....
      DocumentRoot /var/www/oco
      SSLEngine on
      SSLCertificateFile /etc/apache2/ssl/mycertwithchain.crt
      SSLCertificateKeyFile /etc/apache2/ssl/myprivkey.key
      .....
     </VirtualHost>
     ```
     </details>
   - The next section describes in detail how to obtain a LetsEncrypt certificate. It is also possible to use a self-signed certificate if necessary. Then, you have to import your own CA certificate into the trust store of every agent's operating system.
6. Adjust your PHP config (`/etc/php/7.x/apache2/php.ini`) to allow uploading packages of larger size  
  (pick a value that fit your needs for the settings `upload_max_filesize`, `post_max_size` and `max_execution_time`).
7. Use a web browser to open the web frontend. The setup page should appear which allows you to create an admin user account.
8. Set up a cron job executing `lib/HouseKeeping.php` every 10 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /var/www/oco/lib && php HouseKeeping.php
   ```

### Obtaining A Let’s Encrypt Certificate
1. Enable the Apache SSL module: `a2enmod ssl`
2. Install LetsEncrypts certbot: `apt-get install python-certbot-apache`
3. Obtain a certificate: `certbot --apache certonly -d example.com`.  
   This requires that your server is (temporarily) available from the internet, so that LetsEncrypt can contact it.  
   Certificate files (private key + certificate, chain) will be saved in '/etc/letsencrypt/live/example.com'.
4. Certificate can be renewed using `certbot --apache renew`.

### LDAP Sync & Authentication
If you want to use LDAP to authenticate admin users on the web frontend, please follow this steps.

1. Enter your LDAP details in `conf.php`. Please read the comments in the example config file for more information.
2. Set up a cron job executing `lib/LdapSync.php` every 30 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /var/www/oco/lib && php LdapSync.php
   ```
3. Start the first sync manually by executing `cd /var/www/oco/lib && php LdapSync.php`.  
   Now you can log in with the synced accounts on the web frontend.

### Only Provide Agent API On Virtual Host
Ou may only want to provide the agent api and not the full web interface with client API on a virtual host. In this case, please use `api-agent` as web server root directory (instead of `frontend`).

The web client can then be made available on a separate, internal-only web server or virtual host, which has additional security options set in the web server config (e.g. IP address restrictions or an additional HTTP basic auth).

### Server Cluster
You can install this webapp on multiple web servers for failure safety.

In a multi-server configuration, the easiest way to load balance is through multiple DNS records or by using a dedicated load balancer / reverse proxy.

The `depot` directory must then be stored on a shared file system, e.g. on a NFS share.

When using multiple web servers, you should also use a database cluster, so that your OCO installation is not dependend on a single database server.
