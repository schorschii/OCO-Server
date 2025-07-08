<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtEditPackageFamilyId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditPackageFamilyName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('licenses'); ?></th>
		<td><input type='number' class='fullwidth' autocomplete='new-password' id='txtEditPackageFamilyLicenseCount' placeholder='<?php echo LANG('optional_hint'); ?>' min='0'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditPackageFamilyNotes'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdatePackageFamily' class='primary' onclick='editPackageFamily(
		txtEditPackageFamilyId.value,
		txtEditPackageFamilyName.value,
		txtEditPackageFamilyLicenseCount.value=="" ? -1 : txtEditPackageFamilyLicenseCount.value,
		txtEditPackageFamilyNotes.value
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
