<?php

class DatabaseController {

	/*
		Class DatabaseController
		Database Abstraction Layer

		Handles direct database access.

		Function naming:
		- prefix oriented on SQL command: select, insert, update, insertOrUpdate, delete, search (special for search operations)
		- if it returns an array, the word "All" is inserted
		- entity name singular (e.g. "Computer")
		- "By<Attribute>" suffix if objects are filtered by attributes other than the own object id (e.g. "ByHostname")
	*/

	protected $dbh;
	private $stmt;

	public $settings;

	function __construct() {
		try {
			$this->dbh = new PDO(
				DB_TYPE.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';',
				DB_USER, DB_PASS,
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4')
			);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->settings = new SettingsController($this);
		} catch(Exception $e) {
			error_log($e->getMessage());
			throw new Exception('Failed to establish database connection to ›'.DB_HOST.'‹. Gentle panic.');
		}
	}

	private static function compileSqlInValues($array) {
		if(empty($array)) $array = [-1];
		$in_placeholders = ''; $in_params = []; $i = 0;
		foreach($array as $item) {
			$key = ':id'.$i++;
			$in_placeholders .= ($in_placeholders ? ',' : '').$key; // :id0,:id1,:id2
			$in_params[$key] = $item; // collecting values into a key-value array
		}
		return [$in_placeholders, $in_params];
	}

	// Database Handle Access for Extensions
	public function getDbHandle() {
		return $this->dbh;
	}
	public function getLastStatement() {
		return $this->stmt;
	}

	// Special Queries
	public function getServerVersion() {
		return $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
	}
	public function existsSchema() {
		$this->stmt = $this->dbh->prepare('SHOW TABLES LIKE "computer"');
		$this->stmt->execute();
		if($this->stmt->rowCount() != 1) return false;
		$this->stmt = $this->dbh->prepare('SHOW TABLES LIKE "system_user"');
		$this->stmt->execute();
		return ($this->stmt->rowCount() == 1);
	}
	public function getStats() {
		$this->stmt = $this->dbh->prepare(
			'SELECT
			(SELECT count(id) FROM domain_user) AS "domain_users",
			(SELECT count(id) FROM computer) AS "computers",
			(SELECT count(id) FROM package) AS "packages",
			(SELECT (SELECT count(id) FROM job_container_job jcj)+(SELECT count(id) FROM deployment_rule_job)) AS "jobs",
			(SELECT count(id) FROM report) AS "reports"
			FROM DUAL'
		);
		$this->stmt->execute();
		foreach($this->stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			return $row;
		}
	}

