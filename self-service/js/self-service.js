function initSelfServicePackages() {
	let divStoreGrid = document.getElementById('divStoreGrid');
	if(!divStoreGrid || divStoreGrid.dataset.initialized) return;
	divStoreGrid.dataset.initialized = 'true';

	let packageItems = Array.from(divStoreGrid.querySelectorAll('.item'));
	let sltTargetComputer = document.getElementById('sltTargetComputer');
	let txtPackageSearch = document.getElementById('txtPackageSearch');
	let chkNonInstalledOnly = document.getElementById('chkNonInstalledOnly');
	let chkUpdatesOnly = document.getElementById('chkUpdatesOnly');
	let divBottomDrawer = document.getElementById('divBottomDrawer');
	let btnCartSummary = document.getElementById('btnCartSummary');
	let divCartRecapPanel = document.getElementById('divCartRecapPanel');
	let btnDeploy = document.getElementById('btnDeploy');
	let spnCartCount = document.getElementById('spnCartCount');
	let ulCartRecapList = document.getElementById('ulCartRecapList');
	let divNoPackagesFound = document.getElementById('divNoPackagesFound');
	let deploying = false;

	function filterPackages() {
		let search = txtPackageSearch.value.trim().toLowerCase();
		let computer = sltTargetComputer.selectedOptions[0];
		let installedPackages = new Set(sltTargetComputer.value && computer
			? JSON.parse(computer.getAttribute('data_installed_packages') || '[]').map(String)
			: []);
		let installedFamilies = new Set(sltTargetComputer.value && computer
			? JSON.parse(computer.getAttribute('data_installed_package_families') || '[]').map(String)
			: []);
		packageItems.forEach(item => {
			let nameVersion = item.getAttribute('data_name') + ' ' + item.getAttribute('data_version');
			let hideInstalled = chkNonInstalledOnly.checked && installedPackages.has(item.getAttribute('data_id'));
			let hideFamily = chkUpdatesOnly.checked && !installedFamilies.has(item.getAttribute('data_package_family_id'));
			item.classList.toggle('hidden', !nameVersion.toLowerCase().includes(search) || hideInstalled || hideFamily);
		});
		divNoPackagesFound.classList.toggle('hidden', packageItems.some(item => !item.classList.contains('hidden')));
	}

	function isCompatible(value, compatible) {
		return !value || !compatible || compatible.split('\n').some(entry => entry.trim() === value.trim());
	}

	function updateCompatibility() {
		let computer = sltTargetComputer.selectedOptions[0];
		packageItems.forEach(item => {
			let incompatible = !!sltTargetComputer.value && !!computer && (
				!isCompatible(computer.getAttribute('data_os'), item.getAttribute('data_compatible_os')) ||
				!isCompatible(computer.getAttribute('data_os_version'), item.getAttribute('data_compatible_os_version'))
			);
			item.classList.toggle('incompatible', incompatible);
			if(incompatible) item.classList.remove('selected');
		});
		updateCart();
	}

	function updateCart() {
		let selectedItems = packageItems.filter(item => item.classList.contains('selected'));
		ulCartRecapList.replaceChildren();
		selectedItems.forEach(item => {
			let entry = document.createElement('li');
			entry.setAttribute('data_id', item.getAttribute('data_id'));
			entry.textContent = item.getAttribute('data_name') + ' (' + item.getAttribute('data_version')+')';
			ulCartRecapList.appendChild(entry);
		});
		spnCartCount.textContent = LANG['packages_selected_placeholder'].replace('%d', selectedItems.length);
		divBottomDrawer.classList.toggle('visible', selectedItems.length > 0);
		btnDeploy.disabled = deploying || !sltTargetComputer.value || selectedItems.length === 0;
	}

	btnDeploy.addEventListener('click', () => {
		if(deploying) return;
		updateCompatibility();
		let selectedItems = packageItems.filter(item => item.classList.contains('selected'));
		if(!sltTargetComputer.value || selectedItems.length === 0) return;

		let name = LANG['install'] + ' - ' + sltTargetComputer.selectedOptions[0].textContent.trim();
		showDialogAjax(LANG['deploy'], 'views/dialog/install.php?name='+encodeURIComponent(name), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
			// add events
			let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
			let chkWol = dialogContainer.querySelectorAll('input[name=use_wol]')[0];
			let chkShutdownWaked = dialogContainer.querySelectorAll('input[name=shutdown_waked_after_completion]')[0];
			chkWol.addEventListener('click', (e)=>{
				if(e.srcElement.checked) {
					chkShutdownWaked.disabled = false;
				} else {
					chkShutdownWaked.checked = false;
					chkShutdownWaked.disabled=true;
				}
			});
			dialogContainer.querySelectorAll('button[name=install]')[0].addEventListener('click', async (e)=>{
				dialogContainer.close();
				let formData = new FormData();
				formData.append('create_install_job_container', txtName.value);
				formData.append('computer_id[]', sltTargetComputer.value);
				selectedItems.forEach(item => formData.append('package_id[]', item.getAttribute('data_id')));
				formData.append('use_wol', chkWol.checked ? 1 : 0);
				formData.append('shutdown_waked_after_completion', chkShutdownWaked.checked ? 1 : 0);

				deploying = true;
				updateCart();
				try {
					let response = await fetch('ajax-handler/job-containers.php', {
						method: 'POST',
						body: formData
					});
					let result = (await response.text()).trim();
					if(!response.ok) throw new Error(response.status + ' ' + response.statusText + '\n' + result);
					if(!/^[1-9]\d*$/.test(result)) throw new Error(result || LANG['error']);
					selectedItems.forEach(item => item.classList.remove('selected'));
					emitMessage(LANG['jobs_created'], name, MESSAGE_TYPE_SUCCESS);
					refreshContentExplorer('views/job-containers.php?id=' + encodeURIComponent(result));
				} catch(error) {
					emitMessage(LANG['error'], error.message, MESSAGE_TYPE_ERROR, null);
				} finally {
					deploying = false;
					updateCart();
				}
			});
		});
	});

	txtPackageSearch.addEventListener('input', filterPackages);
	chkNonInstalledOnly.addEventListener('change', filterPackages);
	chkUpdatesOnly.addEventListener('change', filterPackages);
	sltTargetComputer.addEventListener('change', () => {
		updateCompatibility();
		filterPackages();
	});
	btnCartSummary.addEventListener('click', () => {
		divCartRecapPanel.classList.toggle('open');
	});
	packageItems.forEach(item => {
		item.addEventListener('click', event => {
			event.preventDefault();
			item.classList.toggle('selected');
			updateCart();
		});
	});

	filterPackages();
	updateCompatibility();
}

function showDialogUninstallSelfService() {
	showDialogAjax(LANG['uninstall_packages'], 'views/dialog/uninstall.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let chkWol = dialogContainer.querySelectorAll('input[name=wol]')[0];
		let chkShutdownWaked = dialogContainer.querySelectorAll('input[name=shutdown_waked]')[0];
		// add events
		chkWol.addEventListener('click', (e)=>{
			if(e.srcElement.checked) {
				chkShutdownWaked.disabled = false;
			} else {
				chkShutdownWaked.checked = false;
				chkShutdownWaked.disabled=true;
			}
		});
		dialogContainer.querySelectorAll('button[name=uninstall]')[0].addEventListener('click', (e)=>{
			uninstallSelfService(
				dialogContainer,
				'package_id[]',
				txtName.value,
				chkWol.checked,
				chkShutdownWaked.checked,
			);
		});
	});
}
function uninstallSelfService(dialogContainer, checkboxName, name, useWol, shutdownWakedAfterCompletion) {
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
		dialogContainer.close();
		refreshSidebar(); refreshContent();
		emitMessage(LANG['jobs_created'], name, MESSAGE_TYPE_SUCCESS);
	});
}
