<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('serial_no'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtCreateMobileDeviceSerial' autofocus='true'></input></td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert info' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('new_ios_device_info'); ?>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnCreateMobileDevice' class='primary' onclick='if(txtCreateMobileDeviceSerial.value=="") return; window.open("views/settings-mdm.php?download=mdm-enrollment-profile&serial="+encodeURIComponent(txtCreateMobileDeviceSerial.value), "_blank")'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create'); ?></button>
</div>
