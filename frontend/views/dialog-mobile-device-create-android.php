<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$ae = new Android\AndroidEnrollment($db);
$qrImageBase64 = base64_encode(
	Android\AndroidEnrollment::generateQrCode(
		$ae->generateEnrollmentToken()
	)
);
?>

<table class='fullwidth aligned'>
	<tr>
		<td>
			<img style='width:350px;height:auto;min-width:100%' src='data:image/png;base64,<?php echo $qrImageBase64; ?>'>
		</td>
	</tr>
	<tr>
		<td>
			<div class='alert info' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('new_android_device_info'); ?>
			</div>
		</td>
	</tr>
</table>
