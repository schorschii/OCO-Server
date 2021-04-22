function obj(id) {
	return document.getElementById(id);
}

function handleRefresh(e) {
	if((e.which || e.keyCode) == 116) {
		e.preventDefault();
		refreshContent();
		refreshSidebar();
	}
};

function rewriteUrlContentParameter(value) {
	key = encodeURIComponent('explorer-content');
	value = encodeURIComponent(value);
	// kvp looks like ['key1=value1', 'key2=value2', ...]
	var kvp = document.location.search.substr(1).split('&');
	let i=0;
	for(; i<kvp.length; i++) {
		if(kvp[i].startsWith(key + '=')) {
			let pair = kvp[i].split('=');
			pair[1] = value;
			kvp[i] = pair.join('=');
			break;
		}
	}
	if(i >= kvp.length) {
		kvp[kvp.length] = [key,value].join('=');
	}
	let params = kvp.join('&');
	window.history.pushState(currentExplorerContentUrl, "", document.location.pathname+"?"+params);
}
window.onpopstate = function (event) {
	if(event.state != null) {
		// browser's back button pressed
		ajaxRequest(event.state, 'explorer-content', null, false);
	}
};

var currentOpenContextMenu = null;
function toggleContextMenu(menu) {
	if(currentOpenContextMenu != null) {
		currentOpenContextMenu.classList.add('hidden');
	}
	if(menu != null) {
		menu.classList.remove('hidden')
		menu.style.top = event.clientY+'px';
		menu.style.left = event.clientX+'px';
	}
	currentOpenContextMenu = menu;
	return false;
}

function showErrorDialog(active, title='', text='', showReload=true) {
	if(active) {
		obj('dialog-container').classList.add('active');
		obj('dialog-title').innerText = title;
		obj('dialog-text').innerText = text;
		if(showReload) {
			btnDialogHome.style.visibility = 'visible';
			btnDialogReload.style.visibility = 'visible';
		} else {
			btnDialogHome.style.visibility = 'collapse';
			btnDialogReload.style.visibility = 'collapse';
		}
	} else {
		obj('dialog-container').classList.remove('active');
	}
}

