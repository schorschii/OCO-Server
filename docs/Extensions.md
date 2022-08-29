# OCO: Server (Web Frontend) Extensions
The OCO server / web frontend can be extended with own scripts. The extension technique is intentionally kept very simple.

## Files
Extensions are placed in the `extensions` directory of your OCO server installation. Every extension must be organized in a separate directory and consists of the following files.

### Metadata File `index.php`
The metadata file must return an array of metadata information about the extension:
```
<?php
return [
	# internal extension id (= extension directory name)
	'id' => 'isc-dhcp-reservations',
	# extension display name
	'name' => 'ISC-DHCP-Server Reservation Editor',
	# extension version
	'version' => '1.0',
	# extension author
	'author' => 'Schorschii',
	# (optional) minimum OCO server version
	'oco-version-min' => '0.14.0',
	# (optional) maximum OCO server version
	'oco-version-max' => '1.99.99',

	# folder which should be registered for class autoloading
	'autoload' => __DIR__.'/lib',

	# web frontend sidebar HTML include
	'frontend-tree' => __DIR__.'/frontend/views/isc-dhcp-reservations.tree.php',
	# web frontend views provided by the extension
	'frontend-views' => [
		'isc-dhcp-reservations.php' => __DIR__.'/frontend/views/isc-dhcp-reservations.php',
	],
	# JavaScript which should be included in the web frontend
	'frontend-js' => [
		'isc-dhcp-reservations.js' => __DIR__.'/frontend/js/isc-dhcp-reservations.js'
	],
	# CSS which should be included in the web frontend
	'frontend-css' => [
		'isc-dhcp-reservations.css' => __DIR__.'/frontend/css/isc-dhcp-reservations.css'
	],
	# (optional) images provided by the extension
	'frontend-img' => [
		'dhcp.dyn.svg' => __DIR__.'/frontend/img/dhcp.dyn.svg',
	],

	# translation files directory (should contain files like the original OCO server translation files en.php and de.php)
	'translation-dir' => __DIR__.'/lang',
];
```

### Web Tree Hook
Example file (`isc-dhcp-reservations.tree.php`, as referenced in `frontend-tree` in `index.php`):
```
<div class='node'>
	<a <?php echo explorerLink('views/myaddon.php'); ?>><img src='img/myaddon.dyn.svg'>My Add-On</a>
</div>
```

### Main Web View
The main view of the extension as shown in explorer content window (can be multiple files which are linked among themselves). It should include the `loader.inc.php` for database access and `session.php` for authorization check. You may include additional libraries which are part of your extension (see "Libraries").

Example file (`isc-dhcp-reservations.php`, as referenced in `frontend-views` in `index.php`):
```
<?php
$SUBVIEW = 1;

# make sure this script is not called directly
if(!isset($db) || !isset($currentSystemUser)) die();

# (optional) ressource permission check
if(!$currentSystemUser->checkPermission(null, 'dhcp_reservation_management', false))
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
?>

<div class='alert bold success'>Your extension content here...</div>
```

## Examples
Complete real-life examples can be found in the official [OCO Server Extensions](https://github.com/schorschii/oco-server-extensions) repository.
