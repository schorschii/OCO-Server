<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$key = $_GET['key'] ?? '';
$value = '';
try {
	$md = $cl->getMobileDevice($_GET['id'] ?? -1);
	$parameters = json_decode($md->parameters,true)??[];
	$value = $parameters[$key] ?? '';
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
	<tr>
		<td>
			<input type='text' class='fullwidth monospace' autofocus='true' autocomplete='new-password' name='key' placeholder='<?php echo LANG('name'); ?>' value='<?php echo htmlspecialchars($key); ?>'></input>
		</td>
	</tr>
	<tr>
		<td>
			<textarea class='fullwidth monospace' autocomplete='new-password' name='value' placeholder='<?php echo LANG('value'); ?>' rows='8'><?php echo htmlspecialchars($value); ?></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('save'); ?></button>
</div>
