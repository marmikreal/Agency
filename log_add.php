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

//$query_display="Y";

$quiet="Y";
$title = "Add a Log Entry";
include "includes.php";

if (db_read_only_mode()) {
	agency_top_header();
	out(alert_mark('Database is in Read-Only Mode: Can\'t add logs'));
	page_close();
	exit;
}

function reset_log()
{
	// edate and etime handled separately, because they are not variables in LOG
	// (added_at) is.

	global $CLIENTS, $STAFF, $LOG;
	$_SESSION['LOG_CLIENTS'] = $CLIENTS = array();
	$_SESSION['LOG_STAFF'] = $STAFF = array();
	/*
	remove_id( 0,$CLIENTS);
	remove_id( 0,$STAFF);
	*/
	$LOG= $_SESSION['LOG'] = array();
	unset($GLOBALS["edate"]);
	unset($_REQUEST["edate"]);
	unset($GLOBALS["etime"]);
	unset($_REQUEST["etime"]);
	unset($GLOBALS["occurred_at"]);
}

function log_type_selector()
{
	global $LOG,$colors, $NL;
	$description = oline(bigger(bold("Specify which logs: ")));
	$types=which_logs($LOG,"in_");
	// values of types are the names of each checked box
	// we need thses values as keys for show_log_types()
	foreach($types as $key => $field_name)
	{
		$field_picks[$field_name]="t";
	}    

	$output .= box($description . show_log_types($field_picks,"",$NL),$colors["pick"]);
	return $output;
}

function subject_text_row()
{
	global $LOG;
	$subject = $LOG["subject"];
	return row( cell(bigger(bold("Subject:"))) 
			. topcell( red("*")) 
			. cell(formvartext("subject",$subject,"SIZE=60 MAXLENGTH=80") ) );
}

function log_text_cell()
{
	global $LOG;
	$log_text = $LOG["log_text"];
	return cell(bigger(bold("Compose<br>your<br>log here:")) )
		. topcell(red("*")) . cell(formtextarea("log_text",$log_text, "wrap=physical")); 
}


function eventtime_box()
{
	// display the Event Time field...the datetime when the event
	// described in the log occurred.
	global $added_at,$colors, $LOG, $occurred_at;       
	$output = box(
			  oline(bigger(bold("Late Entry?"))
				  . smaller(" &nbsp; If so, please enter the following:"),2)
			  . bold("Event Date:")
			  . oline(formdate("edate", dateof($occurred_at)),2 )
			  . bold("Event Time:")
			  . formtime("etime", orr(timeof($occurred_at,"ampm"),$_REQUEST["etime"]))
			  ,$colors["addl"] );
	return $output;
}

function log_flags()
{
	global $was_police, $was_medics, $was_drugs, $was_assault_client, 
		$was_assault_staff, $was_threat_staff, $was_bar, $was_drugs, $fields,$LOG;
	$output = section_title("Additional Information")
		. oline("Please answer Yes or No to the following:"
			  . red("*"),2);
	foreach( $fields as $key=>$value)
	{
		if (!(substr($key,0,4)=="was_"))
		{
			continue;
		}
		$output .= oline(red("*") . yes_no_radio($key, $value,$LOG[$key]));
	}
	return $output;
}

function log_submit_button()
{
	return button("Submit Log","SUBMIT","action");
}

function log_reset_button()
{
	return button("Reset Log","SUBMIT","action",'',call_java_confirm('Reset Log?'));
}

function log_verified_button()
{
	return button("Post Log","SUBMIT","action");
}

function log_reedit_button()
{
	return button("Return to Entry Screen","SUBMIT","action");
}

