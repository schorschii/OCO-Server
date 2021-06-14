<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

$select_computer_ids = [];
$select_computer_group_ids = [];
$select_package_ids = [];
$select_package_group_ids = [];
if(!empty($_GET['computer_id']) && is_array($_GET['computer_id'])) {
	$select_computer_ids = $_GET['computer_id'];
}
if(!empty($_GET['computer_group_id']) && is_array($_GET['computer_group_id'])) {
	$select_computer_group_ids = $_GET['computer_group_id'];
}
if(!empty($_GET['package_id']) && is_array($_GET['package_id'])) {
	$select_package_ids = $_GET['package_id'];
}
if(!empty($_GET['package_group_id']) && is_array($_GET['package_group_id'])) {
	$select_package_group_ids = $_GET['package_group_id'];
}

// ----- refresh list content if requested -----
if(isset($_GET['get_computer_group_members'])) {
	$group = $db->getComputerGroup($_GET['get_computer_group_members']);
	$computers = [];
	if(empty($group)) $computers = $db->getAllComputer();
	else $computers = $db->getComputerByGroup($group->id);
	foreach($computers as $c) {
		$selected = '';
		if(!empty($group) || in_array($c->id, $select_computer_ids)) $selected = 'selected';
		echo "<option value='".$c->id."' ".$selected.">".htmlspecialchars($c->hostname)."</option>";
	}
	die();
}
if(isset($_GET['get_package_group_members'])) {
	$group = $db->getPackageGroup($_GET['get_package_group_members']);
	$packages = [];
	if(empty($group)) $packages = $db->getAllPackage(true);
	else $packages = $db->getPackageByGroup($group->id);
	foreach($packages as $p) {
		$selected = '';
		if(!empty($group) || in_array($p->id, $select_package_ids)) $selected = 'selected';
		echo "<option value='".$p->id."' ".$selected.">".htmlspecialchars($p->name)." (".htmlspecialchars($p->version).")"."</option>";
	}
	die();
}

