function deploySelfService(title, computers, packages, useWol, shutdownWakedAfterCompletion) {
	btnDeploy.disabled = true;
	btnDeploy.classList.add('hidden');
	prgDeploy.classList.remove('hidden');

	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('create_install_job_container', title);
	formData.append('use_wol', useWol ? 1 : 0);
	formData.append('shutdown_waked_after_completion', shutdownWakedAfterCompletion ? 1 : 0);
	packages.forEach(function(entry) {
		formData.append('package_id[]', entry);
	});
	computers.forEach(function(entry) {
		formData.append('computer_id[]', entry);
	});
	req.open('POST', 'ajax-handler/job-containers.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				refreshContentExplorer('views/job-containers.php?id='+parseInt(this.responseText));
				refreshSidebar();
				emitMessage(LANG['jobs_created'], title, MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				btnDeploy.disabled = false;
				btnDeploy.classList.remove('hidden');
				prgDeploy.classList.add('hidden');
			}
		}
	};
}
function uninstallSelfService(checkboxName, name, useWol, shutdownWakedAfterCompletion) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	params.push({'key':'create_uninstall_job_container', 'value':name});
	ids.forEach(function(entry) {
		params.push({'key':'uninstall_package_assignment_id[]', 'value':entry});
	});
	params.push({'key':'use_wol', 'value':useWol ? 1 : 0});
	params.push({'key':'shutdown_waked_after_completion', 'value':shutdownWakedAfterCompletion ? 1 : 0});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
		hideDialog();
		refreshSidebar(); refreshContent();
		emitMessage(LANG['jobs_created'], name, MESSAGE_TYPE_SUCCESS);
	});
}
