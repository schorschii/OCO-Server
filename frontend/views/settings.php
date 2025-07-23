<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('settings'); ?></span></h1>
</div>

<div class='actionmenu'>
	<a <?php echo Html::explorerLink('views/settings-configuration.php'); ?>>&rarr;&nbsp;<?php echo LANG('configuration_overview'); ?></a>
	<a <?php echo Html::explorerLink('views/settings-own.php'); ?>>&rarr;&nbsp;<?php echo htmlspecialchars(str_replace('%',$currentSystemUser->username,LANG('system_user_placeholder'))); ?></a>
	<a <?php echo Html::explorerLink('views/settings-system-users.php'); ?>>&rarr;&nbsp;<?php echo LANG('system_user_management'); ?></a>
	<a <?php echo Html::explorerLink('views/settings-domain-users.php'); ?>>&rarr;&nbsp;<?php echo LANG('self_service_management'); ?></a>
	<a <?php echo Html::explorerLink('views/settings-event-query-rules.php'); ?>>&rarr;&nbsp;<?php echo LANG('event_query_rules'); ?></a>
	<a <?php echo Html::explorerLink('views/settings-password-rotation-rules.php'); ?>>&rarr;&nbsp;<?php echo LANG('password_rotation_rules'); ?></a>
	<a <?php echo Html::explorerLink('views/settings-mdm.php'); ?>>&rarr;&nbsp;<?php echo LANG('mobile_device_management'); ?></a>
	<a <?php echo Html::explorerLink('views/settings-deleted-objects.php'); ?>>&rarr;&nbsp;<?php echo LANG('deleted_objects_history'); ?></a>
</div>
