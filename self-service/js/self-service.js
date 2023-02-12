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
