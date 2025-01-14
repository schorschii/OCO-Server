<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

const DOCS_PATH      = __DIR__.'/../../docs';
const DECISIONS_DIR  = 'decisions';
const DECISIONS_PATH = DOCS_PATH.'/'.DECISIONS_DIR;
const CATALOG_DIR  = 'install-uninstall-catalog';
const CATALOG_PATH = DOCS_PATH.'/'.CATALOG_DIR;

$decisionFiles = [];
foreach(scandir(DECISIONS_PATH) as $file) {
	if(startsWith($file, '.')) continue;
	$decisionFiles[] = DECISIONS_DIR.'/'.$file;
}
$catalogFiles = [];
foreach(scandir(CATALOG_PATH) as $file) {
	if(startsWith($file, '.')) continue;
	$catalogFiles[] = CATALOG_DIR.'/'.$file;
}

$fileName = 'README.md';
if(!empty($_GET['page'])
&& in_array($_GET['page'], array_merge(['../README.md'], scandir(DOCS_PATH), $decisionFiles, $catalogFiles)))
	$fileName = $_GET['page'];

if(file_exists(DOCS_PATH.'/'.$fileName) && is_file(DOCS_PATH.'/'.$fileName)) { // render MarkDown as HTML

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
			if(startsWith($attr, 'https://')) {
				$node->setAttribute('target', '_blank');
			} else {
				$node->setAttribute('href', 'index.php?view=docs&page='.urlencode($attr));
			}
		}
	}
	// image adjustments
	$nodes = $dom->getElementsByTagName('img');
	foreach($nodes as $node) {
		$attr = $node->getAttribute('src');
		if(!empty($attr)) {
			$prefix = ''; if(startsWith($fileName, '../')) $prefix = '../';
			$filePath = DOCS_PATH.'/'.$prefix.$node->getAttribute('src');
			$base64 = base64_encode(file_get_contents($filePath));
			$node->setAttribute('src', 'data:'.mime_content_type($filePath).';base64,'.$base64);
		}
	}
	echo $dom->saveHTML();

} elseif($fileName == DECISIONS_DIR) { // directory listing

	echo "<ul>";
	foreach($decisionFiles as $file) {
		echo "<li><a href='index.php?view=docs&page=".urlencode($fileName)."/".urlencode(basename($file))."'>".htmlspecialchars(basename($file))."</a></href>";
	}
	echo "</ul>";

} elseif($fileName == CATALOG_DIR) { // directory listing

	echo "<ul>";
	foreach($catalogFiles as $file) {
		echo "<li><a href='index.php?view=docs&page=".urlencode($fileName)."/".urlencode(basename($file))."'>".htmlspecialchars(basename($file))."</a></href>";
	}
	echo "</ul>";

} else {

	echo "<div class='alert error'>".LANG('not_found')."</div>";

}
