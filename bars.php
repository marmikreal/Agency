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


function bar_status( $client, $group="",$start="",$end="",$brc="",$which=array())
{
	/* $client could be integer, name or client record
	 *  $group needs to be changed to facility code of some sort
	 *  currently $group is just any bed group...may be expanded
	 *  start is a date for checking bar status during a time period
	 *  end is the end date of that time period.  
	 *  If you send only a start date, then start and end will be the same
	 *  hence you can find out if a client was barred on a specific date
	 *  brc is a flag...if set it checks if client was barred to brc
	 */
	if (is_array($client))
	{
		$client = $client["client_id"];
	}
	if ( ! is_numeric( $client) )
	{    
		// text (unregistered) clients not barred
		return false;
	}
	$func = "get_generic";
	$filt = array("client_id" => $client);// get bars for one client
	if ($start) { // get bars within a time period
		$end = orr($end,$start); // if no end date, set it
		//$start could be a date or an sql keyword like CURRENT_TIMESTAMP
		$filt["BETWEEN:bar_date"]= dateof($start) //
			? " '$start' AND '$end' " : " $start AND $end ";
	} else {           //current status check 
		// get_bars_current
		//$func = "get_bars_current";
		$filt['bar_date_end']=array('FIELD>=:bar_date_end'=>'CURRENT_DATE',
						    'NULL:bar_date_end'=>'');
	}
	if ($brc) { // is it a brc bar?
		$filt["bar_date_end"] = array("NULL:bar_date_end" => "",
							"!NULL:brc_client_attended_date" => "");
	}
	
	foreach ($which as $barred_from) {
		$filt['barred_from_'.$barred_from] = sql_true();
	}
	return sql_num_rows($func($filt,'','','bar'))>0;
}

function bar_status_f( $client,$format='',&$is_provisional)
{
	global $engine;
	$format = orr($format,'short');
	// return a formatted string describing bar status
	$client_id=$client["client_id"];
	$filter = client_filter($client_id);
	$filter['bar_date_end']=array('FIELD>=:bar_date_end'=>'CURRENT_DATE',
						'NULL:bar_date_end'=>'');
	$bars = get_generic($filter,'bar_date DESC, bar_date_end DESC','','bar');

	// all is_prov and is_provisional is to determine provisional brc 
	$is_provisional = true;

	$def = get_def('bar');
	if (sql_num_rows($bars) > 0) {
		while($curr_bar = sql_fetch_assoc($bars)) {
			$days_barred = $curr_bar['days_barred'];
			$enddate = $curr_bar['bar_date_end'];
			$brc_resolution = $curr_bar['brc_resolution_code'];
			if ($brc_resolution == 'PROVISION') {
				$prov_reinstate = smaller('('.value_generic($brc_resolution,$def,'brc_resolution_code','list',$do_formatting=false).')',2);
				$is_prov = true;
			} else {
				$prov_reinstate = '';
				$is_prov = false;
			}
			// this only remains true if ALL bars are provisional
			$is_provisional = $is_prov ? $is_provisional : false;
			if (empty($enddate) || $curr_bar['brc_client_attended_date']) {
				$bar_type = "BRC";
			} else {
				$bar_type = $days_barred . ($days_barred > 1 ? " days" : " day");
			}
			$barred_from = value_generic($curr_bar['barred_from_summary'],$def,'barred_from_summary','list');
			$bar_text = bigger(red(bold('BARRED'))).$prov_reinstate.' ('.bold($barred_from).') ';
			if (($format <> "short") && ($format <> "mail"))
			{
				$bar_text .= bigger(red(bold("($bar_type)")));
				$res .= link_engine(array('object'=>'bar','id'=>$curr_bar['bar_id']),$bar_text);
				$date = $curr_bar["bar_date"];
				// show dates and staff who set the bar
				$res .= smaller(" (" 
							    .( dateof($date,"SQL") > dateof("now","SQL") 
								 ? bold(red("Bar set for ")) : "") 
							    .( $enddate && dateof($enddate,"SQL") < dateof("now","SQL") 
								 ? "expired bar started " : "")  
							    . orr(dateof($date),$date)
							    . ($enddate ?
								 "-->" . orr(dateof($enddate),$enddate) : "" )
							    . ")")
						  . smaller(" (by " 
								. staff_link($curr_bar["barred_by"]) . ")",2);
				$res .= oline();
			}
			else
			{
				// want next bar on a new line for short display
				$res .= $bar_text . bigger(red(bold("("
										. ($bar_type == "BRC" 
										   ? $bar_type . 
										   (($format=="mail")
										    ? smaller(blue(" G-->" . dateof($curr_bar["gate_mail_date"]))) : "")
										   : "-->" . dateof($enddate,"US_SHORT")) . ")")));
				$res .= oline(); 
			}
		}
	}
	else
	{
		$link = html_no_print(' ('.link_engine(array('object'=>'bar','action'=>'add','rec_init'=>array('client_id'=>$client_id)),'Add a bar').')');
		$res = smaller('Not barred'.$link);
		if ($format=='mail') {
			$res = oline($res);
		}
	}
	return $res;
}

function gatemail_status_f($client)
{
// 	$cbars = get_bars_current($client["client_id"]);
	$filter = client_filter($client['client_id']);
	$filter['bar_date_end']=array('FIELD>=:bar_date_end'=>'CURRENT_DATE',
						'NULL:bar_date_end'=>'');
	$cbars = get_generic($filter,'bar_date DESC, bar_date_end DESC','','bar');
	while($bar = sql_fetch_assoc($cbars))
	{
		if ($gdate = $bar["gate_mail_date"])
		{
			$stat = oline(smaller("Gate Mail through " . dateof($gdate)));
			break;
		}
	}
	return $stat;
}

function form_row_bar($key,$value,&$def,$control,&$Java_Engine,$rec)
{
	if (!in_array($key,array('client_id','non_client_name_last','non_client_name_first','non_client_description'))) {
		return form_generic_row($key,$value,&$def,$control,&$Java_Engine,$rec);
	}

	if (!be_null($rec['client_id'])) { //client bar
		//hide non-client bar fields
		if ($key !== 'client_id') {
			return false;
		}
		return form_generic_row($key,$value,&$def,$control,&$Java_Engine,$rec);
	} else { //non-client bar
		//hide client id
		if ($key == 'client_id') {
			return false;
		}
		return form_generic_row($key,$value,&$def,$control,&$Java_Engine,$rec);
	}
}

function view_row_bar($key,$value,$def,$action,$rec)
{
	if (!in_array($key,array('client_id','non_client_name_last','non_client_name_first','non_client_description','non_client_name_full'))) {
		return view_generic_row($key,$value,$def,$action,$rec);
	}

	if (!be_null($rec['client_id'])) { //client bar
		//hide non-client bar fields
		if ($key !== 'client_id') {
			return false;
		}
		return view_generic_row($key,$value,$def,$action,$rec);
	} else { //non-client bar
		//hide client id
		if (in_array($key,array('client_id','non_client_name_last','non_client_name_first'))) {
			return false;
		}
		return view_generic_row($key,$value,$def,$action,$rec);
	}
}

?>
