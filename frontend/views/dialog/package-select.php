<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

function echoTargetPackageGroupOptions($parent=null) {
	global $db;
	global $cl;

	foreach($db->selectAllPackageGroupByParentPackageGroupId($parent) as $pg) {
		if(!$cl->checkPermission($pg, PermissionManager::METHOD_READ, false)
		&& !$cl->checkPermission($pg, PermissionManager::METHOD_DEPLOY, false)) continue;

		echo "<a href='#' class='blockListItem' onclick='refreshDeployPackageList(".$pg->id.");return false'>";
		echo htmlspecialchars($pg->name);
		echo "<img src='img/eye.dyn.svg' class='dragicon'>";
		echo "</a>";
		echo "<div class='subgroup'>";
		echoTargetPackageGroupOptions($pg->id);
		echo "</div>";
	}
}
?>

<script>
btnDoAssignPackage.addEventListener('click', function(e){
	let packageId = getSelectedCheckBoxValues('packages');
	if(packageId.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	if(txtAction.value == 'add_package_dependency') {
		let params = [];
		params.push({'key':'edit_package_id', 'value':txtEditSubjectId.value});
		for(var i = 0; i < packageId.length; i++) {
			params.push({'key':'add_package_dependency_id[]', 'value':packageId[i]});
		}
		ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
			hideDialog(); refreshContent();
			emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
		});

	} else if(txtAction.value == 'add_dependant_package') {
		let params = [];
		params.push({'key':'edit_package_id', 'value':txtEditSubjectId.value});
		for(var i = 0; i < packageId.length; i++) {
			params.push({'key':'add_dependant_package_id[]', 'value':packageId[i]});
		}
		ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
			hideDialog(); refreshContent();
			emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
		});

	} else if(txtAction.value == 'add_computer_package') {
		let params = [];
		params.push({'key':'edit_computer_id', 'value':txtEditSubjectId.value});
		for(var i = 0; i < packageId.length; i++) {
			params.push({'key':'add_package_id[]', 'value':packageId[i]});
		}
		ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function() {
			hideDialog(); refreshContent();
			emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
});
</script>

<input type='hidden' id='txtEditSubjectId' value='<?php echo htmlspecialchars($_GET['subject'],ENT_QUOTES); ?>'></input>
<input type='hidden' id='txtAction' value='<?php echo htmlspecialchars($_GET['action'],ENT_QUOTES); ?>'></input>
<div class='gallery'>
	<div>
		<h3><?php echo LANG('package_selection'); ?> (<span id='spnSelectedPackages'>0</span>/<span id='spnTotalPackages'>0</span>)</h3>
		<div class='listSearch'>
			<input type='checkbox' title='<?php echo LANG('select_all'); ?>' onchange='toggleCheckboxesInContainer(divPackageList, this.checked);refreshDeployPackageCount()'>
			<input type='search' id='txtDeploySearchPackages' placeholder='<?php echo LANG('search_placeholder'); ?>' oninput='searchItems(divPackageList, this.value)'>
		</div>
		<div id='divPackageList' class='box listSearchList'>
			<a href='#' class='blockListItem big noSearch' onclick='refreshDeployPackageList(-1);return false'><?php echo LANG('all_packages'); ?><img src='img/eye.dyn.svg' class='dragicon'></a>
			<?php echoTargetPackageGroupOptions(); ?>
		</div>
		<div id='divPackageListHome' class='box listSearchList hidden'>
			<a href='#' class='blockListItem big noSearch' onclick='refreshDeployPackageList(-1);return false'><?php echo LANG('all_packages'); ?><img src='img/eye.dyn.svg' class='dragicon'></a>
			<?php echoTargetPackageGroupOptions(); ?>
		</div>
	</div>
</div>

<div class='controls right'>
	<button class='closeDialog'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' id='btnDoAssignPackage'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
