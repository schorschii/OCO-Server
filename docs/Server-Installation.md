# OCO: Server Installation

## Docker
Docker is currently used for development/testing purposes only. For productive environments, please set up a dedicated server as described in the section below.
```
docker-compose build --no-cache
docker-compose up

docker ps -a
docker exec -it <CONTAINER-ID> bash
> /usr/local/bin/php console.php upgradeschema
```

## Basic Setup
0. Install PHP (7.3 or newer) with ZIP & DOM modules, MySQL/MariaDB and Apache2 on a Linux server (Debian recommended).
   ```
   apt install php php-zip php-dom php-mysql php-curl php-ldap mariadb-server apache2 libapache2-mod-php
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
3. Configure your MariaDB server lock timeout (via `/etc/mysql/conf.d/lock-timeout.cnf`) and restart the database server.
   ```
   [mysqld]
   innodb_lock_wait_timeout = 120
   ```

   Import the database schema (including all schema upgrades, `/sql/*.sql`) into an empty database.
   ```
   root@ocoserver:/# mysql
   mysql> CREATE DATABASE oco DEFAULT CHARACTER SET utf8mb4;
   mysql> CREATE USER 'oco'@'localhost' IDENTIFIED BY 'choose_your_own_password';
   mysql> GRANT ALL PRIVILEGES ON oco.* TO 'oco'@'localhost';
   mysql> FLUSH PRIVILEGES;
   mysql> EXIT;
   root@ocoserver:/# cat sql/*.sql | mysql oco
   ```
4. Create the configuration file `conf.php` (create this file by copying the template `conf.php.example`).
   - Enter your MySQL credentials in `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`. Use a separate database user for the database connection which only has permission to read and write in the specific OCO database. Do not use the root account.
   - Make sure that the defined `PACKAGE_PATH` (where to save the software packages) is writeable for the webserver user.
5. **Important:** set up HTTPS with a valid certificate and configure your web server to redirect any HTTP request to HTTPS.
   - It is very insecure to let the agent communicate via HTTP with your server because a man-in-the-middle attack can be used to send and install any (malicious) software packages to your client!!!
   - Enable the Apache SSL module using `a2enmod ssl`.
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
   - Please refer to the section "Certificate Setup" how to obtain appropriate certificates.
6. Adjust your PHP config (`/etc/php/x.x/apache2/php.ini`) to allow uploading packages of larger size.
   Pick a value that fit your needs for the settings `upload_max_filesize`, `post_max_size`, `max_execution_time`, `max_input_time`, `memory_limit`. Also, please set your timezone using `date.timezone`.
7. Adjust you Apache config to allow uploading packages of larger size.
   Pick a value that fit your needs for the settings `LimitRequestBody`, `SSLRenegBufferSize`.
8. Use a web browser to open the web frontend. The setup page should appear which allows you to create an admin user account.
9. Set up a cron job executing `php console.php housekeeping` every 2 minutes as webserver user (`www-data`).
   ```
   */2 *  * * *  www-data  cd /srv/www/oco && php console.php housekeeping
   ```
10. Create a DNS SRV record `_oco._tcp.yourdomain.tld` to enable the [agent](https://github.com/schorschii/oco-agent) on managed clients to find the server automatically via DNS auto discovery.

If you like this project, please do not forget to star the GitHub repo.

## Certificate Setup
There are two common ways to get a certificate for your OCO server. Based on your needs, you have to decide which one you want to go.

### Self-Signed Certificate (Own Certificate Authority)
You can set up your own certificate authority (CA) in order to create self-signed certificate for your server, but your CA must be manually imported/trusted in your operating systems's and browser's certificate store.

```
CANAME="My Own Root CA"

# generate encrypted CA private key, choose a strong passphrase!
openssl genrsa -aes256 -out "$CANAME.key" 4096

# create CA certificate, 3650 days = 100 years
openssl req -x509 -new -nodes -key "$CANAME.key" -sha256 -days 36500 -out "$CANAME.crt"

# create certificate for your OCO webserver
SRVNAME="myserver.local"
openssl req -new -nodes -out "$SRVNAME.csr" -newkey rsa:4096 -keyout "$SRVNAME.key"

# create an openssl config file in order to integrate "subject alt names" (IP and/or DNS name) into server certificate
cat > "$SRVNAME.v3.ext" << EOF
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
subjectAltName = @alt_names
[alt_names]
DNS.1 = myserver.local
DNS.2 = myserver1.local
IP.1 = 192.168.1.1
IP.2 = 192.168.2.1
EOF

# create server certificate using our own CA
openssl x509 -req -in "$SRVNAME.csr" -CA "$CANAME.crt" -CAkey "$CANAME.key" -CAcreateserial -out "$SRVNAME.crt" -days 730 -sha256 -extfile "$SRVNAME.v3.ext"

