<?php

class HouseKeeping {

	// this script is intended to be called periodically every 10 minutes via cron

	private /*DatabaseController*/ $db;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->debug = $debug;
	}

	public function cleanup() {
		$this->jobHouseKeeping();
		$this->jobWol();
		$this->logonHouseKeeping();
		$this->logHouseKeeping();
		if($this->debug) echo('Housekeeping Done.'."\n");
	}

	private function jobHouseKeeping() {
		foreach($this->db->getAllJobContainer() as $container) {
			// purge old jobs
			$icon = $this->db->getJobContainerIcon($container->id);
			if($icon == Models\JobContainer::STATUS_SUCCEEDED) {
				if(time() - strtotime($container->execution_finished) > PURGE_SUCCEEDED_JOBS_AFTER) {
					if($this->debug) echo('Remove succeeded job container #'.$container->id.' ('.$container->name.')'."\n");
					$this->db->removeJobContainer($container->id);
				}
			}
			elseif($icon == Models\JobContainer::STATUS_FAILED) {
				if(time() - strtotime($container->execution_finished) > PURGE_FAILED_JOBS_AFTER) {
					if($this->debug) echo('Remove failed job container #'.$container->id.' ('.$container->name.')'."\n");
					$this->db->removeJobContainer($container->id);
				}
			}

			// set job state to expired if job container end date reached
			if($container->end_time !== null && strtotime($container->end_time) < time()) {
				foreach($this->db->getAllJobByContainer($container->id) as $job) {
					if($job->state == Models\Job::STATUS_WAITING_FOR_CLIENT || $job->state == Models\Job::STATUS_DOWNLOAD_STARTED || $job->state == Models\Job::STATUS_EXECUTION_STARTED) {
						if($this->debug) echo('Set job #'.$job->id.' (container #'.$container->id.', '.$container->name.') state to EXPIRED'."\n");
						$this->db->updateJobState($job->id, Models\Job::STATUS_EXPIRED, NULL, '');
					}
				}
			}
		}
	}

	private function jobWol() {
		foreach($this->db->getAllJobContainer() as $container) {
			if($container->wol_sent == 0) {
				if(strtotime($container->start_time) <= time()) {
					if($this->debug) echo('Execute WOL for job container #'.$container->id.' ('.$container->name.')'."\n");
					// check if computers are currently online (to know if we should shut them down after all jobs are done)
					if(!empty($container->shutdown_waked_after_completion)) {
						if($this->debug) echo('   Check if computers are online for possible shutdown after completion'."\n");
						$this->db->setComputerOnlineStateForWolShutdown($container->id);
					}
					// collect MAC addresses for WOL
					$wolMacAddresses = [];
					foreach($this->db->getComputerMacByContainer($container->id) as $c) {
						if($this->debug) echo('   Found MAC address '.$c->computer_network_mac."\n");
						$wolMacAddresses[] = $c->computer_network_mac;
					}
					// send WOL packet
					if($this->debug) echo('   Sending '.count($wolMacAddresses).' WOL Magic Packets'."\n");
					WakeOnLan::wol($wolMacAddresses, $this->debug);
					// update WOL sent info in db
					$this->db->updateJobContainer(
						$container->id,
						$container->name,
						$container->enabled,
						$container->start_time,
						$container->end_time,
						$container->notes,
						1 /* WOL sent */,
						$container->shutdown_waked_after_completion,
						$container->sequence_mode,
						$container->priority,
						$container->agent_ip_ranges
					);
				}
			}

			// check WOL status (for shutting it down later)
			if(!empty($container->shutdown_waked_after_completion)) {
				foreach($this->db->getAllJobByContainer($container->id) as $j) {
					if(!empty($j->wol_shutdown_set)) {
						$c = $this->db->getComputer($j->computer_id); if(!$c) continue;
						// The computer came up in the defined time range, this means WOL worked.
						// Remove the "WOL Shutdown Set" flag to keep the shutdown forever.
						if($c->isOnline() && time() - strtotime($j->wol_shutdown_set) < WOL_SHUTDOWN_EXPIRY_SECONDS) {
							if($this->debug) echo('Host came up before WOL shutdown expiry. Keep shutdown of job #'.$j->id.' (in container #'.$container->id.' '.$container->name.') forever'."\n");
							$this->db->removeWolShutdownJobInContainer($container->id, $j->id, Models\Package::POST_ACTION_SHUTDOWN);
						}
						// If the computer does not came up with WOL after a certain time, WOL didn't work -> remove the shutdown.
						// It is likely that a user has now manually powered on the machine. Then, an automatic shutdown is not desired anymore.
						if(time() - strtotime($j->wol_shutdown_set) > WOL_SHUTDOWN_EXPIRY_SECONDS) {
							if($this->debug) echo('Remove expired WOL shutdown for job #'.$j->id.' (in container #'.$container->id.' '.$container->name.')'."\n");
							$this->db->removeWolShutdownJobInContainer($container->id, $j->id, Models\Package::POST_ACTION_NONE);
						}
					}
				}
			}
		}
	}

	private function logonHouseKeeping() {
		$result = $this->db->removeDomainUserLogonOlderThan(PURGE_DOMAIN_USER_LOGONS_AFTER);
		if($this->debug) echo('Purged '.intval($result).' domain user logons older than '.intval(PURGE_DOMAIN_USER_LOGONS_AFTER).' seconds'."\n");
	}

	private function logHouseKeeping() {
		$result = $this->db->removeLogEntryOlderThan(PURGE_LOGS_AFTER);
		if($this->debug) echo('Purged '.intval($result).' log entries older than '.intval(PURGE_LOGS_AFTER).' seconds'."\n");
	}
}
