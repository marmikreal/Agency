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

/*  charge_add.php provides the user interface for viewing, voiding,
*   and adding new charges.  This script requires a valid client ID
*   to run properly.
*/
//$query_display='Y';
$title = "View Charges";
$quiet="Y";
include "includes.php";

if (! has_perm("rent","R"))
{
	agency_top_header();
	outline("You do not have permission to see charges");
	page_close();
	exit;
}

if (db_read_only_mode()) {
	agency_top_header();
	out(alert_mark('Database is in Read-Only Mode: Can\'t add/void charges'));
	page_close();
	exit;
}


$charge_format = "full";
$show_voids = "N";
$show_vb = "N"; // show void button
$show_add_form = "Y";

$_SESSION['CLIENT_ID'] = $CLIENT_ID = orr($_REQUEST['client_id'],$_SESSION['CLIENT_ID']);

if (empty($CLIENT_ID)) {
	agency_top_header();
	outline("Can't display charges without a client ID.");
	page_close();
	exit;
}

if ($_REQUEST["action"]=="recalc") {
	$re_date = dateof($_REQUEST["re_month"]);
	if ($re_date && $CLIENT_ID)
	{
		$out .= oline("OK.  I'm going to recalculate charges for " . date("F",dateof($re_date,"TS")) .", for client ID # $CLIENT_ID");
		$out .= oline("Please verify that the new charges were created correctly.");
		$out .= oline("(If no charges were created, remember that you may have to void existing charges first)");
		landlord($re_date,array("client_id"=>$CLIENT_ID));
		$out .= oline("Done with recalculation");
	}
	else
	{
		$out .= oline("I was asked to recalculate charges, but I need both a month and a client ID")
			 . oline("I have a client ID of $CLIENT_ID, and a month of $re_date");
	}
}

$name_link = client_link($CLIENT_ID);  // for title display
$action = $_REQUEST['action'];
// process $action
// ADD CHARGE
if ($action == "add")
{
    $action_message = bigger(bold("Adding Charge..."));
//    while (list($key,$value) = each($GLOBALS["HTTP_POST_VARS"]))
    foreach ($_POST as $key => $value) 
    {    
		// FIXME:  This seems like a bad idea, we're just looping through all the POST vars
		// instead of looping through all the charge fields & then finding matching POST vars.
		$value = trim($value);
        // don't want $action or empty values added to the charge table
        // but zeros are OK
        if ($key == "action" || empty($value) && ! is_numeric($value))
        {
            continue;
        }
        $addcharge[$key] = dewebify($value == "0" ? 0 : $value);        
    }
    
    // handle confirmations from adding with unusual project or unit
    $_SESSION['sess_confirm_project'] = $sess_confirm_project = orr($_GET['confirm_project'],$_SESSION['sess_confirm_project']);
    $_SESSION['sess_confirm_unit'] = $sess_confirm_unit = orr($_GET['confirm_unit'],$_SESSION['sess_confirm_unit']);

    // get url variables only happens when user doesn't want to use the last
    // known project or unit for the client
    if ( ($sess_confirm_project=="Y") || ($sess_confirm_unit == "Y") )
    {
//         while (list($key,$value) = each($GLOBALS["HTTP_GET_VARS"]))
	    foreach ($_GET as $key => $value)
	    {
			// See FIXME above.  Also, we're doing the same thing in two different places!
			// don't want to insert certain variables into the db
            if ( ($key == "action") || ($key == "confirm_project") 
                || ($key == "confirm_unit") || empty($value) 
                && (! is_numeric($value)) )
            {
                continue;
            }
            $addcharge[$key] = dewebify($value);        
        }
    }

    // write-offs are negatives; 
    // subsidies need is_subsidy set to true
    // ??? in the long run, I believe this functionality should be in the
    // database as a trigger/function type thing 
    if ($addcharge["charge_type_code"] == "WRITEOFF")
    {
        $addcharge["amount"] = 0 - abs($addcharge["amount"]);
    }
    elseif (in_array($addcharge["charge_type_code"],array("SUBSIDY","VACANCY")))
    {
        $addcharge["is_subsidy"] = sql_true();
    }
    $post_it = validate_addcharge_data($addcharge);
    if ($post_it == "")
    {   
        post_charge($addcharge);
        // free variables for handling the next added charge
	  $_SESSION['sess_confirm_project']=null;
	  $_SESSION['sess_confirm_unit']=null;
        $addcharge = "";  // clear defaults displayed in add form
    }
    else
    {
        $out .= oline("Could not post charge.  There were errors:  ");
        $out .=oline(bold($post_it));        
    }
}

