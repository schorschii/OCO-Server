<?php

class HouseKeeping {

	// this script is intended to be called periodically every 10 minutes via cron

	private /*DatabaseController*/ $db;
	private /*ExtensionController*/ $ext;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, ExtensionController $ext, bool $debug=false) {
		$this->db = $db;
		$this->ext = $ext;
		$this->debug = $debug;
	}

	public function cleanup() {
		if($this->debug) echo('===== Housekeeping '.date('Y-m-d H:i:s').' ====='."\n");

		// core housekeeping
		$this->jobHouseKeeping();
		$this->jobWol();
		$this->logonHouseKeeping();
		$this->serviceHouseKeeping();
		$this->eventHouseKeeping();
		$this->logHouseKeeping();

		// extension housekeeping
		foreach($this->ext->getAggregatedConf('housekeeping-function') as $func) {
			if($this->debug) echo('Executing extension function: '.$func."\n");
			call_user_func($func, $this->db);
		}

		if($this->debug) echo('Done.'."\n");
	}

	private function jobHouseKeeping() {
		$purgeSucceededJobsAfter = $this->db->settings->get('purge-succeeded-jobs-after');
		$purgeFailedJobsAfter = $this->db->settings->get('purge-failed-jobs-after');

		foreach($this->db->selectAllJobContainer() as $container) {
			// purge old jobs
			$status = $container->getStatus($this->db->selectAllStaticJobByJobContainer($container->id));
			if($status == Models\JobContainer::STATUS_SUCCEEDED) {
				if(time() - strtotime($container->execution_finished) > $purgeSucceededJobsAfter) {
					if($this->debug) echo('Remove succeeded job container #'.$container->id.' ('.$container->name.')'."\n");
					$this->db->deleteJobContainer($container->id);
				}
			}
			elseif($status == Models\JobContainer::STATUS_FAILED) {
				if(time() - strtotime($container->execution_finished) > $purgeFailedJobsAfter) {
					if($this->debug) echo('Remove failed job container #'.$container->id.' ('.$container->name.')'."\n");
					$this->db->deleteJobContainer($container->id);
				}
			}

			// set job state to expired if job container end date reached
			if($container->end_time !== null && strtotime($container->end_time) < time()) {
				foreach($this->db->selectAllStaticJobByJobContainer($container->id) as $job) {
					if($job->state == Models\Job::STATE_WAITING_FOR_AGENT || $job->state == Models\Job::STATE_DOWNLOAD_STARTED || $job->state == Models\Job::STATE_EXECUTION_STARTED) {
						if($this->debug) echo('Set job #'.$job->id.' (container #'.$container->id.', '.$container->name.') state to EXPIRED'."\n");
						$job->state = Models\Job::STATE_EXPIRED;
						$job->return_code = null;
						$job->message = '';
						$this->db->updateJobExecutionState($job);
					}
				}
			}
		}
	}

	private function jobWol() {
		$wolController = new WakeOnLan($this->db);
		$wolShutdownExpirySeconds = $this->db->settings->get('wol-shutdown-expiry');

		foreach($this->db->selectAllJobContainer() as $container) {
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
					foreach($this->db->selectAllComputerWithMacByJobContainer($container->id) as $c) {
						if($this->debug) echo('   Found MAC address '.$c->computer_network_mac."\n");
						$wolMacAddresses[] = $c->computer_network_mac;
					}
					// send WOL packet
					if($this->debug) echo('   Sending '.count($wolMacAddresses).' WOL Magic Packets'."\n");
					$wolController->wol($wolMacAddresses, $this->debug);
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
						$container->agent_ip_ranges,
						$container->time_frames
					);
				}
			}

			// check WOL status (for shutting it down later)
			if(!empty($container->shutdown_waked_after_completion)) {
				foreach($this->db->selectAllStaticJobByJobContainer($container->id) as $j) {
					if(!empty($j->wol_shutdown_set)) {
						$c = $this->db->selectComputer($j->computer_id); if(!$c) continue;
						// The computer came up in the defined time range, this means WOL worked.
						// Remove the "WOL Shutdown Set" flag to keep the shutdown forever.
						if($c->isOnline($this->db) && time() - strtotime($j->wol_shutdown_set) < $wolShutdownExpirySeconds) {
							if($this->debug) echo('Host came up before WOL shutdown expiry. Keep shutdown of job #'.$j->id.' (in container #'.$container->id.' '.$container->name.') forever'."\n");
							$this->db->removeWolShutdownStaticJobInJobContainer($container->id, $j->id, Models\Package::POST_ACTION_SHUTDOWN);
						}
						// If the computer does not came up with WOL after a certain time, WOL didn't work -> remove the shutdown.
						// It is likely that a user has now manually powered on the machine. Then, an automatic shutdown is not desired anymore.
						if(time() - strtotime($j->wol_shutdown_set) > $wolShutdownExpirySeconds) {
							if($this->debug) echo('Remove expired WOL shutdown for job #'.$j->id.' (in container #'.$container->id.' '.$container->name.')'."\n");
							$this->db->removeWolShutdownStaticJobInJobContainer($container->id, $j->id, Models\Package::POST_ACTION_NONE);
						}
					}
				}
			}
		}
	}

	private function logonHouseKeeping() {
		$purgeDomainUserLogonsAfter = $this->db->settings->get('purge-domain-user-logons-after');
		$result = $this->db->deleteDomainUserLogonOlderThan($purgeDomainUserLogonsAfter);
		if($this->debug) echo('Purged '.intval($result).' domain user logons older than '.intval($purgeDomainUserLogonsAfter).' seconds'."\n");
	}

	private function serviceHouseKeeping() {
		$purgeServiceHistoryAfter = $this->db->settings->get('purge-events-after');
		$result = $this->db->deleteComputerServiceHistoryOlderThan($purgeServiceHistoryAfter);
		if($this->debug) echo('Purged '.intval($result).' service history entries older than '.intval($purgeServiceHistoryAfter).' seconds'."\n");
	}

	private function eventHouseKeeping() {
		$purgeEventsAfter = $this->db->settings->get('purge-events-after');
		$result = $this->db->deleteComputerEventOlderThan($purgeEventsAfter);
		if($this->debug) echo('Purged '.intval($result).' events older than '.intval($purgeEventsAfter).' seconds'."\n");
	}

	private function logHouseKeeping() {
		$purgeLogsAfter = $this->db->settings->get('purge-logs-after');
		$result = $this->db->deleteLogEntryOlderThan($purgeLogsAfter);
		if($this->debug) echo('Purged '.intval($result).' log entries older than '.intval($purgeLogsAfter).' seconds'."\n");
	}
}
