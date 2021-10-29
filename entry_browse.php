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

$quiet="Y";
include "includes.php";

// Basic Shelter Entry data display page
// Variables passed are:
// 
// $action ( 
		// forward, back, first, last -- page up/down, beginning, end
		// jump: go to date ($_REQUEST["jump_date"]
// $pos ( position in table ) (for browse)

$refresh_rate = 0;
$action = orr($_REQUEST['action'],'browse');
$pos = $_REQUEST['pos'];
//$action="jump";

//set locations (sets AG_ENTRY_LOCATIONS)
set_entry_locations();


//---- find and merge user options ----//

foreach (array_keys($AG_ENTRY_LOCATIONS) as $tmp_key) {
	$tmp_entry_defaults[$tmp_key] = true;
}

$USER_PREFS = $AG_USER_OPTION->get_option('ENTRY_DISPLAY_OPTIONS'); //get entry prefs

$USER_PREFS['entry_location'] = orr($_REQUEST['entry_location'],$USER_PREFS['entry_location'],$tmp_entry_defaults);

switch( $action ) {
 case 'forward' : // forward one browse screen
	 $entry_count=count_rows($entry_table);
	 $pos=min($pos+$entry_per_screen,$entry_count-$entry_per_screen);
	 break;
 case 'back' : // back one browse screen
	 $pos=max($pos-$entry_per_screen,0);
	 break;
 case 'first' : 
	 $pos=0;
	 break;
 case 'last' : 
	 $entry_count=count_rows($entry_table);
	 $pos=$entry_count-$entry_per_screen;  
	 break;
 case 'jump' : // Jump to location
	 $date=dateof($_REQUEST['jump_date']);
	 $pos=count_rows($entry_table,array('>=:entered_at'=>$date))-$entry_per_screen;
 case 'browse' :  
 default:
	 break;
}

$AG_USER_OPTION->set_option('ENTRY_DISPLAY_OPTIONS',$USER_PREFS); //set entry prefs - if changed


$pos=is_numeric($pos) ? $pos : 0;
$a = get_entries( $pos, $entry_per_screen, $order );
if (sql_num_rows($a) > 0 ) {
	$entries = show_entry( $a, '','Yes' );
	$first_datetime = get_browse_datetime($a);
	$last_datetime = get_browse_datetime($a, 'last');
	foreach ($USER_PREFS['entry_location'] as $t_key => $t_val) {
		$viewing_entries[] = $AG_ENTRY_LOCATIONS[$t_key];
	}
	$page_title = 'Viewing Entries on ' . dateof($first_datetime);
	$sub_title = implode(oline(),$viewing_entries);
} else {
	$out.=('Empty set returned.');
	$page_title = 'No Records Found';
}

html_header($page_title,$refresh_rate);
$commands=array(cell(oline(show_entry_browse_navbar() ,2),'bgcolor="'.$colors['nav'].'"'),
		    cell(show_pick_entry(),'class="pick"'));
agency_top_header($commands);	
$out .= bigger(bold($page_title),2) . ', ' . bigger(timeof($last_datetime) . ' through ' . timeof($first_datetime));
$out .= html_heading_4($sub_title);
$out .= oline($entries);
out($out);
page_close();

?>

