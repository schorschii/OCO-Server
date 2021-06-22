<?php
/* KEEP IT SIMPLE */

require_once('../lib/Loader.php');
require_once('session.php');

$initialExplorerContentAjaxRequest = null;
if(!empty($_GET['explorer-content']) && substr($_GET['explorer-content'], 0, 5) == 'views')
	$initialExplorerContentAjaxRequest = $_GET['explorer-content'];
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo LANG['app_name']; ?></title>
	<?php require_once('head.inc.php'); ?>
	<script src='js/strings.js.php'></script>
	<script src='js/main.js'></script>
	<script src='js/table.js'></script>
	<!--
		Wir begrüßen Sie an diesem wunderschönen <?php echo date("l"); ?>,
		<?php echo time(); ?> Sekunden nach dem Unix-Urknall!
		PS. Obacht! Heute ist der <?php echo date("N"); ?>. Tag der Woche und die Woche zieht sich schon wieder!!!
	-->
</head>
<body onclick='toggleContextMenu(null)' onkeydown='handleRefresh(event)'>

<div id='container'>

	<div id='header'>
		<span class='left'>
			<a href='index.php' onclick='event.preventDefault();refreshContentHomepage();' class='title'><?php echo LANG['app_name']; ?></a>
			<span class='separator space'></span>
		</span>
		<span class='search'>
			<input type='text' autocomplete='new-password' placeholder='<?php echo LANG['search_computer_packages_job_container']; ?>' onfocus='openSearchResults()' onkeyup='if(event.keyCode==27) {closeSearchResults();} else {doSearch(this.value);}' onpaste='doSearch(this.value)'></input>
			<div id='search-glass'></div>
			<div id='search-results' style='display:none'>
				<div class='search-result'>
					<?php echo LANG['no_search_results']; ?>
				</div>
			</div>
		</span>
		<span class='right'>
			<button onclick='refreshContentHomepage()' title='<?php echo LANG['home_page']; ?>'><img src='img/home.light.svg'></button>
			<span class='separator'></span>
			<button onclick='refreshContent();refreshSidebar();' title='<?php echo LANG['refresh']; ?>'><img src='img/refresh.light.svg'></button>
			<span class='separator'></span>
			<button onclick='refreshContentSettings()' title='<?php echo LANG['settings']; ?>'><img src='img/settings.light.svg'></button>
			<span class='separator'></span>
			<button onclick='window.location.href="login.php?logout"' title='<?php echo LANG['log_out']; ?>'><span><?php echo htmlspecialchars($_SESSION['um_username']); ?>&nbsp;</span><img src='img/exit.light.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' oncontextmenu='return toggleContextMenu(ctmExplorerTree)' onclick='closeSearchResults()'>

		</div>
		<div id='explorer-content' onclick='closeSearchResults()'>
			<?php if($initialExplorerContentAjaxRequest == null) require('views/homepage.php'); ?>
		</div>
	</div>

	<div id='loader-container'>
		<img src='img/loader.svg'>
	</div>

	<div id='dialog-container'>
		<div id='dialog-box'>
			<h2 id='dialog-title'></h2>
			<div id='dialog-text'></div>
			<div class='spread'>
				<button id='btnDialogHome' onclick='showErrorDialog(false);refreshContentHomepage();'><img src='img/home.svg'>&nbsp;<?php echo LANG['home_page']; ?></button>
				<button id='btnDialogReload' onclick='showErrorDialog(false);refreshContent();'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['retry']; ?></button>
				<button id='btnDialogClose' onclick='showErrorDialog(false);showLoader(false);showLoader2(false);'><img src='img/close.svg'>&nbsp;<?php echo LANG['close']; ?></button>
			</div>
		</div>
	</div>

	<div id='ctmExplorerTree' class='contextMenu hidden'>
		<button onclick='refreshSidebar()'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['refresh']; ?></button>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

	<script>
	refreshSidebar();
	<?php if($initialExplorerContentAjaxRequest != null) { ?>
		ajaxRequest("<?php echo str_replace(["'",'"'],'',$initialExplorerContentAjaxRequest); ?>", "explorer-content");
	<?php } ?>
	<?php if(rand(0,1000) == 42) { ?>
		topConfettiRain();
	<?php } ?>
	</script>

</div>

</body>
</html>
