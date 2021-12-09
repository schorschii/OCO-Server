<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../../lib/Parsedown.php');
require_once('../session.php');

const DOCS_DIR = __DIR__.'/../../docs';

$fileName = 'README.md';
if(!empty($_GET['page']) && in_array($_GET['page'], scandir(DOCS_DIR)))
	$fileName = $_GET['page'];

if(file_exists(DOCS_DIR.'/'.$fileName)) {
	$Parsedown = new Parsedown();
	$content = file_get_contents(DOCS_DIR.'/'.$fileName);
	if(empty($content)) die("<div class='alert error'>".LANG['not_found']."</div>");
	$html = $Parsedown->text($content);

	$dom = new DOMDocument;
	@$dom->loadHTML($html);
	$nodes = $dom->getElementsByTagName('a');
	foreach($nodes as $node) {
		foreach($node->attributes as $att) {
			if($att->name == 'href') {
				$node->setAttribute('href', 'index.php?view=docs&page='.urlencode($att->value));
			}
		}
	}
	echo $dom->saveHTML();
} else {
	echo "<div class='alert error'>".LANG['not_found']."</div>";
}

