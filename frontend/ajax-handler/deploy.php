<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

try {

	// ----- refresh list content if requested -----
	if(isset($_GET['get_computer_group_members'])) {
		$group = $db->getComputerGroup($_GET['get_computer_group_members']);
		$computers = [];
		if(empty($group)) $computers = $db->getAllComputer();
		else $computers = $db->getComputerByGroup($group->id);

		echo "<a class='blockListItem noSearch big' onclick='refreshDeployComputerList()'><img src='img/arrow-back.dyn.svg'>".LANG['back']."</a>";
		foreach($computers as $c) {
			if(!$currentSystemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({".$c->id.": this.innerText}, divTargetComputerList, \"target_computers\")'><input type='checkbox' name='computers' onclick='refreshDeployComputerCount()' value='".$c->id."' />".htmlspecialchars($c->hostname)."</label>";
		}
		die();
	}
	if(isset($_GET['get_computer_report_results'])) {
		$reportResult = $cl->executeReport($_GET['get_computer_report_results']);

		echo "<a class='blockListItem noSearch big' onclick='refreshDeployComputerList()'><img src='img/arrow-back.dyn.svg'>".LANG['back']."</a>";
		foreach($reportResult as $row) {
			if(empty($row['computer_id'])) continue;
			$c = $db->getComputer($row['computer_id']);
			if(!$currentSystemUser->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({".$c->id.": this.innerText}, divTargetComputerList, \"target_computers\")'><input type='checkbox' name='computers' onclick='refreshDeployComputerCount()' value='".$c->id."' />".htmlspecialchars($c->hostname)."</label>";
		}
		die();
	}
	if(isset($_GET['get_package_group_members'])) {
		$group = $db->getPackageGroup($_GET['get_package_group_members']);
		$packages = [];
		if(empty($group)) $packages = $db->getAllPackage(true);
		else $packages = $db->getPackageByGroup($group->id);

		echo "<a class='blockListItem noSearch big' onclick='refreshDeployPackageList()'><img src='img/arrow-back.dyn.svg'>".LANG['back']."</a>";
		foreach($packages as $p) {
			if(!$currentSystemUser->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({".$p->id.": this.innerText}, divTargetPackageList, \"target_packages\")'><input type='checkbox' name='packages' onclick='refreshDeployPackageCount()' value='".$p->id."' />".htmlspecialchars($p->getFullName())."</label>";
		}
		die();
	}
	if(isset($_GET['get_package_report_results'])) {
		$reportResult = $cl->executeReport($_GET['get_package_report_results']);

		echo "<a class='blockListItem noSearch big' onclick='refreshDeployPackageList()'><img src='img/arrow-back.dyn.svg'>".LANG['back']."</a>";
		foreach($reportResult as $row) {
			if(empty($row['package_id'])) continue;
			$p = $db->getPackage($row['package_id']);
			if(!$currentSystemUser->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({".$p->id.": this.innerText}, divTargetPackageList, \"target_packages\")'><input type='checkbox' name='packages' onclick='refreshDeployPackageCount()' value='".$p->id."' />".htmlspecialchars($p->getFullName())."</label>";
		}
		die();
	}

	// ----- create install jobs if requested -----
	if(isset($_POST['create_install_job_container'])) {
		// compile constraints
		$agentIpRanges = [];
		if(!empty($_POST['constraint_ip_range'])) {
			if(is_string($_POST['constraint_ip_range'])) {
				$agentIpRanges[] = $_POST['constraint_ip_range'];
			}
			elseif(is_array($_POST['constraint_ip_range'])) {
				$agentIpRanges = $_POST['constraint_ip_range'];
			}
		}
		// create container + jobs
		$jcid = $cl->deploy(
			$_POST['create_install_job_container'], $_POST['description'], $_SESSION['oco_username'],
			$_POST['computer_id'] ?? [], $_POST['computer_group_id'] ?? [], $_POST['computer_report_id'] ?? [],
			$_POST['package_id'] ?? [], $_POST['package_group_id'] ?? [], $_POST['package_report_id'] ?? [],
			$_POST['date_start'], $_POST['date_end'] ?? null,
			$_POST['use_wol'] ?? 1, $_POST['shutdown_waked_after_completion'] ?? 0, $_POST['restart_timeout'] ?? 5,
			$_POST['auto_create_uninstall_jobs'] ?? 1, $_POST['force_install_same_version'] ?? 0,
			$_POST['sequence_mode'] ?? 0, $_POST['priority'] ?? 0, $agentIpRanges
		);
		die(strval(intval($jcid)));
	}

	// ----- create uninstall jobs if requested -----
	if(isset($_POST['create_uninstall_job_container'])
	&& !empty($_POST['uninstall_package_assignment_id'])
	&& is_array($_POST['uninstall_package_assignment_id'])
	&& isset($_POST['notes'])
	&& isset($_POST['start_time'])
	&& isset($_POST['end_time'])
	&& isset($_POST['use_wol'])
	&& isset($_POST['shutdown_waked_after_completion'])
	&& isset($_POST['restart_timeout'])
	&& isset($_POST['priority'])) {
		$cl->uninstall(
			$_POST['create_uninstall_job_container'], $_POST['notes'], $_SESSION['oco_username'],
			$_POST['uninstall_package_assignment_id'], $_POST['start_time'], $_POST['end_time'],
			$_POST['use_wol'], $_POST['shutdown_waked_after_completion'], $_POST['restart_timeout'],
			0/*sequence mode*/, $_POST['priority']
		);
		die();
	}

	// ----- remove package-computer assignment if requested -----
	if(!empty($_POST['remove_package_assignment_id'])
	&& is_array($_POST['remove_package_assignment_id'])) {
		foreach($_POST['remove_package_assignment_id'] as $id) {
			$cl->removeComputerAssignedPackage($id);
		}
		die();
	}

	// ----- renew failed jobs in container if requested -----
	if(!empty($_POST['create_renew_job_container'])
	&& isset($_POST['renew_container_id'])
	&& isset($_POST['notes'])
	&& isset($_POST['start_time'])
	&& isset($_POST['end_time'])
	&& isset($_POST['use_wol'])
	&& isset($_POST['shutdown_waked_after_completion'])
	&& isset($_POST['priority'])) {
		$cl->renewFailedJobsInContainer(
			$_POST['create_renew_job_container'], $_POST['notes'], $_SESSION['oco_username'],
			$_POST['renew_container_id'], $_POST['start_time'], $_POST['end_time'],
			$_POST['use_wol'], $_POST['shutdown_waked_after_completion'],
			0/*sequence mode*/, $_POST['priority']
		);
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG['permission_denied']);
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG['unknown_method']);
