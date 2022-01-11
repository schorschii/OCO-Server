<?php

require_once(__DIR__.'/../conf.php');
require_once('Const.php');
require_once('Models.php');
require_once('DatabaseController.php');
require_once('Lang.php');
require_once('ResumeDownload.php');
require_once('AuthenticationController.php');
require_once('CoreLogic.php');
require_once('PermissionManager.php');
require_once('Tools.php');

$db = new DatabaseController();

if(DO_HOUSEKEEPING_BY_WEB_REQUESTS) {
	require_once('HouseKeeping.php');
}
