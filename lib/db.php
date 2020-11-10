<?php

class db {
	private $mysqli;
	private $statement;

	function __construct() {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // debug
		$link = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if($link->connect_error) {
			die(':-O !!! failed to establish database connection: ' . $link->connect_error);
		}
		$link->set_charset("utf8");
		$this->mysqli = $link;
	}

	public function getDbHandle() {
		return $this->mysqli;
	}
	public function getLastStatement() {
		return $this->statement;
	}

	public function beginTransaction() {
		return $this->mysqli->autocommit(false);
	}
	public function commitTransaction() {
		return $this->mysqli->commit();
	}
	public function rollbackTransaction() {
		return $this->mysqli->rollback();
	}

	public static function getResultObjectArray($result) {
		$resultArray = [];
		while($row = $result->fetch_object()) {
			$resultArray[] = $row;
		}
		return $resultArray;
	}

	public function existsSchema() {
		$sql = "SHOW TABLES LIKE 'setting'";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		return ($result->num_rows == 1);
	}

	// Computer Operations
	public function getAllComputerCommand() {
		$sql = "SELECT * FROM computer_command";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerByName($name) {
		$sql = "SELECT * FROM computer WHERE hostname = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('s', $name)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getComputer($id) {
		$sql = "SELECT * FROM computer WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getComputerNetwork($cid) {
		$sql = "SELECT * FROM computer_network WHERE computer_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $cid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerScreen($cid) {
		$sql = "SELECT * FROM computer_screen WHERE computer_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $cid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerSoftware($cid) {
		$sql = "
			SELECT cs.id AS 'id', s.id AS 'software_id', s.name AS 'name', s.description AS 'description', cs.version AS 'version', cs.installed AS 'installed'
			FROM computer_software cs
			INNER JOIN software s ON cs.software_id = s.id
			WHERE cs.computer_id = ? ORDER BY s.name
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $cid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function removeComputerSoftware($id) {
		$sql = "DELETE FROM computer_software WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}
	public function getComputerPackage($cid) {
		$sql = "
			SELECT cp.id AS 'id', p.id AS 'package_id', p.name AS 'package_name', cp.installed_procedure AS 'installed_procedure', cp.installed AS 'installed'
			FROM computer_package cp
			INNER JOIN package p ON p.id = cp.package_id
			WHERE cp.computer_id = ?
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $cid)) return false;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getAllComputer() {
		$sql = "SELECT * FROM computer ORDER BY hostname ASC";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function addComputer($hostname, $agent_version, $networks) {
		$sql = "INSERT INTO computer (hostname, agent_version, last_ping, last_update, os, os_version, kernel_version, architecture, cpu, gpu, ram, serial, manufacturer, model, bios_version, boot_type, secure_boot, notes) VALUES (?,?,CURRENT_TIMESTAMP,'2000-01-01 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '')";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('ss', $hostname, $agent_version)) return false;
		if(!$this->statement->execute()) return false;
		$cid = $this->statement->insert_id;

		foreach($networks as $index => $network) {
			$sql = "INSERT INTO computer_network (computer_id, nic_number, addr, netmask, broadcast, mac, domain) VALUES (?,?,?,?,?,?,?)";
			if(!$this->statement = $this->mysqli->prepare($sql)) return false;
			if(!$this->statement->bind_param('iisssss', $cid, $index, $network['addr'], $network['netmask'], $network['broadcast'], $network['mac'], $network['domain'])) return false;
			if(!$this->statement->execute()) return false;
		}

		return $cid;
	}
	public function updateComputerPing($id) {
		$sql = "UPDATE computer SET last_ping = CURRENT_TIMESTAMP WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('i', $id)) return false;
		if(!$this->statement->execute()) return false;
	}
	public function updateComputerNote($id, $note) {
		$sql = "UPDATE computer SET notes = ? WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('si', $note, $id)) return false;
		if(!$this->statement->execute()) return false;
	}
	public function updateComputer($id, $hostname, $os, $os_version, $kernel_version, $architecture, $cpu, $gpu, $ram, $agent_version, $serial, $manufacturer, $model, $bios_version, $boot_type, $secure_boot, $networks, $screens, $software, $logins) {
		$this->mysqli->autocommit(false);

		// update general info
		$sql = "UPDATE computer SET hostname = ?, os = ?, os_version = ?, kernel_version = ?, architecture = ?, cpu = ?, gpu = ?, ram = ?, agent_version = ?, serial = ?, manufacturer = ?, model = ?, bios_version = ?, boot_type = ?, secure_boot = ?, last_ping = CURRENT_TIMESTAMP, last_update = CURRENT_TIMESTAMP WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('sssssssssssssssi', $hostname, $os, $os_version, $kernel_version, $architecture, $cpu, $gpu, $ram, $agent_version, $serial, $manufacturer, $model, $bios_version, $boot_type, $secure_boot, $id)) return false;
		if(!$this->statement->execute()) return false;

		// update networks
		$sql = "DELETE FROM computer_network WHERE computer_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('i', $id)) return false;
		if(!$this->statement->execute()) return false;
		foreach($networks as $index => $network) {
			$sql = "INSERT INTO computer_network (computer_id, nic_number, addr, netmask, broadcast, mac, domain) VALUES (?,?,?,?,?,?,?)";
			if(!$this->statement = $this->mysqli->prepare($sql)) return false;
			if(!$this->statement->bind_param('iisssss', $id, $index, $network['addr'], $network['netmask'], $network['broadcast'], $network['mac'], $network['domain'])) return false;
			if(!$this->statement->execute()) return false;
		}

		// update screens
		$sql = "DELETE FROM computer_screen WHERE computer_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('i', $id)) return false;
		if(!$this->statement->execute()) return false;
		foreach($screens as $index => $screen) {
			$sql = "INSERT INTO computer_screen (computer_id, name, manufacturer, type, resolution) VALUES (?,?,?,?,?)";
			if(!$this->statement = $this->mysqli->prepare($sql)) return false;
			if(!$this->statement->bind_param('issss', $id, $screen['name'], $screen['manufacturer'], $screen['type'], $screen['resolution'])) return false;
			if(!$this->statement->execute()) return false;
		}

		// insert software
		foreach($software as $index => $s) {
			$sid = null;
			$existingSoftware = $this->getSoftwareByName($s['name']);
			if($existingSoftware === null) {
				$sid = $this->addSoftware($s['name'], $s['description']);
			} else {
				$sid = $existingSoftware->id;
			}
			if($this->getComputerSoftwareByComputerSoftwareVersion($id, $sid, $s['version']) === null) {
				$sql = "INSERT INTO computer_software (computer_id, software_id, version) VALUES (?,?,?)";
				if(!$this->statement = $this->mysqli->prepare($sql)) return false;
				if(!$this->statement->bind_param('iis', $id, $sid, $s['version'])) return false;
				if(!$this->statement->execute()) return false;
			}
		}
		// remove software, which can not be found in agent output
		foreach($this->getComputerSoftware($id) as $s) {
			$found = false;
			foreach($software as $s2) {
				if($s->name == $s2['name']) {
					$found = true;
					break;
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
				if($du->username == $l['username']) {
					$domainuser = $du;
					break;
				}
			}
			if($domainuser == null) {
				$du_id = $this->addDomainuser($l['username']);
				$domainuser = $this->getDomainuser($du_id);
			}
			if($this->getDomainuserLogonByComputerDomainuserConsoleTimestamp($id, $domainuser->id, $l['console'], $l['timestamp']) === null) {
				$sql = "INSERT INTO domainuser_logon (computer_id, domainuser_id, console, timestamp) VALUES (?,?,?,?)";
				if(!$this->statement = $this->mysqli->prepare($sql)) return false;
				if(!$this->statement->bind_param('iiss', $id, $domainuser->id, $l['console'], $l['timestamp'])) return false;
				if(!$this->statement->execute()) return false;
			}
		}

		$this->mysqli->commit();
		return true;
	}
	public function removeComputer($id) {
		$sql = "DELETE FROM computer WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}

	public function getComputerGroup($id) {
		$sql = "SELECT * FROM computer_group WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getAllComputerGroup() {
		$sql = "SELECT * FROM computer_group ORDER BY name";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerBySoftware($sid) {
		$sql = "
			SELECT c.id AS 'id', c.hostname AS 'hostname', cs.version AS 'version'
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE cs.software_id = ?
			ORDER BY c.hostname
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $sid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerBySoftwareVersion($sid, $version) {
		$sql = "
			SELECT c.id AS 'id', c.hostname AS 'hostname', cs.version AS 'version'
			FROM computer_software cs
			INNER JOIN computer c ON cs.computer_id = c.id
			WHERE cs.software_id = ? AND cs.version = ?
			ORDER BY c.hostname
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('is', $sid, $version)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerByGroup($id) {
		$sql = "
			SELECT c.* FROM computer c
			INNER JOIN computer_group_member cgm ON c.id = cgm.computer_id
			WHERE cgm.computer_group_id = ?
			ORDER BY c.hostname
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getComputerByComputerAndGroup($cid, $gid) {
		$sql = "
			SELECT * FROM computer_group_member
			WHERE computer_id = ? AND computer_group_id = ?
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('ii', $cid, $gid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getGroupByComputer($cid) {
		$sql = "
			SELECT cg.id AS 'id', cg.name AS 'name'
			FROM computer_group_member cgm
			INNER JOIN computer_group cg ON cg.id = cgm.computer_group_id
			WHERE cgm.computer_id = ?
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $cid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function addComputerGroup($name) {
		$sql = "INSERT INTO computer_group (name) VALUES (?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('s', $name)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function removeComputerGroup($id) {
		$sql = "DELETE FROM computer_group WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}
	public function addComputerToGroup($cid, $gid) {
		$sql = "INSERT INTO computer_group_member (computer_id, computer_group_id) VALUES (?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('ii', $cid, $gid)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function removeComputerFromGroup($cid, $gid) {
		$sql = "DELETE FROM computer_group_member WHERE computer_id = ? AND computer_group_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('ii', $cid, $gid)) return null;
		return $this->statement->execute();
	}

	// Package Operations
	public function addPackage($name, $version, $author, $description, $filename, $install_procedure, $uninstall_procedure) {
		$sql = "INSERT INTO package (name, version, author, notes, filename, install_procedure, uninstall_procedure) VALUES (?,?,?,?,?,?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('sssssss', $name, $version, $author, $description, $filename, $install_procedure, $uninstall_procedure)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function addPackageToComputer($pid, $cid, $procedure) {
		$sql = "INSERT INTO computer_package (package_id, computer_id, installed_procedure) VALUES (?,?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('iis', $pid, $cid, $procedure)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function getPackageComputer($pid) {
		$sql = "
			SELECT cp.id AS 'id', c.id AS 'computer_id', c.hostname AS 'computer_hostname', cp.installed_procedure AS 'installed_procedure', cp.installed AS 'installed'
			FROM computer_package cp
			INNER JOIN computer c ON c.id = cp.computer_id
			WHERE cp.package_id = ?
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $pid)) return false;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getAllPackage() {
		$sql = "SELECT * FROM package";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getAllPackageGroup() {
		$sql = "SELECT * FROM package_group ORDER BY name";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getPackage($id) {
		$sql = "SELECT * FROM package WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getComputerAssignedPackage($id) {
		$sql = "SELECT * FROM computer_package WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getPackageGroup($id) {
		$sql = "SELECT * FROM package_group WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function addPackageGroup($name) {
		$sql = "INSERT INTO package_group (name) VALUES (?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('s', $name)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function getPackageByGroup($id) {
		$sql = "
			SELECT p.*, pgm.sequence AS 'sequence' FROM package p
			INNER JOIN package_group_member pgm ON p.id = pgm.package_id
			WHERE pgm.package_group_id = ?
			ORDER BY pgm.sequence
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function addPackageToGroup($pid, $gid) {
		$sql = "INSERT INTO package_group_member (package_id, package_group_id) VALUES (?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('ii', $pid, $gid)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function reorderPackageInGroup($package_group_id, $old_seq, $new_seq) {
		$sql = "
			SET @oldpos = ".intval($old_seq).";
			SET @newpos = ".intval($new_seq).";
			UPDATE package_group_member
			SET sequence = CASE WHEN sequence = @oldpos THEN @newpos
			WHEN @newpos < @oldpos AND sequence < @oldpos THEN
			sequence + 1
			WHEN @newpos > @oldpos AND sequence > @oldpos THEN
			sequence - 1
			END
			WHERE package_group_id = ".intval($package_group_id)." AND sequence BETWEEN LEAST(@newpos, @oldpos) AND GREATEST(@newpos, @oldpos)
		";
		return $this->mysqli->multi_query($sql);
	}
	public function removePackage($id) {
		$sql = "DELETE FROM package WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}
	public function removePackageGroup($id) {
		$sql = "DELETE FROM package_group WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}
	public function removePackageFromGroup($pid, $gid) {
		$sql = "DELETE FROM package_group_member WHERE package_id = ? AND package_group_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('ii', $pid, $gid)) return null;
		return $this->statement->execute();
	}
	public function removeComputerAssignedPackage($id) {
		$sql = "DELETE FROM computer_package WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}
	public function removeComputerAssignedPackageByIds($cid, $pid) {
		$sql = "DELETE FROM computer_package WHERE computer_id = ? AND package_id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('ii', $cid, $pid)) return null;
		return $this->statement->execute();
	}

	// Job Operations
	public function addJobContainer($name, $start_time, $end_time, $notes) {
		$sql = "INSERT INTO job_container (name, start_time, end_time, notes) VALUES (?,?,?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('ssss', $name, $start_time, $end_time, $notes)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function addJob($job_container_id, $computer_id, $package_id, $package_procedure, $is_uninstall, $sequence) {
		$sql = "INSERT INTO job (job_container_id, computer_id, package_id, package_procedure, is_uninstall, sequence, message) VALUES (?,?,?,?,?,?,'')";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('iiisii', $job_container_id, $computer_id, $package_id, $package_procedure, $is_uninstall, $sequence)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function getJobContainer($id) {
		$sql = "SELECT * FROM job_container WHERE id = ? ORDER BY created DESC";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getAllJobContainer() {
		$sql = "
			SELECT jc.*, (SELECT MAX(last_update) FROM job j WHERE j.job_container_id = jc.id) AS 'last_update'
			FROM job_container jc
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getJob($jid) {
		$sql = "SELECT * FROM job WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $jid)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getComputerMacByContainer($id) {
		$sql = "
			SELECT c.id AS 'id', c.hostname AS 'hostname', cn.mac AS 'mac'
			FROM job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN computer_network cn ON cn.computer_id = c.id
			WHERE j.job_container_id = ?
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getAllJobByContainer($id) {
		$sql = "
			SELECT j.id AS 'id', j.computer_id AS 'computer_id', c.hostname AS 'computer', j.package_id AS 'package_id', p.name AS 'package', j.package_procedure AS 'package_procedure', j.sequence AS 'sequence', j.state AS 'state', j.message AS 'message', j.last_update AS 'last_update'
			FROM job j
			INNER JOIN computer c ON c.id = j.computer_id
			INNER JOIN package p ON p.id = j.package_id
			WHERE j.job_container_id = ?
			ORDER BY j.computer_id, j.sequence
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getPendingJobsForComputer($cid) {
		$sql = "
			SELECT j.id AS 'id', j.package_id AS 'package_id', j.package_procedure AS 'procedure'
			FROM job j
			INNER JOIN job_container jc ON j.job_container_id = jc.id
			WHERE j.computer_id = ? AND j.state = 0 AND (jc.end_time IS NULL OR jc.end_time > CURRENT_TIMESTAMP)
			ORDER BY j.sequence
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $cid)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function updateJobState($id, $state, $message) {
		$sql = "UPDATE job SET state = ?, message = ?, last_update = CURRENT_TIMESTAMP WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('isi', $state, $message, $id)) return false;
		return $this->statement->execute();
	}
	public function updateJobContainer($id, $name, $start_time, $end_time, $notes, $wol_sent) {
		$sql = "UPDATE job_container SET name = ?, start_time = ?, end_time = ?, notes = ?, wol_sent = ? WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('ssssii', $name, $start_time, $end_time, $notes, $wol_sent, $id)) return false;
		return $this->statement->execute();
	}
	public function getJobContainerIcon($id) {
		$container = $this->getJobContainer($id);
		$jobs = $this->getAllJobByContainer($id);
		foreach($jobs as $job) {
			if($job->state == 0 || $job->state == 1) {
				if($container->end_time === null || strtotime($container->end_time) > time()) {
					return 'wait';
				} else {
					return 'error';
				}
			}
			if($job->state == -1) {
				return 'error';
			}
		}
		return 'tick';
	}
	public function removeJobContainer($id) {
		$sql = "DELETE FROM job_container WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}

	// Domainuser Operations
	public function addDomainuser($username) {
		$sql = "INSERT INTO domainuser (username) VALUES (?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('s', $username)) return false;
		if(!$this->statement->execute()) return false;
		return $this->statement->insert_id;
	}
	public function getAllDomainuser() {
		$sql = "SELECT *, (SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id ORDER BY timestamp DESC LIMIT 1) AS 'timestamp' FROM domainuser du ORDER BY username ASC";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getDomainuser($id) {
		$sql = "SELECT *, (SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id ORDER BY timestamp DESC LIMIT 1) AS 'timestamp' FROM domainuser du WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getDomainuserLogonByComputerDomainuserConsoleTimestamp($cid, $did, $console, $timestamp) {
		$sql = "
			SELECT * FROM domainuser_logon dl
			WHERE dl.computer_id = ? AND dl.domainuser_id = ? AND dl.console = ? AND dl.timestamp = ?
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('iiss', $cid, $did, $console, $timestamp)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getDomainuserLogonByDomainuser($id) {
		$sql = "
			SELECT c.id AS 'computer_id', c.hostname AS 'hostname', COUNT(c.hostname) AS 'amount',
			(SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domainuser_id = dl.domainuser_id ORDER BY timestamp DESC LIMIT 1) AS 'timestamp'
			FROM domainuser_logon dl
			INNER JOIN computer c ON dl.computer_id = c.id
			WHERE dl.domainuser_id = ?
			GROUP BY dl.domainuser_id, dl.computer_id
			ORDER BY timestamp DESC, amount DESC, hostname ASC
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getDomainuserLogonByComputer($id) {
		$sql = "
			SELECT du.id AS 'domainuser_id', du.username AS 'username', COUNT(du.username) AS 'amount',
			(SELECT dl2.timestamp FROM domainuser_logon dl2 WHERE dl2.computer_id = dl.computer_id AND dl2.domainuser_id = dl.domainuser_id ORDER BY timestamp DESC LIMIT 1) AS 'timestamp'
			FROM domainuser_logon dl
			INNER JOIN domainuser du ON dl.domainuser_id = du.id
			WHERE dl.computer_id = ?
			GROUP BY dl.computer_id, dl.domainuser_id
			ORDER BY timestamp DESC, amount DESC, username ASC
		";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function removeDomainuser($id) {
		$sql = "DELETE FROM domainuser WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}

	// Settings Operations
	public function getSettingByName($name) {
		$sql = "SELECT value FROM setting WHERE setting = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('s', $name)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row->value;
		}
	}
	public function updateSetting($name, $value) {
		$sql = "UPDATE setting SET value = ? WHERE setting = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('ss', $value, $name)) return false;
		return $this->statement->execute();
	}

	// Systemuser Operations
	public function getAllSystemuser() {
		$sql = "SELECT * FROM systemuser ORDER BY username ASC";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getSystemuser($id) {
		$sql = "SELECT * FROM systemuser WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getSystemuserByLogin($username) {
		$sql = "SELECT * FROM systemuser WHERE username = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('s', $username)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function addSystemuser($login, $fullname, $password, $ldap, $email, $phone, $mobile, $description, $locked) {
		$sql = "INSERT INTO systemuser (username, fullname, password, ldap, email, phone, mobile, description, locked) VALUES (?,?,?,?,?,?,?,?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('sssissssi', $login, $fullname, $password, $ldap, $email, $phone, $mobile, $description, $locked)) return null;
		if(!$this->statement->execute()) return null;
		return $this->statement->insert_id;
	}
	public function updateSystemuser($id, $login, $fullname, $password, $ldap, $email, $phone, $mobile, $description, $locked) {
		$sql = "UPDATE systemuser SET username = ?, fullname = ?, password = ?, ldap = ?, email = ?, phone = ?, mobile = ?, description = ?, locked = ? WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return false;
		if(!$this->statement->bind_param('sssissssii', $login, $fullname, $password, $ldap, $email, $phone, $mobile, $description, $locked, $id)) return false;
		return $this->statement->execute();
	}
	public function removeSystemuser($id) {
		$sql = "DELETE FROM systemuser WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		return $this->statement->execute();
	}

	// Software Operations
	public function getAllSoftware() {
		$sql = "SELECT *, (SELECT count(computer_id) FROM computer_software cs WHERE cs.software_id = s.id) AS 'installations' FROM software s ORDER BY name ASC";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->execute()) return null;
		return self::getResultObjectArray($this->statement->get_result());
	}
	public function getSoftware($id) {
		$sql = "SELECT * FROM software WHERE id = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('i', $id)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getSoftwareByName($name) {
		$sql = "SELECT * FROM software WHERE name = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('s', $name)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function getComputerSoftwareByComputerSoftwareVersion($cid, $sid, $version) {
		$sql = "SELECT * FROM computer_software WHERE computer_id = ? AND software_id = ? AND version = ?";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('iis', $cid, $sid, $version)) return null;
		if(!$this->statement->execute()) return null;
		$result = $this->statement->get_result();
		if($result->num_rows == 0) return null;
		while($row = $result->fetch_object()) {
			return $row;
		}
	}
	public function addSoftware($name, $description) {
		$sql = "INSERT INTO software (name, description) VALUES (?,?)";
		if(!$this->statement = $this->mysqli->prepare($sql)) return null;
		if(!$this->statement->bind_param('ss', $name, $description)) return null;
		if(!$this->statement->execute()) return null;
		return $this->statement->insert_id;
	}

}
