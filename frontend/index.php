<?php
/* KEEP IT SIMPLE */

require_once('../lib/Loader.php');
require_once('session.php');

$initialExplorerContent = 'views/homepage.php';
$initialExplorerContentParameter = '';
if(!empty($_GET['view'])) {
	// check which view should be loaded via ajax
	if(in_array($_GET['view'].'.php', scandir(__DIR__.'/views'))) {
		$initialExplorerContent = 'views/'.$_GET['view'].'.php';
	} elseif(in_array($_GET['view'].'.php', scandir(__DIR__.'/views/views.d'))) {
		$initialExplorerContent = 'views/views.d/'.$_GET['view'].'.php';
	} else {
		$initialExplorerContent = null;
	}
	// compile GET parameter for ajax view request
	$parameter = [];
	foreach($_GET as $key => $value) {
		if($key == 'view') continue;
		$parameter[] = urlencode($key).'='.urlencode($value);
	}
	$initialExplorerContentParameter = implode('&', $parameter);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo LANG['app_name']; ?></title>
	<?php require_once('head.inc.php'); ?>
	<!--
		Wir begrüßen Sie an diesem wunderschönen <?php echo date("l"); ?>,
		<?php echo time(); ?> Sekunden nach dem Unix-Urknall!
		PS. Obacht! Heute ist der <?php echo date("N"); ?>. Tag der Woche und die Woche zieht sich schon wieder!!!
	-->
</head>
<body onkeydown='handleRefresh(event)'>

<div id='container'>

	<div id='header'>
		<span class='left'>
			<a href='index.php' onclick='event.preventDefault();refreshContentExplorer("views/homepage.php");' class='title'><?php echo LANG['app_name']; ?></a>
			<span class='separator space'></span>
		</span>
		<span class='search'>
			<input type='text' autocomplete='new-password' placeholder='<?php echo LANG['search_computer_packages_job_container']; ?>' onfocus='openSearchResults()' onkeyup='if(event.keyCode==27) {closeSearchResults()} else if(event.keyCode==40) {focusNextSearchResult()} else {doSearch(this.value)}' onpaste='doSearch(this.value)'></input>
			<div id='search-glass'></div>
			<div id='search-results' style='display:none'>
				<div class='search-result'>
					<?php echo LANG['no_search_results']; ?>
				</div>
			</div>
		</span>
		<span class='right'>
			<button id='btnHomepage' class='noprint' onclick='refreshContentExplorer("views/homepage.php")' title='<?php echo LANG['home_page']; ?>'><img src='img/home.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnRefresh' class='noprint' onclick='refreshContent();refreshSidebar();' oncontextmenu='toggleAutoRefresh();return false;' title='<?php echo LANG['refresh']; ?>'><img src='img/refresh.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnSettings' class='noprint' onclick='refreshContentExplorer("views/settings.php")' title='<?php echo LANG['settings']; ?>'><img src='img/settings.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnInfo' class='noprint' onclick='showDialogAjax("<?php echo LANG['about']; ?>", "views/dialog-about.php", DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_SMALL, false)' title='<?php echo LANG['about']; ?>'><img src='img/info.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnHelp' class='noprint' onclick='refreshContentExplorer("views/docs.php")' title='<?php echo LANG['help']; ?>'><img src='img/help.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnLogout' onclick='window.location.href="login.php?logout"' title='<?php echo LANG['log_out']; ?>'><span><?php echo htmlspecialchars($_SESSION['um_username']); ?>&nbsp;</span><img src='img/exit.light.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' onclick='closeSearchResults()'>
		</div>
		<div id='explorer-content' onclick='closeSearchResults()'>
			<?php if($initialExplorerContent == null) { ?>
				<div class='alert error'><?php echo LANG['requested_view_does_not_exist']; ?></div>
			<?php } ?>
		</div>
	</div>

	<div id='loader-container'>
		<img src='img/loader.svg'>
	</div>

	<div id='dialog-container'>
		<div id='dialog-box'>
			<h2 id='dialog-title'></h2>
			<div id='dialog-text'></div>
			<div id='dialog-controls' class='spread'>
				<button id='btnDialogHome' onclick='hideDialog();refreshContentExplorer("views/homepage.php");'><img src='img/home.svg'>&nbsp;<?php echo LANG['home_page']; ?></button>
				<button id='btnDialogReload' onclick='hideDialog();refreshContent();'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['retry']; ?></button>
				<button id='btnDialogClose' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.svg'>&nbsp;<?php echo LANG['close']; ?></button>
			</div>
		</div>
	</div>

	<div id='message-container'>
	</div>

	<div id='dialog-contents'>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

	<script>
	refreshSidebar();
	refreshSidebarTimer = setTimeout(function(){ refreshSidebar(null, true) }, REFRESH_SIDEBAR_TIMEOUT);

	<?php if($initialExplorerContent != null) { ?>
		ajaxRequest("<?php echo htmlspecialchars($initialExplorerContent.'?'.$initialExplorerContentParameter); ?>", "explorer-content");
	<?php } ?>
	<?php if(empty($_SESSION['um_last_login'])) {
		$_SESSION['um_last_login'] = true;
		echo "topConfettiRain();";
		echo "emitMessage(L__WELCOME_TEXT, L__WELCOME_DESCRIPTION, MESSAGE_TYPE_INFO);";
	} ?>
	</script>

</div>

</body>
</html>
