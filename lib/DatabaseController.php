<?php

class DatabaseController {

	/*
		 Class DatabaseController
		 Database Abstraction Layer

		 Handles direct database access.
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

	public function getDbHandle() {
		return $this->dbh;
	}
	public function getLastStatement() {
		return $this->stmt;
	}

	public function existsSchema() {
		$this->stmt = $this->dbh->prepare('SHOW TABLES LIKE "computer"');
		$this->stmt->execute();
		return ($this->stmt->rowCount() == 1);
	}

	public function getStats() {
		$this->stmt = $this->dbh->prepare(
			'SELECT
			(SELECT count(id) FROM domain_user) AS "domain_users",
			(SELECT count(id) FROM computer) AS "computers",
			(SELECT count(id) FROM package) AS "packages",
			(SELECT count(id) FROM job) AS "jobs",
			(SELECT count(id) FROM job_container) AS "job_containers",
			(SELECT count(id) FROM report) AS "reports"
			FROM DUAL'
		);
		$this->stmt->execute();
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Stat') as $row) {
			return $row;
		}
	}

	// Computer Operations
	public function getAllComputerByName($hostname, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE hostname LIKE :hostname ORDER BY hostname ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':hostname' => '%'.$hostname.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getComputerByName($hostname) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE hostname = :hostname'
		);
		$this->stmt->execute([':hostname' => $hostname]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer') as $row) {
			return $row;
		}
	}
	public function getComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer') as $row) {
			return $row;
		}
	}
	public function getComputerNetwork($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_network WHERE computer_id = :cid'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerNetwork');
	}
	public function getComputerScreen($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_screen WHERE computer_id = :cid ORDER BY active DESC'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerScreen');
	}
	public function getComputerPrinter($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_printer WHERE computer_id = :cid'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerPrinter');
	}
	public function getComputerPartition($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_partition WHERE computer_id = :cid'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerPartition');
	}
	public function getComputerSoftware($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cs.id AS "id", s.id AS "software_id", s.name AS "software_name", s.version AS "software_version", s.description AS "software_description", cs.installed AS "installed"
			FROM computer_software cs
			INNER JOIN software s ON cs.software_id = s.id
			WHERE cs.computer_id = :cid ORDER BY s.name'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerSoftware');
	}
	public function getComputerPackage($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cp.id AS "id", p.id AS "package_id", p.package_family_id AS "package_family_id", pf.name AS "package_family_name", p.version AS "package_version", cp.installed_procedure AS "installed_procedure", cp.installed_by AS "installed_by", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN package p ON p.id = cp.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE cp.computer_id = :cid'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerPackage');
	}
	public function getAllComputer() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer ORDER BY hostname ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function addComputer($hostname, $agent_version, $networks, $notes, $agent_key, $server_key) {
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
		$cid = $this->dbh->lastInsertId();
		foreach($networks as $index => $network) {
			$this->insertOrUpdateComputerNetwork(
				$cid,
				$index,
				$network['addr'],
				$network['netmask'],
				$network['broadcast'],
				$network['mac'],
				$network['interface']
			);
		}
		return $cid;
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
	public function updateComputerAgentkey($id, $agent_key) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET agent_key = :agent_key WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':agent_key' => $agent_key]);
	}
	public function updateComputerServerkey($id, $server_key) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET server_key = :server_key WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':server_key' => $server_key]);
	}
	public function updateComputerInventoryValues($id, $hostname, $os, $os_version, $os_license, $os_locale, $kernel_version, $architecture, $cpu, $gpu, $ram, $agent_version, $remote_address, $serial, $manufacturer, $model, $bios_version, $uptime, $boot_type, $secure_boot, $domain, $networks, $screens, $printers, $partitions, $software, $logins) {
		$this->dbh->beginTransaction();

		// update general info
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET hostname = :hostname, os = :os, os_version = :os_version, os_license = :os_license, os_locale = :os_locale, kernel_version = :kernel_version, architecture = :architecture, cpu = :cpu, gpu = :gpu, ram = :ram, agent_version = :agent_version, remote_address = :remote_address, serial = :serial, manufacturer = :manufacturer, model = :model, bios_version = :bios_version, uptime = :uptime, boot_type = :boot_type, secure_boot = :secure_boot, domain = :domain, last_ping = CURRENT_TIMESTAMP, last_update = CURRENT_TIMESTAMP, force_update = 0 WHERE id = :id'
		);
		if(!$this->stmt->execute([
			':id' => $id,
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

		///// update networks
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
				$network['interface'] ?? '?',
			);
			$nids[] = $nid;
		}
		// remove networks which can not be found in agent output
		list($in_placeholders, $in_params) = self::compileSqlInValues($nids);
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_network WHERE computer_id = :computer_id AND id NOT IN ('.$in_placeholders.')'
		);
		if(!$this->stmt->execute(array_merge([':computer_id' => $id], $in_params))) return false;

		///// update screens
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

		///// update printers
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

		///// update partitions
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

		///// update software
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

		///// insert new domain user logins
		foreach($logins as $l) {
			if(empty($l['username'])) continue;
			$did = $this->insertOrUpdateDomainUser($l['guid']??null, $l['username'], $l['display_name']??'');
			if(!$this->insertOrUpdateDomainUserLogon($id, $did, $l['console'], $l['timestamp'])) return false;
		}
		// old logins, which are not present in local client logs anymore, should NOT automatically be deleted
		// instead, old logins are cleaned up by the server's housekeeping process after a certain amount of time (defined in configuration)

		$this->dbh->commit();
		return true;
	}
	private function insertOrUpdateComputerPartition($cid, $device, $mountpoint, $filesystem, $size, $free) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_partition (id, computer_id, device, mountpoint, filesystem, size, free)
			(SELECT id, computer_id, device, mountpoint, filesystem, size, free FROM computer_partition WHERE computer_id=:computer_id AND device=:device AND mountpoint=:mountpoint AND filesystem=:filesystem
			UNION SELECT null, :computer_id, :device, :mountpoint, :filesystem, :size, :free FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), size=:size, free=:free'
		);
		$this->stmt->execute([
			':computer_id' => $cid,
			':device' => $device,
			':mountpoint' => $mountpoint,
			':filesystem' => $filesystem,
			':size' => $size,
			':free' => $free,
		]);
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerPrinter($cid, $name, $driver, $paper, $dpi, $uri, $status) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_printer (id, computer_id, name, driver, paper, dpi, uri, status)
			(SELECT id, computer_id, name, driver, paper, dpi, uri, status FROM computer_printer WHERE computer_id=:computer_id AND name=:name AND driver=:driver AND paper=:paper AND dpi=:dpi AND uri=:uri
			UNION SELECT null, :computer_id, :name, :driver, :paper, :dpi, :uri, :status FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), status=:status'
		);
		$this->stmt->execute([
			':computer_id' => $cid,
			':name' => $name,
			':driver' => $driver,
			':paper' => $paper,
			':dpi' => $dpi,
			':uri' => $uri,
			':status' => $status,
		]);
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerScreen($cid, $name, $manufacturer, $type, $resolution, $size, $manufactured, $serialno) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_screen (id, computer_id, name, manufacturer, type, resolution, size, manufactured, serialno, active)
			(SELECT id, computer_id, name, manufacturer, type, resolution, size, manufactured, serialno, active FROM computer_screen WHERE computer_id=:computer_id AND name=:name AND manufacturer=:manufacturer AND type=:type AND size=:size AND manufactured=:manufactured AND serialno=:serialno
			UNION SELECT null, :computer_id, :name, :manufacturer, :type, :resolution, :size, :manufactured, :serialno, 1 FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), resolution=:resolution, active=1'
		);
		$this->stmt->execute([
			':computer_id' => $cid,
			':name' => $name,
			':manufacturer' => $manufacturer,
			':type' => $type,
			':resolution' => $resolution,
			':size' => $size,
			':manufactured' => $manufactured,
			':serialno' => $serialno,
		]);
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerNetwork($cid, $nic_number, $address, $netmask, $broadcast, $mac, $interface) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_network (id, computer_id, nic_number, address, netmask, broadcast, mac, interface)
			(SELECT id, computer_id, nic_number, address, netmask, broadcast, mac, interface FROM computer_network WHERE computer_id=:computer_id AND nic_number=:nic_number AND mac=:mac AND interface=:interface
			UNION SELECT null, :computer_id, :nic_number, :address, :netmask, :broadcast, :mac, :interface FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), address=:address, netmask=:netmask, broadcast=:broadcast'
		);
		$this->stmt->execute([
			':computer_id' => $cid,
			':nic_number' => $nic_number,
			':address' => $address,
			':netmask' => $netmask,
			':broadcast' => $broadcast,
			':mac' => $mac,
			':interface' => $interface,
		]);
		return $this->dbh->lastInsertId();
	}
	public function removeComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer WHERE id = :computer_id'
		);
		$this->stmt->execute([ ':computer_id' => $id ]);
		return ($this->stmt->rowCount() == 1);
	}

	public function getComputerGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->getComputerGroup($currentGroupId);
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
	public function getComputerGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerGroup') as $row) {
			return $row;
		}
	}
	public function getAllComputerGroup($parent_id=null) {
		if($parent_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_group WHERE parent_computer_group_id IS NULL ORDER BY name'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_group WHERE parent_computer_group_id = :parent_computer_group_id ORDER BY name'
			);
			$this->stmt->execute([':parent_computer_group_id' => $parent_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerGroup');
	}
	public function getComputerBySoftwareName($name) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", s.id AS "software_id", s.name AS "software_name", s.version AS "software_version"
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			INNER JOIN software s ON cs.software_id = s.id
			WHERE s.name = :name
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':name' => $name]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getComputerBySoftware($sid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", c.os AS "os", c.os_version AS "os_version", s.id AS "software_id", s.name AS "software_name", s.version AS "software_version"
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			INNER JOIN software s ON cs.software_id = s.id
			WHERE cs.software_id = :id
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':id' => $sid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getComputerByGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.* FROM computer c
			INNER JOIN computer_group_member cgm ON c.id = cgm.computer_id
			WHERE cgm.computer_group_id = :id
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getComputerByComputerAndGroup($cid, $gid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.* FROM computer_group_member cgm
			INNER JOIN computer c ON c.id = cgm.computer_id
			WHERE cgm.computer_id = :cid AND cgm.computer_group_id = :gid'
		);
		$this->stmt->execute([':cid' => $cid, ':gid' => $gid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getGroupByComputer($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cg.id AS "id", cg.name AS "name", cg.parent_computer_group_id AS "parent_computer_group_id"
			FROM computer_group_member cgm
			INNER JOIN computer_group cg ON cg.id = cgm.computer_group_id
			WHERE cgm.computer_id = :cid
			ORDER BY cg.name'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerGroup');
	}
	public function addComputerGroup($name, $parent_id=null) {
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
	public function renameComputerGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer_group SET name = :name WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id, ':name' => $name]);
		return $this->dbh->lastInsertId();
	}
	public function removeComputerGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function addComputerToGroup($cid, $gid) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_group_member (computer_id, computer_group_id) VALUES (:cid, :gid)'
		);
		$this->stmt->execute([':cid' => $cid, ':gid' => $gid]);
		return $this->dbh->lastInsertId();
	}
	public function removeComputerFromGroup($cid, $gid) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_group_member WHERE computer_id = :cid AND computer_group_id = :gid'
		);
		return $this->stmt->execute([':cid' => $cid, ':gid' => $gid]);
	}

	// Package Operations
	public function addPackageFamily($name, $notes) {
		$this->stmt = $this->dbh->prepare('INSERT INTO package_family (name, notes) VALUES (:name, :notes)');
		$this->stmt->execute([
			':name' => $name,
			':notes' => $notes,
		]);
		return $this->dbh->lastInsertId();
	}
	public function addPackage($package_family_id, $version, $author, $notes, $install_procedure, $install_procedure_success_return_codes, $install_procedure_post_action, $uninstall_procedure, $uninstall_procedure_success_return_codes, $download_for_uninstall, $uninstall_procedure_post_action, $compatible_os, $compatible_os_version) {
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
	public function addPackageToComputer($pid, $cid, $author, $procedure) {
		$this->dbh->beginTransaction();
		$this->removeComputerAssignedPackageByIds($cid, $pid);
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_package (package_id, computer_id, installed_by, installed_procedure)
			VALUES (:package_id, :computer_id, :installed_by, :installed_procedure)'
		);
		$this->stmt->execute([
			':package_id' => $pid,
			':computer_id' => $cid,
			':installed_by' => $author,
			':installed_procedure' => $procedure,
		]);
		$this->dbh->commit();
		return $this->dbh->lastInsertId();
	}
	public function getPackageComputer($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cp.id AS "id", c.id AS "computer_id", c.hostname AS "computer_hostname", cp.installed_procedure AS "installed_procedure", cp.installed_by AS "installed_by", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN computer c ON c.id = cp.computer_id
			WHERE cp.package_id = :pid'
		);
		$this->stmt->execute([':pid' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerPackage');
	}
	public function getPackageByPackageAndGroup($pid, $gid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_group_member pgm
			INNER JOIN package p ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pgm.package_id = :pid AND pgm.package_group_id = :gid'
		);
		$this->stmt->execute([':pid' => $pid, ':gid' => $gid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getPackageByFamily($fid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE p.package_family_id = :package_family_id'
		);
		$this->stmt->execute([':package_family_id' => $fid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getDependentPackages($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_dependency pd
			INNER JOIN package p ON p.id = pd.dependent_package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pd.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getDependentForPackages($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_dependency pd
			INNER JOIN package p ON p.id = pd.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pd.dependent_package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getConflictPackages($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package_conflict pc
			INNER JOIN package p ON p.id = pc.conflict_package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pc.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getAllPackage($orderByCreated=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id'
			.($orderByCreated ? ' ORDER BY p.created DESC' : ' ORDER BY pf.name ASC')
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getAllPackageFamily($binaryAsBase64=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pf.*, (SELECT COUNT(id) FROM package p WHERE p.package_family_id = pf.id) AS "package_count",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created DESC LIMIT 1) AS "newest_package_created",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created ASC LIMIT 1) AS "oldest_package_created"
			FROM package_family pf ORDER BY newest_package_created DESC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageFamily', [$binaryAsBase64]);
	}
	public function getPackageFamily($id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM package_family WHERE id = :id');
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageFamily') as $row) {
			return $row;
		}
	}
	public function getPackageFamilyByName($name) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM package_family WHERE name = :name');
		$this->stmt->execute([':name' => $name]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageFamily') as $row) {
			return $row;
		}
	}
	public function getGroupByPackage($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT pg.id AS "id", pg.name AS "name", pg.parent_package_group_id AS "parent_package_group_id"
			FROM package_group_member pgm
			INNER JOIN package_group pg ON pg.id = pgm.package_group_id
			WHERE pgm.package_id = :pid
			ORDER BY pg.name'
		);
		$this->stmt->execute([':pid' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageGroup');
	}
	public function getAllPackageGroup($parent_id=null) {
		if($parent_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM package_group WHERE parent_package_group_id IS NULL ORDER BY name'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM package_group WHERE parent_package_group_id = :parent_package_group_id ORDER BY name'
			);
			$this->stmt->execute([':parent_package_group_id' => $parent_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageGroup');
	}
	public function getPackage($id, $binaryAsBase64=false) {
		if($binaryAsBase64 === null) {
			// do not fetch icons if not necessary
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
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package', [$binaryAsBase64]) as $row) {
			return $row;
		}
	}
	public function getAllPackageFamilyByName($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT id, name FROM package_family
			WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageFamily');
	}
	public function getPackageByNameVersion($name, $version) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pf.name = :name AND p.version = :version'
		);
		$this->stmt->execute([':name' => $name, ':version' => $version]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getComputerAssignedPackage($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_package WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerPackage') as $row) {
			return $row;
		}
	}
	public function getPackageGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->getPackageGroup($currentGroupId);
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
	public function getPackageGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM package_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageGroup') as $row) {
			return $row;
		}
	}
	public function addPackageGroup($name, $parent_id=null) {
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
	public function renamePackageGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_group SET name = :name WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id, ':name' => $name]);
		return $this->dbh->lastInsertId();
	}
	public function getPackageByGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name", pgm.sequence AS "package_group_member_sequence"
			FROM package p
			INNER JOIN package_group_member pgm ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pgm.package_group_id = :id
			ORDER BY pgm.sequence'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function addPackageToGroup($pid, $gid) {
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
		$this->stmt->execute([':package_id' => $pid, ':package_group_id' => $gid, ':sequence' => $seq]);
		return $this->dbh->lastInsertId();
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
		return $this->refactorPackageGroupOrder($package_group_id);
	}
	public function refactorPackageGroupOrder($gid) {
		$seq = 1;
		$this->dbh->beginTransaction();
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM package_group_member WHERE package_group_id = :package_group_id ORDER BY sequence'
		);
		$this->stmt->execute([':package_group_id' => $gid]);
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
	public function addPackageDependency($pid, $dpid) {
		$this->dbh->beginTransaction();
		$this->removePackageDependency($pid, $dpid);
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO package_dependency (package_id, dependent_package_id)
			VALUES (:package_id, :dependent_package_id)'
		);
		$this->stmt->execute([':package_id' => $pid, ':dependent_package_id' => $dpid]);
		$this->dbh->commit();
		return $this->dbh->lastInsertId();
	}
	public function removePackageDependency($pid, $dpid) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_dependency
			WHERE package_id = :package_id AND dependent_package_id = :dependent_package_id'
		);
		$this->stmt->execute([':package_id' => $pid, ':dependent_package_id' => $dpid]);
		return $this->dbh->lastInsertId();
	}
	public function removePackage($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function removePackageFamily($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_family WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function removePackageGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function removePackageFromGroup($pid, $gid) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM package_group_member WHERE package_id = :package_id AND package_group_id = :package_group_id'
		);
		if(!$this->stmt->execute([':package_id' => $pid, ':package_group_id' => $gid])) return false;
		return $this->refactorPackageGroupOrder($gid);
	}
	public function removeComputerAssignedPackage($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_package WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function removeComputerAssignedPackageByIds($cid, $pid) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_package WHERE computer_id = :computer_id AND package_id = :package_id'
		);
		return $this->stmt->execute([':computer_id' => $cid, ':package_id' => $pid]);
	}

	// Job Operations
	public function addJobContainer($name, $author, $start_time, $end_time, $notes, $wol_sent, $shutdown_waked_after_completion, $sequence_mode, $priority, $agent_ip_ranges) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job_container (name, author, start_time, end_time, notes, wol_sent, shutdown_waked_after_completion, sequence_mode, priority, agent_ip_ranges)
			VALUES (:name, :author, :start_time, :end_time, :notes, :wol_sent, :shutdown_waked_after_completion, :sequence_mode, :priority, :agent_ip_ranges)'
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
		]);
		return $this->dbh->lastInsertId();
	}
	public function addJob($job_container_id, $computer_id, $package_id, $package_procedure, $success_return_codes, $is_uninstall, $download, $post_action, $post_action_timeout, $sequence, $state=0) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job (job_container_id, computer_id, package_id, package_procedure, success_return_codes, is_uninstall, download, post_action, post_action_timeout, sequence, state, message)
			VALUES (:job_container_id, :computer_id, :package_id, :package_procedure, :success_return_codes, :is_uninstall, :download, :post_action, :post_action_timeout, :sequence, :state, "")'
		);
		$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':computer_id' => $computer_id,
			':package_id' => $package_id,
			':package_procedure' => $package_procedure,
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
	public function getJobContainer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job_container WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'JobContainer') as $row) {
			return $row;
		}
	}
	public function getAllJobContainerByName($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job_container WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'JobContainer');
	}
	public function getAllJobContainer() {
		$this->stmt = $this->dbh->prepare(
			'SELECT jc.*, (SELECT MAX(execution_finished) FROM job j WHERE j.job_container_id = jc.id) AS "execution_finished"
			FROM job_container jc'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'JobContainer');
	}
	public function getJobContainerMinJobExecution($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job WHERE job_container_id = :job_container_id ORDER BY execution_started ASC LIMIT 1'
		);
		$this->stmt->execute([':job_container_id' => $job_container_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job') as $row) {
			return $row;
		}
	}
	public function getJobContainerMaxJobExecution($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM job WHERE job_container_id = :job_container_id ORDER BY execution_finished DESC LIMIT 1'
		);
		$this->stmt->execute([':job_container_id' => $job_container_id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job') as $row) {
			return $row;
		}
	}
	public function getJob($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, jc.start_time AS "job_container_start_time", jc.author AS "job_container_author" FROM job j
			INNER JOIN job_container jc ON jc.id = j.job_container_id
			WHERE j.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job') as $row) {
			return $row;
		}
	}
	public function getActiveJobByComputerPackage($cid, $pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.* FROM job j
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = :computer_id AND j.package_id = :package_id
			AND jc.enabled != 0
			AND (j.state = '.Job::STATUS_WAITING_FOR_CLIENT.' OR j.state = '.Job::STATUS_DOWNLOAD_STARTED.' OR j.state = '.Job::STATUS_EXECUTION_STARTED.')'
		);
		$this->stmt->execute([':computer_id' => $cid, ':package_id' => $pid]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job') as $row) {
			return $row;
		}
	}
	public function getComputerMacByContainer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", cn.mac AS "computer_network_mac"
			FROM job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN computer_network cn ON cn.computer_id = c.id
			WHERE j.job_container_id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getAllJobByContainer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, pf.name AS "package_family_name", p.version AS "package_version", c.hostname AS "computer_hostname", jc.start_time AS "job_container_start_time"
			FROM job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN package p ON p.id = j.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN job_container jc ON jc.id = j.job_container_id
			WHERE j.job_container_id = :id
			ORDER BY j.computer_id, j.sequence'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job');
	}
	public function getPendingJobsForComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", j.package_id AS "package_id", j.package_procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", jc.sequence_mode AS "sequence_mode", jc.agent_ip_ranges AS "agent_ip_ranges"
			FROM job j
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = :id
			AND jc.enabled = 1
			AND (j.state = '.Job::STATUS_WAITING_FOR_CLIENT.' OR j.state = '.Job::STATUS_DOWNLOAD_STARTED.' OR j.state = '.Job::STATUS_EXECUTION_STARTED.')
			AND (jc.start_time IS NULL OR jc.start_time < CURRENT_TIMESTAMP)
			AND (jc.end_time IS NULL OR jc.end_time > CURRENT_TIMESTAMP)
			ORDER BY jc.priority DESC, jc.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll();
	}
	public function getPendingJobsForComputerDetailPage($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", jc.name AS "job_container_name", jc.start_time AS "job_container_start_time",
			j.package_id AS "package_id", pf.name AS "package_family_name", p.version AS "package_version",
			j.is_uninstall AS "is_uninstall", j.state AS "state", j.package_procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout"
			FROM job j
			INNER JOIN package p ON j.package_id = p.id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = :id
			AND jc.enabled = 1
			AND (j.state = '.Job::STATUS_WAITING_FOR_CLIENT.' OR j.state = '.Job::STATUS_DOWNLOAD_STARTED.' OR j.state = '.Job::STATUS_EXECUTION_STARTED.')
			ORDER BY jc.priority DESC, jc.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job');
	}
	public function getPendingJobsForPackageDetailPage($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", jc.name AS "job_container_name", jc.start_time AS "job_container_start_time",
			j.computer_id AS "computer_id", c.hostname AS "computer_hostname",
			j.is_uninstall AS "is_uninstall", j.state AS "state", j.package_procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout"
			FROM job j
			INNER JOIN computer c ON j.computer_id = c.id
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.package_id = :id
			AND jc.enabled = 1
			AND (j.state = '.Job::STATUS_WAITING_FOR_CLIENT.' OR j.state = '.Job::STATUS_DOWNLOAD_STARTED.' OR j.state = '.Job::STATUS_EXECUTION_STARTED.')
			ORDER BY jc.priority DESC, jc.created ASC, j.sequence ASC'
		);
		$this->stmt->execute([':id' => $id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job');
	}
	public function setComputerOnlineStateForWolShutdown($job_container_id) {
		$tmpJobContainer = $this->getJobContainer($job_container_id);
		if(empty($tmpJobContainer->shutdown_waked_after_completion)) return;
		foreach($this->getAllLastComputerJobInContainer($job_container_id) as $j) {
			$tmpComputer = $this->getComputer($j->computer_id);
			if(!$tmpComputer->isOnline())
				$this->setWolShutdownJobInContainer($job_container_id, $tmpComputer->id, $j->max_sequence);
		}
	}
	public function getAllLastComputerJobInContainer($job_container_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT computer_id, MAX(sequence) AS "max_sequence" FROM job
			WHERE job_container_id = :job_container_id GROUP BY computer_id'
		);
		if(!$this->stmt->execute([':job_container_id' => $job_container_id])) return false;
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job');
	}
	public function setWolShutdownJobInContainer($job_container_id, $computer_id, $sequence) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job SET post_action = '.Package::POST_ACTION_SHUTDOWN.','
			.' wol_shutdown_set = CURRENT_TIMESTAMP'
			.' WHERE job_container_id = :job_container_id'
			.' AND computer_id = :computer_id'
			.' AND sequence = :sequence'
			.' AND (post_action = '.Package::POST_ACTION_NONE.' OR post_action = '.Package::POST_ACTION_EXIT.')'
		);
		if(!$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':computer_id' => $computer_id,
			':sequence' => $sequence,
		])) return false;
	}
	public function removeWolShutdownJobInContainer($job_container_id, $job_id, $post_action) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job SET post_action = :post_action,'
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
	public function updateJobState($id, $state, $return_code, $message) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job SET state = :state, return_code = :return_code, message = :message WHERE id = :id'
		);
		if(!$this->stmt->execute([
			':id' => $id,
			':state' => $state,
			':return_code' => $return_code,
			':message' => $message,
		])) return false;
		// update job timestamps
		if($state === Job::STATUS_DOWNLOAD_STARTED) {
			$this->stmt = $this->dbh->prepare('UPDATE job SET download_started = CURRENT_TIMESTAMP WHERE id = :id');
			if(!$this->stmt->execute([':id'=>$id])) return false;
		} elseif($state === Job::STATUS_EXECUTION_STARTED) {
			$this->stmt = $this->dbh->prepare('UPDATE job SET execution_started = CURRENT_TIMESTAMP WHERE id = :id');
			if(!$this->stmt->execute([':id'=>$id])) return false;
		} else {
			$this->stmt = $this->dbh->prepare('UPDATE job SET execution_finished = CURRENT_TIMESTAMP WHERE id = :id');
			if(!$this->stmt->execute([':id'=>$id])) return false;
		}
		// set all pending jobs of specific computer to failed if sequence_mode is 'abort after failed'
		if($state == Job::STATUS_FAILED) {
			$job_container_id = -1;
			$sequence_mode = JobContainer::SEQUENCE_MODE_IGNORE_FAILED;
			$this->stmt = $this->dbh->prepare(
				'SELECT jc.id AS "job_container_id", jc.sequence_mode FROM job j INNER JOIN job_container jc ON j.job_container_id = jc.id WHERE j.id = :id'
			);
			$this->stmt->execute([':id' => $id]);
			foreach($this->stmt->fetchAll() as $row) {
				$job_container_id = $row['job_container_id'];
				$sequence_mode = $row['sequence_mode'];
			}
			if($sequence_mode == JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED) {
				$this->stmt = $this->dbh->prepare(
					'UPDATE job SET state = :state, return_code = :return_code, message = :message, execution_finished = CURRENT_TIMESTAMP
					WHERE job_container_id = :job_container_id AND state = :old_state'
				);
				return $this->stmt->execute([
					':job_container_id' => $job_container_id,
					':old_state' => Job::STATUS_WAITING_FOR_CLIENT,
					':state' => Job::STATUS_FAILED,
					':return_code' => JobContainer::RETURN_CODE_ABORT_AFTER_FAILED,
					':message' => LANG['aborted_after_failed'],
				]);
			}
		}
		return true;
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
	public function moveJobToContainer($jid, $cid) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job SET job_container_id = :cid WHERE id = :jid'
		);
		return $this->stmt->execute([':jid' => $jid, ':cid' => $cid]);
	}
	public function getJobContainerIcon($id) {
		$container = $this->getJobContainer($id);
		$jobs = $this->getAllJobByContainer($id);
		$waitings = 0;
		$errors = 0;
		foreach($jobs as $job) {
			if($job->state == Job::STATUS_WAITING_FOR_CLIENT
			|| $job->state == Job::STATUS_DOWNLOAD_STARTED
			|| $job->state == Job::STATUS_EXECUTION_STARTED) {
				$waitings ++;
			}
			if($job->state == Job::STATUS_FAILED
			|| $job->state == Job::STATUS_EXPIRED
			|| $job->state == Job::STATUS_OS_INCOMPATIBLE
			|| $job->state == Job::STATUS_PACKAGE_CONFLICT) {
				$errors ++;
			}
		}
		if($waitings > 0) {
			$startTimeParsed = strtotime($container->start_time);
			if($startTimeParsed !== false && $startTimeParsed > time())
				return JobContainer::STATUS_WAITING_FOR_START;
			else return JobContainer::STATUS_IN_PROGRESS;
		}
		elseif($errors > 0) return JobContainer::STATUS_FAILED;
		else return JobContainer::STATUS_SUCCEEDED;
	}
	public function removeJobContainer($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM job_container WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function removeJob($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM job WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}

	// Domain User Operations
	private function insertOrUpdateDomainUser($uid, $username, $display_name) {
		$this->stmt = $this->dbh->prepare(
			// we cannot use REPLACE INTO here because this internally DELETEs and INSERTs existing rows, which automatically deletes the rows in domain_user_logon
			'INSERT INTO domain_user (id, uid, username, display_name)
			(SELECT id, uid, username, display_name FROM domain_user WHERE (uid IS NOT NULL AND uid=:uid) OR (username=:username AND display_name=:display_name)
			UNION SELECT null, :uid, :username, :display_name FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), uid=IF(:uid IS NULL,uid,:uid), username=:username, display_name=:display_name'
		);
		if(empty(trim($uid))) $uid = null;
		$this->stmt->execute([':uid' => $uid, ':username' => $username, ':display_name' => $display_name]);
		return $this->dbh->lastInsertId();
	}
	public function getAllDomainUser() {
		$this->stmt = $this->dbh->prepare(
			'SELECT *,
				(SELECT count(dl2.id) FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id) AS "logon_amount",
				(SELECT count(DISTINCT dl2.computer_id) FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id) AS "computer_amount",
				(SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user du
			ORDER BY username ASC'
		);
		$this->stmt->execute([]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainUser');
	}
	public function getAllDomainUserByName($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM domain_user WHERE username LIKE :username OR display_name LIKE :username ORDER BY username ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':username' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainUser');
	}
	public function getDomainUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT *, (SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user du
			WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainUser') as $row) {
			return $row;
		}
	}
	private function insertOrUpdateDomainUserLogon($cid, $did, $console, $timestamp) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO domain_user_logon (id, computer_id, domain_user_id, console, timestamp)
			(SELECT id, computer_id, domain_user_id, console, timestamp FROM domain_user_logon WHERE computer_id=:computer_id AND domain_user_id=:domain_user_id AND console=:console AND timestamp=:timestamp
			UNION SELECT null, :computer_id, :domain_user_id, :console, :timestamp FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)'
		);
		$this->stmt->execute([':computer_id' => $cid,
			':domain_user_id' => $did,
			':console' => $console,
			':timestamp' => $timestamp,
		]);
		return $this->dbh->lastInsertId();
	}
	public function getDomainUserLogonHistoryByDomainUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "computer_id", c.hostname AS "computer_hostname", dl.console AS "console", dl.timestamp AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domain_user_id = :domain_user_id
			ORDER BY timestamp DESC, computer_hostname ASC'
		);
		$this->stmt->execute([
			':domain_user_id' => $id,
		]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainUserLogon');
	}
	public function getDomainUserLogonByDomainUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "computer_id", c.hostname AS "computer_hostname", COUNT(c.hostname) AS "logon_amount",
			(SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domain_user_id = dl.domain_user_id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domain_user_id = :domain_user_id
			GROUP BY dl.domain_user_id, dl.computer_id
			ORDER BY timestamp DESC, logon_amount DESC, computer_hostname ASC'
		);
		$this->stmt->execute([
			':domain_user_id' => $id,
		]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainUserLogon');
	}
	public function getDomainUserLogonByComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.id AS "domain_user_id", du.username AS "domain_user_username", du.display_name AS "domain_user_display_name", COUNT(du.username) AS "logon_amount",
			(SELECT dl2.timestamp FROM domain_user_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domain_user_id = dl.domain_user_id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domain_user_logon dl
			INNER JOIN domain_user du ON dl.domain_user_id = du.id
			WHERE dl.computer_id = :computer_id
			GROUP BY dl.computer_id, dl.domain_user_id
			ORDER BY timestamp DESC, logon_amount DESC, domain_user_username ASC'
		);
		$this->stmt->execute([
			':computer_id' => $id,
		]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainUserLogon');
	}
	public function removeDomainUser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM domain_user WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function removeDomainUserLogonOlderThan($seconds) {
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM domain_user_logon WHERE timestamp < NOW() - INTERVAL '.intval($seconds).' SECOND'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}

	// System User Operations
	public function getAllSystemUserRole() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM system_user_role ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'SystemUserRole');
	}
	public function getAllSystemUser() {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			ORDER BY username ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'SystemUser', [$this]);
	}
	public function getSystemUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'SystemUser', [$this]) as $row) {
			return $row;
		}
	}
	public function getSystemUserByLogin($username) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.username = :username'
		);
		$this->stmt->execute([':username' => $username]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'SystemUser', [$this]) as $row) {
			return $row;
		}
	}
	public function getSystemUserByUid($uid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.uid = :uid'
		);
		$this->stmt->execute([':uid' => $uid]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'SystemUser', [$this]) as $row) {
			return $row;
		}
	}
	public function addSystemUser($uid, $username, $display_name, $password, $ldap, $email, $phone, $mobile, $description, $locked, $system_user_role_id) {
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
	public function removeSystemUser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM system_user WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}

	// Software Operations
	public function getAllSoftwareNames() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.name AS "name", count(cs.computer_id) AS "installations"
			FROM software s LEFT JOIN computer_software cs ON cs.software_id = s.id GROUP BY s.name ORDER BY s.name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getAllSoftwareNamesWindows() {
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
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getAllSoftwareNamesMacOS() {
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
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getAllSoftwareNamesOther() {
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
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getSoftware($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM software WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software') as $row) {
			return $row;
		}
	}
	private function insertOrUpdateSoftware($name, $version, $description) {
		$this->stmt = $this->dbh->prepare(
			// we cannot use REPLACE INTO here because this internally DELETEs and INSERTs existing rows, which automatically deletes the rows in computer_software
			// https://web.archive.org/web/20150925012041/http://mikefenwick.com:80/blog/insert-into-database-or-return-id-of-duplicate-row-in-mysql/
			'INSERT INTO software (id, name, version, description)
			(SELECT id, name, version, description FROM software WHERE BINARY name=:name AND BINARY version=:version AND description=:description
			UNION SELECT null, :name, :version, :description FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)'
		);
		$this->stmt->execute([':name' => $name, ':version' => $version, ':description' => $description]);
		return $this->dbh->lastInsertId();
	}
	private function insertOrUpdateComputerSoftware($cid, $sid) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_software (id, computer_id, software_id)
			(SELECT id, computer_id, software_id FROM computer_software WHERE computer_id=:computer_id AND software_id=:software_id
			UNION SELECT null, :computer_id, :software_id FROM DUAL LIMIT 1)
			ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)'
		);
		$this->stmt->execute([':computer_id' => $cid, ':software_id' => $sid]);
		return $this->dbh->lastInsertId();
	}

	// Report Operations
	public function addReportGroup($name, $parent_id) {
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
	public function renameReportGroup($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE report_group SET name = :name WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':name' => $name]);
	}
	public function removeReportGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM report_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function getAllReportGroup($parent_id=null) {
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
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ReportGroup');
		foreach($reports as $report) {
			if(!empty(LANG[$report->name])) $report->name = LANG[$report->name];
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		return $reports;
	}
	public function getReportGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM report_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'ReportGroup') as $row) {
			if(!empty(LANG[$row->name])) $row->name = LANG[$row->name];
			return $row;
		}
	}
	public function getReportGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->getReportGroup($currentGroupId);
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

	public function addReport($report_group_id, $name, $notes, $query) {
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
	public function removeReport($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM report WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}
	public function getAllReport() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM report ORDER BY name');
		$this->stmt->execute();
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Report');
		foreach($reports as $report) {
			if(!empty(LANG[$report->name])) $report->name = LANG[$report->name];
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		return $reports;
	}
	public function getAllReportByName($name, $limit=null) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM report');
		$this->stmt->execute();
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Report');
		foreach($reports as $key => $report) {
			if(!empty(LANG[$report->name])) $report->name = LANG[$report->name];
			if(strpos(strtoupper($report->name), strtoupper($name)) === false) unset($reports[$key]);
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		if($limit == null) return $reports;
		else return array_slice($reports, 0, intval($limit));
	}
	public function getAllReportByGroup($groupId) {
		if($groupId == null) $sql = 'SELECT * FROM report WHERE report_group_id IS NULL ORDER BY name';
		else $sql = 'SELECT * FROM report WHERE report_group_id = :report_group_id ORDER BY name';
		$this->stmt = $this->dbh->prepare($sql);
		$this->stmt->execute([':report_group_id' => $groupId]);
		$reports = $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Report');
		foreach($reports as $report) {
			if(!empty(LANG[$report->name])) $report->name = LANG[$report->name];
		}
		usort($reports, function($a, $b) {
			return strnatcmp($a->name, $b->name);
		});
		return $reports;
	}
	public function getReport($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM report WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Report') as $row) {
			if(!empty(LANG[$row->name])) $row->name = LANG[$row->name];
			return $row;
		}
	}
	public function executeReport($id) {
		$report = $this->getReport($id);
		if(!$report) return false;
		$this->dbh->beginTransaction();
		$this->stmt = $this->dbh->prepare($report->query);
		$this->stmt->execute();
		$result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->dbh->rollBack();
		return $result;
	}

	// Log Operations
	public function addLogEntry($level, $user, $object_id, $action, $data) {
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
	public function removeLogEntryOlderThan($seconds) {
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM log WHERE timestamp < NOW() - INTERVAL '.intval($seconds).' SECOND'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}

}
