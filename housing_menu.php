<?php

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

  //fixme: this page can be removed
header('Location: menu.php?type=housing');
exit;

$quiet='Y';
include "includes.php";


agency_top_header();
headtitle('Housing Program Miscellany Page');
outline("");
if (! has_perm("housing","R"))
{
	outline(alert_mark('Sorry, you do not have permission to access this page'));
	exit;
}

out(housing_view_current_form());
outline( bigger(hlink("unit_history.php","View Unit Histories")),2);
outline( bigger(hlink("first_month_rent.php","Calculate First Month's rent for a resident")),2);
//outline( bigger(hlink("reports","Go to the reports page")),2);

outline(link_engine(array('object'=>'bar','action'=>'add'),'Add a non-client bar'));

$control = array('object'=>'staff_schedule','action'=>'list','list'=>array('display_add_link'=>true));

if (has_perm('housing_admin,housing_scattered','RW')) {
	out(subhead('Housing Unit Maintenance'));
	$tmp = array('housing_unit','housing_unit_subsidy');
	$out_all = $out = '';
	$dummy=true;

	// output links in case of empty tables
	$s_button = Java_Engine::toggle_tag_display('div',bold('show empty records'),'childListNullData','block',$dummy);
	$h_button = Java_Engine::toggle_tag_display('div',bold('hide empty records'),'childListNullData','block',$dummy);
	$out .= oline(smaller(Java_Engine::hide_show_buttons($s_button,$h_button,$dummy,'inline',AG_LIST_EMPTY_HIDE)));

	foreach ($tmp as $object) {
		$sing = $engine[$object]['singular'];
		$control = $control_all =array('object'=>$object, 'action'=>'list');
		$control['list']['filter'] = array('NULL:'.$object.'_date_end'=>true);
		$title_all = 'Browse ALL '.$sing.' records';
		$title = 'Browse CURRENT '.$sing.' records';
 		$out .= engine_java_wrapper($control,$object.'cur',$dummy,$title,'Cur');
 		$out_all .= engine_java_wrapper($control_all,$object.'all',$dummy,$title_all,'All');
	}
	out(oline($out)
	    . oline($out_all)
	    );
}
page_close();

?>
