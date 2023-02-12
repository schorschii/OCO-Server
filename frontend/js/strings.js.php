<?php
require_once('../../loader.inc.php');
header('Content-Type: text/javascript');

echo "var LANG = {\n";
foreach(LanguageController::getSingleton()->getMessages() as $key => $message) {
    echo "\t'".$key."': '".str_replace("'",'"',str_replace("\n",'\\n',str_replace('\\','\\\\',$message)))."',\n";
}
echo "}\n";
