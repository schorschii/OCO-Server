// ======== GENERAL ========
function obj(id) {
	return document.getElementById(id);
}
function getCheckedRadioValue(name) {
	var rates = document.getElementsByName(name);
	for(var i = 0; i < rates.length; i++) {
		if(rates[i].checked) {
			return rates[i].value;
		}
	}
}
function toggleSidebar(force=null) {
	if(force == null) {
		obj('explorer').classList.toggle('nosidebar');
		return;
	}
	if(window.innerWidth < 750 /*as defined in CSS*/) {
		if(!force)
			obj('explorer').classList.add('nosidebar');
		else
			obj('explorer').classList.remove('nosidebar');
		return;
	}
}
function toggleTextBoxMultiLine(element) {
	var newTagName = 'input';
	if(element.tagName.toLowerCase() == 'input') newTagName = 'textarea';
	var newElement = document.createElement(newTagName);
	newElement.id = element.id;
	newElement.classList = element.classList;
	newElement.value = element.value;
	newElement.placeholder = element.placeholder;
	element.replaceWith(newElement);
}

function rewriteUrlContentParameter(ajaxRequestUrl) {
	// compile parameters to replace from ajax request URL
	var paramsToReplace = {};
	var url = new URL(ajaxRequestUrl, location);
	paramsToReplace['view'] = url.pathname.split(/[\\/]/).pop().split('.')[0];
	for(const [key, value] of url.searchParams) {
		paramsToReplace[key] = value;
	}
	// now replace the parameters in current URL
	var kvp = []; // kvp looks like ['key1=value1', ...] // document.location.search.substr(1).split('&')
	for(const [ikey, ivalue] of Object.entries(paramsToReplace)) {
		key = encodeURIComponent(ikey);
		value = encodeURIComponent(ivalue);
		let i = 0;
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
	}
	window.history.pushState(
		currentExplorerContentUrl,
		document.title,
		document.location.pathname+'?'+kvp.join('&')
	);
}

// ======== EVENT LISTENERS ========
window.onpopstate = function(event) {
	if(event.state != null) {
		// browser's back button pressed
		ajaxRequest(event.state, 'explorer-content', null, false);
	}
};
window.onkeydown = function(event) {
	// F1 - Help
	if((event.which || event.keyCode) == 112) {
		event.preventDefault();
		refreshContentExplorer('views/docs.php');
	}
	// F3 - Search
	if((event.which || event.keyCode) == 114) {
		event.preventDefault();
		txtGlobalSearch.focus();
	}
	// F5 - Reload Explorer Content
	if((event.which || event.keyCode) == 116) {
		event.preventDefault();
		refreshContent();
		refreshSidebar();
	}
};