// PRCOESS VOID
if ($action == "void")
{
    $charge_id = $_POST["charge_id"];
    if (!empty($charge_id))
    {
        $void_comment = $_POST["vcomment"];
        if (! empty($void_comment))
        {
            if (!void_charge($charge_id, $void_comment))
            {
                $action_message = bold("Unable to void charge.  Please
                        contact your system administrator.");
            }            
			else
			{
				$action_message = bigger(bold("Voiding Charge..."));
			}
		}
        else
        {
            $out .= oline("Could not void charge.  There were errors:  ");
            $out .= oline(bold("Comments are required."));
            $action = "voidform"; // show charge and void form again
        }
    }
    else
    {
        $action_message = bold("No charge ID.  Can not void.");            
    }
}

// SHOW VOID FORM (i.e. selected void button to the right of the charge)
if ($action == "voidform")
{
    $charge_id = $_POST["charge_id"];
    $show_add_form = "N";  // don't show add void form
    $action_message = bigger(bold("Prepare to Void Charge $charge_id"));
    $form_title = bigger(bold("Describe why the charge is being voided"));
    if (!empty($charge_id))
    {
        $charge_results = get_charge($charge_id);
        $charge = sql_fetch_assoc($charge_results);
        $charge_form = show_charges_void_form($charge["charge_id"]);
        $charge_results = get_charge($charge_id);
    }
    else
    { 
        $action_message = bold("No charge ID.  Can not display in void form.");
        $show_add_form = "Y";
    }
}

// PREPARE FOR DISPLAY -- ONLY SHOWING THE VOID FORM IS DIFFERENT FROM THIS
if ($show_add_form == "Y")  // true for most scenarios
{
    // for displaying add form
    $residence = get_last_residence($CLIENT_ID);
    $curr_res = sql_fetch_assoc($residence);
    $curr_project = $curr_res["housing_project_code"]; 
    $curr_unit = $curr_res["housing_unit_code"];
    $show_voids = "Y";
    $show_vb = "Y"; // show void button
    $charge_results = get_charges_for($CLIENT_ID);
    $form_title = hrule() .bigger(bold("Add a New Charge for $name_link"));
    $charge_form = show_charges_add_form($CLIENT_ID, $curr_unit, 
                    $curr_project, $addcharge);    
}

// validate clientid
if (empty($CLIENT_ID) || (!is_client($CLIENT_ID)) )
{
    //  should exit not set client
    $out .= oline(subhead($title));
    $out .= oline("Client ID is invalid");
	agency_top_header();
	out($out);
	page_close();
	exit;        
}

$charge_display = show_charges($charge_results, $charge_format, $show_voids, $show_vb);

// display charges and form
$out .= subhead(hlink($_SERVER['PHP_SELF']."?client_id=$CLIENT_ID", "View Charges") 
    . " for $name_link"); 
$out .=  $action_message;
$out .= oline($charge_display);    
if (has_perm("rent","W"))
{
	$out .= $form_title;
	$out .= $charge_form;
	// post links for recalcing charges for current & previous months
	// If next month charges have already been created (after the 20th of the month), provide that link too
	$cmonth = dateof("now","SQL");
	$lmonth = dateof(last_month($cmonth),"SQL");
	$lmonth2= dateof(last_month($lmonth),"SQL");
	$nmonth = (day_of($cmonth) >= 20) ? next_month($cmonth) : "";
	$commands = array(cell(oline( 
				( $nmonth ? oline(
					hlink($_SERVER['PHP_SELF']."?action=recalc&re_month=$nmonth","Recalculate charges for " . date("F",dateof($nmonth,"TS"))))
					: "")
				. hlink($_SERVER['PHP_SELF']."?action=recalc&re_month=$cmonth","Recalculate charges for " . date("F",dateof($cmonth,"TS"))))
				. oline(hlink($_SERVER['PHP_SELF']."?action=recalc&re_month=$lmonth","Recalculate charges for " . date("F",dateof($lmonth,"TS"))))
				. hlink($_SERVER['PHP_SELF']."?action=recalc&re_month=$lmonth2","Recalculate charges for " . date("F",dateof($lmonth2,"TS")))));
}
else
{
	$out .= oline("You do not have permission to add or void charges");
}
agency_top_header($commands);
out($out);
page_close();    
?>
