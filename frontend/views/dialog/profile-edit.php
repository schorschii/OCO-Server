<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$profile = null;
try {
	$profile = $cl->getProfile($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($profile->id??-1,ENT_QUOTES); ?>'>
<input type='hidden' name='type' value='<?php echo htmlspecialchars($profile->type??$_GET['type']??'',ENT_QUOTES); ?>'>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo htmlspecialchars($profile->name??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' name='notes'><?php echo htmlspecialchars($profile->notes??''); ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('profile_content'); ?></th>
		<td>
			<input type='file' class='fullwidth' name='payload' accept='.mobileconfig' style='<?php if(($profile->type??$_GET['type']??'') != Models\Profile::TYPE_IOS) echo 'display:none'; ?>'></input>
			<textarea class='fullwidth monospace' name='payload' rows='12' cols='45'><?php echo htmlspecialchars($profile->payload??''); ?></textarea>
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
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo $profile ? LANG('change') : LANG('create'); ?></span></button>
</div>
