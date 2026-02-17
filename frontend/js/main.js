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
function isInsideParentWithClass(node, wantedParentClass) {
	let parent = node.parentNode;
	while(parent) {
		if('classList' in parent
		&& parent.classList.contains(wantedParentClass))
			return true;
		parent = parent.parentNode;
	}
	return false;
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
};

// ======== DIALOG ========
const DIALOG_BUTTONS_NONE   = 0;
const DIALOG_BUTTONS_CLOSE  = 1;
const DIALOG_SIZE_LARGE     = 0;
const DIALOG_SIZE_SMALL     = 1;
const DIALOG_SIZE_AUTO      = 2;
function newDialog() {
	let dialogContainer = divDialogTemplate.cloneNode(true);
	divDialogTemplate.parentNode.insertBefore(dialogContainer, divDialogTemplate.nextSibling)
	return dialogContainer;
}
function showDialog(title='', text='', controls=false, size=false, monospace=false, loading=false) {
	return showDialogHTML(title, escapeHTML(text), controls, size, monospace, loading);
}
function showDialogAjax(title='', url='', controls=false, size=false, callback=null) {
	let dialogContainer = newDialog();
	let dialogText = dialogContainer.querySelectorAll('.dialogText')[0];
	// show dark background while waiting for response
	dialogContainer.classList.add('loading');
	// show loader if request took a little longer (would be annoying if shown directly)
	dialogLoaderTimer = setTimeout(function(){ dialogContainer.classList.add('loading2') }, 100);
	// start ajax request
	let finalAction = function() {
		dialogContainer.classList.remove('loading');
		dialogContainer.classList.remove('loading2');
		clearTimeout(dialogLoaderTimer);
	};
	ajaxRequest(url, null, function(text) {
		showDialogHTML(title, text, controls, size, false, false, dialogContainer);
		// execute inline scripts
		var scripts = dialogText.getElementsByTagName('script');
		for(var i = 0; i < scripts.length; i++) {
			eval(scripts[i].innerHTML);
		}
		// exec custom callback
		if(callback && typeof callback == 'function') {
			callback(dialogContainer);
		}
		finalAction();
	}, false, false, finalAction);
}
function showDialogHTML(title='', text='', controls=false, size=false, monospace=false, loading=false, dialogContainer=null) {
	if(!dialogContainer)
		dialogContainer = newDialog();

	let dialogBox   = dialogContainer.querySelectorAll('.dialogBox')[0];
	let dialogTitle = dialogContainer.querySelectorAll('.dialogTitle')[0];
	let dialogText  = dialogContainer.querySelectorAll('.dialogText')[0];
	let dialogClose = dialogContainer.querySelectorAll('.dialogBox > button.dialogClose')[0];

	dialogContainer.close = function(e) {
		let animation = dialogBox.animate(
			[ {transform:'scale(100%)'}, {transform:'scale(98%)'} ],
			{ duration: 150, iterations: 1, easing:'linear' }
		);
		animation.onfinish = (event) => {
			dialogContainer.remove();
		};
	};

	// add events
	dialogBox.addEventListener('keydown', (event) => {
		// ESC - hide dialog
		if((event.which || event.keyCode) == 27)
			dialogContainer.close();
	});
	// set text
	dialogTitle.innerText = title;
	dialogText.innerHTML = text;
	// buttons
	dialogClose.style.visibility = 'collapse';
	if(controls == DIALOG_BUTTONS_CLOSE)
		dialogClose.style.visibility = 'visible';
	// size
	if(size == DIALOG_SIZE_LARGE)
		dialogBox.classList.add('large');
	else if(size == DIALOG_SIZE_SMALL)
		dialogBox.classList.add('small');
	// font
	if(monospace)
		dialogText.classList.add('monospace');
	else
		dialogText.classList.remove('monospace');
	// close action
	dialogBox.querySelectorAll('button.dialogClose').forEach(function(btn){
		btn.addEventListener('click', dialogContainer.close);
	});
	// loading animation
	if(loading) {
		var img = document.createElement('img');
		img.src = 'img/loader-dots.svg';
		img.style = 'display:block';
		dialogText.appendChild(img);
	}
	// make dialog visible
	dialogContainer.classList.add('active');
	let animation = dialogBox.animate(
		[ {transform:'scale(102%)'}, {transform:'scale(100%)'} ],
		{ duration: 250, iterations: 1, easing:'ease' }
	);
	// set focus
	animation.onfinish = (event) => {
		if(!setAutofocus(dialogText))
			dialogClose.focus();
	};
	return dialogContainer;
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
function setAutofocus(container) {
	var childs = container.querySelectorAll('*');
	for(var i = 0; i < childs.length; i++) {
		if(childs[i].getAttribute('autofocus')) {
			childs[i].focus();
			return true;
		}
	}
	return false;
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
function getSelectedSelectBoxValues(selectBox, warnIfEmpty=false) {
	var selected = [];
	if(typeof selectBox === 'string' || selectBox instanceof String)
		selectBox = document.getElementById(selectBox);
	for(var i = 0; i < selectBox.length; i++) {
		if(selectBox[i].selected) {
			selected.push(selectBox[i].value);
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

// ======== GENERAL OPERATIONS ========
function confirmRemoveObject(ids, paramName, apiEndpoint, event=null, confirmText=LANG['confirm_delete'], successText='', redirect=null) {
	if(!ids) return;
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':paramName+'[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(confirmText)) {
		ajaxRequestPost(apiEndpoint, paramString, null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], successText, MESSAGE_TYPE_SUCCESS);
		});
	}
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
function showDialogEditPackageFamily(id) {
	showDialogAjax(LANG['edit_package_family'], 'views/dialog/package-family-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			let txtLicenseCount = dialogContainer.querySelectorAll('input[name=license_count]')[0];
			editPackageFamily(
				dialogContainer,
				dialogContainer.querySelectorAll('input[name=id]')[0].value,
				dialogContainer.querySelectorAll('input[name=name]')[0].value,
				txtLicenseCount.value=='' ? -1 : txtLicenseCount.value,
				dialogContainer.querySelectorAll('textarea[name=notes]')[0].value,
			);
		});
	});
}
function editPackageFamily(dialogContainer, id, name, license_count, notes) {
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({
		'edit_package_family_id':id,
		'name':name,
		'license_count':license_count,
		'notes':notes
	}), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditPackage(id) {
	showDialogAjax(LANG['edit_package'], 'views/dialog/package-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let chkReplaceArchive = dialogContainer.querySelectorAll('input[name=replace_archive]')[0];
		let fleArchive = dialogContainer.querySelectorAll('input[name=archive]')[0];
		// add events
		chkReplaceArchive.addEventListener('click', (e)=>{
			fleArchive.disabled = !e.srcElement.checked;
		});
		dialogContainer.querySelectorAll('button.toggleDirectoryUpload')[0].addEventListener('click', (e)=>{
			toggleInputDirectory(fleArchive, e.srcElement);
		});
		let toggles = dialogContainer.querySelectorAll('.toggleMultiline');
		for(let i=0; i<toggles.length; i++) {
			toggles[i].querySelectorAll('button.toggle')[0].addEventListener('click', (e)=>{
				let textBox = toggles[i].querySelectorAll('input, textarea')[0];
				toggleTextBoxMultiLine(textBox);
			});
		}
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editPackage(
				dialogContainer,
				dialogContainer.querySelectorAll('input[name=id]')[0].value,
				dialogContainer.querySelectorAll('select[name=package_family_id]')[0].value,
				dialogContainer.querySelectorAll('input[name=version]')[0].value,
				dialogContainer.querySelectorAll('select[name=compatible_os]')[0].value,
				dialogContainer.querySelectorAll('select[name=compatible_os_version]')[0].value,
				dialogContainer.querySelectorAll('select[name=compatible_architecture]')[0].value,
				dialogContainer.querySelectorAll('input[name=license_count]')[0].value,
				dialogContainer.querySelectorAll('textarea[name=notes]')[0].value,
				chkReplaceArchive.checked ? fleArchive.files : null,
				dialogContainer.querySelectorAll('input[name=install_procedure], textarea[name=install_procedure]')[0].value,
				dialogContainer.querySelectorAll('input[name=install_procedure_success_return_codes]')[0].value,
				getSelectedCheckBoxValues('install_procedure_post_action', null, false, dialogContainer),
				getSelectedCheckBoxValues('upgrade_behavior', null, false, dialogContainer),
				dialogContainer.querySelectorAll('input[name=uninstall_procedure], textarea[name=uninstall_procedure]')[0].value,
				dialogContainer.querySelectorAll('input[name=uninstall_procedure_success_return_codes]')[0].value,
				getSelectedCheckBoxValues('uninstall_procedure_post_action', null, false, dialogContainer),
				dialogContainer.querySelectorAll('input[name=download_for_uninstall]')[0].checked ? 1 : 0,
			);
		});
	});
}
function editPackage(dialogContainer, id, package_family_id, version, compatible_os, compatible_os_version, compatible_architecture, license_count, notes, archive, install_procedure, install_procedure_success_return_codes, install_procedure_post_action, upgrade_behavior, uninstall_procedure, uninstall_procedure_success_return_codes, uninstall_procedure_post_action, download_for_uninstall) {
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

	let btnEditPackage = dialogContainer.querySelectorAll('button[name=edit]')[0];
	let btnCloseDialog = dialogContainer.querySelectorAll('button.dialogClose')[0];
	let prgPackageUpload = dialogContainer.querySelectorAll('.progressbar-container')[0];
	let prgPackageUploadText = dialogContainer.querySelectorAll('.progressbar-container > .progresstext')[0];
	setInputsDisabled(dialogContainer, true);
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
				dialogContainer.close(); refreshContent();
				emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
			} else {
				emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
				setInputsDisabled(dialogContainer, false);
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
	ajaxRequestPost('ajax-handler/packages.php', urlencodeObject({
		'move_in_group_id': groupId,
		'move_from_pos': oldPos,
		'move_to_pos': newPos,
	}), null, refreshContent);
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
	if(confirm( (installedOnComputers != null ? LANG['package_is_installed_on_computers'].replace('%1',installedOnComputers)+' ' : '') + LANG['confirm_delete_package']) ) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
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
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
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
function showDialogAddPackageDependency(packageId, reverse=false) {
	showDialogAjax(LANG['computer_groups'], 'views/dialog/package-select.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=assign]')[0].addEventListener('click', (e)=>{
			if(reverse) {
				addDependantPackage(
					dialogContainer,
					packageId,
					getSelectedCheckBoxValues('packages', null, true, dialogContainer),
				);
			} else {
				addPackageDependency(
					dialogContainer,
					packageId,
					getSelectedCheckBoxValues('packages', null, true, dialogContainer),
				);
			}
		});
	});
}
function addPackageDependency(dialogContainer, packageId, packageIds) {
	if(!packageIds) return;
	let params = [];
	params.push({'key':'edit_package_id', 'value':packageId});
	for(var i = 0; i < packageIds.length; i++) {
		params.push({'key':'add_package_dependency_id[]', 'value':packageIds[i]});
	}
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function addDependantPackage(dialogContainer, packageId, packageIds) {
	if(!packageIds) return;
	let params = [];
	params.push({'key':'edit_package_id', 'value':packageId});
	for(var i = 0; i < packageIds.length; i++) {
		params.push({'key':'add_dependant_package_id[]', 'value':packageIds[i]});
	}
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignPackageComputer(packageId) {
	showDialogAjax(LANG['computer_groups'], 'views/dialog/computer-select.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=assign]')[0].addEventListener('click', (e)=>{
			assignPackageComputer(
				dialogContainer,
				packageId,
				getSelectedCheckBoxValues('computers', null, true, dialogContainer),
			);
		});
	});
}
function assignPackageComputer(dialogContainer, packageId, computerIds) {
	if(!computerIds) return;
	var params = [];
	params.push({'key':'edit_package_id', 'value':packageId});
	for(var i = 0; i < computerIds.length; i++) {
		params.push({'key':'add_computer_id[]', 'value':computerIds[i]});
	}
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
			refreshContentExplorer('views/packages.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogAddPackageToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['package_groups'], 'views/dialog/package-group-add.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer) {
		let txtPackageId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let sltPackageGroup = dialogContainer.querySelectorAll('select[name=group]')[0];
		dialogContainer.querySelectorAll('button[name=add]')[0].addEventListener('click', (e)=>{
			addPackageToGroup(
				dialogContainer,
				txtPackageId.value,
				getSelectedSelectBoxValues(sltPackageGroup, true)
			);
		});
	});
}
function addPackageToGroup(dialogContainer, packageId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	packageId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_package_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['packages_added'], '', MESSAGE_TYPE_SUCCESS);
	});
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
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
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
	ajaxRequestPost('ajax-handler/packages.php', urlencodeArray(params), null, function() {
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
	var elements = root.getElementsByTagName('input');
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
function showDialogCreateMobileDeviceAndroid() {
	showDialogAjax(LANG['new_android_device'], 'views/dialog/mobile-device-create-android.php', DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO);
}
function showDialogCreateMobileDeviceIos() {
	showDialogAjax(LANG['new_ios_device'], 'views/dialog/mobile-device-create-ios.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=create]')[0].addEventListener('click', (e)=>{
			let txtMobileDeviceName = dialogContainer.querySelectorAll('input[name=name]')[0];
			let txtMobileDeviceSerial = dialogContainer.querySelectorAll('input[name=serial]')[0];
			let txtMobileDeviceNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
			createMobileDeviceIos(
				dialogContainer,
				txtMobileDeviceName.value,
				txtMobileDeviceSerial.value,
				txtMobileDeviceNotes.value
			);
		});
	});
}
function createMobileDeviceIos(dialogContainer, name, serial, notes) {
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
		'create_mobile_device':name, 'notes':notes, 'serial':serial, 'type':'ios'
	}), null, function(response) {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
		window.open('views/dialog/mobile-device-create-ios.php?download_profile='+encodeURIComponent(response), '_blank')
	});
}
function showDialogEditMobileDevice(id) {
	showDialogAjax(LANG['edit_mobile_device'], 'views/dialog/mobile-device-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtEditMobileDeviceId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtEditMobileDeviceName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let txtEditMobileDeviceNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editMobileDevice(
				dialogContainer,
				txtEditMobileDeviceId.value,
				txtEditMobileDeviceName.value,
				txtEditMobileDeviceNotes.value
			);
		});
	});
}
function editMobileDevice(dialogContainer, id, deviceName, notes) {
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
		'edit_mobile_device_id':id,
		'device_name':deviceName,
		'notes':notes
	}), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], deviceName, MESSAGE_TYPE_SUCCESS);
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
	if(confirm(LANG['confirm_delete_mobile_device'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
			refreshContentExplorer('views/mobile-devices.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogAddMobileDeviceToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['mobile_device_groups'], 'views/dialog/mobile-device-group-add.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer) {
		let txtMobileDeviceId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let sltMobileDeviceGroup = dialogContainer.querySelectorAll('select[name=mobile_device_group_id]')[0];
		dialogContainer.querySelectorAll('button[name=add]')[0].addEventListener('click', (e)=>{
			addMobileDeviceToGroup(
				dialogContainer,
				txtMobileDeviceId.value,
				getSelectedSelectBoxValues(sltMobileDeviceGroup, true)
			);
		});
	});
}
function addMobileDeviceToGroup(dialogContainer, mobileDeviceId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	mobileDeviceId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_mobile_device_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['mobile_device_added'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignProfileToGroup(ids) {
	if(!ids) return;
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'id[]', 'value':entry});
	});
	showDialogAjax(LANG['assign'], 'views/dialog/profile-assign.php?'+urlencodeArray(params), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer) {
		dialogContainer.querySelectorAll('button[name=assign]')[0].addEventListener('click', (e)=>{
			let txtIds = dialogContainer.querySelectorAll('input[name=ids]')[0];
			let sltMobileDeviceGroup = dialogContainer.querySelectorAll('select[name=mobile_device_group_id]')[0];
			assignProfileToGroup(
				dialogContainer,
				txtIds.value,
				getSelectedSelectBoxValues(sltMobileDeviceGroup, true)
			);
		});
	});
}
function assignProfileToGroup(dialogContainer, profileId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	profileId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_profile_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		dialogContainer.close();
		refreshContent();
		emitMessage(LANG['profile_assigned'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignManagedAppToGroup(ids) {
	if(!ids) return;
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'id[]', 'value':entry});
	});
	showDialogAjax(LANG['assign'], 'views/dialog/managed-app-assign.php?'+urlencodeArray(params), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=assign]')[0].addEventListener('click', (e)=>{
			let txtIds = dialogContainer.querySelectorAll('input[name=ids]')[0];
			let sltMobileDeviceGroup = dialogContainer.querySelectorAll('select[name=mobile_device_group_id]')[0];
			let chkRemovable = dialogContainer.querySelectorAll('input[name=removable]')[0];
			let chkDisableCloudBackup = dialogContainer.querySelectorAll('input[name=disable_cloud_backup]')[0];
			let chkRemoveOnMdmRemove = dialogContainer.querySelectorAll('input[name=remove_on_mdm_remove]')[0];
			let sltInstallType = dialogContainer.querySelectorAll('select[name=install_type]')[0];
			let sltManagedAppConfig = dialogContainer.querySelectorAll('select[name=managed_app_config_id]')[0];
			let txtManagedAppConfig = dialogContainer.querySelectorAll('textarea[name=managed_app_config_json]')[0];
			assignManagedAppToGroup(
				dialogContainer,
				txtIds.value,
				getSelectedSelectBoxValues(sltMobileDeviceGroup, true),
				typeof chkRemovable !== 'undefined' && chkRemovable.checked ? 1 : 0,
				typeof chkDisableCloudBackup !== 'undefined' && chkDisableCloudBackup.checked ? 1 : 0,
				typeof chkRemoveOnMdmRemove !== 'undefined' && chkRemoveOnMdmRemove.checked ? 1 : 0,
				typeof sltInstallType !== 'undefined' ? sltInstallType.value : '',
				typeof sltManagedAppConfig !== 'undefined' ? sltManagedAppConfig.value : '',
				txtManagedAppConfig.value,
				getSelectedCheckBoxValues('delegated_scopes[]', null, false, dialogContainer)
			);
		});
	});
}
function assignManagedAppToGroup(dialogContainer, managedAppId, groupId, removable, disableCloudBackup, removeOnMdmRemove, installType, configId, config, delegatedScopes) {
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
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['apps_assigned'], '', MESSAGE_TYPE_SUCCESS);
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
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
}

