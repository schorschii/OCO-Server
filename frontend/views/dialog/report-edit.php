<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$report = null;
try {
	if(!empty($_GET['id']) && $_GET['id'] > 0)
		$report = $cl->getReport($_GET['id']);
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('not_found'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('permission_denied'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($report->id??-1,ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('group'); ?></th>
		<td>
			<select class='fullwidth' name='report_group_id'>
				<option value=''>-</option>
				<?php foreach($cl->getReportGroups() as $rg) { ?>
					<option value='<?php echo $rg->id; ?>' <?php if(($report->report_group_id??$_GET['report_group_id']??null)==$rg->id) echo 'selected'; ?>><?php echo htmlspecialchars($rg->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo htmlspecialchars($report->name??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' rows='4' name='notes'><?php echo htmlspecialchars($report->notes??''); ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('query'); ?></th>
		<td><textarea class='fullwidth monospace' rows='6' cols='30' name='query'><?php echo htmlspecialchars($report->query??''); ?></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo $report ? LANG('change') : LANG('create'); ?></button>
</div>
