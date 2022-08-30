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
		return sprintf('%d '.LANG('hours').', %d '.LANG('minutes'), ($seconds/3600), ($seconds/60%60));
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

function explorerLink($explorerContentUrl, $extraJs='') {
	$fileString = basename(parse_url($explorerContentUrl, PHP_URL_PATH), '.php');
	$parameterString = parse_url($explorerContentUrl, PHP_URL_QUERY);
	return "href='index.php?view=".urlencode($fileString)."&".$parameterString."'"
		." onclick='event.preventDefault();".$extraJs.";refreshContentExplorer(\"".$explorerContentUrl."\")'";
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

function isIpInRange($ip, $range) {
	if(strpos( $range, '/' ) == false) {
		$range .= '/32';
	}
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list( $range, $netmask ) = explode( '/', $range, 2 );
	$range_decimal = ip2long( $range );
	$ip_decimal = ip2long( $ip );
	if($range_decimal === false || $ip_decimal === false) {
		throw new Exception(LANG('invalid_ip_address'));
	}
	$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

function GUIDtoStr($binary_guid) {
	$unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
	if(!$unpacked) {
		// fallback string representation (base64) if we got unexpected input
		return base64_encode($binary_guid);
	}
	return sprintf('%08x-%04x-%04x-%04x-%04x%08x', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
}

function echoComputerGroupOptions($db, $parent=null, $indent=0) {
	foreach($db->getAllComputerGroup($parent) as $g) {
		echo "<option value='".$g->id."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->name))."</option>";
		echoComputerGroupOptions($db, $g->id, $indent+1);
	}
}
function echoPackageGroupOptions($db, $parent=null, $indent=0) {
	foreach($db->getAllPackageGroup($parent) as $g) {
		echo "<option value='".$g->id."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->name))."</option>";
		echoPackageGroupOptions($db, $g->id, $indent+1);
	}
}
function echoReportGroupOptions($db, $parent=null, $indent=0) {
	foreach($db->getAllReportGroup($parent) as $g) {
		echo "<option value='".$g->id."'>".trim(str_repeat("‒",$indent)." ".htmlspecialchars($g->name))."</option>";
		echoReportGroupOptions($db, $g->id, $indent+1);
	}
}
