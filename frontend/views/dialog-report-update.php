<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditReportId' value=''></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditReportName' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' rows='4' id='txtEditReportNotes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['query']; ?></th>
		<td><textarea class='fullwidth monospace' rows='4' id='txtEditReportQuery'></textarea></td>
	</tr>
	<tr>
		<th></th>
		<td><button class='fullwidth' onclick='editReport(txtEditReportId.value, txtEditReportName.value, txtEditReportNotes.value, txtEditReportQuery.value)'><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG['edit']; ?></button></td>
	</tr>
</table>
