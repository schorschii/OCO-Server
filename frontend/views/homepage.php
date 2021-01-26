<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../lib/loader.php');
require_once(__DIR__.'/../session.php');

// ----- prepare view -----
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
		<div class='title'><?php echo LANG['app_name_frontpage']; ?></div>
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
				echo LANG['usage'].' '.progressBar($percent);
				?>
			</td>
			<td class='center'>
				<?php echo LANG['version'].' '.APP_VERSION; ?>
			</td>
			<td class='center' colspan='2' title='<?php echo htmlspecialchars(niceSize($used).' / '.niceSize($total)); ?>'>
				<?php
				$percent = round($used/$total*100);
				echo LANG['disk_space'].' '.progressBar($percent);
				?>
			</td>
		</tr>
		<tr>
			<td class='center'><img src='img/users.dyn.svg'><br><?php echo count($db->getAllDomainuser()).' '.LANG['users']; ?></td>
			<td class='center'><img src='img/computer.dyn.svg'><br><?php echo count($db->getAllComputer()).' '.LANG['computer']; ?></td>
			<td class='center'><img src='img/package.dyn.svg'><br><?php echo count($db->getAllPackage()).' '.LANG['packages']; ?></td>
			<td class='center'><img src='img/job.dyn.svg'><br><?php echo count($db->getAllJobcontainer()).' '.LANG['job_container']; ?></td>
			<td class='center'><img src='img/report.dyn.svg'><br><?php echo count($db->getAllReport()).' '.LANG['reports']; ?></td>
		</tr>
		<?php
		// the message of the day is intentionally not escaped by htmlspecialchars() so you can format the text and insert links
		$motd = $db->getSettingByName('motd');
		if(!empty($motd)) {
		?>
		<tr>
			<td class='center' colspan='5'><?php echo $motd=='default_motd' ? LANG['default_motd'] : $motd; ?></td>
		</tr>
		<?php } ?>
	</table>

	<div class='footer'>
		© <a href='https://georg-sieber.de' target='_blank'>Georg Sieber</a> 2020-2021 | <a href='https://github.com/schorschii/oco-server' target='_blank'>OCO-Server auf Github</a>
	</div>
</div>
