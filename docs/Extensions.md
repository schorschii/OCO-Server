# OCO: Server (Web Frontend) Extensions

The OCO server / web frontend can be extended with own scripts. The extension technique is intentionally kept very simple.

## Files
You need the following files for an own extension.

### Web Tree Hook
All PHP files inside `frontend/views/tree.d` will be included in the tree view on the left side in the web frontend. Example file:
```
<div class='node'>
	<a href='<?php echo explorerLink('views/views.d/myaddon.php'); ?>' onclick='event.preventDefault();refreshContentExplorer("views/views.d/myaddon.php")'><img src='img/img.d/myaddon.dyn.svg'>My Add-On</a>
</div>
```

### Main Web View
The main view of the extension (as shown in explorer content window) should be placed inside `frontend/views/views.d` (can be multiple files which are linked among themselves). It should include the `loader.inc.php` for database access and `session.php` for authorization check. You may include additional libraries which are part of your extension (see "Libraries").
Example file:
```
<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.php');
#require_once('../../../lib/lib.d/my-custom-library.php');
?>
<div class='alert bold success'>Your extension content here...</div>
```

### JavaScript & CSS (optional)
Place your extensions JavaScript inside `frontend/js/js.d` and CSS inside `frontend/css/css.d`. All files in this directory are automatically included in the HTML head.

### Images / Icons (optional)
Place your extensions custom images inside `frontend/img/img.d`.

### Libraries (optional)
Place your libraries inside `lib/lib.d`.

## Examples
Complete examples can be found in the official [OCO Server Extensions](https://github.com/schorschii/oco-server-extensions) repository.
