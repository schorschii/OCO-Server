<?php
require_once('../../loader.inc.php');
header('Content-Type: text/javascript');
?>
var LANG = <?php echo json_encode(LanguageController::getSingleton()->getMessages()); ?>
