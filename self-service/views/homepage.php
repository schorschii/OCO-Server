<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');
?>

<div id='homepage'>
	<img src='img/logo.dyn.svg'>
	<p>
		<div class='title'><?php echo LANG('self_service_name'); ?></div>
		<div class='subtitle'><?php echo LANG('project_name'); ?></div>
	</p>

	<div class='box fullwidth margintop stats'>
		<div>
			<div class='motd'><?php echo LANG('self_service_welcome_text'); ?></div>
		</div>
	</div>

	<div class='footer'>
		<?php echo LANG('app_copyright'); ?>
	</div>
</div>
