<?php
require_once('loader.php');

// this script is intended to be called periodically every 10 minutes via cron

// purge old jobs
foreach($db->getAllJobContainer() as $container) {
	if($db->getJobContainerIcon($container->id) == 'tick') {
		if(time() - strtotime($container->last_update) > $db->getSettingByName('purge-succeeded-jobs')) {
			echo('Remove Job Container #'.$container->id.' ('.$container->name.')'."\n");
			$db->removeJobContainer($container->id);
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
