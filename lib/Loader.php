<?php

require_once(__DIR__.'/../conf.php');
require_once('Const.php');
require_once('Models.php');
require_once('Db.php');
require_once('Lang.php');
require_once('ResumeDownload.php');
require_once('CoreLogic.php');
require_once('Tools.php');

$db = new Db();
$cl = new CoreLogic($db);
