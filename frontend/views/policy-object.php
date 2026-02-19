<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

// ----- prepare view -----
$tab = 'machine';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

try {
	$policyObject = $cl->getPolicyObject($_GET['id']);
	$permissionEdit = $cl->checkPermission($policyObject, PermissionManager::METHOD_WRITE, false);
	$permissionDelete = $cl->checkPermission($policyObject, PermissionManager::METHOD_DELETE, false);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}

$translations = $db->selectAllPolicyTranslationByLanguage(LanguageController::getSingleton()->getCurrentLangCode());
function translatePolicy($name) {
	global $translations;
	if(isset($translations[$name]))
		return $translations[$name];
	else
		return LANG($name);
}
function getContents($group_id, $classMask) {
	global $db, $policyObject;
	$html = '';

	// get sub groups/categories
	$groups = $db->selectAllPolicyDefinitionGroupByParentPolicyDefinitionGroup($group_id);
	foreach($groups as $group) {
		$group->display_name = translatePolicy($group->display_name);
		$group->description = translatePolicy($group->description);
	}
	usort($groups, function($a, $b){ return $a->display_name <=> $b->display_name; });
	foreach($groups as $pdg) {
		$subGroups = getContents($pdg->id, $classMask);
		if(!$subGroups) continue;
		$html .= "<li>";
		$html .= "<h3><button class='expander'>".htmlspecialchars(translatePolicy($pdg->display_name))."</button></h3>";
		$html .= "<ul class='hidden'>".$subGroups."</ul>";
		$html .= "</li>\n";
	}

	// get policies of this group/category
	$policies = $db->selectAllPolicyByPolicyObjectAndParentPolicyDefinitionAndPolicyDefinitionGroupAndClass($policyObject->id, null, $group_id, $classMask);
	// if there are no polcies in here, return empty string
	if(!$policies) return $html;

	// sort by translated strings
	foreach($policies as $policy) {
		$policy->display_name = translatePolicy($policy->display_name);
		$policy->description = translatePolicy($policy->description);
	}
	usort($policies, function($a, $b){ return $a->display_name <=> $b->display_name; });

	// build policy table
	$html .= "<table class='list sortable fullwidth margintop policies'>";
	$html .= "<thead>";
	$html .= "<tr>";
	$html .= "	<th class='searchable sortable'>".LANG('name')."</th>";
	$html .= "	<th class='searchable sortable'>".LANG('configured')."</th>";
	$html .= "	<th class='searchable'>".LANG('options')."</th>";
	$html .= "</tr>";
	$html .= "</thead>";
	$html .= "<tbody>";
	foreach($policies as $pd) {
		$html .= "<tr>";
		$html .= "	<td class='subbuttons wrap' description='".htmlspecialchars($pd->description,ENT_QUOTES)."'>"
			.($pd->description ? "<a href='#' class='help'>" : "")
			.htmlspecialchars($pd->display_name)
			.($pd->description ? "</a>" : "")
			."</td>";
		$html .= "	<td><input type='checkbox' class='configured' ".($pd->value!==null ? 'checked' : '')."></td>";
		$html .= "	<td>";
		$html .= getPolicyInput($pd);
		$subPolicies = $db->selectAllPolicyByPolicyObjectAndParentPolicyDefinitionAndPolicyDefinitionGroupAndClass($policyObject->id, $pd->id, $group_id, $classMask);
		foreach($subPolicies as $subPolicy) {
			$html .= getPolicyInput($subPolicy);
		}
		$html .= "	</td>";
		$html .= "</tr>";
	}
	$html .= "</tbody>";
	$html .= "</table>";
	return $html;
}
function getPolicyInput($pd) {
	$html = '';
	if($pd->parent_policy_definition_id) {
		// if it's a sub item: show descriptive text
		$html .= '<div>'.htmlspecialchars(translatePolicy($pd->display_name)).'</div>';
	}
	if($pd->options == 'TEXT') {
		$html .= "<input type='text' class='fullwidth' policy_definition_id='".$pd->id."' value='".htmlspecialchars($pd->value??'',ENT_QUOTES)."' />";
	} elseif($pd->options == 'TEXT-MULTILINE') {
		$html .= "<textarea class='fullwidth' policy_definition_id='".$pd->id."'>".htmlspecialchars($pd->value??'')."</textarea>";
	} elseif(substr($pd->options, 0, 3) == 'INT') {
		$splitter = explode(':', $pd->options);
		$min = $splitter[1] ?? '';
		$max = $splitter[2] ?? '';
		$html .= "<input type='number' class='fullwidth' policy_definition_id='".$pd->id."' min='".$min."' max='".$max."' value='".htmlspecialchars($pd->value??'',ENT_QUOTES)."' />";
	} elseif($pd->options == 'DICT' || $pd->options == 'LIST') {
		$counter = 0;
		foreach(json_decode($pd->value, true) ?? [1=>''] as $key => $value) {
			$html .= "<div class='spread'>";
			if($pd->options == 'DICT') {
				$html .= "<input type='text' class='fullwidth multipleKey' policy_definition_id='".$pd->id."' value='".htmlspecialchars($key,ENT_QUOTES)."' />";
			}
			$html .= "<input type='text' class='fullwidth multiple' policy_definition_id='".$pd->id."' value='".htmlspecialchars($value,ENT_QUOTES)."' />";
			$html .= "<button class='addValue small ".($counter ? 'hidden' : '')."' title='".LANG('add')."'><img src='img/add.dyn.svg'></button>";
			$html .= "<button class='removeValue small ".($counter ? '' : 'hidden')."' title='".LANG('remove')."'><img src='img/remove.dyn.svg'></button>";
			$html .= "</div>";
			$counter ++;
		}
	} elseif($options = json_decode($pd->options)) {
		$html .= "<select class='fullwidth' policy_definition_id='".$pd->id."'>";
		foreach($options as $option => $value) {
			$html .= "<option value='".htmlspecialchars($value,ENT_QUOTES)."' ".(($pd->value!==null && $value==$pd->value) ? 'selected' : '').">"
				.htmlspecialchars(translatePolicy($option))
				."</option>";
		}
		$html .= "</select>";
	} elseif($options == '') {
		// its a parent policy without manifestation,
		// but we still need to place a hidden input to not forget the "active" checkbox state
		$html .= "<input type='hidden' policy_definition_id='".$pd->id."' value='".htmlspecialchars($pd->value??'',ENT_QUOTES)."' />";
	}
	return $html;
}
?>

