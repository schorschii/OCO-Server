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

	private function getTableColumnInfo($table, $column) {
		$this->stmt = $this->dbh->prepare("SELECT DATA_TYPE, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :tbl AND COLUMN_NAME = :col AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute([':tbl'=>$table, ':col'=>$column]);
		if($this->stmt->rowCount() == 0) return false;
		foreach($this->stmt->fetchAll() as $row) {
			return $row;
		}
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

				if($this->debug) echo 'Upgrading to 1.1.2... (add mobile_device_command table)'."\n";
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

				if($this->debug) echo 'Upgrading to 1.1.2... (add mobile_device_group table)'."\n";
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

				if($this->debug) echo 'Upgrading to 1.1.2... (add mobile_device_group_member table)'."\n";
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

				$upgraded = true;
		}

		// upgrade from 1.1.2 to 1.1.3
		$this->stmt = $this->dbh->prepare("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'mobile_device' AND COLUMN_NAME = 'id' AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute();
		foreach($this->stmt->fetchAll() as $row) {
			if($row['EXTRA'] !== 'auto_increment') {
				if($this->debug) echo 'Upgrading to 1.1.3... (add missing mobile_device* AUTO_INCREMENT)'."\n";
				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `mobile_device` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');
				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `mobile_device_command` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');
				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `mobile_device_group` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');
				$this->stmt = $this->dbh->prepare(
					"ALTER TABLE `mobile_device_group_member` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');
				$upgraded = true;
			}
		}

		if(!$this->getTableColumnInfo('app', 'id')) {
				if($this->debug) echo 'Upgrading to 1.1.3... (add app table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `app` (
					  `id` int(11) NOT NULL,
					  `identifier` varchar(350) NOT NULL,
					  `name` varchar(350) NOT NULL,
					  `display_version` text NOT NULL,
					  `version` text NOT NULL,
					  PRIMARY KEY (`id`),
					  KEY `identifier` (`identifier`),
					  KEY `name` (`name`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				if($this->debug) echo 'Upgrading to 1.1.3... (add mobile_device_app table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_app` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `mobile_device_id` int(11) NOT NULL,
					  `app_id` int(11) NOT NULL,
					  PRIMARY KEY (`id`),
					  KEY `fk_mobile_device_app_1` (`mobile_device_id`),
					  KEY `fk_mobile_device_app_2` (`app_id`),
					  CONSTRAINT `fk_mobile_device_app_1` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
					  CONSTRAINT `fk_mobile_device_app_2` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				if($this->debug) echo 'Upgrading to 1.1.3... (add profile table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `profile` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `name` text NOT NULL,
					  `payload` text NOT NULL,
					  `notes` text NOT NULL,
					  `created` timestamp NOT NULL DEFAULT current_timestamp(),
					  `created_by_system_user_id` int(11) DEFAULT NULL,
					  `last_update` timestamp NULL DEFAULT NULL,
					  PRIMARY KEY (`id`),
					  KEY `fk_profile_1` (`created_by_system_user_id`),
					  CONSTRAINT `fk_profile_1` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				if($this->debug) echo 'Upgrading to 1.1.3... (add mobile_device_group_profile table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_group_profile` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `mobile_device_group_id` int(11) NOT NULL,
					  `profile_id` int(11) NOT NULL,
					  PRIMARY KEY (`id`),
					  KEY `fk_mobile_device_group_profile_1` (`mobile_device_group_id`),
					  KEY `fk_mobile_device_group_profile_2` (`profile_id`),
					  CONSTRAINT `fk_mobile_device_group_profile_1` FOREIGN KEY (`mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
					  CONSTRAINT `fk_mobile_device_group_profile_2` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				if($this->debug) echo 'Upgrading to 1.1.3... (add mobile_device_profile table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_profile` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `mobile_device_id` int(11) NOT NULL,
					  `uuid` varchar(350) NOT NULL,
					  `identifier` text NOT NULL,
					  `display_name` text NOT NULL,
					  `version` text NOT NULL,
					  `content` text NOT NULL,
					  PRIMARY KEY (`id`),
					  KEY `fk_mobile_device_profile` (`mobile_device_id`),
					  KEY `uuid` (`uuid`),
					  CONSTRAINT `fk_mobile_device_profile` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				if($this->debug) echo 'Upgrading to 1.1.3... (add managed_app table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `managed_app` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`identifier` text NOT NULL,
						`store_id` text NOT NULL,
						`name` text NOT NULL,
						`vpp_amount` int(11) DEFAULT NULL,
						PRIMARY KEY (`id`)
					  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				if($this->debug) echo 'Upgrading to 1.1.3... (add mobile_device_group_managed_app table)'."\n";
				$this->stmt = $this->dbh->prepare(
					"CREATE TABLE `mobile_device_group_managed_app` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`mobile_device_group_id` int(11) NOT NULL,
						`managed_app_id` int(11) NOT NULL,
						`removable` tinyint(4) NOT NULL DEFAULT 1,
						`disable_cloud_backup` tinyint(4) NOT NULL DEFAULT 1,
						`remove_on_mdm_remove` tinyint(4) NOT NULL DEFAULT 1,
						`config` text DEFAULT NULL,
						PRIMARY KEY (`id`),
						KEY `fk_mobile_device_group_managed_app_1` (`mobile_device_group_id`),
						KEY `fk_mobile_device_group_managed_app_2` (`managed_app_id`),
						CONSTRAINT `fk_mobile_device_group_managed_app_1` FOREIGN KEY (`mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
						CONSTRAINT `fk_mobile_device_group_managed_app_2` FOREIGN KEY (`managed_app_id`) REFERENCES `managed_app` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
					  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		if($this->getTableColumnInfo('mobile_device', 'push_token')['DATA_TYPE'] == 'text') {
			if($this->debug) echo 'Upgrading to 1.1.3... (convert push_token to blob)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device` CHANGE `push_token` `push_token` BLOB NULL DEFAULT NULL;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$this->stmt = $this->dbh->prepare("SELECT id, push_token FROM mobile_device");
			$this->stmt->execute();
			foreach($this->stmt->fetchAll() as $row2) {
				$stmt = $this->dbh->prepare("UPDATE mobile_device SET push_token=:push_token WHERE id=:id");
				$stmt->execute([':id'=>$row2['id'], ':push_token'=>base64_decode($row2['push_token'])]);
			}

			$upgraded = true;
		}

		if($this->getTableColumnInfo('app', 'id')['EXTRA'] != 'auto_increment') {
			if($this->debug) echo 'Upgrading to 1.1.3... (add auto_increment to app.id)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `app` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT; ");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('password_rotation_rule', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.3... (add password_rotation_rule table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `password_rotation_rule` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `computer_group_id` int(11) DEFAULT NULL,
				  `username` text NOT NULL,
				  `alphabet` text NOT NULL,
				  `length` int(11) NOT NULL,
				  `valid_seconds` int(11) NOT NULL,
				  `history` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_password_rotation_rule_1` (`computer_group_id`),
				  CONSTRAINT `fk_password_rotation_rule_1` FOREIGN KEY (`computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.3... (add computer_password table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `computer_password` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `computer_id` int(11) NOT NULL,
				  `username` text NOT NULL,
				  `password` text NOT NULL,
				  `created` timestamp NOT NULL DEFAULT current_timestamp(),
				  PRIMARY KEY (`id`),
				  KEY `fk_computer_password_1` (`computer_id`),
				  CONSTRAINT `fk_computer_password_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.3... (update permissions of superadmin role)'."\n";
				$this->stmt = $this->dbh->prepare(
					"UPDATE system_user_role SET permissions='{\"Special\\\\\\\\ClientApi\": true, \"Special\\\\\\\\WebFrontend\": true, \"Special\\\\\\\\GeneralConfiguration\": true, \"Special\\\\\\\\EventQueryRules\": true, \"Special\\\\\\\\PasswordRotationRules\": true, \"Special\\\\\\\\DeletedObjects\": true, \"Models\\\\\\\\Computer\": {\"*\": {\"read\": true, \"write\": true, \"wol\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\ComputerGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Package\": {\"*\": {\"read\": true, \"write\": true, \"download\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\PackageGroup\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}}, \"Models\\\\\\\\PackageFamily\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\DomainUser\": {\"read\": true, \"delete\": true}, \"Models\\\\\\\\SystemUser\": true, \"Models\\\\\\\\Report\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"delete\": true} }, \"Models\\\\\\\\ReportGroup\": {\"create\":true, \"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}}, \"Models\\\\\\\\JobContainer\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Software\": true, \"Models\\\\\\\\DeploymentRule\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\MobileDevice\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\MobileDeviceGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Profile\": {\"*\": {\"read\": true, \"write\": true, \"deploy\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\ManagedApp\": {\"*\": {\"read\":true, \"write\":true, \"delete\":true, \"deploy\":true}}}' WHERE id = 1"
				);
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
		}

		if($this->getTableColumnInfo('setting', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.4... (remove id from setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `setting` DROP `id`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.4... (change key to varchar in setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `setting` CHANGE `key` `key` VARCHAR(50) NOT NULL");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.4... (make key primary key in setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `setting` ADD PRIMARY KEY(`key`)");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if($this->getTableColumnInfo('log', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.4... (remove id from log table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `log` DROP `id`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.4... (object_id index in setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `log` ADD INDEX(`object_id`)");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		return $upgraded;
	}

}
