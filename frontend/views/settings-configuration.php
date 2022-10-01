<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('configuration_overview'); ?></span></h1>
</div>

			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('oco_configuration'); ?></h2>
					<table class='list metadata'>
						<tr>
							<th><?php echo LANG('client_api_enabled'); ?>:</th>
							<td><?php if(CLIENT_API_ENABLED) echo LANG('yes'); else echo LANG('no'); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('agent_registration_enabled'); ?>:</th>
							<td><?php if(AGENT_SELF_REGISTRATION_ENABLED) echo LANG('yes'); else echo LANG('no'); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('assume_computer_offline_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(COMPUTER_OFFLINE_SECONDS)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('wol_shutdown_expiry_seconds'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(WOL_SHUTDOWN_EXPIRY_SECONDS)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('agent_update_interval'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(AGENT_UPDATE_INTERVAL)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_succeeded_jobs_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_SUCCEEDED_JOBS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_failed_jobs_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_FAILED_JOBS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_logs_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_LOGS_AFTER)); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('purge_domain_user_logons_after'); ?>:</th>
							<td><?php echo htmlspecialchars(niceTime(PURGE_DOMAIN_USER_LOGONS_AFTER)); ?></td>
						</tr>
					</table>
					<p><?php echo LANG('change_settings_in_config_file'); ?></p>
				</div>
				<div>
					<h2><?php echo LANG('server_environment'); ?></h2>
					<table class='list'>
						<tr>
							<th><?php echo LANG('webserver_version'); ?>:</th>
							<td><?php echo htmlspecialchars(apache_get_version()); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('database_server_version'); ?>:</th>
							<td><?php echo htmlspecialchars($db->getServerVersion()); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('php_version'); ?>:</th>
							<td><?php echo htmlspecialchars(phpversion()); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('post_max_size'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('post_max_size')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('upload_max_filesize'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('upload_max_filesize')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('max_input_time'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('max_input_time')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('max_execution_time'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('max_execution_time')); ?></td>
						</tr>
						<tr>
							<th><?php echo LANG('memory_limit'); ?>:</th>
							<td><?php echo htmlspecialchars(ini_get('memory_limit')); ?></td>
						</tr>
					</table>
				</div>
			</div>
			<div class='details-abreast'>
				<div>
					<h2><?php echo LANG('wol_satellites'); ?></h2>
					<?php if(count(SATELLITE_WOL_SERVER) == 0) { ?>
						<div class='alert info'>Keine WOL-Satelliten-Server definiert</div>
					<?php } else { ?>
					<table class='list'>
						<tr>
							<th><?php echo LANG('address'); ?></th>
							<th><?php echo LANG('port'); ?></th>
						</tr>
						<?php foreach(SATELLITE_WOL_SERVER as $s) { ?>
						<tr>
							<td><?php echo htmlspecialchars($s['ADDRESS']); ?></td>
							<td><?php echo htmlspecialchars($s['PORT']); ?></td>
						</tr>
						<?php } ?>
					</table>
					<?php } ?>
				</div>
				<div>
				<h2><?php echo LANG('extensions'); ?></h2>
					<?php if(count($ext->getLoadedExtensions()) == 0) { ?>
						<div class='alert info'>Keine Erweiterungen geladen</div>
					<?php } else { ?>
					<table class='list'>
						<tr>
							<th><?php echo LANG('id'); ?></th>
							<th><?php echo LANG('name'); ?></th>
							<th><?php echo LANG('version'); ?></th>
							<th><?php echo LANG('author'); ?></th>
						</tr>
						<?php foreach($ext->getLoadedExtensions() as $e) { ?>
						<tr>
							<td><?php echo htmlspecialchars($e['id']); ?></td>
							<td><?php echo htmlspecialchars($e['name']); ?></td>
							<td><?php echo htmlspecialchars($e['version']); ?></td>
							<td><?php echo htmlspecialchars($e['author']); ?></td>
						</tr>
						<?php } ?>
					</table>
					<?php } ?>
				</div>
			</div>

