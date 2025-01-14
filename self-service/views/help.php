<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

const HELP_PATH      = __DIR__.'/help';

$fileName = 'index.html';
if(!empty($_GET['page'])
&& in_array($_GET['page'], scandir(HELP_PATH)))
	$fileName = $_GET['page'];

if(file_exists(HELP_PATH.'/'.$fileName) && is_file(HELP_PATH.'/'.$fileName)) {
	$content = file_get_contents(HELP_PATH.'/'.$fileName);
	echo $content;
} else {
	echo "<div class='alert warning'>".LANG('not_found')."</div>";
}
