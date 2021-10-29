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

/* This file contains all the custom code for jquery and AGENCY.
   For now.  It may make more sense to break it up into separate
   files later.
 */

/* Datepicker for date fields */
$(function() {
	var today='<a href=# class="calTodayLink fancyLink">today</a>';
	$(".field_date").after(today);
	$(".calTodayLink").click( function(event) {
		event.preventDefault();
		var tmp=new Date();
		var tmp2=1+tmp.getMonth()+'/'+tmp.getDate()+'/'+tmp.getFullYear();
		$(event.target).prev().prev().val(tmp2);
	});

	$('.field_date').datepick( {
			showOn: 'button',
			buttonImageOnly: true,
			buttonImage: 'images/calendar.png',
			numberOfMonths: 2,
			firstDay: 1,
			yearRange: '-100:+10'
	});
});

/* TimePicker for time fields */
$(function() {
	var clock_button='<img src="images/clock.gif" class="clockButton" />';
	var now=' <a href=# class="calNowLink fancyLink">now</a>';
	$(".field_time").after(now).after(clock_button);
	$(".clockButton").clockpick( {
		starthour: 0,
		endhour: 23,
		minutedivisions: 12
	},
	function( ntime ) {
		$(this).prev().val(ntime);
	});

	$(".calNowLink").click( function(event) {
		event.preventDefault();
		var tmp=new Date();
		var hours=tmp.getHours();
		if ( hours >= 12 ) {
			var ampm = "pm";
			hours = hours - 12;
		} else {
			var ampm = "am";
		}
		var min = tmp.getMinutes();
		if (min < 10) {
			min = '0' + min;
		}
		var tmp2= hours + ':' + min + ' ' + ampm;
		$(event.target).prev().prev().val(tmp2);
	});
});

/* Highlight errors and selected checkboxes on Engine forms */
$(function() {
	$(".engineFormError").closest("tr").addClass("engineFormError"); 
	$(".engineFormError").closest("tr").click( function() { 
		$(this).find(".engineFormError").removeClass("engineFormError");
		$(this).removeClass("engineFormError");
	});
	$(".engineForm input:checkbox:checked").closest('tr').addClass("engineFormSelected");

	$(".engineForm input:checkbox").change( function() {
		$(this).closest('tr').toggleClass('engineFormSelected');
	});
});

/* Hide & Toggle minor engine messages */
$(function() {
	$(".engineMessageResultDetail").hide().before('<a href="#" class="toggleLink">details...</a>');
	$(".engineMessageResultDetail").prev().click( function(event) {
		event.preventDefault();
		$(this).next().toggle();
	});
});

/* Hide & Toggle minor messages */
$(function() {
	//$(".hiddenDetail").hide().before('<a href="#" class="toggleLink">details...</a>');
	$(".hiddenDetail").each( function() {
		var lab = $(this).children("[name=toggleLabel]");
		if (lab.val()==undefined) {
			var text = "details...";
		} else {
			var text = lab.val();
		}
		$(this).hide().before('<a href="#" class="toggleLink">'+text+'</a>');
	});

	$(".toggleLink").click( function(event) {
		event.preventDefault();
		$(this).next().toggle();
	});
});

/*
 * Log text, show as row in table
   Should be genericized to hiddenEngineText
   */

$(function() {
	//$(".hiddenLogText").hide().before('<a href="#" class="toggleLink">read</a>');
	$(".hiddenLogText").each( function(i) { 
		var row=$(this).closest('tr');
		var cols=$(row).children().filter("td").length;
		var text=$(this).next();
		$(this).append("<br /><br />");
		var pre='<tr><td colspan=' + cols + '>';
		var post='</td></tr>';
		var newrow=pre + post;
		var nr = $(row).after(newrow);
		$(nr).next().children().append($(this)).hide();
	});
	var link = $(".hiddenLogText").first().closest("table").before('&nbsp;<a href="#" class="toggleLogText">Show/hide text in place</a>');
	$(".toggleLogText").click( function(event) {
		event.preventDefault();
		$(".hiddenLogText").parent().toggle();
	});
});

$(function() {
	// move staff alerts to command bar
	//$("#engineStaffAlertForm").wrap("<td></td>").after($("#agencyTopLoginBox"));
	//$("#staffAlertContainer").hide();
//	$("#staffAlertContainer").wrap("<td></td>").insertAfter("#agencyTopLoginBox");
//	$("#agencyTopLoginBox").parent().after("test").wrap("<td></td>"));
});

