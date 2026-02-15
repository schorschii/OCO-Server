<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$eqr = null;
try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_EVENT_QUERY_RULES);
	$eqr = $db->selectEventQueryRule($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($eqr->id??'-1',ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('log'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='log' autofocus='true' value='<?php echo htmlspecialchars($eqr->log??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('query'); ?></th>
		<td><textarea class='fullwidth monospace' name='query' rows='5'><?php echo htmlspecialchars($eqr->query??''); ?></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<span><?php echo LANG('change'); ?></span></button>
</div>
