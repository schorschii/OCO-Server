<?php

function LANG($key) {
	return LanguageController::getMessageFromSingleton($key);
}

function startsWith( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}

function isIE() {
	return preg_match('~MSIE|Internet Explorer~i', $_SERVER['HTTP_USER_AGENT'])
		|| (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0; rv:11.0') !== false)
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Edge');
}

function prettyJson($json) {
	$parsed = json_decode($json);
	if($parsed === null) return $json;
	return json_encode($parsed, JSON_PRETTY_PRINT);
}

function niceSize($value, $useBinary=true, $round=1, $echoZeroIfEmpty=false) {
	if($value === 0 || ($echoZeroIfEmpty && empty($value))) return "0 B";
	if(empty($value)) return "";
	if($useBinary) {
		if($value < 1024) return $value . " B";
		else if($value < 1024*1024) return round($value / 1024, $round) . " KiB";
		else if($value < 1024*1024*1024) return round($value / 1024 / 1024, $round) . " MiB";
		else if($value < 1024*1024*1024*1024) return round($value / 1024 / 1024 /1024, $round) . " GiB";
		else return round($value / 1024 / 1024 / 1024 / 1024, $round) . " TiB";
	} else {
		if($value < 1000) return $value . " B";
		else if($value < 1000*1000) return round($value / 1000, $round) . " KB";
		else if($value < 1000*1000*1000) return round($value / 1000 / 1000, $round) . " MB";
		else if($value < 1000*1000*1000*1000) return round($value / 1000 / 1000 / 1000, $round) . " GB";
		else return round($value / 1000 / 1000 / 1000 / 1000, $round) . " TB";
	}
}

function niceTime($seconds) {
	if($seconds < 60)
		return sprintf('%d '.LANG('seconds'), $seconds);
	elseif($seconds < 60*60*24)
		return sprintf('%d '.LANG('hours').', %d '.LANG('minutes'), ($seconds/3600), (intval($seconds/60)%60));
	else return round($seconds/60/60/24).' '.LANG('days');
}

function wrapInSpanIfNotEmpty($text) {
	if($text == null || $text == '') return '';
	return '<span>'.htmlspecialchars($text).'</span>';
}

function progressBar($percent, $cid=null, $tid=null, $class=''/*hidden big stretch animated*/, $style='') {
	$percent = intval($percent);
	return
		'<span class="progressbar-container '.$class.'" style="--progress:'.$percent.'%; '.$style.'" '.($cid==null ? '' : 'id="'.htmlspecialchars($cid).'"').'>'
			.'<span class="progressbar"><span class="progress"></span></span>'
			.'<span class="progresstext" '.($tid==null ? '' : 'id="'.htmlspecialchars($tid).'"').'>'.(strpos($class,'animated')!==false ? LANG('in_progress') : $percent.'%').'</span>'
		.'</span>';
}

function explorerLink($explorerContentUrl, $extraJs=null) {
	$fileString = basename(parse_url($explorerContentUrl, PHP_URL_PATH), '.php');
	$parameterString = parse_url($explorerContentUrl, PHP_URL_QUERY);
	return "href='index.php?view=".urlencode($fileString)."&".$parameterString."'"
		.($extraJs===null ? "" : " onclick='event.preventDefault();".$extraJs."'");
}

function randomString($length = 30) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function shorter($text, $charsLimit=40, $dots=true) {
	if(strlen($text) > $charsLimit) {
		$new_text = substr($text, 0, $charsLimit);
		$new_text = trim($new_text);
		return $new_text . ($dots ? "..." : "");
	} else {
		return $text;
	}
}

function localTimeToUtc($dateTimeString) {
	$date = new DateTime($dateTimeString, new DateTimeZone(date_default_timezone_get()));
	$date->setTimezone(new DateTimeZone('UTC'));
	return $date->format('Y-m-d H:i:s');
}
function utcTimeToLocal($utcTimeString) {
	$date = new DateTime($utcTimeString, new DateTimeZone('UTC'));
	$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
	return $date->format('Y-m-d H:i:s');
}

// converts IPv4 or v6 address to string with bits
function ipAddressToBits($inet) {
	$inet = inet_pton($inet);
	if($inet === false) return false;
	$splitted = str_split($inet);
	$binaryip = '';
	foreach($splitted as $char) {
		$binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
	}
	return $binaryip;
}
function isIpInRange($ip, $range) {
	// $range is in IP/CIDR format e.g. 127.0.0.1/24 or CAFF:EECA:FFEE:0000::/64
	if(strpos( $range, '/' ) == false) $range .= '/32'; // fallback range
	list( $range, $maskBits ) = explode( '/', $range, 2 );
	$binaryNet = ipAddressToBits($range);
	$binaryIp  = ipAddressToBits($ip);
	$ipNetBits = substr($binaryIp, 0, $maskBits);
	$netBits   = substr($binaryNet, 0, $maskBits);
	if($binaryIp === false || $binaryNet === false) {
		throw new Exception(LANG('invalid_ip_address'));
	}
	return ($ipNetBits === $netBits);
}

// time range operations
function isTimeInRange($strRange, $time=null) {
	if(!$time) $time = time();

	$strRange = strtoupper($strRange);
	if(startsWith($strRange, 'MON')
	|| startsWith($strRange, 'TUE')
	|| startsWith($strRange, 'WED')
	|| startsWith($strRange, 'THU')
	|| startsWith($strRange, 'FRI')
	|| startsWith($strRange, 'SAT')
	|| startsWith($strRange, 'SUN')) {
		if(substr($strRange, 0, 3) != strtoupper(date('D', $time))) {
			return false;
		}
		$strRange = trim(substr($strRange, 3));
	}

	$parts = explode('-', $strRange);
	if(count($parts) != 2) throw new Exception(LANG('invalid_time_input'));
	$currentDateTime = DateTime::createFromFormat('H:i', date('H:i', $time));
	$startDateTime = DateTime::createFromFormat('H:i', $parts[0]);
	$endDateTime = DateTime::createFromFormat('H:i', $parts[1]);
	if(!$startDateTime || !$endDateTime) throw new Exception(LANG('invalid_time_input'));
	return ($currentDateTime > $startDateTime && $currentDateTime < $endDateTime);
}

function echoComputerGroupOptions(CoreLogic $cl, $parentId=null, $indent=0, $preselect=-1) {
	foreach($cl->getComputerGroups($parentId) as $g) {
		echo "<option ".($preselect==$g->id ? "selected" : "")." value='".$g->id."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->name))."</option>";
		echoComputerGroupOptions($cl, $g->id, $indent+1, $preselect);
	}
}
function echoPackageGroupOptions(CoreLogic $cl, $parentId=null, $indent=0, $preselect=-1) {
	foreach($cl->getPackageGroups($parentId) as $g) {
		echo "<option ".($preselect==$g->id ? "selected" : "")." value='".$g->id."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->name))."</option>";
		echoPackageGroupOptions($cl, $g->id, $indent+1, $preselect);
	}
}
function echoReportGroupOptions(CoreLogic $cl, $parentId=null, $indent=0, $preselect=-1) {
	foreach($cl->getReportGroups($parentId) as $g) {
		echo "<option ".($preselect==$g->id ? "selected" : "")." value='".$g->id."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->name))."</option>";
		echoReportGroupOptions($cl, $g->id, $indent+1, $preselect);
	}
}

function echoCommandButton($c, $target, $link=false) {
	if(empty($c) || !isset($c['command']) || !isset($c['name'])) return;
	$actionUrl = str_replace('$$TARGET$$', $target, $c['command']);
	$description = LANG($c['description']);
	if($c['new_tab']) {
		if($link) {
			echo "<a title='".htmlspecialchars($description)."' href='".htmlspecialchars($actionUrl)."' target='_blank'>";
			echo htmlspecialchars($c['name']);
			echo "</a>";
		} else {
			echo "<button title='".htmlspecialchars($description)."' onclick='window.open(\"".htmlspecialchars($actionUrl)."\")'>";
			if(!empty($c['icon'])) echo "<img src='".$c['icon']."'>&nbsp;"; echo htmlspecialchars($c['name']);
			echo "</button>";
		}
	} else {
		if($link) {
			echo "<a title='".htmlspecialchars($description)."' href='".htmlspecialchars($actionUrl)."'>";
			echo htmlspecialchars($c['name']);
			echo "</a>";
		} else {
			echo "<button title='".htmlspecialchars($description)."' onclick='window.location=\"".htmlspecialchars($actionUrl)."\"'>";
			if(!empty($c['icon'])) echo "<img src='".$c['icon']."'>&nbsp;"; echo htmlspecialchars($c['name']);
			echo "</button>";
		}
	}
}
