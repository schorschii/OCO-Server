function initTables(root=document) {
	var tables = root.querySelectorAll('table');
	for(var i = 0; i < tables.length; i++) {
		let table = tables[i];

		// count total rows
		let tbody = table.getElementsByTagName('tbody');
		let counter = tbody[0].getElementsByTagName('tr').length;
		let counterNodes = table.querySelectorAll('tfoot span.counterTotal, tfoot span.counterFiltered');
		if(counterNodes) for(var n = 0; n < counterNodes.length; n++) {
			counterNodes[n].textContent = String(counter);
		}

		// init CSV download buttons
		let buttons = table.querySelectorAll('button.downloadCsv');
		if(buttons) for(var n = 0; n < buttons.length; n++) {
			buttons[n].onclick = function() { downloadTableCsv(table) }
		}

		// init checkboxes
		let checkboxes = table.querySelectorAll('input[type=checkbox]');
		if(checkboxes) for(var n = 0; n < checkboxes.length; n++) {
			if(checkboxes[n].classList.contains('toggleAllChecked')) {
				checkboxes[n].onchange = function() { toggleCheckboxesInTable(table, this.checked) }
			} else {
				checkboxes[n].onchange = function() { refreshCheckedCounter(table) }
			}
		}

		// find all tables which should be sortable
		if(table.classList.contains('sortable')) {
			new TableSortUltra(table);
		}

		// find all tables which should be searchable
		if(table.classList.contains('searchable')) {
			let thead = table.getElementsByTagName('thead')[0];
			let trSearch = document.createElement('tr');
			let tr = thead.getElementsByTagName('tr')[0];
			let ths = tr.getElementsByTagName('th');
			// get prev table state
			var startSearch = false;
			var storage = null;
			if(localStorage && table.id) {
				let tmpStore = localStorage.getItem(table.id);
				if(tmpStore) storage = JSON.parse(tmpStore);
			}
			// init search text boxes
			for(var n = 0; n < ths.length; n++) {
				var thSearch = document.createElement('th');
				if(ths[n].classList.contains('searchable')) {
					let txtSearch = document.createElement('input');
					txtSearch.setAttribute('type', 'search');
					txtSearch.setAttribute('placeholder', LANG['search_placeholder']);
					txtSearch.classList.add('tableSearch');
					txtSearch.addEventListener('input', function(){ tableSearch(table) });
					txtSearch.addEventListener('paste', function(){ tableSearch(table) });
					thSearch.appendChild(txtSearch);
					// restore previous search
					if(storage && typeof(storage['search']) != 'undefined'
					&& typeof(storage['search'][n]) != 'undefined' && storage['search'][n] != '') {
						txtSearch.value = storage['search'][n];
						startSearch = true;
					}
				}
				trSearch.appendChild(thSearch);
			}
			thead.appendChild(trSearch);
			if(startSearch) tableSearch(table);
		}
	}
}

