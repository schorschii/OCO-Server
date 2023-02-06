<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('configuration_json'); ?></th>
		<td><textarea class='fullwidth monospace' autocomplete='new-password' id='txtEditDomainUserLdapSync' rows='8'><?php echo $db->selectSettingByKey('domain-user-ldapsync'); ?></textarea></td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert warning' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('configuration_json_docs'); ?>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='editLdapConfigDomainUsers(txtEditDomainUserLdapSync.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('save'); ?></button>
</div>
