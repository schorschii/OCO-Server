// ======== GENERAL ========
var draggedElement;
var draggedElementBeginIndex;
function obj(id) {
	return document.getElementById(id);
}
function getChildIndex(node) {
	return Array.prototype.indexOf.call(node.parentNode.childNodes, node);
}
function getCheckedRadioValue(name) {
	// return the LAST checked element (so we can define default via hidden elements)
	var found = null;
	var inputs = document.getElementsByName(name);
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].checked) {
			found = inputs[i].value;
		}
	}
	return found;
}
function setCheckedRadioValue(name, value) {
	var inputs = document.getElementsByName(name);
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].value == value) {
			inputs[i].checked = true;
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

function rewriteUrlContentParameter(ajaxRequestUrl, paramsToReplace={}) {
	// compile parameters to replace from ajax request URL
	var url = new URL(ajaxRequestUrl, location);
	paramsToReplace['view'] = url.pathname.split(/[\\/]/).pop().split('.')[0];
	// replace the params in current URL
	var parameters = [];
	for(const [key, value] of url.searchParams) {
		if(key in paramsToReplace) {
			parameters[key] = paramsToReplace[key];
		} else {
			parameters[key] = value;
		}
	}
	// add missing additional params
	Object.keys(paramsToReplace).forEach(function(key) {
		if(!(key in parameters)) {
			parameters[key] = paramsToReplace[key];
		}
	});
	// add new entry to browser history
	var keyValuePairs = [];
	Object.keys(parameters).forEach(function(key) {
		keyValuePairs.push(
			encodeURIComponent(key)+'='+encodeURIComponent(parameters[key])
		);
	});
	currentExplorerContentUrl = url.pathname+'?'+keyValuePairs.join('&');
	window.history.pushState(
		currentExplorerContentUrl,
		document.title,
		document.location.pathname+'?'+keyValuePairs.join('&')
	);
}
function getCurrentUrlParameter(param) {
	var url = new URL(location);
	for(const [key, value] of url.searchParams) {
		if(key == param) return value;
	}
}
function openTab(tabControl, tabName, forceRefresh=false) {
	var childs = tabControl.querySelectorAll('.tabbuttons > a, .tabcontents > div');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('name') == tabName) {
			childs[i].classList.add('active');
		} else {
			childs[i].classList.remove('active');
		}
	}
	var childs = tabControl.querySelectorAll('.tabadditionals');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('tab') == tabName) {
			childs[i].classList.remove('hidden');
		} else {
			childs[i].classList.add('hidden');
		}
	}
	let refresh = (forceRefresh && getCurrentUrlParameter('tab') != tabName);
	rewriteUrlContentParameter(currentExplorerContentUrl, {'tab':tabName});
	if(refresh) refreshContent();
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
	// show dark background while waiting for response
	obj('dialog-container').classList.add('loading');
	// show loader if request took a little longer (would be annoying if shown directly)
	dialogLoaderTimer = setTimeout(function(){ obj('dialog-container').classList.add('loading2') }, 100);
	// start ajax request
	let finalAction = function() {
		obj('dialog-container').classList.remove('loading');
		obj('dialog-container').classList.remove('loading2');
		clearTimeout(dialogLoaderTimer);
	};
	ajaxRequest(url, null, function(text) {
		showDialogHTML(title, text, controls, size, false);
		if(callback != undefined && typeof callback == 'function') {
			callback(this.responseText);
		}
		finalAction();
	}, false, false, finalAction);
}
function showDialogHTML(title='', text='', controls=false, size=false, monospace=false) {
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
	// make dialog visible
	obj('dialog-container').classList.add('active');
	let animation = obj('dialog-box').animate(
		[ {transform:'scale(102%)'}, {transform:'scale(100%)'} ],
		{ duration: 200, iterations: 1, easing:'ease' }
	);
	// set focus
	animation.onfinish = (event) => {
		var childs = obj('dialog-text').querySelectorAll('*');
		for(var i = 0; i < childs.length; i++) {
			if(childs[i].getAttribute('autofocus')) {
				childs[i].focus();
			}
		}
	};
}
function hideDialog() {
	let animation = obj('dialog-box').animate(
		[ {transform:'scale(100%)'}, {transform:'scale(98%)'} ],
		{ duration: 100, iterations: 1, easing:'linear' }
	);
	animation.onfinish = (event) => {
		obj('dialog-container').classList.remove('active');
		obj('dialog-title').innerText = '';
		obj('dialog-text').innerHTML = '';
	};
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
function ajaxRequest(url, objID, callback, addToHistory=true, showFullscreenLoader=true, errorCallback=null) {
	let timer = null;
	if(objID == 'explorer-content') {
		currentExplorerContentUrl = url;
		showLoader(true);
		// show fullscreen loading animation only if query takes longer than 200ms (otherwise annoying)
		if(showFullscreenLoader) timer = setTimeout(function(){ showLoader2(true) }, 200);
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
			if(errorCallback != undefined && typeof errorCallback == 'function') {
				errorCallback(this.responseText);
			}
		}
		// hide loaders
		if(objID == 'explorer-content') {
			if(showFullscreenLoader) clearTimeout(timer);
			showLoader(false);
			showLoader2(false);
		}
	};
	xhttp.open('GET', url, true);
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
	xhttp.open('POST', url, true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send(body);
}
function urlencodeObject(srcjson) {
	if(typeof srcjson !== 'object') return null;
	var urljson = '';
	var keys = Object.keys(srcjson);
	for(var i=0; i <keys.length; i++){
		urljson += encodeURIComponent(keys[i]) + '=' + encodeURIComponent(srcjson[keys[i]]);
		if(i < (keys.length-1)) urljson+='&';
	}
	return urljson;
}
function urlencodeArray(src) {
	if(!Array.isArray(src)) return null;
	var urljson = '';
	for(var i=0; i <src.length; i++){
		urljson += encodeURIComponent(src[i]['key']) + '=' + encodeURIComponent(src[i]['value']);
		if(i < (src.length-1)) urljson+='&';
	}
	return urljson;
}

function showLoader(state) {
	// decent loading indication (loading cursor)
	if(state) document.body.classList.add('loading');
	else document.body.classList.remove('loading');
}
function showLoader2(state) {
	// blocking loading animation (fullscreen loader)
	if(state) {
		explorer.classList.add('diffuse');
		explorer.classList.add('noresponse');
		header.classList.add('progress');
	} else {
		explorer.classList.remove('diffuse');
		explorer.classList.remove('noresponse');
		header.classList.remove('progress');
	}
}

function toggleCheckboxesInContainer(container, checked) {
	let items = container.children;
	for(var i = 0; i < items.length; i++) {
		if(items[i].style.display == 'none') continue;
		let inputs = items[i].getElementsByTagName('input');
		for(var n = 0; n < inputs.length; n++) {
			if(inputs[n].type == 'checkbox' && !inputs[n].disabled) {
				inputs[n].checked = checked;
			}
		}
	}
}
function getSelectedCheckBoxValues(checkboxName, attributeName=null, warnIfEmpty=false, root=document) {
	var values = [];
	root.querySelectorAll('input').forEach(function(entry) {
		if(entry.name == checkboxName && entry.checked) {
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
function getAllCheckBoxValues(checkboxName, attributeName=null, warnIfEmpty=false, root=document) {
	var values = [];
	root.querySelectorAll('input').forEach(function(entry) {
		if(entry.name == checkboxName) {
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

// ======== CONTENT REFRESH FUNCTIONS ========
const REFRESH_SIDEBAR_TIMEOUT = 12000;
const REFRESH_CONTENT_TIMEOUT = 2000;
const STORAGE_KEY_SIDEBAR_STATE = 'sidebar-state';
var refreshContentTimer = null;
var refreshSidebarTimer = null;
var refreshSidebarState = JSON.parse(localStorage.getItem(STORAGE_KEY_SIDEBAR_STATE));
function refreshSidebar(callback=null, handleAutoRefresh=false) {
	// save node expand states
	if(refreshSidebarState == null) refreshSidebarState = {};
	var elements = obj('explorer-tree').querySelectorAll('.subitems');
	for(var i = 0; i < elements.length; i++) {
		if(elements[i].id) {
			refreshSidebarState[elements[i].id] = elements[i].classList.contains('expanded');
		}
	}
	localStorage.setItem(STORAGE_KEY_SIDEBAR_STATE, JSON.stringify(refreshSidebarState));
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
function refreshContentDeploy(packages=[], packageGroups=[], computers=[], computerGroups=[]) {
	ajaxRequest('views/job-container-new.php', 'explorer-content', function(){
		addToDeployTarget(computerGroups, divTargetComputerList, 'target_computer_groups');
		addToDeployTarget(computers, divTargetComputerList, 'target_computers');
		addToDeployTarget(packageGroups, divTargetPackageList, 'target_package_groups');
		addToDeployTarget(packages, divTargetPackageList, 'target_packages');
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
	let dismissMessage = function() {
		let animation = messageBox.animate(
			[ {opacity:1, transform:'translateX(0)'}, {opacity:0, transform:'translateX(80%)'} ],
			{ duration: 400, iterations: 1, easing:'ease' }
		);
		animation.onfinish = (event) => {
			messageBox.remove();
		};
	};
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
	messageBoxClose.onclick = dismissMessage;
	messageBox.appendChild(messageBoxClose);
	obj('message-container').prepend(messageBox);
	if(timeout != null) setTimeout(dismissMessage, timeout);
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
	btnCreatePackage.classList.add('hidden');
	prgPackageUpload.classList.remove('hidden');

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
				prgPackageUpload.style.setProperty('--progress', '100%');
			} else {
				prgPackageUpload.classList.remove('animated');
				prgPackageUploadText.innerText = progress+'%';
				prgPackageUpload.style.setProperty('--progress', progress+'%');
			}
		} else {
			console.warn('form length is not computable');
			prgPackageUpload.classList.add('animated');
			prgPackageUploadText.innerText = L__IN_PROGRESS;
			prgPackageUpload.style.setProperty('--progress', '100%');
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
				btnCreatePackage.classList.remove('hidden');
				prgPackageUpload.classList.add('hidden');
			}
		}
	};

	req.open('POST', 'ajax-handler/packages.php');
	req.send(formData);
}
function editPackageFamilyIcon(id, file) {
	if(file.size/1024/1024 > 2/*MiB*/) {
		emitMessage(L__FILE_TOO_BIG, '', MESSAGE_TYPE_ERROR);
		return;
	}

	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_package_family_id', id);
	formData.append('icon', file);

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
function removePackageFamilyIcon(id) {
	if(!confirm(L__ARE_YOU_SURE)) return;
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'edit_package_family_id':id, 'remove_icon':1}), null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditPackageFamily(id, name, notes) {
	showDialogAjax(L__EDIT_PACKAGE_FAMILY, 'views/dialog-package-family-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditPackageFamilyId.value = id;
		txtEditPackageFamilyName.value = name;
		txtEditPackageFamilyNotes.value = notes;
	});
}
function editPackageFamily(id, name, notes) {
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'edit_package_family_id':id, 'name':name, 'notes':notes}), null, function() {
		hideDialog(); refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditPackage(id, package_family_id, version, compatible_os, compatible_os_version, notes, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, uninstall_procedure, uninstall_procedure_success_return_codes, uninstall_procedure_post_action, download_for_uninstall) {
	showDialogAjax(L__EDIT_PACKAGE, 'views/dialog-package-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditPackageId.value = id;
		sltEditPackagePackageFamily.value = package_family_id;
		txtEditPackageVersion.value = version;
		txtEditPackageCompatibleOs.value = compatible_os;
		txtEditPackageCompatibleOsVersion.value = compatible_os_version;
		txtEditPackageNotes.value = notes;

		if(install_procedure.includes("\n")) toggleTextBoxMultiLine(txtEditPackageInstallProcedure);
		txtEditPackageInstallProcedure.value = install_procedure;
		txtEditPackageInstallProcedureSuccessReturnCodes.value = install_procedure_success_return_codes;
		setCheckedRadioValue('edit_package_install_procedure_post_action', install_procedure_post_action);

		if(uninstall_procedure.includes("\n")) toggleTextBoxMultiLine(txtEditPackageUninstallProcedure);
		txtEditPackageUninstallProcedure.value = uninstall_procedure;
		txtEditPackageUninstallProcedureSuccessReturnCodes.value = uninstall_procedure_success_return_codes;
		setCheckedRadioValue('edit_package_uninstall_procedure_post_action', uninstall_procedure_post_action);
		chkEditPackageDownloadForUninstall.checked = download_for_uninstall=='1';
	});
}
function editPackage(id, package_family_id, version, compatible_os, compatible_os_version, notes, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, uninstall_procedure, uninstall_procedure_success_return_codes, uninstall_procedure_post_action, download_for_uninstall) {
	var params = [];
	params.push({'key':'edit_package_id', 'value':id});
	params.push({'key':'package_family_id', 'value':package_family_id});
	params.push({'key':'version', 'value':version});
	params.push({'key':'compatible_os', 'value':compatible_os});
	params.push({'key':'compatible_os_version', 'value':compatible_os_version});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'install_procedure', 'value':install_procedure});
	params.push({'key':'install_procedure_success_return_codes', 'value':install_procedure_success_return_codes});
	params.push({'key':'install_procedure_post_action', 'value':install_procedure_post_action});
	params.push({'key':'uninstall_procedure', 'value':uninstall_procedure});
	params.push({'key':'uninstall_procedure_success_return_codes', 'value':uninstall_procedure_success_return_codes});
	params.push({'key':'uninstall_procedure_post_action', 'value':uninstall_procedure_post_action});
	params.push({'key':'download_for_uninstall', 'value':download_for_uninstall?'1':'0'});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, function(text) {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
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
	var ids = getSelectedCheckBoxValues(checkboxName, attributeName, true);
	if(!ids) return;
	// query package display names by IDs
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'get_package_names[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function(text) {
		refreshContentDeploy(JSON.parse(text));
	});
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
	showDialogAjax(L__PACKAGE_GROUPS, 'views/dialog-package-group-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditPackageId.value = id;
	});
}
function showDialogAddPackageDependency(id) {
	showDialogAjax(L__ADD_DEPENDENCY, 'views/dialog-package-dependency-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditPackageId.value = id;
	});
}
function showDialogAddDependentPackage(id) {
	showDialogAjax(L__ADD_DEPENDENT_PACKAGE, 'views/dialog-package-dependency-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtSetAsDependentPackage.value = '1';
		txtEditPackageId.value = id;
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
			params.push({'key':'edit_package_id', 'value':packageId[i]});
			params.push({'key':'add_dependend_package_id', 'value':dependencyPackageId[n]});
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
	params.push({'key':'edit_package_id', 'value':packageId});
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
	params.push({'key':'edit_package_id', 'value':packageId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_dependent_package_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function refreshDeployComputerList(groupId=null, reportId=null) {
	txtDeploySearchComputers.value = '';
	var params = [];
	if(groupId != null) {
		params.push({'key':'get_computer_group_members', 'value':groupId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divComputerList', function(){
			refreshDeployComputerCount();
		});
	}
	else if(reportId != null) {
		params.push({'key':'get_computer_report_results', 'value':reportId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divComputerList', function(){
			refreshDeployComputerCount();
		});
	} else {
		divComputerList.innerHTML = divComputerListHome.innerHTML;
		refreshDeployComputerCount();
	}
}
function refreshDeployComputerCount() {
	spnTotalComputers.innerText = getAllCheckBoxValues('computer_groups', null, false, divComputerList).length
		+ getAllCheckBoxValues('computer_reports', null, false, divComputerList).length
		+ getAllCheckBoxValues('computers', null, false, divComputerList).length;
	spnSelectedComputers.innerText = getSelectedCheckBoxValues('computer_groups', null, false, divComputerList).length
		+ getSelectedCheckBoxValues('computer_reports', null, false, divComputerList).length
		+ getSelectedCheckBoxValues('computers', null, false, divComputerList).length;
}
function refreshDeployPackageList(groupId=null, reportId=null) {
	txtDeploySearchPackages.value = '';
	var params = [];
	if(groupId != null) {
		params.push({'key':'get_package_group_members', 'value':groupId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divPackageList', function(){
			refreshDeployPackageCount();
		});
	}
	else if(reportId != null) {
		params.push({'key':'get_package_report_results', 'value':reportId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divPackageList', function(){
			refreshDeployPackageCount();
		});
	} else {
		divPackageList.innerHTML = divPackageListHome.innerHTML;
		refreshDeployPackageCount();
	}
}
function refreshDeployPackageCount() {
	spnTotalPackages.innerHTML = getAllCheckBoxValues('package_groups', null, false, divPackageList).length
		+ getAllCheckBoxValues('package_reports', null, false, divPackageList).length
		+ getAllCheckBoxValues('packages', null, false, divPackageList).length;
	spnSelectedPackages.innerHTML = getSelectedCheckBoxValues('package_groups', null, false, divPackageList).length
		+ getSelectedCheckBoxValues('package_reports', null, false, divPackageList).length
		+ getSelectedCheckBoxValues('packages', null, false, divPackageList).length;
}
function getSelectedNodes(root, name=null, warnIfEmpty=false) {
	var items = [];
	var elements = root.getElementsByTagName('input')
	for(var i = 0; i < elements.length; i++) {
		if(name == null || name == elements[i].name) {
			if(elements[i].checked) {
				items[elements[i].value] = elements[i].parentNode.innerText;
			}
		}
	}
	if(warnIfEmpty && items.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return items;
}
function addSelectedComputersToDeployTarget() {
	groupItems = getSelectedNodes(divComputerList, 'computer_groups');
	reportItems = getSelectedNodes(divComputerList, 'computer_reports');
	itemItems = getSelectedNodes(divComputerList, 'computers');
	if(groupItems.length + reportItems.length + itemItems.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
	} else {
		addToDeployTarget(groupItems, divTargetComputerList, 'target_computer_groups');
		addToDeployTarget(reportItems, divTargetComputerList, 'target_computer_reports');
		addToDeployTarget(itemItems, divTargetComputerList, 'target_computers');
	}
}
function addSelectedPackagesToDeployTarget() {
	groupItems = getSelectedNodes(divPackageList, 'package_groups');
	reportItems = getSelectedNodes(divPackageList, 'package_reports');
	itemItems = getSelectedNodes(divPackageList, 'packages');
	if(groupItems.length + reportItems.length + itemItems.length == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
	} else {
		addToDeployTarget(groupItems, divTargetPackageList, 'target_package_groups');
		addToDeployTarget(reportItems, divTargetPackageList, 'target_package_reports');
		addToDeployTarget(itemItems, divTargetPackageList, 'target_packages');
	}
}
function addToDeployTarget(items, targetContainer, inputName) {
	for(var key in items) { // check if it is already in target list
		var found = false;
		var elements = targetContainer.getElementsByTagName('input')
		for(var i = 0; i < elements.length; i++) {
			if(elements[i].name == inputName && elements[i].value == key) {
				found = true;
				emitMessage(L__ELEMENT_ALREADY_EXISTS, items[key], MESSAGE_TYPE_WARNING);
				break;
			}
		}
		if(!found) { // add to target list
			var newLabel = document.createElement('label');
			newLabel.classList.add('blockListItem');
			var newCheckbox = document.createElement('input');
			newCheckbox.type = 'checkbox';
			newCheckbox.name = inputName;
			newCheckbox.value = key;
			newLabel.appendChild(newCheckbox);
			var newIcon = document.createElement('img');
			newIcon.draggable = false;
			if(inputName=='target_computer_groups' || inputName=='target_package_groups') {
				newIcon.src = 'img/folder.dyn.svg';
			} else if(inputName=='target_computer_reports' || inputName=='target_package_reports') {
				newIcon.src = 'img/report.dyn.svg';
			} else if(inputName=='target_computers') {
				newIcon.src = 'img/computer.dyn.svg';
			} else if(inputName=='target_packages') {
				newIcon.src = 'img/package.dyn.svg';
			} else {
				newIcon.src = 'img/warning.dyn.svg';
			}
			newLabel.appendChild(newIcon);
			var newContent = document.createTextNode(items[key]);
			newLabel.appendChild(newContent);

			if(inputName=='target_packages' || inputName=='target_package_groups' || inputName=='target_package_reports') {
				newLabel.draggable = true;
				newLabel.ondragstart = function(e) {
					if(e.target.tagName != 'LABEL') return false;
					draggedElement = e.target;
					draggedElementBeginIndex = getChildIndex(e.target);
					return true;
				};
				newLabel.ondragover = function(e) {
					let children = Array.from(e.target.parentNode.children);
					if(!draggedElement || draggedElement.contains(e.target)) return;
					if(children.indexOf(e.target) > children.indexOf(draggedElement)) {
						e.target.after(draggedElement);
					} else {
						e.target.before(draggedElement);
					}
					return true;
				};
				newLabel.ondragend = function(e) {
					//console.log('old: '+draggedElementBeginIndex+', new: '+getChildIndex(draggedElement));
					return true;
				};
				var newDragIcon = document.createElement('img');
				newDragIcon.classList.add('dragicon');
				newDragIcon.src = 'img/list.dyn.svg';
				newDragIcon.draggable = false;
				newLabel.appendChild(newDragIcon);
				newLabel.title = L__CHANGE_ORDER_VIA_DRAG_AND_DROP;
			}
			targetContainer.appendChild(newLabel);
		}
	}
	refreshDeployTargetCount();
}
function removeSelectedTargets(root, warnIfEmpty=true) {
	var count = 0;
	var elements = root.getElementsByTagName('input')
	for(var i = elements.length-1; i > -1; i--) {
		if(elements[i].checked) {
			elements[i].parentNode.remove();
			count ++;
		}
	}
	if(warnIfEmpty && count == 0) {
		emitMessage(L__NO_ELEMENTS_SELECTED, '', MESSAGE_TYPE_WARNING);
		return false;
	}
	refreshDeployTargetCount();
}
function refreshDeployTargetCount() {
	if(obj('spnTotalTargetComputers')) {
		spnTotalTargetComputers.innerHTML = getAllCheckBoxValues('target_computers').length
			+ getAllCheckBoxValues('target_computer_groups').length
			+ getAllCheckBoxValues('target_computer_reports').length;
	}
	if(obj('spnTotalTargetPackages')) {
		spnTotalTargetPackages.innerHTML = getAllCheckBoxValues('target_packages').length
			+ getAllCheckBoxValues('target_package_groups').length
			+ getAllCheckBoxValues('target_package_reports').length;
	}
}
function searchItems(container, search) {
	search = search.toUpperCase();
	var items = container.querySelectorAll('.blockListItem:not(.noSearch)');
	for(var i = 0; i < items.length; i++) {
		if(search == '' || items[i].textContent.toUpperCase().includes(search))
			items[i].style.display = 'block';
		else
			items[i].style.display = 'none';
	}
}

// ======== COMPUTER OPERATIONS ========
function showDialogCreateComputer() {
	showDialogAjax(L__CREATE_COMPUTER, 'views/dialog-computer-create.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function createComputer(hostname, notes, agentKey) {
	var params = [];
	params.push({'key':'create_computer', 'value':hostname});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'agent_key', 'value':agentKey});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/computers.php', paramString, null, function(text) {
		hideDialog();
		refreshContentExplorer('views/computer-details.php?id='+parseInt(text));
		emitMessage(L__COMPUTER_CREATED, hostname, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditComputer(id, hostname, notes) {
	showDialogAjax(L__EDIT_COMPUTER, 'views/dialog-computer-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditComputerId.value = id;
		txtEditComputerHostname.value = hostname;
		txtEditComputerNotes.value = notes;
	});
}
function editComputer(id, hostname, notes) {
	var params = [];
	params.push({'key':'edit_computer_id', 'value':id});
	params.push({'key':'hostname', 'value':hostname});
	params.push({'key':'notes', 'value':notes});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/computers.php', paramString, null, function(text) {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, hostname, MESSAGE_TYPE_SUCCESS);
	});
}
function setComputerForceUpdate(id, value) {
	ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'edit_computer_id':id, 'force_update':value}), null, function() {
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
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
	if(confirm(L__CONFIRM_DELETE_COMPUTER)) {
		ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function deploySelectedComputer(checkboxName, attributeName=null) {
	var ids = getSelectedCheckBoxValues(checkboxName, attributeName, true);
	if(!ids) return;
	// query computer names by IDs
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'get_computer_names[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function(text) {
		refreshContentDeploy([],[],JSON.parse(text));
	});
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
	showDialogAjax(L__COMPUTER_GROUPS, 'views/dialog-computer-group-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditComputerId.value = id;
	});
}

// ======== JOB OPERATIONS ========
function showDialogEditDeploymentRule(id=-1, name='', notes='', enabled=0, computerGroupId=-1, packageGroupId=-1, priority=0, autoUninstall=1) {
	title = L__EDIT_DEPLOYMENT_RULE;
	buttonText = L__CHANGE;
	if(id == -1) {
		title = L__NEW_DEPLOYMENT_RULE;
		buttonText = L__CREATE;
	}
	showDialogAjax(title, 'views/dialog-deployment-rule-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditDeploymentRuleId.value = id;
		txtEditDeploymentRuleName.value = name;
		txtEditDeploymentRuleNotes.value = notes;
		chkEditDeploymentRuleEnabled.checked = enabled=='1';
		sltEditDeploymentRuleComputerGroupId.value = computerGroupId;
		sltEditDeploymentRulePackageGroupId.value = packageGroupId;
		sldEditDeploymentRulePriority.value = priority;
		lblEditDeploymentRulePriorityPreview.innerText = priority;
		chkEditDeploymentRuleAutoUninstall.checked = autoUninstall=='1';
		spnBtnUpdateDeploymentRule.innerText = buttonText;
	});
}
function editDeploymentRule(id, name, notes, enabled, computerGroupId, packageGroupId, priority, autoUninstall) {
	var params = [];
	params.push({'key':'edit_deployment_rule_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'enabled', 'value':enabled?'1':'0'});
	params.push({'key':'computer_group_id', 'value':computerGroupId});
	params.push({'key':'package_group_id', 'value':packageGroupId});
	params.push({'key':'priority', 'value':priority});
	params.push({'key':'auto_uninstall', 'value':autoUninstall?'1':'0'});
	ajaxRequestPost('ajax-handler/deployment-rules.php', urlencodeArray(params), null, function(response) {
		hideDialog();
		if(id == '-1') {
			refreshContentExplorer('views/deployment-rules.php?id='+parseInt(response));
			refreshSidebar();
			emitMessage(L__JOBS_CREATED, name, MESSAGE_TYPE_SUCCESS);
		} else {
			refreshContent(); refreshSidebar();
			emitMessage(L__SAVED, name, MESSAGE_TYPE_SUCCESS);
		}
	});
}
function reevaluateDeploymentRule(deploymentRuleId) {
	var params = [];
	params.push({'key':'evaluate_deployment_rule_id', 'value':deploymentRuleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/deployment-rules.php', paramString, null, function() {
		refreshContent();
		emitMessage(L__REEVALUATED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedDeploymentRule(checkboxName, attributeName=null) {
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
	confirmRemoveDeploymentRule(ids);
}
function confirmRemoveDeploymentRule(ids, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_deployment_rule_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE_DEPLOYMENT_RULE)) {
		ajaxRequestPost('ajax-handler/deployment-rules.php', paramString, null, function() {
			refreshContentExplorer('views/deployment-rules.php'); refreshSidebar();
			emitMessage(L__OBJECT_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogMoveStaticJobToJobContainer(id) {
	if(!id) return;
	showDialogAjax(L__JOB_CONTAINERS, 'views/dialog-jobs-move.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditJobId.value = id;
	});
}
function moveStaticJobToJobContainer(jobId, containerId) {
	if(containerId === false) return;
	var params = [];
	containerId.toString().split(',').forEach(function(entry) {
		params.push({'key':'move_to_container_id[]', 'value':entry});
	});
	jobId.toString().split(',').forEach(function(entry) {
		params.push({'key':'move_to_container_job_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
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
	if(confirm(L__CONFIRM_DELETE_JOB_CONTAINER)) {
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
function showDialogEditJobContainer(id, name, enabled, start, end, sequence_mode, priority, agent_ip_ranges, notes) {
	showDialogAjax(L__EDIT_JOB_CONTAINER, 'views/dialog-job-container-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditJobContainerId.value = id;
		txtEditJobContainerName.value = name;
		chkEditJobContainerEnabled.checked = enabled=='1';
		try {
			let parts = start.split(' ');
			if(parts.length == 2) {
				dteEditJobContainerStart.value = parts[0];
				tmeEditJobContainerStart.value = parts[1];
			}
		} catch(ignored) {}
		try {
			let parts = end.split(' ');
			if(parts.length == 2) {
				dteEditJobContainerEnd.value = parts[0];
				tmeEditJobContainerEnd.value = parts[1];
			}
		} catch(ignored) {}
		chkEditJobContainerSequenceMode.checked = sequence_mode=='1';
		sldEditJobContainerPriority.value = priority;
		lblEditJobContainerPriorityPreview.innerText = priority;
		txtEditJobContainerAgentIpRanges.value = agent_ip_ranges;
		txtEditJobContainerNotes.value = notes;
	});
}
function editJobContainer(id, name, enabled, start, end, sequence_mode, priority, agent_ip_ranges, notes) {
	var params = [];
	params.push({'key':'edit_job_container_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'enabled', 'value':enabled?'1':'0'});
	params.push({'key':'start', 'value':start});
	params.push({'key':'end', 'value':end});
	params.push({'key':'sequence_mode', 'value':sequence_mode?'1':'0'});
	params.push({'key':'priority', 'value':priority});
	params.push({'key':'agent_ip_ranges', 'value':agent_ip_ranges});
	params.push({'key':'notes', 'value':notes});
	ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
		hideDialog();
		refreshContent(); refreshSidebar();
		emitMessage(L__SAVED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function deploy(title, start, end, description, computers, computerGroups, computerReports, packages, packageGroups, packageReports, useWol, shutdownWakedAfterCompletion, autoCreateUninstallJobs, forceInstallSameVersion, restartTimeout, sequenceMode, priority, constraintIpRange) {
	setInputsDisabled(tabControlDeploy, true);
	btnDeploy.classList.add('hidden');
	prgDeploy.classList.remove('hidden');

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
	packageReports.forEach(function(entry) {
		formData.append('package_report_id[]', entry);
	});
	computers.forEach(function(entry) {
		formData.append('computer_id[]', entry);
	});
	computerGroups.forEach(function(entry) {
		formData.append('computer_group_id[]', entry);
	});
	computerReports.forEach(function(entry) {
		formData.append('computer_report_id[]', entry);
	});
	req.open('POST', 'ajax-handler/job-containers.php');
	req.send(formData);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				refreshContentExplorer('views/job-containers.php?id='+parseInt(this.responseText));
				refreshSidebar();
				emitMessage(L__JOBS_CREATED, title, MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(L__ERROR+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				setInputsDisabled(tabControlDeploy, false);
				btnDeploy.classList.remove('hidden');
				prgDeploy.classList.add('hidden');
			}
		}
	};
}
function showDialogUninstall() {
	showDialogAjax(L__UNINSTALL_PACKAGES, 'views/dialog-uninstall.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
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
	ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
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
		ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogRenewFailedStaticJobs(id, defaultName, jobIds) {
	if(!jobIds) return;
	showDialogAjax(L__RENEW_FAILED_JOBS, 'views/dialog-jobs-renew.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtRenewJobContainerId.value = id;
		txtRenewJobContainerName.value = defaultName;
		txtRenewJobContainerJobId.value = jobIds;
	});
}
function renewFailedStaticJobs(id, jobId, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, priority) {
	var params = [];
	params.push({'key':'create_renew_job_container', 'value':name});
	params.push({'key':'job_container_id', 'value':id});
	jobId.toString().split(',').forEach(function(entry) {
		if(entry.trim() != '') params.push({'key':'job_id[]', 'value':entry});
	});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'start_time', 'value':startTime});
	params.push({'key':'end_time', 'value':endTime});
	params.push({'key':'use_wol', 'value':useWol ? 1 : 0});
	params.push({'key':'shutdown_waked_after_completion', 'value':shutdownWakedAfterCompletion ? 1 : 0});
	params.push({'key':'priority', 'value':priority});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
		hideDialog(); refreshSidebar(); refreshContent();
		emitMessage(L__JOBS_CREATED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function renewFailedDynamicJobs(id, jobId) {
	if(!confirm(L__RENEW_FAILED_DEPLOYMENT_RULE_JOBS_NOW)) return;
	var params = [];
	params.push({'key':'renew_deployment_rule', 'value':id});
	jobId.toString().split(',').forEach(function(entry) {
		if(entry.trim() != '') params.push({'key':'job_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/deployment-rules.php', paramString, null, function() {
		refreshSidebar(); refreshContent();
		emitMessage(L__JOBS_RENEWED, '', MESSAGE_TYPE_SUCCESS);
	});
}

// ======== DOMAIN USER OPERATIONS ========
function showDialogEditDomainUserRole(id=-1, name='', permissions='') {
	title = L__EDIT_DOMAIN_USER_ROLE;
	buttonText = L__CHANGE;
	if(id == -1) {
		title = L__CREATE_DOMAIN_USER_ROLE;
		buttonText = L__CREATE;
	}
	showDialogAjax(title, 'views/dialog-domain-user-role-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditDomainUserRoleId.value = id;
		txtEditDomainUserRoleName.value = name;
		txtEditDomainUserRolePermissions.value = permissions;
		spnBtnUpdateDomainUserRole.innerText = buttonText;
	});
}
function editDomainUserRole(id, name, permissions) {
	var params = [];
	params.push({'key':'edit_domain_user_role_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'permissions', 'value':permissions});
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function(response) {
		hideDialog(); refreshContent();
		emitMessage(L__SAVED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function confirmRemoveSelectedDomainUserRole(checkboxName) {
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
		params.push({'key':'remove_domain_user_role_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogEditDomainUser(id=-1, uid='', username='', displayName='', roleId=-1, ldap=0) {
	title = L__EDIT_DOMAIN_USER;
	buttonText = L__CHANGE;
	if(id == -1) {
		title = L__CREATE_DOMAIN_USER;
		buttonText = L__CREATE;
	}
	showDialogAjax(title, 'views/dialog-domain-user-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditDomainUserId.value = id;
		txtEditDomainUserUid.value = uid;
		txtEditDomainUserUsername.value = username;
		txtEditDomainUserDisplayName.value = displayName;
		sltEditDomainUserRole.value = roleId;
		if(ldap) {
			txtEditDomainUserNewPassword.readOnly = true;
			txtEditDomainUserConfirmNewPassword.readOnly = true;
			sltEditDomainUserRole.disabled = true;
		}
		spnBtnEditDomainUser.innerText = buttonText;
	});
}
function editDomainUser(id, username, password, roleId) {
	var params = [];
	params.push({'key':'edit_domain_user_id', 'value':id});
	params.push({'key':'password', 'value':password});
	params.push({'key':'domain_user_role_id', 'value':roleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog(); refreshContent();
		emitMessage(L__SAVED, username, MESSAGE_TYPE_SUCCESS);
	});
}
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
	showDialogAjax(L__CREATE_REPORT, 'views/dialog-report-create.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
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
	showDialogAjax(L__EDIT_REPORT, 'views/dialog-report-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditReportId.value = id;
		txtEditReportName.value = name;
		txtEditReportNotes.value = notes;
		txtEditReportQuery.value = query;
	});
}
function editReport(id, name, notes, query) {
	var params = [];
	params.push({'key':'edit_report_id', 'value':id});
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
	showDialogAjax(L__REPORT_GROUPS, 'views/dialog-report-group-move.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
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
function showDialogEditOwnSystemUserPassword() {
	showDialogAjax(L__CHANGE_PASSWORD, 'views/dialog-system-user-edit-own-password.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editOwnSystemUserPassword(oldPassword, newPassword) {
	var params = [];
	params.push({'key':'edit_own_system_user_password', 'value':newPassword});
	params.push({'key':'old_password', 'value':oldPassword});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUser(id=-1, uid='', username='', displayName='', description='', roleId=-1, ldap=0) {
	title = L__EDIT_SYSTEM_USER;
	buttonText = L__CHANGE;
	if(id == -1) {
		title = L__CREATE_SYSTEM_USER;
		buttonText = L__CREATE;
	}
	showDialogAjax(title, 'views/dialog-system-user-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditSystemUserId.value = id;
		txtEditSystemUserUid.value = uid;
		txtEditSystemUserUsername.value = username;
		txtEditSystemUserDisplayName.value = displayName;
		txtEditSystemUserDescription.value = description;
		sltEditSystemUserRole.value = roleId;
		if(ldap) {
			txtEditSystemUserUsername.readOnly = true;
			txtEditSystemUserDisplayName.readOnly = true;
			txtEditSystemUserDescription.readOnly = true;
			txtEditSystemUserNewPassword.readOnly = true;
			txtEditSystemUserConfirmNewPassword.readOnly = true;
			sltEditSystemUserRole.disabled = true;
		}
		spnBtnEditSystemUser.innerText = buttonText;
	});
}
function editSystemUser(id, username, displayName, description, password, roleId) {
	var params = [];
	params.push({'key':'edit_system_user_id', 'value':id});
	params.push({'key':'username', 'value':username});
	params.push({'key':'display_name', 'value':displayName});
	params.push({'key':'description', 'value':description});
	params.push({'key':'password', 'value':password});
	params.push({'key':'system_user_role_id', 'value':roleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(L__SAVED, username, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUserRole(id=-1, name='', permissions='') {
	title = L__EDIT_SYSTEM_USER_ROLE;
	buttonText = L__CHANGE;
	if(id == -1) {
		title = L__CREATE_SYSTEM_USER_ROLE;
		buttonText = L__CREATE;
	}
	showDialogAjax(title, 'views/dialog-system-user-role-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditSystemUserRoleId.value = id;
		txtEditSystemUserRoleName.value = name;
		txtEditSystemUserRolePermissions.value = permissions;
		spnBtnUpdateSystemUserRole.innerText = buttonText;
	});
}
function editSystemUserRole(id, name, permissions) {
	var params = [];
	params.push({'key':'edit_system_user_role_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'permissions', 'value':permissions});
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function(response) {
		hideDialog(); refreshContent();
		emitMessage(L__SAVED, name, MESSAGE_TYPE_SUCCESS);
	});
}
function confirmRemoveSelectedSystemUserRole(checkboxName) {
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
		params.push({'key':'remove_system_user_role_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

// ======== SYSTEM OPERATIONS ========
function ldapSyncSystemUsers() {
	var params = [];
	params.push({'key':'ldap_sync_system_users', 'value':1});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function(text) {
		refreshContent();
		emitMessage(L__LDAP_SYNC, text, MESSAGE_TYPE_SUCCESS);
	});
}
function ldapSyncDomainUsers() {
	var params = [];
	params.push({'key':'ldap_sync_domain_users', 'value':1});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function(text) {
		refreshContent();
		emitMessage(L__LDAP_SYNC, text, MESSAGE_TYPE_SUCCESS);
	});
}
function checkUpdate() {
	ajaxRequestPost('ajax-handler/update-check.php', '', null, function(text) {
		if(text.trim() != '') {
			emitMessage(L__UPDATE_AVAILABLE, text.trim(), MESSAGE_TYPE_INFO);
		}
	});
}
