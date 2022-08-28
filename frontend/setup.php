<?php
require_once('../loader.inc.php');

$info = null;
$infoclass = null;

// exit if setup was already done
if($db->existsSchema() && count($db->getAllSystemUser()) > 0) {
	header('Location: index.php');
	die();
}

// create initial admin user
if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['password2'])) {
	if(empty(trim($_POST['username']))) {
		$info = LANG('username_cannot_be_empty');
		$infoclass = 'error';
	} elseif(empty(trim($_POST['password']))) {
		$info = LANG('password_cannot_be_empty');
		$infoclass = 'error';
	} elseif($_POST['password'] !== $_POST['password2']) {
		$info = LANG('passwords_do_not_match');
		$infoclass = 'error';
	} else {
		if(
			$db->addSystemUser(
				md5(rand()), $_POST['username'], $_POST['username'],
				password_hash($_POST['password'], PASSWORD_DEFAULT),
				0/*ldap flag*/, null, null, null, 'initial admin user', 0/*locked*/, 1/*default role: superadmin*/
			)
		) {
			header('Location: login.php');
			die();
		} else {
			$info = LANG('database_error');
			$infoclass = 'error';
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>[<?php echo LANG('setup'); ?>] <?php echo LANG('app_name'); ?></title>
	<?php require_once('head.inc.php'); ?>
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'><span class='title'><?php echo LANG('app_name'); ?></span></span>
		<span class='right'>
		</span>
	</div>

	<div id='login'>
		<div id='login-form'>
			<form method='POST' action='setup.php' onsubmit='btnFinish.disabled = true'>
				<h1><?php echo LANG('setup'); ?></h1>
				<?php if($info !== null) { ?>
					<div class='alert bold <?php echo $infoclass; ?>'><?php echo $info; ?></div>
				<?php } ?>
				<input type='text' name='username' placeholder='<?php echo LANG('choose_admin_username'); ?>' autofocus='true'>
				<input type='password' name='password' placeholder='<?php echo LANG('choose_admin_password'); ?>'>
				<input type='password' name='password2' placeholder='<?php echo LANG('confirm_admin_password'); ?>'>
				<button id='btnFinish'><?php echo LANG('done'); ?></button>
			</form>
			<img src='img/logo.dyn.svg'>
		</div>
		<div id='login-bg'>

		</div>
	</div>

</div>

</body>
</html>
