<?php
require_once('loader.php');

// this script is intended to be called periodically every 10 minutes via cron

// job housekeeping
foreach($db->getAllJobContainer() as $container) {
	// purge old jobs
	$icon = $db->getJobContainerIcon($container->id);
	if($icon == JobContainer::STATUS_SUCCEEDED) {
		if(time() - strtotime($container->last_update) > $db->getSettingByName('purge-succeeded-jobs')) {
			echo('Remove Succeeded Job Container #'.$container->id.' ('.$container->name.')'."\n");
			$db->removeJobContainer($container->id);
		}
	}
	elseif($icon == JobContainer::STATUS_FAILED) {
		if(time() - strtotime($container->last_update) > $db->getSettingByName('purge-failed-jobs')) {
			echo('Remove Failed Job Container #'.$container->id.' ('.$container->name.')'."\n");
			$db->removeJobContainer($container->id);
		}
	}

	// set job state to expired if job container end date reached
	if($container->end_time !== null && strtotime($container->end_time) < time()) {
		foreach($db->getAllJobByContainer($container->id) as $job) {
			if($job->state == Job::STATUS_WAITING_FOR_CLIENT || $job->state == Job::STATUS_DOWNLOAD_STARTED || $job->state == Job::STATUS_EXECUTION_STARTED) {
				echo('Set Job #'.$job->id.' (Container #'.$container->id.', '.$container->name.') state to EXPIRED'."\n");
				$db->updateJobState($job->id, Job::STATUS_EXPIRED, NULL, '');
			}
		}
	}
}

// wake computers for jobs
foreach($db->getAllJobContainer() as $container) {
	if($container->wol_sent == 0) {
		if(strtotime($container->start_time) <= time()) {
			echo('Execute WOL for Job Container #'.$container->id.' ('.$container->name.')'."\n");
			foreach($db->getComputerMacByContainer($container->id) as $c) {
				echo('   Send WOL Magic Packet to '.$c->computer_network_mac."\n");
				wol($c->computer_network_mac);
			}
			$db->updateJobContainer(
				$container->id,
				$container->name,
				$container->start_time,
				$container->end_time,
				$container->notes,
				1 /* WOL sent */
			);
		}
	}
}
