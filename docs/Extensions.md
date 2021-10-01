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
The main view of the extension (as shown in explorer content window) should be placed inside `frontend/views/views.d` (can be multiple files which are linked among themselves).

### JavaScript & CSS (optional)
Place your extensions JavaScript inside `frontend/js/js.d` and CSS inside `frontend/css/css.d`. All files in this directory are automatically included in the HTML head.

### Images / Icons (optional)
Place your extensions custom images inside `frontend/img/img.d`.

### Libraries (optional)
Place your libraries inside `lib/lib.d`.
