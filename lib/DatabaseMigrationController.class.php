<?php

class DatabaseMigrationController {

	/*
		Class DatabaseMigrationController

		Executes automatic database schema upgrades.
	*/

	protected $dbh;
	private $stmt;
	private $debug;

	function __construct($dbh, $debug=false) {
		$this->dbh = $dbh;
		$this->debug = $debug;
	}

	public function upgrade() {
		$upgraded = false;
		// Note: PDO::beginTransaction() is useless for schema changes in MySQL and corresponding commit() will throw an error since PHP 8, that's why no transactions are used here.

		// upgrade from 1.0.0 to 1.0.1
		$this->stmt = $this->dbh->prepare("SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'software' AND COLUMN_NAME = 'description' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		foreach($this->stmt->fetchAll() as $row) {
			if($row['CHARACTER_MAXIMUM_LENGTH'] !== 350) {
				if($this->debug) echo 'Upgrading to 1.0.1... (adjust software.description length)'."\n";

				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `software` CHANGE `description` `description` varchar(350) COLLATE utf8mb4_bin NOT NULL");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
			}
		}

		// upgrade from 1.0.1 to 1.0.2
		$this->stmt = $this->dbh->prepare("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'job_container' AND COLUMN_NAME = 'time_frames' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		if($this->stmt->rowCount() == 0) {
				if($this->debug) echo 'Upgrading to 1.0.2... (add time_frames column)'."\n";

				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `job_container` ADD COLUMN `time_frames` text DEFAULT NULL AFTER agent_ip_ranges");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		return $upgraded;
	}

}
