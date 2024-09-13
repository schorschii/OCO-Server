<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

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
	if(!$cl->checkPermission($c, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($c->hostname, LANG('computer'), 'views/computer-details.php?id='.$c->id, 'img/computer.dyn.svg');
}
$counter = 0;
foreach($db->searchAllMobileDevice($_GET['query']) as $md) {
	$counter ++;
	if(!$cl->checkPermission($md, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($md->device_name.' ('.$md->serial.')', LANG('mobile_device'), 'views/mobile-device-details.php?id='.$md->id, 'img/mobile-device.dyn.svg');
}
$counter = 0;
foreach($db->searchAllPackageFamily($_GET['query']) as $pf) {
	$counter ++;
	if(!$cl->checkPermission($pf, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($pf->name, LANG('package_family'), 'views/packages.php?package_family_id='.$pf->id, 'img/package-family.dyn.svg');
}
$counter = 0;
foreach($db->searchAllPackage($_GET['query']) as $p) {
	$counter ++;
	if(!$cl->checkPermission($p, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($p->getFullname(), LANG('package'), 'views/package-details.php?id='.$p->id, 'img/package.dyn.svg');
}
$counter = 0;
foreach($db->searchAllJobContainer($_GET['query']) as $jc) {
	$counter ++;
	if(!$cl->checkPermission($jc, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($jc->name, LANG('job_container'), 'views/job-containers.php?id='.$jc->id, 'img/job.dyn.svg');
}
$counter = 0;
foreach($db->searchAllDomainUser($_GET['query']) as $u) {
	$counter ++;
	if(!$cl->checkPermission($u, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($u->displayNameWithUsername(), LANG('domain_user'), 'views/domain-users.php?id='.$u->id, 'img/user.dyn.svg');
}
$counter = 0;
foreach($db->searchAllReport($_GET['query']) as $r) {
	$counter ++;
	if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($r->name, LANG('domain_user'), 'views/report-details.php?id='.$r->id, 'img/report.dyn.svg');
}
$counter = 0;
foreach($db->searchAllComputerGroup($_GET['query']) as $r) {
	$counter ++;
	if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($r->name, LANG('computer_group'), 'views/computers.php?id='.$r->id, 'img/folder.dyn.svg');
}
$counter = 0;
foreach($db->searchAllMobileDeviceGroup($_GET['query']) as $r) {
	$counter ++;
	if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($r->name, LANG('mobile_device_group'), 'views/mobile-devices.php?id='.$r->id, 'img/folder.dyn.svg');
}
$counter = 0;
foreach($db->searchAllPackageGroup($_GET['query']) as $r) {
	$counter ++;
	if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($r->name, LANG('package_group'), 'views/packages.php?id='.$r->id, 'img/folder.dyn.svg');
}
$counter = 0;
foreach($db->searchAllReportGroup($_GET['query']) as $r) {
	$counter ++;
	if(!$cl->checkPermission($r, PermissionManager::METHOD_READ, false)) continue;
	if($counter > $maxResults) { $moreAvail = true; break; }
	$items[] = new Models\SearchResult($r->name, LANG('report_group'), 'views/reports.php?id='.$r->id, 'img/folder.dyn.svg');
}
// extension search
foreach($ext->getAggregatedConf('frontend-search-function') as $func) {
	$counter = 0;
	foreach(call_user_func($func, $_GET['query'], $cl, $db) as $sr) {
		if(!$sr instanceof Models\SearchResult) continue;
		$counter ++;
		if($counter > $maxResults) { $moreAvail = true; break; }
		$items[] = $sr;
	}
}

if(count($items) == 0) {
	die('<div class="alert warning nomargin">'.LANG('no_search_results').'</div>');
} elseif(!$more && count($items) > 25) {
	$items = array_chunk($items, 25)[0];
}
?>

<?php if($more) { ?>

<h2><?php echo str_replace('%s', htmlspecialchars($_GET['query']), LANG('search_results_for')); ?></h2>
<div class='details-abreast'>
	<div class='stickytable'>
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
					<td>
						<img src='<?php echo htmlspecialchars($item->icon); ?>'>
						<?php echo htmlspecialchars($item->type); ?>
					</td>
					<td>
						<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink($item->link); ?>><?php echo htmlspecialchars($item->text); ?></a>
					</td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div>
								<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>
							</div>
							<div class='controls'>
								<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<?php } else { ?>

<?php foreach($items as $item) { ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink($item->link, 'closeSearchResults()'); ?>><img src='<?php echo htmlspecialchars($item->icon); ?>'><?php echo htmlspecialchars($item->text); ?></a>
	</div>
<?php } ?>
<?php if($moreAvail) { ?>
	<div class='node'>
		<a onkeydown='handleSearchResultNavigation(event)' <?php echo explorerLink('views/search.php?context=more&query='.urlencode($_GET['query']), 'closeSearchResults()'); ?>><img src='img/eye.dyn.svg'><?php echo LANG('more'); ?></a>
	</div>
<?php } ?>

<?php } ?>
