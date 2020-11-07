<?php

function bytesToGb($bytes) {
	if(empty($bytes)) return '';
	return round($bytes/1024/1024/1014).'&nbsp;GiB';
}

function wol($mac) {
	// create magic packet
	$mac = str_replace('-', '', $mac);
	$addr_byte = explode(':', $mac);
	$hw_addr = '';
	for($a=0; $a < 6; $a++) $hw_addr .= chr(hexdec($addr_byte[$a]));
	$msg = str_repeat(chr(0xff), 6).str_repeat($hw_addr, 16);

	$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if(!$s) {
		echo "Error creating socket! '".socket_last_error($s)."' - " . socket_strerror(socket_last_error($s));
	} else {
		// setting a broadcast option to socket
		$opt_ret = socket_set_option($s, 1, 6, TRUE);
		if($opt_ret < 0) {
			echo "setsockopt() failed, error: " . strerror($opt_ret) . "\n";
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
			$e = socket_sendto($s, $msg, strlen($msg), 0, $addr, 9);
			echo "WOL Magic Packet sent (".$e."), IP=".$addr.", MAC=".$mac."\n";
		}
	}
	socket_close($s);
}
