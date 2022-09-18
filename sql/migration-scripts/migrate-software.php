<?php

// this script migrates data to the new software table schema (v0.14.x -> v0.15)

// 0. git pull (and checkout) to v0.15
// 1. please create the column "version" in the "software" table first
// 2. then run this script
// 3. afterwards, the "version" column can be removed from the "computer_software" table

if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

require_once(__DIR__.'/../../loader.inc.php');

$dbh = $db->getDbHandle();

$dbh->beginTransaction();

$newSoftwareIds = [];
$newComputerSoftwareIds = [];

$stmt = $dbh->prepare('SELECT cs.*, s.name AS "software_name", s.description AS "software_description" FROM computer_software cs INNER JOIN software s ON cs.software_id = s.id');
$stmt->execute();
foreach($stmt->fetchAll() as $row) {
	// check if such a software entry already exists
	$chkStmt = $dbh->prepare('SELECT * FROM software WHERE BINARY name=:name AND BINARY version=:version AND description=:description');
	$chkStmt->execute([':name'=>$row['software_name'], ':version'=>$row['version'], ':description'=>$row['software_description']]);
	if($chkStmt->rowCount() == 0) {
		echo 'No software found for ('.$row['software_name'].', '.$row['version'].', '.$row['software_description'].') - inserting'."\n";
		$insStmt = $dbh->prepare('INSERT INTO software (name, version, description) VALUES (:name, :version, :description)');
		$insStmt->execute([':name'=>$row['software_name'], ':version'=>$row['version'], ':description'=>$row['software_description']]);
		$insertId = $dbh->lastInsertId();
		$newSoftwareIds[] = $insertId;

		$insStmt = $dbh->prepare('UPDATE computer_software SET software_id=:software_id, version="" WHERE id=:id');
		$insStmt->execute([':id'=>$row['id'], ':software_id'=>$insertId]);
		$newComputerSoftwareIds[] = $dbh->lastInsertId();
	} else {
		echo 'Found software for ('.$row['software_name'].', '.$row['version'].', '.$row['software_description'].') - updating computer_software record'."\n";
		$rows = $chkStmt->fetchAll();
		$insStmt = $dbh->prepare('UPDATE computer_software SET software_id=:software_id, version="" WHERE id=:id');
		$insStmt->execute([':id'=>$row['id'], ':software_id'=>$rows[0]['id']]);
		$newComputerSoftwareIds[] = $row['id'];
	}
}

$stmt = $dbh->prepare('DELETE FROM software WHERE id NOT IN ('.implode(',', $newSoftwareIds).')');
$stmt->execute();

$stmt = $dbh->prepare('DELETE FROM computer_software WHERE id NOT IN ('.implode(',', $newComputerSoftwareIds).')');
$stmt->execute();

$dbh->commit();
#$dbh->rollBack();
