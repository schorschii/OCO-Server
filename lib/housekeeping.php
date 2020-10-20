<?php
require_once('loader.php');

// this script is intended to be called periodically via cron

// purge old jobs
foreach($db->getAllJobContainer() as $container) {
	if($db->getJobContainerIcon($container->id) == 'tick') {
		if(time() - strtotime($container->last_update) > $db->getSettingByName('purge-successful-jobs')) {
			echo('Remove Jobcontainer #'.$container->id.' ('.$container->name.')'."\n");
			$db->removeJobContainer($container->id);
		}
	}
}