	// Breadcrumb Generation
	public function getComputerGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->selectComputerGroup($currentGroupId);
			$groupStrings[] = $currentGroup->name;
			if($currentGroup->parent_computer_group_id === null) {
				break;
			} else {
				$currentGroupId = $currentGroup->parent_computer_group_id;
			}
		}
		$groupStrings = array_reverse($groupStrings);
		return implode(' » ', $groupStrings);
	}
	public function getMobileDeviceGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->selectMobileDeviceGroup($currentGroupId);
			$groupStrings[] = $currentGroup->name;
			if($currentGroup->parent_mobile_device_group_id === null) {
				break;
			} else {
				$currentGroupId = $currentGroup->parent_mobile_device_group_id;
			}
		}
		$groupStrings = array_reverse($groupStrings);
		return implode(' » ', $groupStrings);
	}
	public function getPackageGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->selectPackageGroup($currentGroupId);
			$groupStrings[] = $currentGroup->name;
			if($currentGroup->parent_package_group_id === null) {
				break;
			} else {
				$currentGroupId = $currentGroup->parent_package_group_id;
			}
		}
		$groupStrings = array_reverse($groupStrings);
		return implode(' » ', $groupStrings);
	}
	public function getReportGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->selectReportGroup($currentGroupId);
			$groupStrings[] = $currentGroup->name;
			if($currentGroup->parent_report_group_id === null) {
				break;
			} else {
				$currentGroupId = $currentGroup->parent_report_group_id;
			}
		}
		$groupStrings = array_reverse($groupStrings);
		return implode(' » ', $groupStrings);
	}

	// Mobile Device Operations
	public function searchAllMobileDevice($search, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM mobile_device WHERE device_name LIKE :search OR serial LIKE :search ORDER BY device_name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':search' => '%'.$search.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice');
	}
	public function searchAllMobileDeviceGroup($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM mobile_device_group WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceGroup');
	}
	public function selectAllMobileDevice() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device');
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice');
	}
	public function selectMobileDevice($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice') as $row) {
			return $row;
		}
	}
	public function selectAllMobileDeviceByMobileDeviceGroupId($mobile_device_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT md.* FROM mobile_device_group_member mdgm
			INNER JOIN mobile_device md ON md.id = mdgm.mobile_device_id
			WHERE mdgm.mobile_device_group_id = :mobile_device_group_id'
		);
		$this->stmt->execute([':mobile_device_group_id' => $mobile_device_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice');
	}
	public function selectAllMobileDeviceByIdAndMobileDeviceGroupId($mobile_device_id, $mobile_device_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT md.* FROM mobile_device_group_member mdgm
			INNER JOIN mobile_device md ON md.id = mdgm.mobile_device_id
			WHERE mdgm.mobile_device_id = :mobile_device_id AND mdgm.mobile_device_group_id = :mobile_device_group_id'
		);
		$this->stmt->execute([':mobile_device_id' => $mobile_device_id, ':mobile_device_group_id' => $mobile_device_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice');
	}
	public function selectMobileDeviceBySerialNumber($serial) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device WHERE serial = :serial');
		$this->stmt->execute([':serial' => $serial]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice') as $row) {
			return $row;
		}
	}
	public function selectMobileDeviceByUdid($udid) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device WHERE udid = :udid');
		$this->stmt->execute([':udid' => $udid]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDevice') as $row) {
			return $row;
		}
	}
	public function selectAllMobileDeviceProfileUuidByMobileDeviceId($mobile_device_id) {
		$uuids = [];
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_profile WHERE mobile_device_id = :mobile_device_id');
		$this->stmt->execute([':mobile_device_id' => $mobile_device_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceProfile') as $row) {
			$uuids[$row->uuid] = $row;
		}
		return $uuids;
	}
	public function selectAllManagedAppByMobileDeviceId($mobile_device_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT mdgma.*, ma.* FROM managed_app ma
			INNER JOIN mobile_device_group_managed_app mdgma ON mdgma.managed_app_id = ma.id
			INNER JOIN mobile_device_group_member mdgm ON mdgm.mobile_device_group_id = mdgma.mobile_device_group_id
			WHERE mdgm.mobile_device_id = :mobile_device_id'
		);
		$this->stmt->execute([':mobile_device_id' => $mobile_device_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceGroupManagedApp');
	}
	public function insertMobileDevice($udid, $device_name, $serial, $vendor_description, $model, $os, $device_family, $color, $profile_uuid, $push_token, $push_magic, $push_sent, $unlock_token, $info, $notes, $force_update) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO mobile_device (udid, device_name, serial, vendor_description, model, os, device_family, color, profile_uuid, push_token, push_magic, push_sent, unlock_token, info, notes, force_update)
			VALUES (:udid, :device_name, :serial, :vendor_description, :model, :os, :device_family, :color, :profile_uuid, :push_token, :push_magic, :push_sent, :unlock_token, :info, :notes, :force_update)'
		);
		return $this->stmt->execute([
			':udid' => $udid,
			':device_name' => $device_name,
			':serial' => $serial,
			':vendor_description' => $vendor_description,
			':model' => $model,
			':os' => $os,
			':device_family' => $device_family,
			':color' => $color,
			':profile_uuid' => $profile_uuid,
			':push_token' => $push_token,
			':push_magic' => $push_magic,
			':push_sent' => $push_sent,
			':unlock_token' => $unlock_token,
			':info' => $info,
			':notes' => $notes,
			':force_update' => $force_update,
		]);
	}
	public function updateMobileDevice($id, $udid, $device_name, $serial, $vendor_description, $model, $os, $device_family, $color, $profile_uuid, $push_token, $push_magic, $push_sent, $unlock_token, $info, $notes, $force_update, $update_last_update=true) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE mobile_device SET udid=:udid, device_name=:device_name, serial=:serial, vendor_description=:vendor_description, model=:model, os=:os, device_family=:device_family, color=:color, profile_uuid=:profile_uuid, push_token=:push_token, push_magic=:push_magic, push_sent=:push_sent, unlock_token=:unlock_token, info=:info, notes=:notes, force_update=:force_update'
			.($update_last_update ? ', last_update=CURRENT_TIMESTAMP' : '')
			.' WHERE id=:id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':udid' => $udid,
			':device_name' => $device_name,
			':serial' => $serial,
			':vendor_description' => $vendor_description,
			':model' => $model,
			':os' => $os,
			':device_family' => $device_family,
			':color' => $color,
			':profile_uuid' => $profile_uuid,
			':push_token' => $push_token,
			':push_magic' => $push_magic,
			':push_sent' => $push_sent,
			':unlock_token' => $unlock_token,
			':info' => $info,
			':notes' => $notes,
			':force_update' => $force_update,
		]);
	}
	public function selectAllMobileDeviceAppIdentifierByMobileDeviceId($mobile_device_id) {
		$identifier = [];
		$this->stmt = $this->dbh->prepare(
			'SELECT a.* FROM app a
			INNER JOIN mobile_device_app mda ON mda.app_id = a.id
			WHERE mobile_device_id = :mobile_device_id'
		);
		$this->stmt->execute([':mobile_device_id' => $mobile_device_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\App') as $row) {
			$identifier[$row->identifier] = $row;
		}
		return $identifier;
	}
	public function updateMobileDeviceApps($id, $apps) {
		// update apps
		$aids = [];
		foreach($apps as $a) {
			if(empty($a['identifier']) || empty($a['name'])) continue;
			$sid = intval($this->insertOrUpdateApp($a['identifier'], $a['name'], $a['display_version'], $a['version']));
			$aids[] = $sid;
			if(!$this->insertOrUpdateMobileDeviceApp($id, $sid)) return false;
		}
		// remove apps which can not be found
		list($in_placeholders, $in_params) = self::compileSqlInValues($aids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device_app WHERE mobile_device_id = :mobile_device_id AND app_id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':mobile_device_id' => $id], $in_params))) return false;
	}
	private function insertOrUpdateApp($identifier, $name, $display_version, $version) {
		// columns are limited by MySQL index -> cut everything after 350 chars
		$identifier = substr($identifier, 0, 350);
		$name = substr($name, 0, 350);
		$display_version = substr($display_version, 0, 350);
		$version = substr($version, 0, 350);

		$this->stmt = $this->dbh->prepare(
			'SELECT id FROM app WHERE identifier = :identifier AND name = :name AND display_version = :display_version AND version = :version LIMIT 1'
		);
		if(!$this->stmt->execute([':identifier' => $identifier, ':name' => $name, ':display_version' => $display_version, ':version' => $version])) return false;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\App') as $row) {
			return $row->id;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO app (identifier, name, display_version, version) VALUES (:identifier, :name, :display_version, :version)'
		);
		if(!$this->stmt->execute([':identifier' => $identifier, ':name' => $name, ':display_version' => $display_version, ':version' => $version])) return false;
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateMobileDeviceApp($mobile_device_id, $app_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT id FROM mobile_device_app WHERE mobile_device_id = :mobile_device_id AND app_id = :app_id LIMIT 1'
		);
		if(!$this->stmt->execute([':mobile_device_id' => $mobile_device_id, 'app_id' => $app_id])) return false;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceApp') as $row) {
			return $row->id;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO mobile_device_app (mobile_device_id, app_id) VALUES (:mobile_device_id, :app_id)'
		);
		if(!$this->stmt->execute([':mobile_device_id' => $mobile_device_id, ':app_id' => $app_id])) return false;
		return $this->dbh->lastInsertId();
	}
	public function updateMobileDeviceProfiles($mobile_device_id, $profiles) {
		$uuids = [];
		foreach($profiles as $profile) {
			$uuids[] = $profile['uuid'];
			$this->stmt = $this->dbh->prepare(
				'SELECT id FROM mobile_device_profile WHERE mobile_device_id = :mobile_device_id AND uuid = :uuid LIMIT 1'
			);
			if(!$this->stmt->execute([':mobile_device_id' => $mobile_device_id, 'uuid' => $profile['uuid']])) return false;
			foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceProfile') as $row) {
				continue 2;
			}

			$this->stmt = $this->dbh->prepare(
				'INSERT INTO mobile_device_profile (mobile_device_id, uuid, identifier, display_name, version, content) VALUES (:mobile_device_id, :uuid, :identifier, :display_name, :version, :content)'
			);
			if(!$this->stmt->execute([
				':mobile_device_id' => $mobile_device_id,
				':uuid' => $profile['uuid'],
				':identifier' => $profile['identifier'],
				':display_name' => $profile['display_name'],
				':version' => $profile['version'],
				':content' => $profile['content'],
			])) return false;
			return $this->dbh->lastInsertId();
		}

		// remove profiles from database which can not be found
		list($in_placeholders, $in_params) = self::compileSqlInValues($uuids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device_profile WHERE mobile_device_id = :mobile_device_id AND uuid NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':mobile_device_id' => $mobile_device_id], $in_params))) return false;
	}
	public function deleteMobileDevice($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function deleteAllMobileDeviceActivationProfile() {
		$this->stmt = $this->dbh->prepare('UPDATE mobile_device SET profile_uuid = NULL WHERE 1 = 1');
		return $this->stmt->execute();
	}

	public function selectAllManagedApp() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM managed_app');
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ManagedApp');
	}
	public function selectManagedApp($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM managed_app WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ManagedApp') as $row) {
			return $row;
		}
	}
	public function selectAllManagedAppByMobileDeviceGroupId($mobile_device_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM managed_app ma
			INNER JOIN mobile_device_group_managed_app mdgma ON mdgma.managed_app_id = ma.id
			WHERE mdgma.mobile_device_group_id = :mobile_device_group_id'
		);
		$this->stmt->execute([':mobile_device_group_id' => $mobile_device_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ManagedApp');
	}
	public function insertOrUpdateManagedApp($identifier, $store_id, $name, $vpp_amount) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM managed_app WHERE identifier = :identifier AND store_id = :store_id'
		);
		if(!$this->stmt->execute([':identifier' => $identifier, ':store_id' => $store_id])) return false;
		if($this->stmt->rowCount() > 0) {
			$this->stmt = $this->dbh->prepare(
				'UPDATE managed_app SET name = :name, vpp_amount = :vpp_amount WHERE identifier = :identifier AND store_id = :store_id'
			);
			return $this->stmt->execute([':identifier' => $identifier, ':store_id' => $store_id, ':name' => $name, ':vpp_amount' => $vpp_amount]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO managed_app (identifier, store_id, name, vpp_amount) VALUES (:identifier, :store_id, :name, :vpp_amount)'
			);
			if(!$this->stmt->execute([':identifier' => $identifier, ':store_id' => $store_id, ':name' => $name, ':vpp_amount' => $vpp_amount])) return false;
			return $this->dbh->lastInsertId();
		}
	}
	public function insertMobileDeviceGroupManagedApp($mobile_device_group_id, $managed_app_id, $removable, $disable_cloud_backup, $remove_on_mdm_remove, $config) {
		$this->deleteMobileDeviceGroupManagedApp($mobile_device_group_id, $managed_app_id);
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO mobile_device_group_managed_app (mobile_device_group_id, managed_app_id, removable, disable_cloud_backup, remove_on_mdm_remove, config)
			VALUES (:mobile_device_group_id, :managed_app_id, :removable, :disable_cloud_backup, :remove_on_mdm_remove, :config)'
		);
		$this->stmt->execute([
			':mobile_device_group_id' => $mobile_device_group_id,
			':managed_app_id' => $managed_app_id,
			':removable' => $removable,
			':disable_cloud_backup' => $disable_cloud_backup,
			':remove_on_mdm_remove' => $remove_on_mdm_remove,
			':config' => $config,
		]);
		return $this->dbh->lastInsertId();
	}
	public function deleteMobileDeviceGroupManagedApp($mobile_device_group_id, $managed_app_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device_group_managed_app WHERE mobile_device_group_id = :mobile_device_group_id AND managed_app_id = :managed_app_id'
		);
		$this->stmt->execute([':mobile_device_group_id' => $mobile_device_group_id, ':managed_app_id' => $managed_app_id]);
		if($this->stmt->rowCount() != 1) return false;
		return true;
	}

	public function selectAllProfile() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM profile');
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Profile');
	}
	public function selectProfile($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM profile WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Profile') as $row) {
			return $row;
		}
	}
	public function selectAllProfileByMobileDeviceId($mobile_device_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM profile p
			INNER JOIN mobile_device_group_profile mdgp ON mdgp.profile_id = p.id
			INNER JOIN mobile_device_group_member mdgm ON mdgm.mobile_device_group_id = mdgp.mobile_device_group_id
			WHERE mdgm.mobile_device_id = :mobile_device_id'
		);
		$this->stmt->execute([':mobile_device_id' => $mobile_device_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Profile');
	}
	public function selectAllProfileByMobileDeviceGroupId($mobile_device_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM profile p
			INNER JOIN mobile_device_group_profile mdgp ON mdgp.profile_id = p.id
			WHERE mdgp.mobile_device_group_id = :mobile_device_group_id'
		);
		$this->stmt->execute([':mobile_device_group_id' => $mobile_device_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Profile');
	}
	public function insertProfile($name, $payload, $notes, $system_user_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO profile (name, payload, notes, created_by_system_user_id) VALUES (:name, :payload, :notes, :created_by_system_user_id)'
		);
		$this->stmt->execute([':name' => $name, ':payload' => $payload, ':notes' => $notes, ':created_by_system_user_id' => $system_user_id]);
		return $this->dbh->lastInsertId();
	}
	public function insertMobileDeviceGroupProfile($mobile_device_group_id, $profile_id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_group_profile WHERE mobile_device_group_id = :mobile_device_group_id AND profile_id = :profile_id');
		$this->stmt->execute(['mobile_device_group_id'=>$mobile_device_group_id, 'profile_id'=>$profile_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS) as $row) {
			return $row->id;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO mobile_device_group_profile (mobile_device_group_id, profile_id) VALUES (:mobile_device_group_id, :profile_id)'
		);
		$this->stmt->execute([':mobile_device_group_id' => $mobile_device_group_id, ':profile_id' => $profile_id]);
		return $this->dbh->lastInsertId();
	}
	public function deleteMobileDeviceGroupProfile($mobile_device_group_id, $profile_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device_group_profile WHERE mobile_device_group_id = :mobile_device_group_id AND profile_id = :profile_id'
		);
		$this->stmt->execute([':mobile_device_group_id' => $mobile_device_group_id, ':profile_id' => $profile_id]);
		if($this->stmt->rowCount() != 1) return false;
		return true;
	}
	public function deleteProfile($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM profile WHERE id = :id'
		);
		if(!$this->stmt->execute([':id' => $id])) return false;
		if($this->stmt->rowCount() != 1) return false;
		return true;
	}

	public function selectAllMobileDeviceGroup() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_group');
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceGroup');
	}
	public function selectAllMobileDeviceGroupByParentMobileDeviceGroupId($parent_id) {
		if($parent_id === null) {
			$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_group WHERE parent_mobile_device_group_id IS NULL');
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_group WHERE parent_mobile_device_group_id = :parent_id');
			$this->stmt->execute([':parent_id' => $parent_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceGroup');
	}
	public function selectAllMobileDeviceGroupByMobileDeviceId($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT mdg.* FROM mobile_device_group_member mdgm
			INNER JOIN mobile_device_group mdg ON mdg.id = mdgm.mobile_device_group_id
			WHERE mdgm.mobile_device_id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceGroup');
	}
	public function selectMobileDeviceGroup($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_group WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceGroup') as $row) {
			return $row;
		}
	}
	public function insertMobileDeviceGroup($name, $parent_id=null) {
		if(empty($parent_id) || intval($parent_id) < 0) {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO mobile_device_group (name) VALUES (:name)'
			);
			$this->stmt->execute([':name' => $name]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO mobile_device_group (name, parent_mobile_device_group_id) VALUES (:name, :parent_mobile_device_group_id)'
			);
			$this->stmt->execute([':name' => $name, ':parent_mobile_device_group_id' => $parent_id]);
		}
		return $this->dbh->lastInsertId();
	}
	public function updateMobileDeviceGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE mobile_device_group SET name = :name WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id, ':name' => $name]);
		return $this->dbh->lastInsertId();
	}
	public function deleteMobileDeviceGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function insertMobileDeviceGroupMember($mobile_device_id, $mobile_device_group_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO mobile_device_group_member (mobile_device_id, mobile_device_group_id) VALUES (:mobile_device_id, :mobile_device_group_id)'
		);
		if(!$this->stmt->execute([':mobile_device_id' => $mobile_device_id, ':mobile_device_group_id' => $mobile_device_group_id])) return false;
		$insertId = $this->dbh->lastInsertId();
		return $insertId;
	}
	public function deleteMobileDeviceGroupMember($mobile_device_id, $mobile_device_group_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM mobile_device_group_member WHERE mobile_device_id = :mobile_device_id AND mobile_device_group_id = :mobile_device_group_id'
		);
		if(!$this->stmt->execute([':mobile_device_id' => $mobile_device_id, ':mobile_device_group_id' => $mobile_device_group_id])) return false;
		if($this->stmt->rowCount() != 1) return false;
		return true;
	}

	public function selectMobileDeviceCommand($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM mobile_device_command WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceCommand') as $row) {
			return $row;
		}
	}
	public function selectAllMobileDeviceCommand() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM mobile_device_command'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceCommand');
	}
	public function selectAllMobileDeviceCommandByMobileDevice($mobile_device_id, $order_asc=true) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM mobile_device_command WHERE mobile_device_id = :mobile_device_id '
			.($order_asc ? 'ORDER BY created ASC' : 'ORDER BY created DESC')
		);
		$this->stmt->execute([':mobile_device_id' => $mobile_device_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceCommand');
	}
	public function insertMobileDeviceCommand($mobile_device_id, $name, $parameter) {
		// do not create command twice if the same command already exists pending!
		$this->stmt = $this->dbh->prepare(
			'SELECT id FROM mobile_device_command WHERE mobile_device_id = :mobile_device_id AND name = :name AND parameter = :parameter AND state = '.Models\MobileDeviceCommand::STATE_QUEUED.' LIMIT 1'
		);
		if(!$this->stmt->execute([':mobile_device_id' => $mobile_device_id, ':name' => $name, ':parameter' => $parameter])) return false;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\MobileDeviceCommand') as $row) {
			return null;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO mobile_device_command (mobile_device_id, name, parameter)
			VALUES (:mobile_device_id, :name, :parameter)'
		);
		return $this->stmt->execute([
			':mobile_device_id' => $mobile_device_id,
			':name' => $name,
			':parameter' => $parameter,
		]);
	}
	public function updateMobileDeviceCommand($id, $mobile_device_id, $name, $parameter, $state, $message, $finished) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE mobile_device_command SET mobile_device_id=:mobile_device_id, name=:name, parameter=:parameter, state=:state, message=:message, finished=:finished WHERE id=:id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':mobile_device_id' => $mobile_device_id,
			':name' => $name,
			':parameter' => $parameter,
			':state' => $state,
			':message' => $message,
			':finished' => $finished,
		]);
	}
	public function deleteMobileDeviceCommand($id) {
		$this->stmt = $this->dbh->prepare('DELETE FROM mobile_device_command WHERE id=:id');
		return $this->stmt->execute([':id' => $id]);
	}

	// Computer Operations
	public function searchAllComputer($hostname, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE hostname LIKE :hostname ORDER BY hostname ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':hostname' => '%'.$hostname.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function searchAllComputerGroup($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_group WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerGroup');
	}
	public function selectAllComputer() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer ORDER BY hostname ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function selectComputerByHostname($hostname) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE hostname = :hostname'
		);
		$this->stmt->execute([':hostname' => $hostname]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer') as $row) {
			return $row;
		}
	}
	public function selectComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer') as $row) {
			return $row;
		}
	}
	public function selectAllComputerNetworkByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_network WHERE computer_id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerNetwork');
	}
	public function selectAllComputerScreenByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_screen WHERE computer_id = :computer_id ORDER BY active DESC'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerScreen');
	}
	public function selectAllComputerPrinterByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_printer WHERE computer_id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPrinter');
	}
	public function selectAllComputerPartitionByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_partition WHERE computer_id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPartition');
	}
	public function selectAllComputerSoftwareByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cs.id AS "id", s.id AS "software_id", s.name AS "software_name", s.version AS "software_version", s.description AS "software_description", cs.installed AS "installed"
			FROM computer_software cs
			INNER JOIN software s ON cs.software_id = s.id
			WHERE cs.computer_id = :computer_id ORDER BY s.name'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerSoftware');
	}
	public function selectAllComputerPackageByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cp.id AS "id", c.id AS "computer_id", c.hostname AS "computer_hostname", p.id AS "package_id", p.package_family_id AS "package_family_id", pf.name AS "package_family_name", p.version AS "package_version", cp.installed_procedure AS "installed_procedure", cp.installed_by_system_user_id AS "installed_by_system_user_id", su.username AS "installed_by_system_user_username", du.username AS "installed_by_domain_user_username", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN package p ON p.id = cp.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN computer c ON c.id = cp.computer_id
			LEFT JOIN system_user su ON su.id = cp.installed_by_system_user_id
			LEFT JOIN domain_user du ON du.id = cp.installed_by_domain_user_id
			WHERE cp.computer_id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPackage');
	}
	public function insertComputer($hostname, $agent_version, $networks, $notes, $agent_key, $server_key, $created_by_system_user_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer (hostname, agent_version, last_ping, last_update, os, os_version, os_license, os_locale, kernel_version, architecture, cpu, gpu, ram, serial, manufacturer, model, bios_version, remote_address, boot_type, secure_boot, domain, notes, agent_key, server_key, created_by_system_user_id)
			VALUES (:hostname, :agent_version, NULL, NULL, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", :notes, :agent_key, :server_key, :created_by_system_user_id)'
		);
		$this->stmt->execute([
			':hostname' => $hostname,
			':agent_version' => $agent_version,
			':notes' => $notes,
			':agent_key' => $agent_key,
			':server_key' => $server_key,
			':created_by_system_user_id' => $created_by_system_user_id,
		]);
		$computer_id = $this->dbh->lastInsertId();
		foreach($networks as $index => $network) {
			if(empty($network['addr'])) continue;
			$this->insertOrUpdateComputerNetwork(
				$computer_id,
				$index,
				$network['addr'],
				$network['netmask'] ?? '?',
				$network['broadcast'] ?? '?',
				$network['mac'] ?? '?',
				$network['interface'] ?? '?'
			);
		}
		return $computer_id;
	}
	public function updateComputer($id, $hostname, $notes) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET hostname = :hostname, notes = :notes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':hostname' => $hostname, ':notes' => $notes]);
	}
	public function updateComputerPing($id, $agent_version=null, $networks=null, $uptime=null) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET last_ping = CURRENT_TIMESTAMP WHERE id = :id'
		);
		if(!$this->stmt->execute([':id' => $id])) return false;
		if($agent_version !== null) {
			$this->stmt = $this->dbh->prepare(
				'UPDATE computer SET agent_version = :agent_version WHERE id = :id'
			);
			if(!$this->stmt->execute([':id' => $id, ':agent_version' => $agent_version])) return false;
		}
		if($networks !== null) {
			$nids = [];
			foreach($networks as $index => $network) {
				if(empty($network['addr'])) continue;
				$nids[] = $this->insertOrUpdateComputerNetwork(
					$id,
					$index,
					$network['addr'],
					$network['netmask'] ?? '?',
					$network['broadcast'] ?? '?',
					$network['mac'] ?? '?',
					$network['interface'] ?? '?'
				);
			}
			// remove networks which can not be found in agent output
			list($in_placeholders, $in_params) = self::compileSqlInValues($nids);
			$this->stmt = $this->dbh->prepare(
			    'DELETE FROM computer_network WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
			);
			if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;
		}
		if($uptime !== null) {
			$this->stmt = $this->dbh->prepare(
				'UPDATE computer SET uptime = :uptime WHERE id = :id'
			);
			if(!$this->stmt->execute([':id' => $id, ':uptime' => $uptime])) return false;
		}
		return true;
	}
	public function updateComputerForceUpdate($id, $force_update) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET force_update = :force_update WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':force_update' => intval($force_update)]);
	}
	public function updateComputerAgentKey($id, $agent_key) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET agent_key = :agent_key WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':agent_key' => $agent_key]);
	}
	public function updateComputerServerKey($id, $server_key) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET server_key = :server_key WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':server_key' => $server_key]);
	}
	public function updateComputerInventoryValues($id, $uid, $hostname, $os, $os_version, $os_license, $os_locale, $kernel_version, $architecture, $cpu, $gpu, $ram, $agent_version, $remote_address, $serial, $manufacturer, $model, $bios_version, $uptime, $boot_type, $secure_boot, $domain, $networks, $screens, $printers, $partitions, $software, $logins) {
		$this->dbh->beginTransaction();

		// update general info
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET uid = :uid, hostname = :hostname, os = :os, os_version = :os_version, os_license = :os_license, os_locale = :os_locale, kernel_version = :kernel_version, architecture = :architecture, cpu = :cpu, gpu = :gpu, ram = :ram, agent_version = :agent_version, remote_address = :remote_address, serial = :serial, manufacturer = :manufacturer, model = :model, bios_version = :bios_version, uptime = :uptime, boot_type = :boot_type, secure_boot = :secure_boot, domain = :domain, last_ping = CURRENT_TIMESTAMP, last_update = CURRENT_TIMESTAMP, force_update = 0 WHERE id = :id'
		);
		if(!$this->stmt->execute([
			':id' => $id,
			':uid' => $uid,
			':hostname' => $hostname,
			':os' => $os,
			':os_version' => $os_version,
			':os_license' => $os_license,
			':os_locale' => $os_locale,
			':kernel_version' => $kernel_version,
			':architecture' => $architecture,
			':cpu' => $cpu,
			':gpu' => $gpu,
			':ram' => $ram,
			':agent_version' => $agent_version,
			':remote_address' => $remote_address,
			':serial' => $serial,
			':manufacturer' => $manufacturer,
			':model' => $model,
			':bios_version' => $bios_version,
			':uptime' => $uptime,
			':boot_type' => $boot_type,
			':secure_boot' => $secure_boot,
			':domain' => $domain,
		])) return false;

		// update networks
		$nids = [];
		foreach($networks as $index => $network) {
			if(empty($network['addr'])) continue;
			$nid = $this->insertOrUpdateComputerNetwork(
				$id,
				$index,
				$network['addr'],
				$network['netmask'] ?? '?',
				$network['broadcast'] ?? '?',
				$network['mac'] ?? '?',
				$network['interface'] ?? '?'
			);
			$nids[] = $nid;
		}
		// remove networks which can not be found in agent output
		list($in_placeholders, $in_params) = self::compileSqlInValues($nids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_network WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;

		// update screens
		$sids = [];
		foreach($screens as $screen) {
			if(empty($screen['name'])) continue;
			$sid = $this->insertOrUpdateComputerScreen(
				$id,
				$screen['name'],
				$screen['manufacturer'] ?? '?',
				$screen['type'] ?? '?',
				$screen['resolution'] ?? '?',
				$screen['size'] ?? '?',
				$screen['manufactured'] ?? '?',
				$screen['serialno'] ?? '?'
			);
			$sids[] = $sid;
		}
		// remove screens which can not be found in agent output
		list($in_placeholders, $in_params) = self::compileSqlInValues($sids);
		$this->stmt = $this->dbh->prepare(
			$this->settings->get('computer-keep-inactive-screens')
			? 'UPDATE computer_screen SET active=0 WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
			: 'DELETE FROM computer_screen WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;

		// update printers
		$pids = [];
		foreach($printers as $printer) {
			if(empty($printer['name'])) continue;
			$pid = $this->insertOrUpdateComputerPrinter(
				$id,
				$printer['name'],
				$printer['driver'] ?? '?',
				$printer['paper'] ?? '?',
				$printer['dpi'] ?? '?',
				$printer['uri'] ?? '?',
				$printer['status'] ?? '?'
			);
			$pids[] = $pid;
		}
		// remove printers which can not be found in agent output
		list($in_placeholders, $in_params) = self::compileSqlInValues($pids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_printer WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;

		// update partitions
		$pids = [];
		foreach($partitions as $part) {
			if(empty($part['size'])) continue;
			$pid = $this->insertOrUpdateComputerPartition(
				$id,
				$part['device'] ?? '?',
				$part['mountpoint'] ?? '?',
				$part['filesystem'] ?? '?',
				intval($part['size']),
				intval($part['free'])
			);
			$pids[] = $pid;
		}
		// remove partitions which can not be found in agent output
		list($in_placeholders, $in_params) = self::compileSqlInValues($pids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_partition WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;

		// update software
		$sids = [];
		foreach($software as $s) {
			if(empty($s['name'])) continue;
			$sid = intval($this->insertOrUpdateSoftware($s['name'], $s['version'], $s['description']??''));
			$sids[] = $sid;
			if(!$this->insertOrUpdateComputerSoftware($id, $sid)) return false;
		}
		// remove software which can not be found in agent output
		list($in_placeholders, $in_params) = self::compileSqlInValues($sids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_software WHERE computer_id = :computer_id AND software_id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;

		// insert new domain user logins
		foreach($logins as $l) {
			if(empty($l['username'])) continue;
			$guid = empty($l['guid']) ? null : trim($l['guid'], '{}');
			$did = $this->insertOrUpdateDomainUser($guid, $l['username'], $l['display_name']??'');
			if(!$this->insertOrUpdateDomainUserLogon($id, $did, $l['console'], $l['timestamp'])) return false;
		}
		// old logins, which are not present in local client logs anymore, should NOT automatically be deleted
		// instead, old logins are cleaned up by the server's housekeeping process after a certain amount of time (defined in configuration)

		$this->dbh->commit();
		return true;
	}
	private function insertOrUpdateComputerPartition($computer_id, $device, $mountpoint, $filesystem, $size, $free) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_partition SET id = LAST_INSERT_ID(id), size = :size, free = :free
			WHERE computer_id = :computer_id AND device = :device AND mountpoint = :mountpoint AND filesystem = :filesystem LIMIT 1'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':device' => $device,
			':mountpoint' => $mountpoint,
			':filesystem' => $filesystem,
			':size' => $size,
			':free' => $free,
		])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_partition (computer_id, device, mountpoint, filesystem, size, free)
			VALUES (:computer_id, :device, :mountpoint, :filesystem, :size, :free)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':device' => $device,
			':mountpoint' => $mountpoint,
			':filesystem' => $filesystem,
			':size' => $size,
			':free' => $free,
		])) return false;
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerPrinter($computer_id, $name, $driver, $paper, $dpi, $uri, $status) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_printer SET id = LAST_INSERT_ID(id), status = :status
			WHERE computer_id = :computer_id AND name = :name AND driver = :driver AND paper = :paper AND dpi = :dpi AND uri = :uri LIMIT 1'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':name' => $name,
			':driver' => $driver,
			':paper' => $paper,
			':dpi' => $dpi,
			':uri' => $uri,
			':status' => $status,
		])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_printer (computer_id, name, driver, paper, dpi, uri, status)
			VALUES (:computer_id, :name, :driver, :paper, :dpi, :uri, :status)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':name' => $name,
			':driver' => $driver,
			':paper' => $paper,
			':dpi' => $dpi,
			':uri' => $uri,
			':status' => $status,
		])) return false;
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerScreen($computer_id, $name, $manufacturer, $type, $resolution, $size, $manufactured, $serialno) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_screen SET id = LAST_INSERT_ID(id), resolution = :resolution, active = 1
			WHERE computer_id = :computer_id AND name = :name AND manufacturer = :manufacturer AND type = :type AND size = :size AND manufactured = :manufactured AND serialno = :serialno LIMIT 1'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':name' => $name,
			':manufacturer' => $manufacturer,
			':type' => $type,
			':resolution' => $resolution,
			':size' => $size,
			':manufactured' => $manufactured,
			':serialno' => $serialno,
		])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_screen (computer_id, name, manufacturer, type, resolution, size, manufactured, serialno, active)
			VALUES (:computer_id, :name, :manufacturer, :type, :resolution, :size, :manufactured, :serialno, 1)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':name' => $name,
			':manufacturer' => $manufacturer,
			':type' => $type,
			':resolution' => $resolution,
			':size' => $size,
			':manufactured' => $manufactured,
			':serialno' => $serialno,
		])) return false;
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerNetwork($computer_id, $nic_number, $address, $netmask, $broadcast, $mac, $interface) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_network SET id = LAST_INSERT_ID(id), address = :address, netmask = :netmask, broadcast=:broadcast
			WHERE computer_id = :computer_id AND nic_number = :nic_number AND mac = :mac AND interface = :interface LIMIT 1'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':nic_number' => $nic_number,
			':address' => $address,
			':netmask' => $netmask,
			':broadcast' => $broadcast,
			':mac' => $mac,
			':interface' => $interface,
		])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_network (computer_id, nic_number, address, netmask, broadcast, mac, interface)
			VALUES (:computer_id, :nic_number, :address, :netmask, :broadcast, :mac, :interface)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':nic_number' => $nic_number,
			':address' => $address,
			':netmask' => $netmask,
			':broadcast' => $broadcast,
			':mac' => $mac,
			':interface' => $interface,
		])) return false;
		return $this->dbh->lastInsertId();
	}
	public function deleteComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer WHERE id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function selectComputerGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerGroup') as $row) {
			return $row;
		}
	}
	public function selectAllComputerGroupByParentComputerGroupId($parent_computer_group_id=null) {
		if($parent_computer_group_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_group WHERE parent_computer_group_id IS NULL ORDER BY name'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_group WHERE parent_computer_group_id = :parent_computer_group_id ORDER BY name'
			);
			$this->stmt->execute([':parent_computer_group_id' => $parent_computer_group_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerGroup');
	}
	public function selectAllComputerBySoftwareName($name) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", s.id AS "software_id", s.name AS "software_name", s.version AS "software_version"
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			INNER JOIN software s ON cs.software_id = s.id
			WHERE s.name = :name
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':name' => $name]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function selectAllComputerBySoftwareId($software_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", c.os AS "os", c.os_version AS "os_version", s.id AS "software_id", s.name AS "software_name", s.version AS "software_version"
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			INNER JOIN software s ON cs.software_id = s.id
			WHERE cs.software_id = :id
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':id' => $software_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function selectAllComputerByComputerGroupId($computer_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.* FROM computer c
			INNER JOIN computer_group_member cgm ON c.id = cgm.computer_id
			WHERE cgm.computer_group_id = :computer_group_id
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':computer_group_id' => $computer_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function selectAllComputerByIdAndComputerGroupId($computer_id, $computer_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.* FROM computer_group_member cgm
			INNER JOIN computer c ON c.id = cgm.computer_id
			WHERE cgm.computer_id = :computer_id AND cgm.computer_group_id = :computer_group_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id, ':computer_group_id' => $computer_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function selectAllComputerGroupByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cg.id AS "id", cg.name AS "name", cg.parent_computer_group_id AS "parent_computer_group_id"
			FROM computer_group_member cgm
			INNER JOIN computer_group cg ON cg.id = cgm.computer_group_id
			WHERE cgm.computer_id = :computer_id
			ORDER BY cg.name'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerGroup');
	}
	public function selectAllEventQueryRule() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM event_query_rule');
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\EventQueryRule');
	}
	public function selectEventQueryRule($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM event_query_rule WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\EventQueryRule') as $row) {
			return $row;
		}
	}
	public function insertEventQueryRule($log, $query) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO event_query_rule (log, query) VALUES (:log, :query)'
		);
		return $this->stmt->execute([
			':log' => $log,
			':query' => $query,
		]);
	}
	public function updateEventQueryRule($id, $log, $query) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE event_query_rule SET log = :log, query = :query WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':log' => $log,
			':query' => $query,
		]);
	}
	public function deleteEventQueryRule($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM event_query_rule WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function insertOrUpdateComputerService($computer_id, $status, $name, $metrics, $details) {
		$this->dbh->beginTransaction();
		// important: use SELECT FOR UPDATE in a TRANSACTION to prevent "Lost updates"
		// https://stackoverflow.com/questions/27865992/why-use-select-for-update-mysql
		// also, we have the "greatest-n-per-group" problem here
		// https://stackoverflow.com/questions/7745609/sql-select-only-rows-with-max-value-on-a-column
		$this->stmt = $this->dbh->prepare(
			'SELECT cs1.id FROM computer_service cs1
			JOIN (
				SELECT MAX(id) AS `id` FROM computer_service
				WHERE computer_id = :computer_id GROUP BY name
			) cs2 ON cs1.id = cs2.id
			WHERE name = :name AND status = :status AND metrics = :metrics AND details = :details
			FOR UPDATE'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':name' => $name,
			':status' => $status,
			':metrics' => $metrics,
			':details' => $details,
		])) return false;
		$newestServiceRecord = null;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerService') as $row) {
			$newestServiceRecord = $row->id;
		}
		// update timestamp of existing record
		if($newestServiceRecord) {
			$this->stmt = $this->dbh->prepare(
				'UPDATE computer_service SET updated = CURRENT_TIMESTAMP WHERE id = :id'
			);
			if(!$this->stmt->execute([':id' => $newestServiceRecord])) return false;
			if(!$this->stmt->rowCount()) {
				throw new Exception('Lost update: no row affected when updating previously found computer_service');
			}
		// insert new service record
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_service (computer_id, status, name, metrics, details)
				VALUES (:computer_id, :status, :name, :metrics, :details)'
			);
			if(!$this->stmt->execute([
				':computer_id' => $computer_id,
				':status' => $status,
				':name' => $name,
				':metrics' => $metrics,
				':details' => $details,
			])) return false;
		}
		$this->dbh->commit();
	}
	public function selectAllCurrentComputerServiceByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cs.*, (SELECT (COUNT(*) - 1) FROM computer_service cs3 WHERE cs3.computer_id = cs.computer_id AND cs3.name = cs.name) AS "history_count"
			FROM computer_service cs INNER JOIN computer c ON c.id = cs.computer_id WHERE cs.id IN (SELECT MAX(cs2.id) FROM computer_service cs2 GROUP BY cs2.computer_id, cs2.name) AND cs.computer_id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerService');
	}
	public function selectAllComputerServiceByComputerIdAndServiceName($computer_id, $service_name) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cs.name, cs.status, cs.timestamp, cs.updated, cs.details
			FROM computer_service cs WHERE computer_id = :computer_id AND cs.name = :service_name ORDER BY timestamp DESC'
		);
		$this->stmt->execute([':computer_id' => $computer_id, ':service_name' => $service_name]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerService');
	}
	public function deleteComputerServiceHistoryOlderThan($seconds) {
		// this deletes old entries but always keeps the last one of a computer service
		// important note #1: "FROM (SELECT * FROM computer_service)" workaround is necessary for MySQL (all versions) and MariaDB <10.3.2 not allowing to use the same table directly in DELETE subselects - "FROM computer_service" would only work in MariaDB >10.3.2
		// important note #2: "FOR UPDATE" is necessary in the subselect to lock the entire table, otherwise we will get "Serialization failure: 1213 Deadlock found" when an agent updates its service data while deletion is running
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_service WHERE updated < NOW() - INTERVAL '.intval($seconds).' SECOND
			AND id NOT IN (SELECT MAX(cs2.id) FROM (SELECT * FROM computer_service FOR UPDATE) cs2 GROUP BY cs2.computer_id, cs2.name)'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}
	public function selectLastComputerEventByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_event WHERE computer_id = :computer_id ORDER BY timestamp DESC LIMIT 1'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerEvent') as $row) {
			return $row;
		}
	}
	public function selectAllComputerEventByComputerId($computer_id, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_event WHERE computer_id = :computer_id ORDER BY timestamp DESC '.(empty($limit)?'':'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerEvent');
	}
	public function insertOrUpdateComputerEvent($computer_id, $log, $timestamp, $provider, $level, $event_id, $data) {
		$level = empty($level) ? -1 : $level;
		$event_id = empty($event_id) ? -1 : $event_id;

		$this->stmt = $this->dbh->prepare(
			'SELECT id FROM computer_event WHERE computer_id = :computer_id AND log = :log AND timestamp = :timestamp AND provider = :provider AND level = :level AND event_id = :event_id AND data = :data LIMIT 1'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':log' => $log,
			':timestamp' => $timestamp,
			':provider' => $provider,
			':level' => $level,
			':event_id' => $event_id,
			':data' => $data,
		])) return false;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerEvent') as $row) {
			return $row->id;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_event (computer_id, log, timestamp,  provider, level, event_id, data)
			VALUES (:computer_id, :log, :timestamp, :provider, :level, :event_id, :data)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':log' => $log,
			':timestamp' => $timestamp,
			':provider' => $provider,
			':level' => $level,
			':event_id' => $event_id,
			':data' => $data,
		])) return false;
		return $this->dbh->lastInsertId();
	}
	public function deleteComputerEventOlderThan($seconds) {
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_event WHERE timestamp < NOW() - INTERVAL '.intval($seconds).' SECOND'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}
	public function insertComputerGroup($name, $parent_id=null) {
		if(empty($parent_id) || intval($parent_id) < 0) {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_group (name) VALUES (:name)'
			);
			$this->stmt->execute([':name' => $name]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_group (name, parent_computer_group_id) VALUES (:name, :parent_computer_group_id)'
			);
			$this->stmt->execute([':name' => $name, ':parent_computer_group_id' => $parent_id]);
		}
		return $this->dbh->lastInsertId();
	}
	public function updateComputerGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_group SET name = :name WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id, ':name' => $name]);
		return $this->dbh->lastInsertId();
	}
	public function deleteComputerGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function insertComputerGroupMember($computer_id, $computer_group_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_group_member (computer_id, computer_group_id) VALUES (:computer_id, :computer_group_id)'
		);
		if(!$this->stmt->execute([':computer_id' => $computer_id, ':computer_group_id' => $computer_group_id])) return false;
		$insertId = $this->dbh->lastInsertId();

		// evaluate corresponding deployment rule(s)
		foreach($this->selectAllDeploymentRuleByComputerGroupId($computer_group_id) as $dr) {
			$this->evaluateDeploymentRule($dr->id);
		}
		return $insertId;
	}
	public function deleteComputerGroupMember($computer_id, $computer_group_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_group_member WHERE computer_id = :computer_id AND computer_group_id = :computer_group_id'
		);
		if(!$this->stmt->execute([':computer_id' => $computer_id, ':computer_group_id' => $computer_group_id])) return false;
		if($this->stmt->rowCount() != 1) return false;

		// evaluate corresponding deployment rule(s)
		foreach($this->selectAllDeploymentRuleByComputerGroupId($computer_group_id) as $dr) {
			$this->evaluateDeploymentRule($dr->id);
		}
		return true;
	}

	// Package Operations
	public function insertPackageFamily($name, $notes) {
		$this->stmt = $this->dbh->prepare('INSERT INTO package_family (name, notes) VALUES (:name, :notes)');
		$this->stmt->execute([
			':name' => $name,
			':notes' => $notes,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updatePackageFamily($id, $name, $license_count, $notes, $icon) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_family SET name = :name, notes = :notes, license_count = :license_count, icon = :icon WHERE id = :id'
		);
		$this->stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$this->stmt->bindParam(':name', $name, PDO::PARAM_STR);
		$this->stmt->bindParam(':license_count', $license_count, PDO::PARAM_INT);
		$this->stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
		$this->stmt->bindParam(':icon', $icon, PDO::PARAM_LOB);
		return $this->stmt->execute();
	}
	public function insertPackage($package_family_id, $version, $license_count, $created_by_system_user_id, $notes, $install_procedure, $install_procedure_success_return_codes, $install_procedure_post_action, $upgrade_behavior, $uninstall_procedure, $uninstall_procedure_success_return_codes, $download_for_uninstall, $uninstall_procedure_post_action, $compatible_os, $compatible_os_version) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO package (package_family_id, version, license_count, created_by_system_user_id, notes, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, upgrade_behavior, uninstall_procedure, uninstall_procedure_success_return_codes, download_for_uninstall, uninstall_procedure_post_action, compatible_os, compatible_os_version)
			VALUES (:package_family_id, :version, :license_count, :created_by_system_user_id, :notes, :install_procedure, :install_procedure_success_return_codes, :install_procedure_post_action, :upgrade_behavior, :uninstall_procedure, :uninstall_procedure_success_return_codes, :download_for_uninstall, :uninstall_procedure_post_action, :compatible_os, :compatible_os_version)'
		);
		$this->stmt->execute([
			':package_family_id' => $package_family_id,
			':version' => $version,
			':license_count' => $license_count,
			':created_by_system_user_id' => $created_by_system_user_id,
			':notes' => $notes,
			':install_procedure' => $install_procedure,
			':install_procedure_success_return_codes' => $install_procedure_success_return_codes,
			':install_procedure_post_action' => $install_procedure_post_action,
			':upgrade_behavior' => $upgrade_behavior,
			':uninstall_procedure' => $uninstall_procedure,
			':uninstall_procedure_success_return_codes' => $uninstall_procedure_success_return_codes,
			':download_for_uninstall' => $download_for_uninstall,
			':uninstall_procedure_post_action' => $uninstall_procedure_post_action,
			':compatible_os' => $compatible_os,
			':compatible_os_version' => $compatible_os_version,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updatePackage($id, $package_family_id, $version, $compatible_os, $compatible_os_version, $license_count, $notes, $install_procedure, $install_procedure_success_return_codes, $install_procedure_post_action, $upgrade_behavior, $uninstall_procedure, $uninstall_procedure_success_return_codes, $uninstall_procedure_post_action, $download_for_uninstall) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, package_family_id = :package_family_id, version = :version, compatible_os = :compatible_os, compatible_os_version = :compatible_os_version, license_count = :license_count, notes = :notes, install_procedure = :install_procedure, install_procedure_success_return_codes = :install_procedure_success_return_codes, install_procedure_post_action = :install_procedure_post_action, upgrade_behavior = :upgrade_behavior, uninstall_procedure = :uninstall_procedure, uninstall_procedure_success_return_codes = :uninstall_procedure_success_return_codes, uninstall_procedure_post_action = :uninstall_procedure_post_action, download_for_uninstall = :download_for_uninstall WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':package_family_id' => $package_family_id,
			':version' => $version,
			':compatible_os' => $compatible_os,
			':compatible_os_version' => $compatible_os_version,
			':license_count' => $license_count,
			':notes' => $notes,
			':install_procedure' => $install_procedure,
			':install_procedure_success_return_codes' => $install_procedure_success_return_codes,
			':install_procedure_post_action' => $install_procedure_post_action,
			':upgrade_behavior' => $upgrade_behavior,
			':uninstall_procedure' => $uninstall_procedure,
			':uninstall_procedure_success_return_codes' => $uninstall_procedure_success_return_codes,
			':uninstall_procedure_post_action' => $uninstall_procedure_post_action,
			':download_for_uninstall' => $download_for_uninstall,
		]);
	}
	public function insertComputerPackage($package_id, $computer_id, $installed_by_system_user_id, $installed_by_domain_user_id, $procedure) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_package (id, package_id, computer_id, installed_by_system_user_id, installed_by_domain_user_id, installed_procedure, installed)
			(SELECT cp2.id, cp2.package_id, cp2.computer_id, cp2.installed_by_system_user_id, cp2.installed_by_domain_user_id, cp2.installed_procedure, cp2.installed FROM computer_package cp2 WHERE cp2.package_id=:package_id AND cp2.computer_id=:computer_id)
			UNION (SELECT null, :package_id, :computer_id, :installed_by_system_user_id, :installed_by_domain_user_id, :installed_procedure, CURRENT_TIMESTAMP FROM DUAL) LIMIT 1
			ON DUPLICATE KEY UPDATE computer_package.id=LAST_INSERT_ID(computer_package.id), computer_package.installed_by_system_user_id=:installed_by_system_user_id, computer_package.installed_by_domain_user_id=:installed_by_domain_user_id, computer_package.installed_procedure=:installed_procedure, computer_package.installed=CURRENT_TIMESTAMP'
		);
		$this->stmt->execute([
			':package_id' => $package_id,
			':computer_id' => $computer_id,
			':installed_by_system_user_id' => $installed_by_system_user_id,
			':installed_by_domain_user_id' => $installed_by_domain_user_id,
			':installed_procedure' => $procedure,
		]);
		$insertId = $this->dbh->lastInsertId();

		// evaluate corresponding deployment rule(s)
		foreach($this->selectAllComputerGroupByComputerId($computer_id) as $g) {
			foreach($this->selectAllDeploymentRuleByComputerGroupId($g->id) as $dr) {
				$this->evaluateDeploymentRule($dr->id);
			}
		}
		return $insertId;
	}
	public function selectAllComputerPackageByPackageId($package_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cp.id AS "id", p.id AS "package_id", p.package_family_id AS "package_family_id", c.id AS "computer_id", c.hostname AS "computer_hostname", cp.installed_procedure AS "installed_procedure", cp.installed_by_system_user_id AS "installed_by_system_user_id", su.username AS "installed_by_system_user_username", du.username AS "installed_by_domain_user_username", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN computer c ON c.id = cp.computer_id
			INNER JOIN package p ON p.id = cp.package_id
			LEFT JOIN system_user su ON su.id = cp.installed_by_system_user_id
			LEFT JOIN domain_user du ON du.id = cp.installed_by_domain_user_id
			WHERE cp.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPackage');
	}
	public function selectAllPackageByIdAndPackageGroupId($package_id, $package_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name", su.username AS "created_by_system_user_username",
			(SELECT COUNT(cp2.id) FROM computer_package cp2 WHERE cp2.package_id = p.id) AS "install_count"
			FROM package_group_member pgm
			INNER JOIN package p ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			LEFT JOIN system_user su ON su.id = p.created_by_system_user_id
			WHERE pgm.package_id = :package_id AND pgm.package_group_id = :package_group_id'
		);
		$this->stmt->execute([':package_id' => $package_id, ':package_group_id' => $package_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackageByPackageFamilyId($package_family_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name", su.username AS "created_by_system_user_username",
			(SELECT COUNT(cp2.id) FROM computer_package cp2 WHERE cp2.package_id = p.id) AS "install_count"
			FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			LEFT JOIN system_user su ON su.id = p.created_by_system_user_id
			WHERE p.package_family_id = :package_family_id'
		);
		$this->stmt->execute([':package_family_id' => $package_family_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackageDependencyByPackageId($package_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_dependency pd
			INNER JOIN package p ON p.id = pd.dependent_package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pd.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackageDependencyByDependentPackageId($package_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_dependency pd
			INNER JOIN package p ON p.id = pd.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pd.dependent_package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectPackageConflictByPackageId($package_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_conflict pc
			INNER JOIN package p ON p.id = pc.conflict_package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pc.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackage($orderByCreated=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name", su.username AS "created_by_system_user_username",
			(SELECT COUNT(cp2.id) FROM computer_package cp2 WHERE cp2.package_id = p.id) AS "install_count"
			FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			LEFT JOIN system_user su ON su.id = p.created_by_system_user_id'
			.($orderByCreated ? ' ORDER BY p.created DESC' : ' ORDER BY pf.name ASC')
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackageFamily($binaryAsBase64=false, $orderByCreated=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pf.*, (SELECT COUNT(id) FROM package p WHERE p.package_family_id = pf.id) AS "package_count",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created DESC LIMIT 1) AS "newest_package_created",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created ASC LIMIT 1) AS "oldest_package_created",
				(SELECT COUNT(cp2.id) FROM computer_package cp2 INNER JOIN package p2 ON p2.id = cp2.package_id WHERE p2.package_family_id = pf.id) AS "install_count"
			FROM package_family pf'
			.($orderByCreated ? ' ORDER BY newest_package_created DESC' : ' ORDER BY pf.name ASC')
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageFamily', [$binaryAsBase64]);
	}
	public function selectPackageFamily($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pf.*,
			(SELECT COUNT(cp2.id) FROM computer_package cp2 INNER JOIN package p2 ON p2.id = cp2.package_id WHERE p2.package_family_id = pf.id) AS "install_count"
			FROM package_family pf WHERE pf.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageFamily') as $row) {
			return $row;
		}
	}
	public function selectPackageFamilyByName($name) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pf.*,
			(SELECT COUNT(cp2.id) FROM computer_package cp2 INNER JOIN package p2 ON p2.id = cp2.package_id WHERE p2.package_family_id = pf.id) AS "install_count"
			FROM package_family pf WHERE pf.name = :name'
		);
		$this->stmt->execute([':name' => $name]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageFamily') as $row) {
			return $row;
		}
	}
	public function selectAllPackageGroupByPackageId($package_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pg.id AS "id", pg.name AS "name", pg.parent_package_group_id AS "parent_package_group_id"
			FROM package_group_member pgm
			INNER JOIN package_group pg ON pg.id = pgm.package_group_id
			WHERE pgm.package_id = :package_id
			ORDER BY pg.name'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageGroup');
	}
	public function selectAllPackageGroupByParentPackageGroupId($parent_package_group_id=null) {
		if($parent_package_group_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM package_group WHERE parent_package_group_id IS NULL ORDER BY name'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM package_group WHERE parent_package_group_id = :parent_package_group_id ORDER BY name'
			);
			$this->stmt->execute([':parent_package_group_id' => $parent_package_group_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageGroup');
	}
	public function selectPackage($id, $binaryAsBase64=false) {
		if($binaryAsBase64 === null) { // do not fetch icons if not necessary
			$this->stmt = $this->dbh->prepare(
				'SELECT p.*, pf.name AS "package_family_name", su.username AS "created_by_system_user_username",
				(SELECT COUNT(cp2.id) FROM computer_package cp2 WHERE cp2.package_id = p.id) AS "install_count"
				FROM package p
				INNER JOIN package_family pf ON pf.id = p.package_family_id
				LEFT JOIN system_user su ON su.id = p.created_by_system_user_id
				WHERE p.id = :id'
			);
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT p.*, pf.name AS "package_family_name", pf.icon AS "package_family_icon", su.username AS "created_by_system_user_username",
				(SELECT COUNT(cp2.id) FROM computer_package cp2 WHERE cp2.package_id = p.id) AS "install_count"
				FROM package p
				INNER JOIN package_family pf ON pf.id = p.package_family_id
				LEFT JOIN system_user su ON su.id = p.created_by_system_user_id
				WHERE p.id = :id'
			);
		}
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package', [$binaryAsBase64]) as $row) {
			return $row;
		}
	}
	public function searchAllPackageFamily($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT id, name FROM package_family
			WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageFamily');
	}
	public function searchAllPackage($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package p INNER JOIN package_family pf ON p.package_family_id = pf.id
			WHERE p.version LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function searchAllPackageGroup($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM package_group WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageGroup');
	}
	public function selectAllPackageByPackageFamilyNameAndVersion($name, $version) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pf.name = :name AND p.version = :version'
		);
		$this->stmt->execute([':name' => $name, ':version' => $version]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllComputerPackage() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_package'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPackage');
	}
	public function selectComputerPackage($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_package WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPackage') as $row) {
			return $row;
		}
	}
	public function selectPackageGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM package_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageGroup') as $row) {
			return $row;
		}
	}
	public function insertPackageGroup($name, $parent_id=null) {
		if(empty($parent_id) || intval($parent_id) < 0) {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO package_group (name) VALUES (:name)'
			);
			$this->stmt->execute([':name' => $name]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO package_group (name, parent_package_group_id) VALUES (:name, :parent_package_group_id)'
			);
			$this->stmt->execute([':name' => $name, ':parent_package_group_id' => $parent_id]);
		}
		return $this->dbh->lastInsertId();
	}
	public function updatePackageGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_group SET name = :name WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id, ':name' => $name]);
		return $this->dbh->lastInsertId();
	}
	public function selectAllPackageByPackageGroupId($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name", pgm.sequence AS "package_group_member_sequence", su.username AS "created_by_system_user_username",
			(SELECT COUNT(cp2.id) FROM computer_package cp2 WHERE cp2.package_id = p.id) AS "install_count"
			FROM package p
			INNER JOIN package_group_member pgm ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			LEFT JOIN system_user su ON su.id = p.created_by_system_user_id
			WHERE pgm.package_group_id = :id
			ORDER BY pgm.sequence'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function insertPackageGroupMember($pid, $gid) {
		$seq = -1;

		$this->stmt = $this->dbh->prepare(
			'SELECT max(sequence) AS "max_sequence" FROM package_group_member WHERE package_group_id = :package_group_id'
		);
		$this->stmt->execute([':package_group_id' => $gid]);
		foreach($this->stmt->fetchAll() as $row) {
			$seq = $row['max_sequence'] + 1;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO package_group_member (package_id, package_group_id, sequence)
			VALUES (:package_id, :package_group_id, :sequence)'
		);
		if(!$this->stmt->execute([':package_id' => $pid, ':package_group_id' => $gid, ':sequence' => $seq])) return false;
		$insertId = $this->dbh->lastInsertId();

		// evaluate corresponding deployment rule(s)
		foreach($this->selectAllDeploymentRuleByPackageGroupId($gid) as $dr) {
			$this->evaluateDeploymentRule($dr->id);
		}
		return $insertId;
	}
	public function reorderPackageInGroup($package_group_id, int $old_seq, int $new_seq) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_group_member
			SET sequence = CASE
				WHEN sequence = :oldpos THEN :newpos
				WHEN :newpos < :oldpos AND sequence < :oldpos THEN sequence + 1
				WHEN :newpos > :oldpos AND sequence > :oldpos THEN sequence - 1
			END
			WHERE package_group_id = :package_group_id
			AND sequence BETWEEN LEAST(:newpos, :oldpos) AND GREATEST(:newpos, :oldpos)'
		);
		$this->stmt->bindParam(':package_group_id', $package_group_id, PDO::PARAM_INT);
		$this->stmt->bindParam(':oldpos', $old_seq, PDO::PARAM_INT);
		$this->stmt->bindParam(':newpos', $new_seq, PDO::PARAM_INT);
		if(!$this->stmt->execute()) return false;
		return $this->renewPackageGroupOrder($package_group_id);
	}
	private function renewPackageGroupOrder($package_group_id) {
		$seq = 1;
		$this->dbh->beginTransaction();
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM package_group_member WHERE package_group_id = :package_group_id ORDER BY sequence'
		);
		$this->stmt->execute([':package_group_id' => $package_group_id]);
		foreach($this->stmt->fetchAll() as $row) {
			$this->stmt = $this->dbh->prepare(
				'UPDATE package_group_member SET sequence = :sequence WHERE id = :id'
			);
			if(!$this->stmt->execute([':id' => $row['id'], ':sequence' => $seq])) return false;
			$seq ++;
		}
		$this->dbh->commit();
		return true;
	}
	public function insertPackageDependency($package_id, $dependend_package_id) {
		$this->dbh->beginTransaction();
		$this->deletePackageDependencyByPackageIdAndDependentPackageId($package_id, $dependend_package_id);
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO package_dependency (package_id, dependent_package_id)
			VALUES (:package_id, :dependent_package_id)'
		);
		$this->stmt->execute([':package_id' => $package_id, ':dependent_package_id' => $dependend_package_id]);
		$this->dbh->commit();
		return $this->dbh->lastInsertId();
	}
	public function deletePackageDependencyByPackageIdAndDependentPackageId($package_id, $dependend_package_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_dependency
			WHERE package_id = :package_id AND dependent_package_id = :dependent_package_id'
		);
		$this->stmt->execute([':package_id' => $package_id, ':dependent_package_id' => $dependend_package_id]);
		return $this->dbh->lastInsertId();
	}
	public function deletePackage($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function deletePackageFamily($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_family WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function deletePackageGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function deletePackageFromGroup($package_id, $package_group_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_group_member WHERE package_id = :package_id AND package_group_id = :package_group_id'
		);
		if(!$this->stmt->execute([':package_id' => $package_id, ':package_group_id' => $package_group_id])) return false;
		if($this->stmt->rowCount() != 1) return false;
		if(!$this->renewPackageGroupOrder($package_group_id)) return false;

		// evaluate corresponding deployment rule(s)
		foreach($this->selectAllDeploymentRuleByPackageGroupId($package_group_id) as $dr) {
			$this->evaluateDeploymentRule($dr->id);
		}
		return true;
	}
	public function deleteComputerPackage($id) {
		$computerPackageAssignment = $this->selectComputerPackage($id);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_package WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		if($this->stmt->rowCount() != 1) return false;

		// evaluate corresponding deployment rule(s)
		foreach($this->selectAllComputerGroupByComputerId($computerPackageAssignment->computer_id) as $g) {
			foreach($this->selectAllDeploymentRuleByComputerGroupId($g->id) as $dr) {
				$this->evaluateDeploymentRule($dr->id);
			}
		}
		return true;
	}
	public function deleteComputerPackageByComputerIdAndPackageId($computer_id, $package_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_package WHERE computer_id = :computer_id AND package_id = :package_id'
		);
		return $this->stmt->execute([':computer_id' => $computer_id, ':package_id' => $package_id]);
	}
	public function deleteComputerPackageByComputerIdAndPackageFamilyId($computer_id, $package_family_id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE cp FROM computer_package cp INNER JOIN package p ON cp.package_id = p.id WHERE cp.computer_id = :computer_id AND p.package_family_id = :package_family_id'
		);
		return $this->stmt->execute([':computer_id' => $computer_id, ':package_family_id' => $package_family_id]);
	}

	// Job Operations
	public function insertJobContainer($name, $created_by_system_user_id, $created_by_domain_user_id, $start_time, $end_time, $notes, $wol_sent, $shutdown_waked_after_completion, $sequence_mode, $priority, $agent_ip_ranges, $time_frames, $self_service) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job_container (name, created_by_system_user_id, created_by_domain_user_id, start_time, end_time, notes, wol_sent, shutdown_waked_after_completion, sequence_mode, priority, agent_ip_ranges, time_frames, self_service)
			VALUES (:name, :created_by_system_user_id, :created_by_domain_user_id, :start_time, :end_time, :notes, :wol_sent, :shutdown_waked_after_completion, :sequence_mode, :priority, :agent_ip_ranges, :time_frames, :self_service)'
		);
		$this->stmt->execute([
			':name' => $name,
			':created_by_system_user_id' => $created_by_system_user_id,
			':created_by_domain_user_id' => $created_by_domain_user_id,
			':start_time' => $start_time,
			':end_time' => $end_time,
			':notes' => $notes,
			':wol_sent' => $wol_sent,
			':shutdown_waked_after_completion' => $shutdown_waked_after_completion,
			':sequence_mode' => $sequence_mode,
			':priority' => $priority,
			':agent_ip_ranges' => $agent_ip_ranges,
			':time_frames' => $time_frames,
			':self_service' => $self_service,
		]);
		return $this->dbh->lastInsertId();
	}
	public function insertStaticJob($job_container_id, $computer_id, $package_id, $procedure, $success_return_codes, $upgrade_behavior, $is_uninstall, $download, $post_action, $post_action_timeout, $sequence, $state=0) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job_container_job (job_container_id, computer_id, package_id, `procedure`, success_return_codes, upgrade_behavior, is_uninstall, download, post_action, post_action_timeout, sequence, state, message)
			VALUES (:job_container_id, :computer_id, :package_id, :procedure, :success_return_codes, :upgrade_behavior, :is_uninstall, :download, :post_action, :post_action_timeout, :sequence, :state, "")'
		);
		$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':computer_id' => $computer_id,
			':package_id' => $package_id,
			':procedure' => $procedure,
			':success_return_codes' => $success_return_codes,
			':upgrade_behavior' => $upgrade_behavior,
			':is_uninstall' => $is_uninstall,
			':download' => $download,
			':post_action' => $post_action,
			':post_action_timeout' => $post_action_timeout,
			':sequence' => $sequence,
			':state' => $state,
		]);
		return $this->dbh->lastInsertId();
	}
	public function selectJobContainer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT jc.*, su.username AS "created_by_system_user_username", du.username AS "created_by_domain_user_username"
			FROM job_container jc
			LEFT JOIN system_user su ON jc.created_by_system_user_id = su.id
			LEFT JOIN domain_user du ON jc.created_by_domain_user_id = du.id
			WHERE jc.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\JobContainer') as $row) {
			return $row;
		}
	}
	public function searchAllJobContainer($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job_container WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\JobContainer');
	}
	public function selectAllJobContainer($self_service=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT jc.*, su.username AS "created_by_system_user_username", du.username AS "created_by_domain_user_username", (SELECT MAX(execution_finished) FROM job_container_job j WHERE j.job_container_id = jc.id) AS "execution_finished"
			FROM job_container jc
			LEFT JOIN system_user su ON jc.created_by_system_user_id = su.id
			LEFT JOIN domain_user du ON jc.created_by_domain_user_id = du.id '.
			($self_service===null?'':($self_service?'WHERE self_service = 1':'WHERE self_service = 0'))
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\JobContainer');
	}
	public function selectMinExecutionStaticJobByJobContainerId($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job_container_job WHERE job_container_id = :job_container_id ORDER BY execution_started ASC LIMIT 1'
		);
		$this->stmt->execute([':job_container_id' => $job_container_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob') as $row) {
			return $row;
		}
	}
	public function selectMaxExecutionStaticJobByJobContainerId($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job_container_job WHERE job_container_id = :job_container_id ORDER BY execution_finished DESC LIMIT 1'
		);
		$this->stmt->execute([':job_container_id' => $job_container_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob') as $row) {
			return $row;
		}
	}
	public function selectStaticJob($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, jc.start_time AS "job_container_start_time", jc.created_by_system_user_id AS "job_container_created_by_system_user_id", jc.created_by_domain_user_id AS "job_container_created_by_domain_user_id" FROM job_container_job j
			INNER JOIN job_container jc ON jc.id = j.job_container_id
			WHERE j.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob') as $row) {
			return $row;
		}
	}
	public function selectDynamicJob($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, dr.created_by_system_user_id AS "deployment_rule_created_by_system_user_id", dr.sequence_mode AS "deployment_rule_sequence_mode" FROM deployment_rule_job j
			INNER JOIN deployment_rule dr ON dr.id = j.deployment_rule_id
			WHERE j.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DynamicJob') as $row) {
			return $row;
		}
	}
	public function selectAllComputerWithMacByJobContainer($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", cn.mac AS "computer_network_mac"
			FROM job_container_job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN computer_network cn ON cn.computer_id = c.id
			WHERE j.job_container_id = :job_container_id'
		);
		$this->stmt->execute([':job_container_id' => $job_container_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
	}
	public function selectAllStaticJobByJobContainer($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, pf.name AS "package_family_name", p.version AS "package_version", c.hostname AS "computer_hostname", jc.start_time AS "job_container_start_time"
			FROM job_container_job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN package p ON p.id = j.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN job_container jc ON jc.id = j.job_container_id
			WHERE j.job_container_id = :job_container_id
			ORDER BY j.computer_id, j.sequence'
		);
		$this->stmt->execute([':job_container_id' => $job_container_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob');
	}
	public function selectAllDynamicJobByDeploymentRuleId($deployment_rule_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, pf.name AS "package_family_name", p.version AS "package_version", c.hostname AS "computer_hostname"
			FROM deployment_rule_job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN package p ON p.id = j.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN deployment_rule dr ON dr.id = j.deployment_rule_id
			WHERE j.deployment_rule_id = :deployment_rule_id
			ORDER BY j.computer_id, j.sequence'
		);
		$this->stmt->execute([':deployment_rule_id' => $deployment_rule_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DynamicJob');
	}
	public function selectAllPendingAndActiveJobForAgentByComputerId($computer_id) {
		// static jobs
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", jc.enabled AS "job_container_enabled", jc.priority AS "job_container_priority", jc.sequence_mode AS "job_container_sequence_mode", jc.agent_ip_ranges AS "job_container_agent_ip_ranges", jc.time_frames AS "job_container_time_frames", jc.self_service AS "job_container_self_service",
			j.package_id AS "package_id", j.procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", j.sequence AS "sequence"
			FROM job_container_job j
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = :computer_id
			AND jc.enabled = 1
			AND (j.state = '.Models\Job::STATE_WAITING_FOR_AGENT.' OR j.state = '.Models\Job::STATE_DOWNLOAD_STARTED.' OR j.state = '.Models\Job::STATE_EXECUTION_STARTED.')
			AND (jc.start_time IS NULL OR jc.start_time < CURRENT_TIMESTAMP) AND (jc.end_time IS NULL OR jc.end_time > CURRENT_TIMESTAMP)
			ORDER BY jc.priority DESC, jc.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		$static_jobs = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob');
		// dynamic jobs
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.deployment_rule_id AS "deployment_rule_id", dr.enabled AS "deployment_rule_enabled", dr.sequence_mode AS "deployment_rule_sequence_mode", dr.priority AS "deployment_rule_priority",
			j.package_id AS "package_id", j.procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", j.sequence AS "sequence"
			FROM deployment_rule_job j
			INNER JOIN deployment_rule dr ON j.deployment_rule_id = dr.id
			WHERE j.computer_id = :computer_id
			AND dr.enabled = 1
			AND (j.state = '.Models\Job::STATE_WAITING_FOR_AGENT.' OR j.state = '.Models\Job::STATE_DOWNLOAD_STARTED.' OR j.state = '.Models\Job::STATE_EXECUTION_STARTED.')
			ORDER BY dr.priority DESC, dr.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		$dynamic_jobs = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DynamicJob');
		// merge and order
		$jobs = array_merge( $static_jobs, $dynamic_jobs );
		usort($jobs, [Models\Job::class, 'sortJobs']);
		return $jobs;
	}
	public function selectPendingAndActiveJobForAgentByComputerIdAndPackageId($computer_id, $package_id) {
		foreach($this->selectAllPendingAndActiveJobForAgentByComputerId($computer_id) as $job) {
			if($job->package_id === $package_id) return $job;
		}
	}
	public function selectAllPendingJobByComputerId($computer_id) {
		// static jobs
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", jc.name AS "job_container_name", jc.start_time AS "job_container_start_time", jc.enabled AS "job_container_enabled", jc.priority AS "job_container_priority", jc.self_service AS "job_container_self_service",
			j.package_id AS "package_id", pf.name AS "package_family_name", p.version AS "package_version",
			j.is_uninstall AS "is_uninstall", j.state AS "state", j.procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", j.sequence AS "sequence"
			FROM job_container_job j
			INNER JOIN package p ON j.package_id = p.id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = :computer_id
			AND (j.state = '.Models\Job::STATE_WAITING_FOR_AGENT.' OR j.state = '.Models\Job::STATE_DOWNLOAD_STARTED.' OR j.state = '.Models\Job::STATE_EXECUTION_STARTED.')
			ORDER BY jc.priority DESC, jc.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		$static_jobs = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob');
		// dynamic jobs
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.deployment_rule_id AS "deployment_rule_id", dr.name AS "deployment_rule_name", dr.enabled AS "deployment_rule_enabled", dr.priority AS "deployment_rule_priority",
			j.package_id AS "package_id", pf.name AS "package_family_name", p.version AS "package_version",
			j.is_uninstall AS "is_uninstall", j.state AS "state", j.procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", j.sequence AS "sequence"
			FROM deployment_rule_job j
			INNER JOIN package p ON j.package_id = p.id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN deployment_rule dr ON j.deployment_rule_id = dr.id
			WHERE j.computer_id = :computer_id
			AND (j.state = '.Models\Job::STATE_WAITING_FOR_AGENT.' OR j.state = '.Models\Job::STATE_DOWNLOAD_STARTED.' OR j.state = '.Models\Job::STATE_EXECUTION_STARTED.')
			ORDER BY dr.priority DESC, dr.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		$dynamic_jobs = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DynamicJob');
		// merge and order
		$jobs = array_merge( $static_jobs, $dynamic_jobs );
		usort($jobs, [Models\Job::class, 'sortJobs']);
		return $jobs;
	}
	public function selectAllPendingJobByPackageId($package_id) {
		// static jobs
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", jc.name AS "job_container_name", jc.start_time AS "job_container_start_time", jc.enabled AS "job_container_enabled", jc.priority AS "job_container_priority", jc.self_service AS "job_container_self_service",
			j.computer_id AS "computer_id", c.hostname AS "computer_hostname",
			j.is_uninstall AS "is_uninstall", j.state AS "state", j.procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", j.sequence AS "sequence"
			FROM job_container_job j
			INNER JOIN computer c ON j.computer_id = c.id
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.package_id = :package_id
			AND (j.state = '.Models\Job::STATE_WAITING_FOR_AGENT.' OR j.state = '.Models\Job::STATE_DOWNLOAD_STARTED.' OR j.state = '.Models\Job::STATE_EXECUTION_STARTED.')
			ORDER BY jc.priority DESC, jc.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		$static_jobs = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob');
		// dynamic jobs
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.deployment_rule_id AS "deployment_rule_id", dr.name AS "deployment_rule_name", dr.enabled AS "deployment_rule_enabled", dr.priority AS "deployment_rule_priority",
			j.computer_id AS "computer_id", c.hostname AS "computer_hostname",
			j.is_uninstall AS "is_uninstall", j.state AS "state", j.procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", j.sequence AS "sequence"
			FROM deployment_rule_job j
			INNER JOIN computer c ON j.computer_id = c.id
			INNER JOIN deployment_rule dr ON j.deployment_rule_id = dr.id
			WHERE j.package_id = :package_id
			AND (j.state = '.Models\Job::STATE_WAITING_FOR_AGENT.' OR j.state = '.Models\Job::STATE_DOWNLOAD_STARTED.' OR j.state = '.Models\Job::STATE_EXECUTION_STARTED.')
			ORDER BY dr.priority DESC, dr.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		$dynamic_jobs = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DynamicJob');
		// merge and order
		$jobs = array_merge( $static_jobs, $dynamic_jobs );
		usort($jobs, [Models\Job::class, 'sortJobs']);
		return $jobs;
	}
	public function evaluateDeploymentRule($deployment_rule_id) {
		$deployment_rule = $this->selectDeploymentRule($deployment_rule_id);
		$computer_packages = $this->selectAllComputerPackage();
		$dynamic_jobs = [];
		$created_uninstall_jobs = [];
		foreach($this->selectAllComputerByComputerGroupId($deployment_rule->computer_group_id) as $computer) {
			$sequence = 1;
			foreach($this->selectAllPackageByPackageGroupId($deployment_rule->package_group_id) as $package) {
				$state = Models\Job::STATE_WAITING_FOR_AGENT;
				$isInstalled = false;
				// check if already installed
				foreach($computer_packages as $cp) {
					if($cp->computer_id == $computer->id && $cp->package_id == $package->id) {
						$state = Models\Job::STATE_ALREADY_INSTALLED;
						$isInstalled = true;
						break;
					}
				}
				// check if we need to uninstall older versions
				if($package->upgrade_behavior == Models\Package::UPGRADE_BEHAVIOR_EXPLICIT_UNINSTALL_JOBS
				&& $state != Models\Job::STATE_ALREADY_INSTALLED && $state != Models\Job::STATE_OS_INCOMPATIBLE && $state != Models\Job::STATE_PACKAGE_CONFLICT) {
					$dynamic_jobs = array_merge(
						$dynamic_jobs,
						$this->compileDynamicUninstallJobs($deployment_rule, $package->package_family_id, $this->selectAllComputerPackageByComputerId($computer->id), $created_uninstall_jobs, $sequence)
					);
				}
				// add dynamic job
				$dynamic_jobs[] = Models\DynamicJob::__constructWithValues(
					$deployment_rule->id, $deployment_rule->name, $deployment_rule->created_by_system_user_id, $deployment_rule->enabled, $deployment_rule->priority,
					$computer->id, $computer->hostname,
					$package->id, $package->version, $package->package_family_name, $package->install_procedure, $package->install_procedure_success_return_codes, $package->upgrade_behavior,
					0/*is_uninstall*/, $package->getFilePath() ? 1 : 0,
					$package->install_procedure_post_action, $deployment_rule->post_action_timeout,
					$sequence,
					$state, $dynamic_job_execution->return_code??null, $dynamic_job_execution->message??''
				);
				$sequence ++;
			}
		}
		$ids = [];
		foreach($dynamic_jobs as $job) {
			// insert/update dynamic job
			// - do not update state "success" if new state is "already installed" or "failed" and new state is "waiting for agent"
			// - keep execution results (return_code and message)
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO deployment_rule_job (id, deployment_rule_id, computer_id, package_id, `procedure`, success_return_codes, upgrade_behavior, is_uninstall, download, post_action, post_action_timeout, sequence, state, return_code, message)
				(SELECT drj2.id, drj2.deployment_rule_id, drj2.computer_id, drj2.package_id, drj2.`procedure`, drj2.success_return_codes, drj2.upgrade_behavior, drj2.is_uninstall, drj2.download, drj2.post_action, drj2.post_action_timeout, drj2.sequence, drj2.state, drj2.return_code, drj2.message FROM deployment_rule_job drj2 WHERE drj2.deployment_rule_id=:deployment_rule_id AND drj2.computer_id=:computer_id AND drj2.package_id=:package_id AND drj2.is_uninstall=:is_uninstall)
				UNION (SELECT null, :deployment_rule_id, :computer_id, :package_id, :procedure, :success_return_codes, :upgrade_behavior, :is_uninstall, :download, :post_action, :post_action_timeout, :sequence, :state, :return_code, :message FROM DUAL) LIMIT 1
				ON DUPLICATE KEY UPDATE deployment_rule_job.id=LAST_INSERT_ID(deployment_rule_job.id),
					deployment_rule_job.`procedure`=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.`procedure`,:procedure),
					deployment_rule_job.success_return_codes=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.success_return_codes,:success_return_codes),
					deployment_rule_job.upgrade_behavior=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.upgrade_behavior,:upgrade_behavior),
					deployment_rule_job.download=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.download,:download),
					deployment_rule_job.post_action=:post_action,
					deployment_rule_job.post_action_timeout=:post_action_timeout,
					deployment_rule_job.sequence=:sequence,
					deployment_rule_job.state=IF((deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' AND :state='.Models\Job::STATE_ALREADY_INSTALLED.') OR (deployment_rule_job.state='.Models\Job::STATE_FAILED.' AND :state='.Models\Job::STATE_WAITING_FOR_AGENT.') OR (deployment_rule_job.state='.Models\Job::STATE_DOWNLOAD_STARTED.' AND :state='.Models\Job::STATE_WAITING_FOR_AGENT.') OR (deployment_rule_job.state='.Models\Job::STATE_EXECUTION_STARTED.' AND :state='.Models\Job::STATE_WAITING_FOR_AGENT.'), deployment_rule_job.state, :state)'
			);
			$this->stmt->execute([
				':deployment_rule_id' => $job->deployment_rule_id,
				':computer_id' => $job->computer_id,
				':package_id' => $job->package_id,
				':procedure' => $job->procedure,
				':success_return_codes' => $job->success_return_codes,
				':upgrade_behavior' => $job->upgrade_behavior,
				':is_uninstall' => $job->is_uninstall,
				':download' => $job->download,
				':post_action' => $job->post_action,
				':post_action_timeout' => $job->post_action_timeout,
				':sequence' => $job->sequence,
				':state' => $job->state,
				':return_code' => $job->return_code,
				':message' => $job->message,
			]);
			$ids[] = $this->dbh->lastInsertId();
		}
		// remove all obsolete jobs (computers or packages removed from target groups)
		list($in_placeholders, $in_params) = self::compileSqlInValues($ids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM deployment_rule_job WHERE deployment_rule_id = :deployment_rule_id AND id NOT IN ('.$in_placeholders.')'
		);
		return $this->stmt->execute(array_merge([':deployment_rule_id'=>$deployment_rule->id], $in_params));
	}
	private function compileDynamicUninstallJobs($deployment_rule, $package_family_id, $computer_packages, &$createdUninstallJobs, &$sequence) {
		$dynamic_jobs = [];
		foreach($computer_packages as $cp) {
			// uninstall it, if it is from the same package family ...
			if($cp->package_family_id === $package_family_id) {
				$cpp = $this->selectPackage($cp->package_id, null);
				// ... but not, if this uninstall job was already created
				if(in_array($cp->id, $createdUninstallJobs)) continue;
				$createdUninstallJobs[] = $cp->id;
				$dynamic_jobs[] = Models\DynamicJob::__constructWithValues(
					$deployment_rule->id, $deployment_rule->name, $deployment_rule->created_by_system_user_id, $deployment_rule->enabled, $deployment_rule->priority,
					$cp->computer_id, $cp->computer_hostname,
					$cpp->id, $cpp->version, $cpp->package_family_name, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes, $cpp->upgrade_behavior,
					1/*is_uninstall*/, ($cpp->download_for_uninstall&&$cpp->getFilePath()) ? 1 : 0,
					$cpp->uninstall_procedure_post_action, $deployment_rule->post_action_timeout,
					$sequence
				);
				$sequence ++;
			}
		}
		return $dynamic_jobs;
	}
	public function setComputerOnlineStateForWolShutdown($job_container_id) {
		$tmpJobContainer = $this->selectJobContainer($job_container_id);
		if(empty($tmpJobContainer->shutdown_waked_after_completion)) return;
		foreach($this->selectAllLastComputerStaticJobByJobContainer($job_container_id) as $j) {
			$tmpComputer = $this->selectComputer($j->computer_id);
			if(!$tmpComputer->isOnline($this))
				$this->setWolShutdownStaticJobInJobContainer($job_container_id, $tmpComputer->id, $j->max_sequence);
		}
	}
	private function selectAllLastComputerStaticJobByJobContainer($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT computer_id, MAX(sequence) AS "max_sequence" FROM job_container_job
			WHERE job_container_id = :job_container_id GROUP BY computer_id'
		);
		if(!$this->stmt->execute([':job_container_id' => $job_container_id])) return false;
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\StaticJob');
	}
	private function setWolShutdownStaticJobInJobContainer($job_container_id, $computer_id, $sequence) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container_job SET post_action = '.Models\Package::POST_ACTION_SHUTDOWN.','
			.' wol_shutdown_set = CURRENT_TIMESTAMP'
			.' WHERE job_container_id = :job_container_id'
			.' AND computer_id = :computer_id'
			.' AND sequence = :sequence'
			.' AND (post_action = '.Models\Package::POST_ACTION_NONE.' OR post_action = '.Models\Package::POST_ACTION_EXIT.')'
		);
		if(!$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':computer_id' => $computer_id,
			':sequence' => $sequence,
		])) return false;
	}
	public function removeWolShutdownStaticJobInJobContainer($job_container_id, $job_id, $post_action) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container_job SET post_action = :post_action,'
			.' wol_shutdown_set = NULL'
			.' WHERE job_container_id = :job_container_id'
			.' AND id = :id'
			.' AND post_action IS NOT NULL'
		);
		if(!$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':id' => $job_id,
			':post_action' => $post_action,
		])) return false;
	}
	public function renewStaticJob($id, $procedure, $success_return_codes, $upgrade_behavior, $post_action) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container_job
			SET state = 0, download_progress = NULL, return_code = NULL, message = "", download_started = NULL, execution_started = NULL, execution_finished = NULL,
			`procedure` = :procedure, success_return_codes = :success_return_codes, upgrade_behavior = :upgrade_behavior, post_action = :post_action
			WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':procedure' => $procedure,
			':success_return_codes' => $success_return_codes,
			':upgrade_behavior' => $upgrade_behavior,
			':post_action' => $post_action,
		]);
	}
	public function updateJobExecutionState($job) {
		if($job instanceof Models\StaticJob) {
			if($job->state === Models\Job::STATE_DOWNLOAD_STARTED) {
				$timestamp_update = 'download_started = CURRENT_TIMESTAMP';
			} elseif($job->state === Models\Job::STATE_EXECUTION_STARTED) {
				$timestamp_update = 'execution_started = CURRENT_TIMESTAMP';
			} else {
				$timestamp_update = 'execution_finished = CURRENT_TIMESTAMP';
			}
			$this->stmt = $this->dbh->prepare(
				'UPDATE job_container_job SET state = :state, download_progress = :download_progress, return_code = :return_code, message = :message, '.$timestamp_update.'
				WHERE id = :id'
			);
			if(!$this->stmt->execute([
				':id' => $job->id,
				':state' => $job->state,
				':download_progress' => $job->download_progress,
				':return_code' => $job->return_code,
				':message' => $job->message,
			])) return false;
			// set all pending jobs of specific computer to failed if sequence_mode is 'abort after failed'
			if($job->state == Models\Job::STATE_FAILED) {
				$job_container_id = -1;
				$sequence_mode = Models\JobContainer::SEQUENCE_MODE_IGNORE_FAILED;
				$this->stmt = $this->dbh->prepare(
					'SELECT jc.id AS "job_container_id", jc.sequence_mode FROM job_container_job j INNER JOIN job_container jc ON j.job_container_id = jc.id WHERE j.id = :id'
				);
				$this->stmt->execute([':id' => $job->id]);
				foreach($this->stmt->fetchAll() as $row) {
					$job_container_id = $row['job_container_id'];
					$sequence_mode = $row['sequence_mode'];
				}
				if($sequence_mode == Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
					$this->stmt = $this->dbh->prepare(
						'UPDATE job_container_job SET state = :state, return_code = :return_code, message = :message, execution_finished = CURRENT_TIMESTAMP
						WHERE job_container_id = :job_container_id AND state = :old_state'
					);
					return $this->stmt->execute([
						':job_container_id' => $job_container_id,
						':old_state' => Models\Job::STATE_WAITING_FOR_AGENT,
						':state' => Models\Job::STATE_FAILED,
						':return_code' => Models\JobContainer::RETURN_CODE_ABORT_AFTER_FAILED,
						':message' => LANG('aborted_after_failed'),
					]);
				}
			}
		} elseif($job instanceof Models\DynamicJob) {
			if($job->state === Models\Job::STATE_DOWNLOAD_STARTED) {
				$timestamp_update = 'download_started = CURRENT_TIMESTAMP';
			} elseif($job->state === Models\Job::STATE_EXECUTION_STARTED) {
				$timestamp_update = 'execution_started = CURRENT_TIMESTAMP';
			} else {
				$timestamp_update = 'execution_finished = CURRENT_TIMESTAMP';
			}
			$this->stmt = $this->dbh->prepare(
				'UPDATE deployment_rule_job SET state = :state, download_progress = :download_progress, return_code = :return_code, message = :message, '.$timestamp_update.' WHERE id = :id'
			);
			if(!$this->stmt->execute([
				':id' => $job->id,
				':state' => $job->state,
				':download_progress' => $job->download_progress,
				':return_code' => $job->return_code,
				':message' => $job->message,
			])) return false;
		}
	}
	public function updateJobContainer($id, $name, $enabled, $start_time, $end_time, $notes, $wol_sent, $shutdown_waked_after_completion, $sequence_mode, $priority, $agent_ip_ranges, $time_frames) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container
			SET name = :name, enabled = :enabled, start_time = :start_time, end_time = :end_time, notes = :notes, wol_sent = :wol_sent, shutdown_waked_after_completion = :shutdown_waked_after_completion, sequence_mode = :sequence_mode, priority = :priority, agent_ip_ranges = :agent_ip_ranges, time_frames = :time_frames
			WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':name' => $name,
			':enabled' => $enabled,
			':start_time' => $start_time,
			':end_time' => $end_time,
			':notes' => $notes,
			':wol_sent' => $wol_sent,
			':shutdown_waked_after_completion' => $shutdown_waked_after_completion,
			':sequence_mode' => $sequence_mode,
			':priority' => $priority,
			':agent_ip_ranges' => $agent_ip_ranges,
			':time_frames' => $time_frames,
		]);
	}
	public function moveStaticJobToJobContainer($job_id, $job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container_job SET job_container_id = :job_container_id WHERE id = :job_id'
		);
		return $this->stmt->execute([':job_id' => $job_id, ':job_container_id' => $job_container_id]);
	}
	public function deleteJobContainer($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM job_container WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function deleteStaticJob($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM job_container_job WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function selectAllDeploymentRule() {
		$this->stmt = $this->dbh->prepare(
			'SELECT dr.*, su.username AS "created_by_system_user_username" FROM deployment_rule dr
			LEFT JOIN system_user su ON su.id = dr.created_by_system_user_id'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DeploymentRule');
	}
	public function selectDeploymentRule($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT dr.*, su.username AS "created_by_system_user_username" FROM deployment_rule dr
			LEFT JOIN system_user su ON su.id = dr.created_by_system_user_id WHERE dr.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DeploymentRule') as $row) {
			return $row;
		}
	}
	public function selectAllDeploymentRuleByComputerGroupId($computer_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM deployment_rule WHERE computer_group_id = :computer_group_id'
		);
		$this->stmt->execute([':computer_group_id' => $computer_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DeploymentRule');
	}
	public function selectAllDeploymentRuleByPackageGroupId($package_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM deployment_rule WHERE package_group_id = :package_group_id'
		);
		$this->stmt->execute([':package_group_id' => $package_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DeploymentRule');
	}
	public function insertDeploymentRule($name, $notes, $created_by_system_user_id, $enabled, $computer_group_id, $package_group_id, $priority) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO deployment_rule (name, created_by_system_user_id, enabled, computer_group_id, package_group_id, notes, priority)
			VALUES (:name, :created_by_system_user_id, :enabled, :computer_group_id, :package_group_id, :notes, :priority)'
		);
		if(!$this->stmt->execute([
			':name' => $name,
			':created_by_system_user_id' => $created_by_system_user_id,
			':enabled' => $enabled,
			':computer_group_id' => $computer_group_id,
			':package_group_id' => $package_group_id,
			':notes' => $notes,
			':priority' => $priority,
		])) return false;
		$insertId = $this->dbh->lastInsertId();
		$this->evaluateDeploymentRule($insertId);
		return $insertId;
	}
	public function updateDeploymentRule($id, $name, $notes, $enabled, $computer_group_id, $package_group_id, $priority) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE deployment_rule
			SET name = :name, enabled = :enabled, computer_group_id = :computer_group_id, package_group_id = :package_group_id, notes = :notes, priority = :priority
			WHERE id = :id'
		);
		if(!$this->stmt->execute([
			':id' => $id,
			':name' => $name,
			':enabled' => $enabled,
			':computer_group_id' => $computer_group_id,
			':package_group_id' => $package_group_id,
			':notes' => $notes,
			':priority' => $priority,
		])) return false;
		return $this->evaluateDeploymentRule($id);
	}
	public function updateDynamicJob($ids, $state, $download_progress, $return_code, $message) {
		list($in_placeholders, $in_params) = self::compileSqlInValues($ids);
		$this->stmt = $this->dbh->prepare(
			'UPDATE deployment_rule_job SET state = :state, download_progress = :download_progress, return_code = :return_code, message = :message WHERE id IN ('.$in_placeholders.')'
		);
		$this->stmt->execute(array_merge($in_params, [':state'=>$state, ':download_progress'=>$download_progress, ':return_code'=>$return_code, ':message'=>$message]));
		return $this->stmt->rowCount() == 1;
	}
	public function deleteDeploymentRule($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM deployment_rule WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}

	// Domain User Operations
	public function selectAllDomainUserRole() {
		$this->stmt = $this->dbh->prepare(
			'SELECT dur.*, (SELECT count(id) FROM domain_user du WHERE du.domain_user_role_id = dur.id) AS "domain_user_count" FROM domain_user_role dur ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserRole');
	}
	public function selectDomainUserRole($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT dur.*, (SELECT count(id) FROM domain_user du WHERE du.domain_user_role_id = dur.id) AS "domain_user_count" FROM domain_user_role dur WHERE dur.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserRole') as $row) {
			return $row;
		}
	}
	public function insertDomainUserRole($name, $permissions) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO domain_user_role (name, permissions) VALUES (:name, :permissions)'
		);
		$this->stmt->execute([
			':name' => $name,
			':permissions' => $permissions,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateDomainUserRole($id, $name, $permissions) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE domain_user_role SET name = :name, permissions = :permissions WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':name' => $name,
			':permissions' => $permissions,
		]);
	}
	public function deleteDomainUserRole($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM domain_user_role WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}
	private function insertOrUpdateDomainUser($uid, $username, $display_name) {
		if(empty($uid) || empty(trim($uid))) $uid = null;
		$this->stmt = $this->dbh->prepare(
			'UPDATE domain_user SET id = LAST_INSERT_ID(id), uid = IF(:uid IS NULL,uid,:uid), username = :username, display_name = :display_name
			WHERE (uid IS NOT NULL AND uid = :uid) OR (username = :username AND display_name = :display_name) OR (username = :username AND display_name = "") LIMIT 1'
		);
		if(!$this->stmt->execute([':uid' => $uid, ':username' => $username, ':display_name' => $display_name])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO domain_user (uid, username, display_name)
			VALUES (:uid, :username, :display_name)'
		);
		if(!$this->stmt->execute([':uid' => $uid, ':username' => $username, ':display_name' => $display_name])) return false;
		return $this->dbh->lastInsertId();
	}
	public function selectAllDomainUser($selfServiceOnly=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.*, dur.name AS "domain_user_role_name",
				(SELECT count(dl2.id) FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id) AS "logon_amount",
				(SELECT count(DISTINCT dl2.computer_id) FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id) AS "computer_amount",
				(SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user du LEFT JOIN domain_user_role dur ON du.domain_user_role_id = dur.id '
			.($selfServiceOnly ? 'WHERE du.domain_user_role_id IS NOT NULL AND (du.password IS NOT NULL OR du.ldap > 0) ' : '')
			.'ORDER BY username ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUser');
	}
	public function searchAllDomainUser($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM domain_user WHERE username LIKE :username OR display_name LIKE :username ORDER BY username ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':username' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUser');
	}
	public function selectDomainUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.*, dur.permissions AS "domain_user_role_permissions", (SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user du LEFT JOIN domain_user_role dur ON du.domain_user_role_id = dur.id WHERE du.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUser') as $row) {
			return $row;
		}
	}
	public function selectDomainUserByUsername($username) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.*, dur.name AS "domain_user_role_name", dur.permissions AS "domain_user_role_permissions"
			FROM domain_user du LEFT JOIN domain_user_role dur ON du.domain_user_role_id = dur.id
			WHERE du.username = :username'
		);
		$this->stmt->execute([':username' => $username]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUser') as $row) {
			return $row;
		}
	}
	public function selectDomainUserByUid($uid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.*, dur.name AS "domain_user_role_name", dur.permissions AS "domain_user_role_permissions"
			FROM domain_user du LEFT JOIN domain_user_role dur ON du.domain_user_role_id = dur.id
			WHERE du.uid = :uid'
		);
		$this->stmt->execute([':uid' => $uid]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUser') as $row) {
			return $row;
		}
	}
	private function insertOrUpdateDomainUserLogon($computer_id, $did, $console, $timestamp) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE domain_user_logon SET id = LAST_INSERT_ID(id)
			WHERE computer_id = :computer_id AND domain_user_id = :domain_user_id AND console = :console AND timestamp = :timestamp LIMIT 1'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':domain_user_id' => $did,
			':console' => $console,
			':timestamp' => $timestamp,
		])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO domain_user_logon (computer_id, domain_user_id, console, timestamp)
			VALUES (:computer_id, :domain_user_id, :console, :timestamp)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':domain_user_id' => $did,
			':console' => $console,
			':timestamp' => $timestamp,
		])) return false;
		return $this->dbh->lastInsertId();
	}
	public function selectLastDomainUserLogonByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM domain_user_logon WHERE computer_id = :computer_id ORDER BY timestamp DESC LIMIT 1'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserLogon') as $row) {
			return $row;
		}
	}
	public function selectAllDomainUserLogonByDomainUserId($domain_user_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "computer_id", c.hostname AS "computer_hostname", dl.console AS "console", dl.timestamp AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domain_user_id = :domain_user_id
			ORDER BY timestamp DESC, computer_hostname ASC'
		);
		$this->stmt->execute([':domain_user_id' => $domain_user_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserLogon');
	}
	public function selectAllAggregatedDomainUserLogonByDomainUserId($domain_user_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "computer_id", c.hostname AS "computer_hostname", COUNT(c.hostname) AS "logon_amount",
			(SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domain_user_id = dl.domain_user_id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domain_user_id = :domain_user_id
			GROUP BY dl.domain_user_id, dl.computer_id
			ORDER BY timestamp DESC, logon_amount DESC, computer_hostname ASC'
		);
		$this->stmt->execute([':domain_user_id' => $domain_user_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserLogon');
	}
	public function selectAllDomainUserLogonByComputerId($computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.id AS "domain_user_id", du.username AS "domain_user_username", du.display_name AS "domain_user_display_name", COUNT(du.username) AS "logon_amount",
			(SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domain_user_id = dl.domain_user_id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN domain_user du ON dl.domain_user_id = du.id
			WHERE dl.computer_id = :computer_id
			GROUP BY dl.computer_id, dl.domain_user_id
			ORDER BY timestamp DESC, logon_amount DESC, domain_user_username ASC'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserLogon');
	}
	public function selectLastDomainUserLogonByDomainUserIdAndComputerId($domain_user_id, $computer_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.id AS "domain_user_id", du.username AS "domain_user_username", du.display_name AS "domain_user_display_name", dl.timestamp AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN domain_user du ON dl.domain_user_id = du.id
			WHERE dl.computer_id = :computer_id AND dl.domain_user_id = :domain_user_id
			ORDER BY timestamp DESC LIMIT 1'
		);
		$this->stmt->execute([':computer_id' => $computer_id, ':domain_user_id' => $domain_user_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DomainUserLogon') as $row) {
			return $row;
		}
	}
	public function updateDomainUser($id, $domain_user_role_id, $password, $ldap) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE domain_user SET password = :password, domain_user_role_id = :domain_user_role_id, ldap = :ldap WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':domain_user_role_id' => empty($domain_user_role_id) ? null : $domain_user_role_id,
			':password' => $password,
			':ldap' => $ldap,
		]);
	}
	public function revokeAllLdapDomainUserByIds($ids) {
		list($in_placeholders, $in_params) = self::compileSqlInValues($ids);
		$this->stmt = $this->dbh->prepare(
			'UPDATE domain_user SET domain_user_role_id = NULL, password = NULL, ldap = 0 WHERE ldap != 0 AND id NOT IN ('.$in_placeholders.')'
		);
		return $this->stmt->execute($in_params);
	}
	public function updateDomainUserLastLogin($id) {
		$this->stmt = $this->dbh->prepare('UPDATE domain_user SET last_login = CURRENT_TIMESTAMP WHERE id = :id');
		return $this->stmt->execute([':id' => $id]);
	}
	public function deleteDomainUser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM domain_user WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function deleteDomainUserLogonOlderThan($seconds) {
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM domain_user_logon WHERE timestamp < NOW() - INTERVAL '.intval($seconds).' SECOND'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}

	// System User Operations
	public function selectAllSystemUserRole() {
		$this->stmt = $this->dbh->prepare(
			'SELECT sur.*, (SELECT count(id) FROM system_user su WHERE su.system_user_role_id = sur.id) AS "system_user_count" FROM system_user_role sur ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUserRole');
	}
	public function selectSystemUserRole($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT sur.*, (SELECT count(id) FROM system_user su WHERE su.system_user_role_id = sur.id) AS "system_user_count" FROM system_user_role sur WHERE sur.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUserRole') as $row) {
			return $row;
		}
	}
	public function insertSystemUserRole($name, $permissions) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO system_user_role (name, permissions) VALUES (:name, :permissions)'
		);
		$this->stmt->execute([
			':name' => $name,
			':permissions' => $permissions,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateSystemUserRole($id, $name, $permissions) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE system_user_role SET name = :name, permissions = :permissions WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':name' => $name,
			':permissions' => $permissions,
		]);
	}
	public function deleteSystemUserRole($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM system_user_role WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}
	public function selectAllSystemUser() {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			ORDER BY username ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser');
	}
	public function selectSystemUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser') as $row) {
			return $row;
		}
	}
	public function selectSystemUserByUsername($username) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.username = :username'
		);
		$this->stmt->execute([':username' => $username]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser') as $row) {
			return $row;
		}
	}
	public function selectSystemUserByUid($uid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.uid = :uid'
		);
		$this->stmt->execute([':uid' => $uid]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser') as $row) {
			return $row;
		}
	}
	public function insertSystemUser($uid, $username, $display_name, $password, $ldap, $email, $phone, $mobile, $description, $locked, $system_user_role_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO system_user (uid, username, display_name, password, ldap, email, phone, mobile, description, locked, system_user_role_id)
			VALUES (:uid, :username, :display_name, :password, :ldap, :email, :phone, :mobile, :description, :locked, :system_user_role_id)'
		);
		$this->stmt->execute([
			':uid' => $uid,
			':username' => $username,
			':display_name' => $display_name,
			':password' => $password,
			':ldap' => $ldap,
			':email' => $email,
			':phone' => $phone,
			':mobile' => $mobile,
			':description' => $description,
			':locked' => $locked,
			':system_user_role_id' => $system_user_role_id,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateSystemUser($id, $uid, $username, $display_name, $password, $ldap, $email, $phone, $mobile, $description, $locked, $system_user_role_id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE system_user SET uid = :uid, username = :username, display_name = :display_name, password = :password, ldap = :ldap, email = :email, phone = :phone, mobile = :mobile, description = :description, locked = :locked, system_user_role_id = :system_user_role_id WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':uid' => $uid,
			':username' => $username,
			':display_name' => $display_name,
			':password' => $password,
			':ldap' => $ldap,
			':email' => $email,
			':phone' => $phone,
			':mobile' => $mobile,
			':description' => $description,
			':locked' => $locked,
			':system_user_role_id' => $system_user_role_id,
		]);
	}
	public function updateSystemUserLastLogin($id) {
		$this->stmt = $this->dbh->prepare('UPDATE system_user SET last_login = CURRENT_TIMESTAMP WHERE id = :id');
		return $this->stmt->execute([':id' => $id]);
	}
	public function deleteSystemUser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM system_user WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}

	// Software Operations
	public function selectAllSoftware() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s LEFT JOIN computer_software cs ON cs.software_id = s.id
			GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software');
	}
	public function selectAllSoftwareByComputerOsWindows() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s
			INNER JOIN computer_software cs ON cs.software_id = s.id
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE c.os LIKE "%Windows%"
			GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software');
	}
	public function selectAllSoftwareByComputerOsMacOs() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s
			INNER JOIN computer_software cs ON cs.software_id = s.id
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE c.os LIKE "%macOS%"
			GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software');
	}
	public function selectAllSoftwareByComputerOsOther() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s
			INNER JOIN computer_software cs ON cs.software_id = s.id
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE c.os NOT LIKE "%Windows%" AND c.os NOT LIKE "%macOS%"
			GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software');
	}
	public function selectSoftware($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM software WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software') as $row) {
			return $row;
		}
	}
	private function insertOrUpdateSoftware($name, $version, $description) {
		// description is limited by MySQL index -> cut everything after 350 chars
		$description = substr($description, 0, 350);

		$this->stmt = $this->dbh->prepare(
			'SELECT id FROM software WHERE name = :name AND version = :version AND description = :description LIMIT 1'
		);
		if(!$this->stmt->execute([':name' => $name, ':version' => $version, ':description' => $description])) return false;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software') as $row) {
			return $row->id;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO software (name, version, description) VALUES (:name, :version, :description)'
		);
		if(!$this->stmt->execute([':name' => $name, ':version' => $version, ':description' => $description])) return false;
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerSoftware($computer_id, $software_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT id FROM computer_software WHERE computer_id = :computer_id AND software_id = :software_id LIMIT 1'
		);
		if(!$this->stmt->execute([':computer_id' => $computer_id, ':software_id' => $software_id])) return false;
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerSoftware') as $row) {
			return $row->id;
		}

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_software (computer_id, software_id) VALUES (:computer_id, :software_id)'
		);
		if(!$this->stmt->execute([':computer_id' => $computer_id, ':software_id' => $software_id])) return false;
		return $this->dbh->lastInsertId();
	}

	// Report Operations
	public function insertReportGroup($name, $parent_id) {
		if(empty($parent_id) || intval($parent_id) < 0) {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO report_group (name) VALUES (:name)'
			);
			$this->stmt->execute([':name' => $name]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO report_group (name, parent_report_group_id) VALUES (:name, :parent_report_group_id)'
			);
			$this->stmt->execute([':name' => $name, ':parent_report_group_id' => $parent_id]);
		}
		return $this->dbh->lastInsertId();
	}
	public function updateReportGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE report_group SET name = :name WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':name' => $name]);
	}
	public function deleteReportGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM report_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function selectAllReportGroupByParentReportGroupId($parent_id=null) {
		if($parent_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM report_group WHERE parent_report_group_id IS NULL ORDER BY name'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM report_group WHERE parent_report_group_id = :parent_report_group_id ORDER BY name'
			);
			$this->stmt->execute([':parent_report_group_id' => $parent_id]);
		}
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ReportGroup');
		foreach($reports as $report) {
			$report->name = LANG($report->name);
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		return $reports;
	}
	public function selectReportGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM report_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ReportGroup') as $row) {
			$row->name = LANG($row->name);
			return $row;
		}
	}
	public function insertReport($report_group_id, $name, $notes, $query) {
		if(empty($report_group_id) || intval($report_group_id) < 0) {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO report (report_group_id, name, notes, query) VALUES (NULL, :name, :notes, :query)'
			);
			$this->stmt->execute([
				':name' => $name,
				':notes' => $notes,
				':query' => $query,
			]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO report (report_group_id, name, notes, query) VALUES (:report_group_id, :name, :notes, :query)'
			);
			$this->stmt->execute([
				':report_group_id' => $report_group_id,
				':name' => $name,
				':notes' => $notes,
				':query' => $query,
			]);
		}
		return $this->dbh->lastInsertId();
	}
	public function updateReport($id, $report_group_id, $name, $notes, $query) {
		if(empty($report_group_id) || intval($report_group_id) < 0) {
			$this->stmt = $this->dbh->prepare(
				'UPDATE report SET report_group_id = NULL, name = :name, notes = :notes, query = :query WHERE id = :id'
			);
			$this->stmt->execute([
				':id' => $id,
				':name' => $name,
				':notes' => $notes,
				':query' => $query,
			]);
		} else {
			$this->stmt = $this->dbh->prepare(
				'UPDATE report SET report_group_id = :report_group_id, name = :name, notes = :notes, query = :query WHERE id = :id'
			);
			$this->stmt->execute([
				':id' => $id,
				':report_group_id' => $report_group_id,
				':name' => $name,
				':notes' => $notes,
				':query' => $query,
			]);
		}
		return $this->dbh->lastInsertId();
	}
	public function deleteReport($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM report WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function selectAllReport() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM report ORDER BY name');
		$this->stmt->execute();
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Report');
		foreach($reports as $report) {
			$report->name = LANG($report->name);
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		return $reports;
	}
	public function searchAllReport($name, $limit=null) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM report');
		$this->stmt->execute();
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Report');
		foreach($reports as $key => $report) {
			$report->name = LANG($report->name);
			if(strpos(strtoupper($report->name), strtoupper($name)) === false) unset($reports[$key]);
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		if($limit == null) return $reports;
		else return array_slice($reports, 0, intval($limit));
	}
	public function searchAllReportGroup($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM report_group WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ReportGroup');
	}
	public function selectAllReportByReportGroupId($report_group_id) {
		if($report_group_id == null) $sql = 'SELECT * FROM report WHERE report_group_id IS NULL ORDER BY name';
		else $sql = 'SELECT * FROM report WHERE report_group_id = :report_group_id ORDER BY name';
		$this->stmt = $this->dbh->prepare($sql);
		$this->stmt->execute([':report_group_id' => $report_group_id]);
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Report');
		foreach($reports as $report) {
			$report->name = LANG($report->name);
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		return $reports;
	}
	public function selectReport($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM report WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Report') as $row) {
			$row->name = LANG($row->name);
			return $row;
		}
	}
	public function executeReport($id) {
		$report = $this->selectReport($id);
		if(!$report) return false;
		$this->dbh->beginTransaction();
		$this->stmt = $this->dbh->prepare($report->query);
		$this->stmt->execute();
		$result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->dbh->rollBack();
		return $result;
	}

	// Log Operations
	public function insertLogEntry($level, $user, $object_id, $action, $data) {
		if($level < intval($this->settings->get('log-level'))) return;
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO log (level, host, user, object_id, action, data)
			VALUES (:level, :host, :user, :object_id, :action, :data)'
		);
		$this->stmt->execute([
			':level' => $level,
			':host' => $_SERVER['REMOTE_ADDR'] ?? 'local',
			':user' => $user,
			':object_id' => $object_id,
			':action' => $action,
			':data' => is_array($data) ? json_encode($data) : $data,
		]);
		return $this->dbh->lastInsertId();
	}
	public function selectAllLogEntryByObjectIdAndActions($object_id, $actions, $limit=Models\Log::DEFAULT_VIEW_LIMIT) {
		if(empty($actions)) throw new Exception('Log filter: no action specified!');
		if(!is_array($actions)) $actions = [$actions];
		$actionSql = '(';
		$params = [];
		$counter = 0;
		foreach($actions as $action) {
			$counter ++;
			if($actionSql != '(') $actionSql .= ' OR ';
			$actionSql .= 'action LIKE :action'.$counter;
			$params[':action'.$counter] = $action.'%';
		}
		$actionSql .= ')';
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM log WHERE '.($object_id===null ? 'object_id IS NULL' : ($object_id===false ? '1=1' : 'object_id = :object_id')).' AND '.$actionSql.' ORDER BY timestamp DESC '.($limit ? 'LIMIT '.intval($limit) : '')
		);
		if($object_id !== null && $object_id !== false) {
			$params[':object_id'] = $object_id;
		}
		$this->stmt->execute($params);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Log');
	}
	public function deleteLogEntryOlderThan($seconds) {
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM log WHERE timestamp < NOW() - INTERVAL '.intval($seconds).' SECOND'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}

	// Setting Operations
	public function selectSettingByKey($key) {
		try {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM setting WHERE `key` = :key LIMIT 1'
			);
			$this->stmt->execute([':key' => $key]);
			foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Setting') as $row) {
				return $row;
			}
		} catch(PDOException $ignored) {}
		return null;
	}
	public function selectAllSetting() {
		try {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM setting ORDER BY `key`'
			);
			$this->stmt->execute();
			return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Setting');
		} catch(PDOException $ignored) {}
		return [];
	}
	public function insertOrUpdateSettingByKey($key, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE setting SET id = LAST_INSERT_ID(id), `value` = :value WHERE `key` = :key LIMIT 1'
		);
		$this->stmt->execute([':key' => $key, ':value' => $value]);
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO setting (`key`, `value`) VALUES (:key, :value)'
		);
		if(!$this->stmt->execute([':key' => $key, ':value' => $value])) return false;
		return $this->dbh->lastInsertId();
	}
	public function deleteSettingByKey($key) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM setting WHERE `key` = :key'
		);
		return $this->stmt->execute([':key' => $key]);
	}

}
