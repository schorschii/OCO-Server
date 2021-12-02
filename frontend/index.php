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
			<a href='index.php' onclick='event.preventDefault();refreshContentHomepage();' class='title'><?php echo LANG['app_name']; ?></a>
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
			<button id='btnHomepage' class='noprint' onclick='refreshContentHomepage()' title='<?php echo LANG['home_page']; ?>'><img src='img/home.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnRefresh' class='noprint' onclick='refreshContent();refreshSidebar();' ondblclick='toggleAutoRefresh()' title='<?php echo LANG['refresh']; ?>'><img src='img/refresh.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnSettings' class='noprint' onclick='refreshContentSettings()' title='<?php echo LANG['settings']; ?>'><img src='img/settings.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnInfo' class='noprint' onclick='showDialogHTML("<?php echo LANG['about']; ?>", obj("dialog-about").innerHTML, DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_SMALL, false)' title='<?php echo LANG['about']; ?>'><img src='img/info.light.svg'></button>
			<span class='separator noprint'></span>
			<button id='btnLogout' onclick='window.location.href="login.php?logout"' title='<?php echo LANG['log_out']; ?>'><span><?php echo htmlspecialchars($_SESSION['um_username']); ?>&nbsp;</span><img src='img/exit.light.svg'></button>
		</span>
	</div>

	<div id='explorer'>
		<div id='explorer-tree' onclick='closeSearchResults()'>
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
			<div id='dialog-controls' class='spread'>
				<button id='btnDialogHome' onclick='hideDialog();refreshContentHomepage();'><img src='img/home.svg'>&nbsp;<?php echo LANG['home_page']; ?></button>
				<button id='btnDialogReload' onclick='hideDialog();refreshContent();'><img src='img/refresh.svg'>&nbsp;<?php echo LANG['retry']; ?></button>
				<button id='btnDialogClose' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.svg'>&nbsp;<?php echo LANG['close']; ?></button>
			</div>
		</div>
	</div>

	<div id='dialog-contents'>
		<div id='dialog-about'>
			<div style='display:flex;align-items:center;margin-bottom:20px'>
				<img src='img/logo.dyn.svg' style='margin-right:15px'>
				<div style='display:inline-block'>
					<h3 style='margin-top:0px'><?php echo LANG['project_name']; ?></h3>
					<div><?php echo LANG['version'].' '.APP_VERSION; ?></div>
					<div><?php echo LANG['app_subtitle']; ?></div>
					<div><?php echo LANG['app_copyright']; ?></div>
				</div>
			</div>

			<h3>License</h3>
			<p>
				This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
			</p>
			<p>
				This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
			</p>
			<p>
				You should have received a copy of the GNU General Public License along with this program. If not, see https://www.gnu.org/licenses/.
			</p>

			<h3>3rd Party Components</h3>
			<p>
				<a href='https://github.com/catdad/canvas-confetti' target='_blank'><b>confetti.js</b></a>, Copyright (c) 2020, Kiril Vatev, ISC License<br>
				Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.
			</p>
		</div>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

	<script>
	refreshSidebar();
	refreshSidebarTimer = setTimeout(function(){ refreshSidebar(null, true) }, REFRESH_SIDEBAR_TIMEOUT);

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
