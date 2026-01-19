<?php
if(php_sapi_name() != 'cli')
	die('This script must be executed from command line.'."\n");

if(!isset($argv[1]))
	die('Please specify a dir path with .admx files and translations as first parameter.'."\n");

require_once(__DIR__.'/../loader.inc.php');

$policyDefintionGroups = $db->selectAllPolicyDefinitionGroup();
$policyDefintions = $db->selectAllPolicyDefinition();

// parse all .admx files in the given dir
$path = $argv[1];
$files = scandir($path);

// 1st run: import all categories (they are referenced across .admx files internally)
echo "\n".'=== Processing categories'."\n";
$fileCategories = [];
foreach($files as $admxFile) {
	if(!is_file($path.'/'.$admxFile) || substr($admxFile, -5) != '.admx') continue;
	$admxFileName = substr($admxFile, 0, -5);

	echo $admxFile.' - ';
	$admx = simplexml_load_file($path.'/'.$admxFile);
	if(!$admx) {
		echo 'ERROR: unable to parse!'."\n";
		continue;
	}

	$tmpCount = 0;
	if($admx->categories) foreach($admx->categories->category as $c) {
		$parent = $c->parentCategory ? strval($c->parentCategory->attributes()->ref) : null;
		if($parent && strpos($parent, ':') !== false)
			$parent = explode(':', $parent)[1];

		$catName = strval($c->attributes()->name);
		$catDisplayName = empty($c->attributes()->displayName)
			? $admxFileName.'|'.$catName
			: $admxFileName.'|'.stringKeyExtract(strval($c->attributes()->displayName));

		// avoid endless loops, e.g. in CredentialProviders.admx (logon:Logon), grouppolicy-server.admx (grouppolicy:PolicyPolicies)
		if($catName == $parent) continue;
		$fileCategories[$catName] = [$parent, $catDisplayName];
		$tmpCount ++;
	}
	echo $tmpCount.' categories'."\n";
}


