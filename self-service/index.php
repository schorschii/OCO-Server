<?php
/* KEEP IT SIMPLE */

require_once('../loader.inc.php');
require_once('session.inc.php');

$initialExplorerContent = 'views/homepage.php';
$initialExplorerContentParameter = '';
if(!empty($_GET['view'])) {
	// check which view should be loaded via ajax
	if(in_array($_GET['view'].'.php', scandir(__DIR__.'/views'))
	|| array_key_exists($_GET['view'].'.php', $ext->getAggregatedConf('self-service-views'))) {
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
	<title><?php echo LANG('self_service_name'); ?></title>
	<?php require_once('head.inc.php'); ?>
	<!--
		Wir begrüßen Sie an diesem wunderschönen <?php echo date("l"); ?>,
		<?php echo time(); ?> Sekunden nach dem Unix-Urknall!
		PS. Obacht! Heute ist der <?php echo date("N"); ?>. Tag der Woche und die Woche zieht sich schon wieder!!!
	-->
</head>
<body>

<div id='container'>

	<div id='header' role='banner'>
		<span class='left'>
			<button id='btnSidebar' class='noprint' onclick='toggleSidebar()' title='<?php echo LANG('show_hide_sidebar'); ?>'><img src='img/menu.light.svg'></button>
			<a href='index.php' onclick='event.preventDefault();refreshContentExplorer("views/homepage.php");' class='title'><?php echo LANG('self_service_name'); ?></a>
		</span>
		<span class='right'>
			<button id='btnHomepage' class='noprint' onclick='refreshContentExplorer("views/homepage.php")' title='<?php echo LANG('home_page'); ?>'><img src='img/home.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnRefresh' class='noprint' onclick='refreshContent();refreshSidebar();' oncontextmenu='toggleAutoRefresh();return false;' title='<?php echo LANG('refresh'); ?>'><img src='img/refresh.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnInfo' class='noprint' onclick='showDialogAjax("<?php echo LANG('about'); ?>", "views/dialog/about.php", DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_SMALL)' title='<?php echo LANG('about'); ?>'><img src='img/info.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnHelp' class='noprint' onclick='refreshContentExplorer("views/help.php")' title='<?php echo LANG('help'); ?>'><img src='img/help.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnLogout' onclick='window.location.href="login.php?logout"' title='<?php echo LANG('log_out'); ?>'><span><?php echo htmlspecialchars($currentDomainUser->display_name); ?>&nbsp;</span><img src='img/exit.light.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' onclick='toggleSidebar(false)' role='navigation'>
		</div>
		<div id='explorer-content' role='main'>
			<?php if($initialExplorerContent == null) { ?>
				<div class='alert error'><?php echo LANG('requested_view_does_not_exist'); ?></div>
			<?php } ?>
		</div>
	</div>

	<div id='dialog-container' role='complementary'>
		<img src='img/loader.svg'>
		<div id='dialog-box'>
			<h2 id='dialog-title'></h2>
			<div id='dialog-text'></div>
			<button id='btnDialogClose' title='<?php echo LANG('close'); ?>' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'></button>
		</div>
	</div>

	<div id='message-container' role='complementary'>
	</div>

	<script>
	toggleSidebar(false);
	refreshSidebar();
	refreshSidebarTimer = setTimeout(function(){ refreshSidebar(null, true) }, REFRESH_SIDEBAR_TIMEOUT);

	<?php if($initialExplorerContent != null) { ?>
		ajaxRequest("<?php echo $initialExplorerContent.'?'.$initialExplorerContentParameter; ?>", "explorer-content");
	<?php } ?>
	</script>

</div>

</body>
</html>
