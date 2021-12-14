<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<?php
if(!empty($_GET['id'])) {
	$domainUser = $db->getDomainUser($_GET['id']);
	if($domainUser === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
?>


<div class='details-header'>
	<h1><img src='img/user.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($domainUser->username); ?></span></h1>
</div>
<div class='details-abreast'>
	<div>
		<h2><?php echo LANG['aggregated_logins']; ?></h2>
		<table id='tblDomainUserDetailData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
					<th class='searchable sortable'><?php echo LANG['count']; ?></th>
					<th class='searchable sortable'><?php echo LANG['last_login']; ?></th>
				</tr>
			</thead>
			<?php
			$counter = 0;
			foreach($db->getDomainUserLogonByDomainUser($domainUser->id) as $logon) {
				$counter ++;
				echo "<tr>";
				echo "<td><a ".explorerLink('views/computer-details.php?id='.$logon->computer_id).">".htmlspecialchars($logon->computer_hostname)."</a></td>";
				echo "<td>".htmlspecialchars($logon->logon_amount)."</td>";
				echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
				echo "</tr>";
			}
			?>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
						<a href='#' onclick='event.preventDefault();downloadTableCsv("tblDomainUserDetailData")'><?php echo LANG['csv']; ?></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	<div>
		<h2><?php echo LANG['history']; ?></h2>
		<table id='tblDomainUserHistoryData' class='list searchable sortable savesort'>
			<thead>
				<tr>
					<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
					<th class='searchable sortable'><?php echo LANG['console']; ?></th>
					<th class='searchable sortable'><?php echo LANG['timestamp']; ?></th>
				</tr>
			</thead>
			<?php
			$counter = 0;
			foreach($db->getDomainUserLogonHistoryByDomainUser($domainUser->id) as $logon) {
				$counter ++;
				echo "<tr>";
				echo "<td><a ".explorerLink('views/computer-details.php?id='.$logon->computer_id).">".htmlspecialchars($logon->computer_hostname)."</a></td>";
				echo "<td>".htmlspecialchars($logon->console)."</td>";
				echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
				echo "</tr>";
			}
			?>
			<tfoot>
				<tr>
					<td colspan='999'>
						<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
						<a href='#' onclick='event.preventDefault();downloadTableCsv("tblDomainUserHistoryData")'><?php echo LANG['csv']; ?></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>


<?php
} else { $domainUser = $db->getAllDomainUser();
?>


<div class='details-header'>
	<h1><img src='img/users.dyn.svg'><span id='page-title'><?php echo LANG['all_domain_user']; ?></span></h1>
</div>
<div class='details-abreast margintop'>
	<div>
		<table id='tblDomainUserData' class='list searchable sortable savesort'>
		<thead>
			<tr>
				<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblDomainUserData, this.checked)'></th>
				<th class='searchable sortable'><?php echo LANG['login_name']; ?></th>
				<th class='searchable sortable'><?php echo LANG['logons']; ?></th>
				<th class='searchable sortable'><?php echo LANG['computers']; ?></th>
				<th class='searchable sortable'><?php echo LANG['last_login']; ?></th>
			</tr>
		</thead>
		<?php
		$counter = 0;
		foreach($domainUser as $u) {
			$counter ++;
			echo "<tr>";
			echo "<td><input type='checkbox' name='domain_user_id[]' value='".$u->id."' onchange='refreshCheckedCounter(tblDomainUserData)'></td>";
			echo "<td><a ".explorerLink('views/domain-users.php?id='.$u->id).">".htmlspecialchars($u->username)."</a></td>";
			echo "<td>".htmlspecialchars($u->logon_amount)."</td>";
			echo "<td>".htmlspecialchars($u->computer_amount)."</td>";
			echo "<td>".htmlspecialchars($u->timestamp)."</td>";
			echo "</tr>";
		}
		?>
		<tfoot>
			<tr>
				<td colspan='999'>
					<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
					<span class='counter-checked'>0</span>&nbsp;<?php echo LANG['elements_checked']; ?>,
					<a href='#' onclick='event.preventDefault();downloadTableCsv("tblDomainUserData")'><?php echo LANG['csv']; ?></a>
				</td>
			</tr>
		</tfoot>
		</table>
		<div class='controls'>
			<span><?php echo LANG['selected_elements']; ?>:&nbsp;</span>
			<button onclick='confirmRemoveSelectedDomainUser("domain_user_id[]")'><img src='img/delete.svg'>&nbsp;<?php echo LANG['delete']; ?></button>
		</div>
	</div>
</div>


<?php } ?>
