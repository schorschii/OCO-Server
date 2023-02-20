<?php

class WakeOnLan {

	private $db;

	function __construct(DatabaseController $db) {
		$this->db = $db;
	}

	public function wol($macs, $debugOutput=true) {
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

		$satelliteWolServer = json_decode($this->db->settings->get('wol-satellites'), true);
		if(is_array($satelliteWolServer)) foreach($satelliteWolServer as $server) {
			if(empty($server['address']) || empty($server['port']) || empty($server['username'])) continue;

			$originalConnectionTimeout = ini_get('default_socket_timeout');
			ini_set('default_socket_timeout', 4);
			$c = @ssh2_connect($server['address'], $server['port']);
			ini_set('default_socket_timeout', $originalConnectionTimeout);
			if(!$c) {
				error_log('SSH Connection to '.$server['address'].' failed');
				continue;
			}
			$a = @ssh2_auth_pubkey_file($c, $server['username'], $server['pubkey'], $server['privkey']);
			if(!$a) {
				error_log('SSH Authentication with '.$server['username'].'@'.$server['address'].' failed');
				continue;
			}
			$program = 'wakeonlan';
			if(!empty($server['COMMAND'])) $program = $server['COMMAND'];
			$chunkCount = 1;
			// ssh2_exec has a bug which throws "ssh2_exec(): Unable to request command execution on remote host" if the command is longer than 32KB, so we send our MACs in chunks of 1500 MACs which is ~30KB...
			foreach(array_chunk($escapedMacs, 1500) as $escapedMacChunk) {
				$cmd = $program.' '.implode(' ', $escapedMacChunk);
				$stdioStream = ssh2_exec($c, $cmd);
				if($debugOutput) echo "Satellite WOL ".$server['username']."@".$server['address'].": ".$cmd."\n";
				stream_set_blocking($stdioStream, true);
				$cmdOutput = stream_get_contents($stdioStream);
				if($debugOutput) echo "Chunk ".$chunkCount." -> ".$cmdOutput."\n";
				$chunkCount ++;
			}
		}
	
		socket_close($s);
	}

}
