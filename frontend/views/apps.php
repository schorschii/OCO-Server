<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

if(!$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_SOFTWARE_VIEW, false))
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
?>

<?php
if(!empty($_GET['id'])) {
	$app = $db->selectApp($_GET['id']);
	if($app === null) die("<div class='alert warning'>".LANG('not_found')."</div>");
?>


<div class='details-header'>
	<h1><img src='img/apps.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($app->name) . ' ' . htmlspecialchars($app->display_version.' ('.$app->version.')'); ?></span></h1>
</div>
<?php if(!empty($app->identifier)) { ?>
	<p class='quote'><?php echo nl2br(htmlspecialchars($app->identifier)); ?></p>
<?php } ?>
<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('installed_on'); ?></h2>
		<table id='tblAppMobileDeviceData1' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('device_name'); ?></th>
					<th class='searchable sortable'><?php echo LANG('os'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($db->selectAllMobileDeviceByAppId($_GET['id']) as $md) {
				echo "<tr>";
				echo "<td><a ".explorerLink('views/mobile-device-details.php?id='.$md->id).">".htmlspecialchars($md->getDisplayName())."</a></td>";
				echo "<td>".htmlspecialchars($md->os)."</td>";
				echo "</tr>";
			}
			?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php
} elseif(!empty($_GET['name'])) {
?>


<div class='details-header'>
	<h1><img src='img/apps.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($_GET['name']); ?></span></h1>
</div>
<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('installed_on'); ?></h2>
		<table id='tblAppMobileDeviceData2' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('device_name'); ?></th>
					<th class='searchable sortable'><?php echo LANG('version'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($db->selectAllMobileDeviceByAppName($_GET['name']) as $md) {
				echo "<tr>";
				echo "<td><a ".explorerLink('views/mobile-device-details.php?id='.$md->id).">".htmlspecialchars($md->getDisplayName())."</a></td>";
				echo "<td><a ".explorerLink('views/apps.php?id='.$md->app_id).">".htmlspecialchars($md->app_version)."</a></td>";
				echo "</tr>";
			}
			?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php } else { ?>


<div class='details-header'>
	<h1><img src='img/apps.dyn.svg'><span id='page-title'><?php echo LANG('recognised_apps'); ?></span></h1>
</div>
<div class='details-abreast'>
	<div class='stickytable'>
		<?php $apps = [];
		if(isset($_GET['os']) && $_GET['os'] == 'ios') {
			echo "<h2>".LANG('ios')."</h2>";
			$apps = $db->selectAllAppByMobileDeviceOs(Models\MobileDevice::OS_TYPE_IOS);
		} elseif(isset($_GET['os']) && $_GET['os'] == 'android') {
			echo "<h2>".LANG('android')."</h2>";
			$apps = $db->selectAllAppByMobileDeviceOs(Models\MobileDevice::OS_TYPE_ANDROID);
		} else {
			echo "<h2>".LANG('all_os')."</h2>";
			$apps = $db->selectAllAppByMobileDeviceOs(-1);
		}
		?>
		<table id='tblAppData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th class='searchable sortable'><?php echo LANG('name'); ?></th>
				<th class='searchable sortable'><?php echo LANG('installations'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($apps as $a) {
			echo "<tr>";
			echo "<td><a ".explorerLink('views/apps.php?name='.urlencode($a->name)).">".htmlspecialchars($a->name)."</a></td>";
			echo "<td>".$a->installations."</td>";
			echo "</tr>";
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
						</div>
						<div class='controls'>
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>


<?php } ?>
