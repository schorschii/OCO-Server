<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

$domainuser = null;
if(!empty($_GET['id']))
	$domainuser = $db->getDomainuser($_GET['id']);

if($domainuser === null) die();
?>

<h1><?php echo htmlspecialchars($domainuser->username); ?></h1>

<h2><?php echo LANG['logins']; ?></h2>
<table class='list'>
	<tr><th><?php echo LANG['computer']; ?></th><th><?php echo LANG['count']; ?></th><th><?php echo LANG['last_login']; ?></th></tr>
	<?php
	foreach($db->getDomainuserLogonByDomainuser($domainuser->id) as $logon) {
		echo "<tr>";
		echo "<td><a href='#' onclick='event.preventDefault();refreshContentComputerDetail(".$logon->computer_id.")'>".htmlspecialchars($logon->hostname)."</a></td>";
		echo "<td>".htmlspecialchars($logon->amount)."</td>";
		echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
		echo "</tr>";
	}
	?>
</table>
