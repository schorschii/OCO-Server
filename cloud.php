<?php
require_once('loader.inc.php');

$info = null; $infoclass = null;
if(!empty($_POST['company'])
&& !empty($_POST['name']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)
&& !empty($_POST['email'])
&& !empty($_POST['objects'])) {
	if(mail('it@georg-sieber.de',
		'OCO Cloud Hosting Trial Request',

		'Company: '.$_POST['company']."\n".
		'Name: '.$_POST['name']."\n".
		'Devices: '.$_POST['objects']."\n".
		'Notes: '.$_POST['notes'."\n"],

		['Reply-To'=>$_POST['email']]
	)) {
		$info = 'Message sent. Thank you for your request.';
		$infoclass = 'ok';
	} else {
		$info = 'Message could not be sent.';
		$infoclass = 'error';
	}
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

			<div class="actionmenu">
				<a href="/">Homepage</a>
			</div>

<?php if(!empty($info)) { ?>
	<div class='infobox <?php echo $infoclass; ?>'><?php echo $info; ?></div>
<?php } ?>

<h2>Cloud Hosting</h2>
<p>
	If you don't have the neccessary infrastructure or capacity to maintain an OCO server yourself you can get a cloud-hosted instance directly from the vendor. Get in touch now to get a free trial.
</p>
<p>
	By using a cloud-hosted instance, you directly support the further development of the project.
</p>
<form method='POST'>
	<table class='fullwidth'>
		<tr>
			<th>Company name:</th>
			<td><input type='text' name='company' placeholder='' required='true'></td>
		</tr>
		<tr>
			<th>Your name:</th>
			<td><input type='text' name='name' placeholder='' required='true'></td>
		</tr>
		<tr>
			<th>Your email:</th>
			<td><input type='email' name='email' placeholder='' required='true'></td>
		</tr>
		<tr>
			<th>Number of devices:</th>
			<td>
				<input type='number' name='objects' placeholder='' min='1' max='2500' required='true'>
			</td>
		</tr>
		<tr>
			<th>Notes:</th>
			<td>
				<textarea name='notes' placeholder='(optional)'></textarea>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<button><b>→&nbsp;Send Request</b></button>
			</td>
		</tr>
	</table>
</form>

		</div>
	</div>

	<?php require('foot.inc.php'); ?>

</body>
</html>
