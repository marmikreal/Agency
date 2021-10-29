/*
<LICENSE>

This file is part of AGENCY.

AGENCY is Copyright (c) 2003-2009 by Ken Tanzer and Downtown Emergency
Service Center (DESC).

All rights reserved.

For more information about AGENCY, see http://agency.sourceforge.net/
For more information about DESC, see http://www.desc.org/.

AGENCY is free software: you can redistribute it and/or modify
it under the terms of version 3 of the GNU General Public License
as published by the Free Software Foundation.

AGENCY is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CHASERS.  If not, see <http://www.gnu.org/licenses/>.

For additional information, see the README.copyright file that
should be included in this distribution.

</LICENSE>
*/

function HelpWin( title, text )
{
	helpwindow=open("","","width=300,height=250,resizable=yes,scrollbars=yes");
	helpwindow.document.write( "<h1>" + title + "</h1>" + text );
	helpwindow.document.close();
}

function getOffset(el,t) {
	var o=0;
	while(el) {
		 o += el[t];
		 el = el.offsetParent
	}
	return o;
}

function showHideElement(id) { 
	var vis = document.getElementById(id).style.display;
	
	if (vis=="none") {
		showElement(id);
	} else {
		hideElement(id);
	}
}

function showElement(id) {
	var element;
	if (element = document.getElementById(id)) {
		element.style.display = "";
	} else {
		return false;
	}

	showElement(id+"_hide");
	hideElement(id+"_show");
}

function hideElement(id) {
	var element;

	if (element = document.getElementById(id)) {
		element.style.display = "none";
	} else {
		return false;
	}

	hideElement(id+"_hide");
	showElement(id+"_show");

}

function multiShow() {
	for (var i=0; i<arguments.length; i++) {
		showElement(arguments[i]);
	}
}

function multiHide() {
	for (var i=0; i<arguments.length; i++) {
		hideElement(arguments[i]);
	}
}

function toggleDisplayId(tid,displayOpt) {
	var el = document.getElementById(tid);
	if (el.style.display !== "none") {
		el.style.display="none";
	} else {
		el.style.display=displayOpt;
	}
}

var onPrintFriendly = false;
var originalStyleSheet = new Array();

/*
 * This gets set in includes.php, due to $off funkiness
 * var printStyleSheetPath = 'schema/print.css';
 */

function printPreview() {
	if (!document.getElementsByTagName) { return; }
	if (!onPrintFriendly) {
		onPrintFriendly = true;
		var styleSheets = document.getElementsByTagName('link');
		for (var i=0; i<styleSheets.length; i++) {
			if (styleSheets[i].getAttribute('class')=='styleSheetScreen') {
				originalStyleSheet[i] = styleSheets[i].getAttribute('href');
				styleSheets[i].setAttribute('href',printStyleSheetPath);
			}
		}
	} else {
		onPrintFriendly = false;
		var styleSheets = document.getElementsByTagName('link');
		for (var i=0; i<styleSheets.length; i++) {
			if (styleSheets[i].getAttribute('class')=='styleSheetScreen') {
				styleSheets[i].setAttribute('href',originalStyleSheet[i]);
			}
		}
	}
	return false;
}

/* quick search */
function switchQuickSearch(which) {
	whichQuickSearch=which;
      setQuickSearch();
	document.getElementById('QuickSearchText').focus(); 
}

function setQuickSearch() {
	for (var i=0; i < QuickSearches.length; i++) {
		var el=document.getElementById('QuickSearch'+QuickSearches[i]);
		if (QuickSearches[i]==whichQuickSearch) {
			el.className='QuickSearchTab';
		} else {
			el.className='QuickSearchTabInactive';
		}
	}
	document.getElementById('QuickSearchBox').className=whichQuickSearch.toLowerCase();
      document.getElementById('QuickSearchType').setAttribute('value',whichQuickSearch.toLowerCase());
}

function getDaysInMonth(month,year)
{
	var daysInMonths = new Array (31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	var days = daysInMonths[month];
	//calculate leap year
	if ( (month==1) && (year%4==0) && (!(year%100==0) || (year%400==0)) ) {
		days = 29;
	}
	return days;
}