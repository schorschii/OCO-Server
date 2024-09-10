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
		$this->stmt = $this->dbh->prepare("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'deployment_rule' AND COLUMN_NAME = 'auto_uninstall' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		if($this->stmt->rowCount() > 0) {
				if($this->debug) echo 'Upgrading to 1.0.2... (drop auto_uninstall column)'."\n";

				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `deployment_rule` DROP COLUMN `auto_uninstall`");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		// upgrade from 1.0.3 to 1.1.0
		$this->stmt = $this->dbh->prepare("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'job_container_job' AND COLUMN_NAME = 'download_progress' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		if($this->stmt->rowCount() == 0) {
				if($this->debug) echo 'Upgrading to 1.1.0... (add download_progress column)'."\n";

				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `job_container_job` ADD COLUMN `download_progress` float DEFAULT NULL AFTER state");
				if(!$this->stmt->execute()) throw new Exception('SQL error');
				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `deployment_rule_job` ADD COLUMN `download_progress` float DEFAULT NULL AFTER state");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		// upgrade from 1.1.0 to 1.1.1
		$this->stmt = $this->dbh->prepare("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'package_family' AND COLUMN_NAME = 'license_count' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		if($this->stmt->rowCount() == 0) {
				if($this->debug) echo 'Upgrading to 1.1.1... (add license_count column)'."\n";

				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `package_family` ADD COLUMN `license_count` int(11) DEFAULT NULL AFTER icon");
				if(!$this->stmt->execute()) throw new Exception('SQL error');
				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `package` ADD COLUMN `license_count` int(11) DEFAULT NULL AFTER compatible_os_version");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		// upgrade from 1.1.1 to 1.1.2
		$this->stmt = $this->dbh->prepare("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'mobile_device' AND COLUMN_NAME = 'id' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		if($this->stmt->rowCount() == 0) {
				if($this->debug) echo 'Upgrading to 1.1.2... (add mobile_device table)'."\n";

				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device` (
  `id` int(11) NOT NULL,
  `udid` varchar(40) DEFAULT NULL,
  `device_name` text NOT NULL,
  `serial` varchar(100) NOT NULL,
  `vendor_description` text NOT NULL,
  `model` text NOT NULL,
  `os` text NOT NULL,
  `device_family` text NOT NULL,
  `color` text NOT NULL,
  `profile_uuid` text DEFAULT NULL,
  `push_token` text DEFAULT NULL,
  `push_magic` text DEFAULT NULL,
  `push_sent` timestamp NULL DEFAULT NULL,
  `unlock_token` blob DEFAULT NULL,
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `notes` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_update` timestamp NULL DEFAULT NULL,
  `force_update` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `udid` (`udid`),
  UNIQUE KEY `serial_number` (`serial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_command` (
  `id` int(11) NOT NULL,
  `mobile_device_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `parameter` text NOT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_command_1` (`mobile_device_id`),
  CONSTRAINT `fk_mobile_device_command_1` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_group` (
  `id` int(11) NOT NULL,
  `parent_mobile_device_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_parent_mobile_device_group_id` (`parent_mobile_device_group_id`),
  CONSTRAINT `fk_parent_mobile_device_group_id` FOREIGN KEY (`parent_mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_group_member` (
  `id` int(11) NOT NULL,
  `mobile_device_id` int(11) NOT NULL,
  `mobile_device_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_group_member_1` (`mobile_device_group_id`),
  KEY `fk_mobile_device_group_member_2` (`mobile_device_id`),
  CONSTRAINT `fk_mobile_device_group_member_1` FOREIGN KEY (`mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mobile_device_group_member_2` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$this->stmt = $this->dbh->prepare(
					"UPDATE system_user_role SET permissions='{\"Special\\\\\\\\ClientApi\": true, \"Special\\\\\\\\WebFrontend\": true, \"Special\\\\\\\\GeneralConfiguration\": true, \"Special\\\\\\\\EventQueryRules\": true, \"Special\\\\\\\\DeletedObjects\": true, \"Models\\\\\\\\Computer\": {\"*\": {\"read\": true, \"write\": true, \"wol\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\ComputerGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Package\": {\"*\": {\"read\": true, \"write\": true, \"download\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\PackageGroup\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"delete\": true}}, \"Models\\\\\\\\PackageFamily\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\DomainUser\": {\"read\": true, \"delete\": true}, \"Models\\\\\\\\SystemUser\": true, \"Models\\\\\\\\Report\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"delete\": true} }, \"Models\\\\\\\\ReportGroup\": {\"create\":true, \"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}}, \"Models\\\\\\\\JobContainer\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Software\": true, \"Models\\\\\\\\DeploymentRule\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\MobileDevice\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\MobileDeviceGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\MobileDeviceCommand\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true}, \"create\": true}}' WHERE id = 1"
				);
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		return $upgraded;
	}

}
