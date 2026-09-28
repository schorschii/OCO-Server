<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// ----- prepare view -----
$stats = $db->getStats();
$sysload = sys_getloadavg()[2];
$ncpu = 1;
if(is_file('/proc/cpuinfo')) {
	$cpuinfo = file_get_contents('/proc/cpuinfo');
	preg_match_all('/^processor/m', $cpuinfo, $matches);
	$ncpu = count($matches[0]);
}

$total = disk_total_space(PACKAGE_PATH);
$free = disk_free_space(PACKAGE_PATH);
$used = $total - $free;

$license = new LicenseCheck($db);
?>

<div id='homepage'>
	<img src='img/logo.dyn.svg'>
	<p>
		<div class='title'><?php echo LANG('project_name'); ?></div>
		<div class='subtitle'><?php echo LANG('project_subtitle'); ?></div>
	</p>

	<?php if(!$license->isValid()) { ?>
		<div class='alert bold error'><?php echo LANG('your_license_is_invalid'); ?></div>
	<?php } elseif(!$license->isFree() && $license->getRemainingTime() < 60*60*24*14) {
		$remainingDays = round($license->getRemainingTime() / (60*60*24));
	?>
		<div class='alert bold warning'><?php echo str_replace('%1', $remainingDays, LANG('your_license_expires_in_days')); ?></div>
	<?php } ?>

	<div class='box fullwidth margintop stats'>
		<div>
			<div class='bold'><?php echo LANG('server_overview'); ?></div>
		</div>
		<hr/>
		<div class='bars'>
			<div class='' title='<?php echo htmlspecialchars($sysload.' / '.$ncpu); ?>'>
				<div><?php echo LANG('usage'); ?></div>
				<?php
				$percent = round($sysload/$ncpu*100);
				echo Html::progressBar($percent, null, null, '', 'width:280px');
				?>
			</div>
			<div class=' version'>
				<?php echo LANG('version').' '.OcoServer::APP_VERSION.' '.OcoServer::APP_RELEASE; ?>
			</div>
			<div class='' title='<?php echo htmlspecialchars(niceSize($used).' / '.niceSize($total)); ?>'>
				<div><?php echo LANG('disk_space'); ?></div>
				<?php
				if($total !== false && $free !== false) {
					$percent = round($used/$total*100);
					echo Html::progressBar($percent, null, null, '', 'width:280px');
				} else {
					echo '???';
				}
				?>
			</div>
		</div>
		<hr/>
		<div>
			<div><img src='img/users.dyn.svg'><br><?php echo $stats['domain_users'].' '.LANG('domain_users'); ?></div>
			<div><img src='img/computer.dyn.svg'><br><?php echo $stats['computers'].' '.LANG('computer'); ?></div>
			<div><img src='img/mobile-device.dyn.svg'><br><?php echo $stats['mobile_devices'].' '.LANG('mobile_devices'); ?></div>
			<div><img src='img/package.dyn.svg'><br><?php echo $stats['packages'].' '.LANG('software_packages'); ?></div>
			<div><img src='img/job.dyn.svg'><br><?php echo $stats['jobs'].' '.LANG('software_jobs'); ?></div>
			<div><img src='img/report.dyn.svg'><br><?php echo $stats['reports'].' '.LANG('reports'); ?></div>
		</div>
		<hr/>
		<div>
			<div class='motd'><?php echo LANG($db->settings->get('motd')); ?></div>
		</div>
	</div>

	<div class='footer'>
		<?php require('partial/copyright.php'); ?>
	</div>
</div>
