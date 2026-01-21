<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<input type='hidden' id='txtEditEventQueryRuleId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('log'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditEventQueryRuleLog' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('query'); ?></th>
		<td><textarea class='fullwidth monospace' autocomplete='new-password' id='txtEditEventQueryRuleQuery' rows='5'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateEventQueryRule' class='primary' onclick='editEventQueryRule(
		txtEditEventQueryRuleId.value, txtEditEventQueryRuleLog.value, txtEditEventQueryRuleQuery.value
		)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateEventQueryRule'><?php echo LANG('change'); ?></span></button>
</div>
