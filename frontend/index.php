<?php
require_once('../lib/loader.php');
require_once('session.php');
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo LANG['app_name']; ?></title>
	<?php require_once('head.inc.php'); ?>
	<script src='js/strings.js.php'></script>
	<script src='js/main.js'></script>
	<script src='js/table.js'></script>
</head>
<body onclick='toggleContextMenu(null)' onkeydown='handleRefresh(event)'>

<div id='container'>

	<div id='header'>
		<span class='left'><a href='index.php'><?php echo LANG['app_name']; ?></a></span>
		<span class='right'>
			<button onclick='refreshContentSettings()'><img src='img/settings.svg'></button>
			<button onclick='window.location.href="login.php?logout"' title='<?php echo htmlspecialchars($_SESSION['um_username']); ?>'><img src='img/exit.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' oncontextmenu='return toggleContextMenu(ctmExplorerTree)'>

		</div>
		<div id='explorer-content' oncontextmenu='return toggleContextMenu(ctmExplorerContent)'>
			<div id='homepage'>
				<img src='img/logo.dyn.svg'>
				<div class='title'><?php echo LANG['app_name_frontpage']; ?></div>
				<div class='subtitle'><?php echo LANG['app_subtitle']; ?></div>
			</div>
		</div>
	</div>

	<div id='loader-container'>
		<img src='img/loader.svg'>
	</div>

	<div id='ctmExplorerTree' class='contextMenu hidden'>
		<button onclick='refreshSidebar()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>
	<div id='ctmExplorerContent' class='contextMenu hidden'>
		<button onclick='refreshContent()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>

	<script>
	refreshSidebar();
	</script>

</div>

</body>
</html>
