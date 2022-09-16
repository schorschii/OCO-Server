<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

if(empty($_GET['query'])) {
	die('<div class="alert info nomargin">'.LANG('please_enter_a_search_term').'</div>');
}

$maxResults = 5;
$more = false;
if(!empty($_GET['context']) && $_GET['context'] === 'more') {
	$maxResults = 99999999;
	$more = true;
}

$items = [];
$moreAvail = false;
$counter = 0;
foreach($db->searchAllComputer($_GET['query']) as $c) {
	$counter ++;
	if(!$currentSystemUser->checkPermission($c, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = $c;
}
$counter = 0;
foreach($db->searchAllPackageFamily($_GET['query']) as $pf) {
	$counter ++;
	if(!$currentSystemUser->checkPermission($pf, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = $pf;
}
$counter = 0;
foreach($db->searchAllJobContainer($_GET['query']) as $jc) {
	$counter ++;
	if(!$currentSystemUser->checkPermission($jc, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = $jc;
}
$counter = 0;
foreach($db->searchAllDomainUser($_GET['query']) as $u) {
	$counter ++;
	if(!$currentSystemUser->checkPermission($u, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = $u;
}
$counter = 0;
foreach($db->searchAllReport($_GET['query']) as $r) {
	$counter ++;
	if(!$currentSystemUser->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = $r;
}

if(count($items) == 0) {
	die('<div class="alert warning nomargin">'.LANG('no_search_results').'</div>');
}
?>

<?php if($more) { ?>

<h2><?php echo str_replace('%s', htmlspecialchars($_GET['query']), LANG('search_results_for')); ?></h2>
<table class='list searchable sortable savesort fullwidth'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG('type'); ?></th>
			<th class='searchable sortable'><?php echo LANG('name'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($items as $item) { ?>
		<tr>
			<?php if($item instanceof Models\Computer) { ?>
				<td>
					<img src='img/computer.dyn.svg'>
					<?php echo LANG('computer'); ?>
				</td>
				<td>
					<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/computer-details.php?id='.$item->id, 'closeSearchResults()'); ?>><?php echo htmlspecialchars($item->hostname); ?></a>
				</td>
			<?php } elseif($item instanceof Models\PackageFamily) { ?>
				<td>
					<img src='img/package.dyn.svg'>
					<?php echo LANG('package_family'); ?>
				</td>
				<td>
					<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/packages.php?package_family_id='.$item->id, 'closeSearchResults()'); ?>><?php echo htmlspecialchars($item->name); ?></a>
				</td>
			<?php } elseif($item instanceof Models\JobContainer) { ?>
				<td>
					<img src='img/job.dyn.svg'>
					<?php echo LANG('job_container'); ?>
				</td>
				<td>
					<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/job-containers.php?id='.$item->id, 'closeSearchResults()'); ?>><?php echo htmlspecialchars($item->name); ?></a>
				</td>
			<?php } elseif($item instanceof Models\DomainUser) { ?>
				<td>
					<img src='img/user.dyn.svg'>
					<?php echo LANG('domain_user'); ?>
				</td>
				<td>
					<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/domain-users.php?id='.$item->id, 'closeSearchResults()'); ?>><?php echo htmlspecialchars($item->displayNameWithUsername()); ?></a>
				</td>
			<?php } elseif($item instanceof Models\Report) { ?>
				<td>
					<img src='img/report.dyn.svg'>
					<?php echo LANG('report'); ?>
				</td>
				<td>
					<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/report-details.php?id='.$item->id, 'closeSearchResults()'); ?>><?php echo htmlspecialchars($item->name); ?></a>
				</td>
			<?php } ?>
		</tr>
	<?php } ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='999'>
				<span class='counter'><?php echo count($items); ?></span> <?php echo LANG('elements'); ?>
			</td>
		</tr>
	</tfoot>
</table>

<?php } else { ?>

<?php foreach($items as $item) { ?>
	<div class='node'>
		<?php if($item instanceof Models\Computer) { ?>
			<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/computer-details.php?id='.$item->id, 'closeSearchResults()'); ?>><img src='img/computer.dyn.svg'><?php echo htmlspecialchars($item->hostname); ?></a>
		<?php } elseif($item instanceof Models\PackageFamily) { ?>
			<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/packages.php?package_family_id='.$item->id, 'closeSearchResults()'); ?>><img src='img/package.dyn.svg'><?php echo htmlspecialchars($item->name); ?></a>
		<?php } elseif($item instanceof Models\JobContainer) { ?>
			<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/job-containers.php?id='.$item->id, 'closeSearchResults()'); ?>><img src='img/job.dyn.svg'><?php echo htmlspecialchars($item->name); ?></a>
		<?php } elseif($item instanceof Models\DomainUser) { ?>
			<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/domain-users.php?id='.$item->id, 'closeSearchResults()'); ?>><img src='img/user.dyn.svg'><?php echo htmlspecialchars($item->displayNameWithUsername()); ?></a>
		<?php } elseif($item instanceof Models\Report) { ?>
			<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/report-details.php?id='.$item->id, 'closeSearchResults()'); ?>><img src='img/report.dyn.svg'><?php echo htmlspecialchars($item->name); ?></a>
		<?php } ?>
	</div>
<?php } ?>
<?php if($moreAvail) { ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/search.php?context=more&query='.urlencode($_GET['query']), 'closeSearchResults()'); ?>><img src='img/eye.dyn.svg'><?php echo LANG('more'); ?></a>
	</div>
<?php } ?>

<?php } ?>