<h1><img src='img/policy.dyn.svg'><span id='page-title'><?php echo htmlspecialchars($policyObject->name); ?></span></h1>
<div class='controls sticky'>
	<button id='btnSave' class='primary' <?php if(!$permissionEdit) echo 'disabled'; ?>>
		<img src='img/save.white.svg'>&nbsp;<?php echo LANG('save'); ?>
	</button>
	<button onclick='showDialogEditPolicyObject(spnPolicyObjectId.innerText, obj("page-title").innerText)' <?php if(!$permissionEdit) echo 'disabled'; ?>>
		<img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?>
	</button>
	<button onclick='confirmRemoveObject([spnPolicyObjectId.innerText], "remove_policy_object_id", "ajax-handler/policy-objects.php", event, LANG["confirm_delete"], "", "views/policy-objects.php")' <?php if(!$permissionDelete) echo 'disabled'; ?>>
		<img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?>
	</button>
	<span class='filler'></span>
</div>
<span id='spnPolicyObjectId' class='hidden'><?php echo $policyObject->id; ?></span>

<div id='tabControlPolicyObject' class='tabcontainer'>
	<div class='tabbuttons'>
		<a href='#' name='machine' class='<?php if($tab=='machine') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPolicyObject,this.getAttribute("name"))'>
			<?php echo LANG('computer_policies'); ?>
		</a>
		<a href='#' name='user' class='<?php if($tab=='user') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlPolicyObject,this.getAttribute("name"))'>
			<?php echo LANG('user_policies'); ?>
		</a>
	</div>
	<div class='tabcontents'>

		<div name='machine' class='<?php if($tab=='machine') echo 'active'; ?>'>
			<?php $content = getContents(null, Models\PolicyDefinition::CLASS_MACHINE); ?>
			<?php if(empty($content)) { ?>
				<div class='alert warning'><?php echo LANG('import_policy_definitions_first'); ?></div>
			<?php } else { ?>
				<ul class='tree machine'>
					<?php echo $content; ?>
				</ul>
			<?php } ?>
		</div>

		<div name='user' class='<?php if($tab=='user') echo 'active'; ?>'>
			<?php $content = getContents(null, Models\PolicyDefinition::CLASS_USER); ?>
			<?php if(empty($content)) { ?>
				<div class='alert warning'><?php echo LANG('import_policy_definitions_first'); ?></div>
			<?php } else { ?>
				<ul class='tree user'>
					<?php echo $content; ?>
				</ul>
			<?php } ?>
		</div>

	</div>
</div>