var packageDragAndDropEnabled = false;
var TableSortUltra = function(table) {
	var me = this;

	// get rows
	var thead = table.tHead;
	if(thead) {
		var tr_in_thead = thead.querySelectorAll('tr.sortable');
		if(!tr_in_thead.length) tr_in_thead = thead.rows;
	}
	if(tr_in_thead) var tabletitel = tr_in_thead[0].cells;
	if( !(tabletitel && tabletitel.length > 0) ) {
		console.warn('Table has no <TH>');
		return null;
	}
	var tbdy = table.tBodies;
	if( !tbdy || tbdy.length == 0 ) {
		console.warn('Table has no <TBODY>');
		return null;
	}
	tbdy = tbdy[0];
	var tr = tbdy.rows;
	if( tr.length == 0 ) {
		//console.warn('Table has no rows in <TBODY>');
		return null;
	}
	var nrows = tr.length;
	var ncols = tr[0].cells.length;

	// vars
	var arr = [];
	var sorted = -1;
	var sortsymbols = [];
	var sortbuttons = [];
	var sorttype = [];
	var firstsort = [];
	var startsort_u = -1, startsort_d = -1;
	var savesort = table.classList.contains('savesort') && table.id && table.id.length>0 && localStorage;
	var minsort = -1;

	var initTableHead = function(col) {
		if(tabletitel[col].classList.contains('sortable-')) {
			firstsort[col] = 'desc';
		}
		else if(tabletitel[col].classList.contains('sortable')) {
			firstsort[col] = 'asc';
		} else {
			return false;
		}
		var sortbutton = document.createElement('button');
		sortbutton.innerHTML = tabletitel[col].innerHTML;
		sortbutton.title = LANG['order_by'] + ' ' + tabletitel[col].innerHTML;
		sortbutton.className = 'sortbutton';
		sortbutton.type = 'button';
		sortbuttons[col] = sortbutton;
		var sortsymbol = sortbutton;
		var symbolspan = sortbutton.querySelectorAll('span');
		if(symbolspan && symbolspan.length) {
			for(var i=0;i<symbolspan.length;i++) {
				if(!symbolspan[i].hasChildNodes()) {
					sortsymbol = symbolspan[i];
					break;
				}
			}
		}
		sortsymbol.classList.add('sortsymbol');
		if(tabletitel[col].classList.contains('vorsortiert-')) {
			sortsymbol.classList.add('sorteddesc');
			sorted = col;
		} else if(tabletitel[col].classList.contains('vorsortiert')) {
			sortsymbol.classList.add('sortedasc');
			sorted = col;
		} else {
			sortsymbol.classList.add('unsorted');
		}
		sortsymbols[col] = sortsymbol;
		if(tabletitel[col].classList.contains('sortiere-')) {
			startsort_d = col;
		} else if(tabletitel[col].classList.contains('sortiere')) {
			startsort_u = col;
		}
		sortbutton.addEventListener('click',function() { me.tsort(col); },false);
		tabletitel[col].innerHTML = '';
		tabletitel[col].appendChild(sortbutton);
		return true;
	} // initTableHead

	var getCellData = function(ele, col) {
		if(!ele) return '';
		var val = ele.textContent;
		if(ele.getAttribute('sort_key')) val = ele.getAttribute('sort_key');
		if(isNaN(val)) sorttype[col] = 's';
		return val;
	}

	var compareString = function(a,b) {
		var ret = a[sorted].localeCompare(b[sorted]);
		if(!ret && sorted != minsort) {
			if(sorttype[minsort] == 's') {
				ret = a[minsort].localeCompare(b[minsort]);
			} else {
				ret = a[minsort] - b[minsort];
			}
		}
		return ret;
	}

	var compareNumber = function(a,b) {
		var ret = a[sorted] - b[sorted];
		if(!ret && sorted != minsort) {
			if(sorttype[minsort] == 's') {
				ret = a[minsort].localeCompare(b[minsort]);
			} else {
				ret = a[minsort] - b[minsort];
			}
		}
		return ret;
	}

	// main sort function
	this.tsort = function(col) {
		if(col == sorted) { // table already sorted, only reverse order
			arr.reverse();
			sortsymbols[col].classList.toggle('sortedasc');
			sortsymbols[col].classList.toggle('sorteddesc');
		} else {
			if(sorted > -1) {
				sortsymbols[sorted].classList.remove('sortedasc');
				sortsymbols[sorted].classList.remove('sorteddesc');
				sortsymbols[sorted].classList.add('unsorted');
				sortbuttons[sorted].removeAttribute('aria-current');
			}
			sorted = col;
			sortsymbols[col].classList.remove('unsorted');
			sortbuttons[col].setAttribute('aria-current','true');
			if(sorttype[col] == 'n') {
				arr.sort(compareNumber);
			} else {
				arr.sort(compareString);
			}
			if(firstsort[col] == 'desc') {
				arr.reverse();
				sortsymbols[col].classList.add('sorteddesc');
			} else {
				sortsymbols[col].classList.add('sortedasc');
			}
		}

		// write sorted data to table
		for(var r = 0; r < nrows; r++) {
			tbdy.appendChild(arr[r][ncols]);
		}

		// save sort state
		if(savesort && table.id) {
			// get prev table state
			var storage = null;
			if(localStorage && savesort) {
				let tmpStore = localStorage.getItem(table.id);
				if(tmpStore) storage = JSON.parse(tmpStore);
			}

			if(!storage) storage = {};
			storage['sort'] = sorted;
			storage['desc'] = sortsymbols[sorted].classList.contains('sorteddesc');
			localStorage.setItem(table.id, JSON.stringify(storage));
		}

		// enable drag and drop only if package list is sorted by sequence ascending
		packageDragAndDropEnabled = (table.id == 'tblPackageData' && col == 8 && sortsymbols[col].classList.contains('sortedasc'));
		togglePackageDragAndDrop(table, packageDragAndDropEnabled);
	}

	// check if there is a 'sortable' class in thead
	var sortflag = false;
	for(var c = 0; c < tabletitel.length; c++) {
		sortflag |= tabletitel[c].classList.contains('sortable') || tabletitel[c].classList.contains('sortable-');
	}
	if(!sortflag) {
		for(var c = 0; c<tabletitel.length; c++) tabletitel[c].classList.add('sortable');
	}

	// prepare headers
	for(var c = tabletitel.length-1; c >= 0; c--) if(initTableHead(c)) minsort = c;

	// default sort type : number
	for(var c = 0; c < ncols; c++) sorttype[c] = 'n';

	// copy cell contents to array
	for(var r = 0; r < nrows; r++) {
		arr[r] = [];
		for(var c = 0; c < ncols; c++) {
			arr[r][c] = getCellData(tr[r].cells[c], c);
		}
		arr[r][ncols] = tr[r];
	}

	// execute saved/default sort
	var storage = null;
	if(localStorage && savesort && table.id) {
		let tmpStore = localStorage.getItem(table.id);
		if(tmpStore) storage = JSON.parse(tmpStore);
	}
	if(storage && typeof(storage.sort) != 'undefined' && typeof(storage.desc) != 'undefined') {
		if(storage.desc) {
			startsort_d = storage.sort; startsort_u = -1;
		} else {
			startsort_u = storage.sort; startsort_d = -1;
		}
	}
	if(startsort_u >= 0 && startsort_u < ncols) {
		me.tsort(startsort_u);
	}
	if(startsort_d >= 0 && startsort_d < ncols) {
		firstsort[startsort_d] = 'desc';
		me.tsort(startsort_d);
	}

}