function post_log($log)
{
	// This function actually posts the log entry
	global $fields;
	$def = get_def('log');
	unset($log["Title"]);
	if (! $log["occurred_at"])
	{
		unset($log["occurred_at"]);
	} else {
		$log['occurred_at']=datetimeof($log['occurred_at']);
	}
	// Need to add added_at value to filter, so that old logs are not matched.  (bug 4369)
	$a=get_logs($log); // Make sure not to re-post a log entry
	if (sql_num_rows($a)>0)
	{
		$a=sql_fetch_assoc($a);
		$a=$a["log_id"];
		outline("This log entry already exists as " . log_link($a,"log $a") . ".  Will not post duplicate.");
		return false;
	}	
	$log['added_at']=date("Y-m-d H:i:s");
	$log["md5_sum"]= md5($log["log_text"] . $log["added_by"] . $log["subject"]
				   . $log["added_at"] . $log["added_at"] );

	if (! agency_query(sql_insert( $def['table_post'],$log )))
	{
		log_error("Failed inserting log into table.  Record was " . dump_array($log));
		return false;
	}
	$a=get_logs($log);
	if (! $a || (sql_num_rows($a) == 0)  )
	{
		log_error("Couldn't get log after posting.  Record was " . dump_array($log));
		return false;
	}
	else
	{
		$row = sql_fetch_assoc( $a );
		return $row["log_id"];
	}
}