<script>
// init expand buttons
let expandButtons = tabControlPolicyObject.querySelectorAll('button.expander');
for(let i=0; i<expandButtons.length; i++) {
	expandButtons[i].addEventListener('click', (e) => {
		let sub = e.srcElement.parentNode.parentNode.childNodes;
		for(let n=0; n<sub.length; n++) {
			if(sub[n].tagName == 'UL')
				sub[n].classList.toggle('hidden');
		}
	});
}
// init help buttons
let helpButtons = tabControlPolicyObject.querySelectorAll('a.help');
for(let i=0; i<helpButtons.length; i++) {
	helpButtons[i].addEventListener('click', (e) => {
		e.preventDefault();
		let parent = e.srcElement.parentNode;
		showDialog(parent.innerText, parent.getAttribute('description'), DIALOG_BUTTONS_CLOSE, false, true);
	});
}
// init multivalue add buttons
let addButtons = tabControlPolicyObject.querySelectorAll('button.addValue');
for(let i=0; i<addButtons.length; i++) {
	addButtons[i].addEventListener('click', (e) => {
		let parent = e.srcElement.parentNode;
		let clone = parent.cloneNode(true);
		clone.querySelectorAll('button.addValue')[0].remove();
		clone.querySelectorAll('button.removeValue')[0].classList.remove('hidden');
		clone.querySelectorAll('button.removeValue')[0].addEventListener('click', (e2) => {
			e2.srcElement.parentElement.remove();
		});
		parent.parentNode.appendChild(clone);
	});
}
let removeButtons = tabControlPolicyObject.querySelectorAll('button.removeValue');
for(let i=0; i<removeButtons.length; i++) {
	removeButtons[i].addEventListener('click', (e) => {
		e.srcElement.parentElement.remove();
	});
}
// init configured checkboxes
let configuredCheckBoxes = tabControlPolicyObject.querySelectorAll('input[type=checkbox].configured');
for(let i=0; i<configuredCheckBoxes.length; i++) {
	configuredCheckBoxes[i].addEventListener('click', (e) => {
		e.srcElement.parentNode.setAttribute('sort_key', e.srcElement.checked ? 1 : 0);
	});
	configuredCheckBoxes[i].parentNode.setAttribute('sort_key', configuredCheckBoxes[i].checked ? 1 : 0);
}
// init save button
btnSave.addEventListener('click', (e) => {
	let policyData = {'edit_policy_object_id':spnPolicyObjectId.innerText};
	let policyRows = tabControlPolicyObject.querySelectorAll('table.policies tbody tr');
	for(let i=0; i<policyRows.length; i++) {
		let policyConfiguredCheckbox = policyRows[i].querySelectorAll('input[type=checkbox].configured')[0];
		if(policyConfiguredCheckbox.checked) {
			let policyValueInputs = policyRows[i].querySelectorAll(
				'td:nth-child(3) input:not(.multipleKey), td:nth-child(3) textarea, td:nth-child(3) select'
			);
			for(let n=0; n<policyValueInputs.length; n++) {
				let policyClassPrefix = isInsideParentWithClass(policyValueInputs[n], 'user') ? 'user' : 'machine';
				let policyDefinitionId = policyClassPrefix+':'+policyValueInputs[n].getAttribute('policy_definition_id');
				if(policyValueInputs[n].classList.contains('multiple')) {
					// multiple values - create an array or dict
					let policyValueKeys = policyValueInputs[n].parentNode.querySelectorAll('input.multipleKey');
					if(policyValueKeys.length > 1) {
						console.warn('More than 1 input.multipleKey found, sus!');
					} else if(policyValueKeys.length == 1) {
						if(!(policyDefinitionId in policyData))
							policyData[policyDefinitionId] = {}
						policyData[policyDefinitionId][policyValueKeys[0].value] = policyValueInputs[n].value;
					} else {
						if(policyDefinitionId in policyData) {
							policyData[policyDefinitionId].push(policyValueInputs[n].value);
						} else {
							policyData[policyDefinitionId] = [policyValueInputs[n].value];
						}
					}
				} else {
					// single value
					policyData[policyDefinitionId] = policyValueInputs[n].value;
				}
			}
		}
	}
	// convert arrays to JSON string
	for(const [key, value] of Object.entries(policyData)) {
		if(value.constructor === Array || value.constructor === Object)
			policyData[key] = JSON.stringify(value);
	}
	ajaxRequestPost('ajax-handler/policy-objects.php', urlencodeObject(policyData), null, function() {
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
});
</script>
