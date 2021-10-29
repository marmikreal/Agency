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

//FIXME:  This code could be MUCH nicer and cleaner

/* Client Selector */
function qs_object( searchText, obj ) {
		var curPage = window.location.pathname;
		//var args = { QuickSearch: searchText, MyClients: myClient, select_to_url: curPage };
		var args = { QuickSearch: searchText, QuickSearchObject: obj, MyClients: false, select_to_url: curPage };
		return args;
};	

/* Object Picker */
objectSelectNum = 1;
function addSelectedObject( id, label, obj, canRemove, refType ) {
	// Check if exists
	var dupe=false;
	$("[name=selectedObjectNumber[]]").each( function() {
		var num = $(this).val();
		var t_obj = $('#selectedObjectObject'+num).val();
		var t_id = $('#selectedObjectId'+num).val();
		var t_label = $('#selectedObjectLabel'+num).val();
		if ( (t_obj==obj) && (t_id==id) ) {
			dupe = true;
		}
	});
	if (dupe) {
	       return false;
	};

	var lab = '<a href=display.php?control[action]=view&control[object]=' + obj + '&control[id]='
		+ id +' class=' + obj + 'Link>'+label + '</a>';
	var pre = '<input type=hidden id=selectedObject';
	var val_num = pre + 'Number'+objectSelectNum + ' value="' + objectSelectNum + 
		'" name=selectedObjectNumber[]>';
	var val_label = pre + 'Label'+objectSelectNum + ' value="' + label + 
		'" name=selectedObjectLabel[]>';
	var val_id = pre + 'Id'+objectSelectNum + ' value=' + id + 
		" name=selectedObjectId[]>";
	var val_obj = pre + 'Object'+objectSelectNum + ' value=' + obj + 
		" name=selectedObjectObject[]>";
	var val_type = pre + 'RefType'+objectSelectNum + ' value=' + refType + 
		" name=selectedObjectRefType[]>";
	var val_remove = canRemove ? ' <a href=# id=selectedObjectRemove' +objectSelectNum + ' class=selectedObjectRemove>(remove)</a>' : '';
 
	objectSelectNum++;
	var text = "<span>"+val_num+val_id+val_obj+val_label+val_type+ "<li>" + lab + val_remove + "</li>" + "</span>";
	if (obj=='info_additional') {
		$("#infoAdditionalContainer").show().append(text);
	} else {
		$("#objectReferenceContainerReference" + (refType=='from' ? 'From' : 'To')).show().append(text);
		$("#objectReferenceContainer").show();
	}
	return;
}

$(function() {
	$(".objectPickerSubmit").live("click", function(event) {
		event.preventDefault();
		var method =$(event.target).closest('div').find("[name=objectPickerMethod]").val();
		if (!method) {
			method = $(event.target.row).find("[name=objectPickerMethod]").val();
		}
		switch (method) {
			case 'Pick':
				var obj_name = $(event.target).closest('div').find("[name=objectPickerObject]").val();
				var obj_id = $(event.target).closest('div').find("[name=objectPickerPickList]").val();
				var obj_text = $(event.target).closest('div').find("[name=objectPickerPickList] :selected").text();
				addSelectedObject(obj_id,obj_text,obj_name,true,'to');
				break;
			case 'searchResult':
				var obj_name = $(event.target).closest('tr').find("[name=objectPickerObject]").val();
				var obj_id = $(event.target).closest('tr').find("[name=objectPickerId]").val();
				var obj_text = $(event.target).closest('tr').find("[name=objectPickerLabel]").val();
				addSelectedObject(obj_id,obj_text,obj_name,true,'to');
				break;
			case 'Search':
				var search_text = $(event.target).closest('div').find("[name=objectPickerSearchText]").val();
				var obj = $(event.target).closest('div').find("[name=objectPickerObject]").val();
				var args = qs_object(search_text,obj);
				$.ajax({
					method: "get",
					url: "ajax_selector.php",
					data: args,
					beforeSend: function(){$("#page_loading").show("fast");},
					complete: function(){ $("#page_loading").hide("fast");}, 
					success: function(html){
						$("#aj_client_selector_my_clients").val(false);
						$("#ajClientSearchResults").html(html); 
						var button = '<td><button type="button" class="objectPickerSubmit">SELECT</button></td>';
						$("#ajClientSearchResults tr.generalData2,tr.generalData1").each( function(i) {
							$(this).children("td:eq(1)").html(button);
						});
						$("#ajClientSearchResults").show(); 
						var tab=$("#ajClientSearchResults table");
						}
				});
		}
	});
});

/*
 * Object Reference Container & Additional Information
 */

$(function() {
	var section_to='<div id=objectReferenceContainerReferenceTo><h2>Refers to</h2></div>';
	var section_from='<div id=objectReferenceContainerReferenceFrom><h2>Referenced By</h2></div>';
	var sections = section_to + section_from;
	$("#objectReferenceContainer").append(sections).draggable().hide();
	$("#objectReferenceContainer div").hide();
	$("#infoAdditionalContainer").append("<h2>Additional Information</h2>").draggable().hide();
	var closeButton = '<a id="objectSelectorHideLink" class="fancyLink">close</a>';
	$("#objectSelectorForm").tabs().draggable().hide().append(closeButton);
	addPreSelectedObjects();
	$("#objectSelectorShowLink").click( function(event) {
		event.preventDefault();
		$("#objectSelectorForm").show();
		$(this).hide();
	} );
	$("#objectSelectorHideLink").click( function(event) {
		event.preventDefault();
		$("#objectSelectorForm").hide();
		$("#ajClientSearchResults").hide();
		$("#objectSelectorShowLink").show();
	});
	$('.selectedObjectRemove').live( 'click', function(event) {
		//fixme: test for canRemove
		event.preventDefault();
		var visible_count=$(event.target).closest("div").find("span");
		if (visible_count.length==1) {
			$(event.target).closest("div").hide();
		}
		$(event.target).parents('span:eq(0)').remove(); //Fixme--better selector?
		if ($("#objectReferenceContainer span:visible").length==0) {
			$("#objectReferenceContainer").hide();
		}
	});
});

/* pre-selected objects */
function addPreSelectedObjects() {
	var n = 'preSelectedObject';
	var count=$('[name=' + n + 'Number[]]').length;
	$('[name=' +n + 'Number[]]').each( function() {
		var num = $(this).val();
		var obj = $('#' +n + 'Object'+num).val();
		var id = $('#' +n + 'Id'+num).val();	
		var label = $('#' +n + 'Label'+num).val();	
		var canRemove = $('#' +n + 'CanRemove'+num).val();	
		var refType = $('#' +n + 'RefType'+num).val();	
		addSelectedObject(id,label,obj,canRemove,refType);
	});
};

