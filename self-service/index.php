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
			<a href='index.php' id='m3NavbarLeftLogo' class='noprint' onclick='event.preventDefault();refreshContentExplorer("views/homepage.php");' title='<?php echo LANG('home_page'); ?>'>
				<img src='img/logo.dyn.svg' alt='Logo'>
			</a>
			<a href='index.php' onclick='event.preventDefault();refreshContentExplorer("views/homepage.php");' class='title'><?php echo LANG('self_service_name'); ?></a>
		</span>

		<div id='m3NavLinks' class='m3-navbar-links'>
			<button id='m3NavComputers' class='m3-navbar-link' onclick='event.preventDefault();this.parentElement.classList.remove("show-mobile-menu");refreshContentExplorer("views/computers.php");'>
				<img src='img/computer.dyn.svg' class='m3-navbar-btn-icon'> <?php echo LANG('portal_redesign_nav_computers'); ?>
			</button>
			<button id='m3NavStore' class='m3-navbar-link' onclick='event.preventDefault();this.parentElement.classList.remove("show-mobile-menu");refreshContentExplorer("views/packages.php");'>
				<img src='img/package.dyn.svg' class='m3-navbar-btn-icon'> <?php echo LANG('portal_redesign_nav_store'); ?>
			</button>
			<button id='m3NavJobs' class='m3-navbar-link' onclick='event.preventDefault();this.parentElement.classList.remove("show-mobile-menu");refreshContentExplorer("views/job-containers.php");'>
				<img src='img/job.dyn.svg' class='m3-navbar-btn-icon'> <?php echo LANG('portal_redesign_nav_jobs'); ?>
			</button>
		</div>

		<span class='right'>
			<button id='m3BurgerBtn' class='m3-navbar-link m3-burger-btn noprint' onclick='event.preventDefault();event.stopPropagation();document.getElementById("m3NavLinks").classList.toggle("show-mobile-menu");'>
				<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" style="display: block;">
					<path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
				</svg>
			</button>
			<button id='btnThemeToggle' class='noprint' onclick='toggleDarkMode()' title='<?php echo LANG('portal_redesign_theme_dark'); ?>'>
				<img id='imgThemeToggle' src='img/theme-moon.light.svg'>
			</button>
			<button id='btnRefresh' class='noprint' onclick='refreshContent();' title='<?php echo LANG('refresh'); ?>'>
				<img src='img/refresh.light.svg' alt='Refresh'>
			</button>
			<span class='separator noprint'></span>
			<button id='btnLogout' onclick='window.location.href="login.php?logout"' title='<?php echo LANG('log_out'); ?>'><span><?php echo htmlspecialchars($currentDomainUser->display_name); ?>&nbsp;</span><img src='img/exit.light.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-content' role='main'>
			<?php if($initialExplorerContent == null) { ?>
				<div class='alert error'><?php echo LANG('requested_view_does_not_exist'); ?></div>
			<?php } ?>
		</div>
	</div>

	<div id='divDialogTemplate' class='dialogContainer' role='complementary'>
		<img src='img/loader.svg'>
		<div class='dialogBox'>
			<h2 class='dialogTitle'></h2>
			<div class='dialogText'></div>
			<button class='dialogClose' title='<?php echo LANG('close'); ?>'><img src='img/close.dyn.svg'></button>
		</div>
	</div>

	<div id='message-container' role='complementary'>
	</div>

	<script>
	<?php if($initialExplorerContent != null) { ?>
		ajaxRequest("<?php echo $initialExplorerContent.'?'.$initialExplorerContentParameter; ?>", "explorer-content");
	<?php } ?>
	</script>

</div>

</body>
</html>