function verify_log()
{
	global $LOG, $colors,$UID, $STAFF, $CLIENTS;
	headtitle ("Verify Log Entry.");
	outline(bigger(bold(red("IMPORTANT:  Your Log Has NOT been posted yet!"))),2);
	outline("
You are about to post this entry to the log.
Once it is posted, it cannot be removed or
changed.  Please review it carefully to make
sure it is clear and accurate.

If you want to make changes, press the \"Return to Entry Screen\" button.
If you want to post this entry without changes, 
press the \"Post Log\" button.
	",2);
	out(log_show1($LOG,$CLIENTS,$STAFF));
	out(formto($_SERVER['PHP_SELF'])
	    . hiddenvar("valid",true)
	    . log_reedit_button()
	    . log_verified_button()
	    . formend()
	    );
}

function update_field_vars()
{
	// set_session_var is in agency_config.php
	global $fields, $LOG;
	// not using foreach here because it requires setting two variables
	// the element and its value when all we need are the elements

	foreach (array_keys($fields) as $key) {
		global $$key;
		if (get_magic_quotes_gpc()) {
			$$key = stripslashes($$key);
		}
		$$key = $LOG[$key] = orr($$key,$LOG[$key]);
	}
}

function update_pick_vars()
{
	global $log_types,$LOG,$pick_logs;
	// we need to pass each element of pick_logs as a string variable
	// that set_session_var can use
	foreach($log_types as $type => $label)
	{   
		$pick="in_$type";
		if ($pick_logs[$pick])
		{
			$LOG[$pick]=sql_true();
		}
		else
		{
			$LOG[$pick]=sql_false();
		}
	}
	//outline("Done with update_pick_vars: " . dump_array($LOG));
}

function validate_log_vars($log, &$message)
{
	global $fields,$CLIENTS;
	$message = ""; //reset
	foreach ($fields as $name => $label)
	{
		// all fields need a value  and added_at is not required
		if ($log[$name]==""  && $name != "occurred_at")
		{
			$message .= oline("$label cannot be blank");
		}        
	}
	/*
	 // no logs or clients specified:
	if ( (count(which_logs($log))==0) && ($CLIENTS[0]==0) )
	{
		$message .= oline("You must specify at least one log, or one client, to post a log entry.");
	}
	*/
	if ( (count(which_logs($log))==0) ) // client-only logs disabled
	{
		$message .= oline("You must specify at least one log to post a log entry.");
	}
	if ( (dateof($log["occurred_at"])) || (timeof($log["occurred_at"])) )
	{
		if ( ! (dateof($log["occurred_at"])) )
		{
			$message .= oline("For late entry, must enter a valid date");
		}
		if ( ! (timeof($log["occurred_at"])) )
		{
			$message .= oline("For late entry, must enter a valid time");
		}
		if ( datetimeof($log["occurred_at"] ) > datetimeof("now") )
		{
			$message .= oline("Late entry date cannot be in the future.");
		}
	}	
	if ( ($log["added_by"] == 0) or ($log["added_by"] <> $GLOBALS["UID"]) )
	{
		$message .= oline("staff_id in log array =" . $log["added_by"] );
		$message .= oline("UID Global =" . $GLOBALS["UID"]);
		$message .= oline("Title =" . $log["Title"] );
		$message .= oline("Here is the log array: " . dump_array($log));
		$message .= oline("Problem with posted by staff field.  Contact your system administrator now.");
	}
	return ($message ? 0 : 1);
}

$warning = "Note:  No logs specified.  <BR>This log entry will only show 
            in the ".AG_MAIN_OBJECT."'s record.";


//----------set requested variables----------//
$tmp_request_vars = array('action',
				  'edate',
				  'etime',
				  'log_text',
				  'pick_logs',
				  'subject',
				  'valid',
				  'was_police',
				  'was_medics',
				  'was_drugs',
				  'was_assault_client',
				  'was_assault_staff',
				  'was_threat_staff',
				  'was_bar',
				  'was_drugs');
foreach( $tmp_request_vars as $tmp ) {
	$$tmp = $_REQUEST[$tmp];
}
//-------------------------------------------//

$LOG = $_SESSION['LOG'];
$SHOW_LOGS = $_SESSION['SHOW_LOGS'];
$LOG_POS = $_SESSION['LOG_POS'];

// make sure CLIENTS & STAFF are arrays
$CLIENTS = $_SESSION['LOG_CLIENTS'] = orr($_SESSION['LOG_CLIENTS'],array());
$STAFF = $_SESSION['LOG_STAFF'] = orr($_SESSION['LOG_STAFF'],array());

// data fields for LOG session variable with labels
// used by validate_log_vars() and update_field_vars()
$fields = array('log_text'           => 'Text of Log', 
		    'subject'            => 'subject',
		    'occurred_at'        => 'Event Date and Time',
		    'was_assault_staff'  => 'Staff Assaulted or Injured',
		    'was_threat_staff'   => 'Staff Threatened',
		    'was_assault_client' => 'Client(s) Assaulted or Injured',
		    'was_police'            => 'Were Police Called',
		    'was_medics'         => 'Were Medics Called',
		    'was_bar'            => 'Client(s) Barred',
		    'was_drugs'          => 'Drugs or Alcohol Involved');

//$edate and $etime are local to the form.  They are not in $LOG.
//$added_at becomes the valid variable for the session.    

if (!be_null(trim($edate)) || !be_null(trim($etime)))
{
	$occurred_at = dateof($edate,'SQL') . (timeof($etime) ? ' ' . timeof($etime) : '');
}

// if any new data, put in LOG session variable
update_field_vars();

if (($action == 'Submit Log') || ($action == 'ClientSearch') || isset($_REQUEST['staff_select']))
{
	// capture log selections if coming from log edit screen
	update_pick_vars();
	//set session var here, in case of exit
	$_SESSION['LOG'] = $LOG;
}

$CLIENT_ONLY = $_SESSION['LOG_CLIENT_ONLY'] = orr($_REQUEST['client_record_only'],$_SESSION['LOG_CLIENT_ONLY']);

if ($action=='Reset Log')
{
	reset_log();
	$action='new';
}
$cl_sel=bottomcell(client_selector()); // this gets selector code, and processess selections
$st_sel=bottomcell(staff_selector(),'class="staff" style="text-align: center;"');

// Quick Search Processing (moved to function):
process_quick_search("Y",false); // do quick search, and stop


//$valid is a flag if LOG data is valid, then $valid is true
$LOG['changed_by'] = $LOG['added_by'] = $UID;
$LOG["Title"]="New Log Entry";

if ($action == "Submit Log") // This is submit
{
	$valid = validate_log_vars($LOG, $message);
	if ($valid)
	{    
		agency_top_header();
		if (count(which_logs($LOG)) < 1)
		{
			outline(alert_mark($warning));
		}
		verify_log();
		page_close();
		$_SESSION['LOG'] = $LOG;
		exit;
	}
}

if (($action == "Post Log") && $valid && (count($LOG)>2) &&
    ((count(which_logs($LOG)) >= 1) || ($CLIENT_ONLY=="Y"))  ) // Time to post
     // don't post without LogText & subject (reload of successful posting)
{
	agency_top_header();
	headline("Posting log... please stand by",2);
	foreach ($log_types as $z=>$dummy)
	{
		$LOG["in_$z"] = orr($LOG["in_$z"],sql_false());
	}
	$newid = post_log($LOG);
	if ($newid)
	{
		outline("Your Log was Posted with ID # $newid");

		if ( post_staff_a($newid, $UID) )
		{
			outline("Staff Alerts Successfully Posted");
			if ( post_clients($newid, $UID) )
			{
				outline(ucwords(AG_MAIN_OBJECT)." References Successfully Posted",2);
				headline("Your Complete Log Entry was Successfully Posted!!",2);
				reset_log();
				$LOG_POS++;
				$_SESSION['LOG_POS'] = $LOG_POS;
			}
			else
			{
				sql_warn("Failed Posting ".AG_MAIN_OBJECT." References");
			}
		}
		else
		{
			sql_warn("Failed Posting Staff References.<br>".AG_MAIN_OBJECT." References were NOT posted");
		}
		$CLIENT_ONLY = $_SESSION['LOG_CLIENT_ONLY'] = '';
	}
	else
	{
		//		sql_warn("Failed to Post Log Entry");
		//		log_error("Failed to Post Log Entry");
		outline("");
		outline(bigger(bold("Your log entry was not posted.")),2);
	}
	outline(bigger(link_index_log("Return to the Log Index",2)) );
	outline("OR");
	outline(bigger(link_add_log("Add Another Log Entry")));
}
// when log is reset, it contains only title & UID, meaning count=2, which
// is thus test for a blank log.
elseif(($action=="Post Log") && ((count(which_logs($LOG))) < 1) && (count($LOG)>2)
	 && ($CLIENT_ONLY != "Y") && $valid)
{
	$response_ok = "Thanks for the warning.  Please post this entry to the
        ".AG_MAIN_OBJECT."'s record.";
	$response_edit = "Oops! I'd like to edit this entry again.";
	$edit_entry = webify("Return to Entry Screen");
	$post_entry = webify("Post Log");    
	outline(alert_mark($warning),2);      
	outline(hlink($_SERVER['PHP_SELF']."?client_record_only=Y&action=$post_entry&valid=Y",$response_ok),2);
	outline(hlink($_SERVER['PHP_SELF']."?action=$edit_entry",$response_edit));
}
else
{
	// This is basic log edit
	// post warnings if submit or post was unsucessful
	agency_top_header();

	if ( (($action == "Post Log") || ($action == "Submit Log")) && ($message != ""))
	{
		out(box(
			  oline(bigger(bold("You have the following problems with your entry")),2)
			  . $message,$colors["alert"]));
	}
	headline(center("Add A New Log Entry"));
	outline(tablestart("",'bgcolor="'.$colors["blank"].'"') . row( cell("(Fields marked with a " . red("*") . " are required.) (")
										     . cell(ucwords(AG_MAIN_OBJECT)." References","bgcolor=${colors["client"]}")
										     . cell(" and ") . cell("Staff Alerts","bgcolor=${colors["staff"]}") . cell("are Optional.)" ))
		  . tableend() );
	out( formto( $_SERVER['PHP_SELF'] )
	     . oline(log_type_selector(),2)
	     . tablestart()  
	     . row($cl_sel . $st_sel)
	     . tableend()
	     . oline("",2)
	     . oline(eventtime_box())
	     . oline(box(log_flags(),$colors["alert"]))
	     . tablestart("","width=70%")
	     . subject_text_row()
	     . row( log_text_cell() )
	     . tableend()
	     . oline("")
	     . log_submit_button() 
	     . log_reset_button()
	     . formend() 
	     );

}
page_close();
//set session var here.
$_SESSION['LOG'] = $LOG;
?>
