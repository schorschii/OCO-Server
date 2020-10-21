function obj(id) {
	return document.getElementById(id);
}

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

var currentExplorerContentUrl = null;
function ajaxRequest(url, objID, callback) {
	let timer = null;
	if(objID == 'explorer-content') {
		currentExplorerContentUrl = url;
		timer = setTimeout(function(){ showLoader(true) }, 100);
	}
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			if(obj(objID) != null) {
				obj(objID).innerHTML = this.responseText;
				if(objID == 'explorer-content') {
					initTableSort()
					initTableSearch()
					clearTimeout(timer);
					showLoader(false);
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.status == 401) {
			window.location.href = 'login.php';
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

// content refresh functions
function refreshSidebar() {
	ajaxRequest('views/tree.php', 'explorer-tree');
}
function refreshContent() {
	if(currentExplorerContentUrl == null) {
		alert('Kein View aktiv!');
	} else {
		ajaxRequest(currentExplorerContentUrl, 'explorer-content');
	}
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
	ajaxRequest('views/jobcontainer.php?id='+encodeURIComponent(id), 'explorer-content');
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
	ajaxRequest('views/deploy.php?'+paramString, 'explorer-content');
}

// package operations
function createPackage(name, version, author, description, archive, install_procedure, uninstall_procedure) {
	btnCreatePackage.disabled = true;
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('name', name);
	formData.append('version', version);
	formData.append('author', author);
	formData.append('description', description);
	formData.append('archive', archive);
	formData.append('install_procedure', install_procedure);
	formData.append('uninstall_procedure', uninstall_procedure);
	req.open('POST', 'views/package_new.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			alert('Paket wurde erstellt');
			refreshContentPackage();
		}
	};
}
function removeSelectedPackage(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert('Keine Elemente ausgewählt');
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm('Wirklich löschen?')) {
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
		alert('Keine Elemente ausgewählt');
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
function deploySelectedPackage(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	refreshContentDeploy(ids);
}
function newPackageGroup() {
	var newName = prompt('Name für die neue Gruppe');
	if(newName != null && newName != '') {
		ajaxRequestPost('views/package.php', urlencodeObject({'add_group':newName}), null, refreshSidebar);
	}
}
function confirmRemovePackageGroup(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm('Möchten Sie die Gruppe wirklich löschen? Pakete innerhalb der Gruppe werden nicht gelöscht.')) {
		ajaxRequestPost('views/package.php', paramString, null, function(){ refreshContentPackage(); refreshSidebar(); });
	}
}
function addSelectedPackageToGroup(checkboxName, groupId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert('Keine Elemente ausgewählt');
		return;
	}
	var params = [];
	params.push({'key':'add_to_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'add_to_group_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/package.php', paramString, null, function() { alert('Pakete wurden hinzugefügt') });
}
function confirmUninstallPackage(assignment_id) {
	if(confirm('Möchten Sie das Paket wirklich deinstallieren? Es wird ein Deinstallationsjob erstellt.')) {
		let req = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('uninstall_package_assignment_id', assignment_id);
		req.open('POST', 'views/computer_detail.php');
		req.send(formData);
		req.onreadystatechange = function() {
			if(this.readyState == 4 && this.status == 200) {
				refreshSidebar();
			}
		};
	}
}
function confirmRemovePackageComputerAssignment(assignment_id) {
	if(confirm('Möchten Sie die Computer-Paket-Zuordnung wirklich aufheben? Normalerweise sollte das Paket deinstalliert werden.')) {
		let req = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('remove_package_assignment_id', assignment_id);
		req.open('POST', 'views/computer_detail.php');
		req.send(formData);
		req.onreadystatechange = function() {
			if(this.readyState == 4 && this.status == 200) {
				refreshContent();
			}
		};
	}
}

// computer operations
function removeSelectedComputerFromGroup(checkboxName, groupId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert('Keine Elemente ausgewählt');
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
function removeSelectedComputer(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert('Keine Elemente ausgewählt');
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm('Sind Sie sicher?')) {
		ajaxRequestPost('views/computer.php', paramString, null, refreshContent);
	}
}
function deploySelectedComputer(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	refreshContentDeploy([],[],ids);
}
function newComputerGroup() {
	var newName = prompt('Name für die neue Gruppe');
	if(newName != null && newName != '') {
		ajaxRequestPost('views/computer.php', urlencodeObject({'add_group':newName}), null, refreshSidebar);
	}
}
function confirmRemoveComputerGroup(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm('Möchten Sie die Gruppe(n) wirklich löschen? Die Computer in der Gruppe werden nicht gelöscht.')) {
		ajaxRequestPost('views/computer.php', paramString, null, function(){ refreshContentComputer(); refreshSidebar(); });
	}
}
function addSelectedComputerToGroup(checkboxName, groupId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		alert('Keine Elemente ausgewählt');
		return;
	}
	var params = [];
	params.push({'key':'add_to_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'add_to_group_computer_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/computer.php', paramString, null, function() { alert('Computer wurden hinzugefügt') });
}

// job operations
function confirmRemoveJobContainer(id) {
	if(confirm('Möchten Sie diesen Jobcontainer wirklich löschen? Ausstehende Jobs werden nicht verteilt.')) {
		ajaxRequestPost('views/jobcontainer.php', urlencodeObject({'remove_container_id':id}), null, function(){ refreshContentJobContainer(); refreshSidebar(); });
	}
}
function deploy(title, start, end, description, sltComputer, sltComputerGroup, sltPackage, sltPackageGroup) {
	var params = [
		{'key':'add_jobcontainer', 'value':title},
		{'key':'date_start', 'value':start},
		{'key':'date_end', 'value':end},
		{'key':'description', 'value':description}
	];
	getSelectValues(sltPackage).forEach(function(entry) {
		params.push({'key':'package_id[]', 'value':entry});
	});
	getSelectValues(sltPackageGroup).forEach(function(entry) {
		params.push({'key':'package_group_id[]', 'value':entry});
	});
	getSelectValues(sltComputer).forEach(function(entry) {
		params.push({'key':'computer_id[]', 'value':entry});
	});
	getSelectValues(sltComputerGroup).forEach(function(entry) {
		params.push({'key':'computer_group_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('views/deploy.php', paramString, null, function(){ refreshContentJobContainer(); refreshSidebar(); });
}

// domainuser operations
function removeSelectedDomainuser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm('Sind Sie sicher?')) {
		ajaxRequestPost('views/domainuser.php', paramString, null, refreshContent);
	}
}