function tableSearch(table) {
	var count = 0, txtValue, active = false, conditions = [];

	// get prev table state
	var storage = null;
	if(localStorage && table.id) {
		let tmpStore = localStorage.getItem(table.id);
		if(tmpStore) storage = JSON.parse(tmpStore);
	}

	// get search conditions from table head
	thead = table.getElementsByTagName('thead');
	if(!thead || thead.length == 0) return;
	trs = thead[0].getElementsByTagName('tr');
	for(var i = 0; i < trs.length; i++) {
		ths = trs[i].getElementsByTagName('th');
		for(var n = 0; n < ths.length; n++) {
			let inputs = ths[n].querySelectorAll('input.tableSearch');
			for(var m = 0; m < inputs.length; m++) {
				conditions[n] = inputs[m].value.toUpperCase();
				if(inputs[m].value != '') active = true;

				// save state
				if(localStorage && table.id) {
					if(!storage) storage = {};
					if(!storage['search']) storage['search'] = {};
					storage['search'][n] = inputs[m].value;
					localStorage.setItem(table.id, JSON.stringify(storage));
				}
			}
		}
	}

	// get table body
	tbody = table.getElementsByTagName('tbody');
	if(!tbody || tbody.length == 0) return;

	// get all rows in table body
	trs = tbody[0].getElementsByTagName('tr');
	for(var i = 0; i < trs.length; i++) {
		var visible = true;
		tds = trs[i].getElementsByTagName('td');
		if(!tds || tds.length == 0) continue;
		for(var n = 0; n < tds.length; n++) {
			txtValue = tds[n].textContent || tds[n].textContent;
			if(!active || !(n in conditions) || conditions[n] == ''
				|| txtValue.toUpperCase().includes(conditions[n])
			) {
				trs[i].style.display = '';
			} else {
				trs[i].style.display = 'none';
				visible = false;
				break;
			}
		}
		if(visible) {
			count = count + 1;
		}
	}

	// refresh counter
	let counterNodes = table.querySelectorAll('tfoot span.counterFiltered');
	if(counterNodes) for(var n = 0; n < counterNodes.length; n++) {
		counterNodes[n].textContent = String(count);
	}
}

function toggleCheckboxesInTable(table, checked) {
	// iterate over all checkboxes and toggle
	let trs = table.getElementsByTagName('tr');
	for(var i = 0; i < trs.length; i++) {
		if(trs[i].style.display == 'none') continue;
		let inputs = trs[i].getElementsByTagName('input');
		for(var n = 0; n < inputs.length; n++) {
			if(inputs[n].type == 'checkbox') {
				inputs[n].checked = checked;
			}
		}
	}
	refreshCheckedCounter(table);
}

