<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('license_file'); ?></th>
		<td><input type='file' class='fullwidth' id='fleLicenseFile' accept='.ocolicense' autofocus='true'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='editLicense(fleLicenseFile.files[0])'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
