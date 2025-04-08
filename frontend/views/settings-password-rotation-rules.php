<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_PASSWORD_ROTATION_RULES);
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('password_rotation_rules'); ?></span></h1>
</div>

<div class='controls'>
	<button onclick='showDialogEditPasswordRotationRule()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('create'); ?></button>
	<span class='filler'></span>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblPasswordRotationRules' class='list searchable sortable savesort actioncolumn'>
			<thead>
				<tr>
					<th><input type='checkbox' class='toggleAllChecked'></th>
					<th class='searchable sortable'><?php echo LANG('computer_group'); ?></th>
					<th class='searchable sortable'><?php echo LANG('username'); ?></th>
					<th class='searchable sortable'><?php echo LANG('alphabet'); ?></th>
					<th class='searchable sortable'><?php echo LANG('length'); ?></th>
					<th class='searchable sortable'><?php echo LANG('valid_for'); ?></th>
					<th class='searchable sortable'><?php echo LANG('history_count'); ?></th>
					<th class='searchable sortable'><?php echo LANG('initial_password_macos'); ?></th>
					<th class=''><?php echo LANG('action'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($db->selectAllPasswordRotationRule() as $r) {
					echo "<tr>";
					echo "<td><input type='checkbox' name='password_rotation_rule_id[]' value='".$r->id."'></td>";
					echo "<td id='spnPasswordRotationRuleComputerGroup".$r->id."' value='".htmlspecialchars($r->computer_group_id??'')."'>".htmlspecialchars($r->computer_group_name??'-')."</td>";
					echo "<td id='spnPasswordRotationRuleUsername".$r->id."'>".htmlspecialchars($r->username)."</td>";
					echo "<td id='spnPasswordRotationRuleAlphabet".$r->id."'>".htmlspecialchars($r->alphabet)."</td>";
					echo "<td id='spnPasswordRotationRuleLength".$r->id."'>".htmlspecialchars($r->length)."</td>";
					echo "<td id='spnPasswordRotationRuleValidSeconds".$r->id."' value='".htmlspecialchars($r->valid_seconds,ENT_QUOTES)."'>".niceTime($r->valid_seconds)."</td>";
					echo "<td id='spnPasswordRotationRuleHistory".$r->id."'>".htmlspecialchars($r->history)."</td>";
					echo "<td id='spnPasswordRotationRuleDefaultPassword".$r->id."' class='mask monospace' tabindex='0'><div class='maskValue'>".htmlspecialchars($r->default_password)."</div></td>";
					echo "<td><button onclick='showDialogEditPasswordRotationRule(".$r->id.", spnPasswordRotationRuleComputerGroup".$r->id.".getAttribute(\"value\"), spnPasswordRotationRuleUsername".$r->id.".innerText, spnPasswordRotationRuleAlphabet".$r->id.".innerText, spnPasswordRotationRuleLength".$r->id.".innerText, spnPasswordRotationRuleValidSeconds".$r->id.".getAttribute(\"value\"), spnPasswordRotationRuleHistory".$r->id.".innerText, spnPasswordRotationRuleDefaultPassword".$r->id.".innerText)' title='".LANG('edit')."'><img src='img/edit.dyn.svg'></button></td>";
					echo "</tr>";
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
								<button onclick='confirmRemoveSelectedPasswordRotationRule("password_rotation_rule_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
