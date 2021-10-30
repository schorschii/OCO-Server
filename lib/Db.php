<?php

class Db {

	/*
		 Class Db
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

	public function getDbHandle() {
		return $this->dbh;
	}
	public function getLastStatement() {
		return $this->stmt;
	}

	public function existsSchema() {
		$this->stmt = $this->dbh->prepare(
			'SHOW TABLES LIKE "setting"'
		);
		$this->stmt->execute();
		return ($this->stmt->rowCount() == 1);
	}

	public function getStats() {
		$this->stmt = $this->dbh->prepare(
			'SELECT
			(SELECT count(id) FROM domainuser) AS "domain_users",
			(SELECT count(id) FROM computer) AS "computers",
			(SELECT count(id) FROM package) AS "packages",
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
			'SELECT * FROM computer_screen WHERE computer_id = :cid'
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
			'SELECT cs.id AS "id", s.id AS "software_id", s.name AS "software_name", s.description AS "software_description", cs.version AS "version", cs.installed AS "installed"
			FROM computer_software cs
			INNER JOIN software s ON cs.software_id = s.id
			WHERE cs.computer_id = :cid ORDER BY s.name'
		);
		$this->stmt->execute([':cid' => $cid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerSoftware');
	}
	public function removeComputerSoftware($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_software WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}
	public function getComputerPackage($cid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cp.id AS "id", p.id AS "package_id", p.package_family_id AS "package_family_id", pf.name AS "package_family_name", p.version AS "package_version", cp.installed_procedure AS "installed_procedure", cp.installed AS "installed"
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
			'INSERT INTO computer (hostname, agent_version, last_ping, last_update, os, os_version, os_license, os_locale, kernel_version, architecture, cpu, gpu, ram, serial, manufacturer, model, bios_version, boot_type, secure_boot, domain, notes, agent_key, server_key)
			VALUES (:hostname, :agent_version, CURRENT_TIMESTAMP, NULL, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", :notes, :agent_key, :server_key)'
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
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_network (computer_id, nic_number, addr, netmask, broadcast, mac, interface)
				VALUES (:computer_id, :nic_number, :addr, :netmask, :broadcast, :mac, :interface)'
			);
			$this->stmt->execute([
				':computer_id' => $cid,
				':nic_number' => $index,
				':addr' =>  $network['addr'],
				':netmask' => $network['netmask'],
				':broadcast' => $network['broadcast'],
				':mac' => $network['mac'],
				':interface' => $network['interface'],
			]);
		}

		return $cid;
	}
	public function updateComputerPing($id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET last_ping = CURRENT_TIMESTAMP WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}
	public function updateComputerNote($id, $notes) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET notes = :notes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':notes' => $notes]);
	}
	public function updateComputerForceUpdate($id, $force_update) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET force_update = :force_update WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':force_update' => $force_update]);
	}
	public function updateComputerHostname($id, $hostname) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET hostname = :hostname WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':hostname' => $hostname]);
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
	public function updateComputer($id, $hostname, $os, $os_version, $os_license, $os_locale, $kernel_version, $architecture, $cpu, $gpu, $ram, $agent_version, $serial, $manufacturer, $model, $bios_version, $boot_type, $secure_boot, $domain, $networks, $screens, $printers, $partitions, $software, $logins) {
		$this->dbh->beginTransaction();

		// update general info
		$this->stmt = $this->dbh->prepare(
			'UPDATE computer SET hostname = :hostname, os = :os, os_version = :os_version, os_license = :os_license, os_locale = :os_locale, kernel_version = :kernel_version, architecture = :architecture, cpu = :cpu, gpu = :gpu, ram = :ram, agent_version = :agent_version, serial = :serial, manufacturer = :manufacturer, model = :model, bios_version = :bios_version, boot_type = :boot_type, secure_boot = :secure_boot, domain = :domain, last_ping = CURRENT_TIMESTAMP, last_update = CURRENT_TIMESTAMP, force_update = 0 WHERE id = :id'
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
			':serial' => $serial,
			':manufacturer' => $manufacturer,
			':model' => $model,
			':bios_version' => $bios_version,
			':boot_type' => $boot_type,
			':secure_boot' => $secure_boot,
			':domain' => $domain,
		])) return false;

		// update networks
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM computer_network WHERE computer_id = :id'
		);
		if(!$this->stmt->execute([':id' => $id])) return false;
		foreach($networks as $index => $network) {
			if(empty($network['addr'])) continue;
			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_network (computer_id, nic_number, addr, netmask, broadcast, mac, interface)
				VALUES (:computer_id, :nic_number, :addr, :netmask, :broadcast, :mac, :interface)'
			);
			if(!$this->stmt->execute([
				':computer_id' => $id,
				':nic_number' => $index,
				':addr' => $network['addr'],
				':netmask' => $network['netmask'] ?? '?',
				':broadcast' => $network['broadcast'] ?? '?',
				':mac' => $network['mac'] ?? '?',
				':interface' => $network['interface'] ?? '?',
			])) return false;
		}

		// update screens
		foreach($screens as $index => $screen) {
			if(empty($screen['name'])) continue;
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_screen
				WHERE computer_id = :computer_id AND name = :name AND manufacturer = :manufacturer AND type = :type AND resolution = :resolution AND size = :size AND serialno = :serialno'
			);
			if(!$this->stmt->execute([
				':computer_id' => $id,
				':name' => $screen['name'],
				':manufacturer' => $screen['manufacturer'] ?? '?',
				':type' => $screen['type'] ?? '?',
				':resolution' => $screen['resolution'] ?? '?',
				':size' => $screen['size'] ?? '?',
				':serialno' => $screen['serialno'] ?? '?',
			])) return false;
			if($this->stmt->rowCount() > 0) continue;

			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_screen (computer_id, name, manufacturer, type, resolution, size, manufactured, serialno)
				VALUES (:computer_id, :name, :manufacturer, :type, :resolution, :size, :manufactured, :serialno)'
			);
			if(!$this->stmt->execute([
				':computer_id' => $id,
				':name' => $screen['name'],
				':manufacturer' => $screen['manufacturer'] ?? '?',
				':type' => $screen['type'] ?? '?',
				':resolution' => $screen['resolution'] ?? '?',
				':size' => $screen['size'] ?? '?',
				':manufactured' => $screen['manufactured'] ?? '?',
				':serialno' => $screen['serialno'] ?? '?',
			])) return false;
		}
		// remove screens, which can not be found in agent output
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_screen WHERE computer_id = :computer_id'
		);
		if(!$this->stmt->execute([':computer_id' => $id])) return false;
		foreach($this->stmt->fetchAll() as $s) {
			$found = false;
			foreach($screens as $s2) {
				if($s['name'] == $s2['name']
				&& $s['manufacturer'] == $s2['manufacturer']
				&& $s['type'] == $s2['type']
				&& $s['resolution'] == $s2['resolution']
				&& $s['size'] == ($s2['size']??'')
				&& $s['serialno'] == ($s2['serialno']??'')) {
					$found = true; break;
				}
			}
			if(!$found) {
				$this->stmt = $this->dbh->prepare(
					'DELETE FROM computer_screen WHERE id = :id'
				);
				if(!$this->stmt->execute([':id' => $s['id']])) return false;
			}
		}

		// update printers
		foreach($printers as $index => $printer) {
			if(empty($printer['name'])) continue;
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_printer
				WHERE computer_id = :computer_id AND name = :name AND driver = :driver AND paper = :paper AND dpi = :dpi AND uri = :uri AND status = :status'
			);
			if(!$this->stmt->execute([
				':computer_id' => $id,
				':name' => $printer['name'],
				':driver' => $printer['driver'] ?? '?',
				':paper' => $printer['paper'] ?? '?',
				':dpi' => $printer['dpi'] ?? '?',
				':uri' => $printer['uri'] ?? '?',
				':status' => $printer['status'] ?? '?',
			])) return false;
			if($this->stmt->rowCount() > 0) continue;

			$this->stmt = $this->dbh->prepare(
				'INSERT INTO computer_printer (computer_id, name, driver, paper, dpi, uri, status)
				VALUES (:computer_id, :name, :driver, :paper, :dpi, :uri, :status)'
			);
			if(!$this->stmt->execute([
				':computer_id' => $id,
				':name' => $printer['name'],
				':driver' => $printer['driver'] ?? '?',
				':paper' => $printer['paper'] ?? '?',
				':dpi' => $printer['dpi'] ?? '?',
				':uri' => $printer['uri'] ?? '?',
				':status' => $printer['status'] ?? '?',
			])) return false;
		}
		// remove printers, which can not be found in agent output
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_printer WHERE computer_id = :computer_id'
		);
		if(!$this->stmt->execute([':computer_id' => $id])) return false;
		foreach($this->stmt->fetchAll() as $p) {
			$found = false;
			foreach($printers as $s2) {
				if($p['name'] == $s2['name']
				&& $p['driver'] == $s2['driver']
				&& $p['paper'] == $s2['paper']
				&& $p['dpi'] == $s2['dpi']
				&& $p['uri'] == $s2['uri']
				&& $p['status'] == $s2['status']) {
					$found = true; break;
				}
			}
			if(!$found) {
				$this->stmt = $this->dbh->prepare(
					'DELETE FROM computer_printer WHERE id = :id'
				);
				if(!$this->stmt->execute([':id' => $p['id']])) return false;
			}
		}

		// update partitions
		foreach($partitions as $index => $part) {
			if(empty($part['size'])) continue;
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM computer_partition
				WHERE computer_id = :computer_id AND device = :device AND mountpoint = :mountpoint AND filesystem = :filesystem'
			);
			if(!$this->stmt->execute([
				':computer_id' => $id,
				':device' => $part['device'] ?? '?',
				':mountpoint' => $part['mountpoint'] ?? '?',
				':filesystem' => $part['filesystem'] ?? '?',
			])) return false;
			if($this->stmt->rowCount() > 0) {
				foreach($this->stmt->fetchAll() as $p) {
					$this->stmt = $this->dbh->prepare(
						'UPDATE computer_partition SET size = :size, free = :free WHERE id = :id'
					);
					if(!$this->stmt->execute([
						':id' => $p['id'],
						':size' => intval($part['size']),
						':free' => intval($part['free']),
					])) return false;
				}
			} else {
				$this->stmt = $this->dbh->prepare(
					'INSERT INTO computer_partition (computer_id, device, mountpoint, filesystem, size, free)
					VALUES (:computer_id, :device, :mountpoint, :filesystem, :size, :free)'
				);
				if(!$this->stmt->execute([
					':computer_id' => $id,
					':device' => $part['device'] ?? '?',
					':mountpoint' => $part['mountpoint'] ?? '?',
					':filesystem' => $part['filesystem'] ?? '?',
					':size' => intval($part['size']),
					':free' => intval($part['free']),
				])) return false;
			}
		}
		// remove partitions, which can not be found in agent output
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_partition WHERE computer_id = :computer_id'
		);
		if(!$this->stmt->execute([':computer_id' => $id])) return false;
		foreach($this->stmt->fetchAll() as $p) {
			$found = false;
			foreach($partitions as $s2) {
				if($p['device'] == $s2['device']
				&& $p['mountpoint'] == $s2['mountpoint']
				&& $p['filesystem'] == $s2['filesystem']) {
					$found = true; break;
				}
			}
			if(!$found) {
				$this->stmt = $this->dbh->prepare(
					'DELETE FROM computer_partition WHERE id = :id'
				);
				if(!$this->stmt->execute([':id' => $p['id']])) return false;
			}
		}

		// update software
		foreach($software as $index => $s) {
			$sid = null;
			$existingSoftware = $this->getSoftwareByName($s['name']);
			if($existingSoftware === null) {
				$sid = $this->addSoftware($s['name'], $s['description']);
			} else {
				$sid = $existingSoftware->id;
			}
			if($this->getComputerSoftwareByComputerSoftwareVersion($id, $sid, $s['version']) === null) {
				$this->stmt = $this->dbh->prepare(
					'INSERT INTO computer_software (computer_id, software_id, version)
					VALUES (:computer_id, :software_id, :version)'
				);
				if(!$this->stmt->execute([
					':computer_id' => $id,
					':software_id' => $sid,
					':version' => $s['version'],
				])) return false;
			}
		}
		// remove software, which can not be found in agent output
		foreach($this->getComputerSoftware($id) as $s) {
			$found = false;
			foreach($software as $s2) {
				if($s->software_name == $s2['name']
				&& $s->version == $s2['version']) {
					$found = true; break;
				}
			}
			if(!$found) {
				$this->removeComputerSoftware($s->id);
			}
		}

		// insert new domainuser logins
		$domainusers = $this->getAllDomainuser();
		foreach($logins as $index => $l) {
			$domainuser = null;
			foreach($domainusers as $du) {
				if(strtolower($du->username) === strtolower($l['username'])) {
					$domainuser = $du; break;
				}
			}
			if($domainuser === null) {
				$du_id = $this->addDomainuser($l['username']);
				$domainuser = $this->getDomainuser($du_id);
				$domainusers = $this->getAllDomainuser();
			}
			if($this->getDomainuserLogonByComputerDomainuserConsoleTimestamp($id, $domainuser->id, $l['console'], $l['timestamp']) === null) {
				$this->stmt = $this->dbh->prepare(
					'INSERT INTO domainuser_logon (computer_id, domainuser_id, console, timestamp)
					VALUES (:computer_id, :domainuser_id, :console, :timestamp)'
				);
				if(!$this->stmt->execute([
					':computer_id' => $id,
					':domainuser_id' => $domainuser->id,
					':console' => $l['console'],
					':timestamp' => $l['timestamp'],
				])) return false;
			}
		}

		$this->dbh->commit();
		return true;
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
	public function getComputerBySoftware($sid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", cs.version AS "software_version"
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE cs.software_id = :id
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':id' => $sid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Computer');
	}
	public function getComputerBySoftwareVersion($sid, $version) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "id", c.hostname AS "hostname", c.os AS "os", c.os_version AS "os_version", cs.version AS "software_version"
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE cs.software_id = :id AND cs.version = :version
			ORDER BY c.hostname'
		);
		$this->stmt->execute([':id' => $sid, ':version' => $version]);
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
			'SELECT cg.id AS "id", cg.name AS "name"
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
	public function updatePackageFamilyName($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_family SET name = :name WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':name' => $newValue]);
	}
	public function updatePackageFamilyNotes($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_family SET notes = :notes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':notes' => $newValue]);
	}
	public function updatePackageFamilyIcon($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package_family SET icon = :icon WHERE id = :id'
		);
		$this->stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$this->stmt->bindParam(':icon', $newValue, PDO::PARAM_LOB);
		return $this->stmt->execute();
	}
	public function updatePackageVersion($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, version = :version WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':version' => $newValue]);
	}
	public function updatePackageNote($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, notes = :notes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':notes' => $newValue]);
	}
	public function updatePackageInstallProcedure($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, install_procedure = :install_procedure WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':install_procedure' => $newValue]);
	}
	public function updatePackageInstallProcedureSuccessReturnCodes($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, install_procedure_success_return_codes = :install_procedure_success_return_codes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':install_procedure_success_return_codes' => $newValue]);
	}
	public function updatePackageInstallProcedurePostAction($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, install_procedure_post_action = :install_procedure_post_action WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':install_procedure_post_action' => $newValue]);
	}
	public function updatePackageUninstallProcedure($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, uninstall_procedure = :uninstall_procedure WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':uninstall_procedure' => $newValue]);
	}
	public function updatePackageUninstallProcedureSuccessReturnCodes($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, uninstall_procedure_success_return_codes = :uninstall_procedure_success_return_codes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':uninstall_procedure_success_return_codes' => $newValue]);
	}
	public function updatePackageUninstallProcedurePostAction($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, uninstall_procedure_post_action = :uninstall_procedure_post_action WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':uninstall_procedure_post_action' => $newValue]);
	}
	public function updatePackageDownloadForUninstall($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, download_for_uninstall = :download_for_uninstall WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':download_for_uninstall' => $newValue]);
	}
	public function updatePackageCompatibleOs($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, compatible_os = :compatible_os WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':compatible_os' => $newValue]);
	}
	public function updatePackageCompatibleOsVersion($id, $newValue) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE package SET last_update = CURRENT_TIMESTAMP, compatible_os_version = :compatible_os_version WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':compatible_os_version' => $newValue]);
	}
	public function addPackageToComputer($pid, $cid, $procedure) {
		$this->dbh->beginTransaction();
		$this->removeComputerAssignedPackageByIds($cid, $pid);
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO computer_package (package_id, computer_id, installed_procedure)
			VALUES (:package_id, :computer_id, :installed_procedure)'
		);
		$this->stmt->execute([
			':package_id' => $pid,
			':computer_id' => $cid,
			':installed_procedure' => $procedure,
		]);
		$this->dbh->commit();
		return $this->dbh->lastInsertId();
	}
	public function getPackageComputer($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cp.id AS "id", c.id AS "computer_id", c.hostname AS "computer_hostname", cp.installed_procedure AS "installed_procedure", cp.installed AS "installed"
			FROM computer_package cp
			INNER JOIN computer c ON c.id = cp.computer_id
			WHERE cp.package_id = :pid'
		);
		$this->stmt->execute([':pid' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'ComputerPackage');
	}
	public function getPackageByPackageAndGroup($pid, $gid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package_group_member pgm
			INNER JOIN package p ON p.id = pgm.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pgm.package_id = :pid AND pgm.package_group_id = :gid'
		);
		$this->stmt->execute([':pid' => $pid, ':gid' => $gid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getPackageByFamily($fid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE p.package_family_id = :package_family_id'
		);
		$this->stmt->execute([':package_family_id' => $fid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getDependentPackages($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package_dependency pd
			INNER JOIN package p ON p.id = pd.dependent_package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pd.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getDependentForPackages($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package_dependency pd
			INNER JOIN package p ON p.id = pd.package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pd.dependent_package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getConflictPackages($pid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package_conflict pc
			INNER JOIN package p ON p.id = pc.conflict_package_id
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE pc.package_id = :package_id'
		);
		$this->stmt->execute([':package_id' => $pid]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getAllPackage($orderByCreated=false) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id'
			.($orderByCreated ? ' ORDER BY p.created DESC' : ' ORDER BY pf.name ASC')
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getAllPackageFamily() {
		$this->stmt = $this->dbh->prepare(
			'SELECT pf.*, (SELECT COUNT(id) FROM package p WHERE p.package_family_id = pf.id) AS "package_count",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created DESC LIMIT 1) AS "newest_package_created",
				(SELECT created FROM package p WHERE p.package_family_id = pf.id ORDER BY created ASC LIMIT 1) AS "oldest_package_created"
			FROM package_family pf ORDER BY newest_package_created DESC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'PackageFamily');
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
			'SELECT pg.id AS "id", pg.name AS "name"
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
	public function getPackage($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "package_family_name", pf.icon AS "package_family_icon" FROM package p
			INNER JOIN package_family pf ON pf.id = p.package_family_id
			WHERE p.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package') as $row) {
			return $row;
		}
	}
	public function getAllPackageFamilyByName($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT id, name FROM package_family
			WHERE name LIKE :name ORDER BY name ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':name' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Package');
	}
	public function getPackageByNameVersion($name, $version) {
		$this->stmt = $this->dbh->prepare(
			'SELECT p.*, pf.name AS "name" FROM package p
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
			'SELECT p.*, pf.name AS "name", pgm.sequence AS "package_group_member_sequence"
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
	public function addJobContainer($name, $author, $start_time, $end_time, $notes, $wol_sent, $shutdown_waked_after_completion, $sequence_mode, $priority) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO job_container (name, author, start_time, end_time, notes, wol_sent, shutdown_waked_after_completion, sequence_mode, priority)
			VALUES (:name, :author, :start_time, :end_time, :notes, :wol_sent, :shutdown_waked_after_completion, :sequence_mode, :priority)'
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
			'SELECT jc.*, (SELECT MAX(last_update) FROM job j WHERE j.job_container_id = jc.id) AS "last_update"
			FROM job_container jc'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'JobContainer');
	}
	public function getJob($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT j.*, jc.start_time AS "job_container_start_time" FROM job j
			INNER JOIN job_container jc ON jc.id = j.job_container_id
			WHERE j.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Job') as $row) {
			return $row;
		}
	}
	public function renameJobContainer($id, $name) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET name = :name WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':name' => $name]);
	}
	public function editJobContainerStart($id, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET start_time = :start_time WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':start_time' => $value]);
	}
	public function editJobContainerEnd($id, $value) {
		if(empty($value)) $value = null;
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET end_time = :end_time WHERE id = :id'
		);
		$this->stmt->bindValue(':id', $id);
		$this->stmt->bindValue(':end_time', $value);
		return $this->stmt->execute();
	}
	public function editJobContainerSequenceMode($id, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET sequence_mode = :sequence_mode WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':sequence_mode' => $value]);
	}
	public function editJobContainerPriority($id, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET priority = :priority WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':priority' => $value]);
	}
	public function editJobContainerNotes($id, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET notes = :notes WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id, ':notes' => $value]);
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
			'SELECT j.id AS "id", j.job_container_id AS "job_container_id", j.package_id AS "package_id", j.package_procedure AS "procedure", j.download AS "download", j.post_action AS "post_action", j.post_action_timeout AS "post_action_timeout", jc.sequence_mode AS "sequence_mode"
			FROM job j
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = :id
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
	public function removeWolShutdownJobInContainer($job_container_id, $job_id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job SET post_action = '.Package::POST_ACTION_NONE.','
			.' wol_shutdown_set = NULL'
			.' WHERE job_container_id = :job_container_id'
			.' AND id = :id'
			.' AND post_action IS NOT NULL'
		);
		if(!$this->stmt->execute([
			':job_container_id' => $job_container_id,
			':id' => $job_id,
		])) return false;
	}
	public function updateJobState($id, $state, $return_code, $message) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job SET state = :state, return_code = :return_code, message = :message, last_update = CURRENT_TIMESTAMP WHERE id = :id'
		);
		if(!$this->stmt->execute([
			':id' => $id,
			':state' => $state,
			':return_code' => $return_code,
			':message' => $message,
		])) return false;
		// set all pending jobs to failed if sequence_mode is 'abort after failed'
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
					'UPDATE job SET state = :state, return_code = :return_code, message = :message, last_update = CURRENT_TIMESTAMP
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
	public function updateJobContainer($id, $name, $start_time, $end_time, $notes, $wol_sent) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE job_container SET name = :name, start_time = :start_time, end_time = :end_time, notes = :notes, wol_sent = :wol_sent WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':name' => $name,
			':start_time' => $start_time,
			':end_time' => $end_time,
			':notes' => $notes,
			':wol_sent' => $wol_sent,
		]);
	}
	public function getJobContainerIcon($id) {
		$container = $this->getJobContainer($id);
		$jobs = $this->getAllJobByContainer($id);
		$waitings = 0;
		$errors = 0;
		foreach($jobs as $job) {
			if($job->state == Job::STATUS_WAITING_FOR_CLIENT || $job->state == Job::STATUS_DOWNLOAD_STARTED || $job->state == Job::STATUS_EXECUTION_STARTED) {
				$waitings ++;
			}
			if($job->state == Job::STATUS_FAILED || $job->state == Job::STATUS_EXPIRED || $job->state == Job::STATUS_OS_INCOMPATIBLE || $job->state == Job::STATUS_PACKAGE_CONFLICT) {
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

	// Domainuser Operations
	public function addDomainuser($username) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO domainuser (username) VALUES (:username)'
		);
		$this->stmt->execute([
			':username' => $username,
		]);
		return $this->dbh->lastInsertId();
	}
	public function getAllDomainuser() {
		$this->stmt = $this->dbh->prepare(
			'SELECT *,
				(SELECT count(dl2.id) FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id) AS "logon_amount",
				(SELECT count(DISTINCT dl2.computer_id) FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id) AS "computer_amount",
				(SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domainuser du
			ORDER BY username ASC'
		);
		$this->stmt->execute([]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Domainuser');
	}
	public function getAllDomainuserByName($name, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM domainuser WHERE username LIKE :username ORDER BY username ASC ' . ($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':username' => '%'.$name.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Domainuser');
	}
	public function getDomainuser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT *, (SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domainuser du
			WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Domainuser') as $row) {
			return $row;
		}
	}
	public function getDomainuserLogonByComputerDomainuserConsoleTimestamp($cid, $did, $console, $timestamp) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM domainuser_logon dl
			WHERE dl.computer_id = :computer_id AND dl.domainuser_id = :domainuser_id AND dl.console = :console AND dl.timestamp = :timestamp'
		);
		$this->stmt->execute([
			':computer_id' => $cid,
			':domainuser_id' => $did,
			':console' => $console,
			':timestamp' => $timestamp,
		]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainuserLogon') as $row) {
			return $row;
		}
	}
	public function getDomainuserLogonHistoryByDomainuser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "computer_id", c.hostname AS "computer_hostname", dl.console AS "console", dl.timestamp AS "timestamp"
			FROM domainuser_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domainuser_id = :domainuser_id
			ORDER BY timestamp DESC, computer_hostname ASC'
		);
		$this->stmt->execute([
			':domainuser_id' => $id,
		]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainuserLogon');
	}
	public function getDomainuserLogonByDomainuser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.id AS "computer_id", c.hostname AS "computer_hostname", COUNT(c.hostname) AS "logon_amount",
			(SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domainuser_id = dl.domainuser_id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domainuser_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domainuser_id = :domainuser_id
			GROUP BY dl.domainuser_id, dl.computer_id
			ORDER BY timestamp DESC, logon_amount DESC, computer_hostname ASC'
		);
		$this->stmt->execute([
			':domainuser_id' => $id,
		]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainuserLogon');
	}
	public function getDomainuserLogonByComputer($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT du.id AS "domainuser_id", du.username AS "domainuser_username", COUNT(du.username) AS "logon_amount",
			(SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domainuser_id = dl.domainuser_id ORDER BY timestamp DESC LIMIT 1) AS "timestamp"
			FROM domainuser_logon dl
			INNER JOIN domainuser du ON dl.domainuser_id = du.id
			WHERE dl.computer_id = :computer_id
			GROUP BY dl.computer_id, dl.domainuser_id
			ORDER BY timestamp DESC, logon_amount DESC, domainuser_username ASC'
		);
		$this->stmt->execute([
			':computer_id' => $id,
		]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'DomainuserLogon');
	}
	public function removeDomainuser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM domainuser WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
	}

	// Settings Operations
	public function getSettingByName($name) {
		$this->stmt = $this->dbh->prepare(
			'SELECT value FROM setting WHERE setting = :setting'
		);
		$this->stmt->execute([':setting' => $name]);
		foreach($this->stmt->fetchAll() as $row) {
			return $row['value'];
		}
	}
	public function updateSetting($name, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE setting SET value = :value WHERE setting = :setting'
		);
		return $this->stmt->execute([':setting' => $name, ':value' => $value]);
	}

	// Systemuser Operations
	public function getAllSystemuser() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM systemuser ORDER BY username ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Systemuser');
	}
	public function getSystemuser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM systemuser WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Systemuser') as $row) {
			return $row;
		}
	}
	public function getSystemuserByLogin($username) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM systemuser WHERE username = :username'
		);
		$this->stmt->execute([':username' => $username]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Systemuser') as $row) {
			return $row;
		}
	}
	public function addSystemuser($username, $fullname, $password, $ldap, $email, $phone, $mobile, $description, $locked) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO systemuser (username, fullname, password, ldap, email, phone, mobile, description, locked)
			VALUES (:username, :fullname, :password, :ldap, :email, :phone, :mobile, :description, :locked)'
		);
		$this->stmt->execute([
			':username' => $username,
			':fullname' => $fullname,
			':password' => $password,
			':ldap' => $ldap,
			':email' => $email,
			':phone' => $phone,
			':mobile' => $mobile,
			':description' => $description,
			':locked' => $locked,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateSystemuser($id, $username, $fullname, $password, $ldap, $email, $phone, $mobile, $description, $locked) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE systemuser SET username = :username, fullname = :fullname, password = :password, ldap = :ldap, email = :email, phone = :phone, mobile = :mobile, description = :description, locked = :locked WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':username' => $username,
			':fullname' => $fullname,
			':password' => $password,
			':ldap' => $ldap,
			':email' => $email,
			':phone' => $phone,
			':mobile' => $mobile,
			':description' => $description,
			':locked' => $locked,
		]);
	}
	public function removeSystemuser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM systemuser WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}

	// Software Operations
	public function getAllSoftware() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.*, (SELECT count(computer_id) FROM computer_software cs WHERE cs.software_id = s.id) AS "installations"
			FROM software s ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getAllSoftwareWindows() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.*, (SELECT count(computer_id) FROM computer_software cs WHERE cs.software_id = s.id) AS "installations"
			FROM software s INNER JOIN computer_software cs2 ON cs2.id = (
				SELECT cs3.id FROM computer_software AS cs3
				INNER JOIN computer c ON cs3.computer_id = c.id
				WHERE cs3.software_id = s.id
				AND c.os LIKE "%Windows%"
				LIMIT 1
			)
			ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getAllSoftwareMacOS() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.*, (SELECT count(computer_id) FROM computer_software cs WHERE cs.software_id = s.id) AS "installations"
			FROM software s INNER JOIN computer_software cs2 ON cs2.id = (
				SELECT cs3.id FROM computer_software AS cs3
				INNER JOIN computer c ON cs3.computer_id = c.id
				WHERE cs3.software_id = s.id
				AND c.os LIKE "%macOS%"
				LIMIT 1
			)
			ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software');
	}
	public function getAllSoftwareOther() {
		$this->stmt = $this->dbh->prepare(
			'SELECT s.*, (SELECT count(computer_id) FROM computer_software cs WHERE cs.software_id = s.id) AS "installations"
			FROM software s INNER JOIN computer_software cs2 ON cs2.id = (
				SELECT cs3.id FROM computer_software AS cs3
				INNER JOIN computer c ON cs3.computer_id = c.id
				WHERE cs3.software_id = s.id
				AND c.os NOT LIKE "%Windows%" AND c.os NOT LIKE "%macOS%"
				LIMIT 1
			)
			ORDER BY name ASC'
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
	public function getSoftwareByName($name) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM software WHERE name = :name'
		);
		$this->stmt->execute([':name' => $name]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software') as $row) {
			return $row;
		}
	}
	public function getComputerSoftwareByComputerSoftwareVersion($cid, $sid, $version) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM computer_software WHERE computer_id = :computer_id AND software_id = :software_id AND version = :version'
		);
		$this->stmt->execute([':computer_id' => $cid, ':software_id' => $sid, ':version' => $version]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Software') as $row) {
			return $row;
		}
	}
	public function addSoftware($name, $description) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO software (name, description) VALUES (:name, :description)'
		);
		$this->stmt->execute([
			':name' => $name,
			':description' => $description,
		]);
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
		return $this->stmt->execute([':id' => $id]);
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
		return $this->stmt->execute([':id' => $id]);
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
	public function addLogEntry($level, $user, $realm, $message) {
		if($level < LOG_LEVEL) return;
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO log (level, host, user, realm, message) VALUES (:level, :host, :user, :realm, :message)'
		);
		$this->stmt->execute([
			':level' => $level,
			':host' => $_SERVER['REMOTE_ADDR'] ?? 'local',
			':user' => $user,
			':realm' => $realm,
			':message' => $message,
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
