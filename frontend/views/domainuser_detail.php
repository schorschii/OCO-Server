<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

$domainuser = null;
if(!empty($_GET['id']))
	$domainuser = $db->getDomainuser($_GET['id']);

if($domainuser === null) die("<div class='alert warning'>".LANG['not_found']."</div>");
?>

<h1><img src='img/user.dyn.svg'><?php echo htmlspecialchars($domainuser->username); ?></h1>

<h2><?php echo LANG['logins']; ?></h2>
<table id='tblDomainuserDetailData' class='list searchable sortable savesort'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG['computer']; ?></th>
			<th class='searchable sortable'><?php echo LANG['count']; ?></th>
			<th class='searchable sortable'><?php echo LANG['last_login']; ?></th>
		</tr>
	</thead>


	<?php
	$counter = 0;
	foreach($db->getDomainuserLogonByDomainuser($domainuser->id) as $logon) {
		$counter ++;
		echo "<tr>";
		echo "<td><a href='".explorerLink('views/computer_detail.php?id='.$logon->computer_id)."' onclick='event.preventDefault();refreshContentComputerDetail(".$logon->computer_id.")'>".htmlspecialchars($logon->computer_hostname)."</a></td>";
		echo "<td>".htmlspecialchars($logon->logon_amount)."</td>";
		echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
		echo "</tr>";
	}
	?>

	<tfoot>
		<tr>
			<td colspan='999'>
				<span class='counter'><?php echo $counter; ?></span> <?php echo LANG['elements']; ?>,
				<a href='#' onclick='event.preventDefault();downloadTableCsv("tblDomainuserDetailData")'><?php echo LANG['csv']; ?></a>
			</td>
		</tr>
	</tfoot>
</table>
