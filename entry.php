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

function show_entry( $entries,$count=0,$full="",$cols=3 )
{
	// needs client_id, GateDate, GateTime,source 
	$output = "";
	$count=is_numeric($count) ? $count : sql_num_rows($entries);
	if (sql_num_rows($entries) != 0) {
		$output .= (tablestart("","border=5"));
		$row = cell(bold("Date"));
		if ($full) {
			$row .= cell(bold("Client")) . cell(bold("Bar Status"))
				. cell(bold("Photo")); 
			$cols=1;	// for full display, only one column
		}

// 		$row .=  cell(bold("Source"));
		$row .= cell(bold('Location'));
		$row2=$row;

		for ($x=0; $x<$cols-1; $x++) {
			$row2 .= cell(' ') . $row;
		}

		$output .= (row($row2)); 

		$row_count=$count / $cols;
		if ($row_count != intval($row_count)) {
			$row_count++;
		}
		$remainder=$row_count*$cols-sql_num_rows($entries);
		// $row_count and $y are for handling 2 columns
		$row_count=intval($row_count);
		$y=0;

		for ($x=0; $x<$count;$x++) {
			$entry=sql_fetch_assoc( $entries );
			$client=sql_fetch_assoc(client_get($entry["client_id"]));
			$row=cell( dateof($entry["entered_at"]) . "<br>"
				     . smaller(timeof( $entry["entered_at"],"ampm", "secs" )) );
			if ($full) {
				$row .= cell(client_link($entry["client_id"]) .
						 "<br>" . smaller("id # " . $entry["client_id"] .
									", ss# " . $client["ssn"])) .
					cell(bar_status_f($entry,'',$is_provisional)) .
					cell(client_photo($entry["client_id"],.5)); 
			}
// 			$row .= cell( $entry["source"] ); 
			$def = get_def('entry');
			$row .= cell(value_generic($entry['scanner_location_code'],$def,'scanner_location_code','list'));
			$rows[$y] .= $rows[$y] ? cell(" ") . $row : $row;  // add separator for cols 2+
			$y++;
			if ($y == $row_count) {
				$y=0;
			}
		}
		// add blank cells for remainder here
		for ($z=1;$z<=$remainder;$z++) {
			$rows[$row_count-$z] .= cell(" ") . cell("") . cell("");
		}
		// And do the output
		for ($x=0; $x<$row_count;$x++) {
			$output .= (row($rows[$x]));
		}

		$output .= (tableend());
	} else {
		$output .= outline("No Entries to Display");		
	}
	return $output;
}

// returns entries (section at a time based on $entry_per_screen
function get_entries($start, $count, $order="")
{
 	global $entry_select_sql,$USER_PREFS;

	$order = orr($order,"entered_at DESC");

	$filter = array();
	$filter['IN:scanner_location_code'] = array_keys($USER_PREFS['entry_location']);

	$entries = agency_query($entry_select_sql, $filter, $order .
				    sql_limit($count, $start));
	return $entries;
}

// get date of the first record displayed
function get_browse_datetime($entries, $position="")
{
    // postgres can grab any specified row, so we just use sql_fetch_assoc
    // no need to worry about data_seek
    // won't need the sql_data_seek calls here when we're not using mysql
    if ($position == "last")
    {
        $count = sql_num_rows($entries);
        // offset can not be negative or zero
        if ($count > 0)
        {
            $count = $count - 1;
        }
            
        sql_data_seek($entries, $count); //for mysql only
    }
    else
    {
        $count=0; // for postgres only
    }

    $record = sql_fetch_assoc($entries, $count);

    sql_data_seek($entries, 0); //for mysql only
    return ($record["entered_at"]);
}

// what is valid total number of entries...it's not just the number of records
// in the table...only needed when going to the 'Oldest' records
function get_real_count($filter="")
{
    global $entry_table, $client_table;
	$filter=orr($filter,array());
    $res = agency_query("SELECT COUNT(*) FROM $entry_table",$filter);
    $count = sql_fetch_row($res);
    return $count[0];
}

function last_entry_f($clientid, $java=false) {

 	$def = get_def('entry');
	$res = get_generic(client_filter($clientid),'entered_at DESC','1',$def);
	if (sql_num_rows($res) < 1) {
		return smaller('(no Entries)');
	}
	$entry = sql_fetch_assoc($res);
	$entered = value_generic($entry['entered_at'],$def,'entered_at','view');
	$link = $java
		? seclink('entry',$entered,
			    'onclick="javascript:showHideElement(\'entryChildList\')"')
		: $entered;
	return 'Last Entry ('.value_generic($entry['scanner_location_code'],$def,'scanner_location_code','list').'): '
		. $link;

}

function count_entries_client($idnum)
{
	global $client_table, $client_table_id, $entry_table;
        $count_sql = "
		SELECT Count(*) 
		FROM $client_table AS CL, $entry_table as EN
		WHERE CL.$client_table_id=EN.client_id 
		AND CL.$client_table_id=$idnum";
        $count = sql_fetch_row( sql_query( $count_sql  ));
	return $count[0];
}

function link_entry_last()
{
    global $entry_page;
    return link_last($entry_page, "Oldest");
}
function link_entry_first()
{
    global $entry_page;
    return link_first($entry_page, "Newest");
}

function link_entry_prev()
{
    global $entry_page;
    return link_prev($entry_page, "Newer");
}

function link_entry_next()
{
    global $entry_page;
    return link_next($entry_page, "Older");
}

function show_entry_browse_navbar( $sep=" | " )
{
	$date_jump=formto() . "Jump to date: " . hiddenvar("action","jump") . formdate("jump_date") . formend();
	return oline(implode($sep,array(link_entry_last(),link_entry_first(),link_entry_next(),link_entry_prev())),2) . $date_jump;
}

function show_pick_entry()
{
	global $AG_ENTRY_LOCATIONS, $USER_PREFS;

	$entry_locations = array();
	foreach ($AG_ENTRY_LOCATIONS as $key => $label) {
		$default = !be_null($USER_PREFS['entry_location'][$key]);
		$entry_locations[] = span(formcheck('entry_location['.$key.']',$default) . ' ' . $label
						  ,'class="radioButtonSet"');
	}

	$output =
		oline(bold('Select Entry Location(s)'))
		. formto($_SERVER['PHP_SELF'])
		. implode(oline(),$entry_locations)
		. hiddenvar('action','change_locations')
		. oline()
		. button('View','SUBMIT') . smaller(help('Entry','','Help!','',false,true)) 
		. formend();
	return $output;
}

function set_entry_locations()
{
	global $AG_ENTRY_LOCATIONS;

	$entry_res = agency_query('SELECT * FROM l_scanner_location WHERE is_current');
	
	$AG_ENTRY_LOCATIONS = array();
	while ($a = sql_fetch_assoc($entry_res)) {
		$AG_ENTRY_LOCATIONS[$a['scanner_location_code']] = $a['description'];
	}
	return true;
}

?>
