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

<h2>Anmeldungen</h2>
<table class='list'>
	<tr><th>Computer</th><th>Anzahl</th><th>Letzte Anmeldung</th></tr>
	<?php
	foreach($db->getDomainuserLogonByDomainuser($domainuser->id) as $logon) {
		echo "<tr>";
		echo "<td><a href='#' onclick='refreshContentComputerDetail(".$logon->computer_id.")'>".htmlspecialchars($logon->hostname)."</a></td>";
		echo "<td>".htmlspecialchars($logon->amount)."</td>";
		echo "<td>".htmlspecialchars($logon->timestamp)."</td>";
		echo "</tr>";
	}
	?>
</table>
