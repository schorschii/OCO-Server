<?php

class Html {

	static function wrapInSpanIfNotEmpty($text) {
		if($text == null || $text == '') return '';
		return '<span>'.htmlspecialchars($text).'</span>';
	}

	static function explorerLink($explorerContentUrl, $extraJs=null) {
		$fileString = basename(parse_url($explorerContentUrl, PHP_URL_PATH), '.php');
		$parameterString = parse_url($explorerContentUrl, PHP_URL_QUERY);
		return "href='index.php?view=".urlencode($fileString)."&".$parameterString."'"
			.($extraJs===null ? "" : " onclick='event.preventDefault();".$extraJs."'");
	}

	static function buildGroupOptions(CoreLogic $cl, Models\HierarchicalGroup $hierarchicalGroup, $indent=0, $preselect=-1) {
		$groups = call_user_func([$cl, $hierarchicalGroup::GET_OBJECTS_FUNCTION], $hierarchicalGroup->getId());
		foreach($groups as $g) {
			echo "<option ".($preselect==$g->getId() ? "selected" : "")." value='".$g->getId()."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->getName()))."</option>";
			self::buildGroupOptions($cl, $g, $indent+1, $preselect);
		}
	}

	static function progressBar($percent, $cid=null, $tid=null, $class=''/*hidden big stretch animated*/, $style='', $text=null) {
		$percent = intval($percent);
		return
			'<span class="progressbar-container '.$class.'" style="--progress:'.$percent.'%; '.$style.'" '.($cid==null ? '' : 'id="'.htmlspecialchars($cid).'"').'>'
				.'<span class="progressbar"><span class="progress"></span></span>'
				.'<span class="progresstext" '.($tid==null ? '' : 'id="'.htmlspecialchars($tid).'"').'>'.(
					$text ? htmlspecialchars($text) : (strpos($class,'animated')!==false ? LANG('in_progress') : $percent.'%')
				).'</span>'
			.'</span>';
	}

	static function dictTable($value, array $exclude=[], $return=false) {
		$html = '';
		if($value === true) $html .= '<img title="'.LANG('yes').'" src="img/success.dyn.svg">';
		elseif($value === false) $html .= '<img title="'.LANG('no').'" src="img/close.opacity.svg">';
		elseif(is_array($value)) {
			$html .= '<table class="list metadata"><tbody>';
			foreach($value as $subkey => $subvalue) {
				if(in_array($subkey, $exclude)) continue;
				$html .= '<tr>'
					.'<th>'.htmlspecialchars(LANG($subkey)).'</th>'
					.'<td>';
				$html .= self::dictTable($subvalue, $exclude, true);
				$html .= '</td>'
					.'</tr>';
			}
			$html .= '</tbody></table>';
		}
		else $html .= htmlspecialchars($value);
		if($return) return $html;
		else echo $html;
	}

	static function commandButton($c, $target, $link=false) {
		if(empty($c) || !isset($c['command']) || !isset($c['name'])) return;
		if(startsWith($c['command'], 'rdp://') && strpos($_SERVER['HTTP_USER_AGENT']??'', 'Mac') !== false) {
			// for macOS "Windows App", see https://learn.microsoft.com/en-us/windows-server/remote/remote-desktop-services/clients/remote-desktop-uri#legacy-rdp-uri-scheme
			$actionUrl = str_replace('$$TARGET$$', http_build_query(['full address'=>'s:'.$target]), $c['command']);
		} else {
			$actionUrl = str_replace('$$TARGET$$', $target, $c['command']);
		}
		$description = LANG($c['description']);
		if($link) {
			echo "<a title='".htmlspecialchars($description,ENT_QUOTES)."' href='".htmlspecialchars($actionUrl,ENT_QUOTES)."' ".($c['new_tab'] ? "target='_blank'" : "").">"
				. htmlspecialchars($c['name'])
				. "</a>";
		} else {
			if($c['new_tab'])
				$onclick = "window.open(\"".htmlspecialchars($actionUrl,ENT_QUOTES)."\")";
			else
				$onclick = "window.location=\"".htmlspecialchars($actionUrl,ENT_QUOTES)."\"";
			echo "<button title='".htmlspecialchars($description,ENT_QUOTES)."' onclick='".$onclick."'>"
				. (empty($c['icon']) ? "" : "<img src='".$c['icon']."'>&nbsp;")
				. htmlspecialchars($c['name'])
				. "</button>";
		}
	}

}
