<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../lib/Loader.php');
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
?>

<div id='homepage'>
	<img src='img/logo.dyn.svg'>
	<p>
		<div class='title'>[ <?php echo LANG['project_name']; ?> ]</div>
		<div class='subtitle'><?php echo LANG['app_subtitle']; ?></div>
	</p>

	<table class='list fullwidth margintop fixed largepadding'>
		<tr>
			<th class='center' colspan='5'><?php echo LANG['server_overview']; ?></th>
		</tr>
		<tr>
			<td class='center' colspan='2' title='<?php echo htmlspecialchars($sysload.' / '.$ncpu); ?>'>
				<?php
				$percent = round($sysload/$ncpu*100);
				echo progressBar($percent, null, null, null, 'width:350px', false, false, LANG['usage']).'';
				?>
			</td>
			<td class='center'>
				<?php echo LANG['version'].' '.APP_VERSION.' '.APP_RELEASE; ?>
			</td>
			<td class='center' colspan='2' title='<?php echo htmlspecialchars(niceSize($used).' / '.niceSize($total)); ?>'>
				<?php
				$percent = round($used/$total*100);
				echo progressBar($percent, null, null, null, 'width:350px', false, false, LANG['disk_space']).'';
				?>
			</td>
		</tr>
		<tr>
			<td class='center'><img src='img/users.dyn.svg'><br><?php echo $stats->domain_users.' '.LANG['users']; ?></td>
			<td class='center'><img src='img/computer.dyn.svg'><br><?php echo $stats->computers.' '.LANG['computer']; ?></td>
			<td class='center'><img src='img/package.dyn.svg'><br><?php echo $stats->packages.' '.LANG['packages']; ?></td>
			<td class='center'><img src='img/job.dyn.svg'><br><?php echo $stats->jobs.' '.LANG['jobs']; ?></td>
			<td class='center'><img src='img/report.dyn.svg'><br><?php echo $stats->reports.' '.LANG['reports']; ?></td>
		</tr>
		<?php
		// the message of the day is intentionally not escaped by htmlspecialchars() so you can format the text and insert links
		if(!empty(MOTD)) {
		?>
		<tr>
			<td class='center' colspan='5'><?php echo MOTD=='default_motd' ? LANG['default_motd'] : MOTD; ?></td>
		</tr>
		<?php } ?>
	</table>

	<div class='footer'>
		<?php echo LANG['app_copyright']; ?>
	</div>
</div>
