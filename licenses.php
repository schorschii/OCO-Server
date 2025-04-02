<?php
require_once('loader.inc.php');

$info = null;
$infoclass = null;

try {
	if(!empty($_POST['action']) && $_POST['action'] == 'order'
	&& !empty($_POST['company']) && !empty($_POST['objects'])) {
		$objects = intval($_POST['objects']);
		if($objects < 1) throw new Exception('Invalid amount!');

		$purchase = new OcoPurchase();
		$link = $purchase->startFlow(
			'OCO Licenses',
			'OCO License (Price per Computer per Year)',
			$objects
		);
		header('Location: '.$link); die();
	}
	if(isset($_GET['paypalresult'])) {
		$purchase = new OcoPurchase();
		$values = $purchase->completeFlow(
			$_GET['paypalresult'], $_GET['paymentId']??'', $_GET['PayerID']??'',
			'Your OCO License File',
			'Thank you for purchasing OCO licenses. You will find your license file in the attachment.'."\n"
			. 'Please import this file into your OCO installation (Settings > License menu).'."\n"
		);
		$info = 'Thank you for your order! You will receive the license file automatically by email in just a few minutes to '.$values['email'].'. '
		. 'If you have any questions, please use the <a href="https://georg-sieber.de/?page=contact">contact form</a>.';
		$infoclass = 'ok';
	}
} catch(PaymentCanceledException $ex) {
	$info = 'You cancelled the PayPal payment.';
	$infoclass = 'warn';
} catch(PaymentFailedException $ex) {
	$info = 'PayPal payment could not be completed.';
	$infoclass = 'error';
} catch(Exception $ex) {
	$info = 'Error: '.$ex->getMessage();
	$infoclass = 'error';
}
?>

<!DOCTYPE html>
<html>
<head>

	<?php require('head.inc.php'); ?>

</head>
<body>

	<?php require('top.inc.php'); ?>

	<div id="maincontent">
		<div id="body">

			<div id="downloads">
				<a href="/">Homepage</a>
			</div>

<?php if(!empty($info)) { ?>
	<div class='infobox <?php echo $infoclass; ?>'><?php echo $info; ?></div>
<?php } ?>

<h2>Purchase OCO Licenses</h2>
<p>
	OCO can be used free of charge for <?php echo OcoPurchase::FREE_OBJECTS; ?> managed devices. For additional devices, enterprise licenses are required, which you can purchase here directly via PayPal. Payment by SEPA bank transfer or individual conditions are possible upon request. In this case, please use the <a href='https://georg-sieber.de/?page=contact'>contact form</a>.
</p>
<p>
	After completing the purchase, you will receive a license file by email, which you can import into your OCO installation in the menu "Settings" > "Configuration overview" > "License".
</p>
<form method='POST'>
	<input type='hidden' name='action' value='order'>
	<table>
		<tr>
			<th>Company name:</th>
			<td><input type='text' name='company' placeholder='' required='true'></td>
		</tr>
		<tr>
			<th>Number of devices:</th>
			<td>
				<input type='number' id='objects' name='objects' placeholder='' min='1' max='2500' required='true' oninput='total.innerText=(<?php echo OcoPurchase::PRICE; ?>*objects.value).toFixed(2)+" €"'> + <?php echo OcoPurchase::FREE_OBJECTS; ?> free devices
			</td>
		</tr>
		<tr>
			<th>Price:</th>
			<td><?php echo number_format(OcoPurchase::PRICE, 2, ',', '.'); ?> € per device for one year</td>
		</tr>
		<tr>
			<th>Total:</th>
			<td id='total'>0,00 €</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<button><img src='img/paypal/de-pp-logo-100px.png'>&nbsp;&nbsp;Buy licenses now</button>
			</td>
		</tr>
	</table>
</form>

		</div>
	</div>

	<?php require('foot.inc.php'); ?>

</body>
</html>
