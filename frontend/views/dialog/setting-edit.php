<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
	$key = $_GET['key'] ?? '';
	$settingValue = $db->settings->get($key) ?? '';
	$hideKey = $_GET['hideKey'] ?? false;
	$file = $_GET['file'] ?? '';
	$warning = $_GET['warning'] ?? 'be_careful_when_manual_editing_settings';
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('permission_denied'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('not_found'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<table class='fullwidth aligned'>
	<tr class='<?php if($hideKey) echo 'hidden'; ?>'>
		<td>
			<input type='text' class='fullwidth monospace' <?php if(!$hideKey) echo 'autofocus="true"'; ?> autocomplete='new-password' name='key' placeholder='<?php echo LANG('key'); ?>' value='<?php echo htmlspecialchars($key); ?>'></input>
		</td>
	</tr>
	<tr class='<?php if($file) echo 'hidden'; ?>'>
		<td>
			<textarea class='fullwidth monospace' <?php if($hideKey && !$file) echo 'autofocus="true"'; ?> autocomplete='new-password' name='value' placeholder='<?php echo LANG('value'); ?>' rows='8'><?php echo htmlspecialchars($settingValue); ?></textarea>
		</td>
	</tr>
	<tr class='<?php if(!$file) echo 'hidden'; ?>'>
		<td>
			<input type='file' class='fullwidth' <?php if($hideKey && $file) echo 'autofocus="true"'; ?> name='value' accept='<?php echo htmlspecialchars($file,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<?php if($warning) { ?>
	<tr>
		<td>
			<div class='alert warning' style='margin-top:0px;width:450px;min-width:100%'>
				<?php echo htmlspecialchars(LANG($warning)); ?>
			</div>
		</td>
	</tr>
	<?php } ?>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('save'); ?></button>
</div>
