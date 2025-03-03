<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$ade = new Apple\AutomatedDeviceEnrollment($db);
	$vpp = new Apple\VolumePurchaseProgram($db);
	$as = new Apple\AppStore($db, $vpp);
	$ae = new Android\AndroidEnrollment($db);
	$license = new LicenseCheck($db);
	$permGeneral = $cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);

	// deliver download file if requested
	if(!empty($_GET['download'])) switch($_GET['download']) {
		case 'mdm-vendor-csr':
			header('Content-Disposition: attachment; filename=mdm-vendor.csr');
			echo $ade->generateMdmVendorCsr();
			die();
		case 'mdm-apn-csr':
			header('Content-Disposition: attachment; filename=mdm-apn.csr');
			echo $ade->generateMdmApnCsr();
			die();
		case 'mdm-token-cert':
			header('Content-Disposition: attachment; filename=mdm-token-cert.pem');
			echo $ade->generateMdmServerTokenCert();
			die();
	}

	// deliver download file if requested
	if(!empty($_POST['action'])) switch($_POST['action']) {
		case 'generate-google-signup-url':
			$ae->generateSignupUrl();
			die();
	}

	// ----- prepare view -----
	$ownMdmVendorCertExpiry = null;
	$mdmApnCertExpiry = null;
	$mdmServerTokenExpiry = null;
	$mdmActivationProfile = null;
	$mdmApiUrl = null;
	$vppTokenExpiry = null;
	$appStoreKey = false;
	$googleApiCredentials = null;
	$companyName = null;
	$signupUrl = null;
	$enterprise = null;
	try {
		$ownMdmVendorCertInfo = openssl_x509_parse( $ade->getOwnMdmVendorCert()['cert'] );
		$ownMdmVendorCertExpiry = date('Y-m-d H:i:s', intval($ownMdmVendorCertInfo['validTo_time_t']));
	} catch(RuntimeException $e) {}
	try {
		$mdmApnCertInfo = openssl_x509_parse( $ade->getMdmApnCert()['cert'] );
		$mdmApnCertExpiry = date('Y-m-d H:i:s', intval($mdmApnCertInfo['validTo_time_t']));
	} catch(RuntimeException $e) {}
	try {
		$mdmServerToken = $ade->getMdmServerToken();
		$mdmServerTokenExpiry = date('Y-m-d H:i:s', strtotime($mdmServerToken['access_token_expiry']));
	} catch(RuntimeException $e) {}
	try {
		$mdmActivationProfile = $ade->getActivationProfile();
	} catch(RuntimeException $e) {}
	try {
		$mdmApiUrl = $ade->getMdmApiUrl();
	} catch(RuntimeException $e) {}
	try {
		$vppToken = $vpp->getToken();
		$vppTokenExpiry = date('Y-m-d H:i:s', strtotime($vppToken['expDate']));
	} catch(RuntimeException $e) {}
	try {
		$appStoreKey = $as->getKey() && $as->getKeyId() && $as->getTeamId();
	} catch(RuntimeException $e) {}
	try {
		$googleApiCredentials = $ae->getOAuthCredentials();
	} catch(RuntimeException $e) {}
	try {
		$companyName = $ae->getCompanyName();
	} catch(RuntimeException $e) {}
	try {
		$signupUrl = $ae->getSignupUrl();
	} catch(RuntimeException $e) {}
	try {
		$enterprise = $ae->getEnterprise();
	} catch(RuntimeException $e) {}
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('mobile_device_management'); ?></span></h1>
</div>

