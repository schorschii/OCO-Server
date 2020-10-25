<?php

function bytesToGb($bytes) {
	if(empty($bytes)) return '';
	return round($bytes/1024/1024/1014).'&nbsp;GiB';
}