# now, copy "$SRVNAME.crt" and "$SRVNAME.key" into the Apache config folder and set the paths to the files (`SSLCertificateFile` and `SSLCertificateFile`)

# keep "$CANAME.key" and it's passphrase to renew your server certificate and to issue new certificates later

# import "$CANAME.crt" in the client's system trust store now to verify the server certificate on your clients:
# Debian, Ubuntu: sudo cp "$CANAME.crt" /usr/local/share/ca-certificates && sudo update-ca-certificates
# Fedora, CentOS: sudo cp "$CANAME.crt" /etc/pki/ca-trust/source/anchors && sudo update-ca-trust
# Windows GUI: Open the .crt file and install it for all users to "Trusted Root Certificate Authorities"
# Windows cmd: certutil.exe -addstore root "$CANAME.crt"
```

### Let’s Encrypt Certificate
Let's Encrypt Certificates are trusted by default in nearly all operating systems and browsers, but to obtain a certificate, your server must be available from the internet for the verification process.

1. Install the LetsEncrypts certbot: `apt-get install python-certbot-apache`
2. Ensure that the DNS entries for your server are correct and obtain a certificate: `certbot --apache certonly -d oco.example.com`.  
   This requires that your server is available from the internet, so that LetsEncrypt can contact it.  
   Certificate files (private key + certificate, chain) will be saved in '/etc/letsencrypt/live/'.
3. Certificate will be renewed automatically (use `certbot --apache renew` for manual renewal).

## Server Hardening
While it is technically possible, **never** let the agent commuicate in plaintext HTTP with the server! Attackers can do a man-in-the-middle attack to send any malicious software package to your agent. **Always** configure your (Apache) web server to use HTTPS with a valid certificate. Redirect **all** HTTP requests to HTTPS using appropriate rewrite rules as described in the installation guide.

It is recommended to not make the OCO server available on the internet to prevent brute force attacks. If possible, make the server only available in your internal company network and use a VPN connection for mobile devices.

It is also possible to use a self-signed certificate if necessary. Then, you have to import your own CA certificate into the trust store of your agent's operating system.

### fail2ban
You can set up fail2ban to prevent brute force attacks. Example configuration can be found in the `examples/fail2ban` directory.

### Only Provide Agent API On Virtual Host
Ou may only want to provide the agent API and not the full web interface with client API on a virtual host. In this case, please use `api-agent` as web server root directory for the virtual host (instead of `frontend`).

The web interface and client API can then be made available on a separate, internal-only web server or virtual host, which has additional security options set in the web server config (e.g. IP address restrictions or an additional HTTP basic auth).

## Set Up LDAP Sync & Authentication
If you want to use LDAP to authenticate admin users on the web frontend, please follow this steps.

1. Set up the LDAP configuration as administrator on the "Settings" > "System User" page in the web frontend.  
   The LDAP configuration is stored as a JSON array, allowing multiple LDAP servers of different types and syncing multiple groups:
   ```
   {
     "1": {
       "address": "ldap://192.168.56.101",
       "username": "ldap-reader@sieber.systems",
       "password": "<PASSWORD>",
       "query-root": "DC=sieber,DC=systems",
       "queries": {
         "(&(objectClass=user)(memberof=CN=OcoAdmins,OU=Benutzer,DC=sieber,DC=systems))": 1,
         "(&(objectClass=user)(memberof=CN=OcoUsers,OU=Benutzer,DC=sieber,DC=systems))": 2
       },
       "login-binddn-query": "(&(objectClass=user)(samaccountname=%s))",
       "attribute-matching": {},
       "lock-deleted-users": false
     }
     ... more servers here ...
   }
   ```
   - Array Key: an integer greater that 0 which uniquely identifies a LDAP server pool
   - `address` Server IP address or DNS name as LDAP URL, e.g. 'ldap://192.168.56.101' (single) or 'ldaps://192.168.56.101' (secure) or 'ldaps://192.168.56.101 ldaps://192.168.56.102' (multiple).
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
   - `lock-deleted-users`: Set to true if you want to lock deleted LDAP users instead of deleting them directly.
2. Start the first sync manually by executing `cd /srv/www/oco && php console.php ldapsync`.  
   Now you can log in with the synced accounts on the web frontend.
3. Set up a cron job executing `php console.php ldapsync` every 30 minutes as webserver user (`www-data`).
   ```
   */10 *  * * *  www-data  cd /srv/www/oco && php console.php ldapsync
   ```

## Self Service Portal
If you want to provide the Self Service Portal to your users please read [Self-Service.md](Self-Service.md).

## Server Cluster
You can install this webapp on multiple web servers for failure safety.

In a multi-server configuration, the easiest way to load balance is through multiple DNS records or by using a dedicated load balancer / reverse proxy.

The `depot` directory must then be stored on a shared file system, e.g. on a NFS share.

When using multiple web servers, you should also use a database cluster, so that your OCO installation is not dependend on a single database server.
