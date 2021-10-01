<link rel='icon' href='img/logo.dyn.svg' type='image/svg'>
<link rel='apple-touch-icon' sizes='1024x1024' href='img/logo.png'>

<link rel='stylesheet' type='text/css' href='css/font.css'>
<link rel='stylesheet' type='text/css' href='css/main.css'>

<script src='js/strings.js.php'></script>
<script src='js/main.js'></script>
<script src='js/table.js'></script>
<script src='js/notification.js'></script>
<script src='js/confetti.js'></script>

<?php
// include extension JS & CSS
foreach(glob(__DIR__.'/js/js.d/*.js') as $filename) {
	echo '<script src="js/js.d/'.htmlspecialchars(basename($filename), ENT_QUOTES).'"></script>';
}
foreach(glob(__DIR__.'/css/css.d/*.css') as $filename) {
	echo '<link rel="stylesheet" type="text/css" href="css/css.d/'.htmlspecialchars(basename($filename), ENT_QUOTES).'"></link>';
}
?>

<meta name='viewport' content='width=720'>
<meta name='author' content='Georg Sieber'>