// ----- create jobs if requested -----
if(!empty($_POST['add_jobcontainer'])) {
	// check user input
	if(empty($_POST['restart_timeout']) || empty($_POST['date_start']) || strtotime($_POST['date_start']) === false) {
		header('HTTP/1.1 400 Missing Information');
		die(LANG['please_fill_required_fields']);
	}
	if(!empty($_POST['date_end']) // check date_end if not empty
	&& (strtotime($_POST['date_end']) === false || strtotime($_POST['date_start']) >= strtotime($_POST['date_end']))
	) {
		header('HTTP/1.1 400 Invalid Request');
		die(LANG['end_time_before_start_time']);
	}

	// check if given IDs exist
	$computer_ids = [];
	$packages = [];
	$computer_group_ids = [];
	$package_group_ids = [];
	if(!empty($_POST['computer_id'])) foreach($_POST['computer_id'] as $computer_id) {
		if($db->getComputer($computer_id) !== null) $computer_ids[$computer_id] = $computer_id;
	}
	if(!empty($_POST['computer_group_id'])) foreach($_POST['computer_group_id'] as $computer_group_id) {
		if($db->getComputerGroup($computer_group_id) !== null) $computer_group_ids[] = $computer_group_id;
	}

	if(!empty($_POST['package_id'])) foreach($_POST['package_id'] as $package_id) {
		$p = $db->getPackage($package_id);
		if($p !== null) $packages[$p->id] = [
			'package_family_id' => $p->package_family_id,
			'procedure' => $p->install_procedure,
			'success_return_codes' => $p->install_procedure_success_return_codes,
			'install_procedure_restart' => $p->install_procedure_restart,
			'install_procedure_shutdown' => $p->install_procedure_shutdown,
			'download' => $p->getFilePath() ? true : false,
		];
	}
	if(!empty($_POST['package_group_id'])) foreach($_POST['package_group_id'] as $package_group_id) {
		if($db->getPackageGroup($package_group_id) !== null) $package_group_ids[] = $package_group_id;
	}

	// if multiple groups selected: add all group members
	if(count($computer_group_ids) > 1) foreach($computer_group_ids as $computer_group_id) {
		foreach($db->getComputerByGroup($computer_group_id) as $c) {
			$computer_ids[$c->id] = $c->id;
		}
	}
	if(count($package_group_ids) > 1) foreach($package_group_ids as $package_group_id) {
		foreach($db->getPackageByGroup($package_group_id) as $p) {
			$packages[$p->id] = [
				'package_family_id' => $p->package_family_id,
				'procedure' => $p->install_procedure,
				'success_return_codes' => $p->install_procedure_success_return_codes,
				'install_procedure_restart' => $p->install_procedure_restart,
				'install_procedure_shutdown' => $p->install_procedure_shutdown,
				'download' => $p->getFilePath() ? true : false,
			];
		}
	}

	// check if there are any computer & packages
	if(count($computer_ids) == 0 || count($packages) == 0) {
		header('HTTP/1.1 400 No Jobs Created');
		die(LANG['no_jobs_created']);
	}

	// wol handling
	$wolSent = -1;
	if($_POST['use_wol']) {
		if(strtotime($_POST['date_start']) <= time()) {
			// instant WOL if start time is already in the past
			$wolSent = 1;
			$wolMacAdresses = [];
			foreach($computer_ids as $cid) {
				foreach($db->getComputerNetwork($cid) as $cn) {
					$wolMacAdresses[] = $cn->mac;
				}
			}
			wol($wolMacAdresses, false);
		} else {
			$wolSent = 0;
		}
	}

	// create jobs
	if($jcid = $db->addJobContainer(
		$_POST['add_jobcontainer'], $_SESSION['um_username'],
		empty($_POST['date_start']) ? date('Y-m-d H:i:s') : $_POST['date_start'],
		empty($_POST['date_end']) ? null : $_POST['date_end'],
		$_POST['description'],
		$wolSent
	)) {
		foreach($computer_ids as $computer_id) {
			$sequence = 1;

			foreach($packages as $pid => $package) {

				// create uninstall jobs
				if(!empty($_POST['auto_create_uninstall_jobs'])) {
					foreach($db->getComputerPackage($computer_id) as $cp) {
						// uninstall it, if it is from the same package family
						if($cp->package_family_id === $package['package_family_id']) {
							$cpp = $db->getPackage($cp->package_id);
							if($cpp == null || empty($cpp->uninstall_procedure)) continue;
							$db->addJob($jcid, $computer_id,
								$cpp->id, $cpp->uninstall_procedure, $cpp->uninstall_procedure_success_return_codes,
								1/*is_uninstall*/, $cpp->download_for_uninstall,
								$cpp->uninstall_procedure_restart ? $_POST['restart_timeout'] : -1,
								$cpp->uninstall_procedure_shutdown ? $_POST['restart_timeout'] : -1,
								$sequence
							);
							$sequence ++;
						}
					}
				}

				// create job
				if($db->addJob($jcid, $computer_id,
					$pid, $package['procedure'], $package['success_return_codes'],
					0/*is_uninstall*/, $package['download'] ? 1 : 0/*download*/,
					$package['install_procedure_restart'] ? $_POST['restart_timeout'] : -1,
					$package['install_procedure_shutdown'] ? $_POST['restart_timeout'] : -1,
					$sequence
				)) {
					$sequence ++;
				}
			}
		}
	}

	die(strval(intval($jcid)));
}
?>

<h1><?php echo LANG['deployment_assistant']; ?></h1>

<table class='form'>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td>
			<input type='text' id='txtName' value='<?php echo LANG['install'].' '.date('y-m-d H:i:s'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td>
			<textarea id='txtDescription'></textarea>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['start']; ?></th>
		<td>
			<input type='date' id='dteStart' value='<?php echo date('Y-m-d'); ?>'></input>
			<input type='time' id='tmeStart' value='<?php echo date('H:i'); ?>'></input>
		</td>

		<th><?php echo LANG['end']; ?></th>
		<td>
			<input type='date' id='dteEnd' value='' onchange='chkDateEndEnabled.checked=true'></input>
			<input type='time' id='tmeEnd' value='' onchange='chkDateEndEnabled.checked=true'></input>
		</td>
	</tr>
	<tr>
		<th></th>
		<td><label><input type='checkbox' id='chkWol'><?php echo LANG['send_wol']; ?></label></td>
		<th></th>
		<td><label><input type='checkbox' id='chkDateEndEnabled'><?php echo LANG['set_end']; ?></label></td>
	</tr>
	<tr>
		<th><?php echo LANG['timeout_for_reboot']; ?></th>
		<td>
			<input type='number' id='txtRestartTimeout' value='<?php echo htmlspecialchars($db->getSettingByName('default-restart-timeout')); ?>' min='-1' title='<?php echo LANG['timeout_for_reboot_description']; ?>'></input>
		</td>
		<td><?php echo LANG['minutes']; ?></td>
	</tr>
