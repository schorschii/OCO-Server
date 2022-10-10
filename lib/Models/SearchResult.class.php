<?php

namespace Models;

class SearchResult {

	public $searchTerm;
	public $object;

	public $text;
	public $type;
	public $link;
	public $newTab;
	public $icon;

	function __construct(string $text, string $type, string $link, string $icon, $newTab=false) {
		$this->text = $text;
		$this->type = $type;
		$this->link = $link;
		$this->icon = $icon;
		$this->newTab = $newTab;
	}

}
