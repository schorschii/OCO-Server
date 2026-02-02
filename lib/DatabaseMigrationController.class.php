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
		$this->stmt = $this->dbh->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :tbl AND COLUMN_NAME = :col AND TABLE_SCHEMA = '".DB_NAME."'");
		$this->stmt->execute([':tbl'=>$table, ':col'=>$column]);
		if($this->stmt->rowCount() == 0) return false;
		foreach($this->stmt->fetchAll() as $row) {
			return $row;
		}
	}

	public function upgrade() {
		$upgraded = false;
		// Note: PDO::beginTransaction() is useless for schema changes in MySQL and corresponding commit() will throw an error since PHP 8, that's why no transactions are used here.

		/*** 1.1.1 ***/
		if(!$this->getTableColumnInfo('package_family', 'license_count')) {
			if($this->debug) echo 'Upgrading to 1.1.1... (add license_count column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package_family` ADD COLUMN `license_count` int(11) DEFAULT NULL AFTER icon");
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package` ADD COLUMN `license_count` int(11) DEFAULT NULL AFTER compatible_os_version");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.1.2 ***/
		if(!$this->getTableColumnInfo('mobile_device', 'id')) {
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

		/*** 1.1.3 ***/
		if($this->getTableColumnInfo('mobile_device', 'id')['EXTRA'] !== 'auto_increment') {
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

			$upgraded = true;
		}

		/*** 1.1.6 ***/
		if($this->getTableColumnInfo('setting', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.6... (remove id from setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `setting` DROP `id`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.6... (change key to varchar in setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `setting` CHANGE `key` `key` VARCHAR(50) NOT NULL");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.6... (make key primary key in setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `setting` ADD PRIMARY KEY(`key`)");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if($this->getTableColumnInfo('log', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.6... (remove id from log table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `log` DROP `id`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.6... (object_id index in setting table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `log` ADD INDEX(`object_id`)");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('password_rotation_rule', 'default_password')) {
			if($this->debug) echo 'Upgrading to 1.1.6... (add default_password column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `password_rotation_rule` ADD COLUMN `default_password` text DEFAULT NULL AFTER history");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.1.7 ***/
		if(!$this->getTableColumnInfo('computer', 'battery_level')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add battery_level column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer` ADD COLUMN `battery_level` float DEFAULT NULL AFTER domain");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.7... (add battery_status column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer` ADD COLUMN `battery_status` tinyint(4) DEFAULT NULL AFTER battery_level");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('computer_device', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add computer_device table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `computer_device` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `computer_id` int(11) NOT NULL,
				  `subsystem` tinytext NOT NULL,
				  `vendor` int(11) NOT NULL,
				  `product` int(11) NOT NULL,
				  `serial` text NOT NULL,
				  `name` text NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_computer_device_1` (`computer_id`),
				  CONSTRAINT `fk_computer_device_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('computer_user', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add computer_user table)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `computer_user` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `computer_id` int(11) NOT NULL,
				  `username` text NOT NULL,
				  `display_name` text NOT NULL,
				  `uid` text NOT NULL,
				  `gid` text NOT NULL,
				  `home` text NOT NULL,
				  `shell` text NOT NULL,
				  `disabled` tinyint(4) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_computer_user_1` (`computer_id`),
				  CONSTRAINT `fk_computer_user_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if($this->getTableColumnInfo('mobile_device', 'serial')['COLUMN_DEFAULT'] !== 'NULL') {
			if($this->debug) echo 'Upgrading to 1.1.7... (make mobile_device.serial nullable)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device` CHANGE `serial` `serial` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.7... (add battery_status column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device` ADD COLUMN `state` text DEFAULT NULL AFTER udid");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('managed_app', 'type')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add type column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `managed_app` ADD `type` VARCHAR(10) NOT NULL DEFAULT 'ios' AFTER `id`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('mobile_device_command', 'external_id')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add external_id column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_command` ADD `external_id` VARCHAR(10) NULL DEFAULT NULL AFTER `id`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('mobile_device_group_managed_app', 'install_type')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add install_type column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_group_managed_app` ADD `install_type` TINYTEXT NULL DEFAULT NULL AFTER `remove_on_mdm_remove`;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(strtolower($this->getTableColumnInfo('mobile_device', 'info')['COLUMN_TYPE']) !== 'mediumtext') {
			if($this->debug) echo 'Upgrading to 1.1.7... (modify info column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device` CHANGE `info` `info` MEDIUMTEXT NULL DEFAULT NULL;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('computer_partition', 'name')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add name column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer_partition` ADD `name` text NOT NULL AFTER `free`;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			if($this->debug) echo 'Upgrading to 1.1.7... (add uuid column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer_partition` ADD `uuid` text NOT NULL AFTER `name`;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			if($this->debug) echo 'Upgrading to 1.1.7... (add encrypted column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer_partition` ADD `encrypted` tinyint(4) NOT NULL DEFAULT 0 AFTER `uuid`;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('profile', 'type')) {
			if($this->debug) echo 'Upgrading to 1.1.7... (add type column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `profile` ADD `type` VARCHAR(10) NOT NULL DEFAULT 'ios' AFTER `id`;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			if($this->debug) echo 'Upgrading to 1.1.7... (add policy_update column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device` ADD `policy` MEDIUMTEXT NULL DEFAULT NULL AFTER `info`;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.7... (update permissions of superadmin role)'."\n";
			$this->stmt = $this->dbh->prepare(
				"UPDATE system_user_role SET permissions='{\"Special\\\\\\\\ClientApi\": true, \"Special\\\\\\\\WebFrontend\": true, \"Special\\\\\\\\GeneralConfiguration\": true,  \"Special\\\\\\\\MobileDeviceSync\": true, \"Special\\\\\\\\EventQueryRules\": true, \"Special\\\\\\\\PasswordRotationRules\": true, \"Special\\\\\\\\DeletedObjects\": true, \"Models\\\\\\\\Computer\": {\"*\": {\"read\": true, \"write\": true, \"wol\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\ComputerGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Package\": {\"*\": {\"read\": true, \"write\": true, \"download\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\PackageGroup\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}}, \"Models\\\\\\\\PackageFamily\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\DomainUser\": {\"read\": true, \"delete\": true}, \"Models\\\\\\\\SystemUser\": true, \"Models\\\\\\\\Report\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"delete\": true} }, \"Models\\\\\\\\ReportGroup\": {\"create\":true, \"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}}, \"Models\\\\\\\\JobContainer\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Software\": true, \"Models\\\\\\\\DeploymentRule\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\MobileDevice\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\\\\\MobileDeviceGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\Profile\": {\"*\": {\"read\": true, \"write\": true, \"deploy\": true, \"delete\": true}, \"create\": true}, \"Models\\\\\\\\ManagedApp\": {\"*\": {\"read\":true, \"write\":true, \"delete\":true, \"deploy\":true}, \"create\": true}}' WHERE id = 1"
			);
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.1.10 ***/
		if(strtolower($this->getTableColumnInfo('mobile_device_command', 'external_id')['COLUMN_TYPE']) != 'varchar(20)') {
			if($this->debug) echo 'Upgrading to 1.1.10... (modify external_id column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_command` CHANGE `external_id` `external_id` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		$this->stmt = $this->dbh->prepare("SELECT JSON_UNQUOTE(JSON_EXTRACT(permissions, '$.\"Models\\\\\\\\ManagedApp\".create')) AS 'permissions' FROM system_user_role WHERE id = 1");
		$this->stmt->execute();
		foreach($this->stmt->fetchAll() as $row) {
			if($row['permissions'] != 'true') {
				if($this->debug) echo 'Upgrading to 1.1.10... (update permissions of superadmin role)'."\n";
				$this->stmt = $this->dbh->prepare(
					"UPDATE system_user_role SET permissions=JSON_SET(permissions, '$.\"Models\\\\\\\\ManagedApp\".create', 'true') WHERE id = 1"
				);
				if(!$this->stmt->execute()) throw new Exception('SQL error');

				$upgraded = true;
			}
		}

		if(!$this->getTableColumnInfo('managed_app', 'configurations')) {
			if($this->debug) echo 'Upgrading to 1.1.10... (add configurations column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `managed_app` ADD `configurations` TEXT NULL DEFAULT NULL AFTER `vpp_amount`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.10... (add config_id column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_group_managed_app` ADD `config_id` BIGINT NULL DEFAULT NULL AFTER `install_type`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('computer', 'agent_timestamp')) {
			if($this->debug) echo 'Upgrading to 1.1.10... (add agent_timestamp column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer` ADD `agent_timestamp` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00' AFTER `force_update`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('package', 'compatible_architecture')) {
			if($this->debug) echo 'Upgrading to 1.1.10... (add compatible_architecture column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package` ADD `compatible_architecture` text DEFAULT NULL AFTER `compatible_os_version`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(strtolower($this->getTableColumnInfo('computer', 'agent_timestamp')['DATA_TYPE']) != 'double') {
			if($this->debug) echo 'Upgrading to 1.1.10... (change agent_timestamp to DOUBLE)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `computer` CHANGE `agent_timestamp` `agent_timestamp` DOUBLE NOT NULL DEFAULT '0'");
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			$this->stmt = $this->dbh->prepare(
				"UPDATE `computer` SET `agent_timestamp` = 0 WHERE 1 = 1");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$this->stmt = $this->dbh->prepare(
				'UPDATE `package` SET compatible_os = REPLACE(compatible_os, ",", "\n") WHERE 1 = 1');
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			$this->stmt = $this->dbh->prepare(
				'UPDATE `package` SET compatible_os_version = REPLACE(compatible_os_version, ",", "\n") WHERE 1 = 1');
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			$this->stmt = $this->dbh->prepare(
				'UPDATE `package` SET compatible_architecture = REPLACE(compatible_architecture, ",", "\n") WHERE 1 = 1');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(!$this->getTableColumnInfo('package', 'last_update_by_system_user_id')) {
			if($this->debug) echo 'Upgrading to 1.1.10... (add last_update_by_system_user_id column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package` ADD `last_update_by_system_user_id` INT NULL DEFAULT NULL AFTER `last_update`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package` ADD CONSTRAINT `fk_package_3` FOREIGN KEY (`last_update_by_system_user_id`) REFERENCES `system_user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.1.11 ***/
		if(strtolower($this->getTableColumnInfo('mobile_device_group_managed_app', 'config_id')['COLUMN_TYPE']) != 'bigint(20) unsigned') {
			if($this->debug) echo 'Upgrading to 1.1.11... (change config_id to UNSIGNED)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_group_managed_app` CHANGE `config_id` `config_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		if(strtoupper($this->getTableColumnInfo('package', 'last_update')['COLUMN_DEFAULT']) != 'NULL') {
			if($this->debug) echo 'Upgrading to 1.1.11... (change last_update to NULL)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package` CHANGE `last_update` `last_update` TIMESTAMP NULL DEFAULT NULL");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.1.12 ***/
		if(!$this->getTableColumnInfo('mobile_device_group_managed_app', 'delegated_scopes')) {
			if($this->debug) echo 'Upgrading to 1.1.12... (add delegated_scopes column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_group_managed_app` ADD `delegated_scopes` text DEFAULT NULL AFTER `config`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}
		if(!$this->getTableColumnInfo('policy_definition_group', 'id')) {
			if($this->debug) echo 'Upgrading to 1.1.12... (add policy_definition_group)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `policy_definition_group` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `parent_policy_definition_group_id` INT NULL DEFAULT NULL,
				  `name` text NOT NULL,
				  `display_name` text NOT NULL,
				  `description` text DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  CONSTRAINT `fk_parent_policy_definition_group` FOREIGN KEY (`parent_policy_definition_group_id`) REFERENCES `policy_definition_group`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add policy_definition)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `policy_definition` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `policy_definition_group_id` int(11) NOT NULL,
				  `parent_policy_definition_id` INT NULL DEFAULT NULL,
				  `name` text NOT NULL,
				  `display_name` mediumtext NOT NULL,
				  `description` longtext NOT NULL,
				  `class` tinyint(4) NOT NULL,
				  `options` text NOT NULL,
				  `manifestation_linux` text DEFAULT NULL,
				  `manifestation_macos` text DEFAULT NULL,
				  `manifestation_windows` text DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_policy_definition_1` (`policy_definition_group_id`),
				  CONSTRAINT `fk_policy_definition_1` FOREIGN KEY (`policy_definition_group_id`) REFERENCES `policy_definition_group` (`id`),
				  CONSTRAINT `fk_policy_definition_2` FOREIGN KEY (`parent_policy_definition_id`) REFERENCES `policy_definition`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add policy_object)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `policy_object` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `name` text NOT NULL,
				  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `created_by_system_user_id` INT NULL,
				  `updated` DATETIME NULL DEFAULT NULL,
				  `updated_by_system_user_id` INT NULL,
				  PRIMARY KEY (`id`),
				  CONSTRAINT `fk_policy_object_1` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
				  CONSTRAINT `fk_policy_object_2` FOREIGN KEY (`updated_by_system_user_id`) REFERENCES `system_user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add policy_object_item)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `policy_object_item` (
				  `policy_object_id` int(11) NOT NULL,
				  `policy_definition_id` int(11) NOT NULL,
				  `class` tinyint(4) NOT NULL,
				  `value` text NOT NULL,
				  `description` text NOT NULL,
				  PRIMARY KEY (`policy_object_id`, `policy_definition_id`, `class`),
				  KEY `fk_policy_object_item_1` (`policy_definition_id`),
				  CONSTRAINT `fk_policy_object_item_1` FOREIGN KEY (`policy_definition_id`) REFERENCES `policy_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				  CONSTRAINT `fk_policy_object_item_2` FOREIGN KEY (`policy_object_id`) REFERENCES `policy_object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add policy_translation)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `policy_translation` (
				  `language` varchar(5) NOT NULL,
				  `name` varchar(120) NOT NULL,
				  `translation` text NOT NULL,
				  PRIMARY KEY (`language`,`name`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add computer_group_policy_object)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `computer_group_policy_object` (
				  `id` INT NOT NULL AUTO_INCREMENT,
				  `computer_group_id` int(11) NULL,
				  `policy_object_id` int(11) NOT NULL,
				  `sequence` INT NOT NULL DEFAULT '0',
				  PRIMARY KEY (`id`),
				  KEY `fk_computer_group_policy_object_1` (`computer_group_id`),
				  KEY `fk_computer_group_policy_object_2` (`policy_object_id`),
				  CONSTRAINT `fk_computer_group_policy_object_1` FOREIGN KEY (`computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				  CONSTRAINT `fk_computer_group_policy_object_2` FOREIGN KEY (`policy_object_id`) REFERENCES `policy_object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add domain_user_group)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `domain_user_group` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `parent_domain_user_group_id` int(11) NOT NULL,
				  `name` text NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add domain_user_group_policy_object)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `domain_user_group_policy_object` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `domain_user_group_id` int(11) NULL,
				  `policy_object_id` int(11) NOT NULL,
				  `sequence` int(11) NOT NULL DEFAULT 0,
				  PRIMARY KEY (`id`),
				  KEY `fk_domain_user_group_policy_object_1` (`domain_user_group_id`),
				  KEY `fk_domain_user_group_policy_object_2` (`policy_object_id`),
				  CONSTRAINT `fk_domain_user_group_policy_object_1` FOREIGN KEY (`domain_user_group_id`) REFERENCES `domain_user_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				  CONSTRAINT `fk_domain_user_group_policy_object_2` FOREIGN KEY (`policy_object_id`) REFERENCES `policy_object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (add domain_user_group_member)'."\n";
			$this->stmt = $this->dbh->prepare(
				"CREATE TABLE `domain_user_group_member` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `domain_user_id` int(11) NOT NULL,
				  `domain_user_group_id` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_domain_user_group_member_1` (`domain_user_id`),
				  KEY `fk_domain_user_group_member_2` (`domain_user_group_id`),
				  CONSTRAINT `fk_domain_user_group_member_1` FOREIGN KEY (`domain_user_id`) REFERENCES `domain_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				  CONSTRAINT `fk_domain_user_group_member_2` FOREIGN KEY (`domain_user_group_id`) REFERENCES `domain_user_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.1.12... (granting policy_object permission to superadmin)'."\n";
			$this->stmt = $this->dbh->prepare(
				'UPDATE system_user_role SET permissions = JSON_SET(permissions, "$.Models\\\\\\\\PolicyObject", JSON_OBJECT("*", JSON_OBJECT("read",true,"write",true,"delete",true,"deploy",true), "create", true))
				WHERE id = 1');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.1.13 ***/
		if(strtolower($this->getTableColumnInfo('mobile_device_command', 'message')['DATA_TYPE']) != 'mediumtext') {
			if($this->debug) echo 'Upgrading to 1.1.13... (modify message column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `mobile_device_command` CHANGE `message` `message` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		return $upgraded;
	}

}