var currentExplorerContentUrl = null;
function ajaxRequest(url, objID, callback, addToHistory=true) {
	let timer = null;
	if(objID == 'explorer-content') {
		currentExplorerContentUrl = url;
		showLoader(true);
		timer = setTimeout(function(){ showLoader2(true) }, 100);
	}
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState != 4) {
			return;
		}
		if(this.status == 200) {
			if(obj(objID) != null) {
				obj(objID).innerHTML = this.responseText;
				if(objID == 'explorer-content') {
					if(addToHistory) {
						rewriteUrlContentParameter(currentExplorerContentUrl);
					}
					initTableSort()
					initTableSearch()
					clearTimeout(timer);
					showLoader(false);
					showLoader2(false);
					showErrorDialog(false);
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.status == 401) {
			window.location.href = 'login.php';
		} else {
			showLoader(false);
			showLoader2(false);
			if(this.status == 0) {
				showErrorDialog(true, L__NO_CONNECTION_TO_SERVER, L__PLEASE_CHECK_NETWORK);
			} else {
				showErrorDialog(true, L__ERROR+' '+this.status+' '+this.statusText, this.responseText);
			}
		}
	};
	xhttp.open("GET", url, true);
	xhttp.send();
}
function ajaxRequestPost(url, body, objID, callback) {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			if(obj(objID) != null) {
				obj(objID).innerHTML = this.responseText;
				if(objID == 'explorer-content') {
					initTableSort()
					initTableSearch()
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.readyState == 4) {
			alert(L__ERROR+' '+this.status+' '+this.statusText+"\n"+this.responseText);
		}
	};
	xhttp.open("POST", url, true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send(body);
}
function urlencodeObject(srcjson) {
	if(typeof srcjson !== "object") return null;
	var urljson = "";
	var keys = Object.keys(srcjson);
	for(var i=0; i <keys.length; i++){
		urljson += encodeURIComponent(keys[i]) + "=" + encodeURIComponent(srcjson[keys[i]]);
		if(i < (keys.length-1)) urljson+="&";
	}
	return urljson;
}
function urlencodeArray(src) {
	if(!Array.isArray(src)) return null;
	var urljson = "";
	for(var i=0; i <src.length; i++){
		urljson += encodeURIComponent(src[i]['key']) + "=" + encodeURIComponent(src[i]['value']);
		if(i < (src.length-1)) urljson+="&";
	}
	return urljson;
}

function showLoader(state) {
	if(state) {
		document.body.classList.add('loading');
	} else {
		document.body.classList.remove('loading');
	}
}
function showLoader2(state) {
	if(state) {
		document.body.classList.add('loading2');
	} else {
		document.body.classList.remove('loading2');
	}
}

function getSelectValues(select) {
	var result = [];
	var options = select && select.options;
	var opt;
	for (var i=0, iLen=options.length; i<iLen; i++) {
		opt = options[i];
		if(opt.selected) {
			result.push(opt.value || opt.text);
		}
	}
	return result;
}

// auto refresh content
setInterval(refreshSidebar, 10000);

// content refresh functions
function refreshSidebar() {
	ajaxRequest('views/tree.php', 'explorer-tree');
}
function refreshContent() {
	if(currentExplorerContentUrl != null) {
		ajaxRequest(currentExplorerContentUrl, 'explorer-content', null, false);
	}
}
function refreshContentHomepage() {
	ajaxRequest('views/homepage.php', 'explorer-content');
}
function refreshContentSettings(id='') {
	ajaxRequest('views/setting.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentDomainuser() {
	ajaxRequest('views/domainuser.php', 'explorer-content');
}
function refreshContentDomainuserDetail(id) {
	ajaxRequest('views/domainuser_detail.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentComputer(id='') {
	ajaxRequest('views/computer.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentComputerDetail(id) {
	ajaxRequest('views/computer_detail.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentSoftware(id='', version='', os='') {
	ajaxRequest('views/software.php?id='+encodeURIComponent(id)+'&version='+encodeURIComponent(version)+'&os='+encodeURIComponent(os), 'explorer-content');
}
function refreshContentPackage(id='') {
	ajaxRequest('views/package.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentPackageDetail(id) {
	if(id == null) {
		ajaxRequest('views/package_new.php', 'explorer-content');
	} else {
		ajaxRequest('views/package_detail.php?id='+encodeURIComponent(id), 'explorer-content');
	}
}
function refreshContentJobContainer(id='') {
	ajaxRequest('views/job_container.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentDeploy(package_ids=[], package_group_ids=[], computer_ids=[], computer_group_ids=[]) {
	var params = [];
	package_ids.forEach(function(entry) {
		params.push({'key':'package_id[]', 'value':entry});
	});
	package_group_ids.forEach(function(entry) {
		params.push({'key':'package_group_id[]', 'value':entry});
	});
	computer_ids.forEach(function(entry) {
		params.push({'key':'computer_id[]', 'value':entry});
	});
	computer_group_ids.forEach(function(entry) {
		params.push({'key':'computer_group_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequest('views/deploy.php?'+paramString, 'explorer-content', function(){
		refreshDeployComputerAndPackages(sltComputerGroup.value, sltPackageGroup.value, computer_ids, package_ids);
	});
}
function refreshContentReport(id='') {
	ajaxRequest('views/report.php?id='+encodeURIComponent(id), 'explorer-content');
}
function refreshContentReportDetail(id='') {
	ajaxRequest('views/report_detail.php?id='+encodeURIComponent(id), 'explorer-content');
}

// package operations
function updatePackageProcedureTemplates() {
	if(fleArchive.files.length > 0) {
		var newOptions = '';
		var i, L = lstInstallProceduresTemplates.options.length - 1;
		for(i = L; i >= 0; i--) {
			newOptions += '<option>'+lstInstallProceduresTemplates.options[i].innerText.replace('[FILENAME]',fleArchive.files[0].name)+'</option>';
		}
		lstInstallProcedures.innerHTML = newOptions;

		var newOptions2 = '';
		var i, L = lstUninstallProceduresTemplates.options.length - 1;
		for(i = L; i >= 0; i--) {
			newOptions2 += '<option>'+lstUninstallProceduresTemplates.options[i].innerText.replace('[FILENAME]',fleArchive.files[0].name)+'</option>';
		}
		lstUninstallProcedures.innerHTML = newOptions2;
	}
}
function createPackage(name, version, description, archive, install_procedure, install_procedure_success_return_codes, install_procedure_restart, install_procedure_shutdown, uninstall_procedure, uninstall_procedure_success_return_codes, download_for_uninstall, uninstall_procedure_restart, uninstall_procedure_shutdown) {
	if(typeof archive === 'undefined') {
		if(!confirm(L__CONFIRM_CREATE_EMPTY_PACKAGE)) {
			return;
		}
	}

	btnCreatePackage.disabled = true;
	btnCreatePackage.style.display = 'none';
	prgPackageUploadContainer.style.display = 'inline-block';

	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('name', name);
	formData.append('version', version);
	formData.append('description', description);
	formData.append('archive', archive);
	formData.append('install_procedure', install_procedure);
	formData.append('install_procedure_success_return_codes', install_procedure_success_return_codes);
	formData.append('install_procedure_restart', install_procedure_restart ? '1' : '0');
	formData.append('install_procedure_shutdown', install_procedure_shutdown ? '1' : '0');
	formData.append('uninstall_procedure', uninstall_procedure);
	formData.append('uninstall_procedure_success_return_codes', uninstall_procedure_success_return_codes);
	formData.append('download_for_uninstall', download_for_uninstall ? '1' : '0');
	formData.append('uninstall_procedure_restart', uninstall_procedure_restart ? '1' : '0');
	formData.append('uninstall_procedure_shutdown', uninstall_procedure_shutdown ? '1' : '0');

	req.upload.onprogress = function(evt) {
		if(evt.lengthComputable) {
			var progress = Math.ceil((evt.loaded / evt.total) * 100);
			if(progress == 100) {
				prgPackageUpload.classList.add('animated');
				prgPackageUploadText.innerText = L__IN_PROGRESS;
				prgPackageUpload.style.width = '100%';
			} else {
				prgPackageUpload.classList.remove('animated');
				prgPackageUploadText.innerText = progress + '%';
				prgPackageUpload.style.width = progress + '%';
			}
		} else {
			console.warn('form length is not computable');
			prgPackageUpload.classList.add('animated');
			prgPackageUploadText.innerText = L__IN_PROGRESS;
			prgPackageUpload.style.width = '100%';
		}
	};
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				alert(L__PACKAGE_CREATED);
				refreshContentPackageDetail(parseInt(this.responseText));
			} else {
				alert(L__ERROR+' '+this.status+' '+this.statusText+"\n"+this.responseText);
				btnCreatePackage.disabled = false;
				btnCreatePackage.style.display = 'inline-block';
				prgPackageUploadContainer.style.display = 'none';
			}
		}
	};

	req.open('POST', 'views/package_new.php');
	req.send(formData);
}
function updatePackage(id, description, install_procedure, install_procedure_success_return_codes, install_procedure_restart, install_procedure_shutdown, uninstall_procedure, uninstall_procedure_success_return_codes, download_for_uninstall, uninstall_procedure_restart, uninstall_procedure_shutdown) {
	btnEditPackage.disabled = true;
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_id', id);
	formData.append('description', description);
	formData.append('install_procedure', install_procedure);
	formData.append('install_procedure_success_return_codes', install_procedure_success_return_codes);
	formData.append('install_procedure_restart', install_procedure_restart ? '1' : '0');
	formData.append('install_procedure_shutdown', install_procedure_shutdown ? '1' : '0');
	formData.append('uninstall_procedure', uninstall_procedure);
	formData.append('uninstall_procedure_success_return_codes', uninstall_procedure_success_return_codes);
	formData.append('download_for_uninstall', download_for_uninstall ? '1' : '0');
	formData.append('uninstall_procedure_restart', uninstall_procedure_restart ? '1' : '0');
	formData.append('uninstall_procedure_shutdown', uninstall_procedure_shutdown ? '1' : '0');
	req.open('POST', 'views/package_detail.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				alert(L__SAVED);
				refreshContent();
			} else {
				alert(L__ERROR+' '+this.status+' '+this.statusText+"\n"+this.responseText);
				btnEditPackage.disabled = false;
			}
		}
	};
}
function reorderPackageInGroup(groupId, oldPos, newPos) {
	var params = [];
	params.push({'key':'move_in_group', 'value':groupId});
	params.push({'key':'move_from_pos', 'value':oldPos});
	params.push({'key':'move_to_pos', 'value':newPos});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/package.php', paramString, null, refreshContent);
}
function removeSelectedPackage(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	confirmRemovePackage(ids);
}
function confirmRemovePackage(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_PACKAGE)) {
		ajaxRequestPost('views/package.php', paramString, null, refreshContent);
	}
}
function removeSelectedPackageFromGroup(checkboxName, groupId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	params.push({'key':'remove_from_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/package.php', paramString, null, refreshContent);
}
function deploySelectedPackage(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	refreshContentDeploy(ids);
}
function newPackageGroup(parent_id=null) {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null && newName != '') {
		ajaxRequestPost('views/package.php', urlencodeObject({'add_group':newName, 'parent_id':parent_id}), null, refreshSidebar);
	}
}
function renamePackageGroup(id, oldName) {
	var newName = prompt(L__ENTER_NAME, oldName);
	if(newName != null && newName != '') {
		ajaxRequestPost('views/package.php', urlencodeObject({'rename_group':id, 'new_name':newName}), null, function(){ refreshContent(); refreshSidebar(); });
	}
}
function confirmRemovePackageGroup(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_GROUP)) {
		ajaxRequestPost('views/package.php', paramString, null, function(){ refreshContentPackage(); refreshSidebar(); });
	}
}
function addSelectedPackageToGroup(checkboxName, groupId, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	params.push({'key':'add_to_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'add_to_group_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/package.php', paramString, null, function() { alert(L__PACKAGES_ADDED) });
}
function addPackageToGroup(packageId, groupId) {
	var params = [];
	params.push({'key':'add_to_group_id', 'value':groupId});
	params.push({'key':'add_to_group_package_id[]', 'value':packageId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/package.php', paramString, null, function() { alert(L__PACKAGES_ADDED); refreshContent(); });
}
function confirmUninstallPackage(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'uninstall_package_assignment_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_UNINSTALL_PACKAGE)) {
		ajaxRequestPost('views/computer_detail.php', paramString, null, function() { refreshSidebar() });
	}
}
function confirmRemovePackageComputerAssignment(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_package_assignment_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_REMOVE_PACKAGE_ASSIGNMENT)) {
		ajaxRequestPost('views/computer_detail.php', paramString, null, function() { refreshContent() });
	}
}
function refreshDeployComputerAndPackages(refreshComputersGroupId=null, refreshPackagesGroupId=null, preselectComputerIds=[], preselectPackageIds=[]) {
	if(refreshComputersGroupId != null) {
		var params = [];
		params.push({'key':'get_computer_group_members', 'value':refreshComputersGroupId});
		preselectComputerIds.forEach(function(entry) {
			params.push({'key':'computer_id[]', 'value':entry});
		});
		ajaxRequest("views/deploy.php?"+urlencodeArray(params), 'sltComputer', function(){ refreshDeployCount() });
		if(refreshComputersGroupId < 1) sltComputerGroup.value = -1;
	}
	if(refreshPackagesGroupId != null) {
		var params = [];
		params.push({'key':'get_package_group_members', 'value':refreshPackagesGroupId});
		preselectPackageIds.forEach(function(entry) {
			params.push({'key':'package_id[]', 'value':entry});
		});
		ajaxRequest("views/deploy.php?"+urlencodeArray(params), 'sltPackage', function(){ refreshDeployCount() });
		if(refreshPackagesGroupId < 1) sltPackageGroup.value = -1;
	}
}
function refreshDeployCount() {
	spnSelectedComputers.innerHTML = getSelectValues(sltComputer).length;
	spnSelectedPackages.innerHTML = getSelectValues(sltPackage).length;
	spnTotalComputers.innerHTML = sltComputer.options.length;
	spnTotalPackages.innerHTML = sltPackage.options.length;
}

// computer operations
function saveComputerNotes(id, notes) {
	ajaxRequestPost('views/computer_detail.php', urlencodeObject({'update_note_computer_id':id, 'update_note':notes}), null, function(){ alert(L__SAVED); });
}
function newComputer() {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null && newName != '') {
		let req = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('add_computer', newName);
		req.onreadystatechange = function() {
			if(this.readyState == 4) {
				if(this.status == 200) {
					refreshContentComputerDetail(parseInt(this.responseText));
				} else {
					alert(L__ERROR+' '+this.status+' '+this.statusText+"\n"+this.responseText);
				}
			}
		};
		req.open('POST', 'views/computer.php');
		req.send(formData);
	}
}
function removeSelectedComputerFromGroup(checkboxName, groupId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	params.push({'key':'remove_from_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_computer_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/computer.php', paramString, null, refreshContent);
}
function removeSelectedComputer(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	confirmRemoveComputer(ids);
}
function confirmRemoveComputer(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('views/computer.php', paramString, null, refreshContent);
	}
}
function deploySelectedComputer(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	refreshContentDeploy([],[],ids);
}
function wolSelectedComputer(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	confirmWolComputer(ids);
}
function confirmWolComputer(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'wol_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/computer.php', paramString, null, function() { alert(L__WOL_SENT) });
}
function newComputerGroup(parent_id=null) {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null && newName != '') {
		ajaxRequestPost('views/computer.php', urlencodeObject({'add_group':newName, 'parent_id':parent_id}), null, refreshSidebar);
	}
}
function renameComputerGroup(id, oldName) {
	var newName = prompt(L__ENTER_NAME, oldName);
	if(newName != null && newName != '') {
		ajaxRequestPost('views/computer.php', urlencodeObject({'rename_group':id, 'new_name':newName}), null, function(){ refreshContent(); refreshSidebar(); });
	}
}
function confirmRemoveComputerGroup(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_GROUP)) {
		ajaxRequestPost('views/computer.php', paramString, null, function(){ refreshContentComputer(); refreshSidebar(); });
	}
}
function addSelectedComputerToGroup(checkboxName, groupId, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	params.push({'key':'add_to_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'add_to_group_computer_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/computer.php', paramString, null, function() { alert(L__COMPUTER_ADDED) });
}
function addComputerToGroup(computerId, groupId) {
	var params = [];
	params.push({'key':'add_to_group_id', 'value':groupId});
	params.push({'key':'add_to_group_computer_id[]', 'value':computerId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/computer.php', paramString, null, function() { alert(L__COMPUTER_ADDED); refreshContent(); });
}

// job operations
function confirmRemoveJobContainer(id) {
	if(confirm(L__CONFIRM_DELETE_JOBCONTAINER)) {
		ajaxRequestPost('views/job_container.php', urlencodeObject({'remove_container_id':id}), null, function(){ refreshContentJobContainer(); refreshSidebar(); });
	}
}
function confirmRenewFailedJobsInContainer(id) {
	if(confirm(L__CONFIRM_RENEW_JOBS)) {
		ajaxRequestPost('views/job_container.php', urlencodeObject({'renew_container_id':id}), null, function(){ refreshContentJobContainer(); refreshSidebar(); });
	}
}
function deploy(title, start, end, description, sltComputer, sltComputerGroup, sltPackage, sltPackageGroup, useWol, autoCreateUninstallJobs, restartTimeout) {
	btnDeploy.disabled = true;
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('add_jobcontainer', title);
	formData.append('date_start', start);
	formData.append('date_end', end);
	formData.append('description', description);
	formData.append('use_wol', useWol ? 1 : 0);
	formData.append('auto_create_uninstall_jobs', autoCreateUninstallJobs ? 1 : 0);
	formData.append('restart_timeout', restartTimeout);
	getSelectValues(sltPackage).forEach(function(entry) {
		formData.append('package_id[]', entry);
	});
	getSelectValues(sltPackageGroup).forEach(function(entry) {
		formData.append('package_group_id[]', entry);
	});
	getSelectValues(sltComputer).forEach(function(entry) {
		formData.append('computer_id[]', entry);
	});
	getSelectValues(sltComputerGroup).forEach(function(entry) {
		formData.append('computer_group_id[]', entry);
	});
	req.open('POST', 'views/deploy.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				refreshContentJobContainer(parseInt(this.responseText));
				refreshSidebar();
			} else {
				alert(L__ERROR+' '+this.status+' '+this.statusText+"\n"+this.responseText);
				btnDeploy.disabled = false;
			}
		}
	};
}

// domainuser operations
function confirmRemoveSelectedDomainuser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('views/domainuser.php', paramString, null, refreshContent);
	}
}

