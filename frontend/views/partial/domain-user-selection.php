<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../../loader.inc.php');
require_once(__DIR__.'/../../session.inc.php');
?>

<div class='gallery domainUserSelection'>
	<div>
		<h3><?php echo LANG('domain_user_selection'); ?> (<span class='selectedItems'>0</span>/<span class='totalItems'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' class='toggleAll'>
			<input type='search' class='searchItems' placeholder='<?php echo LANG('search_placeholder'); ?>'>
		</div>
		<div class='box listItems listHome'>
			<?php foreach($cl->getDomainUsers() as $du) { ?>
				<label class='blockListItem'>
					<input type='checkbox' name='domain_users' value='<?php echo htmlspecialchars($du->id,ENT_QUOTES); ?>' />
					<?php echo htmlspecialchars($du->displayNameWithUsername()); ?>
				</label>
			<?php } ?>
		</div>
	</div>
</div>
