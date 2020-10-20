<?php

/* Source https://gist.github.com/kosinix/4cf0d432638817888149 */
class ResumeDownload {
	private $file;
	private $name;
	private $boundary;
	private $content_type;
	private $delay = 0;
	private $size = 0;
	function __construct($file, $content_type = "application/octet-stream", $delay = 0) {
		if (! is_file($file)) {
			header("HTTP/1.1 400 Invalid Request");
			die("<h3>File Not Found</h3>");
		}
		$this->size = filesize($file);
		$this->file = fopen($file, "r");
		$this->boundary = md5($file);
		$this->delay = $delay;
		$this->name = basename($file);
		$this->content_type = $content_type;
	}
	public function process() {
		$ranges = NULL;
		$t = 0;
		if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_SERVER['HTTP_RANGE']) && $range = stristr(trim($_SERVER['HTTP_RANGE']), 'bytes=')) {
			$range = substr($range, 6);
			$ranges = explode(',', $range);
			$t = count($ranges);
		}
		header("Accept-Ranges: bytes");
		header("Content-Type: " . $this->content_type);
		header("Content-Transfer-Encoding: binary");
		header(sprintf('Content-Disposition: attachment; filename="%s"', $this->name));
		if ($t > 0) {
			header("HTTP/1.1 206 Partial content");
			$t === 1 ? $this->pushSingle($range) : $this->pushMulti($ranges);
		} else {
			header("Content-Length: " . $this->size);
			$this->readFile();
		}
		flush();
	}
	private function pushSingle($range) {
		$start = $end = 0;
		$this->getRange($range, $start, $end);
		header("Content-Length: " . ($end - $start + 1));
		header(sprintf("Content-Range: bytes %d-%d/%d", $start, $end, $this->size));
		fseek($this->file, $start);
		$this->readBuffer($end - $start + 1);
		$this->readFile();
	}
	private function pushMulti($ranges) {
		$length = $start = $end = 0;
		$output = "";
		$tl = "Content-type: " . $this->content_type . "\r\n";
		$formatRange = "Content-range: bytes %d-%d/%d\r\n\r\n";
		foreach ( $ranges as $range ) {
			$this->getRange($range, $start, $end);
			$length += strlen("\r\n--$this->boundary\r\n");
			$length += strlen($tl);
			$length += strlen(sprintf($formatRange, $start, $end, $this->size));
			$length += $end - $start + 1;
		}
		$length += strlen("\r\n--$this->boundary--\r\n");
		header("Content-Length: $length");
		header("Content-Type: multipart/x-byteranges; boundary=$this->boundary");
		foreach ( $ranges as $range ) {
			$this->getRange($range, $start, $end);
			echo "\r\n--$this->boundary\r\n";
			echo $tl;
			echo sprintf($formatRange, $start, $end, $this->size);
			fseek($this->file, $start);
			$this->readBuffer($end - $start + 1);
		}
		echo "\r\n--$this->boundary--\r\n";
	}
	private function getRange($range, &$start, &$end) {
		list($start, $end) = explode('-', $range);
		$fileSize = $this->size;
		if ($start == '') {
			$tmp = $end;
			$end = $fileSize - 1;
			$start = $fileSize - $tmp;
			if ($start < 0)
				$start = 0;
		} else {
			if ($end == '' || $end > $fileSize - 1)
				$end = $fileSize - 1;
		}
		if ($start > $end) {
			header("Status: 416 Requested range not satisfiable");
			header("Content-Range: */" . $fileSize);
			exit();
		}
		return array(
				$start,
				$end
		);
	}
	private function readFile() {
		while ( ! feof($this->file) ) {
			echo fgets($this->file);
			flush();
			usleep($this->delay);
		}
	}
	private function readBuffer($bytes, $size = 1024) {
		$bytesLeft = $bytes;
		while ( $bytesLeft > 0 && ! feof($this->file) ) {
			$bytesLeft > $size ? $bytesRead = $size : $bytesRead = $bytesLeft;
			$bytesLeft -= $bytesRead;
			echo fread($this->file, $bytesRead);
			flush();
			usleep($this->delay);
		}
	}
}
