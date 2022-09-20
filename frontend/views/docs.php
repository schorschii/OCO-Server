<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

const DOCS_PATH      = __DIR__.'/../../docs';
const DECISIONS_DIR  = 'decisions';
const DECISIONS_PATH = DOCS_PATH.'/'.DECISIONS_DIR;

$decisionFiles = [];
foreach(scandir(DECISIONS_PATH) as $decision) {
	if(startsWith($decision, '.')) continue;
	$decisionFiles[] = DECISIONS_DIR.'/'.$decision;
}

$fileName = 'README.md';
if(!empty($_GET['page'])
&& in_array($_GET['page'], array_merge(['../README.md'], scandir(DOCS_PATH), $decisionFiles)))
	$fileName = $_GET['page'];

if(file_exists(DOCS_PATH.'/'.$fileName) && is_file(DOCS_PATH.'/'.$fileName)) {
	$Parsedown = new Parsedown();
	$content = file_get_contents(DOCS_PATH.'/'.$fileName);
	if(empty($content)) die("<div class='alert error'>".LANG('not_found')."</div>");
	$html = $Parsedown->text($content);

	$dom = new DOMDocument;
	@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
	// link adjustments
	$nodes = $dom->getElementsByTagName('a');
	foreach($nodes as $node) {
		$attr = $node->getAttribute('href');
		if(!empty($attr)) {
			if(startsWith($attr, 'https://github.com/')) continue;
			$node->setAttribute('href', 'index.php?view=docs&page='.urlencode($attr));
		}
	}
	// image adjustments
	$nodes = $dom->getElementsByTagName('img');
	foreach($nodes as $node) {
		$attr = $node->getAttribute('src');
		if(!empty($attr)) {
			$prefix = ''; if(startsWith($fileName, '../')) $prefix = '../';
			$base64 = base64_encode(file_get_contents(DOCS_PATH.'/'.$prefix.$node->getAttribute('src')));
			$node->setAttribute('src', 'data:image/png;base64,'.$base64);
		}
	}
	echo $dom->saveHTML();
} elseif($fileName == 'decisions') {
	echo "<ul>";
	foreach($decisionFiles as $decision) {
		echo "<li><a href='index.php?view=docs&page=decisions/".urlencode(basename($decision))."'>".htmlspecialchars(basename($decision))."</a></href>";
	}
	echo "</ul>";
} else {
	echo "<div class='alert error'>".LANG('not_found')."</div>";
}
