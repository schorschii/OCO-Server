<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$info = [
	'job_container' => [],
];

foreach($cl->getJobContainers(null) as $jc) {
	$stateDescription = '';
	$state = $jc->getStatus($db->selectAllStaticJobByJobContainer($jc->id));
	if($state == 'schedule') $stateDescription = LANG('waiting_for_start');
	if($state == 'wait') $stateDescription = LANG('waiting_for_agent');
	if($state == 'error') $stateDescription = LANG('failed');
	if($state == 'success') $stateDescription = LANG('succeeded');
	$info['job_container'][] = [
		'id'=>$jc->id, 'name'=>$jc->name, 'state'=>$state, 'state_description'=>$stateDescription
	];
}

echo json_encode($info);
