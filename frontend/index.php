<?php
require_once('../lib/loader.php');
require_once('session.php');
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo APP_NAME; ?></title>
	<?php require_once('head.inc.php'); ?>
	<script src='js/table.js'></script>
</head>
<body onclick='toggleContextMenu(null)'>

<div id='container'>

	<div id='header'>
		<span class='left'><?php echo APP_NAME; ?></span>
		<span class='right'>
			<!--<button><img src='img/settings.svg' onclick='openSettings()'></button>-->
			<button><img src='img/exit.svg' onclick='window.location.href="login.php?logout"' title='<?php echo htmlspecialchars($_SESSION['um_username']); ?>'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' oncontextmenu='return toggleContextMenu(ctmExplorerTree)'>

		</div>
		<div id='explorer-content' oncontextmenu='return toggleContextMenu(ctmExplorerContent)'>
			<div id='homepage'>
				<img src='img/logo.svg'>
				<div class='title'>[ Open Computer Orchestration ]</div>
				<div class='subtitle'>Client inventory and software delivery made simple</div>
			</div>
		</div>
	</div>

	<div id='loader-container'>
		<img src='img/loader.svg'>
	</div>

	<div id='ctmExplorerTree' class='contextMenu hidden'>
		<button onclick='refreshSidebar()'><img src='img/refresh.svg'>&nbsp;Aktualisieren</button>
	</div>
	<div id='ctmExplorerContent' class='contextMenu hidden'>
		<button onclick='refreshContent()'><img src='img/refresh.svg'>&nbsp;Aktualisieren</button>
	</div>

	<script>
	refreshSidebar();
	</script>

</div>

</body>
</html>