// 2nd run: import translations, presentations and policy templates
$presentations = [];
$foundPresentationStrings = [];
foreach($files as $admxFile) {
	if(!is_file($path.'/'.$admxFile) || substr($admxFile, -5) != '.admx') continue;
	$admxFileName = substr($admxFile, 0, -5);

	echo "\n".'=== Processing: '.$admxFile."\n";
	$admx = simplexml_load_file($path.'/'.$admxFile);
	if(!$admx) {
		echo 'ERROR: unable to parse '.$admxFile."\n";
		continue;
	}

	// parse all .adml translation files with the same base name
	$transCounter = 0;
	foreach($files as $dir) {
		$admlFile = $path.'/'.$dir.'/'.$admxFileName.'.adml';
		if(!is_dir($path.'/'.$dir) || !file_exists($admlFile)) continue;
		if(strlen($dir) != 5) {
			echo 'ERROR: skipping invalid translation dir '.$dir."\n";
			continue;
		}
		$adml = simplexml_load_file($admlFile);
		if(!$adml) {
			echo 'ERROR: unable to parse translation file '.$admlFile."\n";
			continue;
		}

		if(property_exists($adml, 'resources')
		&& property_exists($adml->resources, 'stringTable')) {
			foreach($adml->resources->stringTable->string as $s) {
				$stringName = $admxFileName.'|'.strval($s->attributes()->id);
				$db->replacePolicyTranslation(strtolower($dir), $stringName, strval($s));
				$transCounter ++;
			}
		}

		if(property_exists($adml, 'resources')
		&& property_exists($adml->resources, 'presentationTable')) {
			foreach($adml->resources->presentationTable->presentation as $p) {
				foreach($p->children() as $child) {
					if(empty($child->attributes()->refId)) continue;
					if(!empty($child->label)) $text = strval($child->label);
					else $text = strval($child);
					if(!empty($text)) {
						$presentationStringName = $admxFileName.'|P|'.strval($child->attributes()->refId);
						$foundPresentationStrings[] = $presentationStringName;
						$db->replacePolicyTranslation(strtolower($dir), $presentationStringName, $text);
						$transCounter ++;
					}
				}
			}
		}
	}
	echo 'Stored '.$transCounter.' translations'."\n";

	// create policy definitions
	if(empty($admx->policies->policy)) {
		echo 'WARN: no policies found in '.$admxFile."\n";
		continue;
	}
	foreach($admx->policies->policy as $p) {
		$groupName = strval($p->parentCategory->attributes()->ref);
		$name = strval($p->attributes()->name);
		$displayName = stringKeyExtract(strval($p->attributes()->displayName));
		$description = stringKeyExtract(strval($p->attributes()->explainText));
		$key = strval($p->attributes()->key);
		$policyValueName = strval($p->attributes()->valueName);

		if($groupName && strpos($groupName, ':') !== false)
			$groupName = explode(':', $groupName)[1];

		// determine class (1=Machine, 2=User, 3=Both)
		$class = 0;
		if(property_exists($p->attributes(), 'class')) {
			if(strval($p->attributes()->class) == 'Machine')
				$class = Models\PolicyDefinition::CLASS_MACHINE;
			if(strval($p->attributes()->class) == 'User')
				$class = Models\PolicyDefinition::CLASS_USER;
			if(strval($p->attributes()->class) == 'Both')
				$class = Models\PolicyDefinition::CLASS_BOTH;
		}
		if(!$class) {
			echo 'ERROR: no class found for '.$name."\n";
			continue;
		}

		// determine group id (maybe create a new one)
		$groupDisplayName = empty($fileCategories[$groupName][1]) ? null : $fileCategories[$groupName][1];
		$groupId = getGroupId($groupName, $groupDisplayName);
		if(!$groupId) {
			echo 'ERROR: no group found for '.$groupName.', skipping '.$name."\n";
			continue;
		}

		// check if a policy with same name already exists
		foreach($policyDefintions as $pd) {
			if($pd->policy_definition_group_id == $groupId
			&& $pd->name == $name) {
				echo 'INFO: skipping existing '.$name."\n";
				continue 2;
			}
		}

		// determine value type and its options if applicable
		list($manifestation1, $manifestation2) = optionsExtract($p, $admxFileName);
		if(empty($manifestation1) && empty($manifestation2)) {
			echo 'WARN: no options found for '.$name."\n";
			continue;
		}
		// translate into OCO manifestation
		$manifestations = [];
		$options = null;
		$info = '';
		foreach($manifestation1 as $pm) {
			list($options, $optionKey, $optionValueName) = $pm;
			if($options === 'DICT' || $options === 'LIST') {
				$manifestations[] = 'REGISTRY:'.($optionKey ? $optionKey : $key);
			} else {
				if(!empty($policyValueName)) {
					$manifestations[] = 'REGISTRY:'.($optionKey ? $optionKey : $key).':'.$policyValueName;
				} elseif(!empty($optionValueName)) { // theoretically, this case does not exist in practice
					$manifestations[] = 'REGISTRY:'.($optionKey ? $optionKey : $key).':'.$optionValueName;
				} else {
					// this is legit for policies which only have sub items
					$info = ' (INFO: no valueName found)';
					#echo 'WARN: no valueName found for '.$name.' '.($optionKey ? $optionKey : $key)."\n";
					#continue 2;
				}
			}
		}
		// insert manifestation
		$insertId = null;
		$insertId = $db->insertPolicyDefinition(
			$groupId, null,
			$name, $admxFileName.'|'.$displayName, $admxFileName.'|'.$description,
			$class, empty($manifestations) ? '' : ($options ?? ''),
			null, null, empty($manifestations) ? null : implode("\n", $manifestations)
		);
		echo 'OK  : created '.$name.$info."\n";

		// compile secondary manifestations
		foreach($manifestation2 as $sm) {
			$options = null;
			list($options, $optionKey, $optionValueName, $optionId) = $sm;
			$manifestations = [];
			if($options === 'DICT' || $options === 'LIST') {
				$manifestations[] = 'REGISTRY:'.($optionKey ? $optionKey : $key);
			} else {
				if(!empty($policyValueName)) { // inherit from parent <policy>
					$manifestations[] = 'REGISTRY:'.($optionKey ? $optionKey : $key).':'.$policyValueName;
				} elseif(!empty($optionValueName)) { // use from sub item
					$manifestations[] = 'REGISTRY:'.($optionKey ? $optionKey : $key).':'.$optionValueName;
				} else {
					echo '-- WARN: no valueName found for '.$name.' '.$optionId."\n";
					continue;
				}
			}
			// insert manifestation
			$displayName = ''; // sub items may have no description
			$potentialDisplayName = $admxFileName.'|P|'.$optionId;
			if(in_array($potentialDisplayName, $foundPresentationStrings))
				$displayName = $potentialDisplayName;
			$db->insertPolicyDefinition(
				$groupId, $insertId,
				$name.'-'.$optionId, $displayName, '',
				$class, empty($manifestations) ? '' : ($options ?? ''),
				null, null, empty($manifestations) ? null : implode("\n", $manifestations)
			);
			echo 'OK  : created '.$name.' '.$optionId."\n";
		}
	}
}


