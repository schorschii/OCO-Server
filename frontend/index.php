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
	<script src='js/confetti.js'></script>
	<!--
		Wir begrüßen Sie an diesem wunderschönen <?php echo date("l"); ?>,
		<?php echo time(); ?> Sekunden nach dem Unix-Urknall!
		PS. Obacht! Heute ist der <?php echo date("N"); ?>. Tag der Woche und die Woche zieht sich schon wieder!!!
	-->
</head>
<body onclick='toggleContextMenu(null)' onkeydown='handleRefresh(event)'>

<div id='container'>

	<div id='header'>
		<span class='left'><a href='index.php'><?php echo LANG['app_name']; ?></a></span>
		<span class='right'>
			<button onclick='location.href="index.php"' title='<?php echo LANG['home_page']; ?>'><img src='img/home.light.svg'></button>
			<span class='separator'></span>
			<button onclick='refreshContent();refreshSidebar();' title='<?php echo LANG['refresh']; ?>'><img src='img/refresh.light.svg'></button>
			<span class='separator'></span>
			<button onclick='refreshContentSettings()' title='<?php echo LANG['settings']; ?>'><img src='img/settings.light.svg'></button>
			<span class='separator'></span>
			<button onclick='window.location.href="login.php?logout"' title='<?php echo LANG['log_out']; ?>'><?php echo htmlspecialchars($_SESSION['um_username']); ?>&nbsp;<img src='img/exit.light.svg'></button>
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
			<div>
				<button onclick='showErrorDialog(false);showLoader(false);showLoader2(false);'><?php echo LANG['close']; ?>
				<button onclick='showErrorDialog(false);refreshContent();'><?php echo LANG['retry']; ?>
				<button onclick='showErrorDialog(false);refreshContentHomepage();'><?php echo LANG['home_page']; ?>
			</div>
			<div id='dialog-text'></div>
		</div>
	</div>

	<div id='ctmExplorerTree' class='contextMenu hidden'>
		<button onclick='refreshSidebar()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>
	<div id='ctmExplorerContent' class='contextMenu hidden'>
		<button onclick='document.execCommand("copy");'><img src='img/copy.svg'>&nbsp;<?php echo LANG['copy']; ?></button>
		<button onclick='refreshContent()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

	<script>
	refreshSidebar();
	<?php if(!empty($_GET['explorer-content']) && substr($_GET['explorer-content'], 0, 5) == 'views') { ?>
		ajaxRequest("<?php echo htmlspecialchars($_GET['explorer-content']); ?>", "explorer-content");
	<?php } ?>
	</script>

</div>

</body>
</html>
