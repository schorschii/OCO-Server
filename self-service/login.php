<?php
require_once('../loader.inc.php');

$license = new LicenseCheck($db);

$info = null;
$infoclass = null;

// execute login if requested
require_once('session-options.php');
if(isset($_POST['username']) && isset($_POST['password']) && SELF_SERVICE_ENABLED) {
	try {
		$authenticator = new SelfService\AuthenticationController($db);
		$user = $authenticator->login($_POST['username'], $_POST['password']);
		if($user == null || !$user instanceof Models\DomainUser) throw new Exception(LANG('unknown_error'));

		// login successful
		$db->insertLogEntry(Models\Log::LEVEL_INFO, $user->username, null, Models\Log::ACTION_SELF_SERVICE_WEB, ['authenticated'=>true]);
		$_SESSION['oco_self_service_last_login'] = $user->last_login;
		$_SESSION['oco_self_service_username'] = $user->username;
		$_SESSION['oco_self_service_user_id'] = $user->id;

		$redirect = 'index.php';
		if(!empty($_SESSION['oco_self_service_login_redirect'])) $redirect = $_SESSION['oco_self_service_login_redirect'];
		header('Location: '.$redirect); die('Welcome to the enchanting world of OCO!');
	} catch(AuthenticationException $e) {
		$db->insertLogEntry(Models\Log::LEVEL_WARNING, $_POST['username'], null, Models\Log::ACTION_SELF_SERVICE_WEB, ['authenticated'=>false]);

		$info = $e->getMessage();
		$infoclass = 'error';
	}
}

// execute logout if requested
elseif(isset($_GET['logout'])) {
	if(isset($_SESSION['oco_self_service_user_id'])) {
		$db->insertLogEntry(Models\Log::LEVEL_INFO, $_SESSION['oco_self_service_username'], null, Models\Log::ACTION_SELF_SERVICE_WEB, ['logout'=>true]);
		session_unset();
		session_destroy();
		$info = LANG('log_out_successful');
		$infoclass = 'success';
	}
}

// redirect to index.php if already logged in
if(!empty($_SESSION['oco_self_service_user_id']) && SELF_SERVICE_ENABLED) {
	header('Location: index.php');
	die();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>[<?php echo LANG('login'); ?>] <?php echo LANG('self_service_name'); ?></title>
	<?php require_once('head.inc.php'); ?>
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'>
			<a href='#' class='title'><?php echo LANG('self_service_name'); ?></a>
		</span>
		<span class='right'>
		</span>
	</div>

	<div id='login'>
		<div id='login-form'>
			<?php if(isIE()) { ?>
				<img src='img/ietroll.png'>
			<?php } elseif(!$license->isValid()) { ?>
				<div class='alert bold error'><?php echo LANG('your_license_is_invalid'); ?></div>
			<?php } elseif(SELF_SERVICE_ENABLED) { ?>
				<form method='POST' action='login.php' onsubmit='btnLogin.disabled=true; txtUsername.readOnly=true; txtPassword.readOnly=true;'>
					<h1><?php echo LANG('login'); ?></h1>
					<?php if($info !== null) { ?>
						<div class='alert bold <?php echo $infoclass; ?>'><?php echo $info; ?></div>
					<?php } ?>
					<input id='txtUsername' type='text' name='username' placeholder='<?php echo LANG('username'); ?>' autofocus='true'>
					<input id='txtPassword' type='password' name='password' placeholder='<?php echo LANG('password'); ?>'>
					<button id='btnLogin' class='primary'><?php echo LANG('log_in'); ?></button>
				</form>
				<img src='img/logo.dyn.svg'>
			<?php } else { ?>
				<div class='alert bold error'><?php echo LANG('self_service_is_disabled'); ?></div>
			<?php } ?>
		</div>

		<div id='login-wall'>
			<div id='login-bg'></div>
			<a href='https://github.com/schorschii/oco-server' target='_blank'>
				<img id='forkme' src='img/forkme.png'>
			</a>
			<div id='motd'><?php if(!empty(LOGIN_SCREEN_QUOTES)) echo LOGIN_SCREEN_QUOTES[ rand(0, sizeof(LOGIN_SCREEN_QUOTES)-1) ]; ?></div>
		</div>
	</div>

</div>

</body>
</html>
