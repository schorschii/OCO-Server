document.addEventListener('DOMContentLoaded', function(e){
	obj('search-container').addEventListener('focusout', function(event){
		if(!this.contains(event.relatedTarget)) {
			closeSearchResults();
		}
	});
	obj('txtGlobalSearch').addEventListener('focus', openSearchResults);
	obj('txtGlobalSearch').addEventListener('keyup', function(event){
		if(event.keyCode == 27) {
			closeSearchResults();
		} else if(event.keyCode == 40) {
			focusNextSearchResult();
		} else if(event.keyCode == 13) {
			doSearch(this.value);
		}
	});
	obj('txtGlobalSearch').addEventListener('input', function(event){
		doSearch(this.value);
	});
});

var previousSearchOperation = null;
function doSearch(query) {
	if(previousSearchOperation !== null && previousSearchOperation.status === 0) {
		previousSearchOperation.userCancelled = true;
		previousSearchOperation.abort();
	}
	previousSearchOperation = ajaxRequest('views/search.php?query='+encodeURIComponent(query), 'search-results');
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
