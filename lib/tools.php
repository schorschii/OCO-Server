<?php

function isIE() {
	return preg_match('~MSIE|Internet Explorer~i', $_SERVER['HTTP_USER_AGENT'])
		|| (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0; rv:11.0') !== false)
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Edge');
}

function niceSize($value, $useBinary=true, $round=1) {
	if($value === 0) {
		return "0 B";
	}
	if(empty($value)) {
		return "";
	}
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

function wrapInSpanIfNotEmpty($text) {
	if($text == null || $text == '') return '';
	return '<span>'.htmlspecialchars($text).'</span>';
}

function progressBar($percent, $id=null, $cid=null, $tid=null, $style=null, $stretch=false) {
	$percent = intval($percent);
	return
		'<span class="progressbar-container '.($stretch ? 'stretch' : '').'" style="'.(empty($style) ? '' : $style).'" '.($cid==null ? '' : 'id="'.htmlspecialchars($cid).'"').'>'
			.'<span class="progressbar">'
				.'<span class="progress" style="width:'.$percent.'%" '.($id==null ? '' : 'id="'.htmlspecialchars($id).'"').'></span>'
			.'</span>'
			.'<span class="progresstext" '.($tid==null ? '' : 'id="'.htmlspecialchars($tid).'"').'>'.$percent.'%</span>'
		.'</span>';
}

function explorerLink($explorerContent) {
	return 'index.php?explorer-content='.urlencode($explorerContent);
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

function wol($macs, $debugOutput=true) {
	// create socket for sending local WOL packets
	$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if(!$s) throw new Exception("Error creating socket! '".socket_last_error($s)."' - " . socket_strerror(socket_last_error($s)));

	$escapedMacs = [];
	foreach($macs as $mac) {
		// check validity
		if(!filter_var($mac, FILTER_VALIDATE_MAC)) continue;

		// create magic packet
		$mac = str_replace('-', '', $mac);
		$escapedMacs[] = escapeshellarg($mac);
		$addr_byte = explode(':', $mac);
		$hw_addr = '';
		for($a=0; $a < 6; $a++) $hw_addr .= chr(hexdec($addr_byte[$a]));
		$packetPayload = str_repeat(chr(0xff), 6).str_repeat($hw_addr, 16);

		// setting a broadcast option to socket
		$opt_ret = socket_set_option($s, 1, 6, TRUE);
		if($opt_ret < 0) {
			throw new Exception("setsockopt() failed, error: " . strerror($opt_ret) . "\n");
		}
		// send magic packet to broadcast on all interfaces
		$interfaceAddresses = ['0.0.0.0']; // fallback address - sends packet on first interface
		$cmdOutLines = '';
		exec("ip -o a | grep -Ev '127.0.0.1|::1' | awk {'print $5 \" \" $6'}", $cmdOutLines);
		foreach($cmdOutLines as $line) {
			$fields = explode(' ', $line);
			if($fields[0] == 'brd') $interfaceAddresses[] = $fields[1];
		}
		foreach($interfaceAddresses as $addr) {
			$e = socket_sendto($s, $packetPayload, strlen($packetPayload), 0, $addr, 9);
			if($debugOutput) echo "WOL Magic Packet sent (".$e."), IP=".$addr.", MAC=".$mac."\n";
		}
	}

	foreach(SATELLITE_WOL_SERVER as $server) {
		$originalConnectionTimeout = ini_get('default_socket_timeout');
		ini_set('default_socket_timeout', 4);
		$c = @ssh2_connect($server['ADDRESS'], $server['PORT']);
		ini_set('default_socket_timeout', $originalConnectionTimeout);
		if(!$c) {
			error_log('SSH Connection to '.$server['ADDRESS'].' failed');
			continue;
		}
		$a = @ssh2_auth_pubkey_file($c, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
		if(!$a) {
			error_log('SSH Authentication with '.$server['USER'].'@'.$server['ADDRESS'].' failed');
			continue;
		}
		$program = 'wakeonlan';
		if(!empty($server['COMMAND'])) $program = $server['COMMAND'];
		$cmd = $program.' '.implode(' ', $escapedMacs);
		$stdioStream = ssh2_exec($c, $cmd);
		if($debugOutput) echo "Satellite WOL ".$server['USER']."@".$server['ADDRESS'].": ".$cmd."\n";
		stream_set_blocking($stdioStream, true);
		$cmdOutput = stream_get_contents($stdioStream);
		if($debugOutput) echo "-> ".$cmdOutput."\n";
	}

	socket_close($s);
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

function getLocaleNameByLcid($lcid) {
	if(empty($lcid) || $lcid == '-' || $lcid == '?') return $lcid;
	$lcidDec = intval(hexdec($lcid));
	if(($handle = fopen(__DIR__.'/lcid.csv', 'r')) !== false) {
		while(($data = fgetcsv($handle, 1000, ',')) !== false) {
			if(intval($data[3]) == $lcidDec) {
				return $data[0];
			}
		}
		fclose($handle);
	}
	return $lcid;
}
