// ======== GENERAL ========
var draggedElement;
var draggedElementBeginIndex;
function obj(id) {
	return document.getElementById(id);
}
function toClipboard(text, info=null) {
	navigator.clipboard.writeText(text);
	emitMessage(LANG['copied_to_clipboard'], info?info:text, MESSAGE_TYPE_INFO, 2000);
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
function toggleInputDirectory(element, sender=null) {
	if(element.getAttribute('webkitdirectory') != 'true') {
		element.setAttribute('webkitdirectory', true);
		element.setAttribute('directory', true);
		if(sender) sender.querySelectorAll('img')[0].src = 'img/folder.dyn.svg';
	} else {
		element.removeAttribute('webkitdirectory');
		element.removeAttribute('directory');
		if(sender) sender.querySelectorAll('img')[0].src = 'img/files.dyn.svg';
	}
}

function rewriteUrlContentParameter(paramsToReplace={}, refresh=false) {
	// compile parameters to replace from ajax request URL
	var url = new URL(currentExplorerContentUrl, location);
	paramsToReplace['view'] = url.pathname.split(/[\\/]/).pop().split('.')[0];
	// replace the params in current URL
	var parameters = [];
	for(const [key, value] of url.searchParams) {
		if(key in paramsToReplace) {
			if(paramsToReplace[key] !== null) {
				parameters[key] = paramsToReplace[key];
			}
		} else {
			parameters[key] = value;
		}
	}
	// add missing additional params
	Object.keys(paramsToReplace).forEach(function(key) {
		if(!(key in parameters)) {
			if(paramsToReplace[key] !== null) {
				parameters[key] = paramsToReplace[key];
			}
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
	// reload content with new query parameters
	if(refresh) refreshContent();
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
	rewriteUrlContentParameter({'tab':tabName});
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
	// ESC - hide dialog
	if((event.which || event.keyCode) == 27) {
		hideDialog();
	}
};

// ======== DIALOG ========
const DIALOG_BUTTONS_NONE   = 0;
const DIALOG_BUTTONS_CLOSE  = 1;
const DIALOG_SIZE_LARGE     = 0;
const DIALOG_SIZE_SMALL     = 1;
const DIALOG_SIZE_AUTO      = 2;
function showDialog(title='', text='', controls=false, size=false, monospace=false, loading=false) {
	showDialogHTML(title, escapeHTML(text), controls, size, monospace, loading);
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
		if(callback && typeof callback == 'function') {
			callback(this.responseText);
		}
		finalAction();
	}, false, false, finalAction);
}
function showDialogHTML(title='', text='', controls=false, size=false, monospace=false, loading=false) {
	obj('dialog-title').innerText = title;
	obj('dialog-text').innerHTML = text;
	// buttons
	obj('btnDialogClose').style.visibility = 'collapse';
	if(controls == DIALOG_BUTTONS_CLOSE) {
		obj('btnDialogClose').style.visibility = 'visible';
	}
	// size
	obj('dialog-box').className = '';
	if(size == DIALOG_SIZE_LARGE) {
		obj('dialog-box').classList.add('large');
	} else if(size == DIALOG_SIZE_SMALL) {
		obj('dialog-box').classList.add('small');
	}
	// font
	if(monospace) {
		obj('dialog-text').classList.add('monospace');
	} else {
		obj('dialog-text').classList.remove('monospace');
	}
	// loading animation
	if(loading) {
		var img = document.createElement('img');
		img.src = 'img/loader-dots.svg';
		img.style = 'display:block';
		obj('dialog-text').appendChild(img);
	}
	// make dialog visible
	obj('dialog-container').classList.add('active');
	let animation = obj('dialog-box').animate(
		[ {transform:'scale(102%)'}, {transform:'scale(100%)'} ],
		{ duration: 200, iterations: 1, easing:'ease' }
	);
	// set focus
	animation.onfinish = (event) => {
		setAutofocus(obj('dialog-text'));
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
function setAutofocus(container) {
	var childs = container.querySelectorAll('*');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('autofocus')) {
			childs[i].focus();
			break;
		}
	}
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
var lastExplorerTreeContent = '';
function ajaxRequest(url, objID, callback, addToHistory=true, showFullscreenLoader=true, errorCallback=null) {
	let timer = null;
	if(objID == 'explorer-content') {
		currentExplorerContentUrl = url;
		showLoader(true);
		// show fullscreen loading animation only if query takes longer than 200ms (otherwise annoying)
		if(showFullscreenLoader) timer = setTimeout(showLoader2, 200, true);
	}
	var xhttp = new XMLHttpRequest();
	xhttp.userCancelled = false;
	xhttp.onreadystatechange = function() {
		if(this.readyState != 4) {
			return;
		}
		if(this.status == 200) {
			var object = obj(objID);
			if(object != null) {
				if(objID == 'explorer-tree') {
					// only update content if new content differs to avoid page jumps
					// this info must be stored in a sparate variable since we manipulate classes to restore tree view expanded/collapsed states
					if(lastExplorerTreeContent != this.responseText) {
						object.innerHTML = this.responseText;
						lastExplorerTreeContent = this.responseText;
						initLinks(object);
					}
				} else {
					object.innerHTML = this.responseText;
					if(objID == 'explorer-content') {
						// add to history
						if(addToHistory) rewriteUrlContentParameter();
						// set page title
						let titleObject = obj('page-title');
						if(titleObject != null) document.title = titleObject.innerText;
						else document.title = LANG['app_name'];
						// init newly loaded tables
						initTables(object);
						setAutofocus(object);
					}
					initLinks(object);
				}
				// execute inline scripts
				var scripts = object.getElementsByTagName('script');
				for(var i = 0; i < scripts.length; i++) {
					eval(scripts[i].innerHTML);
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.status == 401) {
			let currentUrl = new URL(window.location.href);
			window.location.href = 'login.php?redirect='+encodeURIComponent(currentUrl.pathname+currentUrl.search);
		} else {
			if(!this.userCancelled) {
				if(this.status == 0) {
					emitMessage(LANG['no_connection_to_server'], LANG['please_check_network'], MESSAGE_TYPE_ERROR);
				} else {
					emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR);
				}
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
	return xhttp;
}
function ajaxRequestPost(url, body, objID, callback, errorCallback) {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			var object = obj(objID);
			if(object != null) {
				object.innerHTML = this.responseText;
				if(objID == 'explorer-content') {
					initTables(object) // init newly loaded tables
				}
			}
			if(callback != undefined && typeof callback == 'function') {
				callback(this.responseText);
			}
		} else if(this.readyState == 4) {
			if(errorCallback != undefined && typeof errorCallback == 'function') {
				errorCallback(this.status, this.statusText, this.responseText);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	xhttp.open('POST', url, true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send(body);
	return xhttp;
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
function initLinks(root) {
	var links = root.querySelectorAll('a');
	for(var i = 0; i < links.length; i++) {
		var linkUrl = links[i].getAttribute('href');
		if(linkUrl == null || !linkUrl.startsWith('index.php?view=')) continue;
		// open explorer-content links via AJAX, do not reload the complete page
		links[i].addEventListener('click', function(e) {
			e.preventDefault();
			toggleAutoRefresh(false);
			var urlParams = new URLSearchParams(this.getAttribute('href').split('?')[1]);
			var ajaxUrlParams = [];
			for(const entry of urlParams.entries()) {
				ajaxUrlParams.push(encodeURIComponent(entry[0])+'='+encodeURIComponent(entry[1]));
			}
			refreshContentExplorer('views/'+encodeURIComponent(urlParams.get('view'))+'.php?'+ajaxUrlParams.join('&'));
		});
	}
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
	let elements = obj('explorer-tree').querySelectorAll('.node, .subnode');
	for(let i = 0; i < elements.length; i++) {
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
		let setupExpandIcon = function(node) {
			let isExpandable = false;
			let imgs = node.querySelectorAll(':scope > a > img');
			for(let n = 0; n < imgs.length; n++) {
				if(node.classList.contains('expandable')) {
					isExpandable = true;
					imgs[n].title = LANG['expand_or_collapse_tree'];
				}
			}
			return isExpandable;
		}
		let expandOrCollapse = function(e) {
			let node = e.target;
			if(e.target.tagName == 'A') node = e.target.parentElement;
			if(e.target.tagName == 'IMG') node = e.target.parentElement.parentElement;
			node.classList.toggle('expanded');
			if(setupExpandIcon(node)) {
				e.preventDefault();
				e.stopPropagation();
			}
		}
		let elements = obj('explorer-tree').querySelectorAll('.node > a, .subnode > a');
		for(let i = 0; i < elements.length; i++) {
			elements[i].ondblclick = expandOrCollapse;
			elements[i].onkeypress = function(e){
				if(e.code == 'Space') expandOrCollapse(e);
			};
			let children = elements[i].querySelectorAll(':scope > img');
			if(children.length) children[0].onclick = expandOrCollapse;
		}
		// schedule next refresh after loading finished
		if(handleAutoRefresh && refreshSidebarTimer != null) {
			refreshSidebarTimer = setTimeout(refreshSidebar, REFRESH_SIDEBAR_TIMEOUT, null, true);
		}
		// restore previous expand states
		for(let key in refreshSidebarState) {
			if(refreshSidebarState[key]) {
				let node = obj(key);
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
	if(refreshContentTimer) {
		clearTimeout(refreshContentTimer);
	}
	refreshContentTimer = setTimeout(refreshContent, REFRESH_CONTENT_TIMEOUT, null, true);
}
function toggleAutoRefresh(force=null) {
	let newState = (refreshContentTimer == null);
	if(force != null) newState = force;
	if(newState) {
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
function refreshContentPackageNew(name=null, version=null, license_count=null, description=null, install_procedure=null, install_procedure_success_return_codes=null, install_procedure_post_action=null, upgrade_behavior=null, uninstall_procedure=null, uninstall_procedure_success_return_codes=null, uninstall_procedure_post_action=null, download_for_uninstall=null, compatible_os=null, compatible_os_version=null, compatible_architecture=null) {
	toggleAutoRefresh(false);
	ajaxRequest('views/package-new.php?' +
		(name ? '&name='+encodeURIComponent(name) : '') +
		(version ? '&version='+encodeURIComponent(version) : '') +
		(license_count&&license_count>=0 ? '&license_count='+encodeURIComponent(license_count) : '') +
		(description ? '&description='+encodeURIComponent(description) : '') +
		(install_procedure ? '&install_procedure='+encodeURIComponent(install_procedure) : '') +
		(install_procedure_success_return_codes ? '&install_procedure_success_return_codes='+encodeURIComponent(install_procedure_success_return_codes) : '') +
		(install_procedure_post_action ? '&install_procedure_post_action='+encodeURIComponent(install_procedure_post_action) : '') +
		(upgrade_behavior ? '&upgrade_behavior='+encodeURIComponent(upgrade_behavior) : '') +
		(uninstall_procedure ? '&uninstall_procedure='+encodeURIComponent(uninstall_procedure) : '') +
		(uninstall_procedure_success_return_codes ? '&uninstall_procedure_success_return_codes='+encodeURIComponent(uninstall_procedure_success_return_codes) : '') +
		(uninstall_procedure_post_action ? '&uninstall_procedure_post_action='+encodeURIComponent(uninstall_procedure_post_action) : '') +
		(download_for_uninstall ? '&download_for_uninstall='+encodeURIComponent(download_for_uninstall) : '') +
		(compatible_os ? '&compatible_os='+encodeURIComponent(compatible_os) : '') +
		(compatible_os_version ? '&compatible_os_version='+encodeURIComponent(compatible_os_version) : '') +
		(compatible_architecture ? '&compatible_architecture='+encodeURIComponent(compatible_architecture) : ''),
		'explorer-content'
	);
}
function refreshContentDeploy(packages=[], packageGroups=[], computers=[], computerGroups=[]) {
	toggleAutoRefresh(false);
	ajaxRequest('views/job-container-new.php', 'explorer-content', function(){
		addToDeployTarget(computerGroups, divTargetComputerList, 'target_computer_groups');
		addToDeployTarget(computers, divTargetComputerList, 'target_computers');
		addToDeployTarget(packageGroups, divTargetPackageList, 'target_package_groups');
		addToDeployTarget(packages, divTargetPackageList, 'target_packages');
	});
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
	var messageBoxContent = document.createElement('div');
	messageBoxContent.classList.add('message-content');
	messageBox.appendChild(messageBoxContent);
	var messageBoxTitle = document.createElement('div');
	messageBoxTitle.classList.add('message-title');
	messageBoxTitle.innerText = title;
	messageBoxContent.appendChild(messageBoxTitle);
	var messageBoxText = document.createElement('div');
	messageBoxText.innerText = text;
	messageBoxContent.appendChild(messageBoxText);
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
	if(typeof lstInstallProceduresTemplates === 'undefined') return;
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
function createPackage(name, version, license_count, notes, archive, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, upgrade_behavior, uninstall_procedure, uninstall_procedure_success_return_codes, download_for_uninstall, uninstall_procedure_post_action, compatible_os, compatible_os_version, compatible_architecture) {
	if(typeof archive === 'undefined' || archive.length == 0) {
		if(!confirm(LANG['confirm_create_empty_package'])) {
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
	formData.append('license_count', license_count);
	formData.append('notes', notes);
	for(i = 0; i <= archive.length; i++) {
		formData.append('archive[]', archive[i]);
	}
	formData.append('install_procedure', install_procedure);
	formData.append('install_procedure_success_return_codes', install_procedure_success_return_codes);
	formData.append('install_procedure_post_action', install_procedure_post_action);
	formData.append('upgrade_behavior', upgrade_behavior);
	formData.append('uninstall_procedure', uninstall_procedure);
	formData.append('uninstall_procedure_success_return_codes', uninstall_procedure_success_return_codes);
	formData.append('download_for_uninstall', download_for_uninstall ? '1' : '0');
	formData.append('uninstall_procedure_post_action', uninstall_procedure_post_action);
	formData.append('compatible_os', compatible_os);
	formData.append('compatible_os_version', compatible_os_version);
	formData.append('compatible_architecture', compatible_architecture);

	req.upload.onprogress = function(evt) {
		if(evt.lengthComputable) {
			var progress = Math.ceil((evt.loaded / evt.total) * 100);
			if(progress == 100) {
				prgPackageUpload.classList.add('animated');
				prgPackageUploadText.innerText = LANG['in_progress'];
				prgPackageUpload.style.setProperty('--progress', '100%');
			} else {
				prgPackageUpload.classList.remove('animated');
				prgPackageUploadText.innerText = progress+'%';
				prgPackageUpload.style.setProperty('--progress', progress+'%');
			}
		} else {
			console.warn('form length is not computable');
			prgPackageUpload.classList.add('animated');
			prgPackageUploadText.innerText = LANG['in_progress'];
			prgPackageUpload.style.setProperty('--progress', '100%');
		}
	};
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				var newPackageId = parseInt(this.responseText);
				refreshContentExplorer('views/package-details.php?id='+newPackageId);
				emitMessage(LANG['package_created'], name+' ('+version+')', MESSAGE_TYPE_SUCCESS);
				if(newPackageId == 1 || newPackageId % 100 == 0) {
					fireworkConfetti();
					emitMessage(LANG['congratulations_package_placeholder'].replace('%',newPackageId), '', MESSAGE_TYPE_INFO);
				}
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
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
		emitMessage(LANG['file_too_big'], '', MESSAGE_TYPE_ERROR);
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
				emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR);
			}
		}
	};

	req.open('POST', 'ajax-handler/packages.php');
	req.send(formData);
}
function removePackageFamilyIcon(id) {
	if(!confirm(LANG['are_you_sure'])) return;
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'edit_package_family_id':id, 'remove_icon':1}), null, function() {
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditPackageFamily(id, name, license_count, notes) {
	showDialogAjax(LANG['edit_package_family'], 'views/dialog-package-family-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditPackageFamilyId.value = id;
		txtEditPackageFamilyName.value = name;
		if(license_count >= 0) {
			txtEditPackageFamilyLicenseCount.value = license_count;
		}
		txtEditPackageFamilyNotes.value = notes;
	});
}
function editPackageFamily(id, name, license_count, notes) {
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({
		'edit_package_family_id':id,
		'name':name,
		'license_count':license_count,
		'notes':notes
	}), null, function() {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditPackage(id) {
	showDialogAjax(LANG['edit_package'], 'views/dialog-package-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editPackage(id, package_family_id, version, compatible_os, compatible_os_version, compatible_architecture, license_count, notes, archive, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, upgrade_behavior, uninstall_procedure, uninstall_procedure_success_return_codes, uninstall_procedure_post_action, download_for_uninstall) {
	let req = new XMLHttpRequest();
	let formData = new FormData();

	if(archive !== null) {
		if(archive.length == 0) {
			if(!confirm(LANG['confirm_create_empty_package'])) {
				return;
			}
		}
		for(i = 0; i <= archive.length; i++) {
			formData.append('archive[]', archive[i]);
		}
		formData.append('update_archive', '1');
	}

	setInputsDisabled(frmEditPackage, true);
	btnEditPackage.classList.add('hidden');
	btnCloseDialog.classList.add('hidden');
	prgPackageUpload.classList.remove('hidden');

	formData.append('edit_package_id', id);
	formData.append('package_family_id', package_family_id);
	formData.append('version', version);
	formData.append('compatible_os', compatible_os);
	formData.append('compatible_os_version', compatible_os_version);
	formData.append('compatible_architecture', compatible_architecture);
	formData.append('license_count', license_count);
	formData.append('notes', notes);
	formData.append('install_procedure', install_procedure);
	formData.append('install_procedure_success_return_codes', install_procedure_success_return_codes);
	formData.append('install_procedure_post_action', install_procedure_post_action);
	formData.append('upgrade_behavior', upgrade_behavior);
	formData.append('uninstall_procedure', uninstall_procedure);
	formData.append('uninstall_procedure_success_return_codes', uninstall_procedure_success_return_codes);
	formData.append('uninstall_procedure_post_action', uninstall_procedure_post_action);
	formData.append('download_for_uninstall', download_for_uninstall?'1':'0');

	req.upload.onprogress = function(evt) {
		if(evt.lengthComputable) {
			var progress = Math.ceil((evt.loaded / evt.total) * 100);
			if(progress == 100) {
				prgPackageUpload.classList.add('animated');
				prgPackageUploadText.innerText = LANG['in_progress'];
				prgPackageUpload.style.setProperty('--progress', '100%');
			} else {
				prgPackageUpload.classList.remove('animated');
				prgPackageUploadText.innerText = progress+'%';
				prgPackageUpload.style.setProperty('--progress', progress+'%');
			}
		} else {
			console.warn('form length is not computable');
			prgPackageUpload.classList.add('animated');
			prgPackageUploadText.innerText = LANG['in_progress'];
			prgPackageUpload.style.setProperty('--progress', '100%');
		}
	};
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				setInputsDisabled(frmEditPackage, false);
				btnEditPackage.classList.remove('hidden');
				btnCloseDialog.classList.remove('hidden');
				prgPackageUpload.classList.add('hidden');
			}
		}
	};

	req.open('POST', 'ajax-handler/packages.php');
	req.send(formData);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemovePackage(ids, event);
}
function confirmRemovePackage(ids, event=null, infoText='', redirect=null, installedOnComputers=null) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm( (installedOnComputers != null ? LANG['package_is_installed_on_computers'].replace('%1',installedOnComputers)+' ' : '') + LANG['confirm_delete_package']) ) {
		ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemovePackageFamily(ids);
}
function confirmRemovePackageFamily(ids, infoText='', redirect=null) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_package_family_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
	var newName = prompt(LANG['enter_name']);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text) {
			refreshContentExplorer('views/packages.php?id='+parseInt(text));
			refreshSidebar();
			emitMessage(LANG['group_created'], newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renamePackageGroup(id, oldName) {
	var newValue = prompt(LANG['enter_name'], oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['group_renamed'], newValue, MESSAGE_TYPE_SUCCESS);
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/packages.php', paramString, null, function() {
			refreshContentExplorer('views/packages.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['packages_added'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAddPackageToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['package_groups'], 'views/dialog-package-group-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditPackageId.value = id;
	});
}
function showDialogAddPackageDependency(id) {
	showDialogAjax(LANG['add_dependency'], 'views/dialog-package-dependency-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditPackageId.value = id;
	});
}
function showDialogAddDependentPackage(id) {
	showDialogAjax(LANG['add_dependent_package'], 'views/dialog-package-dependency-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtSetAsDependentPackage.value = '1';
		txtEditPackageId.value = id;
	});
}
function addPackageDependency(packageId, dependencyPackageId) {
	if(packageId.length == 0 || dependencyPackageId.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
				emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function refreshDeployComputerList(groupId=null, reportId=null) {
	txtDeploySearchComputers.value = '';
	var params = [];
	if(groupId != null) {
		params.push({'key':'get_computer_group_members', 'value':groupId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divComputerList', function(){
			txtDeploySearchComputers.focus();
			refreshDeployComputerCount();
		});
	}
	else if(reportId != null) {
		params.push({'key':'get_computer_report_results', 'value':reportId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divComputerList', function(){
			txtDeploySearchComputers.focus();
			refreshDeployComputerCount();
		});
	} else {
		divComputerList.innerHTML = divComputerListHome.innerHTML;
		txtDeploySearchComputers.focus();
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
			txtDeploySearchPackages.focus();
			refreshDeployPackageCount();
		});
	}
	else if(reportId != null) {
		params.push({'key':'get_package_report_results', 'value':reportId});
		ajaxRequest('ajax-handler/job-containers.php?'+urlencodeArray(params), 'divPackageList', function(){
			txtDeploySearchPackages.focus();
			refreshDeployPackageCount();
		});
	} else {
		divPackageList.innerHTML = divPackageListHome.innerHTML;
		txtDeploySearchPackages.focus();
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
				items.push({'id':elements[i].value, 'name':elements[i].parentNode.innerText});
			}
		}
	}
	if(warnIfEmpty && items.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return false;
	}
	return items;
}
function addSelectedComputersToDeployTarget() {
	groupItems = getSelectedNodes(divComputerList, 'computer_groups');
	reportItems = getSelectedNodes(divComputerList, 'computer_reports');
	itemItems = getSelectedNodes(divComputerList, 'computers');
	if(groupItems.length + reportItems.length + itemItems.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
	} else {
		addToDeployTarget(groupItems, divTargetPackageList, 'target_package_groups');
		addToDeployTarget(reportItems, divTargetPackageList, 'target_package_reports');
		addToDeployTarget(itemItems, divTargetPackageList, 'target_packages');
	}
}
function addToDeployTarget(items, targetContainer, inputName) {
	if(!Array.isArray(items)) items = [items];
	items.forEach(item => {
		// check if it is already in target list
		var found = false;
		var elements = targetContainer.getElementsByTagName('input')
		for(var i = 0; i < elements.length; i++) {
			if(elements[i].name == inputName && elements[i].value == item['id']) {
				found = true;
				emitMessage(LANG['element_already_exists'], item['name'], MESSAGE_TYPE_WARNING);
				break;
			}
		}
		// add to target list
		if(!found) {
			var newLabel = document.createElement('label');
			newLabel.classList.add('blockListItem');
			var newCheckbox = document.createElement('input');
			newCheckbox.type = 'checkbox';
			newCheckbox.name = inputName;
			newCheckbox.value = item['id'];
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
			var newContent = document.createTextNode(item['name']);
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
				newLabel.title = LANG['reorder_via_drag_drop'];
			}
			targetContainer.appendChild(newLabel);
		}
	});
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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

// ======== MOBILE DEVICE OPERATIONS ========
function showDialogCreateMobileDeviceIos() {
	showDialogAjax(LANG['new_ios_device'], 'views/dialog-mobile-device-create-ios.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function showDialogCreateMobileDeviceAndroid() {
	showDialogAjax(LANG['new_android_device'], 'views/dialog-mobile-device-create-android.php', DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO);
}
function showDialogEditMobileDevice(id) {
	showDialogAjax(LANG['edit_mobile_device'], 'views/dialog-mobile-device-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function createMobileDeviceIos(name, serial, notes) {
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
		'create_mobile_device':name, 'notes':notes, 'serial':serial, 'type':'ios'
	}), null, function(response) {
		refreshContent();
		emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
		hideDialog();showLoader(false);showLoader2(false);
		window.open('views/dialog-mobile-device-create-ios.php?download_profile='+encodeURIComponent(response), '_blank')
	});
}
function editMobileDevice(id, deviceName, notes) {
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
		'edit_mobile_device_id':id,
		'device_name':deviceName,
		'notes':notes
	}), null, function() {
		refreshContent(); hideDialog();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function setMobileDeviceForceUpdate(id, value) {
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'edit_mobile_device_id':id, 'force_update':value}), null, function(responseText) {
		refreshContent();
		emitMessage(LANG['force_update'], responseText, MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedMobileDevice(checkboxName, attributeName=null, event=null) {
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveMobileDevice(ids, event);
}
function confirmRemoveMobileDevice(ids, event=null, infoText='', redirect=null) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete_mobile_device'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function createMobileDeviceGroup(parent_id=null) {
	var newName = prompt(LANG['enter_name']);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text){
			refreshSidebar(); refreshContentExplorer('views/mobile-devices.php?id='+parseInt(text));
			emitMessage(LANG['group_created'], newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameMobileDeviceGroup(id, oldName) {
	var newValue = prompt(LANG['enter_name'], oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['group_renamed'], newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function confirmRemoveMobileDeviceGroup(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
			refreshContentExplorer('views/mobile-devices.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogAddMobileDeviceToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['mobile_device_groups'], 'views/dialog-mobile-device-group-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditMobileDeviceId.value = id;
	});
}
function addMobileDeviceToGroup(mobileDeviceId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	mobileDeviceId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_mobile_device_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(LANG['mobile_device_added'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignProfileToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['assign'], 'views/dialog-profile-assign.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtProfileId.value = id;
	});
}
function assignProfileToGroup(profileId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	profileId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_profile_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(LANG['profile_assigned'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeProfileFromGroup(ids, groupId) {
	if(!confirm(LANG['are_you_sure'])) return;
	hideDialog();
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'remove_from_group_id[]', 'value':entry});
	});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_profile_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignManagedAppToGroup(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'id[]', 'value':entry});
	});
	showDialogAjax(LANG['assign'], 'views/dialog-managed-app-assign.php?'+urlencodeArray(params), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function assignManagedAppToGroup(managedAppId, groupId, removable, disableCloudBackup, removeOnMdmRemove, installType, configId, config, delegatedScopes) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	managedAppId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_managed_app_id[]', 'value':entry});
	});
	params.push({'key':'removable', 'value':removable});
	params.push({'key':'disable_cloud_backup', 'value':disableCloudBackup});
	params.push({'key':'remove_on_mdm_remove', 'value':removeOnMdmRemove});
	params.push({'key':'install_type', 'value':installType});
	params.push({'key':'config_id', 'value':configId});
	params.push({'key':'config', 'value':config});
	delegatedScopes.forEach(function(entry) {
		params.push({'key':'delegated_scopes[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(LANG['apps_assigned'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeManagedAppFromGroup(ids, groupId) {
	if(!confirm(LANG['are_you_sure'])) return;
	hideDialog();
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'remove_from_group_id[]', 'value':entry});
	});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_managed_app_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function removeSelectedMobileDeviceFromGroup(checkboxName, groupId) {
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
	removeMobileDeviceFromGroup(ids, groupId);
}
function removeMobileDeviceFromGroup(ids, groupId) {
	var params = [];
	params.push({'key':'remove_from_group_id', 'value':groupId});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_mobile_device_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
}

// ======== COMPUTER OPERATIONS ========
function showDialogCreateComputer() {
	showDialogAjax(LANG['create_computer'], 'views/dialog-computer-create.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function createComputer(hostname, notes, agentKey, serverKey) {
	var params = [];
	params.push({'key':'create_computer', 'value':hostname});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'agent_key', 'value':agentKey});
	params.push({'key':'server_key', 'value':serverKey});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/computers.php', paramString, null, function(text) {
		hideDialog();
		refreshContentExplorer('views/computer-details.php?id='+parseInt(text));
		emitMessage(LANG['computer_created'], hostname, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditComputer(id, hostname, notes) {
	showDialogAjax(LANG['edit_computer'], 'views/dialog-computer-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
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
		emitMessage(LANG['saved'], hostname, MESSAGE_TYPE_SUCCESS);
	});
}
function setComputerForceUpdate(id, value) {
	ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'edit_computer_id':id, 'force_update':value}), null, function(responseText) {
		refreshContent();
		emitMessage(LANG['force_update'], responseText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveComputer(ids, event);
}
function confirmRemoveComputer(ids, event=null, infoText='', redirect=null) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete_computer'])) {
		ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
		emitMessage(LANG['wol_packet_sent'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function createComputerGroup(parent_id=null) {
	var newName = prompt(LANG['enter_name']);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text){
			refreshSidebar(); refreshContentExplorer('views/computers.php?id='+parseInt(text));
			emitMessage(LANG['group_created'], newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameComputerGroup(id, oldName) {
	var newValue = prompt(LANG['enter_name'], oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['group_renamed'], newValue, MESSAGE_TYPE_SUCCESS);
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/computers.php', paramString, null, function() {
			refreshContentExplorer('views/computers.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['computer_added'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAddComputerToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['computer_groups'], 'views/dialog-computer-group-add.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtEditComputerId.value = id;
	});
}

// ======== JOB OPERATIONS ========
function showDialogMobileDeviceCommand(mobile_device_id) {
	showDialogAjax(LANG['send_command'], 'views/dialog-mobile-device-command.php?id='+encodeURIComponent(mobile_device_id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function showMobileDeviceCommandParameter(option) {
	let param = option.getAttribute('parameter');
	if(param) {
		txtMobileDeviceCommandParameter.name = param;
		thCommandParameterName.innerText = LANG[param];
		trCommandParameter.style.display = 'table-row';
	} else {
		trCommandParameter.style.display = 'none';
	}
}
function sendMobileDeviceCommand(mobile_device_id, name, parameter) {
	var params = [];
	params.push({'key':'send_command_to_mobile_device_id', 'value':mobile_device_id});
	params.push({'key':'command', 'value':name});
	for(const [key, value] of Object.entries(parameter)) {
		params.push({'key':key, 'value':value});
	}
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditProfile(type, id=-1) {
	title = LANG['edit'];
	if(id == -1) {
		title = type=='android' ? LANG['new_android_policy'] : LANG['new_ios_profile'];
	}
	showDialogAjax(title, 'views/dialog-profile-edit.php?id='+encodeURIComponent(id)+'&type='+encodeURIComponent(type), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editProfile(id, type, name, payload, notes) {
	let req = new XMLHttpRequest();
	let formData = new FormData();

	if(payload !== null) {
		if(typeof payload == 'string') {
			formData.append('payload_text', payload);
		} else {
			for(i = 0; i <= payload.length; i++) {
				formData.append('payload[]', payload[i]);
			}
		}
		formData.append('update_payload', '1');
	}
	formData.append('edit_profile_id', id);
	formData.append('type', type);
	formData.append('name', name);
	formData.append('notes', notes);

	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};

	req.open('POST', 'ajax-handler/mobile-devices.php');
	req.send(formData);
}
function removeSelectedProfile(checkboxName, attributeName=null, event=null) {
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveProfile(ids, event);
}
function confirmRemoveProfile(ids, event=null, infoText='', redirect=null) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_profile_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete_profile'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditDeploymentRule(id=-1, name='', notes='', enabled=0, computerGroupId=-1, packageGroupId=-1, priority=0) {
	title = LANG['edit_deployment_rule'];
	buttonText = LANG['change'];
	if(id == -1) {
		title = LANG['new_deployment_rule'];
		buttonText = LANG['create'];
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
		spnBtnUpdateDeploymentRule.innerText = buttonText;
	});
}
function editDeploymentRule(id, name, notes, enabled, computerGroupId, packageGroupId, priority) {
	var params = [];
	params.push({'key':'edit_deployment_rule_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'enabled', 'value':enabled?'1':'0'});
	params.push({'key':'computer_group_id', 'value':computerGroupId});
	params.push({'key':'package_group_id', 'value':packageGroupId});
	params.push({'key':'priority', 'value':priority});
	ajaxRequestPost('ajax-handler/deployment-rules.php', urlencodeArray(params), null, function(response) {
		hideDialog();
		if(id == '-1') {
			refreshContentExplorer('views/deployment-rules.php?id='+parseInt(response));
			refreshSidebar();
			emitMessage(LANG['jobs_created'], name, MESSAGE_TYPE_SUCCESS);
		} else {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
		}
	});
}
function reevaluateDeploymentRule(deploymentRuleId) {
	var params = [];
	params.push({'key':'evaluate_deployment_rule_id', 'value':deploymentRuleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/deployment-rules.php', paramString, null, function() {
		refreshContent();
		emitMessage(LANG['reevaluated'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
	if(confirm(LANG['confirm_delete_deployment_rule'])) {
		ajaxRequestPost('ajax-handler/deployment-rules.php', paramString, null, function() {
			refreshContentExplorer('views/deployment-rules.php'); refreshSidebar();
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogMoveStaticJobToJobContainer(id) {
	if(!id) return;
	showDialogAjax(LANG['job_container'], 'views/dialog-jobs-move.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
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
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
	if(confirm(LANG['confirm_delete_job_container'])) {
		ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
			refreshContentExplorer('views/job-containers.php'); refreshSidebar();
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
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
	if(confirm(LANG['confirm_delete_job'])) {
		ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogEditJobContainer(id, name, enabled, start, end, sequence_mode, priority, agent_ip_ranges, time_frames, notes) {
	showDialogAjax(LANG['edit_job_container'], 'views/dialog-job-container-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
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
		txtEditJobContainerTimeFrames.value = time_frames;
		txtEditJobContainerNotes.value = notes;
	});
}
function editJobContainer(id, name, enabled, start, end, sequence_mode, priority, agent_ip_ranges, time_frames, notes) {
	var params = [];
	params.push({'key':'edit_job_container_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'enabled', 'value':enabled?'1':'0'});
	params.push({'key':'start', 'value':start});
	params.push({'key':'end', 'value':end});
	params.push({'key':'sequence_mode', 'value':sequence_mode?'1':'0'});
	params.push({'key':'priority', 'value':priority});
	params.push({'key':'agent_ip_ranges', 'value':agent_ip_ranges});
	params.push({'key':'time_frames', 'value':time_frames});
	params.push({'key':'notes', 'value':notes});
	ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
		hideDialog();
		refreshContent(); refreshSidebar();
		emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
	});
}
function deploy(title, start, end, description, computers, computerGroups, computerReports, packages, packageGroups, packageReports, useWol, shutdownWakedAfterCompletion, forceInstallSameVersion, restartTimeout, sequenceMode, priority, agentIpRanges, timeFrames) {
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
	formData.append('force_install_same_version', forceInstallSameVersion ? 1 : 0);
	formData.append('restart_timeout', restartTimeout);
	formData.append('sequence_mode', sequenceMode);
	formData.append('priority', priority);
	formData.append('agent_ip_ranges', agentIpRanges);
	formData.append('time_frames', timeFrames);
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
				emitMessage(LANG['jobs_created'], title, MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				setInputsDisabled(tabControlDeploy, false);
				btnDeploy.classList.remove('hidden');
				prgDeploy.classList.add('hidden');
			}
		}
	};
}
function showDialogUninstall() {
	showDialogAjax(LANG['uninstall_packages'], 'views/dialog-uninstall.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function uninstall(checkboxName, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, restartTimeout, priority) {
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
		emitMessage(LANG['jobs_created'], name, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_package_assignment_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_remove_package_assignment'])) {
		ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogRenewFailedStaticJobs(id, defaultName, jobIds) {
	if(!jobIds) return;
	showDialogAjax(LANG['renew_failed_jobs'], 'views/dialog-jobs-renew.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
		txtRenewJobContainerId.value = id;
		txtRenewJobContainerName.value = defaultName;
		txtRenewJobContainerJobId.value = jobIds;
	});
}
function renewFailedStaticJobs(id, jobId, createNewJobContainer, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, priority) {
	var params = [];
	params.push({'key':'renew_job_container', 'value':id});
	jobId.toString().split(',').forEach(function(entry) {
		if(entry.trim() != '') params.push({'key':'job_id[]', 'value':entry});
	});
	params.push({'key':'create_new_job_container', 'value':createNewJobContainer ? 1 : 0});
	params.push({'key':'job_container_name', 'value':name});
	params.push({'key':'notes', 'value':notes});
	params.push({'key':'start_time', 'value':startTime});
	params.push({'key':'end_time', 'value':endTime});
	params.push({'key':'use_wol', 'value':useWol ? 1 : 0});
	params.push({'key':'shutdown_waked_after_completion', 'value':shutdownWakedAfterCompletion ? 1 : 0});
	params.push({'key':'priority', 'value':priority});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/job-containers.php', paramString, null, function() {
		hideDialog(); refreshSidebar(); refreshContent();
		emitMessage(LANG['jobs_created'], name, MESSAGE_TYPE_SUCCESS);
	});
}
function renewFailedDynamicJobs(id, jobId) {
	if(!confirm(LANG['renew_failed_deployment_rule_jobs_now'])) return;
	var params = [];
	params.push({'key':'renew_deployment_rule', 'value':id});
	jobId.toString().split(',').forEach(function(entry) {
		if(entry.trim() != '') params.push({'key':'job_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/deployment-rules.php', paramString, null, function() {
		refreshSidebar(); refreshContent();
		emitMessage(LANG['jobs_renewed'], '', MESSAGE_TYPE_SUCCESS);
	});
}

// ======== DOMAIN USER OPERATIONS ========
function showDialogEditDomainUserRole(id=-1) {
	title = LANG['edit_domain_user_role'];
	if(id == -1) title = LANG['create_domain_user_role'];
	showDialogAjax(title, 'views/dialog-domain-user-role-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editDomainUserRole(id, name, permissions) {
	var params = [];
	params.push({'key':'edit_domain_user_role_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'permissions', 'value':permissions});
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function(response) {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_domain_user_role_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogEditDomainUser(id=-1) {
	title = LANG['edit_domain_user'];
	if(id == -1) title = LANG['create_domain_user'];
	showDialogAjax(title, 'views/dialog-domain-user-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editDomainUser(id, username, password, roleId) {
	var params = [];
	params.push({'key':'edit_domain_user_id', 'value':id});
	params.push({'key':'password', 'value':password});
	params.push({'key':'domain_user_role_id', 'value':roleId});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], username, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/domain-users.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

// ======== REPORT OPERATIONS ========
function createReportGroup(parent_id=null) {
	var newValue = prompt(LANG['enter_name']);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/reports.php', urlencodeObject({'create_group':newValue, 'parent_id':parent_id}), null, function(text) {
			refreshContentExplorer('views/reports.php?id='+parseInt(text));
			refreshSidebar();
			emitMessage(LANG['group_created'], newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameReportGroup(id, oldName) {
	var newValue = prompt(LANG['enter_name'], oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/reports.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['group_renamed'], newValue, MESSAGE_TYPE_SUCCESS);
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/reports.php', paramString, null, function() {
			refreshContentExplorer('views/reports.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogCreateReport(group_id=0) {
	showDialogAjax(LANG['create_report'], 'views/dialog-report-create.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
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
		emitMessage(LANG['report_created'], name, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditReport(id, name, notes, query) {
	showDialogAjax(LANG['edit_report'], 'views/dialog-report-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
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
		emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveReport(ids);
}
function confirmRemoveReport(ids, infoText='', redirect=null) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/reports.php', paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogMoveReportToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['report_groups'], 'views/dialog-report-group-move.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function() {
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_system_user_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'lock_system_user_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'unlock_system_user_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditOwnSystemUserPassword() {
	showDialogAjax(LANG['change_password'], 'views/dialog-system-user-edit-own-password.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editOwnSystemUserPassword(oldPassword, newPassword) {
	var params = [];
	params.push({'key':'edit_own_system_user_password', 'value':newPassword});
	params.push({'key':'old_password', 'value':oldPassword});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
		hideDialog();
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUser(id=-1) {
	title = LANG['edit_system_user'];
	if(id == -1) title = LANG['create_system_user'];
	showDialogAjax(title, 'views/dialog-system-user-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
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
		emitMessage(LANG['saved'], username, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUserRole(id=-1) {
	title = LANG['edit_system_user_role'];
	if(id == -1) title = LANG['create_system_user_role'];
	showDialogAjax(title, 'views/dialog-system-user-role-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editSystemUserRole(id, name, permissions) {
	var params = [];
	params.push({'key':'edit_system_user_role_id', 'value':id});
	params.push({'key':'name', 'value':name});
	params.push({'key':'permissions', 'value':permissions});
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function(response) {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_system_user_role_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

// ======== SETTINGS OPERATIONS ========
function showDialogEditEventQueryRule(id=-1, log='', query='') {
	title = LANG['change'];
	buttonText = LANG['change'];
	if(id == -1) {
		title = LANG['create'];
		buttonText = LANG['create'];
	}
	showDialogAjax(title, 'views/dialog-event-query-rule-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditEventQueryRuleId.value = id;
		txtEditEventQueryRuleLog.value = log;
		txtEditEventQueryRuleQuery.value = query;
		spnBtnUpdateEventQueryRule.innerText = buttonText;
	});
}
function editEventQueryRule(id, log, query) {
	var params = [];
	params.push({'key':'edit_event_query_rule_id', 'value':id});
	params.push({'key':'log', 'value':log});
	params.push({'key':'query', 'value':query});
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function(response) {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], log, MESSAGE_TYPE_SUCCESS);
	});
}
function confirmRemoveSelectedEventQueryRule(checkboxName) {
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
	ids.forEach(function(entry) {
		params.push({'key':'remove_event_query_rule_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditPasswordRotationRule(id=-1, computer_group_id='', username='administrator', alphabet='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_+*.#=!', length=15, validSeconds=2592000, history=5, default_password='') {
	title = LANG['change'];
	buttonText = LANG['change'];
	if(id == -1) {
		title = LANG['create'];
		buttonText = LANG['create'];
	}
	showDialogAjax(title, 'views/dialog-password-rotation-rule-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		txtEditPasswordRotationRuleId.value = id;
		sltEditPasswordRotationRuleComputerGroupId.value = computer_group_id;
		txtEditPasswordRotationRuleUsername.value = username;
		txtEditPasswordRotationRuleAlphabet.value = alphabet;
		txtEditPasswordRotationRuleLength.value = length;
		txtEditPasswordRotationRuleValidSeconds.value = validSeconds;
		txtEditPasswordRotationRuleHistory.value = history;
		txtEditPasswordRotationRuleDefaultPassword.value = default_password;
		spnBtnUpdatePasswordRotationRule.innerText = buttonText;
	});
}
function editPasswordRotationRule(id, computer_group_id, username, alphabet, length, valid_seconds, history, default_password) {
	var params = [];
	params.push({'key':'edit_password_rotation_rule_id', 'value':id});
	params.push({'key':'computer_group_id', 'value':computer_group_id});
	params.push({'key':'username', 'value':username});
	params.push({'key':'alphabet', 'value':alphabet});
	params.push({'key':'length', 'value':length});
	params.push({'key':'valid_seconds', 'value':valid_seconds});
	params.push({'key':'history', 'value':history});
	params.push({'key':'default_password', 'value':default_password});
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function(response) {
		hideDialog(); refreshContent();
		emitMessage(LANG['saved'], username, MESSAGE_TYPE_SUCCESS);
	});
}
function confirmRemoveSelectedPasswordRotationRule(checkboxName) {
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
	ids.forEach(function(entry) {
		params.push({'key':'remove_password_rotation_rule_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditGeneralConfig() {
	showDialogAjax(LANG['oco_configuration'], 'views/dialog-general-config-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editGeneralConfig(clientApiEnabled, clientApiKey, agentRegistrationEnabled, agentRegistrationKey, assumeComputerOfflineAfter, wolShutdownExpiry, agentUpdateInterval, purgeSucceededJobsAfter, purgeFailedJobsAfter, purgeDomainUserLogonsAfter, purgeEventsAfter, logLevel, purgeLogsAfter, keepInactiveScreens, selfServiceEnabled) {
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_general_config', 1);
	formData.append('client_api_enabled', clientApiEnabled);
	formData.append('client_api_key', clientApiKey);
	formData.append('agent_registration_enabled', agentRegistrationEnabled);
	formData.append('agent_registration_key', agentRegistrationKey);
	formData.append('assume_computer_offline_after', assumeComputerOfflineAfter);
	formData.append('wol_shutdown_expiry', wolShutdownExpiry);
	formData.append('agent_update_interval', agentUpdateInterval);
	formData.append('purge_succeeded_jobs_after', purgeSucceededJobsAfter);
	formData.append('purge_failed_jobs_after', purgeFailedJobsAfter);
	formData.append('purge_domain_user_logons_after', purgeDomainUserLogonsAfter);
	formData.append('purge_events_after', purgeEventsAfter);
	formData.append('log_level', logLevel);
	formData.append('purge_logs_after', purgeLogsAfter);
	formData.append('computer_keep_inactive_screens', keepInactiveScreens);
	formData.append('self_service_enabled', selfServiceEnabled);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], LANG['oco_configuration'], MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	req.open('POST', 'ajax-handler/settings.php');
	req.send(formData);
}
function showDialogEditLdapConfigSystemUsers() {
	showDialogAjax(LANG['ldap_config'], 'views/dialog-system-user-ldap-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editLdapConfigSystemUsers(jsonConfig) {
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_system_user_ldap_sync', jsonConfig);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], LANG['ldap_config'], MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	req.open('POST', 'ajax-handler/settings.php');
	req.send(formData);
}
function showDialogEditLdapConfigDomainUsers() {
	showDialogAjax(LANG['ldap_config'], 'views/dialog-domain-user-ldap-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editLdapConfigDomainUsers(jsonConfig) {
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_domain_user_ldap_sync', jsonConfig);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], LANG['ldap_config'], MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	req.open('POST', 'ajax-handler/settings.php');
	req.send(formData);
}
function enableDisableButton(btn, state) {
	var btnImg = btn.querySelectorAll('img')[0];
	if(state) {
		btn.disabled = false;
		btnImg.classList.remove('animRotate');
	} else {
		btn.disabled = true;
		btnImg.classList.add('animRotate');
	}
}
function ldapSyncSystemUsers(btn) {
	enableDisableButton(btn, false);
	var params = [];
	params.push({'key':'ldap_sync_system_users', 'value':1});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function(text) {
		refreshContent();
		emitMessage(LANG['ldap_sync'], text, MESSAGE_TYPE_SUCCESS);
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function ldapSyncDomainUsers(btn) {
	enableDisableButton(btn, false);
	var params = [];
	params.push({'key':'ldap_sync_domain_users', 'value':1});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/settings.php', paramString, null, function(text) {
		refreshContent();
		emitMessage(LANG['ldap_sync'], text, MESSAGE_TYPE_SUCCESS);
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function showDialogEditWolSatellites() {
	showDialogAjax(LANG['wol_satellites'], 'views/dialog-wol-satellites-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editWolSatellites(jsonConfig) {
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_wol_satellites', jsonConfig);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], LANG['wol_satellites'], MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	req.open('POST', 'ajax-handler/settings.php');
	req.send(formData);
}
function showDialogEditSetting(key='', file=false, warning=true, keyHidden=false, title=null) {
	if(title == null) {
		title = LANG['edit_setting'];
		if(key == '') {
			title = LANG['create_setting'];
		}
	}
	showDialogAjax(title, 'views/dialog-setting-edit.php?key='+encodeURIComponent(key), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(){
		if(file) {
			fleEditSettingValue.classList.remove('hidden');
			txtEditSettingValue.classList.add('hidden');
			if(typeof file === 'string' || file instanceof String) {
				fleEditSettingValue.accept = file;
			}
			if(keyHidden) window.setTimeout(() => fleEditSettingValue.focus(), 0);
		} else {
			if(keyHidden) window.setTimeout(() => txtEditSettingValue.focus(), 0);
		}
		if(!warning) {
			trSettingsManualChangesWarning.classList.add('hidden');
		}
		if(keyHidden) {
			txtEditSettingKey.classList.add('hidden');
		}
	});
}
function editSetting(key, value) {
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_setting', key);
	formData.append('value', value);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				hideDialog(); refreshContent();
				emitMessage(LANG['saved'], key, MESSAGE_TYPE_SUCCESS);
				if(key == 'license') {
					emitMessage(LANG['thank_you_for_support'], '', MESSAGE_TYPE_INFO);
					sideConfetti();
				}
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
			}
		}
	};
	req.open('POST', 'ajax-handler/settings.php');
	req.send(formData);
}
function removeSelectedSetting(checkboxName, attributeName=null) {
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_setting[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['really_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', paramString, null, function() {
			emitMessage(LANG['object_deleted'], ids.join(', '), MESSAGE_TYPE_SUCCESS);
			refreshContent();
		});
	}
}
function readFileInputBlob(file) {
	var start = 0;
	var stop = file.size - 1;
	var reader = new FileReader();
	var blob;
	if(file.slice) {
		blob = file.slice(start, stop + 1);
	} else if(file.webkitSlice) {
		blob = file.webkitSlice(start, stop + 1);
	} else if(file.mozSlice) {
		blob = file.mozSlice(start, stop + 1);
	}
	reader.readAsBinaryString(blob);
	return blob;
}

function syncAppsProfiles(btn) {
	var params = [];
	params.push({'key':'sync_apps_profiles', 'value':1});
	var paramString = urlencodeArray(params);
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(text) {
		emitMessage(LANG['apps_profiles_policies_synced'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}

function syncAppleDevices(btn) {
	var params = [];
	params.push({'key':'sync_apple_devices', 'value':1});
	var paramString = urlencodeArray(params);
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(text) {
		emitMessage(LANG['sync_apple_devices'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function syncAppleAssets(btn) {
	var params = [];
	params.push({'key':'sync_apple_assets', 'value':1});
	var paramString = urlencodeArray(params);
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(text) {
		emitMessage(LANG['sync_apple_vpp'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}

function showDialogManagedPlayStore() {
	// get the token for the Play Store iframe
	var paramString = urlencodeArray([
		{'key':'get_playstore_token', 'value':1}
	]);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(token) {
		// load the Google JS
		var script = document.createElement('script');
		script.src = 'https://apis.google.com/js/api.js';
		script.onload = function() {
			// show the OCO dialog
			showDialog(LANG['manage_android_apps'], '', DIALOG_BUTTONS_CLOSE);
			// embed the Play Store iframe into the dialog
			gapi.load('gapi.iframes', function() {
				var options = {
					'url': 'https://play.google.com/work/embedded/search?token='+encodeURIComponent(token)+'&mode=SELECT',
					'where': obj('dialog-text'),
					'attributes': { style:'height:100%; display:block', scrolling:'yes'}
				};
				var iframe = gapi.iframes.getContext().openChild(options);
				iframe.register('onproductselect', function(event) {
					var displayName = prompt(LANG['display_name']);
					if(displayName) {
						var paramString = urlencodeArray([
							{'key':'playstore_onproductselect', 'value':event.action},
							{'key':'package_name', 'value':event.packageName},
							{'key':'product_id', 'value':event.productId},
							{'key':'app_name', 'value':displayName},
						]);
						ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(response) {
							emitMessage(LANG['saved'], event.packageName, MESSAGE_TYPE_SUCCESS);
							refreshContent();
						});
					}
				}, gapi.iframes.CROSS_ORIGIN_IFRAMES_FILTER);
			});
		};
		document.head.appendChild(script);
	});
}
function showDialogAndroidZeroTouch() {
	// get the token for the Play Store iframe
	var params = [];
	params.push({'key':'get_playstore_token', 'value':1});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(token) {
		// load the Google JS
		var script = document.createElement('script');
		script.src = 'https://apis.google.com/js/api.js';
		script.onload = function() {
			// show the OCO dialog
			showDialog(LANG['manage_zero_touch_enrollment'], '', DIALOG_BUTTONS_CLOSE);
			// embed the Play Store iframe into the dialog
			gapi.load('gapi.iframes', function() {
				var options = {
					'url': 'https://enterprise.google.com/android/zero-touch/embedded/companyhome?token='+encodeURIComponent(token)+'&dpcId=com.google.android.apps.work.clouddpc',
					'where': obj('dialog-text'),
					'attributes': { style:'height:100%; display:block', scrolling:'yes'}
				};
				var iframe = gapi.iframes.getContext().openChild(options);
			});
		};
		document.head.appendChild(script);
	});
}
function showDialogManagedPlayStoreConfig(packageName, managedAppId, configId=null) {
	// get the token for the Play Store iframe
	var params = [];
	params.push({'key':'get_playstore_token', 'value':1});
	var paramString = urlencodeArray(params);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(token) {
		// load the Google JS
		var script = document.createElement('script');
		script.src = 'https://apis.google.com/js/api.js';
		script.onload = function() {
			// show the OCO dialog
			showDialog(packageName, '', DIALOG_BUTTONS_CLOSE);
			// embed the Play Store iframe into the dialog
			gapi.load('gapi.iframes', function() {
				var options = {
					'url': 'https://play.google.com/managed/mcm?token='+encodeURIComponent(token)+'&packageName='+encodeURIComponent(packageName)+(configId ? '&mcmId='+encodeURIComponent(configId)+'&canDelete=TRUE' : ''),
					'where': obj('dialog-text'),
					'attributes': { style:'height:100%; display:block', scrolling:'yes'}
				};
				var iframe = gapi.iframes.getContext().openChild(options);
				iframe.register('onconfigupdated', function(event) {
					var paramString = urlencodeArray([
						{'key':'playstore_onconfigupdated', 'value':event.mcmId},
						{'key':'name', 'value':event.name},
						{'key':'managed_app_id', 'value':managedAppId},
					]);
					ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(response) {
						emitMessage(LANG['saved'], event.name, MESSAGE_TYPE_SUCCESS);
						refreshContent();
						hideDialog();
					});
				}, gapi.iframes.CROSS_ORIGIN_IFRAMES_FILTER);
				iframe.register('onconfigdeleted', function(event) {
					var paramString = urlencodeArray([
						{'key':'playstore_onconfigdeleted', 'value':event.mcmId},
						{'key':'managed_app_id', 'value':managedAppId},
					]);
					ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(response) {
						emitMessage(LANG['configuration_deleted'], '', MESSAGE_TYPE_SUCCESS);
						refreshContent();
						hideDialog();
					});
				}, gapi.iframes.CROSS_ORIGIN_IFRAMES_FILTER);
			});
		};
		document.head.appendChild(script);
	});
}
function removeSelectedManagedApp(checkboxName, attributeName=null) {
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
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_managed_app_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['really_delete'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function() {
			emitMessage(LANG['object_deleted'], null, MESSAGE_TYPE_SUCCESS);
			refreshContent();
		});
	}
}
function syncAndroidDevices(btn) {
	var params = [];
	params.push({'key':'sync_android_devices', 'value':1});
	var paramString = urlencodeArray(params);
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', paramString, null, function(text) {
		emitMessage(LANG['sync_android_devices'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function showDialogAssignedProfileInfo(groupId, profileId) {
	showDialogAjax(LANG['managed_app'], 'views/dialog-mobile-device-assigned-profile-info.php?mobile_device_group_id='+encodeURIComponent(groupId)+'&profile_id='+encodeURIComponent(profileId), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO);
}
function showDialogAssignedManagedAppInfo(groupId, managedAppId) {
	showDialogAjax(LANG['managed_app'], 'views/dialog-mobile-device-assigned-managed-app-info.php?mobile_device_group_id='+encodeURIComponent(groupId)+'&managed_app_id='+encodeURIComponent(managedAppId), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO);
}

function checkUpdate() {
	ajaxRequestPost('ajax-handler/update-check.php', '', null, function(text) {
		if(text.trim() != '') {
			emitMessage(LANG['update_available'], text.trim(), MESSAGE_TYPE_INFO);
		}
	});
}