function refreshCheckedCounter(table) {
	// count
	let counter = 0;
	let tbody = table.getElementsByTagName('tbody');
	if(tbody && tbody.length > 0) {
		let inputs = tbody[0].getElementsByTagName('input');
		for(var i = 0; i < inputs.length; i++) {
			if(inputs[i].type == 'checkbox' && inputs[i].checked) {
				counter ++;
			}
		}
	}
	// refresh counter
	let counterNodes = table.querySelectorAll('tfoot span.counterSelected');
	if(counterNodes) for(var n = 0; n < counterNodes.length; n++) {
		counterNodes[n].textContent = String(counter);
	}
}

function downloadTableCsv(table, separator = ';') {
	// select all rows
	var rows = table.querySelectorAll('thead tr, tbody tr');
	// construct csv
	var ignoreFirstColumn = false;
	var firstRow = true;
	var csv = [];
	for(var i = 0; i < rows.length; i++) {
		if(rows[i].style.display == 'none') continue; // do not export invisible (filtered) lines
		var firstColumn = true;
		var row = [], cols = rows[i].querySelectorAll('td, th');
		for(var j = 0; j < cols.length; j++) {
			// clean innertext to remove multiple spaces
			var data = cols[j].innerText.replace(/(\s\s)/gm, ' ')
			// escape double-quote with double-double-quote (see https://stackoverflow.com/questions/17808511/properly-escape-a-double-quote-in-csv)
			data = data.replace(/"/g, '""');
			// check if first column should be ignored (checkbox column)
			if(firstRow && data == '') {
				ignoreFirstColumn = true;
			}
			if(!(firstColumn && ignoreFirstColumn)) {
				// push escaped string
				row.push('"' + data.trim() + '"');
			}
			firstColumn = false;
		}
		csv.push(row.join(separator));
		firstRow = false;
	}
	var csv_string = csv.join('\n');
	// download it
	var filename = 'export_' + table.id + '_' + new Date().toLocaleDateString() + '.csv';
	var link = document.createElement('a');
	link.style.display = 'none';
	link.setAttribute('target', '_blank');
	link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv_string));
	link.setAttribute('download', filename);
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
}

function togglePackageDragAndDrop(table, state) {
	if(table == null) return;
	// remove invalid elements (e.g. spaces, tabs in HTML code) as they distort the element index
	var tbody = table.querySelectorAll('tbody')[0];
	for(var i = 0; i < tbody.childNodes.length; i++) {
		if(tbody.childNodes[i].tagName === undefined) {
			tbody.childNodes[i].remove();
		}
	}
	// set dragable state to drag elements
	var rows = tbody.querySelectorAll('tr.draggable');
	for(var i = 0; i < rows.length; i++) {
		rows[i].draggable = state;
		if(state) {
			rows[i].classList.remove('nodrag');
		} else {
			rows[i].classList.add('nodrag');
		}
	}
}
var draggedPackageTableElement;
var draggedPackageTableElementBeginIndex = 0;
function dragStartPackageTable(e) {
	if(!packageDragAndDropEnabled) return false;
	draggedPackageTableElement = e.target;
	draggedPackageTableElementBeginIndex = getChildIndex(e.target);
	return true;
}
function dragOverPackageTable(e) {
	if(!packageDragAndDropEnabled) return false;
	let children = Array.from(e.target.parentNode.parentNode.children);
	if(children.indexOf(e.target.parentNode) > children.indexOf(draggedPackageTableElement)) {
		e.target.parentNode.after(draggedPackageTableElement);
	} else {
		e.target.parentNode.before(draggedPackageTableElement);
	}
	return true;
}
function dragEndPackageTable(e, gid) {
	if(!packageDragAndDropEnabled) return false;
	reorderPackageInGroup(gid, draggedPackageTableElementBeginIndex, getChildIndex(draggedPackageTableElement));
	return true;
}
function handlePackageReorderByKeyboard(e, gid, sequence) {
	if(e.keyCode == 13) {
		if(e.shiftKey) {
			// move relative to current position
			var newValue = prompt(LANG['enter_new_sequence_number']);
			if(newValue == null || newValue == '') return;
			var newValueInt = parseInt(newValue);
			if(isNaN(newValueInt)) return;
			reorderPackageInGroup(gid, sequence, sequence+newValueInt);
		} else {
			// move to absolute position
			var newValue = prompt(LANG['enter_new_sequence_number']);
			if(newValue == null || newValue == '') return;
			var newValueInt = parseInt(newValue);
			if(isNaN(newValueInt)) return;
			reorderPackageInGroup(gid, sequence, newValueInt);
		}
	}
}
