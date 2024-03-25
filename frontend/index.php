<?php
/* KEEP IT SIMPLE */

require_once('../loader.inc.php');
require_once('session.php');

$initialExplorerContent = 'views/homepage.php';
$initialExplorerContentParameter = '';
if(!empty($_GET['view'])) {
	// check which view should be loaded via ajax
	if(in_array($_GET['view'].'.php', scandir(__DIR__.'/views'))
	|| array_key_exists($_GET['view'].'.php', $ext->getAggregatedConf('frontend-views'))) {
		$initialExplorerContent = 'views/'.$_GET['view'].'.php';
	} else {
		$initialExplorerContent = null;
	}
	// compile GET parameter for ajax view request
	$parameter = [];
	foreach($_GET as $key => $value) {
		if($key == 'view') continue;
		if(is_array($value)) continue;
		$parameter[] = urlencode($key).'='.urlencode($value);
	}
	$initialExplorerContentParameter = implode('&', $parameter);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo LANG('app_name'); ?></title>
	<?php require_once('head.inc.php'); ?>
	<!--
		Wir begrüßen Sie an diesem wunderschönen <?php echo date("l"); ?>,
		<?php echo time(); ?> Sekunden nach dem Unix-Urknall!
		PS. Obacht! Heute ist der <?php echo date("N"); ?>. Tag der Woche und die Woche zieht sich schon wieder!!!
	-->
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'>
			<button id='btnSidebar' class='noprint' onclick='toggleSidebar()' title='<?php echo LANG('show_hide_sidebar'); ?>'><img src='img/menu.light.svg'></button>
			<a href='index.php' onclick='event.preventDefault();refreshContentExplorer("views/homepage.php");' class='title'><?php echo LANG('app_name'); ?></a>
			<span class='separator space'></span>
		</span>
		<span id='search-container'>
			<input type='text' id='txtGlobalSearch' autocomplete='off' placeholder='<?php echo LANG('search_computer_packages_job_container'); ?>'></input>
			<div id='search-glass'></div>
			<div id='search-results'>
				<div class='search-result'>
					<div class="alert info nomargin"><?php echo LANG('please_enter_a_search_term'); ?></div>
				</div>
			</div>
		</span>
		<span class='right'>
			<button id='btnHomepage' class='noprint' onclick='refreshContentExplorer("views/homepage.php")' title='<?php echo LANG('home_page'); ?>'><img src='img/home.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnRefresh' class='noprint' onclick='refreshContent();refreshSidebar();' oncontextmenu='toggleAutoRefresh();return false;' title='<?php echo LANG('refresh'); ?>'><img src='img/refresh.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnSettings' class='noprint' onclick='refreshContentExplorer("views/settings.php")' title='<?php echo LANG('settings'); ?>'><img src='img/settings.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnInfo' class='noprint' onclick='showDialogAjax("<?php echo LANG('about'); ?>", "views/dialog-about.php", DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_SMALL)' title='<?php echo LANG('about'); ?>'><img src='img/info.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnHelp' class='noprint' onclick='refreshContentExplorer("views/docs.php")' title='<?php echo LANG('help'); ?>'><img src='img/help.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnLogout' onclick='window.location.href="login.php?logout"' title='<?php echo LANG('log_out'); ?>'><span><?php echo htmlspecialchars($currentSystemUser->display_name); ?>&nbsp;</span><img src='img/exit.light.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' onclick='toggleSidebar(false)'>
		</div>
		<div id='explorer-content'>
			<?php if($initialExplorerContent == null) { ?>
				<div class='alert error'><?php echo LANG('requested_view_does_not_exist'); ?></div>
			<?php } ?>
		</div>
	</div>

	<div id='dialog-container'>
		<img src='img/loader.svg'>
		<div id='dialog-box'>
			<h2 id='dialog-title'></h2>
			<div id='dialog-text'></div>
			<button id='btnDialogClose' title='<?php echo LANG('close'); ?>' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'></button>
		</div>
	</div>

	<div id='message-container'>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

	<script>
	toggleSidebar(false);
	refreshSidebar();
	refreshSidebarTimer = setTimeout(function(){ refreshSidebar(null, true) }, REFRESH_SIDEBAR_TIMEOUT);

	<?php if($initialExplorerContent != null) { ?>
		ajaxRequest("<?php echo $initialExplorerContent.'?'.$initialExplorerContentParameter; ?>", "explorer-content");
	<?php } ?>
	<?php if(empty($_SESSION['oco_last_login'])) {
		$_SESSION['oco_last_login'] = true;
		echo "topConfettiRain();";
		echo "emitMessage(LANG['welcome_text'], LANG['welcome_description'], MESSAGE_TYPE_INFO);";
	} ?>
	<?php if(boolval($db->settings->get('check-update'))) { echo "window.setTimeout(checkUpdate, 1000);"; } ?>
	</script>

</div>

</body>
</html>
