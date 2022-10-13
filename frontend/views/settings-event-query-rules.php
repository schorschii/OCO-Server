<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_EVENT_QUERY_RULES);
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('event_query_rules'); ?></span></h1>
</div>

<p><?php echo LANG('event_query_rules_description'); ?></p>

<div class='controls'>
	<button onclick='showDialogEditEventQueryRule()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create'); ?></button>
	<span class='filler'></span>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblEventQueryRules' class='list searchable sortable savesort actioncolumn'>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='toggleCheckboxesInTable(tblEventQueryRules, this.checked)'></th>
					<th class='searchable sortable'><?php echo LANG('log'); ?></th>
					<th class='searchable sortable'><?php echo LANG('query'); ?></th>
					<th class=''><?php echo LANG('action'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$counter = 0;
				foreach($db->selectAllEventQueryRule() as $r) {
					$counter ++;
					echo "<tr>";
					echo "<td><input type='checkbox' name='event_query_rule_id[]' value='".$r->id."' onchange='refreshCheckedCounter(tblEventQueryRules)'></td>";
					echo "<td id='spnEventQueryRuleLog".$r->id."'>".htmlspecialchars($r->log)."</td>";
					echo "<td id='spnEventQueryRuleQuery".$r->id."' class='monospace'>".htmlspecialchars($r->query)."</td>";
					echo "<td><button onclick='showDialogEditEventQueryRule(".$r->id.", spnEventQueryRuleLog".$r->id.".innerText, spnEventQueryRuleQuery".$r->id.".innerText)' title='".LANG('edit')."'><img src='img/edit.dyn.svg'></button></td>";
					echo "</tr>";
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counter'><?php echo $counter; ?></span> <?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button onclick='confirmRemoveSelectedEventQueryRule("event_query_rule_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
