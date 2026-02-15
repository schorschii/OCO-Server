<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$dr = $cl->getDeploymentRule($_GET['id'] ?? -1);
} catch(Exception $ignored) { }
?>

<input type='hidden' name='id' value='<?php echo $dr->id??-1; ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td>
			<input type='text' name='name' class='fullwidth' autocomplete='new-password' autofocus='true' value='<?php echo htmlspecialchars($dr->name??'',ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('state'); ?></th>
		<td>
			<label><input type='checkbox' name='enabled' <?php if($dr->enabled) echo 'checked'; ?>></input>&nbsp;<?php echo LANG('enabled'); ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('computer_group'); ?></th>
		<td>
			<select name='computer_group_id' class='fullwidth'>
				<?php Html::buildGroupOptions($cl, new Models\ComputerGroup(), 0, $dr->computer_group_id??null); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('package_group'); ?></th>
		<td>
			<select name='package_group_id' class='fullwidth'>
				<?php Html::buildGroupOptions($cl, new Models\PackageGroup(), 0, $dr->package_group_id??null); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td>
			<textarea name='notes' class='fullwidth' autocomplete='new-password'><?php echo htmlspecialchars($dr->notes??''); ?></textarea>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel' title='<?php echo LANG('priority_description'); ?>'>
				<input name='priority' type='range' min='-10' max='10' value='<?php echo htmlspecialchars($dr->priority??0,ENT_QUOTES); ?>'>
				<div class='priorityPreview'><?php echo htmlspecialchars($dr->priority??0); ?></div>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<span><?php echo LANG('change'); ?></span></button>
</div>
