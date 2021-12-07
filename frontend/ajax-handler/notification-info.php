<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

$info = [
	'job_container' => [],
];

foreach($db->getAllJobContainer() as $jc) {
	$stateDescription = '';
	$state = $db->getJobContainerIcon($jc->id);
	if($state == 'schedule') $stateDescription = LANG['waiting_for_start'];
	if($state == 'wait') $stateDescription = LANG['waiting_for_client'];
	if($state == 'error') $stateDescription = LANG['failed'];
	if($state == 'tick') $stateDescription = LANG['succeeded'];
	$info['job_container'][] = [
		'id'=>$jc->id, 'name'=>$jc->name, 'state'=>$state, 'state_description'=>$stateDescription
	];
}

echo json_encode($info);
