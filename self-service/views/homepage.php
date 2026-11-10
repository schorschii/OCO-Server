<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// 1. Retrieve user's display name or username
$displayName = !empty($currentDomainUser->display_name)
	? $currentDomainUser->display_name
	: (
		!empty($currentDomainUser->username) ? $currentDomainUser->username : LANG('user')
	);

$firstName = $displayName;
if(strpos($displayName, ' ') !== false) {
	$parts = explode(' ', $displayName);
	$firstName = $parts[0];
} elseif(strpos($displayName, ',') !== false) {
	$parts = explode(',', $displayName);
	$firstName = trim(end($parts));
}

// 2. Count active jobs
$activeJobsCount = 0;
if(isset($cl) && isset($db)) {
	$containers = $cl->getMyJobContainers();
	foreach($containers as $jc) {
		$jobs = $db->selectAllStaticJobByJobContainer($jc->id);
		$status = $jc->getStatus($jobs);
		if($status !== Models\JobContainer::STATUS_SUCCEEDED && $status !== Models\JobContainer::STATUS_FAILED) {
			$activeJobsCount++;
		}
	}
}
?>

<div id='homepage'>
	<div>
		<h1><?php echo sprintf(LANG('self_service_welcome_title'), htmlspecialchars($firstName)); ?></h1>
		<p><?php echo LANG('self_service_welcome_subtitle'); ?></p>
	</div>

	<div class='gallery gap action-cards'>
		<!-- Card : Install software -->
		<a <?php echo Html::explorerLink('views/packages.php'); ?> class='item box'>
			<div>
				<img src='img/package.dyn.svg'>
			</div>
			<div>
				<h2><?php echo LANG('self_service_install_software'); ?></h2>
				<p><?php echo LANG('self_service_install_software_desc'); ?></p>
			</div>
		</a>

		<!-- Card : Update computer -->
		<a <?php echo Html::explorerLink('views/packages.php?updates_only=1'); ?> class='item box'>
			<div>
				<img src='img/refresh.dyn.svg'>
			</div>
			<div>
				<h2><?php echo LANG('self_service_update_computer'); ?></h2>
				<p><?php echo LANG('self_service_update_computer_desc'); ?></p>
			</div>
		</a>

		<!-- Card : Track installations -->
		<a <?php echo Html::explorerLink('views/job-containers.php'); ?> class='item box'>
			<div>
				<img src='img/job.dyn.svg'>
				<?php if($activeJobsCount > 0) { ?>
					<span class='badge'><?php echo $activeJobsCount; ?></span>
				<?php } ?>
			</div>
			<div>
				<h2><?php echo LANG('self_service_track_installations'); ?></h2>
				<p><?php echo LANG('self_service_track_installations_desc'); ?></p>
			</div>
		</a>
	</div>

	<?php if(is_file(__DIR__.'/help/index.html')) { ?>
	<div class='gallery gap action-cards'>
		<!-- Card : User guides -->
		<a <?php echo Html::explorerLink('views/help.php'); ?> class='item box'>
			<div>
				<img src='img/help.dyn.svg'>
			</div>
			<div>
				<h2><?php echo LANG('user_guides'); ?></h2>
				<p><?php echo LANG('user_guides_desc'); ?></p>
			</div>
		</a>
	</div>
	<?php } ?>

	<div class='footer'>
		<?php require('partial/copyright.php'); ?>
	</div>
</div>
