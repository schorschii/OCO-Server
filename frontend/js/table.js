var TableSortUltra = function(tab, startsort) {
	var me = this;

	// get rows
	var thead = tab.tHead;
	if(thead) {
		var tr_in_thead = thead.querySelectorAll("tr.sortable");
		if(!tr_in_thead.length) tr_in_thead = thead.rows;
	}
	if(tr_in_thead) var tabletitel = tr_in_thead[0].cells;
	if( !(tabletitel && tabletitel.length > 0) ) {
		console.warn("Table has no <TH>"); return null;
	}
	var tbdy = tab.tBodies;
	if( !tbdy || tbdy.length == 0 ) {
		console.warn("Table has no <TBODY>"); return null;
	}
	tbdy = tbdy[0];
	var tr = tbdy.rows;
	if( tr.length == 0 ) {
		console.warn("Table has no rows in <TBODY>"); return null;
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
	var savesort = tab.classList.contains("savesort") && tab.id && tab.id.length>0 && localStorage;
	var minsort = -1;

	var initTableHead = function(col) {
		if(tabletitel[col].classList.contains("sortable-")) {
			firstsort[col] = "desc";
		}
		else if(tabletitel[col].classList.contains("sortable")) {
			firstsort[col] = "asc";
		} else {
			return false;
		}
		var sortbutton = document.createElement("button");
		sortbutton.innerHTML = tabletitel[col].innerHTML;
		sortbutton.title = "Sortiere nach " + tabletitel[col].innerHTML;
		sortbutton.className = "sortbutton";
		sortbutton.type = "button";
		sortbuttons[col] = sortbutton;
		var sortsymbol = sortbutton;
		var symbolspan = sortbutton.querySelectorAll("span");
		if(symbolspan && symbolspan.length) {
			for(var i=0;i<symbolspan.length;i++) {
				if(!symbolspan[i].hasChildNodes()) {
					sortsymbol = symbolspan[i];
					break;
				}
			}
		}
		sortsymbol.classList.add("sortsymbol");
		if(tabletitel[col].classList.contains("vorsortiert-")) {
			sortsymbol.classList.add("sorteddesc");
			sorted = col;
		} else if(tabletitel[col].classList.contains("vorsortiert")) {
			sortsymbol.classList.add("sortedasc");
			sorted = col;
		} else {
			sortsymbol.classList.add("unsorted");
		}
		sortsymbols[col] = sortsymbol;
		if(tabletitel[col].classList.contains("sortiere-")) {
			startsort_d = col;
		} else if(tabletitel[col].classList.contains("sortiere")) {
			startsort_u = col;
		}
		sortbutton.addEventListener("click",function() { me.tsort(col); },false);
		tabletitel[col].innerHTML = "";
		tabletitel[col].appendChild(sortbutton);
		return true;
	} // initTableHead

	var getCellData = function(ele, col) {
		var val = ele.textContent;
		if(ele.getAttribute("sort_key")) val = ele.getAttribute("sort_key");
		if(isNaN(val)) sorttype[col] = "s";
		return val;
	}

	var compareString = function(a,b) {
		var ret = a[sorted].localeCompare(b[sorted]);
		if(!ret && sorted != minsort) {
			if(sorttype[minsort] == "s") {
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
			if(sorttype[minsort] == "s") {
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
			sortsymbols[col].classList.toggle("sortedasc");
			sortsymbols[col].classList.toggle("sorteddesc");
		} else {
			if(sorted > -1) {
				sortsymbols[sorted].classList.remove("sortedasc");
				sortsymbols[sorted].classList.remove("sorteddesc");
				sortsymbols[sorted].classList.add("unsorted");
				sortbuttons[sorted].removeAttribute("aria-current");
			}
			sorted = col;
			sortsymbols[col].classList.remove("unsorted");
			sortbuttons[col].setAttribute("aria-current","true");
			if(sorttype[col] == "n") {
				arr.sort(compareNumber);
			} else {
				arr.sort(compareString);
			}
			if(firstsort[col] == "desc") {
				arr.reverse();
				sortsymbols[col].classList.add("sorteddesc");
			}
			else {
				sortsymbols[col].classList.add("sortedasc");
			}
		}

		// write sorted data to table
		for(var r = 0; r < nrows; r++) {
			tbdy.appendChild(arr[r][ncols]);
		}

		// save search setting
		if(savesort) {
			var store = { sorted: sorted, desc: sortsymbols[sorted].classList.contains("sorteddesc") };
			localStorage.setItem(tab.id, JSON.stringify(store));
		}
	}

	// check if there is a 'sortable' class in thead
	var sortflag = false;
	for(var c = 0; c < tabletitel.length; c++) {
		sortflag |= tabletitel[c].classList.contains("sortable") || tabletitel[c].classList.contains("sortable-");
	}
	if(!sortflag) {
		for(var c = 0; c<tabletitel.length; c++) tabletitel[c].classList.add("sortable");
	}

	// prepare headers
	for(var c = tabletitel.length-1; c >= 0; c--) if(initTableHead(c)) minsort = c;

	// default sort type : number
	for(var c = 0; c < ncols; c++) sorttype[c] = "n";

	// copy cell contents to array
	for(var r = 0; r < nrows; r++) {
		arr[r] = [];
		for(var c = 0; c < ncols; c++) {
			arr[r][c] = getCellData(tr[r].cells[c], c);
		}
		arr[r][ncols] = tr[r];
	}

	// execute sort
	if(startsort && typeof(startsort.sorted)!="undefined" && typeof(startsort.desc)!="undefined") {
		if(startsort.desc) {
			startsort_d = startsort.sorted; startsort_u = -1;
		} else {
			startsort_u = startsort.sorted; startsort_d = -1;
		}
	}
	if(startsort_u >= 0 && startsort_u < ncols) {
		me.tsort(startsort_u);
	}
	if(startsort_d >= 0 && startsort_d < ncols) {
		me.tsort(startsort_d);
	}

}

function initTableSort() {
	// find all tables which should be sortable
	var tables = document.querySelectorAll("table.sortable");
	for(var i = 0, store; i < tables.length; i++) {
		store = null;
		if(localStorage && tables[i].id && tables[i].classList.contains("savesort") && tables[i].id.length) {
			store = localStorage.getItem(tables[i].id);
			if(store) store = JSON.parse(store);
		}
		new TableSortUltra(tables[i], store);
	}
}

function initTableSearch() {
	// find all tables which should be searchable
	var tables = document.querySelectorAll("table.searchable");
	for(var n = 0; n < tables.length; n++) {
		let table = tables[n];
		let thead = table.getElementsByTagName("thead")[0];
		let trSearch = document.createElement("tr");
		let tr = thead.getElementsByTagName("tr")[0];
		let ths = tr.getElementsByTagName("th");
		for(var i = 0; i < ths.length; i++) {
			let currentColumnIndex = i;
			var thSearch = document.createElement("th");
			if(ths[i].classList.contains("searchable")) {
				let txtSearch = document.createElement("input");
				txtSearch.setAttribute("placeholder", L__SEARCH_PLACEHOLDER);
				txtSearch.classList.add("tableSearch");
				txtSearch.addEventListener("input", function(){ tableSearch(table, currentColumnIndex, txtSearch) });
				txtSearch.addEventListener("paste", function(){ tableSearch(table, currentColumnIndex, txtSearch) });
				thSearch.appendChild(txtSearch);
			}
			trSearch.appendChild(thSearch);
		}
		thead.appendChild(trSearch);
	}
}

function tableSearch(table, column, input) {
	var count = 0, filter, tr, td, txtValue;

	// ignore case
	filter = input.value.toUpperCase();

	// get table body
	tbody = table.getElementsByTagName("tbody");
	if(!tbody || tbody.length == 0) return;

	// get all rows in table body
	tr = tbody[0].getElementsByTagName("tr");
	for(var i = 0; i < tr.length; i++) {
		td = tr[i].getElementsByTagName("td")[column];
		if(td) {
			txtValue = td.textContent || td.innerText;
			if(!tr[i].classList.contains('nosearch')) {
			if(txtValue.toUpperCase().includes(filter)) {
				tr[i].style.display = "";
				count = count +1
				} else {
					tr[i].style.display = "none";
				}
			} else {
				count = count + 1
			}
		}
	}

	// refresh counter
	tfoot = table.getElementsByTagName("tfoot");
	if(tfoot && tfoot.length == 1) {
		spnCount = tfoot[0].querySelectorAll("span.counter");
		if(spnCount || spnCount.length > 0) {
			spnCount[0].innerText = String(count);
		}
	}
}

function toggleCheckboxesInTable(table, checked) {
	let trs = table.getElementsByTagName("tr");
	for(var i = 0; i < trs.length; i++) {
		if(trs[i].style.display == "none") continue;
		let inputs = trs[i].getElementsByTagName("input");
		for(var n = 0; n < inputs.length; n++) {
			if(inputs[n].type == "checkbox") {
				inputs[n].checked = checked;
			}
		}
	}
	refreshCheckedCounter(table);
}

function refreshCheckedCounter(table) {
	// count
	let counter = 0;
	let tbody = table.getElementsByTagName("tbody");
	if(tbody && tbody.length > 0) {
		let inputs = tbody[0].getElementsByTagName("input");
		for(var i = 0; i < inputs.length; i++) {
			if(inputs[i].type == "checkbox" && inputs[i].checked) {
				counter ++;
			}
		}
	}

	// refresh counter
	tfoot = table.getElementsByTagName("tfoot");
	if(tfoot && tfoot.length == 1) {
		spnCount = tfoot[0].querySelectorAll("span.counter-checked");
		if(spnCount && spnCount.length > 0) {
			spnCount[0].innerText = String(counter);
		}
	}
}

function downloadTableCsv(table_id, separator = ',') {
	// Select rows from table_id
	var rows = document.querySelectorAll('table#' + table_id + ' tr');
	// Construct csv
	var csv = [];
	for(var i = 0; i < rows.length; i++) {
		var row = [], cols = rows[i].querySelectorAll('td, th');
		for(var j = 0; j < cols.length; j++) {
			// Clean innertext to remove multiple spaces and jumpline (break csv)
			var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ')
			// Escape double-quote with double-double-quote (see https://stackoverflow.com/questions/17808511/properly-escape-a-double-quote-in-csv)
			data = data.replace(/"/g, '""');
			// Push escaped string
			row.push('"' + data + '"');
		}
		csv.push(row.join(separator));
	}
	var csv_string = csv.join('\n');
	// Download it
	var filename = 'export_' + table_id + '_' + new Date().toLocaleDateString() + '.csv';
	var link = document.createElement('a');
	link.style.display = 'none';
	link.setAttribute('target', '_blank');
	link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv_string));
	link.setAttribute('download', filename);
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
}
