<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditPackageFamilyId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('hostname'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditPackageFamilyName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditPackageFamilyNotes'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdatePackageFamily' class='primary' onclick='editPackageFamily(txtEditPackageFamilyId.value, txtEditPackageFamilyName.value, txtEditPackageFamilyNotes.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
