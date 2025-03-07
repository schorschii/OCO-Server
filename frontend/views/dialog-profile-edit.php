<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtProfileId' value='-1'>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtProfileName' autofocus='true'></input></td>
	</tr>
	<tr id='trProfileFile'>
		<th><?php echo LANG('profile_file'); ?></th>
		<td><input type='file' class='fullwidth' id='fleProfilePayload'></input></td>
	</tr>
	<tr id='trProfileText' style='display:none'>
		<th><?php echo LANG('profile_content'); ?></th>
		<td><textarea class='fullwidth' id='txtProfilePayload'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' id='txtNotes'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateProfile' class='primary' onclick='editProfile(
		txtProfileId.value,
		txtProfileName.value,
		txtProfilePayload.value != "" ? txtProfilePayload.value : fleProfilePayload.files,
		txtNotes.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateProfile'><?php echo LANG('change'); ?></span></button>
</div>
