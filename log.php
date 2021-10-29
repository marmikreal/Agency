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

function log_link( $idnum, $label="lookup" )
{        // Doesn't do Validate or Match
	if (!$idnum) { return false; }
	$def=get_def('log');
	$log_table=$def['table'];
	$id_field=$def['id_field'];
	if ($label=="lookup")
	{
		//$label =sql_assign("SELECT SUBSTRING(COALESCE(subject,log_text) FROM 0 FOR 50) FROM $log_table",array($log_table . "_id" => $idnum));
			$label =object_label('log',$idnum);
	}
	return elink('log',$idnum,$label);
}

/*
function which_logs_f( $log, $sep=", ")
{
// take a log record, and return a formatted string of which logs it's in
	global $log_types;
	$in_logs=which_logs( $log );
	foreach ($in_logs as $l) {
		$in_logs_f[] = $log_types[$l];
	}
	$in_logs_text=implode($sep,orr($in_logs_f,array()));
	return blue(orr($in_logs_text,AG_MAIN_OBJECT." record only"));
}

function which_logs( $log,$pre="" )
{
// take a log record, and return an array of which logs it's in
	global $log_types;
	$in_logs_list=array();
	foreach ($log_types as $key=>$value)
	{
		if ($log["in_$key"]==sql_true())
		{
			array_push($in_logs_list,"$pre$key");
		}
	}
	return $in_logs_list;
}

function engine_record_perm_log($control,$rec,$def) {

	global $UID;

	if ($control['action']=='add') { return true; }
	if (isset($rec['_client_links'])) {
		$clients = sql_to_php_array($rec['_client_links']);
	} else {
		$clients = get_clients_for_log( $rec['log_id'],'NF');
	}

	$clients = array_filter($clients);

	if (isset($rec['_case_mgr_id'])) {
		$cm = sql_to_php_array($rec['_case_mgr_id']);
	} else {
		$cm = get_staff_clients($clients);
	}

	if (isset($rec['_staff_alert_ids'])) {
		$staff_alert=sql_to_php_array($rec['_staff_alert_ids']);
	} else {
		$staff_alert=get_alerts_for_log( $rec['log_id'],'NF' );
	}

	$in_logs=which_logs($rec,'log_');

	$perm = (!$in_logs) // client_only entry
			|| has_perm($in_logs,'R') // log_specific permission
			|| in_array($UID,$staff_alert)  // Flagged to user's attention
			|| in_array($UID,$cm); //staff assigned to client
	return $perm;
}

function db_links_array($arr)
{
	foreach ($arr as $key=>$link)
	{
		$arr[$key]=stripslashes($link);
	}
	return $arr;
}
*/
/* log/engine functions */

/*
function generate_list_medium_log($result,$fields,$max,$position,$total,$control,$def,$control_array_variable,&$rec_num)
{
	return generate_list_long_log($result,$fields,$max,$position,$total,$control,$def,$control_array_variable,&$rec_num);
}
*/

function generate_list_long_log($result,$fields,$max,$position,$total,$control,$def,$control_array_variable,&$rec_num)
{
	if (!in_array($control['format'],array('long','medium'))) {
		return generate_list_generic($result,$fields,$max,$position,$total,$control,$def,$control_array_variable,&$rec_num);
	}

	$pos=$control['list']['position'];
      $mx=$control['list']['max'];

      while ( $x<$mx and $pos<$total) {
		$a = sql_fetch_assoc($result,$pos);
		$link = link_engine(array('object'=>$def['object'],'id'=>$a[$def['id_field']]),'View');
		$out .= div(view_generic($a,$def,'list',$control) . html_heading_6($link)); 
		$pos++;
		$x++;
	}
	return $out . list_links($max,$position,$total,$control,$control_array_variable);
}
/* end log/engine functions */

function show_log_types($picks="",$formvar="",$sep="")
{
// create options to choose which logs to view
	$formvar=orr($formvar,"pick_logs");
	$picks=orr($picks,array());
	return do_checkbox_sql('SELECT log_type_code AS value,description AS label FROM l_log_type',$formvar,$picks);
}

function get_logs( $filter, $order="" )
{
    global $log_table, $log_table_id,$log_select_sql;
    //return agency_query( $log_select_sql, $filter, $order );
    return get_generic( $filter, $order,NULL,get_def('log') );
}

function show_pick_logs()
{
// allow user to select which logs to view
	global $SHOW_LOGS,$logs_per_screen,$DISPLAY;
	$output = formto($_SERVER['PHP_SELF'])
		. table(row(leftcell(smaller("Jump to date: " . formvartext('log_jump_date','','size=7')))
		. cell(  
					     oline(smaller(hard_space("Logs per page "))
					     . formvartext("logs_index_count",$logs_per_screen,"size=5"))
					     . rightcell(formcheck("show_photos",$DISPLAY["photos"]) 
					     . smaller(hard_space(" Show Photos"))))
//					     . formcheck("pick_logs[flagged_only]",$SHOW_LOGS["flagged_only"]) 
//					     . smaller(hard_space(' "Red-flagged" Only'))
						,' style="white-space: nowrap;"')
		//. smaller(hard_space("Logs to view: ") . show_log_types($SHOW_LOGS))
		. row(cell(''))
		. row(cell(smaller(hard_space("Logs to view: ") . show_log_types($SHOW_LOGS))
		. hiddenvar('$control[action]',"pick_logs")
		. button("View","SUBMIT") . smaller(help("Logs","","Help!",'class="fancyLink"')) 
		. formend(),'colspan=2'))
			  ,'',' cellpadding="0" cellspacing="0" width=100% class="pick"');
	return $output;
}

