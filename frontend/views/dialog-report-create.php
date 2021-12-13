<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<input type='hidden' id='txtCreateReportGroup' value=''></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtCreateReportName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' rows='4' id='txtCreateReportNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['query']; ?></th>
		<td><textarea class='fullwidth monospace' rows='4' id='txtCreateReportQuery'></textarea></td>
	</tr>
	<tr>
		<th></th>
		<td><button class='fullwidth' onclick='createReport(txtCreateReportName.value, txtCreateReportNotes.value, txtCreateReportQuery.value, txtCreateReportGroup.value)'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button></td>
	</tr>
</table>