// ======== COMPUTER OPERATIONS ========
function showDialogCreateComputer() {
	showDialogAjax(LANG['create_computer'], 'views/dialog/computer-create.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtComputerHostname = dialogContainer.querySelectorAll('input[name=hostname]')[0];
		let txtComputerNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		let txtComputerAgentKey = dialogContainer.querySelectorAll('input[name=agent_key]')[0];
		let txtComputerServerKey = dialogContainer.querySelectorAll('input[name=server_key]')[0];
		dialogContainer.querySelectorAll('button[name=create]')[0].addEventListener('click', (e)=>{
			createComputer(
				dialogContainer,
				txtComputerHostname.value,
				txtComputerNotes.value,
				txtComputerAgentKey.value,
				txtComputerServerKey.value
			);
		});
	});
}
function createComputer(dialogContainer, hostname, notes, agentKey, serverKey) {
	ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({
		'create_computer': hostname,
		'notes': notes,
		'agent_key': agentKey,
		'server_key': serverKey,
	}), null, function(text) {
		dialogContainer.close();
		refreshContentExplorer('views/computer-details.php?id='+parseInt(text));
		emitMessage(LANG['computer_created'], hostname, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditComputer(id) {
	showDialogAjax(LANG['edit_computer'], 'views/dialog/computer-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtComputerId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtComputerHostname = dialogContainer.querySelectorAll('input[name=hostname]')[0];
		let txtComputerNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editComputer(
				dialogContainer,
				txtComputerId.value,
				txtComputerHostname.value,
				txtComputerNotes.value
			);
		});
	});
}
function editComputer(dialogContainer, id, hostname, notes) {
	ajaxRequestPost('ajax-handler/computers.php', urlencodeObject({
		'edit_computer_id': id,
		'hostname': hostname,
		'notes': notes,
	}), null, function(text) {
		dialogContainer.close(); refreshContent();
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
	ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function() {
		refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
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
	ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function() {
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function() {
			refreshContentExplorer('views/computers.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogAddComputerToGroup(id) {
	if(!id) return;
	showDialogAjax(LANG['computer_groups'], 'views/dialog/computer-group-add.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtComputerId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let sltComputerGroup = dialogContainer.querySelectorAll('select[name=group]')[0];
		dialogContainer.querySelectorAll('button[name=add]')[0].addEventListener('click', (e)=>{
			addComputerToGroup(
				dialogContainer,
				txtComputerId.value,
				getSelectedSelectBoxValues(sltComputerGroup, true)
			);
		});
	});
}
function addComputerToGroup(dialogContainer, computerId, groupId) {
	if(groupId === false) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_id[]', 'value':entry});
	});
	computerId.toString().split(',').forEach(function(entry) {
		params.push({'key':'add_to_group_computer_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['computer_added'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignComputerPackage(computerId) {
	showDialogAjax(LANG['computer_groups'], 'views/dialog/package-select.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=assign]')[0].addEventListener('click', (e)=>{
			assignComputerPackage(
				dialogContainer,
				computerId,
				getSelectedCheckBoxValues('packages', null, true, dialogContainer),
			);
		});
	});
}
function assignComputerPackage(dialogContainer, computerId, packageIds) {
	if(!packageIds) return;
	let params = [];
	params.push({'key':'edit_computer_id', 'value':computerId});
	for(var i = 0; i < packageIds.length; i++) {
		params.push({'key':'add_package_id[]', 'value':packageIds[i]});
	}
	ajaxRequestPost('ajax-handler/computers.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}

// ======== JOB OPERATIONS ========
function showDialogMobileDeviceCommand(mobile_device_id) {
	showDialogAjax(LANG['send_command'], 'views/dialog/mobile-device-command.php?id='+encodeURIComponent(mobile_device_id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtMobileDeviceId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let sltCommand = dialogContainer.querySelectorAll('select[name=command]')[0];
		let txtsParameter = dialogContainer.querySelectorAll('input.parameter');
		let trParameter = dialogContainer.querySelectorAll('tr.parameter')[0];
		let thParameterName = dialogContainer.querySelectorAll('th.parameterName')[0];
		sltCommand.addEventListener('change', (e)=>{
			let parameterName = e.srcElement.selectedOptions[0].getAttribute('parameter');
			if(parameterName) {
				txtsParameter[0].name = parameterName;
				thParameterName.innerText = LANG[parameterName];
				trParameter.style.display = 'table-row';
			} else {
				trParameter.style.display = 'none';
			}
		});
		dialogContainer.querySelectorAll('button[name=send]')[0].addEventListener('click', (e)=>{
			// in the future, there are maybe commands with multiple parameters
			let params = {};
			for(let i=0; i<txtsParameter.length; i++) {
				if(txtsParameter[i].name)
					params[txtsParameter[i].name] = txtsParameter[i].value;
			}
			sendMobileDeviceCommand(
				dialogContainer,
				txtMobileDeviceId.value,
				sltCommand.value,
				params
			);
		});
	});
}
function sendMobileDeviceCommand(dialogContainer, mobile_device_id, name, parameter) {
	var params = [];
	params.push({'key':'send_command_to_mobile_device_id', 'value':mobile_device_id});
	params.push({'key':'command', 'value':name});
	for(const [key, value] of Object.entries(parameter)) {
		params.push({'key':key, 'value':value});
	}
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditProfile(type, id=-1) {
	title = LANG['edit'];
	if(id == -1)
		title = type=='android' ? LANG['new_android_policy'] : LANG['new_ios_profile'];
	showDialogAjax(title, 'views/dialog/profile-edit.php?id='+encodeURIComponent(id)+'&type='+encodeURIComponent(type), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtType = dialogContainer.querySelectorAll('input[name=type]')[0];
		let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let txtNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		let flePayload = dialogContainer.querySelectorAll('input[name=payload]')[0];
		let txtPayload = dialogContainer.querySelectorAll('textarea[name=payload]')[0];
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editProfile(
				dialogContainer,
				txtId.value,
				txtType.value,
				txtName.value,
				txtPayload.value != '' ? txtPayload.value : flePayload.files,
				txtNotes.value
			);
		});
	});
}
function editProfile(dialogContainer, id, type, name, payload, notes) {
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
				dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditDeploymentRule(id=-1) {
	title = LANG['edit_deployment_rule'];
	if(id == -1) {
		title = LANG['new_deployment_rule'];
	}
	showDialogAjax(title, 'views/dialog/deployment-rule-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtDeploymentRuleId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtDeploymentRuleName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let txtDeploymentRuleNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		let chkDeploymentRuleEnabled = dialogContainer.querySelectorAll('input[name=enabled]')[0];
		let sltDeploymentRuleComputerGroupId = dialogContainer.querySelectorAll('select[name=computer_group_id]')[0];
		let sltDeploymentRulePackageGroupId = dialogContainer.querySelectorAll('select[name=package_group_id]')[0];
		let sldDeploymentRulePriority = dialogContainer.querySelectorAll('input[name=priority]')[0];
		let lblDeploymentRulePriorityPreview = dialogContainer.querySelectorAll('div.priorityPreview')[0];
		sldDeploymentRulePriority.addEventListener('input', (e)=>{
			lblDeploymentRulePriorityPreview.innerText = sldDeploymentRulePriority.value;
		});
		sldDeploymentRulePriority.addEventListener('change', (e)=>{
			lblDeploymentRulePriorityPreview.innerText = sldDeploymentRulePriority.value;
		});
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editDeploymentRule(
				dialogContainer,
				txtDeploymentRuleId.value,
				txtDeploymentRuleName.value,
				txtDeploymentRuleNotes.value,
				chkDeploymentRuleEnabled.checked,
				sltDeploymentRuleComputerGroupId.value,
				sltDeploymentRulePackageGroupId.value,
				sldDeploymentRulePriority.value,
			);
		});
	});
}
function editDeploymentRule(dialogContainer, id, name, notes, enabled, computerGroupId, packageGroupId, priority) {
	ajaxRequestPost('ajax-handler/deployment-rules.php', urlencodeObject({
		'edit_deployment_rule_id':id,
		'name':name,
		'notes':notes,
		'enabled':enabled?'1':'0',
		'computer_group_id':computerGroupId,
		'package_group_id':packageGroupId,
		'priority':priority,
	}), null, function(response) {
		dialogContainer.close();
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
	ajaxRequestPost('ajax-handler/deployment-rules.php', urlencodeObject({
		'evaluate_deployment_rule_id': deploymentRuleId
	}), null, function() {
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
	if(confirm(LANG['confirm_delete_deployment_rule'])) {
		ajaxRequestPost('ajax-handler/deployment-rules.php', urlencodeArray(params), null, function() {
			refreshContentExplorer('views/deployment-rules.php'); refreshSidebar();
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogMoveStaticJobToJobContainer(ids) {
	if(!ids) return;
	showDialogAjax(LANG['job_container'], 'views/dialog/jobs-move.php?job_ids='+encodeURIComponent(ids), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer) {
		let txtJobIds = dialogContainer.querySelectorAll('input[name=job_ids]')[0];
		let sltNewJobContainerId = dialogContainer.querySelectorAll('select[name=new_job_container_id]')[0];
		dialogContainer.querySelectorAll('button[name=move]')[0].addEventListener('click', (e)=>{
			moveStaticJobToJobContainer(
				dialogContainer,
				txtJobIds.value,
				sltNewJobContainerId.value,
			);
		});
	});
}
function moveStaticJobToJobContainer(dialogContainer, jobIds, containerIds) {
	if(containerIds === false) return;
	var params = [];
	containerIds.toString().split(',').forEach(function(entry) {
		params.push({'key':'move_to_container_id[]', 'value':entry});
	});
	jobIds.toString().split(',').forEach(function(entry) {
		params.push({'key':'move_to_container_job_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete_job_container'])) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
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
	if(confirm(LANG['confirm_delete_job'])) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogEditJobContainer(id) {
	showDialogAjax(LANG['edit_job_container'], 'views/dialog/job-container-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtJobContainerId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtJobContainerName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let chkJobContainerEnabled = dialogContainer.querySelectorAll('input[name=enabled]')[0];
		let txtJobContainerStartDate = dialogContainer.querySelectorAll('input[name=start_date]')[0];
		let txtJobContainerStartTime = dialogContainer.querySelectorAll('input[name=start_time]')[0];
		let txtJobContainerEndDate = dialogContainer.querySelectorAll('input[name=end_date]')[0];
		let txtJobContainerEndTime = dialogContainer.querySelectorAll('input[name=end_time]')[0];
		let chkJobContainerSequenceMode = dialogContainer.querySelectorAll('input[name=sequence_mode]')[0];
		let txtJobContainerPriority = dialogContainer.querySelectorAll('input[name=priority]')[0];
		let txtJobContainerAgentIpRanges = dialogContainer.querySelectorAll('input[name=agent_ip_ranges]')[0];
		let txtJobContainerTimeFrames = dialogContainer.querySelectorAll('input[name=time_frames]')[0];
		let txtJobContainerNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		txtJobContainerPriority.addEventListener('input', (e)=>{
			e.srcElement.parentElement.querySelectorAll('div.priority_preview')[0].innerText = e.srcElement.value;
		});
		txtJobContainerPriority.addEventListener('change', (e)=>{
			e.srcElement.parentElement.querySelectorAll('div.priority_preview')[0].innerText = e.srcElement.value;
		});
		let btnsClear = dialogContainer.querySelectorAll('td.dualInput > button');
		for(let i=0; i<btnsClear.length; i++) {
			btnsClear[i].addEventListener('click', (e)=>{
				let inputs = e.srcElement.parentElement.querySelectorAll('input');
				for(let n=0; n<inputs.length; n++) {
					inputs[n].value = '';
				}
			});
		}
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editJobContainer(
				dialogContainer,
				txtJobContainerId.value,
				txtJobContainerName.value,
				chkJobContainerEnabled.checked ? 1 : 0,
				txtJobContainerStartDate.value+' '+txtJobContainerStartTime.value,
				txtJobContainerEndDate.value!='' && txtJobContainerEndTime.value!=''
					? txtJobContainerEndDate.value+' '+txtJobContainerEndTime.value : '',
				chkJobContainerSequenceMode.checked ? 1 : 0,
				txtJobContainerPriority.value,
				txtJobContainerAgentIpRanges.value,
				txtJobContainerTimeFrames.value,
				txtJobContainerNotes.value,
			);
		});
	});
}
function editJobContainer(dialogContainer, id, name, enabled, start, end, sequence_mode, priority, agent_ip_ranges, time_frames, notes) {
	ajaxRequestPost('ajax-handler/job-containers.php', urlencodeObject({
		'edit_job_container_id':id,
		'name':name,
		'enabled':enabled,
		'start':start,
		'end':end,
		'sequence_mode':sequence_mode,
		'priority':priority,
		'agent_ip_ranges':agent_ip_ranges,
		'time_frames':time_frames,
		'notes':notes,
	}), null, function() {
		dialogContainer.close();
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
	showDialogAjax(LANG['uninstall_packages'], 'views/dialog/uninstall.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let txtNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		let txtStartDate = dialogContainer.querySelectorAll('input[name=start_date]')[0];
		let txtStartTime = dialogContainer.querySelectorAll('input[name=start_time]')[0];
		let chkWol = dialogContainer.querySelectorAll('input[name=wol]')[0];
		let chkShutdownWaked = dialogContainer.querySelectorAll('input[name=shutdown_waked]')[0];
		let txtEndDate = dialogContainer.querySelectorAll('input[name=end_date]')[0];
		let txtEndTime = dialogContainer.querySelectorAll('input[name=end_time]')[0];
		let sldPriority = dialogContainer.querySelectorAll('input[name=priority]')[0];
		let lblPriorityPreview = dialogContainer.querySelectorAll('.priorityPreview')[0];
		let txtRestartTimeout = dialogContainer.querySelectorAll('input[name=restart_timeout]')[0];
		// add events
		chkWol.addEventListener('click', (e)=>{
			if(e.srcElement.checked) {
				chkShutdownWaked.disabled = false;
			} else {
				chkShutdownWaked.checked = false;
				chkShutdownWaked.disabled=true;
			}
		});
		sldPriority.addEventListener('input', (e)=>{
			lblPriorityPreview.innerText = e.srcElement.value;
		});
		sldPriority.addEventListener('change', (e)=>{
			lblPriorityPreview.innerText = e.srcElement.value;
		});
		dialogContainer.querySelectorAll('button[name=uninstall]')[0].addEventListener('click', (e)=>{
			uninstall(
				dialogContainer,
				'package_id[]',
				txtName.value,
				txtNotes.value,
				txtStartDate.value+' '+txtStartTime.value,
				(txtEndDate.value+' '+txtEndTime.value).trim(),
				chkWol.checked,
				chkShutdownWaked.checked,
				txtRestartTimeout.value,
				sldPriority.value
			);
		});
	});
}
function uninstall(dialogContainer, checkboxName, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, restartTimeout, priority) {
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
	ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
		dialogContainer.close();
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
	if(confirm(LANG['confirm_remove_package_assignment'])) {
		ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
			refreshContent();
			emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogRenewFailedStaticJobs(containerId, jobIds) {
	if(!jobIds) return;
	showDialogAjax(LANG['renew_failed_jobs'], 'views/dialog/jobs-renew.php?job_container_id='+encodeURIComponent(containerId)+'&job_ids='+encodeURIComponent(jobIds), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer) {
		let txtJobContainerId = dialogContainer.querySelectorAll('input[name=job_container_id]')[0];
		let txtJobIds = dialogContainer.querySelectorAll('input[name=job_ids]')[0];
		let chkCreateNewJobContainer = dialogContainer.querySelectorAll('input[name=create_new_job_container]')[0];
		let tbNewJobContainer = dialogContainer.querySelectorAll('tbody.newJobContainer')[0];
		let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let txtNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		let txtStartDate = dialogContainer.querySelectorAll('input[name=start_date]')[0];
		let txtStartTime = dialogContainer.querySelectorAll('input[name=start_time]')[0];
		let txtEndDate = dialogContainer.querySelectorAll('input[name=end_date]')[0];
		let txtEndTime = dialogContainer.querySelectorAll('input[name=end_time]')[0];
		let chkWol = dialogContainer.querySelectorAll('input[name=wol]')[0];
		let chkShutdownWakedAfterCompletion = dialogContainer.querySelectorAll('input[name=shutdown_waked_after_completion]')[0];
		let sldPriority = dialogContainer.querySelectorAll('input[name=priority]')[0];
		let lblPriorityPreview = dialogContainer.querySelectorAll('span.priority_preview')[0];
		sldPriority.addEventListener('input', (e)=>{
			lblPriorityPreview.innerText = sldPriority.value;
		});
		sldPriority.addEventListener('change', (e)=>{
			lblPriorityPreview.innerText = sldPriority.value;
		});
		chkCreateNewJobContainer.addEventListener('click', (e)=>{
			if(e.srcElement.checked) tbNewJobContainer.style.display = 'table-row-group';
			else tbNewJobContainer.style.display = 'none';
		});
		chkWol.addEventListener('click', (e)=>{
			if(e.srcElement.checked) {
				chkShutdownWakedAfterCompletion.disabled = false;
			} else {
				chkShutdownWakedAfterCompletion.checked = false; chkShutdownWakedAfterCompletion.disabled = true;
			}
		});
		dialogContainer.querySelectorAll('button[name=renew]')[0].addEventListener('click', (e)=>{
			renewFailedStaticJobs(
				dialogContainer,
				txtJobContainerId.value,
				txtJobIds.value,
				chkCreateNewJobContainer.checked,
				txtName.value,
				txtNotes.value,
				txtStartDate.value+' '+txtStartTime.value,
				(txtEndDate.value+' '+txtEndTime.value).trim(),
				chkWol.checked,
				chkShutdownWakedAfterCompletion.checked,
				sldPriority.value
			);
		});
	});
}
function renewFailedStaticJobs(dialogContainer, jobContainerId, jobIds, createNewJobContainer, name, notes, startTime, endTime, useWol, shutdownWakedAfterCompletion, priority) {
	var params = [];
	params.push({'key':'renew_job_container', 'value':jobContainerId});
	jobIds.toString().split(',').forEach(function(entry) {
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
	ajaxRequestPost('ajax-handler/job-containers.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshSidebar(); refreshContent();
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
	ajaxRequestPost('ajax-handler/deployment-rules.php', urlencodeArray(params), null, function() {
		refreshSidebar(); refreshContent();
		emitMessage(LANG['jobs_renewed'], '', MESSAGE_TYPE_SUCCESS);
	});
}

// ======== POLICY OPERATIONS ========
function showDialogEditPolicyObject(id=-1, name='') {
	let newName = prompt(LANG['name'], name);
	if(newName) {
		if(id > 0) {
			ajaxRequestPost('ajax-handler/policy-objects.php', urlencodeObject({
				'edit_policy_object_id':id,
				'name':newName,
			}), null, function(response) {
				refreshContent();
				emitMessage(LANG['saved'], newName, MESSAGE_TYPE_SUCCESS);
			});
		} else {
			ajaxRequestPost('ajax-handler/policy-objects.php', urlencodeObject({
				'create_policy_object':newName,
			}), null, function(response) {
				refreshContentExplorer('views/policy-objects.php?id='+parseInt(response));
				emitMessage(LANG['saved'], newName, MESSAGE_TYPE_SUCCESS);
			});
		}
	}
}
function showDialogAssignPolicyObject(policyObjectIds) {
	let params = [];
	if(policyObjectIds) {
		policyObjectIds.forEach(function(entry) {
			params.push({'key':'id[]', 'value':entry});
		});
		showDialogAjax(LANG['assign'], 'views/dialog/policy-object-assign.php?'+urlencodeArray(params), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
			dialogContainer.querySelectorAll('button[name=assign]')[0].addEventListener('click', (e)=>{
				var params = [];
				let policyObjectIds = dialogContainer.querySelectorAll('input[name=ids]')[0].value.split(',');
				let computerGroupIds = getSelectedSelectBoxValues(dialogContainer.querySelectorAll('select[name=computer_group_id]')[0], false);
				let domainUserGroupIds = getSelectedSelectBoxValues(dialogContainer.querySelectorAll('select[name=domain_user_group_id]')[0], false);
				if(!computerGroupIds && !domainUserGroupIds) return;
				computerGroupIds.forEach(function(entry) {
					params.push({'key':'add_to_computer_group_id[]', 'value':entry});
				});
				domainUserGroupIds.forEach(function(entry) {
					params.push({'key':'add_to_domain_user_group_id[]', 'value':entry});
				});
				policyObjectIds.forEach(function(entry) {
					params.push({'key':'policy_object_id[]', 'value':entry});
				});
				ajaxRequestPost('ajax-handler/policy-objects.php', urlencodeArray(params), null, function() {
					dialogContainer.close(); refreshContent();
					emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
				});
			});
		});
	}
}
function showDialogPolicyObjectOverview(id) {
	showDialogAjax(LANG['overview'], 'views/dialog/policy-object-overview.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_LARGE);
}

// ======== DOMAIN USER OPERATIONS ========
function showDialogEditDomainUserRole(id=-1) {
	title = LANG['edit_domain_user_role'];
	if(id == -1) title = LANG['create_domain_user_role'];
	showDialogAjax(title, 'views/dialog/domain-user-role-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtDomainUserRoleId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtDomainUserRoleName = dialogContainer.querySelectorAll('input[name=name]')[0];
		let txtDomainUserRolePermissions = dialogContainer.querySelectorAll('textarea[name=permissions]')[0];
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editDomainUserRole(
				dialogContainer,
				txtDomainUserRoleId.value,
				txtDomainUserRoleName.value,
				txtDomainUserRolePermissions.value
			);
		});
	});
}
function editDomainUserRole(dialogContainer, id, name, permissions) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_domain_user_role_id':id,
		'name':name,
		'permissions':permissions,
	}), null, function(response) {
		dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogEditDomainUser(id=-1) {
	title = LANG['edit_domain_user'];
	if(id == -1) title = LANG['create_domain_user'];
	showDialogAjax(title, 'views/dialog/domain-user-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtDomainUserId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtDomainUserUsername = dialogContainer.querySelectorAll('input[name=username]')[0];
		let sltDomainUserRoleId = dialogContainer.querySelectorAll('select[name=domain_user_role_id]')[0];
		let txtDomainUserPassword = dialogContainer.querySelectorAll('input[name=password]')[0];
		let txtDomainUserPasswordConfirm = dialogContainer.querySelectorAll('input[name=password_confirm]')[0];
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			if(txtDomainUserPassword.value != txtDomainUserPasswordConfirm.value) {
				emitMessage(LANG['passwords_do_not_match'], '', MESSAGE_TYPE_WARNING);
				return false;
			}
			editDomainUser(
				dialogContainer,
				txtDomainUserId.value,
				txtDomainUserUsername.value,
				txtDomainUserPassword.value,
				sltDomainUserRoleId.value
			);
		});
	});
}
function editDomainUser(dialogContainer, id, username, password, roleId) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_domain_user_id':id,
		'password':password,
		'domain_user_role_id':roleId,
	}), null, function() {
		dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/domain-users.php', urlencodeArray(params), null, function() {
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
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/reports.php', urlencodeArray(params), null, function() {
			refreshContentExplorer('views/reports.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function showDialogEditReport(id=-1, reportGroupId=-1) {
	showDialogAjax(id>0 ? LANG['edit_report'] : LANG['create_report'], 'views/dialog/report-edit.php?id='+encodeURIComponent(id)+'&report_group_id='+encodeURIComponent(reportGroupId), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editReport(
				dialogContainer,
				dialogContainer.querySelectorAll('input[name=id]')[0].value,
				dialogContainer.querySelectorAll('select[name=report_group_id]')[0].value,
				dialogContainer.querySelectorAll('input[name=name]')[0].value,
				dialogContainer.querySelectorAll('textarea[name=notes]')[0].value,
				dialogContainer.querySelectorAll('textarea[name=query]')[0].value,
			);
		});
	});
}
function editReport(dialogContainer, id, reportGroupId, name, notes, query) {
	var params = [
		{'key':'report_group_id', 'value':reportGroupId},
		{'key':'name', 'value':name},
		{'key':'notes', 'value':notes},
		{'key':'query', 'value':query},
	];
	if(id > 0)
		params.push({'key':'edit_report_id', 'value':id});
	else
		params.push({'key':'create_report', 'value':name});
	ajaxRequestPost('ajax-handler/reports.php', urlencodeArray(params), null, function(text) {
		dialogContainer.close();
		if(id > 0) {
			refreshContent();
			emitMessage(LANG['saved'], name, MESSAGE_TYPE_SUCCESS);
		} else {
			refreshContentExplorer('views/report-details.php?id='+parseInt(text));
			emitMessage(LANG['report_created'], name, MESSAGE_TYPE_SUCCESS);
		}
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/reports.php', urlencodeArray(params), null, function() {
			if(redirect != null) currentExplorerContentUrl = redirect;
			refreshContentExplorer(currentExplorerContentUrl);
			emitMessage(LANG['object_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
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
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
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
	ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
		refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditOwnSystemUserPassword() {
	showDialogAjax(LANG['change_password'], 'views/dialog/system-user-edit-own-password.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			let txtOldPassword = dialogContainer.querySelectorAll('input[name=old_password]')[0];
			let txtNewPassword = dialogContainer.querySelectorAll('input[name=new_password]')[0];
			let txtNewPasswordConfirm = dialogContainer.querySelectorAll('input[name=new_password_confirm]')[0];
			if(txtNewPassword.value != txtNewPasswordConfirm.value) {
				emitMessage(LANG['passwords_do_not_match'], '', MESSAGE_TYPE_WARNING);
				return false;
			}
			editOwnSystemUserPassword(
				dialogContainer,
				txtOldPassword.value,
				txtNewPassword.value
			);
		});
	});
}
function editOwnSystemUserPassword(dialogContainer, oldPassword, newPassword) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_own_system_user_password':newPassword,
		'old_password':oldPassword,
	}), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUser(id=-1) {
	title = LANG['edit_system_user'];
	if(id == -1) title = LANG['create_system_user'];
	showDialogAjax(title, 'views/dialog/system-user-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			let txtId = dialogContainer.querySelectorAll('input[name=id]')[0];
			let txtUsername = dialogContainer.querySelectorAll('input[name=username]')[0];
			let txtDisplayName = dialogContainer.querySelectorAll('input[name=display_name]')[0];
			let txtDescription = dialogContainer.querySelectorAll('textarea[name=description]')[0];
			let txtNewPassword = dialogContainer.querySelectorAll('input[name=new_password]')[0];
			let txtNewPasswordConfirm = dialogContainer.querySelectorAll('input[name=new_password_confirm]')[0];
			let sltRole = dialogContainer.querySelectorAll('select[name=system_user_role_id]')[0];
			if(txtNewPassword.value != txtNewPasswordConfirm.value) {
				emitMessage(LANG['passwords_do_not_match'], '', MESSAGE_TYPE_WARNING);
				return false;
			}
			editSystemUser(
				dialogContainer,
				txtId.value,
				txtUsername.value,
				txtDisplayName.value,
				txtDescription.value,
				txtNewPassword.value,
				sltRole.value
			);
		});
	});
}
function editSystemUser(dialogContainer, id, username, displayName, description, password, roleId) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_system_user_id':id,
		'username':username,
		'display_name':displayName,
		'description':description,
		'password':password,
		'system_user_role_id':roleId,
	}), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], username, MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogEditSystemUserRole(id=-1) {
	title = LANG['edit_system_user_role'];
	if(id == -1) title = LANG['create_system_user_role'];
	showDialogAjax(title, 'views/dialog/system-user-role-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			let txtId = dialogContainer.querySelectorAll('input[name=id]')[0];
			let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
			let txtPermissions = dialogContainer.querySelectorAll('textarea[name=permissions]')[0];
			editSystemUserRole(
				dialogContainer,
				txtId.value,
				txtName.value,
				txtPermissions.value,
			)
		});
	});
}
function editSystemUserRole(dialogContainer, id, name, permissions) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_system_user_role_id':id,
		'name':name,
		'permissions':permissions,
	}), null, function(response) {
		dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

// ======== SETTINGS OPERATIONS ========
function showDialogEditEventQueryRule(id=-1) {
	title = LANG['change'];
	if(id == -1) title = LANG['create'];
	showDialogAjax(title, 'views/dialog/event-query-rule-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtEventQueryRuleId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtEventQueryRuleLog = dialogContainer.querySelectorAll('input[name=log]')[0];
		let txtEventQueryRuleQuery = dialogContainer.querySelectorAll('textarea[name=query]')[0];
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editEventQueryRule(
				dialogContainer,
				txtEventQueryRuleId.value,
				txtEventQueryRuleLog.value,
				txtEventQueryRuleQuery.value
			);
		});
	});
}
function editEventQueryRule(dialogContainer, id, log, query) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_event_query_rule_id':id,
		'log':log,
		'query':query,
	}), null, function(response) {
		dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditPasswordRotationRule(id=-1) {
	showDialogAjax('', 'views/dialog/password-rotation-rule-edit.php?id='+encodeURIComponent(id), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editPasswordRotationRule(
				dialogContainer,
				dialogContainer.querySelectorAll('input[name=id]')[0].value,
				dialogContainer.querySelectorAll('select[name=computer_group_id]')[0].value,
				dialogContainer.querySelectorAll('input[name=username]')[0].value,
				dialogContainer.querySelectorAll('input[name=alphabet]')[0].value,
				dialogContainer.querySelectorAll('input[name=length]')[0].value,
				dialogContainer.querySelectorAll('input[name=valid_seconds]')[0].value,
				dialogContainer.querySelectorAll('input[name=history]')[0].value,
				dialogContainer.querySelectorAll('input[name=default_password]')[0].value,
			);
		});
	});
}
function editPasswordRotationRule(dialogContainer, id, computer_group_id, username, alphabet, length, valid_seconds, history, default_password) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_password_rotation_rule_id':id,
		'computer_group_id':computer_group_id,
		'username':username,
		'alphabet':alphabet,
		'length':length,
		'valid_seconds':valid_seconds,
		'history':history,
		'default_password':default_password,
	}), null, function(response) {
		dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditGeneralConfig() {
	showDialogAjax(LANG['oco_configuration'], 'views/dialog/general-config-edit.php', DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editGeneralConfig(
				dialogContainer,
				dialogContainer.querySelectorAll('input[name=client-api-enabled]')[0].checked ? 1 : 0,
				dialogContainer.querySelectorAll('input[name=client-api-key]')[0].value,
				dialogContainer.querySelectorAll('input[name=agent-registration-enabled]')[0].checked ? 1 : 0,
				dialogContainer.querySelectorAll('input[name=agent-registration-key]')[0].value,
				dialogContainer.querySelectorAll('input[name=assume-computer-offline-after]')[0].value,
				dialogContainer.querySelectorAll('input[name=wol-shutdown-expiry]')[0].value,
				dialogContainer.querySelectorAll('input[name=agent-update-interval]')[0].value,
				dialogContainer.querySelectorAll('input[name=purge-succeeded-jobs-after]')[0].value,
				dialogContainer.querySelectorAll('input[name=purge-failed-jobs-after]')[0].value,
				dialogContainer.querySelectorAll('input[name=purge-domain-user-logons-after]')[0].value,
				dialogContainer.querySelectorAll('input[name=purge-events-after]')[0].value,
				dialogContainer.querySelectorAll('select[name=log-level]')[0].value,
				dialogContainer.querySelectorAll('input[name=purge-logs-after]')[0].value,
				dialogContainer.querySelectorAll('input[name=computer-keep-inactive-screens]')[0].checked ? 1 : 0,
				dialogContainer.querySelectorAll('input[name=self-service-enabled]')[0].checked ? 1 : 0,
			);
		});
	});
}
function editGeneralConfig(dialogContainer, clientApiEnabled, clientApiKey, agentRegistrationEnabled, agentRegistrationKey, assumeComputerOfflineAfter, wolShutdownExpiry, agentUpdateInterval, purgeSucceededJobsAfter, purgeFailedJobsAfter, purgeDomainUserLogonsAfter, purgeEventsAfter, logLevel, purgeLogsAfter, keepInactiveScreens, selfServiceEnabled) {
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({
		'edit_general_config':1,
		'client_api_enabled':clientApiEnabled,
		'client_api_key':clientApiKey,
		'agent_registration_enabled':agentRegistrationEnabled,
		'agent_registration_key':agentRegistrationKey,
		'assume_computer_offline_after':assumeComputerOfflineAfter,
		'wol_shutdown_expiry':wolShutdownExpiry,
		'agent_update_interval':agentUpdateInterval,
		'purge_succeeded_jobs_after':purgeSucceededJobsAfter,
		'purge_failed_jobs_after':purgeFailedJobsAfter,
		'purge_domain_user_logons_after':purgeDomainUserLogonsAfter,
		'purge_events_after':purgeEventsAfter,
		'log_level':logLevel,
		'purge_logs_after':purgeLogsAfter,
		'computer_keep_inactive_screens':keepInactiveScreens,
		'self_service_enabled':selfServiceEnabled,
	}), null, function(text) {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['saved'], LANG['oco_configuration'], MESSAGE_TYPE_SUCCESS);
	});
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
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({'ldap_sync_system_users':1}), null, function(text) {
		refreshContent();
		emitMessage(LANG['ldap_sync'], text, MESSAGE_TYPE_SUCCESS);
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function ldapSyncDomainUsers(btn) {
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/settings.php', urlencodeObject({'ldap_sync_domain_users':1}), null, function(text) {
		refreshContent();
		emitMessage(LANG['ldap_sync'], text, MESSAGE_TYPE_SUCCESS);
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function showDialogEditSetting(key='', file=false, warning='be_careful_when_manual_editing_settings', hideKey=false, title=null) {
	if(title == null) {
		title = LANG['edit_setting'];
		if(key == '')
			title = LANG['create_setting'];
	}
	showDialogAjax(title,
		'views/dialog/setting-edit.php?key='+encodeURIComponent(key)+'&file='+encodeURIComponent(file?file:0)+'&warning='+encodeURIComponent(warning?warning:0)+'&hideKey='+(hideKey?1:0),
		DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO,
		function(dialogContainer){
			dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
				let txtSettingKey = dialogContainer.querySelectorAll('input[name=key]')[0];
				let txtSettingValue = dialogContainer.querySelectorAll('textarea[name=value]')[0];
				let fleSettingValue = dialogContainer.querySelectorAll('input[name=value]')[0];
				editSetting(
					dialogContainer,
					txtSettingKey.value,
					fleSettingValue.files.length ? readFileInputBlob(fleSettingValue.files[0]) : txtSettingValue.value
				);
			});
		}
	);
}
function editSetting(dialogContainer, key, value) {
	let req = new XMLHttpRequest();
	let formData = new FormData();
	formData.append('edit_setting', key);
	formData.append('value', value);
	req.onreadystatechange = function() {
		if(this.readyState == 4) {
			if(this.status == 200) {
				dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['really_delete'])) {
		ajaxRequestPost('ajax-handler/settings.php', urlencodeArray(params), null, function() {
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
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'sync_apps_profiles':1}), null, function(text) {
		emitMessage(LANG['apps_profiles_policies_synced'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}

function syncAppleDevices(btn) {
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'sync_apple_devices':1}), null, function(text) {
		emitMessage(LANG['sync_apple_devices'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function syncAppleAssets(btn) {
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'sync_apple_assets':1}), null, function(text) {
		emitMessage(LANG['sync_apple_vpp'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}

function showDialogManagedPlayStore() {
	// get the token for the Play Store iframe
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'get_playstore_token':1}), null, function(token) {
		// load the Google JS
		var script = document.createElement('script');
		script.src = 'https://apis.google.com/js/api.js';
		script.onload = function() {
			// show the OCO dialog
			let dialogContainer = showDialog(LANG['manage_android_apps'], '', DIALOG_BUTTONS_CLOSE);
			let dialogText  = dialogContainer.querySelectorAll('.dialogText')[0];
			// embed the Play Store iframe into the dialog
			gapi.load('gapi.iframes', function() {
				var options = {
					'url': 'https://play.google.com/work/embedded/search?token='+encodeURIComponent(token)+'&mode=SELECT',
					'where': dialogText,
					'attributes': { style:'height:100%; display:block', scrolling:'yes'}
				};
				var iframe = gapi.iframes.getContext().openChild(options);
				iframe.register('onproductselect', function(event) {
					var displayName = prompt(LANG['display_name']);
					if(displayName) {
						ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
							'playstore_onproductselect':event.action,
							'package_name':event.packageName,
							'product_id':event.productId,
							'app_name':displayName,
						}), null, function(response) {
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
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'get_playstore_token':1}), null, function(token) {
		// load the Google JS
		var script = document.createElement('script');
		script.src = 'https://apis.google.com/js/api.js';
		script.onload = function() {
			// show the OCO dialog
			let dialogContainer = showDialog(LANG['manage_zero_touch_enrollment'], '', DIALOG_BUTTONS_CLOSE);
			let dialogText = dialogContainer.querySelectorAll('.dialogText')[0];
			// embed the Play Store iframe into the dialog
			gapi.load('gapi.iframes', function() {
				var options = {
					'url': 'https://enterprise.google.com/android/zero-touch/embedded/companyhome?token='+encodeURIComponent(token)+'&dpcId=com.google.android.apps.work.clouddpc',
					'where': dialogText,
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
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'get_playstore_token':1}), null, function(token) {
		// load the Google JS
		var script = document.createElement('script');
		script.src = 'https://apis.google.com/js/api.js';
		script.onload = function() {
			// show the OCO dialog
			let dialogContainer = showDialog(packageName, '', DIALOG_BUTTONS_CLOSE);
			let dialogText = dialogContainer.querySelectorAll('.dialogText')[0];
			// embed the Play Store iframe into the dialog
			gapi.load('gapi.iframes', function() {
				var options = {
					'url': 'https://play.google.com/managed/mcm?token='+encodeURIComponent(token)+'&packageName='+encodeURIComponent(packageName)+(configId ? '&mcmId='+encodeURIComponent(configId)+'&canDelete=TRUE' : ''),
					'where': dialogText,
					'attributes': { style:'height:100%; display:block', scrolling:'yes'}
				};
				var iframe = gapi.iframes.getContext().openChild(options);
				iframe.register('onconfigupdated', function(event) {
					ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
						'playstore_onconfigupdated':event.mcmId,
						'name':event.name,
						'managed_app_id':managedAppId,
					}), null, function(response) {
						emitMessage(LANG['saved'], event.name, MESSAGE_TYPE_SUCCESS);
						dialogContainer.close(); refreshContent();
					});
				}, gapi.iframes.CROSS_ORIGIN_IFRAMES_FILTER);
				iframe.register('onconfigdeleted', function(event) {
					ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({
						'playstore_onconfigdeleted':event.mcmId,
						'managed_app_id':managedAppId,
					}), null, function(response) {
						emitMessage(LANG['configuration_deleted'], '', MESSAGE_TYPE_SUCCESS);
						dialogContainer.close(); refreshContent();
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
	if(confirm(LANG['really_delete'])) {
		ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
			emitMessage(LANG['object_deleted'], null, MESSAGE_TYPE_SUCCESS);
			refreshContent();
		});
	}
}
function syncAndroidDevices(btn) {
	enableDisableButton(btn, false);
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeObject({'sync_android_devices':1}), null, function(text) {
		emitMessage(LANG['sync_android_devices'], text, MESSAGE_TYPE_SUCCESS);
		refreshContent();
	}, function(status, statusText, responseText){
		enableDisableButton(btn, true);
		emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR, null);
	});
}
function showDialogAssignedProfileInfo(groupId, profileId) {
	showDialogAjax(LANG['managed_app'], 'views/dialog/mobile-device-assigned-profile-info.php?mobile_device_group_id='+encodeURIComponent(groupId)+'&profile_id='+encodeURIComponent(profileId), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=remove]')[0].addEventListener('click', (e)=>{
			removeProfileFromGroup(dialogContainer, [profileId], groupId);
		});
	});
}
function removeProfileFromGroup(dialogContainer, ids, groupId) {
	if(!confirm(LANG['are_you_sure'])) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'remove_from_group_id[]', 'value':entry});
	});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_profile_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
}
function showDialogAssignedManagedAppInfo(groupId, managedAppId) {
	showDialogAjax(LANG['managed_app'], 'views/dialog/mobile-device-assigned-managed-app-info.php?mobile_device_group_id='+encodeURIComponent(groupId)+'&managed_app_id='+encodeURIComponent(managedAppId), DIALOG_BUTTONS_CLOSE, DIALOG_SIZE_AUTO, function(dialogContainer){
		dialogContainer.querySelectorAll('button[name=remove]')[0].addEventListener('click', (e)=>{
			removeManagedAppFromGroup(dialogContainer, [managedAppId], groupId);
		});
	});
}
function removeManagedAppFromGroup(dialogContainer, ids, groupId) {
	if(!confirm(LANG['are_you_sure'])) return;
	var params = [];
	groupId.toString().split(',').forEach(function(entry) {
		params.push({'key':'remove_from_group_id[]', 'value':entry});
	});
	ids.forEach(function(entry) {
		params.push({'key':'remove_from_group_managed_app_id[]', 'value':entry});
	});
	ajaxRequestPost('ajax-handler/mobile-devices.php', urlencodeArray(params), null, function() {
		dialogContainer.close(); refreshContent();
		emitMessage(LANG['object_removed_from_group'], '', MESSAGE_TYPE_SUCCESS);
	});
}

function checkUpdate() {
	ajaxRequestPost('ajax-handler/update-check.php', '', null, function(text) {
		if(text.trim() != '') {
			emitMessage(LANG['update_available'], text.trim(), MESSAGE_TYPE_INFO);
		}
	});
}