function getGroupId($groupName, $groupDisplayName) {
	global $db, $policyDefintionGroups, $fileCategories, $admxFileName;

	if($groupName && strpos($groupName, ':') !== false)
		$groupName = explode(':', $groupName)[1];

	$groupId = null;
	foreach($policyDefintionGroups as $pdg) {
		if($pdg->name == $groupName) {
			$groupId = $pdg->id;
			break;
		}
	}
	if(!$groupId) {
		$parent = null;
		foreach($fileCategories as $catName => list($catParentName, $catDisplayName)) {
			if($catName != $groupName) continue;
			if(!$catParentName) {
				$groupId = $db->insertPolicyDefinitionGroup(null, $groupName, $groupDisplayName ? $groupDisplayName : $groupName);
			} else {
				$catParentDisplayName = empty($fileCategories[$catParentName][1]) ? $catParentName : $fileCategories[$catParentName][1];
				$parentGroupId = getGroupId($catParentName, $catParentDisplayName);
				$groupId = $db->insertPolicyDefinitionGroup($parentGroupId, $groupName, $groupDisplayName ? $groupDisplayName : $groupName);
			}
			$policyDefintionGroups = $db->selectAllPolicyDefinitionGroup();
			break;
		}
	}
	return $groupId;
}
function stringKeyExtract($stringVar) {
	// extract "stringKey" from something like:
	// $(strings.stringKey)
	preg_match('/\$\(.*\.(.+?)\)/', $stringVar, $matches);
	if(!empty($matches[1])) return $matches[1];
	return $stringVar; // fallback
}
function optionsExtract($xmlElement, $admxFileName) {
	$manifestation1 = [];
	$manifestation2 = [];
	if(property_exists($xmlElement, 'enabledValue')
	|| property_exists($xmlElement, 'disabledValue')) {
		$options = [];
		if(property_exists($xmlElement, 'enabledValue'))
			$options['enabled'] = optionValueExtract($xmlElement->enabledValue);
		if(property_exists($xmlElement, 'disabledValue'))
			$options['disabled'] = optionValueExtract($xmlElement->disabledValue);
		$manifestation1[] = [json_encode($options), null, null];
	} elseif(property_exists($xmlElement, 'enabledList')) {
		foreach($xmlElement->enabledList->item as $item) {
			if(!property_exists($item, 'value')) continue;
			if(property_exists($item->value, 'decimal')) {
				// get corresponding disabledValue if exists
				$disabledValue = 0;
				if(property_exists($xmlElement, 'disabledList'))
					foreach($xmlElement->disabledList->item as $item2) {
						if(strval($item->attributes()->key) === strval($item2->attributes()->key)
						&& strval($item->attributes()->valueName) === strval($item2->attributes()->valueName)) {
							if(property_exists($item2, 'value') && property_exists($item2->value, 'decimal'))
								$disabledValue = intval($item2->value->decimal->attributes()->value);
							elseif(property_exists($item2->value, 'delete'))
								$disabledValue = null;
						}
					}
				$manifestation1[] = [
					json_encode(['enabled' => intval($item->value->decimal->attributes()->value), 'disabled' => $disabledValue]),
					strval($item->attributes()->key),
					strval($item->attributes()->valueName),
				];
			} elseif(property_exists($item->value, 'string')) {
				// get corresponding disabledValue if exists
				$disabledValue = 0;
				if(property_exists($xmlElement, 'disabledList'))
					foreach($xmlElement->disabledList->item as $item2) {
						if(strval($item->attributes()->key) === strval($item2->attributes()->key)
						&& strval($item->attributes()->valueName) === strval($item2->attributes()->valueName)) {
							if(property_exists($item2, 'value') && property_exists($item2->value, 'string'))
								$disabledValue = strval($item2->value->string);
							elseif(property_exists($item2->value, 'delete'))
								$disabledValue = null;
						}
					}
				$manifestation1[] = [
					json_encode(['enabled' => strval($item->value->string), 'disabled' => $disabledValue]),
					strval($item->attributes()->key),
					strval($item->attributes()->valueName),
				];
			}
		}
	} else { // fallback
		$manifestation1[] = [json_encode(['enabled' => 1, 'disabled' => 0]), null, null];
	}
	if(property_exists($xmlElement, 'elements')) {
		foreach($xmlElement->elements->children() as $element) {
			if($element->getName() == 'enum') {
				$options = [];
				foreach($element->item as $item) {
					if(property_exists($item, 'value')) {
						$stringKey = stringKeyExtract($item->attributes()->displayName);
						$options[$admxFileName.'|'.$stringKey] = optionValueExtract($item->value);
					}
				}
				$manifestation2[] = [
					json_encode($options),
					property_exists($element->attributes(), 'key') ? strval($element->attributes()->key) : null,
					strval($element->attributes()->valueName),
					strval($element->attributes()->id),
				];
			}
			elseif($element->getName() == 'list') {
				$type = 'LIST';
				if(property_exists($element->attributes(), 'explicitValue')
				&& strtolower($element->attributes()->explicitValue) === 'true')
					$type = 'DICT';
				$manifestation2[] = [
					$type,
					property_exists($element->attributes(), 'key') ? strval($element->attributes()->key) : null,
					false, // dicts/lists do not have a valueName since multiple values are created based on user input
					strval($element->attributes()->id),
				];
			}
			elseif($element->getName() == 'text') {
				$manifestation2[] = [
					'TEXT',
					property_exists($element->attributes(), 'key') ? strval($element->attributes()->key) : null,
					strval($element->attributes()->valueName),
					strval($element->attributes()->id),
				];
			}
			elseif($element->getName() == 'multiText') {
				$manifestation2[] = [
					'TEXT-MULTILINE',
					property_exists($element->attributes(), 'key') ? strval($element->attributes()->key) : null,
					strval($element->attributes()->valueName),
					strval($element->attributes()->id),
				];
			}
			elseif($element->getName() == 'decimal') {
				$min = ''; $max = '';
				if($element->attributes()->minValue)
					$min = intval($element->attributes()->minValue);
				if($element->attributes()->maxValue)
					$max = intval($element->attributes()->maxValue);
				$manifestation2[] = [
					'INT:'.$min.':'.$max,
					property_exists($element->attributes(), 'key') ? strval($element->attributes()->key) : null,
					strval($element->attributes()->valueName),
					strval($element->attributes()->id),
				];
			}
			elseif($element->getName() == 'boolean') {
				$options = [
					'enabled' => optionValueExtract($xmlElement->trueValue), // ???
					'disabled' => optionValueExtract($xmlElement->falseValue),
				];
				$manifestation2[] = [
					json_encode($options),
					property_exists($element->attributes(), 'key') ? strval($element->attributes()->key) : null,
					strval($element->attributes()->valueName),
					strval($element->attributes()->id),
				];
			}
		}
	}
	return [$manifestation1, $manifestation2];
}
function optionValueExtract($xmlElement) {
	if(property_exists($xmlElement, 'decimal'))
		return (int) $xmlElement->decimal->attributes()->value[0];
	elseif(property_exists($xmlElement, 'string'))
		return (string) $xmlElement->string;
}
