<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$policyObject = $cl->getPolicyObject($_GET['id'] ?? -1);
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('permission_denied'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('not_found'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
} catch(Exception $e) {
	http_response_code(500);
	die($e->getMessage());
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
		$html .= "<h3>".htmlspecialchars(translatePolicy($pdg->display_name))."</h3>";
		$html .= "<ul>".$subGroups."</ul>";
		$html .= "</li>\n";
	}

	// get policies of this group/category and sort by translated strings
	$policies = $db->selectAllPolicyByPolicyObjectAndParentPolicyDefinitionAndPolicyDefinitionGroupAndClass($policyObject->id, null, $group_id, $classMask, true);
	foreach($policies as $policy) {
		$policy->display_name = translatePolicy($policy->display_name);
		$policy->description = translatePolicy($policy->description);
	}
	usort($policies, function($a, $b){ return $a->display_name <=> $b->display_name; });

	// if there are no polcies in here, return empty string
	if(!$policies) return $html;

	// build policy table
	$html .= "<table class='list fullwidth margintop'>";
	$html .= "<tbody>";
	foreach($policies as $pd) {
		$subPolicies = $db->selectAllPolicyByPolicyObjectAndParentPolicyDefinitionAndPolicyDefinitionGroupAndClass($policyObject->id, $pd->id, $group_id, $classMask, true);

		$html .= "<tr class='".($subPolicies?'nobottom':'')."'>";
		$html .= "	<th class='subbuttons wrap'>".htmlspecialchars($pd->display_name)."</th>";
		$html .= "	<td>".getPolicyValue($pd)."</td>";
		$html .= "</tr>";

		if($subPolicies) {
			$html .= "<tr><td colspan='999'>";
			$html .= "<table class='list fullwidth'>";
			foreach($subPolicies as $subPolicy) {
				$html .= "<tr>";
				$html .= "<th>".htmlspecialchars(translatePolicy($pd->display_name))."</th>";
				$html .= "<td>".getPolicyValue($subPolicy)."</td>";
				$html .= "</tr>";
			}
			$html .= "</table>";
			$html .= "</td></tr>";
		}
	}
	$html .= "</tbody>";
	$html .= "</table>";
	return $html;
}
function getPolicyValue($pd) {
	$html = '';
	if($pd->options == 'TEXT'
	|| $pd->options == 'TEXT-MULTILINE'
	|| substr($pd->options, 0, 3) == 'INT') {
		return htmlspecialchars($pd->value??'',ENT_QUOTES);
	} elseif($pd->options == 'DICT' || $pd->options == 'LIST') {
		return Html::dictTable(json_decode($pd->value, true), [], true);
	} elseif($options = json_decode($pd->options)) {
		foreach($options as $option => $value) {
			if($pd->value!==null && $value==$pd->value)
				return LANG(translatePolicy($option));
		}
	}
}
?>

<h2>Computer</h2>
<?php $content = getContents(null, Models\PolicyDefinition::CLASS_MACHINE); ?>
<?php if(empty($content)) { ?>
	<div class='alert info'><?php echo LANG('no_policies_defined'); ?></div>
<?php } else { ?>
	<ul class='tree machine'>
		<?php echo $content; ?>
	</ul>
<?php } ?>

<h2>Benutzer</h2>
<?php $content = getContents(null, Models\PolicyDefinition::CLASS_USER); ?>
<?php if(empty($content)) { ?>
	<div class='alert info'><?php echo LANG('no_policies_defined'); ?></div>
<?php } else { ?>
	<ul class='tree user'>
		<?php echo $content; ?>
	</ul>
<?php } ?>
