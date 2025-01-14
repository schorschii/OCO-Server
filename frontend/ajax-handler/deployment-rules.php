<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {

	if(!empty($_POST['edit_deployment_rule_id'])
	&& isset($_POST['name'])
	&& isset($_POST['enabled'])
	&& isset($_POST['computer_group_id'])
	&& isset($_POST['package_group_id'])
	&& isset($_POST['priority'])
	&& isset($_POST['notes'])) {
		if($_POST['edit_deployment_rule_id'] == '-1') {
			die($cl->createDeploymentRule(
				$_POST['name'],
				$_POST['notes'],
				$_POST['enabled'],
				$_POST['computer_group_id'],
				$_POST['package_group_id'],
				$_POST['priority']
			));
		} else {
			$cl->editDeploymentRule($_POST['edit_deployment_rule_id'],
				$_POST['name'],
				$_POST['notes'],
				$_POST['enabled'],
				$_POST['computer_group_id'],
				$_POST['package_group_id'],
				$_POST['priority']
			);
		}
		die();
	}

	if(!empty($_POST['renew_deployment_rule'])) {
		$cl->renewDeploymentRuleJob($_POST['renew_deployment_rule'], $_POST['job_id'] ?? []);
		die();
	}

	if(!empty($_POST['remove_deployment_rule_id']) && is_array($_POST['remove_deployment_rule_id'])) {
		foreach($_POST['remove_deployment_rule_id'] as $id) {
			$cl->removeDeploymentRule($id);
		}
		die();
	}

	if(isset($_POST['evaluate_deployment_rule_id'])) {
		$cl->evaluateDeploymentRule($_POST['evaluate_deployment_rule_id']);
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die(htmlspecialchars($e->getMessage()));
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
