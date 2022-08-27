<link rel='icon' href='img/logo.dyn.svg' type='image/svg'>
<link rel='apple-touch-icon' sizes='1024x1024' href='img/logo.png'>

<link rel='stylesheet' type='text/css' href='css/main.css?v=<?php echo urlencode(OcoServer::APP_VERSION); ?>'>

<script src='js/strings.js.php?v=<?php echo urlencode(OcoServer::APP_VERSION); ?>'></script>
<script src='js/main.js?v=<?php echo urlencode(OcoServer::APP_VERSION); ?>'></script>
<script src='js/table.js?v=<?php echo urlencode(OcoServer::APP_VERSION); ?>'></script>
<script src='js/notification.js?v=<?php echo urlencode(OcoServer::APP_VERSION); ?>'></script>
<script src='js/confetti.js?v=<?php echo urlencode(OcoServer::APP_VERSION); ?>'></script>

<?php
// include extension JS & CSS
foreach(glob(__DIR__.'/js/js.d/*.js') as $filename) {
	echo '<script src="js/js.d/'.htmlspecialchars(basename($filename), ENT_QUOTES).'"></script>';
}
foreach(glob(__DIR__.'/css/css.d/*.css') as $filename) {
	echo '<link rel="stylesheet" type="text/css" href="css/css.d/'.htmlspecialchars(basename($filename), ENT_QUOTES).'"></link>';
}
?>

<link rel='prefetch' as='image' href='img/expand.dyn.svg'>
<link rel='prefetch' as='image' href='img/collapse.dyn.svg'>
<link rel='prefetch' as='image' href='img/close.opacity.svg'>
<link rel='prefetch' as='image' href='img/info.message.svg'>
<link rel='prefetch' as='image' href='img/tick.message.svg'>
<link rel='prefetch' as='image' href='img/warning.message.svg'>
<link rel='prefetch' as='image' href='img/error.message.svg'>

<meta name='viewport' content='width=device-width'>
<meta name='author' content='Georg Sieber'>
