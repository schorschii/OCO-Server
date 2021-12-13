<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<input type='hidden' id='txtReportGroup' value=''></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtReportName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' rows='4' id='txtReportNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['query']; ?></th>
		<td><textarea class='fullwidth monospace' rows='4' id='txtReportQuery'></textarea></td>
	</tr>
	<tr>
		<th></th>
		<td><button class='fullwidth' onclick='createReport(txtReportName.value, txtReportNotes.value, txtReportQuery.value, txtReportGroup.value)'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button></td>
	</tr>
</table>