<div class='details-abreast'>
	<div>
		<div class='controls heading'>
			<h2><?php echo LANG('general'); ?></h2>
			<div class='filler invisible'></div>
		</div>
		<table class='list'>
			<tr>
				<th><?php echo LANG('mdm_api_url'); ?>:</th>
				<td>
					<?php if($mdmApiUrl) { ?>
						<div class='alert success'><?php echo htmlspecialchars($mdmApiUrl); ?></div>
					<?php } else { ?>
						<div class='alert error'><?php echo LANG('not_defined'); ?></div>
					<?php } ?>
					<button onclick='showDialogEditSetting("apple-mdm-api-url",false,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
				</td>
			</tr>
		</table>

		<div class='controls heading'>
			<h2><?php echo LANG('apple_business_manager'); ?></h2>
			<div class='filler invisible'></div>
		</div>
		<table class='list'>
			<tr>
				<th><?php echo LANG('mdm_vendor_cert'); ?>:</th>
				<td>
					<?php if($ownMdmVendorCertExpiry) { ?>
						<div class='alert success'><?php echo str_replace('%1', $ownMdmVendorCertExpiry, LANG('valid_until_placeholder')); ?></div>
					<?php } elseif($license->isValid()) { ?>
						<div class='alert info'><?php echo LANG('valid_oco_license_vendor_mdm_cert_service_can_be_used'); ?></div>
					<?php } else { ?>
						<div class='alert error'><?php echo LANG('no_mdm_vendor_cert_and_no_oco_license'); ?></div>
					<?php } ?>
					<button onclick='window.open("views/settings-mdm.php?download=mdm-vendor-csr","_blank")' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/download.dyn.svg'>&nbsp;<?php echo LANG('download_csr'); ?></button>
					<button onclick='showDialogEditSetting("apple-mdm-vendor-cert",true,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('upload_cert'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('mdm_apn_cert'); ?>:</th>
				<td>
					<?php if($mdmApnCertExpiry) { ?>
						<div class='alert success'><?php echo str_replace('%1', $mdmApnCertExpiry, LANG('valid_until_placeholder')); ?></div>
					<?php } else { ?>
						<div class='alert error'><?php echo LANG('not_found'); ?></div>
					<?php } ?>
					<button onclick='window.open("views/settings-mdm.php?download=mdm-apn-csr","_blank")' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/download.dyn.svg'>&nbsp;<?php echo LANG('download_csr'); ?></button>
					<button onclick='showDialogEditSetting("apple-mdm-apn-cert",true,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('upload_cert'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('mdm_server_token'); ?>:</th>
				<td>
					<?php if($mdmServerTokenExpiry) { ?>
						<div class='alert success'><?php echo str_replace('%1', $mdmServerTokenExpiry, LANG('valid_until_placeholder')); ?></div>
					<?php } else { ?>
						<div class='alert error'><?php echo LANG('not_found'); ?></div>
					<?php } ?>
					<button onclick='window.open("views/settings-mdm.php?download=mdm-token-cert","_blank")' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/download.dyn.svg'>&nbsp;<?php echo LANG('download_public_key'); ?></button>
					<button onclick='showDialogEditSetting("apple-mdm-token",true,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('upload_token'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('activation_profile'); ?>:</th>
				<td>
					<?php if($mdmActivationProfile) { ?>
						<div class='alert success'><?php echo LANG('defined'); ?></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('no_activation_profile_defined'); ?></div>
					<?php } ?>
					<button onclick='showDialogEditSetting("apple-mdm-activation-profile",false,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('vpp_token'); ?>:</th>
				<td>
					<?php if($vppTokenExpiry) { ?>
						<div class='alert success'><?php echo str_replace('%1', $vppTokenExpiry, LANG('valid_until_placeholder')); ?></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('no_vpp_token_provided'); ?></div>
					<?php } ?>
					<button onclick='showDialogEditSetting("apple-vpp-token",true,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('app_store_api_key'); ?>:</th>
				<td>
					<?php if($appStoreKey) { ?>
						<div class='alert success'><?php echo LANG('defined'); ?></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('no_app_store_api_key_provided'); ?></div>
					<?php } ?>
					<button onclick='showDialogEditSetting("apple-appstore-key",true,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('upload_key'); ?></button>
					<button onclick='showDialogEditSetting("apple-appstore-keyid",false,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('key_id'); ?></button>
					<button onclick='showDialogEditSetting("apple-appstore-teamid",false,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('team_id'); ?></button>
				</td>
			</tr>
		</table>

		<div class='controls heading'>
			<h2><?php echo LANG('google_android_enterprise'); ?></h2>
			<div class='filler invisible'></div>
		</div>
		<table class='list'>
			<tr>
				<th><?php echo LANG('service_account_credentials'); ?>:</th>
				<td>
					<?php
					if($googleApiCredentials) { ?>
						<div class='alert success'><?php echo htmlspecialchars($googleApiCredentials['client_email']); ?></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('not_set_up'); ?></div>
					<?php } ?>
					<button onclick='showDialogEditSetting("google-api-credentials",true,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('company_name'); ?>:</th>
				<td>
					<?php if($companyName) { ?>
						<div class='alert success'><?php echo htmlspecialchars($companyName); ?></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('not_set_up'); ?></div>
					<?php } ?>
					<button onclick='showDialogEditSetting("google-company-name",false,false,true)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('signup_url'); ?>:</th>
				<td>
					<?php if($signupUrl) { ?>
						<div class='alert success'><a target='_blank' href='<?php echo htmlspecialchars($signupUrl['url']); ?>'><?php echo htmlspecialchars($signupUrl['url']); ?></a></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('not_set_up'); ?></div>
					<?php } ?>
					<button onclick='ajaxRequestPost("views/settings-mdm.php","action=generate-google-signup-url", null, refreshContent)' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('generate'); ?></button>
				</td>
			</tr>
			<tr>
				<th><?php echo LANG('linked_enterprise'); ?>:</th>
				<td>
					<?php if($enterprise) { ?>
						<div class='alert success'><?php echo htmlspecialchars($enterprise['name']); ?></div>
					<?php } else { ?>
						<div class='alert warning'><?php echo LANG('not_set_up'); ?></div>
					<?php } ?>
				</td>
			</tr>
		</table>
	</div>
</div>
