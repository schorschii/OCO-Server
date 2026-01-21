<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$profile = null;
try {
	$profile = $cl->getProfile($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' id='txtProfileId' value='<?php echo htmlspecialchars($profile->id??-1,ENT_QUOTES); ?>'>
<input type='hidden' id='txtProfileType' value='<?php echo htmlspecialchars($profile->type??$_GET['type']??'',ENT_QUOTES); ?>'>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtProfileName' autofocus='true' value='<?php echo htmlspecialchars($profile->name??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' id='txtNotes'><?php echo htmlspecialchars($profile->notes??''); ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('profile_content'); ?></th>
		<td>
			<input type='file' class='fullwidth' id='fleProfilePayload' accept='.mobileconfig' style='<?php if(($profile->type??$_GET['type']??'') != Models\Profile::TYPE_IOS) echo 'display:none'; ?>'></input>
			<textarea class='fullwidth monospace' id='txtProfilePayload' rows='12' cols='45'><?php echo htmlspecialchars($profile->payload??''); ?></textarea>
		</td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert info'><?php echo LANG('policy_docs'); ?></div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateProfile' class='primary' onclick='editProfile(
		txtProfileId.value,
		txtProfileType.value,
		txtProfileName.value,
		txtProfilePayload.value != "" ? txtProfilePayload.value : fleProfilePayload.files,
		txtNotes.value
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateProfile'><?php echo $profile ? LANG('change') : LANG('create'); ?></span></button>
</div>