// systemuser operations
function confirmRemoveSelectedSystemuser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_systemuser_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('views/setting.php', paramString, null, refreshContent);
	}
}
function lockSelectedSystemuser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'lock_systemuser_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/setting.php', paramString, null, refreshContent);
}
function unlockSelectedSystemuser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'unlock_systemuser_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/setting.php', paramString, null, refreshContent);
}
function createSystemuser(username, fullname, password) {
	btnCreateUser.disabled = true;
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('add_systemuser_username', username);
	formData.append('add_systemuser_fullname', username);
	formData.append('add_systemuser_password', password);
	req.open('POST', 'views/setting.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			refreshContent();
		}
	};
}
function changeSelectedSystemuserPassword(checkboxName, password, password2) {
	if(password != password2) {
		alert(L__PASSWORDS_DO_NOT_MATCH);
		return;
	}
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert(L__NO_ELEMENTS_SELECTED);
		return;
	}
	btnChangePassword.disabled = true;
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('change_systemuser_id', ids[0]);
	formData.append('change_systemuser_password', password);
	req.open('POST', 'views/setting.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			alert(L__SAVED);
			refreshContent();
		}
	};
}

// setting operations
function saveGeneralSettings() {
	btnSaveGeneralSettings.disabled = true;
	var values = {
		"agent-registration-enabled": chkAgentRegistrationEnabled.checked ? 1 : 0,
		"agent-key": txtAgentKey.value,
		"agent-update-interval": txtAgentUpdateInterval.value,
		"purge-succeeded-jobs": txtPurgeSucceededJobsAfter.value,
		"purge-failed-jobs": txtPurgeFailedJobsAfter.value
	};
	ajaxRequestPost('views/setting.php', urlencodeObject(values), null, function(){ alert(L__SAVED); refreshContent(); });
}
