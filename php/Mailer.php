<?php
// PEAR mail libs
require('Mail.php');
require('Mail/mime.php');

class Mailer {

	/*
	===== Beispielaufruf =====

	mailAtt(
	    "receiver@example.com", "Ihr Betreff hier!", "Hier könnte Ihr Text stehen!",
	    "Sender Name", "sender@example.com", "reply-to@example.com",

	    // array with attachments
	    [ '/path/to/file.txt' => 'filename-in-email.txt' ],

	    // options for signature, NULL for no signature
	    [ 'cert' => 'file://cert.pem', 'key' => ['file://key.pem', 'PASSPHRASE'], 'chain' => 'chain.pem' ]
	);
	*/

	static function send($to, $subject, $message, $sender_name, $sender_email, $reply_email, $files=[]) {
		$crlf = "\r\n";
		$mime = new Mail_mime($crlf);
		$mime->setTXTBody($message);
		foreach($files as $key => $val) {
			if(is_int($key)) {
				$datei = $val;
				$name = basename($datei);
			} else {
				$datei = $key;
				$name = basename($val);
			}
			$mime->addAttachment($datei, mime_content_type($datei), $name, true);
		}

		$backend = 'sendmail';
		$mail =& Mail::factory($backend);
		$mail->send($to, $mime->headers([
			'From' => '"'.addslashes($sender_name).'" <'.$sender_email.'>',
			'Reply-To' => $reply_email,
			'Subject' => '=?UTF-8?B?'.base64_encode($subject).'?=',
		]), $mime->get([
			'text_encoding' => '7bit',
			'text_charset'  => 'UTF-8',
			'html_charset'  => 'UTF-8',
			'head_charset'  => 'UTF-8',
		]));
	}

	static function send_old($to, $subject, $message, $sender, $sender_email, $reply_email, $files, $signopts=null, $content_type='text/plain') {
		if(!is_array($files)) {
			$files = array($files);
		}
	
		// prepare attachments
		$attachments = array();
		foreach($files AS $key => $val) {
			if(is_int($key)) {
				$datei = $val;
				$name = basename($datei);
			} else {
				$datei = $key;
				$name = basename($val);
			}
			$size = filesize($datei);
			$data = file_get_contents($datei);
			$type = mime_content_type($datei);
			$attachments[] = array("name"=>$name, "size"=>$size, "type"=>$type, "data"=>$data);
		}
	
		// generate mime boundary to separate attachments in mail body
		$mime_boundary = "-----=" . md5(uniqid(microtime(), true));
		$encoding = mb_detect_encoding($message, "auto"); #UTF-8,ISO-8859-1,CP-1252
	
		// compile mail headers
		$header1a = 'From: "'.addslashes($sender).'" <'.$sender_email.">\r\n";
		$header1b = "Reply-To: ".$reply_email."\r\n";
	
		$header2  = "MIME-Version: 1.0\r\n";
		$header2 .= "Content-Type: multipart/mixed; charset=\"$encoding\"; boundary=\"".$mime_boundary."\"\r\n";
	
		// compile mail body
		$content  = "This is a multi-part message in MIME format.\r\n\r\n";
		$content .= "--".$mime_boundary."\r\n";
		$content .= "Content-Type: ".$content_type."; charset=\"$encoding\"\r\n";
		$content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
	
		// we don't know if newlines are \n or \r\n in $message -> normalize newlines to \r\n
		$message = preg_replace('/\r\n|\r|\n/', "\r\n", $message);
		// consider SMTP maximum line length
		$content.= wordwrap($message, 950, "\r\n")."\r\n"."\r\n";
	
		// add attachments to mail body
		foreach($attachments as $dat) {
			$data = chunk_split(base64_encode($dat['data']));
			$content .= "--".$mime_boundary."\r\n";
			$content .= "Content-Disposition: attachment;\r\n";
			$content .= "\tfilename=\"".$dat['name']."\";\r\n";
			$content .= "Content-Length: .".$dat['size'].";\r\n";
			$content .= "Content-Type: ".$dat['type']."; name=\"".$dat['name']."\"\r\n";
			$content .= "Content-Transfer-Encoding: base64\r\n\r\n";
			$content .= $data."\r\n";
		}
		$content .= "--".$mime_boundary."--";
	
		// do some strange magic with the subject so we can use umlauts
		$subject = '=?utf-8?B?'.base64_encode($subject).'?=';
	
		// sign message if requested
		if(!empty($signopts)) {
	
			// let openssl do the signing work
			$TMP_MAIL_FILE = '/tmp/signmail.txt';
			$TMP_MAIL_FILE_SIGNED = '/tmp/signedmail.txt';
			file_put_contents($TMP_MAIL_FILE, $header2.$content);
			$res = openssl_pkcs7_sign(
				$TMP_MAIL_FILE, $TMP_MAIL_FILE_SIGNED,
				$signopts['cert'], $signopts['key'],
				[ 'To: '.$to, trim($header1a), trim($header1b) ],
				null, $signopts['chain']
			);
			if($res) {
				// separate header and body for mail function
				$signedData = file_get_contents($TMP_MAIL_FILE_SIGNED);
				$parts = explode("\n\n", $signedData, 2);
	
				// send mail with PHP's built-in mail() function using our specific headers
				return mail($to, $subject, $parts[1], $parts[0]);
			}
	
		} else { // send without signing
	
			// send mail with PHP's built-in mail() function using our specific headers
			return mail($to, $subject, $content, $header1a.$header1b.$header2);
	
		}
	}

}
