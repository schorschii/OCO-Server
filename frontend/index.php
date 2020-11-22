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
			<?php require('views/homepage.php'); ?>
		</div>
	</div>

	<div id='loader-container'>
		<img src='img/loader.svg'>
	</div>

	<div id='dialog-container'>
		<div id='dialog-box'>
			<h2 id='dialog-title'></h2>
			<textarea readonly='true' rows='15' id='txtDialogText'></textarea>
			<div>
				<button onclick='showErrorDialog(false);'><?php echo LANG['close']; ?>
				<button onclick='showErrorDialog(false);refreshContent();'><?php echo LANG['retry']; ?>
				<button onclick='showErrorDialog(false);refreshContentHomepage();'><?php echo LANG['home_page']; ?>
			</div>
		</div>
	</div>

	<div id='ctmExplorerTree' class='contextMenu hidden'>
		<button onclick='refreshSidebar()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>
	<div id='ctmExplorerContent' class='contextMenu hidden'>
		<button onclick='refreshContent()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>

	<script>
	refreshSidebar();
	<?php if(!empty($_GET['explorer-content']) && substr($_GET['explorer-content'], 0, 5) == 'views') { ?>
		ajaxRequest("<?php echo htmlspecialchars($_GET['explorer-content']); ?>", "explorer-content");
	<?php } ?>
	</script>

</div>

</body>
</html>