// ======== DIALOG ========
const DIALOG_BUTTONS_NONE   = 0;
const DIALOG_BUTTONS_RELOAD = 1;
const DIALOG_BUTTONS_CLOSE  = 2;
const DIALOG_SIZE_LARGE     = 0;
const DIALOG_SIZE_SMALL     = 1;
const DIALOG_SIZE_AUTO      = 2;
function showDialog(title='', text='', controls=false, size=false, monospace=false) {
	showDialogHTML(title, escapeHTML(text), controls, size, monospace);
}
function showDialogAjax(title='', url='', controls=false, size=false, callback=null) {
	ajaxRequest(url, null, function(text) {
		showDialogHTML(title, text, controls, size, false);
		if(callback != undefined && typeof callback == 'function') {
			callback(this.responseText);
		}
	}, false)
}
function showDialogHTML(title='', text='', controls=false, size=false, monospace=false) {
	obj('dialog-container').classList.add('active');
	obj('dialog-title').innerText = title;
	obj('dialog-text').innerHTML = text;
	if(controls == DIALOG_BUTTONS_RELOAD) {
		obj('dialog-controls').style.display = 'flex';
		obj('btnDialogHome').style.visibility = 'visible';
		obj('btnDialogReload').style.visibility = 'visible';
		obj('btnDialogClose').style.visibility = 'visible';
	} else if(controls == DIALOG_BUTTONS_CLOSE) {
		obj('dialog-controls').style.display = 'flex';
		obj('btnDialogHome').style.visibility = 'collapse';
		obj('btnDialogReload').style.visibility = 'collapse';
		obj('btnDialogClose').style.visibility = 'inline-block';
	} else {
		obj('dialog-controls').style.display = 'none';
	}
	obj('dialog-box').className = '';
	if(size == DIALOG_SIZE_LARGE) {
		obj('dialog-box').classList.add('large');
	} else if(size == DIALOG_SIZE_SMALL) {
		obj('dialog-box').classList.add('small');
	}
	if(monospace) {
		obj('dialog-text').classList.add('monospace');
	} else {
		obj('dialog-text').classList.remove('monospace');
	}
	var childs = obj('dialog-text').querySelectorAll('*');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('autofocus'))
			childs[i].focus();
	}
}
function hideDialog() {
	obj('dialog-container').classList.remove('active');
	obj('dialog-title').innerText = '';
	obj('dialog-text').innerHTML = '';
}
function escapeHTML(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

// ======== AJAX OPERATIONS ========
var currentExplorerContentUrl = null;
function ajaxRequest(url, objID, callback, addToHistory=true, showFullscreenLoader=true) {
	let timer = null;
	if(objID == 'explorer-content') {
		currentExplorerContentUrl = url;
		showLoader(true);
		if(showFullscreenLoader) timer = setTimeout(function(){ showLoader2(true) }, 100);
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
					// add to history
					if(addToHistory) rewriteUrlContentParameter(currentExplorerContentUrl);
					// set page title
					let titleObject = obj('page-title');
					if(titleObject != null) document.title = titleObject.innerText;
					else document.title = L__DEFAULT_PAGE_TITLE;
					// init newly loaded tables
					initTableSort()
					initTableSearch()
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.status == 401) {
			window.location.href = 'login.php';
		} else {
			if(this.status == 0) {
				emitMessage(L__NO_CONNECTION_TO_SERVER, L__PLEASE_CHECK_NETWORK, MESSAGE_TYPE_ERROR);
			} else {
				emitMessage(L__ERROR+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR);
			}
		}
		// hide loaders
		if(objID == 'explorer-content') {
			if(showFullscreenLoader) clearTimeout(timer);
			showLoader(false);
			showLoader2(false);
		}
	};
	xhttp.open("GET", url, true);
	xhttp.send();
}
function ajaxRequestPost(url, body, objID, callback, errorCallback) {
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
			if(errorCallback != undefined && typeof errorCallback == 'function') {
				errorCallback(this.status, this.statusText, this.responseText);
			} else {
				emitMessage(L__ERROR+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
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
function showLoader2(state) {
	if(state) {
		document.body.classList.add('loading2');
	} else {
		document.body.classList.remove('loading2');
	}
}

function toggleCheckboxesInContainer(container, checked) {
	let items = container.children;
	for(var i = 0; i < items.length; i++) {
		if(items[i].style.display == 'none') continue;
		let inputs = items[i].getElementsByTagName("input");
		for(var n = 0; n < inputs.length; n++) {
			if(inputs[n].type == 'checkbox') {
				inputs[n].checked = checked;
			}
		}
	}
	refreshDeployCount();
}
function getSelectedCheckBoxValues(checkboxName, attributeName=null, warnIfEmpty=false) {
	var values = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				values.push(entry.value);
			} else {
				values.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(warnIfEmpty && values.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return values;
}
function getAllCheckBoxValues(checkboxName) {
	var values = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		values.push(entry.value);
	});
	return values;
}
function getSelectedSelectBoxValues(selectBoxId, warnIfEmpty=false) {
	var selected = [];
	var items = document.getElementById(selectBoxId);
	for(var i = 0; i < items.length; i++) {
		if(items[i].selected) {
			selected.push(items[i].value);
		}
	}
	if(warnIfEmpty && selected.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return selected;
}
function setInputsDisabled(rootElement, disabled) {
	var elements = rootElement.querySelectorAll('input, select, textarea, button');
	for(var i = 0; i < elements.length; i++) {
		elements[i].disabled = disabled;
	}
	elements = rootElement.querySelectorAll('.box');
	for(var i = 0; i < elements.length; i++) {
		if(disabled) elements[i].classList.add('disabled');
		else elements[i].classList.remove('disabled');
	}
}

// ======== COOKIE HANDLING ========
function setCookie(cookieName, value) {
	const d = new Date();
	d.setTime(d.getTime() + (365*24*60*60*1000));
	let expires = 'expires=' + d.toUTCString();
	document.cookie = cookieName + '=' + value + ';' + expires + ';path=/';
}
function getCookie(cookieName, defaultValue) {
	let name = cookieName + '=';
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for(let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while(c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if(c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return defaultValue;
}

// ======== CONTENT REFRESH FUNCTIONS ========
const REFRESH_SIDEBAR_TIMEOUT = 12000;
const REFRESH_CONTENT_TIMEOUT = 2000;
const COOKIE_SIDEBAR_STATE    = 'sidebar-state';
var refreshContentTimer = null;
var refreshSidebarTimer = null;
var refreshSidebarState = JSON.parse(getCookie(COOKIE_SIDEBAR_STATE, '{}'));
function refreshSidebar(callback=null, handleAutoRefresh=false) {
	// save node expand states
	var elements = obj('explorer-tree').querySelectorAll('.subitems');
	for(var i = 0; i < elements.length; i++) {
		if(elements[i].id) {
			refreshSidebarState[elements[i].id] = elements[i].classList.contains('expanded');
		}
	}
	setCookie(COOKIE_SIDEBAR_STATE, JSON.stringify(refreshSidebarState));
	// do refresh
	ajaxRequest('views/tree.php', 'explorer-tree', function() {
		// execute custom callback
		if(callback != undefined && typeof callback == 'function') callback(text);
		// register events for expand/collapse
		var updateExpandIcon = function(node) {
			var isExpandable = false;
			var isExpanded = false;
			subnodes = node.querySelectorAll(':scope > .subitems');
			for(var n = 0; n < subnodes.length; n++) {
				isExpanded = subnodes[n].classList.contains('expanded');
			}
			imgs = node.querySelectorAll(':scope > a > img');
			for(var n = 0; n < imgs.length; n++) {
				if(node.classList.contains('expandable')) {
					isExpandable = true;
					imgs[n].title = window.L__EXPAND_COLLAPSE_TREE;
					if(isExpanded) imgs[n].src = 'img/collapse.dyn.svg';
					else imgs[n].src = 'img/expand.dyn.svg';
				}
			}
			return isExpandable;
		}
		var showExpandIcon = function(e) {
			updateExpandIcon(e.target.parentElement);
		}
		var hideExpandIcon = function(e) {
			var children = e.target.querySelectorAll(':scope > img');
			for(var n = 0; n < children.length; n++) {
				children[n].src = children[n].getAttribute('originalSrc');
			}
		}
		var expandOrCollapse = function(e) {
			var node = e.target;
			if(e.target.tagName == 'A') node = e.target.parentElement;
			if(e.target.tagName == 'IMG') node = e.target.parentElement.parentElement;
			var isExpanded = null;
			var children = node.querySelectorAll(':scope > .subitems');
			for(var n = 0; n < children.length; n++) {
				isExpanded = children[n].classList.contains('expanded');;
			}
			for(var n = 0; n < children.length; n++) {
				if(isExpanded) children[n].classList.remove('expanded');
				else children[n].classList.add('expanded');
			}
			if(updateExpandIcon(node)) {
				e.preventDefault();
				e.stopPropagation();
			}
		}
		var elements = obj('explorer-tree').querySelectorAll('.node > a, .subnode > a');
		for(var i = 0; i < elements.length; i++) {
			elements[i].onmouseenter = showExpandIcon;
			elements[i].onfocus = showExpandIcon;
			elements[i].onmouseleave = hideExpandIcon;
			elements[i].onblur = hideExpandIcon;
			elements[i].ondblclick = expandOrCollapse;
			children = elements[i].querySelectorAll(':scope > img');
			for(var n = 0; n < children.length; n++) {
				children[n].onclick = expandOrCollapse;
				children[n].setAttribute('originalSrc', children[n].src);
			}
		}
		// schedule next refresh after loading finished
		if(handleAutoRefresh && refreshSidebarTimer != null) {
			refreshSidebarTimer = setTimeout(function(){ refreshSidebar(null, true) }, REFRESH_SIDEBAR_TIMEOUT);
		}
		// restore previous expand states
		for(var key in refreshSidebarState) {
			if(refreshSidebarState[key]) {
				var node = obj(key);
				if(node) node.classList.add('expanded');
			}
		}
	}, false);
}
function refreshContent(callback=null, handleAutoRefresh=false) {
	if(currentExplorerContentUrl == null) return;
	ajaxRequest(currentExplorerContentUrl, 'explorer-content', function(text) {
		// execute custom callback
		if(callback != undefined && typeof callback == 'function') callback(text);
		// schedule next refresh after loading finished
		if(handleAutoRefresh && refreshContentTimer != null) {
			scheduleNextContentRefresh();
		}
	}, false, !handleAutoRefresh);
}
function scheduleNextContentRefresh() {
	refreshContentTimer = setTimeout(function(){ refreshContent(null, true) }, REFRESH_CONTENT_TIMEOUT);
}
function toggleAutoRefresh() {
	if(refreshContentTimer == null) {
		scheduleNextContentRefresh();
		btnRefresh.classList.add('active');
	} else {
		clearTimeout(refreshContentTimer);
		refreshContentTimer = null;
		btnRefresh.classList.remove('active');
	}
}
function refreshContentExplorer(url) {
	ajaxRequest(url, 'explorer-content');
}
function refreshContentPackageNew(name=null, version=null, description=null, install_procedure=null, install_procedure_success_return_codes=null, install_procedure_post_action=null, uninstall_procedure=null, uninstall_procedure_success_return_codes=null, uninstall_procedure_post_action=null, download_for_uninstall=null, compatible_os=null, compatible_os_version=null) {
	ajaxRequest('views/package-new.php?' +
		(name ? '&name='+encodeURIComponent(name) : '') +
		(version ? '&version='+encodeURIComponent(version) : '') +
		(description ? '&description='+encodeURIComponent(description) : '') +
		(install_procedure ? '&install_procedure='+encodeURIComponent(install_procedure) : '') +
		(install_procedure_success_return_codes ? '&install_procedure_success_return_codes='+encodeURIComponent(install_procedure_success_return_codes) : '') +
		(install_procedure_post_action ? '&install_procedure_post_action='+encodeURIComponent(install_procedure_post_action) : '') +
		(uninstall_procedure ? '&uninstall_procedure='+encodeURIComponent(uninstall_procedure) : '') +
		(uninstall_procedure_success_return_codes ? '&uninstall_procedure_success_return_codes='+encodeURIComponent(uninstall_procedure_success_return_codes) : '') +
		(uninstall_procedure_post_action ? '&uninstall_procedure_post_action='+encodeURIComponent(uninstall_procedure_post_action) : '') +
		(download_for_uninstall ? '&download_for_uninstall='+encodeURIComponent(download_for_uninstall) : '') +
		(compatible_os ? '&compatible_os='+encodeURIComponent(compatible_os) : '') +
		(compatible_os_version ? '&compatible_os_version='+encodeURIComponent(compatible_os_version) : ''),
		'explorer-content'
	);
}
function refreshContentDeploy(package_ids=[], package_group_ids=[], computer_ids=[], computer_group_ids=[]) {
	var params = [];
	package_group_ids.forEach(function(entry) {
		params.push({'key':'package_group_id[]', 'value':entry});
	});
	computer_group_ids.forEach(function(entry) {
		params.push({'key':'computer_group_id[]', 'value':entry});
	});
	ajaxRequest('views/deploy.php?'+urlencodeArray(params), 'explorer-content', function(){
		refreshDeployComputerAndPackages(
			getSelectedCheckBoxValues('computer_groups'),
			getSelectedCheckBoxValues('package_groups'),
			computer_ids, package_ids
		);
	});
}

// ======== SEARCH OPERATIONS ========
function doSearch(query) {
	ajaxRequest('views/search.php?query='+encodeURIComponent(query), 'search-results');
	openSearchResults();
}
function closeSearchResults() {
	obj('search-results').classList.remove('visible');
	obj('search-glass').classList.remove('focus');
	obj('explorer').classList.remove('diffuse');
}
function openSearchResults() {
	obj('search-results').classList.add('visible');
	obj('search-glass').classList.add('focus');
	obj('explorer').classList.add('diffuse');
}
function handleSearchResultNavigation(event) {
	if(event.code == 'ArrowDown') focusNextSearchResult();
	else if(event.code == 'ArrowUp') focusNextSearchResult(-1);
}
function focusNextSearchResult(step=1) {
	var links = document.querySelectorAll('#search-results a');
	for(let i=0; i<links.length; i++) {
		if(links[i] === document.activeElement) {
			var next = links[i + step] || links[0];
			next.focus();
			return;
		}
	}
	links[0].focus();
}

// ======== MESSAGE BOX OPERATIONS ========
const MESSAGE_TYPE_INFO    = 'info';
const MESSAGE_TYPE_SUCCESS = 'success';
const MESSAGE_TYPE_WARNING = 'warning';
const MESSAGE_TYPE_ERROR   = 'error';
function emitMessage(title, text, type='info', timeout=8000) {
	var messageBox = document.createElement('div');
	messageBox.classList.add('message');
	messageBox.classList.add('icon');
	messageBox.classList.add(type);
	var messageBoxTitle = document.createElement('div');
	messageBoxTitle.classList.add('message-title');
	messageBoxTitle.innerText = title;
	messageBox.appendChild(messageBoxTitle);
	var messageBoxText = document.createElement('div');
	messageBoxText.innerText = text;
	messageBox.appendChild(messageBoxText);
	var messageBoxClose = document.createElement('button');
	messageBoxClose.classList.add('message-close');
	messageBoxClose.innerText = 'Close';
	messageBoxClose.onclick = function() { messageBox.remove(); };
	messageBox.appendChild(messageBoxClose);
	obj('message-container').prepend(messageBox);
	if(timeout != null) setTimeout(function() { messageBox.remove(); }, timeout);
}

// ======== PACKAGE OPERATIONS ========
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
			var fileName = fleArchive.files[0].name;
			if(fileName.endsWith('.deb')) fileName = fileName.replace('.deb', '');
			newOptions2 += '<option>'+lstUninstallProceduresTemplates.options[i].innerText.replace('[FILENAME]',fileName)+'</option>';
		}
		lstUninstallProcedures.innerHTML = newOptions2;
	}
}
function createPackage(name, version, description, archive, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, uninstall_procedure, uninstall_procedure_success_return_codes, download_for_uninstall, uninstall_procedure_post_action, compatible_os, compatible_os_version) {
	if(typeof archive === 'undefined') {
		if(!confirm(L__CONFIRM_CREATE_EMPTY_PACKAGE)) {
			return;
		}
	}

	setInputsDisabled(frmNewPackage, true);
	btnCreatePackage.style.display = 'none';
	prgPackageUploadContainer.style.display = 'inline-block';

	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('create_package', name);
	formData.append('version', version);
	formData.append('description', description);
	formData.append('archive', archive);
	formData.append('install_procedure', install_procedure);
	formData.append('install_procedure_success_return_codes', install_procedure_success_return_codes);
	formData.append('install_procedure_post_action', install_procedure_post_action);
	formData.append('uninstall_procedure', uninstall_procedure);
	formData.append('uninstall_procedure_success_return_codes', uninstall_procedure_success_return_codes);
	formData.append('download_for_uninstall', download_for_uninstall ? '1' : '0');
	formData.append('uninstall_procedure_post_action', uninstall_procedure_post_action);
	formData.append('compatible_os', compatible_os);
	formData.append('compatible_os_version', compatible_os_version);

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
				var newPackageId = parseInt(this.responseText);
				refreshContentExplorer('views/package-details.php?id='+newPackageId);
				emitMessage(L__PACKAGE_CREATED, name+' ('+version+')', MESSAGE_TYPE_SUCCESS);
				if(newPackageId == 1 || newPackageId % 100 == 0) topConfettiRain();
			} else {
				emitMessage(L__ERROR+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				setInputsDisabled(frmNewPackage, false);
				btnCreatePackage.style.display = 'inline-block';
				prgPackageUploadContainer.style.display = 'none';
			}
		}
	};

	req.open('POST', 'ajax-handler/packages.php');
	req.send(formData);
}
function renamePackageFamily(id, oldValue) {
	var newValue = prompt(L__ENTER_NAME, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_family_id':id, 'update_name':newValue}), null, function() {
			refreshContent();
			emitMessage(L__OBJECT_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function removePackageFamilyIcon(id) {
	if(!confirm(L__ARE_YOU_SURE)) return;
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_family_id':id, 'remove_icon':1}), null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function editPackageFamilyIcon(id, file) {
	if(file.size/1024/1024 > 2/*MiB*/) {
		emitMessage(L__FILE_TOO_BIG, '', MESSAGE_TYPE_ERROR);
		return;
	}

	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('update_package_family_id', id);
	formData.append('update_icon', file);

	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				refreshContent();
				emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(L__ERROR+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR);
			}
		}
	};

	req.open('POST', 'ajax-handler/packages.php');
	req.send(formData);
}
function editPackageFamilyNotes(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_family_id':id, 'update_notes':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageVersion(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_version':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageInstallProcedure(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_install_procedure':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageInstallProcedureSuccessReturnCodes(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_install_procedure_success_return_codes':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageInstallProcedureAction(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_PROCEDURE_POST_ACTION, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_install_procedure_action':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageUninstallProcedure(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_uninstall_procedure':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageUninstallProcedureSuccessReturnCodes(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_uninstall_procedure_success_return_codes':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageUninstallProcedureAction(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_PROCEDURE_POST_ACTION, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_uninstall_procedure_action':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageDownloadForUninstall(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_DOWNLOAD_FOR_UNINSTALL_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_download_for_uninstall':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageNotes(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_note':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageCompatibleOs(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_compatible_os':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editPackageCompatibleOsVersion(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'update_package_id':id, 'update_compatible_os_version':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function reorderPackageInGroup(groupId, oldPos, newPos) {
	var params = [];
	params.push({'key':'move_in_group_id', 'value':groupId});
	params.push({'key':'move_from_pos', 'value':oldPos});
	params.push({'key':'move_to_pos', 'value':newPos});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, refreshContent);
}
function removeSelectedPackage(checkboxName, attributeName=null, event=null) {
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemovePackage(ids, event);
}
function confirmRemovePackage(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_PACKAGE)) {
		ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	removePackageFromGroup(ids, groupId);
}
function removePackageFromGroup(ids, groupId) {
	var params = [];
	params.push({'key':'remove_from_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__OBJECT_REMOVED_FROM_GROUP, '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedPackageFamily(checkboxName, attributeName=null) {
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemovePackageFamily(ids);
}
function confirmRemovePackageFamily(ids, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_package_family_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
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
function createPackageGroup(parent_id=null) {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text) {
			refreshContentExplorer('views/packages.php?id='+parseInt(text));
			refreshSidebar();
			emitMessage(L__GROUP_CREATED, newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renamePackageGroup(id, oldName) {
	var newValue = prompt(L__ENTER_NAME, oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(L__GROUP_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function confirmRemovePackageGroup(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_GROUP)) {
		ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
			refreshContentExplorer('views/packages.php'); refreshSidebar();
			emitMessage(L__GROUP_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function addPackageToGroup(packageId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	packageId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__PACKAGES_ADDED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAddPackageToGroup(id) {
	if(!id) return;
	showDialogAjax(L__PACKAGE_GROUPS, "views/dialog-package-group-add.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditPackageId.value = id;
	});
}
function showDialogAddPackageDependency(id) {
	showDialogAjax(L__ADD_DEPENDENCY, "views/dialog-package-dependency-add.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditPackageId.value = id;
		refreshDeployComputerAndPackages(null, getSelectedCheckBoxValues('packages'));
	});
}
function showDialogAddDependentPackage(id) {
	showDialogAjax(L__ADD_DEPENDENT_PACKAGE, "views/dialog-package-dependency-add.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtSetAsDependentPackage.value = "1";
		txtEditPackageId.value = id;
		refreshDeployComputerAndPackages(null, getSelectedCheckBoxValues('packages'));
	});
}
function addPackageDependency(packageId, dependencyPackageId) {
	if(packageId.length == 0 || dependencyPackageId.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	for(var i = 0; i < packageId.length; i++) {
		for(var n = 0; n < dependencyPackageId.length; n++) {
			var params = [];
			params.push({'key':'update_package_id', 'value':packageId[i]});
			params.push({'key':'add_dependency_package_id', 'value':dependencyPackageId[n]});
			ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
				hideDialog();
				refreshContent();
				emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
			});
		}
	}
}
function removeSelectedPackageDependency(checkboxName, packageId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	removePackageDependency(ids, packageId);
}
function removePackageDependency(ids, packageId) {
	var params = [];
	params.push({'key':'update_package_id', 'value':packageId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_dependency_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedDependentPackages(checkboxName, packageId) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	removeDependentPackages(ids, packageId);
}
function removeDependentPackages(ids, packageId) {
	var params = [];
	params.push({'key':'update_package_id', 'value':packageId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_dependent_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function refreshDeployComputerAndPackages(refreshComputersGroupId=null, refreshPackagesGroupId=null, preselectComputerIds=[], preselectPackageIds=[]) {
	if(refreshComputersGroupId != null) {
		var params = [];
		params.push({'key':'get_computer_group_members', 'value':refreshComputersGroupId});
		preselectComputerIds.forEach(function(entry) {
			params.push({'key':'computer_id[]', 'value':entry});
		});
		ajaxRequest("ajax-handler/deploy.php?"+urlencodeArray(params), 'divComputerList', function() {
			refreshDeployCount();
			// scroll to first checked checkbox
			var childs = divComputerList.querySelectorAll('input[type=checkbox]');
			for(var i = 0; i < childs.length; i++) {
				if(childs[i].checked) {childs[i].scrollIntoView(); break;}
			}
		});
	}
	if(refreshPackagesGroupId != null) {
		var params = [];
		params.push({'key':'get_package_group_members', 'value':refreshPackagesGroupId});
		preselectPackageIds.forEach(function(entry) {
			params.push({'key':'package_id[]', 'value':entry});
		});
		ajaxRequest("ajax-handler/deploy.php?"+urlencodeArray(params), 'divPackageList', function() {
			refreshDeployCount();
			// scroll to first checked checkbox
			var childs = divPackageList.querySelectorAll('input[type=checkbox]');
			for(var i = 0; i < childs.length; i++) {
				if(childs[i].checked) {childs[i].scrollIntoView(); break;}
			}
		});
	}
}
function refreshDeployComputerList(groupId) {
	var values = getSelectedCheckBoxValues('computer_groups');
	if(values.length > 0) {
		divComputerList.innerHTML = '';
		divComputerList.classList.add('disabled');
		refreshDeployCount();
	} else {
		divComputerList.classList.remove('disabled');
		refreshDeployComputerAndPackages(groupId, null);
	}
	txtDeploySearchComputers.value = '';
}
function refreshDeployPackageList(groupId) {
	var values = getSelectedCheckBoxValues('package_groups');
	if(values.length > 0) {
		divPackageList.innerHTML = '';
		divPackageList.classList.add('disabled');
		refreshDeployCount();
	} else {
		divPackageList.classList.remove('disabled');
		refreshDeployComputerAndPackages(null, groupId);
	}
	txtDeploySearchPackages.value = '';
}
function refreshDeployCount() {
	if(obj('spnSelectedComputers')) {
		spnSelectedComputers.innerHTML = getSelectedCheckBoxValues('computers').length;
		spnTotalComputers.innerHTML = getAllCheckBoxValues('computers').length;
	}
	if(obj('spnSelectedPackages')) {
		spnSelectedPackages.innerHTML = getSelectedCheckBoxValues('packages').length;
		spnTotalPackages.innerHTML = getAllCheckBoxValues('packages').length;
	}
	if(obj('spnSelectedComputerGroups')) {
		spnSelectedComputerGroups.innerHTML = getSelectedCheckBoxValues('computer_groups').length;
		spnTotalComputerGroups.innerHTML = getAllCheckBoxValues('computer_groups').length;
	}
	if(obj('spnSelectedPackageGroups')) {
		spnSelectedPackageGroups.innerHTML = getSelectedCheckBoxValues('package_groups').length;
		spnTotalPackageGroups.innerHTML = getAllCheckBoxValues('package_groups').length;
	}
}
function searchItems(container, search) {
	search = search.toUpperCase();
	var items = container.children;
	for(var i = 0; i < items.length; i++) {
		if(search == '' || items[i].innerText.toUpperCase().includes(search))
			items[i].style.display = 'block';
		else
			items[i].style.display = 'none';
	}
}

// ======== COMPUTER OPERATIONS ========
function editComputerNotes(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'update_computer_id':id, 'update_note':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function setComputerForceUpdate(id, value) {
	ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'update_computer_id':id, 'update_force_update':value}), null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function createComputer() {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null) {
		var params = [];
		params.push({'key':'create_computer', 'value':newName});
		var paramString = urlencodeArray(params);
		ajaxRequestPost('ajax-handler/computers.php', paramString, null, function(text) {
			refreshContentExplorer('views/computer-details.php?id='+parseInt(text));
			emitMessage(L__COMPUTER_CREATED, newName, MESSAGE_TYPE_SUCCESS);
		});
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	removeComputerFromGroup(ids, groupId);
}
function removeComputerFromGroup(ids, groupId) {
	var params = [];
	params.push({'key':'remove_from_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_computer_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__OBJECT_REMOVED_FROM_GROUP, '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedComputer(checkboxName, attributeName=null, event=null) {
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveComputer(ids, event);
}
function confirmRemoveComputer(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
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
function renameComputer(id, oldName) {
	var newValue = prompt(L__ENTER_NEW_HOSTNAME, oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'rename_computer_id':id, 'new_name':newValue}), null, function() {
			refreshContent();
			emitMessage(L__OBJECT_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
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
	ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
		emitMessage(L__WOL_SENT, '', MESSAGE_TYPE_SUCCESS);
	});
}
function createComputerGroup(parent_id=null) {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text){
			refreshSidebar(); refreshContentExplorer('views/computers.php?id='+parseInt(text));
			emitMessage(L__GROUP_CREATED, newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameComputerGroup(id, oldName) {
	var newValue = prompt(L__ENTER_NAME, oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(L__GROUP_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function confirmRemoveComputerGroup(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_GROUP)) {
		ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
			refreshContentExplorer('views/computers.php'); refreshSidebar();
			emitMessage(L__GROUP_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function addComputerToGroup(computerId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	computerId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_computer_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__COMPUTER_ADDED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAddComputerToGroup(id) {
	if(!id) return;
	showDialogAjax(L__COMPUTER_GROUPS, "views/dialog-computer-group-add.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditComputerId.value = id;
	});
}

// ======== JOB OPERATIONS ========
function removeSelectedJobContainer(checkboxName, attributeName=null) {
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveJobContainer(ids);
}
function confirmRemoveJobContainer(ids, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_container_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_JOBCONTAINER)) {
		ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
			refreshContentExplorer('views/job-containers.php'); refreshSidebar();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function removeSelectedJob(checkboxName, attributeName=null) {
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveJob(ids);
}
function confirmRemoveJob(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_job_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_JOB)) {
		ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameJobContainer(id, oldName) {
	var newValue = prompt(L__ENTER_NAME, oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({'edit_container_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(L__OBJECT_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editJobContainerStart(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({'edit_container_id':id, 'new_start':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editJobContainerEnd(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({'edit_container_id':id, 'new_end':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editJobContainerSequenceMode(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({'edit_container_id':id, 'new_sequence_mode':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editJobContainerPriority(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({'edit_container_id':id, 'new_priority':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function editJobContainerNotes(id, oldValue) {
	var newValue = prompt(L__ENTER_NEW_VALUE, oldValue);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({'edit_container_id':id, 'new_notes':newValue}), null, function() {
			refreshContent();
			emitMessage(L__SAVED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function deploy(title, start, end, description, computers, computerGroups, packages, packageGroups, useWol, shutdownWakedAfterCompletion, autoCreateUninstallJobs, forceInstallSameVersion, restartTimeout, sequenceMode, priority, constraintIpRange) {
	setInputsDisabled(frmDeploy, true);
	btnDeploy.style.display = 'none';
	prgDeployContainer.style.display = 'flex';

	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('create_install_job_container', title);
	formData.append('date_start', start);
	formData.append('date_end', end);
	formData.append('description', description);
	formData.append('use_wol', useWol ? 1 : 0);
	formData.append('shutdown_waked_after_completion', shutdownWakedAfterCompletion ? 1 : 0);
	formData.append('auto_create_uninstall_jobs', autoCreateUninstallJobs ? 1 : 0);
	formData.append('force_install_same_version', forceInstallSameVersion ? 1 : 0);
	formData.append('restart_timeout', restartTimeout);
	formData.append('sequence_mode', sequenceMode);
	formData.append('priority', priority);
	var ipRanges = constraintIpRange.split(',');
	for(var i = 0; i < ipRanges.length; i++) {
		formData.append('constraint_ip_range[]', ipRanges[i]);
	}
	packages.forEach(function(entry) {
		formData.append('package_id[]', entry);
	});
	packageGroups.forEach(function(entry) {
		formData.append('package_group_id[]', entry);
	});
	computers.forEach(function(entry) {
		formData.append('computer_id[]', entry);
	});
	computerGroups.forEach(function(entry) {
		formData.append('computer_group_id[]', entry);
	});
	req.open('POST', 'ajax-handler/deploy.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				refreshContentExplorer('views/job-containers.php?id='+parseInt(this.responseText));
				refreshSidebar();
				emitMessage(L__JOBS_CREATED, title, MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(L__ERROR+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				setInputsDisabled(frmDeploy, false);
				btnDeploy.style.display = 'inline-block';
				prgDeployContainer.style.display = 'none';
			}
		}
	};
}
function showDialogUninstall() {
	showDialogAjax(L__UNINSTALL_PACKAGES, "views/dialog-uninstall.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function uninstall(checkboxName, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, restartTimeout, priority) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	params.push({'key':'create_uninstall_job_container', 'value':name});
	ids.forEach(function(entry) {
		params.push({'key':'uninstall_package_assignment_id[]', 'value':entry});
	});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'start_time', 'value':startTime});
	params.push({'key':'end_time', 'value':endTime});
	params.push({'key':'use_wol', 'value':useWol ? 1 : 0});
	params.push({'key':'shutdown_waked_after_completion', 'value':shutdownWakedAfterCompletion ? 1 : 0});
	params.push({'key':'restart_timeout', 'value':restartTimeout});
	params.push({'key':'priority', 'value':priority});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/deploy.php', paramString, null, function() {
		hideDialog();
		refreshSidebar(); refreshContent();
		emitMessage(L__JOBS_CREATED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function confirmRemovePackageComputerAssignment(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_package_assignment_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_REMOVE_PACKAGE_ASSIGNMENT)) {
		ajaxRequestPost('ajax-handler/deploy.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogRenewFailedJobs(id, defaultName) {
	showDialogAjax(L__RENEW_FAILED_JOBS, "views/dialog-jobs-renew.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtRenewJobContainerId.value = id;
		txtRenewJobContainerName.value = defaultName;
	});
}
function renewFailedJobsInContainer(id, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, priority) {
	var params = [];
	params.push({'key':'create_renew_job_container', 'value':name});
	params.push({'key':'renew_container_id', 'value':id});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'start_time', 'value':startTime});
	params.push({'key':'end_time', 'value':endTime});
	params.push({'key':'use_wol', 'value':useWol ? 1 : 0});
	params.push({'key':'shutdown_waked_after_completion', 'value':shutdownWakedAfterCompletion ? 1 : 0});
	params.push({'key':'priority', 'value':priority});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/deploy.php', paramString, null, function() {
		hideDialog();
		refreshSidebar(); refreshContent();
		emitMessage(L__JOBS_CREATED, name, MESSAGE_TYPE_SUCCESS);
	});
}

// ======== DOMAIN USER OPERATIONS ========
function confirmRemoveSelectedDomainUser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/domain-users.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

// ======== REPORT OPERATIONS ========
function createReportGroup(parent_id=null) {
	var newValue = prompt(L__ENTER_NAME);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/reports.php', urlencodeObject({'create_group':newValue, 'parent_id':parent_id}), null, function(text) {
			refreshContentExplorer('views/reports.php?id='+parseInt(text));
			refreshSidebar();
			emitMessage(L__GROUP_CREATED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameReportGroup(id, oldName) {
	var newValue = prompt(L__ENTER_NAME, oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/reports.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(L__GROUP_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function confirmRemoveReportGroup(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_GROUP)) {
		ajaxRequestPost('ajax-handler/reports.php', paramString, null, function() {
			refreshContentExplorer('views/reports.php'); refreshSidebar();
			emitMessage(L__GROUP_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogCreateReport(group_id=0) {
	showDialogAjax(L__CREATE_REPORT, "views/dialog-report-create.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtCreateReportGroup.value = group_id
	});
}
function createReport(name, notes, query, group_id=0) {
	var params = [];
	params.push({'key':'create_report', 'value':name});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'query', 'value':query});
	params.push({'key':'group_id', 'value':group_id});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/reports.php', paramString, null, function(text) {
		hideDialog();
		refreshContentExplorer('views/report-details.php?id='+parseInt(text));
		emitMessage(L__REPORT_CREATED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditReport(id, name, notes, query) {
	showDialogAjax(L__EDIT_REPORT, "views/dialog-report-update.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditReportId.value = id;
		txtEditReportName.value = name;
		txtEditReportNotes.value = notes;
		txtEditReportQuery.value = query;
	});
}
function editReport(id, name, notes, query) {
	var params = [];
	params.push({'key':'update_report_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'query', 'value':query});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/reports.php', paramString, null, function(text) {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedReport(checkboxName, attributeName=null) {
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
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveReport(ids);
}
function confirmRemoveReport(ids, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/reports.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function moveReportToGroup(reportId, groupId) {
	var params = [];
	params.push({'key':'move_to_group_id', 'value':groupId});
	reportId.split(',').forEach(function(entry) {
		params.push({'key':'move_to_group_report_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/reports.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogMoveReportToGroup(id) {
	if(!id) return;
	showDialogAjax(L__REPORT_GROUPS, "views/dialog-report-group-move.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditReportId.value = id;
	});
}

// ======== SYSTEM USER OPERATIONS ========
function confirmRemoveSelectedSystemUser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_system_user_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function lockSelectedSystemUser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'lock_system_user_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function unlockSelectedSystemUser(checkboxName) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			ids.push(entry.value);
		}
	});
	if(ids.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'unlock_system_user_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogCreateSystemUser() {
	showDialogAjax(L__CREATE_SYSTEM_USER, "views/dialog-system-user-create.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO)
}
function createSystemUser(username, fullname, description, password, roleId) {
	var params = [];
	params.push({'key':'create_system_user', 'value':username});
	params.push({'key':'fullname', 'value':fullname});
	params.push({'key':'description', 'value':description});
	params.push({'key':'password', 'value':password});
	params.push({'key':'role_id', 'value':roleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__USER_CREATED, username, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditOwnSystemUserPassword() {
	showDialogAjax(
		L__CHANGE_PASSWORD,
		"views/dialog-system-user-update-own-password.php",
		DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO
	);
}
function editOwnSystemUserPassword(oldPassword, newPassword) {
	var params = [];
	params.push({'key':'update_own_system_user_password', 'value':newPassword});
	params.push({'key':'old_password', 'value':oldPassword});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, "", MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUser(id, username, fullname, description, roleId, ldap) {
	showDialogAjax(L__EDIT_USER, "views/dialog-system-user-update.php", DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditSystemUserId.value = id;
		txtEditSystemUserUsername.value = username;
		txtEditSystemUserFullname.value = fullname;
		txtEditSystemUserDescription.value = description;
		sltEditSystemUserRole.value = roleId;
		if(ldap) {
			txtEditSystemUserUsername.readOnly = true;
			txtEditSystemUserFullname.readOnly = true;
			txtEditSystemUserDescription.readOnly = true;
			txtEditSystemUserNewPassword.readOnly = true;
			txtEditSystemUserConfirmNewPassword.readOnly = true;
		}
	});
}
function editSystemUser(id, username, fullname, description, password, roleId) {
	var params = [];
	params.push({'key':'update_system_user_id', 'value':id});
	params.push({'key':'username', 'value':username});
	params.push({'key':'fullname', 'value':fullname});
	params.push({'key':'description', 'value':description});
	params.push({'key':'password', 'value':password});
	params.push({'key':'role_id', 'value':roleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, username, MESSAGE_TYPE_SUCCESS);
	});
}

// ======== SYSTEM OPERATIONS ========
function checkUpdate() {
	ajaxRequestPost('ajax-handler/update-check.php', '', null, function(text) {
		if(text.trim() != '') {
			emitMessage(L__UPDATE_AVAILABLE, text.trim(), MESSAGE_TYPE_INFO);
		}
	});
}
