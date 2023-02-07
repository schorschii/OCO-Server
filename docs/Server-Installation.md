# OCO: Server Installation

## Basic Setup
0. Install PHP (7.3 or newer) with ZIP & DOM modules, MySQL/MariaDB and Apache2 on a Linux server (Debian recommended).
   ```
   apt install php php-zip php-dom mariadb-server apache2 libapache2-mod-php
   php --version
   ```
1. Download the [latest release](https://github.com/schorschii/oco-server/releases) and copy/extract all files into `/srv/www/oco`.
2. Configure your Apache webserver
   - configure your web sever to use the `/srv/www/oco/frontend` directory as webroot
   - make sure that `AllowOverride All` is set (for the frontend directory `/srv/www/oco/frontend`) so that the `.htaccess` files work
   - make sure that the `rewrite` module is installed and enabled
     ```
     a2enmod rewrite
     service apache2 restart
     ```
3. Import the database schema (including all schema upgrades, `/sql/*.sql`) into an empty database.
   ```
   root@ocoserver:/# mysql
   mysql> CREATE DATABASE oco DEFAULT CHARACTER SET utf8mb4;
   mysql> CREATE USER 'oco'@'localhost' IDENTIFIED BY 'choose_your_own_password';
   mysql> GRANT ALL PRIVILEGES ON oco.* TO 'oco'@'localhost';
   mysql> FLUSH PRIVILEGES;
   mysql> EXIT;
   root@ocoserver:/# cat sql/*.sql | mysql oco
   ```
4. Create the configuration file `conf.php` (create this file by copying the template `conf.example.php`).
   - Enter your MySQL credentials in `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`. Use a separate database user for the database connection which only has permission to read and write in the specific OCO database. Do not use the root account.
   - Make sure that the defined `PACKAGE_PATH` (where to save the software packages) is writeable for the webserver user.
5. **Important:** set up HTTPS with a valid certificate and configure your web server to redirect any HTTP request to HTTPS.
   - It is very insecure to let the agent communicate via HTTP with your server because a man-in-the-middle attack can be used to send and install any (malicious) software packages to your client!!!
   - Redirect all HTTP requests to HTTPS using appropriate rewrite rules.  
     <details>
     <summary>/etc/apache2/sites-enabled/000-default.conf</summary>

     ```
     <VirtualHost *:80>
        .....
        DocumentRoot /srv/www/oco/frontend
        ## Redirect to HTTPS
        RewriteEngine On
        RewriteCond %{HTTPS} !=on
        RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]
        .....
     </VirtualHost>

     <VirtualHost *:443>
      .....
      DocumentRoot /srv/www/oco/frontend
      SSLEngine on
      SSLCertificateFile /etc/apache2/ssl/mycertwithchain.crt
      SSLCertificateKeyFile /etc/apache2/ssl/myprivkey.key
      .....
      <Directory /srv/www/oco/frontend>
        AllowOverride All
      </Directory>
      .....
     </VirtualHost>
     ```
     </details>
   - The next section describes in detail how to obtain a LetsEncrypt certificate. It is also possible to use a self-signed certificate if necessary. Then, you have to import your own CA certificate into the trust store of every agent's operating system.
   - After you have sucessfully set up HTTPS, please enable the option `php_value session.cookie_secure 1` in the `frontend/.htaccess` file to ensure cookies are only transferred via HTTPS.
6. Adjust your PHP config (`/etc/php/7.x/apache2/php.ini`) to allow uploading packages of larger size  
  (pick a value that fit your needs for the settings `upload_max_filesize`, `post_max_size` and `max_execution_time`).
7. Adjust you Apache config to allow uploading packages of larger size  
  (pick a value that fit your needs for the settings `LimitRequestBody`, `SSLRenegBufferSize`).
8. Use a web browser to open the web frontend. The setup page should appear which allows you to create an admin user account.
9. Set up a cron job executing `php console.php housekeeping` every 2 minutes as webserver user (`www-data`).
   ```
   */2 *  * * *  www-data  cd /srv/www/oco && php console.php housekeeping
   ```
10. Create a DNS SRV record `_oco._tcp.yourdomain.tld` to enable the [agent](https://github.com/schorschii/oco-agent) on managed clients to find the server automatically via DNS auto discovery.

### Obtaining A Let’s Encrypt Certificate
1. Enable the Apache SSL module: `a2enmod ssl`
2. Install LetsEncrypts certbot: `apt-get install python-certbot-apache`
3. Obtain a certificate: `certbot --apache certonly -d example.com`.  
   This requires that your server is (temporarily) available from the internet, so that LetsEncrypt can contact it.  
   Certificate files (private key + certificate, chain) will be saved in '/etc/letsencrypt/live/example.com'.
4. Certificate can be renewed using `certbot --apache renew`.

### fail2ban
You can set up fail2ban for OCO to prevent brute force attacks. Example configuration can be found in the `examples/fail2ban` directory.

### LDAP Sync & Authentication
If you want to use LDAP to authenticate admin users on the web frontend, please follow this steps.

1. Set up the LDAP configuration as administrator on the "Settings" > "System User" page in the web frontend.  
   The LDAP configuration is stored as a JSON array, allowing multiple LDAP servers of different types and syncing multiple groups:
   ```
   {
     "ldap://192.168.56.101": {
       "username": "ldap-reader@sieber.systems",
       "password": "<PASSWORD>",
       "query-root": "DC=sieber,DC=systems",
       "queries": {
         "(&(objectClass=user)(memberof=CN=OcoAdmins,OU=Benutzer,DC=sieber,DC=systems))": 1,
         "(&(objectClass=user)(memberof=CN=OcoUsers,OU=Benutzer,DC=sieber,DC=systems))": 2
       },
       "login-binddn-query": "(&(objectClass=user)(samaccountname=%s))"
       "attribute-matching": {}
     }
     ... more servers here ...
   }
   ```
   - Array Key: Server IP address or DNS name, e.g. 'ldap://192.168.56.101' (single) or 'ldaps://192.168.56.101' (secure) or 'ldaps://192.168.56.101 ldaps://192.168.56.102' (multiple).
   - `username`: The username of the LDAP reader user.
   - `password`: The password of the LDAP reader user.
   - `query-root`: The LDAP query root, e.g. 'OU=Benutzer,DC=sieber,DC=systems'.
   - `queries`: Array of LDAP queries and role IDs for syncing. The array key must me a valid LDAP query and the value must be an OCO role ID. The role ID can be viewed in the admin web interface on the system users/roles settings page.
     - The order of the groups is important: the first matching group is used for determining the role ID of a user.
     - You can use any LDAP filter you like. If supported by your LDAP server, you can also resolve recursive group memberships ("group in group"), e.g. with a filter like: `(memberof:1.2.840.113556.1.4.1941:=cn=testgroup,dc=domain,dc=tld)`.
   - `login-binddn-query`: The LDAP query to determine the login DN for LDAP authentication attempts. If you leave this empty, `(&(objectClass=user)(samaccountname=%s))` will be used for use with Active Directory. Note: AD allows LDAP bind in form `DOMAIN\username` or `username@domain.tld`, but other LDAP servers require the login via DN as username (e.g. `cn=user,dc=domain,dc=tld`). `%s` is used as placeholder for the entered username.
   - `attribute-matching`: LDAP attribute matching. You can leave this empty if you are using Active Directory - the Active Directory attribute names will be used as default. You can adjust it if you are using an other LDAP servers like OpenLDAP, e.g.:
     ```
     "attribute-matching": {
       "uid": "entryUUID",
       "username": "cn",
       "first_name": "givenname",
       "last_name": "sn",
       "display_name": "displayname",
       "email": "mail",
       "phone": "telephonenumber",
       "mobile": "mobile",
       "description": "description"
     }
     ```
2. Start the first sync manually by executing `cd /srv/www/oco && php console.php ldapsync`.  
   Now you can log in with the synced accounts on the web frontend.
3. Set up a cron job executing `php console.php ldapsync` every 30 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /srv/www/oco && php console.php ldapsync
   ```

### Only Provide Agent API On Virtual Host
Ou may only want to provide the agent api and not the full web interface with client API on a virtual host. In this case, please use `api-agent` as web server root directory (instead of `frontend`).

The web client can then be made available on a separate, internal-only web server or virtual host, which has additional security options set in the web server config (e.g. IP address restrictions or an additional HTTP basic auth).

### Self Service Portal
If you want to provide the Self Service Portal to your users please read [Self-Service.md](Self-Service.md).

### Server Cluster
You can install this webapp on multiple web servers for failure safety.

In a multi-server configuration, the easiest way to load balance is through multiple DNS records or by using a dedicated load balancer / reverse proxy.

The `depot` directory must then be stored on a shared file system, e.g. on a NFS share.

When using multiple web servers, you should also use a database cluster, so that your OCO installation is not dependend on a single database server.
