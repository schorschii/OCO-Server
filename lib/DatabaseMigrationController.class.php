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
				'UPDATE system_user_role SET permissions = JSON_SET(permissions, "$.Models\\\\\\\\PolicyObject", JSON_OBJECT("*", JSON_OBJECT("read",true,"write",true,"delete",true,"deploy",true), "create",true))
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
		if(!$this->getTableColumnInfo('package', 'line_endings')) {
			if($this->debug) echo 'Upgrading to 1.1.13... (add line_endings column)'."\n";
			$this->stmt = $this->dbh->prepare(
				"ALTER TABLE `package` ADD `line_endings` TEXT NULL DEFAULT NULL AFTER `uninstall_procedure_post_action`");
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.2.0 ***/
		$this->stmt = $this->dbh->prepare("SELECT * FROM system_user_role WHERE id = 1 AND permissions NOT LIKE '%DomainUserGroup%'");
		$this->stmt->execute();
		foreach($this->stmt->fetchAll() as $row) {
			if($this->debug) echo 'Upgrading to 1.2.0... (granting domain_user_group permission to superadmin)'."\n";
			$this->stmt = $this->dbh->prepare(
				'UPDATE system_user_role SET permissions = JSON_SET(permissions, "$.Models\\\\\\\\DomainUserGroup", JSON_OBJECT("*", JSON_OBJECT("read",true,"write",true,"create",true,"delete",true), "create",true))
				WHERE id = 1');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.2.0... (adding domain_user_group fk)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `domain_user_group` ADD CONSTRAINT `fk_domain_user_group_1` FOREIGN KEY (`parent_domain_user_group_id`) REFERENCES `domain_user_group`(`id`) ON DELETE NO ACTION ON UPDATE NO ACTION');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.2.0... (allow domain_user_group parent NULL)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `domain_user_group` CHANGE `parent_domain_user_group_id` `parent_domain_user_group_id` INT(11) NULL DEFAULT NULL');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		/*** 1.2.1 ***/
		if(!$this->getTableColumnInfo('mobile_device', 'parameters')) {
			if($this->debug) echo 'Upgrading to 1.2.1... (column mobile_device parameters)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `mobile_device` ADD `parameters` text NULL DEFAULT NULL AFTER `policy`');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}
		if(!$this->getTableColumnInfo('mobile_device_user', 'short_name')) {
			if($this->debug) echo 'Upgrading to 1.2.1... (table mobile_device_user)'."\n";
			$this->stmt = $this->dbh->prepare(
				'CREATE TABLE `mobile_device_user` (
				  `mobile_device_id` int(11) NOT NULL,
				  `short_name` varchar(100) NOT NULL,
				  `long_name` text NOT NULL,
				  `user_id` text NOT NULL,
				  `push_token` blob NOT NULL,
				  `push_magic` text NOT NULL,
				  PRIMARY KEY (`mobile_device_id`,`short_name`),
				  CONSTRAINT `fk_mobile_device_user_1` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}
		if($this->getTableColumnInfo('mobile_device', 'serial')['COLUMN_KEY'] === 'UNI') {
			if($this->debug) echo 'Upgrading to 1.2.1... (remove serial_number unique key)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `mobile_device` DROP INDEX `serial_number`;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}
		if(!$this->getTableColumnInfo('profile', 'declaration_type')) {
			if($this->debug) echo 'Upgrading to 1.2.1... (add declaration_type column)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `profile` ADD `declaration_type` TEXT NULL DEFAULT NULL AFTER `name`;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}
		if($this->getTableColumnInfo('profile', 'last_update')) {
			if($this->debug) echo 'Upgrading to 1.2.1... (rename last_update column)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `profile` CHANGE `last_update` `updated` TIMESTAMP NULL DEFAULT NULL;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.2.1... (add updated_by_system_user_id column)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `profile` ADD `updated_by_system_user_id` int(11) DEFAULT NULL AFTER `updated`;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.2.1... (add fk_profile_2 constraint)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `profile` ADD CONSTRAINT `fk_profile_2` FOREIGN KEY (`updated_by_system_user_id`) REFERENCES `system_user`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}
		if($this->getTableColumnInfo('package', 'last_update')) {
			if($this->debug) echo 'Upgrading to 1.2.1... (rename last_update column)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `package` CHANGE `last_update` `updated` TIMESTAMP NULL DEFAULT NULL;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			if($this->debug) echo 'Upgrading to 1.2.1... (rename last_update_by_system_user_id column)'."\n";
			$this->stmt = $this->dbh->prepare(
				'ALTER TABLE `package` CHANGE `last_update_by_system_user_id` `updated_by_system_user_id` INT(11) NULL DEFAULT NULL;');
			if(!$this->stmt->execute()) throw new Exception('SQL error');

			$upgraded = true;
		}

		return $upgraded;
	}

}
