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

	private $dbh;
	private $stmt;

	function __construct() {
		try {
			$this->dbh = new PDO(
				DB_TYPE.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';',
				DB_USER, DB_PASS,
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4')
			);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
		return implode($groupStrings, ' » ');
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
		return implode($groupStrings, ' » ');
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
		return implode($groupStrings, ' » ');
	}

	// Computer Operations
	public function searchAllComputer($hostname, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE hostname LIKE :hostname ORDER BY hostname ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':hostname' => '%'.$hostname.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Computer');
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
			'SELECT cp.id AS "id", c.id AS "computer_id", c.hostname AS "computer_hostname", p.id AS "package_id", p.package_family_id AS "package_family_id", pf.name AS "package_family_name", p.version AS "package_version", cp.installed_procedure AS "installed_procedure", cp.installed_by AS "installed_by", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN package p ON p.id = cp.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN computer c ON c.id = cp.computer_id
			WHERE cp.computer_id = :computer_id'
		);
		$this->stmt->execute([':computer_id' => $computer_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPackage');
	}
	public function insertComputer($hostname, $agent_version, $networks, $notes, $agent_key, $server_key) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer (hostname, agent_version, last_ping, last_update, os, os_version, os_license, os_locale, kernel_version, architecture, cpu, gpu, ram, serial, manufacturer, model, bios_version, remote_address, boot_type, secure_boot, domain, notes, agent_key, server_key)
			VALUES (:hostname, :agent_version, NULL, NULL, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", :notes, :agent_key, :server_key)'
		);
		$this->stmt->execute([
			':hostname' => $hostname,
			':agent_version' => $agent_version,
			':notes' => $notes,
			':agent_key' => $agent_key,
			':server_key' => $server_key,
		]);
		$computer_id = $this->dbh->lastInsertId();
		foreach($networks as $index => $network) {
			$this->insertOrUpdateComputerNetwork(
				$computer_id,
				$index,
				$network['addr'],
				$network['netmask'],
				$network['broadcast'],
				$network['mac'],
				$network['interface']
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
	public function updateComputerPing($id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET last_ping = CURRENT_TIMESTAMP WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
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
			COMPUTER_KEEP_INACTIVE_SCREENS
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
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM event_query_rule'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\EventQueryRule');
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
	public function insertOrUpdateComputerEvent($computer_id, $timestamp, $level, $event_id, $data) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_event (computer_id, timestamp, level, event_id, data) VALUES (:computer_id, :timestamp, :level, :event_id, :data)'
		);
		if(!$this->stmt->execute([
			':computer_id' => $computer_id,
			':timestamp' => $timestamp,
			':level' => $level,
			':event_id' => $event_id,
			':data' => $data,
		])) return false;
	}
	public function deleteComputerEventEntryOlderThan($seconds) {
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
	public function updatePackageFamily($id, $name, $notes, $icon) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_family SET name = :name, notes = :notes, icon = :icon WHERE id = :id'
		);
		$this->stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$this->stmt->bindParam(':name', $name, PDO::PARAM_STR);
		$this->stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
		$this->stmt->bindParam(':icon', $icon, PDO::PARAM_LOB);
		return $this->stmt->execute();
	}
	public function insertPackage($package_family_id, $version, $author, $notes, $install_procedure, $install_procedure_success_return_codes, $install_procedure_post_action, $uninstall_procedure, $uninstall_procedure_success_return_codes, $download_for_uninstall, $uninstall_procedure_post_action, $compatible_os, $compatible_os_version) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO package (package_family_id, version, author, notes, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, uninstall_procedure, uninstall_procedure_success_return_codes, download_for_uninstall, uninstall_procedure_post_action, compatible_os, compatible_os_version)
			VALUES (:package_family_id, :version, :author, :notes, :install_procedure, :install_procedure_success_return_codes, :install_procedure_post_action, :uninstall_procedure, :uninstall_procedure_success_return_codes, :download_for_uninstall, :uninstall_procedure_post_action, :compatible_os, :compatible_os_version)'
		);
		$this->stmt->execute([
			':package_family_id' => $package_family_id,
			':version' => $version,
			':author' => $author,
			':notes' => $notes,
			':install_procedure' => $install_procedure,
			':install_procedure_success_return_codes' => $install_procedure_success_return_codes,
			':install_procedure_post_action' => $install_procedure_post_action,
			':uninstall_procedure' => $uninstall_procedure,
			':uninstall_procedure_success_return_codes' => $uninstall_procedure_success_return_codes,
			':download_for_uninstall' => $download_for_uninstall,
			':uninstall_procedure_post_action' => $uninstall_procedure_post_action,
			':compatible_os' => $compatible_os,
			':compatible_os_version' => $compatible_os_version,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updatePackage($id, $package_family_id, $author, $version, $compatible_os, $compatible_os_version, $notes, $install_procedure, $install_procedure_success_return_codes, $install_procedure_post_action, $uninstall_procedure, $uninstall_procedure_success_return_codes, $uninstall_procedure_post_action, $download_for_uninstall) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, package_family_id = :package_family_id, author = :author, version = :version, compatible_os = :compatible_os, compatible_os_version = :compatible_os_version, notes = :notes, install_procedure = :install_procedure, install_procedure_success_return_codes = :install_procedure_success_return_codes, install_procedure_post_action = :install_procedure_post_action, uninstall_procedure = :uninstall_procedure, uninstall_procedure_success_return_codes = :uninstall_procedure_success_return_codes, uninstall_procedure_post_action = :uninstall_procedure_post_action, download_for_uninstall = :download_for_uninstall WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':package_family_id' => $package_family_id,
			':author' => $author,
			':version' => $version,
			':compatible_os' => $compatible_os,
			':compatible_os_version' => $compatible_os_version,
			':notes' => $notes,
			':install_procedure' => $install_procedure,
			':install_procedure_success_return_codes' => $install_procedure_success_return_codes,
			':install_procedure_post_action' => $install_procedure_post_action,
			':uninstall_procedure' => $uninstall_procedure,
			':uninstall_procedure_success_return_codes' => $uninstall_procedure_success_return_codes,
			':uninstall_procedure_post_action' => $uninstall_procedure_post_action,
			':download_for_uninstall' => $download_for_uninstall,
		]);
	}
	public function insertComputerPackage($package_id, $computer_id, $author, $procedure) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_package (id, package_id, computer_id, installed_by, installed_procedure, installed)
			(SELECT cp2.id, cp2.package_id, cp2.computer_id, cp2.installed_by, cp2.installed_procedure, cp2.installed FROM computer_package cp2 WHERE cp2.package_id=:package_id AND cp2.computer_id=:computer_id)
			UNION (SELECT null, :package_id, :computer_id, :installed_by, :installed_procedure, CURRENT_TIMESTAMP FROM DUAL) LIMIT 1
			ON DUPLICATE KEY UPDATE computer_package.id=LAST_INSERT_ID(computer_package.id), computer_package.installed_by=:installed_by, computer_package.installed_procedure=:installed_procedure, computer_package.installed=CURRENT_TIMESTAMP'
		);
		$this->stmt->execute([
			':package_id' => $package_id,
			':computer_id' => $computer_id,
			':installed_by' => $author,
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
			'SELECT cp.id AS "id", p.id AS "package_id", p.package_family_id AS "package_family_id", c.id AS "computer_id", c.hostname AS "computer_hostname", cp.installed_procedure AS "installed_procedure", cp.installed_by AS "installed_by", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN computer c ON c.id = cp.computer_id
			INNER JOIN package p ON p.id = cp.package_id
			WHERE cp.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $package_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ComputerPackage');
	}
	public function selectAllPackageByIdAndPackageGroupId($package_id, $package_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_group_member pgm
			INNER JOIN package p ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pgm.package_id = :package_id AND pgm.package_group_id = :package_group_id'
		);
		$this->stmt->execute([':package_id' => $package_id, ':package_group_id' => $package_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackageByPackageFamilyId($package_family_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
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
			'SELECT p.*, pf.name AS "package_family_name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id'
			.($orderByCreated ? ' ORDER BY p.created DESC' : ' ORDER BY pf.name ASC')
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Package');
	}
	public function selectAllPackageFamily($binaryAsBase64=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pf.*, (SELECT COUNT(id) FROM package p WHERE p.package_family_id = pf.id) AS "package_count",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created DESC LIMIT 1) AS "newest_package_created",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created ASC LIMIT 1) AS "oldest_package_created"
			FROM package_family pf ORDER BY newest_package_created DESC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageFamily', [$binaryAsBase64]);
	}
	public function selectPackageFamily($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM package_family WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\PackageFamily') as $row) {
			return $row;
		}
	}
	public function selectPackageFamilyByName($name) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM package_family WHERE name = :name');
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
				'SELECT p.*, pf.name AS "package_family_name" FROM package p
				INNER JOIN package_family pf ON pf.id = p.package_family_id
				WHERE p.id = :id'
			);
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT p.*, pf.name AS "package_family_name", pf.icon AS "package_family_icon" FROM package p
				INNER JOIN package_family pf ON pf.id = p.package_family_id
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
			'SELECT p.*, pf.name AS "package_family_name", pgm.sequence AS "package_group_member_sequence"
			FROM package p
			INNER JOIN package_group_member pgm ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
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
	public function reorderPackageInGroup($package_group_id, $old_seq, $new_seq) {
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
		$this->stmt->bindParam(':oldpos', intval($old_seq), PDO::PARAM_INT);
		$this->stmt->bindParam(':newpos', intval($new_seq), PDO::PARAM_INT);
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

	// Job Operations
	public function insertJobContainer($name, $author, $start_time, $end_time, $notes, $wol_sent, $shutdown_waked_after_completion, $sequence_mode, $priority, $agent_ip_ranges, $self_service) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job_container (name, author, start_time, end_time, notes, wol_sent, shutdown_waked_after_completion, sequence_mode, priority, agent_ip_ranges, self_service)
			VALUES (:name, :author, :start_time, :end_time, :notes, :wol_sent, :shutdown_waked_after_completion, :sequence_mode, :priority, :agent_ip_ranges, :self_service)'
		);
		$this->stmt->execute([
			':name' => $name,
			':author' => $author,
			':start_time' => $start_time,
			':end_time' => $end_time,
			':notes' => $notes,
			':wol_sent' => $wol_sent,
			':shutdown_waked_after_completion' => $shutdown_waked_after_completion,
			':sequence_mode' => $sequence_mode,
			':priority' => $priority,
			':agent_ip_ranges' => $agent_ip_ranges,
			':self_service' => $self_service,
		]);
		return $this->dbh->lastInsertId();
	}
	public function insertStaticJob($job_container_id, $computer_id, $package_id, $procedure, $success_return_codes, $is_uninstall, $download, $post_action, $post_action_timeout, $sequence, $state=0) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job_container_job (job_container_id, computer_id, package_id, `procedure`, success_return_codes, is_uninstall, download, post_action, post_action_timeout, sequence, state, message)
			VALUES (:job_container_id, :computer_id, :package_id, :procedure, :success_return_codes, :is_uninstall, :download, :post_action, :post_action_timeout, :sequence, :state, "")'
		);
		$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':computer_id' => $computer_id,
			':package_id' => $package_id,
			':procedure' => $procedure,
			':success_return_codes' => $success_return_codes,
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
			'SELECT * FROM job_container WHERE id = :id'
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
			'SELECT jc.*, (SELECT MAX(execution_finished) FROM job_container_job j WHERE j.job_container_id = jc.id) AS "execution_finished"
			FROM job_container jc '.($self_service===null?'':($self_service?'WHERE self_service = 1':'WHERE self_service = 0'))
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
			'SELECT j.*, jc.start_time AS "job_container_start_time", jc.author AS "job_container_author" FROM job_container_job j
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
			'SELECT j.*, dr.author AS "deployment_rule_author", dr.sequence_mode AS "deployment_rule_sequence_mode" FROM deployment_rule_job j
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
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", jc.enabled AS "job_container_enabled", jc.priority AS "job_container_priority", jc.sequence_mode AS "job_container_sequence_mode", jc.agent_ip_ranges AS "job_container_agent_ip_ranges", jc.self_service AS "job_container_self_service",
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
				if($deployment_rule->auto_uninstall && $state != Models\Job::STATE_ALREADY_INSTALLED && $state != Models\Job::STATE_OS_INCOMPATIBLE && $state != Models\Job::STATE_PACKAGE_CONFLICT) {
					$dynamic_jobs = array_merge(
						$dynamic_jobs,
						$this->compileDynamicUninstallJobs($deployment_rule, $package->package_family_id, $this->selectAllComputerPackageByComputerId($computer->id), $created_uninstall_jobs, $sequence)
					);
				}
				// add dynamic job
				$dynamic_jobs[] = Models\DynamicJob::__constructWithValues(
					$deployment_rule->id, $deployment_rule->name, $deployment_rule->author, $deployment_rule->enabled, $deployment_rule->priority,
					$computer->id, $computer->hostname,
					$package->id, $package->version, $package->package_family_name, $package->install_procedure, $package->install_procedure_success_return_codes,
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
				'INSERT INTO deployment_rule_job (id, deployment_rule_id, computer_id, package_id, `procedure`, success_return_codes, is_uninstall, download, post_action, post_action_timeout, sequence, state, return_code, message)
				(SELECT drj2.id, drj2.deployment_rule_id, drj2.computer_id, drj2.package_id, drj2.`procedure`, drj2.success_return_codes, drj2.is_uninstall, drj2.download, drj2.post_action, drj2.post_action_timeout, drj2.sequence, drj2.state, drj2.return_code, drj2.message FROM deployment_rule_job drj2 WHERE drj2.deployment_rule_id=:deployment_rule_id AND drj2.computer_id=:computer_id AND drj2.package_id=:package_id AND drj2.is_uninstall=:is_uninstall)
				UNION (SELECT null, :deployment_rule_id, :computer_id, :package_id, :procedure, :success_return_codes, :is_uninstall, :download, :post_action, :post_action_timeout, :sequence, :state, :return_code, :message FROM DUAL) LIMIT 1
				ON DUPLICATE KEY UPDATE deployment_rule_job.id=LAST_INSERT_ID(deployment_rule_job.id), deployment_rule_job.`procedure`=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.`procedure`,:procedure), deployment_rule_job.success_return_codes=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.success_return_codes,:success_return_codes), deployment_rule_job.download=IF(deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' OR deployment_rule_job.state='.Models\Job::STATE_FAILED.',deployment_rule_job.download,:download), deployment_rule_job.post_action=:post_action, deployment_rule_job.post_action_timeout=:post_action_timeout, deployment_rule_job.sequence=:sequence, deployment_rule_job.state=IF((deployment_rule_job.state='.Models\Job::STATE_SUCCEEDED.' AND :state='.Models\Job::STATE_ALREADY_INSTALLED.') OR (deployment_rule_job.state='.Models\Job::STATE_FAILED.' AND :state='.Models\Job::STATE_WAITING_FOR_AGENT.') OR (deployment_rule_job.state='.Models\Job::STATE_DOWNLOAD_STARTED.' AND :state='.Models\Job::STATE_WAITING_FOR_AGENT.') OR (deployment_rule_job.state='.Models\Job::STATE_EXECUTION_STARTED.' AND :state='.Models\Job::STATE_WAITING_FOR_AGENT.'), deployment_rule_job.state, :state)'
			);
			$this->stmt->execute([
				':deployment_rule_id' => $job->deployment_rule_id,
				':computer_id' => $job->computer_id,
				':package_id' => $job->package_id,
				':procedure' => $job->procedure,
				':success_return_codes' => $job->success_return_codes,
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
					$deployment_rule->id, $deployment_rule->name, $deployment_rule->author, $deployment_rule->enabled, $deployment_rule->priority,
					$cp->computer_id, $cp->computer_hostname,
					$cpp->id, $cpp->version, $cpp->package_family_name, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes,
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
			if(!$tmpComputer->isOnline())
				$this->setWolShutdownStaticJobInJobContainer($job_container_id, $tmpComputer->id, $j->max_sequence);
		}
	}
	private function selectAllLastComputerStaticJobByJobContainer($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT computer_id, MAX(sequence) AS "max_sequence" FROM job
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
				'UPDATE job_container_job SET state = :state, return_code = :return_code, message = :message, '.$timestamp_update.'
				WHERE id = :id'
			);
			if(!$this->stmt->execute([
				':id' => $job->id,
				':state' => $job->state,
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
				'UPDATE deployment_rule_job SET state = :state, return_code = :return_code, message = :message, '.$timestamp_update.' WHERE id = :id'
			);
			if(!$this->stmt->execute([
				':id' => $job->id,
				':state' => $job->state,
				':return_code' => $job->return_code,
				':message' => $job->message,
			])) return false;
		}
	}
	public function updateJobContainer($id, $name, $enabled, $start_time, $end_time, $notes, $wol_sent, $shutdown_waked_after_completion, $sequence_mode, $priority, $agent_ip_ranges) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container
			SET name = :name, enabled = :enabled, start_time = :start_time, end_time = :end_time, notes = :notes, wol_sent = :wol_sent, shutdown_waked_after_completion = :shutdown_waked_after_completion, sequence_mode = :sequence_mode, priority = :priority, agent_ip_ranges = :agent_ip_ranges
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
			'SELECT * FROM deployment_rule'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\DeploymentRule');
	}
	public function selectDeploymentRule($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM deployment_rule WHERE id = :id'
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
	public function insertDeploymentRule($name, $notes, $author, $enabled, $computer_group_id, $package_group_id, $priority, $auto_uninstall) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO deployment_rule (name, author, enabled, computer_group_id, package_group_id, notes, priority, auto_uninstall)
			VALUES (:name, :author, :enabled, :computer_group_id, :package_group_id, :notes, :priority, :auto_uninstall)'
		);
		if(!$this->stmt->execute([
			':name' => $name,
			':author' => $author,
			':enabled' => $enabled,
			':computer_group_id' => $computer_group_id,
			':package_group_id' => $package_group_id,
			':notes' => $notes,
			':priority' => $priority,
			':auto_uninstall' => $auto_uninstall,
		])) return false;
		$insertId = $this->dbh->lastInsertId();
		$this->evaluateDeploymentRule($insertId);
		return $insertId;
	}
	public function updateDeploymentRule($id, $name, $notes, $enabled, $computer_group_id, $package_group_id, $priority, $auto_uninstall) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE deployment_rule
			SET name = :name, enabled = :enabled, computer_group_id = :computer_group_id, package_group_id = :package_group_id, notes = :notes, priority = :priority, auto_uninstall = :auto_uninstall
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
			':auto_uninstall' => $auto_uninstall,
		])) return false;
		return $this->evaluateDeploymentRule($id);
	}
	public function updateDynamicJob($ids, $state, $return_code, $message) {
		list($in_placeholders, $in_params) = self::compileSqlInValues($ids);
		$this->stmt = $this->dbh->prepare(
			'UPDATE deployment_rule_job SET state = :state, return_code = :return_code, message = :message WHERE id IN ('.$in_placeholders.')'
		);
		$this->stmt->execute(array_merge($in_params, [':state'=>$state, ':return_code'=>$return_code, ':message'=>$message]));
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
		if(empty(trim($uid))) $uid = null;
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
			'UPDATE domain_user SET domain_user_role_id = NULL, password = NULL, ldap = 0 WHERE ldap = 1 AND id NOT IN ('.$in_placeholders.')'
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
			FROM software s INNER JOIN computer_software cs ON cs.id = (
				SELECT cs3.id FROM computer_software AS cs3
				INNER JOIN computer c ON cs3.computer_id = c.id
				WHERE cs3.software_id = s.id
				AND c.os LIKE "%Windows%"
				LIMIT 1
			)
			GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software');
	}
	public function selectAllSoftwareByComputerOsMacOs() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s INNER JOIN computer_software cs ON cs.id = (
				SELECT cs3.id FROM computer_software AS cs3
				INNER JOIN computer c ON cs3.computer_id = c.id
				WHERE cs3.software_id = s.id
				AND c.os LIKE "%macOS%"
				LIMIT 1
			)
			GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Software');
	}
	public function selectAllSoftwareByComputerOsOther() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s INNER JOIN computer_software cs ON cs.id = (
				SELECT cs3.id FROM computer_software AS cs3
				INNER JOIN computer c ON cs3.computer_id = c.id
				WHERE cs3.software_id = s.id
				AND c.os NOT LIKE "%Windows%" AND c.os NOT LIKE "%macOS%"
				LIMIT 1
			)
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
		$this->stmt = $this->dbh->prepare(
			'UPDATE software SET id = LAST_INSERT_ID(id)
			WHERE BINARY name = :name AND BINARY version = :version AND description = :description LIMIT 1'
		);
		if(!$this->stmt->execute([':name' => $name, ':version' => $version, ':description' => $description])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO software (name, version, description) VALUES (:name, :version, :description)'
		);
		if(!$this->stmt->execute([':name' => $name, ':version' => $version, ':description' => $description])) return false;
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerSoftware($computer_id, $software_id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_software SET id = LAST_INSERT_ID(id)
			WHERE computer_id = :computer_id AND software_id = :software_id LIMIT 1'
		);
		if(!$this->stmt->execute([':computer_id' => $computer_id, ':software_id' => $software_id])) return false;
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

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
		if($level < LOG_LEVEL) return;
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
			'SELECT * FROM log WHERE '.($object_id===null ? 'object_id IS NULL' : $object_id===false ? '1=1' : 'object_id = :object_id').' AND '.$actionSql.' ORDER BY timestamp DESC '.($limit ? 'LIMIT '.intval($limit) : '')
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

}
