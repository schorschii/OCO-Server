# OCO: Web Frontend Add-Ons

The OCO web frontend can be extended with own scripts. The add on technique is intentionally kept very simple.

## Files
You need the follwing files for an own add on.

### Tree-Hook
All PHP files inside `frontend/views/tree.d` will be included in the tree view on the left side in the web frontend. Example file:
```
<div class='node'>
	<a href='<?php echo explorerLink('views/views.d/myaddon.php'); ?>' onclick='event.preventDefault();refreshContentExplorer("views/views.d/myaddon.php")'><img src='img/img.d/myaddon.dyn.svg'>My Add-On</a>
</div>
```

### Add-On View
The main view of the add on (as shown in explorer content window) should be placed inside `frontend/views/views.d` (can be multiple files which are linked among themselves). Example file:

## Images / Icons
Place your custom images here, which are used by your add ons.
