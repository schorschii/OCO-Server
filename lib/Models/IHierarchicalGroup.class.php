<?php

namespace Models;

interface IHierarchicalGroup {

	public function getId();
	public function getParentId();
	public function getName();

}
