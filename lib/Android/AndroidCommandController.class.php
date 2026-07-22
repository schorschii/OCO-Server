<?php

namespace Android;

class AndroidCommandController extends \MobileDeviceCommandControllerBase {

	private $ae;
	private $debug;

	function __construct(\DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->ae = new AndroidEnrollment($db);
		$this->debug = $debug;
	}

	function syncAppsProfiles(\Models\MobileDevice $md) {
		$appPolicy = $this->androidAppInstalls($md);
		$generalPolicy = $this->androidPolicies($md);
		return $this->androidPoliciesPatch($md, array_merge($appPolicy, $generalPolicy));
	}

	private function androidAppInstalls(\Models\MobileDevice $md) {
		// check if every assigned app is installed, otherwise add to policy
		$policy = [];
		foreach($this->getManagedAppsByMobileDeviceId($md->id) as $app) {
			if($app->type != \Models\ManagedApp::TYPE_ANDROID) continue;

			if(empty($policy)) $policy['applications'] = [];

			$appConfig = [
				'packageName' => $app->identifier,
				'installType' => $app->install_type,
				'delegatedScopes' => empty($app->delegated_scopes) ? [] : explode("\n", $app->delegated_scopes),
			];
			if(empty($app->config_id)) {
				$appConfig['managedConfiguration'] = json_decode($app->config, true);
			} else {
				$appConfig['managedConfigurationTemplate'] = [
					'templateId' => strval($app->config_id),
					'configurationVariables' => json_decode($app->config, true),
				];
			}

			// replace parameters
			if(!self::replacePlaceholders($appConfig, json_decode($md->parameters, true)??[])) {
				echo('Cannot install app '.$app->identifier.' because a parameter is missing'."\n");
				continue;
			}

			$policy['applications'][] = $appConfig;
		}
		return $policy;
	}

	private function androidPolicies(\Models\MobileDevice $md) {
		// check if every assigned policy is applied, otherwise add it to device policy
		$policy = [];
		foreach($this->getProfilesByMobileDeviceId($md->id) as $p) {
			if($p->type != \Models\Profile::TYPE_ANDROID) continue;

			// check valid JSON
			$policyValues = json_decode($p->payload, true);
			if(!$policyValues || !is_array($policyValues)) continue;

			// replace parameters
			if(!self::replacePlaceholders($policyValues, json_decode($md->parameters, true)??[])) {
				echo('Cannot install policy '.$p->name.' because a parameter is missing'."\n");
				continue;
			}

			// merge policy values
			$policy = array_merge($policy, $policyValues);
		}
		return $policy;
	}

	private function androidPoliciesPatch(\Models\MobileDevice $md, array $policyValues) {
		$success = true;
		$appCount = count($policyValues['applications']??[]);
		$policyCount = count($policyValues);
		$newPolicy = json_encode($policyValues);

		// sync policy with Google API if changed
		if($newPolicy != $md->policy) {
			try {
				$this->ae->patchPolicy(strval($md->udid), $policyValues, strval($md->udid));
				echo('Updated policy for device '.$md->udid.' ('.$appCount.' apps, '.$policyCount.' policies)'."\n");
				$this->db->updateMobileDevice($md->id,
					$md->udid, $md->state, $md->device_name, $md->serial, $md->vendor_description,
					$md->model, $os??$md->os, $md->device_family, $md->color,
					$md->profile_uuid, $md->push_token, $md->push_magic, $md->push_sent, $md->unlock_token,
					$md->info, $newPolicy, $md->parameters, $md->notes, $md->force_update
				);
			} catch(Exception $e) {
				// if it fails, continue execution for other devices
				echo('Error updating policy for device '.$md->udid.': '.$e->getMessage()."\n");
				$success = false;
			}
		}
		return $success;
	}

}
