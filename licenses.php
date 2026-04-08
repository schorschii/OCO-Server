<?php
require_once('loader.inc.php');
?>

<!DOCTYPE html>
<html>
<head>

	<?php require('head.inc.php'); ?>
	<style>
		form button {
			font-weight: bold;
		}
	</style>

</head>
<body>

	<?php require('top.inc.php'); ?>

	<div id="maincontent">
		<div id="body">

			<div class="actionmenu">
				<a href="/">Homepage</a>
			</div>

			<h2>Purchase OCO Licenses</h2>
			<p>
				OCO can be used free of charge for <?php echo OcoPurchase::FREE_OBJECTS; ?> managed devices. For additional devices, enterprise licenses are required, which you can purchase here directly. Individual conditions are possible upon request. In this case, please <a href='https://georg-sieber.de/impressum'>contact sales</a>.
			</p>
			<p>
				After completing the purchase, you will receive a license file by email, which you can import into your OCO installation.
			</p>
			<form method='GET' action='https://store.payproglobal.com/checkout' style='margin-bottom:60px'>
				<input type='hidden' name='products[1][id]' value='128532'>
				<table>
					<tr>
						<th>Number of devices:</th>
						<td>
							<input type='number' name='products[1][qty]' value='10' min='10' max='2500' required='true'> + <?php echo OcoPurchase::FREE_OBJECTS; ?> free devices
						</td>
					</tr>
					<tr>
						<th>Price:</th>
						<td><?php echo number_format(OcoPurchase::PRICE, 2, ',', '.'); ?> € per device for one year</td>
					</tr>
					<tr>
						<th>Checkout:</th>
						<td>
							<button>→&nbsp;Buy license now&nbsp;<img src='img/payment/pp-cc.png' style='height:45px; margin-left:5px;'></button>
						</td>
					</tr>
				</table>
			</form>

		</div>
	</div>

	<?php require('foot.inc.php'); ?>

</body>
</html>
