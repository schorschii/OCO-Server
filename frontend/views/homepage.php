<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.php');

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
		<div class='title'>[ <?php echo LANG('project_name'); ?> ]</div>
		<div class='subtitle'><?php echo LANG('app_subtitle'); ?></div>
	</p>

	<?php if(!$license->isValid()) { ?>
		<div class='alert bold error'><?php echo LANG('your_license_is_invalid'); ?></div>
	<?php } ?>

	<table class='list fullwidth margintop fixed largepadding'>
		<tr>
			<th class='center' colspan='5'><?php echo LANG('server_overview'); ?></th>
		</tr>
		<tr>
			<td class='center' colspan='2' title='<?php echo htmlspecialchars($sysload.' / '.$ncpu); ?>'>
				<div><?php echo LANG('usage'); ?></div>
				<?php
				$percent = round($sysload/$ncpu*100);
				echo progressBar($percent, null, null, '', 'width:280px');
				?>
			</td>
			<td class='center'>
				<?php echo LANG('version').' '.OcoServer::APP_VERSION.' '.OcoServer::APP_RELEASE; ?>
			</td>
			<td class='center' colspan='2' title='<?php echo htmlspecialchars(niceSize($used).' / '.niceSize($total)); ?>'>
				<div><?php echo LANG('disk_space'); ?></div>
				<?php
				if($total !== false && $free !== false) {
					$percent = round($used/$total*100);
					echo progressBar($percent, null, null, '', 'width:280px');
				} else {
					echo '???';
				}
				?>
			</td>
		</tr>
		<tr>
			<td class='center'><img src='img/users.dyn.svg'><br><?php echo $stats['domain_users'].' '.LANG('domain_users'); ?></td>
			<td class='center'><img src='img/computer.dyn.svg'><br><?php echo $stats['computers'].' '.LANG('computer'); ?></td>
			<td class='center'><img src='img/package.dyn.svg'><br><?php echo $stats['packages'].' '.LANG('packages'); ?></td>
			<td class='center'><img src='img/job.dyn.svg'><br><?php echo $stats['jobs'].' '.LANG('jobs'); ?></td>
			<td class='center'><img src='img/report.dyn.svg'><br><?php echo $stats['reports'].' '.LANG('reports'); ?></td>
		</tr>

		<tr>
			<td class='center' colspan='5'><?php echo LANG($db->settings->get('motd')); ?></td>
		</tr>
	</table>

	<div class='footer'>
		<?php echo LANG('app_copyright'); ?>
	</div>
</div>