</table>

<h2><?php echo LANG['target_computer']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['computer_groups']; ?></h3>
		<select id='sltComputerGroup' size='10' multiple='true' onchange='if(getSelectValues(this).length > 1) { sltComputer.innerHTML="";sltComputer.disabled=true;refreshDeployCount(); }else{ sltComputer.disabled=false;refreshDeployComputerAndPackages(this.value, null); }'>
			<option value='-1'><?php echo LANG['all_computer']; ?></option>
			<?php echoTargetComputerGroupOptions($db, $select_computer_group_ids); ?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['computer']; ?> (<span id='spnSelectedComputers'>0</span>/<span id='spnTotalComputers'>0</span>)</h3>
		<select id='sltComputer' size='10' multiple='true' onchange='refreshDeployCount()'>
			<!-- filled by JS -->
		</select>
	</div>
</div>

<h2><?php echo LANG['packages_to_deploy']; ?></h2>
<div class='gallery'>
	<div>
		<h3><?php echo LANG['package_groups']; ?></h3>
		<select id='sltPackageGroup' size='10' multiple='true' onchange='if(getSelectValues(this).length > 1) { sltPackage.innerHTML="";sltPackage.disabled=true;refreshDeployCount(); }else{ sltPackage.disabled=false;refreshDeployComputerAndPackages(null, this.value) }'>
			<option value='-1'><?php echo LANG['all_packages']; ?></option>
			<?php echoTargetPackageGroupOptions($db, $select_package_group_ids); ?>
		</select>
	</div>
	<div>
		<h3><?php echo LANG['packages']; ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<select id='sltPackage' size='10' multiple='true' onchange='refreshDeployCount()'>
			<!-- filled by JS -->
		</select>
	</div>
</div>

<div class='controls'>
	<button id='btnDeploy' onclick='deploy(txtName.value, dteStart.value+" "+tmeStart.value, chkDateEndEnabled.checked ? dteEnd.value+" "+tmeEnd.value : "", txtDescription.value, sltComputer, sltComputerGroup, sltPackage, sltPackageGroup, chkWol.checked, chkAutoCreateUninstallJobs.checked, txtRestartTimeout.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['deploy']; ?></button>
	<label><input type='checkbox' id='chkAutoCreateUninstallJobs' <?php if(!empty($db->getSettingByName('default-auto-create-uninstall-jobs'))) echo 'checked'; ?>>&nbsp;<div><?php echo LANG['auto_create_uninstall_jobs']; ?></div></label>
</div>

<?php
function echoTargetComputerGroupOptions($db, $select_computer_group_ids, $parent=null, $indent=0) {
	foreach($db->getAllComputerGroup($parent) as $cg) {
		$selected = '';
		if(in_array($cg->id, $select_computer_group_ids)) $selected = 'selected';
		echo "<option value='".htmlspecialchars($cg->id)."' ".$selected.">".trim(str_repeat("‒",$indent)." ".htmlspecialchars($cg->name))."</option>";
		echoTargetComputerGroupOptions($db, $select_computer_group_ids, $cg->id, $indent+1);
	}
}
function echoTargetPackageGroupOptions($db, $select_package_group_ids, $parent=null, $indent=0) {
	foreach($db->getAllPackageGroup($parent) as $pg) {
		$selected = '';
		if(in_array($pg->id, $select_package_group_ids)) $selected = 'selected';
		echo "<option value='".htmlspecialchars($pg->id)."' ".$selected.">".trim(str_repeat("‒",$indent)." ".htmlspecialchars($pg->name))."</option>";
		echoTargetPackageGroupOptions($db, $select_package_group_ids, $pg->id, $indent+1);
	}
}
