<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$domainUser = $cl->getDomainUser($_GET['id'] ?? -1);
	$groups = $db->selectAllDomainUserGroupByDomainUserId($domainUser->id);

	$historyLimit = null;
	$computerHistoryLimit = null;
	$permissionEntry = $cl->getPermissionEntry(PermissionManager::SPECIAL_PERMISSION_DOMAIN_USER, PermissionManager::METHOD_READ);
	if(isset($permissionEntry['history_limit'])) $historyLimit = intval($permissionEntry['history_limit']);
	if(isset($permissionEntry['computer_history_limit'])) $computerHistoryLimit = intval($permissionEntry['computer_history_limit']);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/user.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($domainUser->displayNameWithUsername()); ?></span></h1>
</div>

<?php if(!empty($groups)) { ?>
<div class='controls subfolders'>
	<?php foreach($groups as $g) { ?>
		<a class='box' <?php echo Html::explorerLink('views/domain-users.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>

<div class='details-abreast'>
	<div class='stickytable'>
		<h2><?php echo LANG('aggregated_logins'); ?></h2>
		<table id='tblDomainUserDetailData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('computer'); ?></th>
					<th class='searchable sortable'><?php echo LANG('count'); ?></th>
					<th class='searchable sortable'><?php echo LANG('last_login'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$counter = 0;
			foreach($db->selectAllAggregatedDomainUserLogonByDomainUserId($domainUser->id) as $logon) {
				if(is_int($computerHistoryLimit) && $counter >= $computerHistoryLimit) {
					echo "<tr><td colspan='999'><div class='alert warning'>".LANG('restricted_view')."</div></td></tr>";
					break;
				}
				$counter ++;
				echo "<tr>";
				echo "<td><a ".Html::explorerLink('views/computer-details.php?id='.$logon->computer_id).">".htmlspecialchars($logon->computer_hostname)."</a></td>";
				echo "<td>".htmlspecialchars($logon->logon_amount)."</td>";
				echo "<td>".htmlspecialchars($cl->formatLoginDate($logon->timestamp))."</td>";
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
	<div class='stickytable'>
		<h2><?php echo LANG('history'); ?></h2>
		<table id='tblDomainUserHistoryData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG('computer'); ?></th>
					<th class='searchable sortable'><?php echo LANG('console'); ?></th>
					<th class='searchable sortable'><?php echo LANG('timestamp'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$counter = 0;
			foreach($db->selectAllDomainUserLogonByDomainUserId($domainUser->id) as $logon) {
				if(is_int($historyLimit) && $counter >= $historyLimit) {
					echo "<tr><td colspan='999'><div class='alert warning'>".LANG('restricted_view')."</div></td></tr>";
					break;
				}
				$counter ++;
				echo "<tr>";
				echo "<td><a ".Html::explorerLink('views/computer-details.php?id='.$logon->computer_id).">".htmlspecialchars($logon->computer_hostname)."</a></td>";
				echo "<td>".htmlspecialchars($logon->console)."</td>";
				echo "<td>".htmlspecialchars($cl->formatLoginDate($logon->timestamp))."</td>";
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