/*

function get_log($filter,$order='',$limit='',$def,$control=null) //same params as get_generic
{
	outline("FIXME:  Called get_log.  Don't delete me so fast!!!");
}
*/

/*
function get_log($filter,$order='',$limit='',$def,$control=null) //same params as get_generic
{ //this would be named get_log, but that function already exists
	$filter=orr($filter,array());
	$log_table = $def['table'];

	$ref_def = get_def('client_ref');
	$ref_table = $ref_def['table'];

	$a_def = get_def('alert');
	$alert_table = $a_def['table'];
	if (array_key_exists('client_id',$filter)) { //we'll be requiring a left join on the client_ref table
		$sql = "SELECT DISTINCT log.*,
                          array(SELECT t.staff_id FROM $alert_table AS t WHERE t.ref_id=$log_table.log_id) AS _staff_alert_ids,
array(SELECT distinct client_id FROM $ref_table WHERE ref_table='LOG' AND ref_id=log.log_id) AS _client_links,
//						array(SELECT distinct client_link(client_id) FROM $ref_table WHERE ref_table='LOG' AND ref_id=log.log_id) AS _client_links,
                          array(SELECT coalesce(staff_id,0) FROM staff_assign WHERE client_id IN 
                                     (SELECT distinct client_id FROM $ref_table WHERE ref_table='LOG' AND ref_id=log.log_id)
                                      AND staff_assign_date_end IS NULL
                          ) AS _case_mgr_id
                  FROM {$log_table} AS log 
                     LEFT JOIN ${ref_table} AS ref ON (ref.ref_table='LOG' AND ref.ref_id=log.log_id)";
		$def['sel_sql']=$sql;
	} elseif ($control['action']=='list' && in_array('client_id',$control['list']['fields'])) {
		$sql="SELECT DISTINCT log.*, ref.client_id 
                  FROM {$log_table} AS log 
                     LEFT JOIN ${ref_table} AS ref ON (ref.ref_table='LOG' AND ref.ref_id=log.log_id)";
		$def['sel_sql']=$sql;
	}

	return get_generic($filter,$order,$limit,$def,$control);
}
*/

/*
function show_log_heads( $logs, $photos="N", $reverse=false)
{
	global $colors, $UID;
// needs added_at, added_by, log_id, Snippet, subject
	if (sql_num_rows($logs)==0 )
	{
		$result .= oline("No Logs to Display");
		return $result;
	}
	$result .= tablestart("","border=5")
		. row( 
		cell(bold(oline('When/') . 'Log #')) .
		cell(bold(oline('By Whom/') . 'Log(s)')) .
		cell(bold(ucwords(AG_MAIN_OBJECT)."s")) .
		cell(bold("subject")) ); 
 
	$rows = '';
	while($log=sql_fetch_assoc($logs)) {
		$st=get_alerts_for_log( $log["log_id"],"NF" );
		$cl=get_clients_for_log( $log["log_id"],"NF");
		// if alert to this staff, use $alert_color:
		$color_ref = (in_array($UID,$st)) ? " bgcolor=${colors["alert"]}" : "";
		// if log by this staff, use $alert_color:
		$color_by = ($UID==$log['added_by']) ? " bgcolor=${colors["alert"]}" : "";
		$Snippet = orr($log["subject"],$log["snippet"]);
		$in_logs=which_logs($log,"log_");
		$authorized = (!$in_logs) // client_only entry
			|| has_perm($in_logs,"R") // log_specific permission
			|| in_array($UID,$st)  // Flagged to user's attention
			|| in_array($UID,get_staff_clients($cl)); //staff assigned to client
		$tmp_row = row( 
				   cell( smaller(oline(dateof($log["added_at"])) . 
					   oline(timeof($log["added_at"]))
						. bold(italic("#" . $log["log_id"])),2))
				   . cell( smaller(oline(staff_link($log["added_by"]))
										. which_logs_f($log),1), $color_by ) 
				   . cell( orr( get_clients_for_log( $log["log_id"],"<br>",$photos), 
						    "(no ".AG_MAIN_OBJECT."s referenced)" ),'class="client"')
				   . cell( $authorized
					     ? log_link($log["log_id"],webify($Snippet))
					     : "(Not authorized to view)" ,$color_ref));
		if ($reverse) { 
			$rows = $tmp_row . $rows; 
		} else {
			$rows .= $tmp_row;
		}
    }
	$result .= $rows . tableend();
	return $result;
}
*/

?>
