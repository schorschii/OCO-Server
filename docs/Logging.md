# OCO: Logging
The OCO server logs into the database table `log`. You can filter this table easily for IP addresses, usernames and actions.

The log level is defined in the configuration file `conf.php`. Please choose a log level appropriate for your environment.

## `4` - No Logging
Not recommended.

## `3` - Error
Logs critical errors.

## `2` - Warning
Logs failed login attempts.

## `1` - Info
Logs every action (e.g. package/computer/report creation, update, deletion) and login attempts.

## `0` - Debug
Logs every agent and client request including the complete payload. Only recommended for troubleshooting.
