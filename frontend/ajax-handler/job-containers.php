<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	// ----- refresh list content if requested -----
	if(isset($_GET['get_computer_group_members'])) {
		$group = $db->selectComputerGroup($_GET['get_computer_group_members']);
		$computers = [];
		if(empty($group)) $computers = $db->selectAllComputer();
		else $computers = $db->selectAllComputerByComputerGroupId($group->id);

		echo "<a href='#' class='blockListItem noSearch big' onclick='refreshDeployComputerList();return false'><img src='img/arrow-back.dyn.svg'>".LANG('back')."</a>";
		foreach($computers as $c) {
			if(!$cl->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({\"id\":".$c->id.",\"name\":this.innerText}, divTargetComputerList, \"target_computers\")'><input type='checkbox' name='computers' onclick='refreshDeployComputerCount()' value='".$c->id."' />".htmlspecialchars($c->hostname)."</label>";
		}
		die();
	}
	if(isset($_GET['get_computer_report_results'])) {
		$reportResult = $cl->executeReport($_GET['get_computer_report_results']);

		echo "<a href='#' class='blockListItem noSearch big' onclick='refreshDeployComputerList();return false'><img src='img/arrow-back.dyn.svg'>".LANG('back')."</a>";
		foreach($reportResult as $row) {
			if(empty($row['computer_id'])) continue;
			$c = $db->selectComputer($row['computer_id']);
			if(!$cl->checkPermission($c, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({\"id\":".$c->id.",\"name\":this.innerText}, divTargetComputerList, \"target_computers\")'><input type='checkbox' name='computers' onclick='refreshDeployComputerCount()' value='".$c->id."' />".htmlspecialchars($c->hostname)."</label>";
		}
		die();
	}
	if(isset($_GET['get_package_group_members'])) {
		$group = $db->selectPackageGroup($_GET['get_package_group_members']);
		$packages = [];
		if(empty($group)) $packages = $db->selectAllPackage(true);
		else $packages = $db->selectAllPackageByPackageGroupId($group->id);

		echo "<a href='#' class='blockListItem noSearch big' onclick='refreshDeployPackageList();return false'><img src='img/arrow-back.dyn.svg'>".LANG('back')."</a>";
		foreach($packages as $p) {
			if(!$cl->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({\"id\":".$p->id.",\"name\":this.innerText}, divTargetPackageList, \"target_packages\")'><input type='checkbox' name='packages' onclick='refreshDeployPackageCount()' value='".$p->id."' />".htmlspecialchars($p->getFullName())."</label>";
		}
		die();
	}
	if(isset($_GET['get_package_report_results'])) {
		$reportResult = $cl->executeReport($_GET['get_package_report_results']);

		echo "<a href='#' class='blockListItem noSearch big' onclick='refreshDeployPackageList();return false'><img src='img/arrow-back.dyn.svg'>".LANG('back')."</a>";
		foreach($reportResult as $row) {
			if(empty($row['package_id'])) continue;
			$p = $db->selectPackage($row['package_id']);
			if(!$cl->checkPermission($p, PermissionManager::METHOD_DEPLOY, false)) continue;

			echo "<label class='blockListItem' ondblclick='addToDeployTarget({\"id\":".$p->id.",\"name\":this.innerText}, divTargetPackageList, \"target_packages\")'><input type='checkbox' name='packages' onclick='refreshDeployPackageCount()' value='".$p->id."' />".htmlspecialchars($p->getFullName())."</label>";
		}
		die();
	}

	// ----- create install jobs if requested -----
	if(isset($_POST['create_install_job_container'])) {
		// compile constraints
		$agentIpRanges = [];
		if(!empty($_POST['agent_ip_ranges']) && is_string($_POST['agent_ip_ranges'])) {
			foreach(explode(',', $_POST['agent_ip_ranges']) as $range) {
				$agentIpRanges[] = $range;
			}
		}
		$timeFrames = [];
		if(!empty($_POST['time_frames']) && is_string($_POST['time_frames'])) {
			foreach(explode(',', $_POST['time_frames']) as $range) {
				$timeFrames[] = $range;
			}
		}
		// create container + jobs
		die($cl->deploy(
			$_POST['create_install_job_container'], $_POST['description'] ?? '',
			$_POST['computer_id'] ?? [], $_POST['computer_group_id'] ?? [], $_POST['computer_report_id'] ?? [],
			$_POST['package_id'] ?? [], $_POST['package_group_id'] ?? [], $_POST['package_report_id'] ?? [],
			$_POST['date_start'], $_POST['date_end'] ?? null,
			$_POST['use_wol'] ?? 1, $_POST['shutdown_waked_after_completion'] ?? 0, $_POST['restart_timeout'] ?? 5,
			$_POST['force_install_same_version'] ?? 0, $_POST['sequence_mode'] ?? 0, $_POST['priority'] ?? 0,
			$agentIpRanges, $timeFrames
		));
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
			$_POST['create_uninstall_job_container'], $_POST['notes'],
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
	if(isset($_POST['renew_job_container'])
	&& isset($_POST['create_new_job_container'])
	&& isset($_POST['job_container_name'])
	&& isset($_POST['notes'])
	&& isset($_POST['start_time'])
	&& isset($_POST['end_time'])
	&& isset($_POST['use_wol'])
	&& isset($_POST['shutdown_waked_after_completion'])
	&& isset($_POST['priority'])) {
		die($cl->renewFailedStaticJobsInJobContainer(
			$_POST['renew_job_container'], $_POST['job_id'] ?? [], $_POST['create_new_job_container'],
			$_POST['job_container_name'], $_POST['notes'],
			$_POST['start_time'], $_POST['end_time'],
			$_POST['use_wol'], $_POST['shutdown_waked_after_completion'],
			0/*sequence mode*/, $_POST['priority']
		));
	}

	// ----- remove jobs in container if requested -----
	if(!empty($_POST['remove_job_id']) && is_array($_POST['remove_job_id'])) {
		foreach($_POST['remove_job_id'] as $id) {
			$cl->removeStaticJob($id);
		}
		die();
	}

	// ----- update job container if requested -----
	if(!empty($_POST['edit_job_container_id'])
	&& isset($_POST['name'])
	&& isset($_POST['enabled'])
	&& isset($_POST['start'])
	&& isset($_POST['end'])
	&& isset($_POST['sequence_mode'])
	&& isset($_POST['priority'])
	&& isset($_POST['agent_ip_ranges'])
	&& isset($_POST['time_frames'])
	&& isset($_POST['notes'])) {
		// compile constraints
		$agentIpRanges = [];
		if(!empty($_POST['agent_ip_ranges']) && is_string($_POST['agent_ip_ranges'])) {
			foreach(explode(',', $_POST['agent_ip_ranges']) as $range) {
				$agentIpRanges[] = $range;
			}
		}
		$timeFrames = [];
		if(!empty($_POST['time_frames']) && is_string($_POST['time_frames'])) {
			foreach(explode(',', $_POST['time_frames']) as $range) {
				$timeFrames[] = $range;
			}
		}
		// update
		$cl->editJobContainer($_POST['edit_job_container_id'],
			$_POST['name'],
			$_POST['enabled'],
			$_POST['start'],
			$_POST['end'],
			$_POST['notes'],
			$_POST['sequence_mode'],
			$_POST['priority'],
			$agentIpRanges,
			$timeFrames
		);
		die();
	}

	// ----- remove job container if requested -----
	if(!empty($_POST['remove_container_id']) && is_array($_POST['remove_container_id'])) {
		foreach($_POST['remove_container_id'] as $id) {
			$cl->removeJobContainer($id);
		}
		die();
	}

	// ----- move jobs in job container if requested -----
	if(isset($_POST['move_to_container_id']) && is_array($_POST['move_to_container_id']) && isset($_POST['move_to_container_job_id']) && is_array($_POST['move_to_container_job_id'])) {
		foreach($_POST['move_to_container_job_id'] as $cid) {
			foreach($_POST['move_to_container_id'] as $gid) {
				$cl->moveStaticJobToJobContainer($cid, $gid);
			}
		}
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
