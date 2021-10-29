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


function show_query_row_client($fields,$position,$rec,$control,$def,$control_array_variable='')
{

	/*
	 * If writing a generic function to mimic this, 
	 * it must return count($fields)+2 cells as a string
	 * keeping in mind that the column labels will be for 
	 * the fields passed in the $fields variable
	*/

      global $colors;
      $reverse=$control['list']['reverse'];
      $MAX=$control['list']['position']+$control['list']['max'];
      $count = $reverse 
		? ($MAX-$position)+$control['list']['position']
		: $position+1;
      $control['list']['position']=$position;
	$id=$rec[AG_MAIN_OBJECT_DB.'_id'];

	$deceased = client_death_f($id,$death_date,true);

	$out = ( $def['list_hide_view_links'] 
		   ? cell('', 'width="2" class="generalData1"') //hide view links
		   : cell(center(list_view_link($rec,$control,$def)),
			    "bgcolor=\"width=\"20\" class=\"generalDataHeader\""))
		. (($def['list_hide_numbers']) ? cell('','class="generalData1"') : cell(smaller($count),'class="generalData1"'))
		. cell( oline(client_link($id))
			  . ($deceased ? oline(smaller($deceased)) : '')
			  . "ID #" . $id
			  . ($rec['clinical_id'] ? smaller(" (clinical id " . $rec['clinical_id'] . ")") : "")
//			  . smaller("<br>" . priority_status_f($id,"")) . " | " . smaller(" assess: ") . bigger(assessment_f($rec,"tiny"))
//			  . smaller("<br>" . housing_status_f($id)),'class="generalData1"')
			  ,'class="generalData1"')
		
            . cell( last_entry_f($id),'class="generalData1"')
            . cell( bar_status_f($rec,'',$is_provisional ),'class="generalData1"')
//            . cell( '&nbsp;','class="generalData1"')
            . cell( smaller(oline(value_generic($rec['gender_code'],$def,'gender_code','list'))

		   			. oline(multi_objects_f( 
								 get_generic(client_filter($id),'','','ethnicity')
								 ,'ethnicity','ethnicity_code'))
				    . oline(dateof($rec['dob']))
				    . oline($rec['ssn'])) ,'class="generalData1"')
            . cell( client_photo( sql_true($rec['is_protected_id']) ? 0 : $id, 0.5 ),'class="generalData1"'); //passing 0 for protected 
      return $out;
}

function client_show( $id )
{
	global $UID,$colors;

	$def = get_def(AG_MAIN_OBJECT_DB);
	$ID_FIELD=$def['id_field'];
	$client = sql_fetch_assoc(client_get($id));
	$id = $client[AG_MAIN_OBJECT_DB.'_id'];
	
	$protected = sql_true($client['is_protected_id']);
	$protected_f = $protected && has_perm('read_all') ? oline(center(smaller(red('--------> Protected ID <--------')))) : '';

	$deceased_f = client_death_f($id,$deceased_date);

	$elevated_concern = client_elevated_concern_short_f($id);
	
	if ($_REQUEST['display_all_photos'] && (!$protected) )
	{
		$photo_list = client_photo($client[$ID_FIELD],1,true);
		foreach ($photo_list as $p)
		{
			$p_row .= cell(oline($p["http"]) 
					   . oline(orr($p["date"],"unknown date"))
					   . oline(orr($p["time"],"unknown time"))
					   . oline(basename($p["file"])));
			if ($count++ == 5)
			{
				$count=0;
				$p_row .= rowend() . rowstart();
			}
		}
		$photos = oline(table(row(cell(bigger(bold("Showing all ".AG_MAIN_OBJECT." photos...")))) . $p_row,"","bgcolor=\"{$colors['blank']}\" border=\"1\""));
	}
	$ids = oline(bold(smaller("AGENCY ".ucfirst(AG_MAIN_OBJECT)." ID # " . blue($client[$ID_FIELD]))));
	if ($client["clinical_id"])
	{
		$ids .= oline(smaller("Clinical ID (Case ID) # " . $client["clinical_id"]));
	}
	if ($client["resident_id"])
	{
		$ids .= oline(smaller("Resident ID (Housing ID) # " . $client["resident_id"]));
	}
	if ($client["king_cty_id"])
	{
		$ids .= oline(smaller("King County ID # " . $client["king_cty_id"]));
	}
	if ($client['spc_id']) {
		$ids .= oline(smaller('SPC ID# '.$client['spc_id']));
	}

	//-----client notes-----//
	$comments = client_note_f($client[$ID_FIELD]);
	$client_note_hide_button = oline(right(Java_Engine::hide_show_button('ClientNote',false) . smaller('Notes',3)));

	out(div($client_note_hide_button . Java_Engine::hide_show_content($comments,'ClientNote',false),'',' class="clientComment"'));

	// Staff & Case Manager Assignments:
	$staff_assigns=row(rightcell(smaller('Staff & Case Manager Assignments'
							 .html_no_print(oline().link_engine(array('object'=>'staff_assign',
														'action'=>'add',
														'rec_init'=>array(AG_MAIN_OBJECT_DB.'_id'=>$id))
													,smaller('Add new staff assignment'))))
					     ,' style="vertical-align: top; border-right: solid 1px gray; padding: 4px;"') //style here until such time as style sheet
				 .leftcell(client_staff_assignments_f($id),' style="vertical-align: top; padding: 4px;"')); //and here as well
	$bar_status = bar_status($client[$ID_FIELD])
				  ? row( cell(bar_status_f($client,'long',$is_provisional) . gatemail_status_f($client) .smaller(oline()),'colspan="2"')) : '';
	$quick_look = table($bar_status . $staff_assigns . $calendar_appointments,'',' class="" cellspacing="0"');
	$name = $deceased_f
		? gray(client_name($client,0,true))
		: client_name($client);
	$out .= tablestart("",'class=""')
		. row(cell(oline(client_photo( $protected ? 0 : $client[$ID_FIELD]),.25),'rowspan="2"') //passing 0 for protected clients
			. topcell( $elevated_concern . $protected_f . oline(bold(bigger($name,3)).smaller(blue(' (ID #'.$id.')'))). $deceased_f  
				    . $quick_look ) )
		. row("") 
		. row(
			cell(
			     ($client["last_photo_at"] 
				? smaller(oline(
						    dateof($client["last_photo_at"])
						    . (timeof($client["last_photo_at"]) 
							 ? ", " . timeof($client["last_photo_at"]) : "")
						    ),2) 
				: "")
			     . smaller("Card issue # " . orr($client["issue_no"], "(missing)"),2))
			) . row(cell(""))
			. tableend();
	out(oline($out) . $photos);

	$out=tablestart("","border=3 cellpadding=2");

	// Current Registrations:
	$out .= rowrlcell("Current Registrations & Status",
				jail_status_f($id)
				. hospital_status_f($id)
/*
				. conditional_release_f($id)
				. housing_status_f($id)
				. tier_status_f($id)
				. oline(crp_status_f($id))
				. cd_reg_f($id)
*/
				. oline(last_entry_f($id,true))
				. (($bed=which_current_bed($id)) ? oline(which_current_bed_f($bed)) : "")
			  );

	//Bar Status
	$out .= row(
			rightcell('Bar Status')
			. leftcell(bar_status_f($client,'long',$is_provisional) . gatemail_status_f($client)));
	// Medical Appointments
/*
	if ($c_apps = medical_appointments_f($id)) {
		$out .= rowrlcell(oline('Medical/Intake Appointments')
					.jump_to_object_link('calendar_appointment','client',smaller('Jump to medical/intake appointment records',2)),
					$c_apps);
	}
*/
	// Overnight / Priority Status
/*
	$out .= row( 
			rightcell('Shelter Eligibility &amp; Assessment')
			. leftcell(
				     oline(priority_status_f( $id ,' '))
				     . oline(sex_offender_reg_f( $client[$ID_FIELD] ))
				     . oline(volunteer_status_f($client))
				     . oline(client_locker_assignment_f($id))
   				     . oline(safe_harbors_consent_f($id))
				     . oline(chronic_homeless_status_f($id))
				     . assessment_f($client))
			
			);
*/	
	// Disability Information
	$disab_filt = client_filter($id);
	$disab_filt[] = array('NULL:disability_date_end'=>true,
				    'FIELD>=:disability_date_end'=>'CURRENT_DATE');
	$out .= row( rightcell('Disability Information'.
				     html_no_print(oline().smaller(link_engine(array("object"=>"disability",
												     "action"=>"add",
												     "rec_init"=>array($ID_FIELD=>$client[$ID_FIELD])),
											     "Add a disability record") )))
		   . leftcell( multi_objects_f( 
								 get_generic($disab_filt,'','','disability'), 'disability','disability_code','<br>'
								 ) 
				   ));

	// Meds & Allergies
	$out .= row( rightcell("Medical Concerns") . leftcell(
									  ($client["med_issues"]) ? blue(italic($client["med_issues"]))
									  : smaller("(none)") ) );
	$out .= row( rightcell("Medications") . leftcell(
								   ($client["medications"]) ? blue(italic($client["medications"]))
								   : smaller("(none)") ) );
	if ($nic = nicotine_distribution_f($id)) {
		$out .= row( rightcell('NRT Distribution') . leftcell($nic));
	}
	$out .= row( rightcell("Allergies") . leftcell(
								 ( $client["med_allergies"]) ? blue(italic($client["med_allergies"]))
								 : smaller("(none)") ) );
	$out .= row(rightcell("Language")
		  . leftcell ( lang_f($client)));
	
	// Income
	$inc = income_f($id,$has_inc);
	$jump_add = !$has_inc ? 
		smaller(link_engine(array('object'=>'income','action'=>'add','rec_init'=>array(AG_MAIN_OBJECT_DB.'_id'=>$id)),smaller('Add an income record')))
 		: smaller(jump_to_object_link('income'));
	$out .= row(rightcell('Monthly Income'. html_no_print(oline().$jump_add)). leftcell($inc));
	// Balances
//	$out .= row(rightcell('Outstanding Balances').leftcell(balance_by_project($id)));
	// Basic Info
	$out .= row( rightcell(oline('Date of Birth')
				     . 'Gender, Ethnicity, SS, Vet Status')
			 . leftcell( //oline(ageof($client['dob'],'Formatted') ) 
					oline($deceased_f
						? red('Deceased. '.dateof($client['dob']).' - '.dateof($deceased_date).' ('.client_age($id,'NO',$deceased_date).' years old)')
						: client_age($client,'DOB') 

						) 
					. blue(value_generic($client['gender_code'],$def,'gender_code','list')) . green("  |  ")
		   			. multi_objects_f( 
								 get_generic(client_filter($id),'','','ethnicity')
								 ,'ethnicity','ethnicity_code') . green("  |  ")
					. blue($client["ssn"]) . green("  |  ") 
					. blue(value_generic($client['veteran_status_code'],$def,'veteran_status_code','list'))) );
	//ids
	$out .= row(rightcell(ucfirst(AG_MAIN_OBJECT).' ID #\'s').leftcell($ids));

	$out .= row(cell(smaller(
				   oline("Record Added By " . staff_link($client["added_by"]) . ", at " . datetimeof($client["added_at"],"US"))
				   . "Record Last Edited By " . staff_link($client["changed_by"]) . ", at " . datetimeof($client["changed_at"],"US"))
			 ,'colspan="2" class="systemField"'));


	// End of Client Summary Table
	$out .= tableend() . oline();

	$summary_hide_button = Java_Engine::hide_show_button('ClientSummary',false);

	//loading graphic
 	$out .= div(html_image($GLOBALS['AG_IMAGES']['page_loading_animation']) . ' loading '.$def['singular'].' records...','page_loading',' style="border: solid 1px black; padding: 5px; width: 17em; white-space: nowrap; background-color: #efefef; position: fixed; bottom: 2px; right: 2px; display: none;"');
 	$out .= Java_Engine::get_js('document.getElementById(\'page_loading\').style.display="block";');

	out(span($summary_hide_button. '&nbsp;'.section_title(ucfirst(AG_MAIN_OBJECT).' Summary'),' class="childListTitle"') );
	out(Java_Engine::hide_show_content($out,'ClientSummary',false));

	// elevated concern detail
	if ($elevated_concern) {
		out(client_elevated_concern_f($id));
	}

	//Engine-handled child records
	$display_client = get_object_display_settings(AG_MAIN_OBJECT_DB);
	$out = $expanded = array();
	$any_expanded = false;
	foreach ($def['child_records'] as $object => $grouping) {
		$expanded[$grouping] = orr($expanded[$grouping],AG_LIST_GROUPING_EXPANDED);
 		if ($display_client[$object]['show']) {
			$js_hide = !$display_client[$object]['js_show'];
			$page    = $_SERVER['PHP_SELF'].'?id='.$GLOBALS['ID'];
			$t_out   = child_list($object,$client[$ID_FIELD],$page,'','',$js_hide);
			$expanded[$grouping] = !$js_hide ? true : $expanded[$grouping];
			$out[$grouping] .= $t_out;
 		}
		if ($expanded[$grouping]) { $any_expanded = true; }
	}
	

	//  Don't show groupings if only 1
	$only_1 = count(array_keys($expanded)) < 2;
	$group_ids = $order = client_child_record_sort($out);
	//javascript expand/collapse all links
	$hide_style = ' style="display: none;"';
	if ( !$only_1) {
		$js_buttons = '';
		$js_buttons .= span(js_link('Expand All','multiShow(\''.implode('ChildList\',\'',$group_ids).'ChildList\'); showElement(\'clientHideAllLink\'); hideElement(\'clientExpandAllLink\')',' class="fancyLink"'),' id="clientExpandAllLink"'.(AG_LIST_GROUPING_EXPANDED ? $hide_style : ''));
		$js_buttons .= span(js_link('Collapse All','multiHide(\''.implode('ChildList\',\'',$group_ids).'ChildList\'); showElement(\'clientExpandAllLink\'); hideElement(\'clientHideAllLink\')',' class="fancyLink"'),' id="clientHideAllLink"'.(AG_LIST_GROUPING_EXPANDED ? '' : $hide_style));
		$js_buttons.= ' | ';
	}

	$s_button = Java_Engine::toggle_tag_display('div','show empty records','childListNullData','block',true);
	$h_button = Java_Engine::toggle_tag_display('div','hide empty records','childListNullData','block',true);
 	$js_buttons .= Java_Engine::hide_show_buttons($s_button,$h_button,true,'inline',AG_LIST_EMPTY_HIDE);
	out(div($js_buttons,'childListJSButtons',' style="display: none;"')); //hide js control until page has loaded
	//output child records
	foreach ($order as $grouping) {
		$content = $out[$grouping];
		$hide_button = Java_Engine::hide_show_button($grouping.'ChildList',!$only_1 and (!$expanded[$grouping]),'','','now');
 		out(html_heading_2($hide_button . blue(ucfirst($grouping.' Records'))) . 
		    Java_Engine::hide_show_content($content,$grouping.'ChildList',!($only_1 or $expanded[$grouping])));
		}
	//page finished loading, hide loading graphic
 	out(Java_Engine::get_js('document.getElementById(\'page_loading\').style.display="none";'
					.'document.getElementById(\'childListJSButtons\').style.display="";')); //only display the buttons after page has loaded
}

function client_child_record_sort($a)
{
	//returns an ordered array based on project and program
	global $UID;

	static $proj, $prog;
	if (!$proj) { $proj = staff_project($UID); }
	if (!$prog) { $prog = staff_program($UID); }

	$new = array();

	foreach ($a as $group => $content) {
		if ($group == 'general') {
			//do nothing
		} elseif (strtolower($group) == strtolower($proj)) {
			$project_match = $group;
		} elseif (strtolower($group) == strtolower($prog)) {
			$program_match = $group;
		} else {
			array_push($new,$group);
		}
	}
	if ($program_match) {
		array_unshift($new,$program_match);
	}
	if ($project_match) {
		array_unshift($new,$project_match);
	}
	array_unshift($new,'general');
	return $new;
}

function client_display( $idnum )
{
	$rec = client_get( $idnum );
	client_show( $rec );
}

function client_name($idnum,$max_length=0,$text_only=false)
{
	if (is_array($idnum) )
	{
		$q=$idnum;
	}
	elseif (! is_numeric( $idnum ))
	{
		return $idnum;
	}
	else
	{
		$q=client_get($idnum);
		if (! ($q and (sql_num_rows($q)<2)) )
		{
			log_error("client_name: lookup failed for $idnum.");
		}
		else
		{
			$q=sql_fetch_assoc($q);
		}
	}
	if ($q)
  	{
		$name = trim($q["name_full"]);
		$alias = trim($q["name_alias"]);
		if ($text_only) {
			$full = $name.($alias ? ' ( aka '.$alias.')' : '');
			return $max_length ? substr($full,0,$max_length) : $full;
		}
		if (!$max_length)
		{
		}
		elseif (($max_length > 0)  && (strlen($name) > ($max_length-5)))
		{
			$name=substr($name,0,$max_length);
			$alias="";
		}
		else
		{
			$alias = substr($alias,0,$max_length - 5 - strlen($name));
		}
		$name .=  $alias ? smaller(" (" . red("aka ") . $alias . ")") : "";
	}
	else
	{
		$name="ID # $idnum (not found)";
	}
	return $GLOBALS['AG_DEMO_MODE'] ? preg_replace('/[a-z]/','x',preg_replace('/[A-Z]/','X',trim($name))) : trim($name);
}

function show_client_heads( $clients , $select_to_url = "" , $allow_other="N" )
{
	//    global $client_page;
	$def = get_def(AG_MAIN_OBJECT_DB);

	$result="";
	if ( ($allow_other=="Y") && $select_to_url )
	{

		$result .= formto($select_to_url)
			. formvartext("client_select")
			. button("Enter Unregistered ".ucfirst(AG_MAIN_OBJECT))
			. formend();
	}
	if (sql_num_rows($clients)>0)
	{
		$result .= tablestart("","border=1")
			. row(boldcell("#")
				. boldcell(oline(ucfirst(AG_MAIN_OBJECT)." / ID # /") 
					     . oline("Overnight Eligibility | Assessed Score") 
					     . "Housing Status")
 				. boldcell("Last Entry")
				. boldcell("Bar Status") 
				.  boldcell("Gender / Ethnicity /<br />Date of Birth / ssn") 
				. boldcell("Picture"));
	}
	if ( ereg("\?",$select_to_url) )// figure out whether to add vars to
	{			   // url with ? or &
		$select_char = "&";
	}
	else
	{
		$select_char = "?";
	}

	for ($i=0; $i<sql_num_rows($clients); $i++)
	{
		$info = sql_fetch_assoc($clients);
		//check if deceased
		$deceased = client_death_f($info[AG_MAIN_OBJECT_DB.'_id'],$death_date,true);
		$opts = $deceased ? ' style="background-color: #efefef;"' : ''; //this could be a class for more dramatic control over row

		if ($select_to_url) {
			global $AG_QUICK_SEARCH_CONTROL_ARRAY;
			$engine_control_variable_string = '&control[object]='.$AG_QUICK_SEARCH_CONTROL_ARRAY['object']
				. '&control[action]='.$AG_QUICK_SEARCH_CONTROL_ARRAY['action']
				. '&control[id]='.$AG_QUICK_SEARCH_CONTROL_ARRAY['id'];
		}


        	$result .= row(
				   cell( ( $select_to_url ?
					     hlink( $select_to_url 
							. $select_char
							. "client_select=" 
							. $info[AG_MAIN_OBJECT_DB."_id"]
							. $engine_control_variable_string,"Select", "Button" )
					     : ($i+1)
					     ))
				   .  cell( oline(client_link($info[AG_MAIN_OBJECT_DB."_id"])
						. smaller(" (" . $info[AG_MAIN_OBJECT_DB."_id"] . ")")
						. ($info["clinical_id"] ? smaller(" (clinical id " . $info["clinical_id"] . ")",2) : ""))
						. ($deceased ? oline($deceased) : '')
						. smaller(client_staff_assignments_f($info[AG_MAIN_OBJECT_DB."_id"]))
//						. smaller(priority_status_f($info[AG_MAIN_OBJECT_DB.'_id'],"")) . " | " . smaller(" assess: ") . bigger(assessment_f($info,"tiny"))
						. smaller(housing_status_f($info[AG_MAIN_OBJECT_DB."_id"]))
						)
				   //            . cell( $info["junk_LastGateDate"] )
 				   . cell( last_entry_f($info[AG_MAIN_OBJECT_DB."_id"]))
				   . cell(bar_status_f($info,'',$is_provisional ))
				   . cell( smaller(oline(value_generic($info['gender_code'],$def,'gender_code','list'))
		   			. oline(multi_objects_f( 
								 get_generic(client_filter($info[AG_MAIN_OBJECT_DB.'_id']),'','','ethnicity')
								 ,'ethnicity','ethnicity_code'))
					 . oline(dateof($info["dob"]) . green(' | ') . $info['ssn'])))
				   . cell( client_photo( $info[AG_MAIN_OBJECT_DB."_id"], 0.5 )), $opts);
	}
	$result .= tableend();
	return $result;
}

function get_clients( $filter, $order="name_full" )
{
	global $client_select_sql;

	$fixed_sql = trim($client_select_sql);

	return agency_query($fixed_sql,$filter,$order);
}

function multi_objects_f( $recs, $object, $field, $sep=", " )
{
	// Returns formatted object info, passed array of records (from get_generic.  Genericized from disabilities_f())
	$def = get_def($object);
	$c_def = get_def(AG_MAIN_OBJECT_DB);
	$output = array();
	$count = sql_num_rows( $recs );
	if ($count == 0)
	{
		return (smaller("(no {$def['plural']})"));
	}
	else
	{
		while ($y=sql_fetch_assoc($recs)) {
			$link_text= value_generic($y[$field],$def,$field,'list');
			$add_on = in_array($y[$field],$def['fields'][$field]['require_comment_codes']) 
				? smaller(' ('.$y['comment'].')',2)
				: '';
			array_push($output,elink($object,$y[$def['id_field']],$link_text).$add_on);
		}
		$output = implode($sep,$output);
	}
	return $output;
}

function is_male( $client ) // client rec or id
{
	return is_gender($client,"M");
}

function is_female( $client) // client rec or id
{
	return is_gender($client,"F");
}

function is_gender( $client, $gender ) // client rec or id, "M" or "F"
{
	/*
Agency=> select * from l_gender;
 gender_code |               description
-------------+------------------------------------------
 1           | Female
 2           | Male
 3           | Transgender (Female to Male)
 4           | Transgender (Male to Female)
 5           | Transgender (Direction Unkown)
 6           | Transgender (F to M), CONSIDER FEMALE
 7           | Transgender (M to F), CONSIDER MALE
	*/
	if ( is_numeric( $client ) ) // if we get an ID #
	{
		$client = sql_fetch_assoc(client_get( $client ));
	}
	$code=$client["gender_code"];
	if (($gender == "F"))
	{
 		return (($code==1) || ($code==4) || ($code==6));
	}
	if (($gender == "M"))
	{
 		return (($code==2) || ($code==3) || ($code==7));
	}
	return false;
}

function volunteer_status_f($client)
{
	// bar_status seems hopelessly overloaded as a function, and not clear.
	// I'm pulling BRC w/in last 30-days out into it's own logice,
	// so that it will be easier to simplify bar_status at some point.
	//    $barred = bar_status($client["client_id"],"SHELTER","CURRENT_TIMESTAMP::date-30","CURRENT_TIMESTAMP::date","BRC");
	if (is_array($client))
	{
		$client = $client[AG_MAIN_OBJECT_DB."_id"];
	}
	$barred = bar_status($client[AG_MAIN_OBJECT_DB."_id"],"SHELTER"); // is currently barred?
	if (!$barred) // if no, check for BRC ended in last 30 days
	{
		$filt = client_filter($client);
		$filt['>:bar_date_end-bar_date']='3';
		$filt['FIELD>:bar_date_end']='CURRENT_DATE - 30';
		$bars=get_generic($filt,'','','bar');
		$barred=(sql_num_rows($bars) > 0);
	}
	return smaller( ($barred ? "Not " : "") . "Eligible to Volunteer");
}

// returns bool; priority_type is the type required to return true
// in the future we'll want to add location, but for right now, a location is
// just another priority type (stored the same way in the client table
function has_priority($clientid, $ptype="ANY")
{
	/* ptype could be 
	 *  OPEN (priority_open == 1)
	 *  CURRENT (priority_date_start exists and is <= today's date and
	 *  priority_date_end is >= today's date or null) 
	 *  ANY (match OPEN or CURRENT)
	 *  KSH (priority_ksh == 1)
	 *  to return priority = true
	 */
	/* 9/11/03--changed priority fields to booleans
		updating code to reflect/handle this
	*/
	
	global $bed_kerner;
	$priority = false;
	$today = dateof("now","SQL");       
	
	if (is_numeric($clientid)) {
		$id = $clientid;
	} elseif (is_array($clientid)) {
		$id = $clientid[AG_MAIN_OBJECT_DB.'_id'];
	} else {
		return false;
	}

	//get shelter registration
	$pdef = get_def('shelter_reg');
	$pfilt = array(AG_MAIN_OBJECT_DB.'_id'=>$id,
			   array('NULL:shelter_reg_date_end'=>true,
				   '>=:shelter_reg_date_end'=>dateof('now','SQL')));
	$res = agency_query($pdef['sel_sql'],$pfilt);
	if (sql_num_rows($res)<1) {
		return false;
	}
	$reg=sql_fetch_assoc($res);

	// verify priority by type
	$priority=false;
	switch (strtoupper($ptype))
	{
	case "ANY" :
	case  "CURRENT" :
// 		$prstart = $reg['shelter_reg_date'];
// 		$prend = $reg['shelter_reg_date_end'];
// 		if (( ($prstart<=$today) && (! empty($prstart)) ) 
// 		    && (($prend>=$today) || ($prend=="")))
// 		{
		$priority = $reg;
// 		}
		if ($ptype == "CURRENT" || $priority)
		{
			break;  //keep going if ANY
		}
	case "KSH" :
		if (sql_true($reg['priority_ksh']))
		{
			$priority = $reg;
			break;
		}
		// check for 60+ or female
	case "ANY" :
	case "OPEN" :
// 		if ((ageof($client["dob"]) >= 60) || is_female($client)
// 		    || sql_true($client["priority_open"]))
// 		{
		if (sql_true($reg['priority_elderly']) || sql_true($reg['priority_female'])) {
			$priority = $reg;
		}
		break;
	default : 
		$ptype = strtolower($ptype);
		if (sql_true($reg['priority_'.$ptype]))
		{
			$priority = $reg;
		}
		break;
	}
	return $priority;
}

function priority_status_f( $client_id, $sep="<br>", $format="" )
{
	// return a formatted string displaying a client's priority status.
	// for terse format, return either "Open" or Dates
	
	$add_link = link_engine(array('object'=>'shelter_reg',
						'action'=>'add',
						'rec_init'=>array(AG_MAIN_OBJECT_DB.'_id'=>(is_array($client_id) 
											  ? $client_id[AG_MAIN_OBJECT_DB.'_id']
											  : $client_id)
									)),
					smaller('Add a shelter registration'));
	if (is_array($client_id)) {
		$priority_rec = $client_id; //save on queries
	} else {
		//get priority record
		$pdef = get_def('shelter_reg');
		$pfilt = array(AG_MAIN_OBJECT_DB.'_id'=>$client_id);
// 				   array('NULL:shelter_reg_date_end'=>true,
// 					   '>=:shelter_reg_date_end'=>dateof('now','SQL')));
		$res = agency_query($pdef['sel_sql'],$pfilt,'shelter_reg_date_end DESC',1);
		if (sql_num_rows($res)<1) {
			return smaller('No Shelter Registrations ').$add_link;
		}
		$priority_rec=sql_fetch_assoc($res);
	}
	$enddate = $priority_rec['shelter_reg_date_end'];
	if (!be_null($enddate) && (dateof($enddate,'SQL') < dateof('now','SQL') ) ) {	//expired registrations here
		$expired = true;
	}
	// Open Status?
	if (be_null($enddate) || sql_true($priority_rec['priority_elderly'])) {
	// 	if (sql_true($priority_rec['priority_open']) || (ageof($client_rec["dob"]) >= 60)) {
		$str= bold(red("OPEN"));
		if ($format<>"terse" && !$expired)
		{
			$str=bigger($str);
		}
	}
	
	if ($format <> "terse") // skip individual priorities
	{
		// Individual Priorities:
		foreach (array("cd","dd","disabled","med","mh") as $x)
		{
			$y="priority_" . $x;
			$str .= sql_true($priority_rec[$y]) ?
				' ' . blue($x) : null;
		}
		// Female?
		// 	if (is_female($client_rec)) {
		if (sql_true($priority_rec['priority_female'])) {
			$str .= blue(" Fem");
		}
		// 60+?
		// 	if (ageof($client_rec["dob"]) >= 60) {
		if (sql_true($priority_rec['priority_elderly'])) {
			$str .= blue(" 60+");
		}
		// Kerner-Scott Women's Shelter
		$str .= sql_true($priority_rec["priority_ksh"]) ?
			bigger(bold(red(" K/S")))
			: "";
		// Queen Anne Shelter
		$str .= sql_true($priority_rec["priority_queen_anne"]) ?
			bigger(bold(red(" QA")))
			: "";
	}
	// Date limited eligibility
	// imported data from SHAMIS is "0000-00-00" not blank
	// if terse, only display dates if status <> OPEN (whence $str will be null)
	if (($format=="terse") && (!$str))
	{
		if ($enddate>=dateof("now","SQL")) // Priority in effect
		{
			$str="-->" . dateof($enddate);
		}
	}elseif ( $format<>"short" && ($format<>"terse"))
	{
		$str .= $sep . smaller("(" . blue(dateof($priority_rec["shelter_reg_date"]) . "-->"
							    . dateof($enddate) ) . ")");
	}
	$str = elink('shelter_reg',$priority_rec['shelter_reg_id'],$str);
	if ($format<>"short" && $format<>"terse")
	{
		// comment:
		$str .= $priority_rec["comments"] ?
			$sep . "(" . italic(blue($priority_rec["comments"])) . ")"
			: "";
		// Staff ID?
		$str .= !$expired ? smaller(" (set by " . staff_link( $priority_rec["changed_by"] ) . ")") : null;
	}

	if ($expired) {
		$str = smaller('Expired: '.$str.' ').$add_link;
	}
	// 	echo " short client heads.  Open = " . $client_rec["priority_open"];
	// Return Here:
	return $str;
}

function sex_offender_reg_f( $id )
{
	$def = get_def('sex_offender_reg');

	$sql = $def['sel_sql'];
	$filter = client_filter( $id );
	$res = agency_query($sql,$filter);
	if (sql_num_rows($res) < 1 ) {
		$control = array('object'=>'sex_offender_reg',
				     'action'=>'add',
				     'rec_init'=>array(AG_MAIN_OBJECT_DB.'_id'=>$id));
		$out = smaller('Not on File');
	} else {
		$rec = sql_fetch_assoc($res);
		$control = array('object'=>'sex_offender_reg',
				     'id'=>$rec['sex_offender_reg_id']);
		if (sql_true($rec['has_registration_requirement'])) {
			$def['fields']['reoffense_risk_code']['show_lookup_code_view'] = 'DESCRIPTION';
			$out = red(bold(value_generic($rec['reoffense_risk_code'],$def,'reoffense_risk_code','view')));
		} else {
			$out = smaller('On file, none ' . dateof($rec['added_at']));
		}
	}
	$out = 'SO Reg Req: ' . link_engine($control,$out);
	return $out;
}

function client_locker_priority($id)
{
	if (!$id) {
		return false;
	}
	$priority = (($reg = has_priority($id))  
			 and ((days_interval($reg['shelter_reg_date'],$reg['shelter_reg_date_end']) >= 14)  
				or be_null($reg['shelter_reg_date_end'])));
	/* bug 27453 */
	return $priority and ((assessment_of($id) >= 0) or is_female($id));
}

function client_locker_assignment_f($id)
{
	if (!$def = get_def('client_locker_assignment')) {
		return '';
	}

	$res = get_generic(client_filter($id),'','','client_locker_assignment_current');
	if (sql_num_rows($res) < 1) {
		$get_locker = client_locker_priority($id);
		$label = smaller('Add Client Locker Assignment',2);
		return $get_locker
			? link_engine(array('object'=>'client_locker_assignment','action'=>'add','rec_init'=>client_filter($id)),
					 $label)
			: dead_link($label);
	}

	$a = sql_fetch_assoc($res);

	$link = elink('client_locker_assignment',$a['client_locker_assignment_id'],'Locker #'.$a['client_locker_code']);

	$end_date = $a['client_locker_assignment_date_end'];
	$renewal = $a['days_from_last_renewal'] > 30
		? red(bold('client must renew locker '))
		: '';

	$expires = days_interval($end_date,today()) <= 5
		? red(bold(bigger(dateof($end_date))))
		: dateof($end_date);

	if (has_perm($def['perm_view'])) {
		$combination = Java_Engine::toggle_id_display(smaller('Combination (+/-):'),'clientLockerCombination')
			.span(' '.bold($a['combination']),' id="clientLockerCombination" style="display: none;"').', ';
	}

	return oline($renewal . $link) 
		. indent($combination.'expires: '.$expires);
}

function lang_f($client_rec)
{
	$def = get_def(AG_MAIN_OBJECT_DB);

	$language = value_generic(orr($client_rec['language_code'],'0'),$def,'language_code','view');

	switch($client_rec['needs_interpreter_code']) {
	case 'YES':
		$barrier = red(bold('Needs interpreter: '));
		break;
	case 'UNKNOWN':
		$barrier = smaller(red('(Unknown Interpreter Status) '));
		break;
	case 'NO':
	default:
		$barrier = smaller('(No Barrier) ');
		$language = smaller($language);

	}
	return $barrier.$language;
}

function income_f($client_id, &$has_inc) {

	$def = get_def('income');
	$has_inc = false;

	//current income
	$filter=array(AG_MAIN_OBJECT_DB.'_id'=>$client_id,
			  array('NULL:income_date_end'=>'',
				  'FIELD>=:income_date_end'=>'CURRENT_DATE'));
	$res = get_generic($filter,'income_date DESC',1,$def);
	if (sql_num_rows($res) > 0) {
		$has_inc = true;
		$income=sql_fetch_assoc($res);
		$annual_income = $income['annual_income'];

		$output .= oline(blue(currency_of($annual_income/12))
				    . smaller(black(' ('.value_generic($income['income_primary_code'],$def,'income_primary_code','list')
							  . ( ($income['income_secondary_code']!=='NONE' && !be_null($income['income_secondary_code'])) 
								? ', '.value_generic($income['income_secondary_code'],$def,'income_secondary_code','list')
								: '' )
							  . ( $income['monthly_interest_income']>0 ? ', Interest': '')
							  . ')')
						  ));
		$other1=$income['other_assistance_1_code'];
		$other2=$income['other_assistance_2_code'];
		$output .= (!be_null($other1)||!be_null($$other2))
			? oline(smaller(blue( ($other1 ? value_generic($other1,$def,'other_assistance_1_code','list'):'')
						    . ($other1 && $other2 ? ', ':'')
						    . ( ($other2 && ($other2 !== $other1))
							  ? value_generic($other2,$def,'other_assistance_2_code','list') : ''))
					    ))
			: '';

		$output = alt(link_engine(array('object'=>'income','action'=>'view','id'=>$income['income_id']),$output),
			     'Click to view income record');
	}

	$filter=client_filter($client_id);

	//any income records
	if (!$has_inc) {
		$res=agency_query($def['sel_sql'],$filter,'income_date',1);
		if (sql_num_rows($res) > 0) {
			$has_inc = true;
			$output = bold(smaller('No current income records.'));
		} else {
			$has_inc = false;
			$output = smaller('No income records.');
		}
	}

	return $output;
}

function client_note_f ($id)
{
	$def = get_def('client_note');
	$filter = client_filter($id);
// 	$filter['is_front_page'] = sql_true();
	$res = get_generic($filter,' added_at DESC','','client_note');
	if (sql_num_rows($res) < 1) {
		return html_no_print(link_engine(array('object'=>'client_note','action'=>'add','rec_init'=>client_filter($id)),'Add a Note'));

	}
 	$out = html_no_print(jump_to_object_link('client_note'));
	while ($a = sql_fetch_assoc($res)) {
		$a = sql_to_php_generic($a,$def); //convert sql arrays to php arrays
		if (sql_true($a['is_front_page'])) {
			$flag = '';
			if ($flag_entries = $a['flag_entry_codes']) {
				$flag_title = array();
				foreach ($flag_entries as $location) {
					$flag_title[] = sql_lookup_description($location,'l_scanner_location');
				}
				$flag_title = 'this note is displayed on gatekeeping ('.implode(' &amp; ',$flag_title).') when '.AG_MAIN_OBJECT.'\'s card is swiped.';
				$flag = span('flagged for entry',' title="'.$flag_title.'" class="clientCommentFlag"');
			}

			$link = link_engine(array('object'=>'client_note','id'=>$a['client_note_id'],'action'=>'edit'),'Edit/Remove');
			$author = 'Author: '.staff_name($a['added_by']);
			$author .= $a['added_by'] !== $a['changed_by'] ? ' - Update: '.staff_name($a['changed_by']) : '';
			$out .= row(cell(value_generic($a['added_at'],$def,'added_at','list'),' valign="top" class="clientCommentDate"')
					. cell($flag.alt(oline($link).value_generic($a['note'],$def,'note','view'),$author),' class="clientCommentNote"'));
		}
	}
	return table($out,'',' bgcolor="" class="clientComment"');
}

function client_search($allow_other="N",$auto_forward=true)
{

	/*
	 * This page performs a client search based on global variables
	 *
	 * The fields that can be searched on are defined in the $fields array.
	 * In order for a field to be searched, the field name must be included
	 * in this array.  Additionally, the variables $fieldnameText and
	 * $fieldnameType (i.e., for "Gender" field "GenderText" and "GenderType"
	 * must be passed.  The Text is the value to search for, and the Type
	 * is the type of comparison test being made (see the switch on "type"
	 * in build_where_string for the possible types.
	 *
	 * the following additional variables are used:
	 * $order : sort order field
	 * $limit : limit number of queries
	 * $revorder : boolean to reverse sort order
	 * $showsql : display sql query
	 *
	 * $select_to_url : if this is set, it will enable the selection
	 * of displayed clients, with the results being passed to this url.
	 *
	 * $QuickSearch : if this value is set, then it is evaluated numeric or text
	 * 		if numeric, it does a lookup against client_id
	 *		otherwise, it does a substring search on fullname
	 */

	global $order, $limit, $revorder, $showsql, $select_to_url, 
		$client_select_sql, $client_search_fields,$QuickSearch;

	$QuickSearch = trim(orr($QuickSearch,$_REQUEST['QuickSearch']));
	$fields      = $client_search_fields;

	if (isset($QuickSearch)) {

		/*
		 * Prior to doing a standard "name" or text search, check to see
		 * if the QS is for a special type or shortcut. If adding or altering
		 * these, keep in mind that the order is very important. For example, the
		 * KC, clinical, and authorization searches must be checked prior to the
		 * housing unit search, since the pattern is the same.
		 */

		if ($QuickSearch == '') {

			return oline('You must specify a search criteria for a Quick Search');

		} elseif ( is_numeric( $QuickSearch )  && ($QuickSearch <= AG_POSTGRESQL_MAX_INT)) {

			/*
			 * Client ID search
			 */

			global $client_idType, $client_idText;
			$client_idType = "equal";
			$jump = $client_idText = $QuickSearch;

			// check for unduplicated clients
			$client = sql_fetch_assoc(client_get($client_idText));
			if ($client and $client['client_id'] != $client_idText) {

				$client_idText = $client[AG_MAIN_OBJECT_DB . '_id'];

			}

		} elseif( $x = dateof($QuickSearch,'SQL') ) {

			/*
			 * DOB search
			 */

			global $dobType, $dobText;
			$dobType = 'equal';
			$dobText = $x;

		} elseif( $x = ssn_of($QuickSearch) ) {

			/*
			 * SSN search
			 */

			global $ssnType, $ssnText;
			$ssnType = "equal";
			$ssnText = $x;

		} elseif (preg_match('/^c(ase)?[: ]*([0-9]{1,6})/i',$QuickSearch,$matches)) {

			/*
			 * Clinical ID search
			 */
			global $clinical_idType, $clinical_idText;

			$clinical_idType = 'equal';
			$clinical_idText = $matches[2];

			// do the lookup here on this page
			// so we can jump directly to the id page
			$client = sql_fetch_assoc(get_clients(array('clinical_id'=>$clinical_idText)));
			$jump   = $client[AG_MAIN_OBJECT_DB . '_id'];

		} elseif (preg_match('/^kc(id)?[: ]*([0-9]{1,6})/i',$QuickSearch,$matches)) {

			/*
			 * KCID search
			 */

			global $king_cty_idType, $king_cty_idText;
			$king_cty_idType = 'equal';
			$king_cty_idText = $matches[2];

			// do the lookup here on this page
			// so we can jump directly to the id page
			$client = sql_fetch_assoc(get_clients(array('king_cty_id' => $king_cty_idText))); //hack to get around our varchar kcids
			$jump   = $client[AG_MAIN_OBJECT_DB . '_id'];

		} elseif (preg_match('/a(uth)?[: ]*([0-9]{1,7})/i',$QuickSearch,$matches)) {

			/*
			 * KC clinical authorization number search
			 */

			$rec = sql_fetch_assoc(get_generic(array('kc_authorization_id'=>$matches[2]),'','','clinical_reg'));

			if ( $x = $rec[AG_MAIN_OBJECT_DB . '_id'] ) {

				global $client_idType, $client_idText;
				$client_idType = 'equal';
				$jump = $client_idText = $rec[AG_MAIN_OBJECT_DB."_id"];

			}

		} elseif ( $x = unit_no_of($QuickSearch)) {

			/*
			 * Housing Unit search (most recent occupant)
			 */
		
			$rec = sql_fetch_assoc(get_last_residence($x));
			if ( $x = $rec[AG_MAIN_OBJECT_DB . '_id'] ) {

				global $client_idType, $client_idText;
				$client_idType = "equal";
				$jump = $client_idText = $rec[AG_MAIN_OBJECT_DB.'_id'];

			}

		} elseif (preg_match('/^([a-z_]{3,}):([0-9]*)$/i',$QuickSearch,$m)) {

			/*
			 * Engine Object search of the form {obj}:xxxx
			 * where {obj} is the name of the object (or the
			 * first 3 or more characters of the name)
			 */

			if ($found = search_engine_object($m[1],$m[2])) {
				return $found;
			}

		} else {

			global $name_full_aliasText, $name_full_aliasType;
			$name_full_aliasType = "sub";
			$name_full_aliasText = $QuickSearch;
		}

		if ($auto_forward && $jump) { 

			/*
			 * Re-direct to client page if $auto_forward is set and a
			 * match was found in $jump
			 */

			global $client_page;

			/*
			 * This seemingly un-necessary step will
			 * pick up clients who have been unduplicated
			 */
			$client=sql_fetch_assoc(client_get($jump));

			if ($client) {

				header("Location: $client_page?id=" . $client[AG_MAIN_OBJECT_DB.'_id']);
				page_close();
				exit;

			}

		}

	}

	// This ends the Quick Search processing.
	$empty = true;
	foreach ($fields as $x) {

		$type=$x.'Type';
		$text=$x.'Text';
		global $$type, $$text;

		if ( isset( $$type ) || isset( $$text) ) {

			$empty = false;

		}
	}

	if ($empty) {

		return oline('Sorry, no search criteria specified.  Maybe you forget to enter some, or maybe your session timed out.  Either way, go BACK to the previous page and retry your search.');

	} else {

		return search( $fields, $client_select_sql . ' WHERE 1=1 ', AG_MAIN_OBJECT_DB, 'show_client_heads', AG_MAIN_OBJECT, AG_MAIN_OBJECT.'s',$allow_other);

	}

}

function client_death_f ($id,&$deceased_date,$small=false)
{
	$res = get_generic(client_filter($id),'','','client_death');
	if (sql_num_rows($res)<1) {
		return false;
	}
	$rec = sql_fetch_assoc($res);
	$deceased_date = $rec['client_death_date'];
	$app = $rec['client_death_date_accuracy']=='E' ? '' : '(approximately)';
	$age = client_age($id,'NO',$deceased_date);
	$text = red(bold('Died on ' . dateof($deceased_date,'WORDY').$app.' (at '.$age.')'));
	$text = $small ? $text : bigger($text);
	return link_engine(array('object'=>'client_death','id'=>$rec['client_death_id']),$text,'',' class="fancyLink"');
}

function client_age($id,$dob='NO',$date='',$format='year')
{
	/* this function replaces ageof() */

	$date = orr($date,'now');
	if (is_array($id)) {
		$client = $id;
		$id = $client[AG_MAIN_OBJECT_DB.'_id'];
	}

	$age = call_sql_function('client_age',$id,"'".dateof($date,'SQL')."'");
	switch ($format) {
	case 'year':
		preg_match('/^([0-9]*)\syears/i',$age,$m);
		$age = $m[1];
	case 'full':
	default:
	}

	if ($dob=='NO') {
		return $age;
	} elseif (is_array($client)) {
		return dateof($client['dob']) . " (" . blue("age=" . $age . ")");
	} else {
		return 'Error: DOB request made to client_age() without client record ';
	}

}

function client_reg_search()
{
      global $def,$rec,$main_object_reg_search_fields,$engine; 

      foreach ($main_object_reg_search_fields as $x)
	{
		    $$x=$rec[$x];
	}
      $SSN=$ssn=ssn_of($ssn);
      $dob=dateof($dob);
	$filter = build_client_match_filter($name_last,$name_first,$dob,$ssn);
	$client_search_rank = build_client_match_order($name_last,$name_first,$dob,$ssn);
	$obj = AG_MAIN_OBJECT_DB;	
	$obj_label = AG_MAIN_OBJECT;
	$objs = $obj_label . 's';  //FIXME--should reference plural	
	$t_obj = $obj . '_reg_tmp';
	$control=array('object'=>$t_obj,
			   'action'=>'list',
			   'list'=>array('filter'=>$filter,
					     'order'=>array('match'=>true)
					     ));

      $title = "Review existing $objs for a match";
	$out .= oline(smaller('(searching on name:'.bold($name_last.', '.$name_first).', dob: '.bold($dob).', ssn: '.bold($ssn).')'));
      $out.=oline() . oline(red("Review the following $objs to make sure $obj_label is not already registered"));
	//----- Create a copy of def -----//
	$engine[$t_obj]=$engine[$obj];
	$engine[$t_obj]['object']=$t_obj;
	//----- modify client columns -----//
	$engine[$t_obj]['fn']['show_query_row'] = 'show_query_row_generic';
	$engine[$t_obj]['list_fields'] = array('match',$obj.'_id','custom5','ssn','dob','name_last','name_first');

	$engine[$t_obj]['sel_sql'] = 'SELECT '.$client_search_rank.' as match, client_id,dob,ssn,name_last,name_first FROM client';

	//------ Highlight exact matches ------//
	foreach ($main_object_reg_search_fields as $field) {
		$engine[$t_obj]['fields'][$field]['value_format_list'] = '(strtolower($x)==strtolower(\''.$rec[$field].'\')) ? bigger(bold($x)) : $x';
		$REC[$field] = $rec[$field];
	}
	if (!in_array($t_obj,$GLOBALS['AG_ENGINE_TABLES'])) {
		array_push($GLOBALS['AG_ENGINE_TABLES'],$t_obj);
	}

	$out.=call_engine($control,'control_client_reg',$NO_TITLE=true,$NO_MESSAGES=false,$TOT,$PERM);
      $out.=oline() . oline(bigger(link_engine(array('object'=>$obj,
								     'action'=>'add',
								     'rec_init'=>$REC),
							     "If the $obj_label is not already registered, proceed here")));
      
      return $out;
}

function build_client_match_filter($name_last,$name_first,$dob=null,$ssn=null)
{
	//Name stuff
	$meta_first = levenshteinMetaphoneDistance($name_first,'name_first');
	$meta_last =  levenshteinMetaphoneDistance($name_last,'name_last');

	$filter=array(	array(
					array('FIELD<=:'.$meta_first => LEVENSHTEIN_ACCEPT,
						'FIELD<=:'.$meta_last => LEVENSHTEIN_ACCEPT),
					array('ILIKE:name_alias'=>"%$name_last%",
						'ILIKE:name_alias '=>"%$name_first%")));

	if ( ssn_of($ssn) and ssn_of($ssn)!='999-99-9999') { //Don't search on 999-99-9999 or blank

		//SSN variations
		for ($x=0;$x<11;$x++) {
			//find matching ssn's off by one digit
			if ($x==3 || $x==6) continue;
			$ssn_search[] = substr_replace($ssn,'_',$x,1);
		}
		
		$filter[0][] = array(" LIKE : ssn "=>ssn_flip($ssn));
		$filter[0][] = array(" LIKE : ssn "=>ssn_flip($ssn,7));
		$filter[0][] = array(" LIKE : ssn "=>ssn_flip($ssn,5));
		$filter[0][] = array(" LIKE : ssn "=>$ssn_search);
	}

	if (dateof($dob)) { //set DOB
		$filter[0]["dob"]=dateof($dob,"SQL"); 
	} elseif (!is_assoc_array($filter[0])) {
		//must make filter an associative array, or read_filter will barf
		$filter[0]['NULL:'.AG_MAIN_OBJECT_DB.'_id']=true; //or client_id IS NULL -- will never return true
	}

      $filter=array_filter($filter); //remove blanks which crash sql searches
	return $filter;
}

function build_client_match_order($name_last,$name_first,$dob,$ssn)
{
	//db function requires a dob
	if (!dateof($dob)) {
		return '1';
	}
	$dob = dateof($dob,'SQL');
	$name_last = sqlify($name_last);
	$name_first = sqlify($name_first);
	return "rank_client_search_results(name_last,name_first,name_alias,ssn,dob,'{$name_last}','{$name_first}','{$ssn}','{$dob}')";
}

function client_agency_home_links()
{
	/* setup home links */
	$add_client_link = hlink("client_reg.php",'Add '.ucfirst(AG_MAIN_OBJECT));
	$home_links_1 = array(hlink_if('mail_entry.php','Mail Entry',has_perm('admin,mail_entry')), //mail
				    link_entry('Gatekeeping'), //entry
				    $add_client_link //add client
				    );
	$home_links_2 = array(link_feedback(), //feedback
				    link_admin() //AGENCY admin
				    //hlink('menu.php#staff','Staff Menu') //staff
				    );

	return implode(' | ',$home_links_1)
		. ($home_links_2 ? oline() . implode(' | ',$home_links_2) : '');

}

function client_home_sidebar_left()
{
	/* You could customize the client-version sidebar for the home page here */
	return generic_home_sidebar_left();
}

function safe_harbors_consent_f($id)
{
	$def = get_def('safe_harbors_consent');
	$res = get_generic(client_filter($id),'','',$def);

	if (sql_num_rows($res) < 1) {
		return smaller(link_engine(array('object'=>$def['object'],'action'=>'add','rec_init'=>client_filter($id)),
		       red('Record This Person\'s Safe Harbors Preference')),2);
	} else {
		$a = sql_fetch_assoc($res);
		$val = $a['safe_harbors_consent_status_code'];
		$control = array('object'=>'safe_harbors_consent','id'=>$a['safe_harbors_consent_id']);
	}

	switch ($val) {
	case 'REFUSED':
		$value = 'Client has opted out of Safe Harbors';
		break;
	case 'CONSENTED':
		$value = 'Client has consented to Safe Harbors';
		break;
	case 'REFUSED_D':
		$value = 'Client has consented to Safe Harbors with exceptions';
		break;
	default:
		$value = value_generic($val,$def,'safe_harbors_consent_status_code','list');
	}

	return smaller('Safe Harbors Status: '.link_engine($control,alt($value,'Click to change status')),2);
}


function chronic_homeless_status_f($id)
{
	$def = get_def('chronic_homeless_status_asked');
	$res = get_generic(client_filter($id),'','',$def);

	if (sql_num_rows($res) < 1) {
	  return smaller(link_engine(array('object'=>$def['object'],'action'=>'add','rec_init'=>client_filter($id)),
		 red('Record This Person\'s Chronic Homeless Status')),2);
	} else {
		$a = sql_fetch_assoc($res);
		$val = $a['chronic_homeless_status_code'];
		$control = array('object'=>'chronic_homeless_status_asked','id'=>$a['chronic_homeless_status_asked_id']);
	}
			
	$value = value_generic($val,$def,'chronic_homeless_status_code','list');
	
	return smaller('Self-Reported Chronic Homeless Status: '.link_engine($control,alt($value,'Click to change status')),2);
}

function assignments_f($staff_id, $my=false) {
	
	global $colors, $UID, $AG_USER_OPTION;

	$def = get_def('staff_assign');

	//these settings are stored across sessions
	$hide = $AG_USER_OPTION->show_hide('assignments_f');
	$show_hide_link = $AG_USER_OPTION->link_show_hide('assignments_f');

	$res=staff_client_assignments($staff_id);
	$list=array();
	while ($tmp=sql_fetch_assoc($res)) {
		$tmp2=array();
		$tmp2['client_name']           = client_name($tmp[AG_MAIN_OBJECT_DB.'_id']);
		$tmp2[AG_MAIN_OBJECT_DB.'_id'] = $tmp[AG_MAIN_OBJECT_DB.'_id'];
		$tmp2['description']           = $tmp['description'];
		$tmp2['staff_assign_id']       = $tmp['staff_assign_id'];
		$tmp2['type_code']             = $tmp['staff_assign_type_code'];
		array_push($list,$tmp2);
	}
	asort($list);
	if (count($list)>0) {
		$proj = staff_project($staff_id);
		while ($a=array_shift($list)) {
			$client=$a[AG_MAIN_OBJECT_DB.'_id']; //can either be an ID or a name
			$type=$a['description'];
			$id=$a['staff_assign_id'];
			$assigns[$client][$type]=$id;
			$types[$type] = $a['type_code'];
		}
		$cnt = count($assigns);
		
		// moved here so the count would be set, even when hidden (bug 27034.) 
		if (!$hide) {					
			foreach ($assigns as $client => $assign) {
				$t_add_links = $tmp = array();
				
				foreach ($assign as $type=>$id) {
					$formatted = alt(link_engine(array('object'=>'staff_assign','action'=>'view','id'=>$id),black($type),'',' class="fancyLink"'),
							     'Click to view staff assignment');
					array_push($tmp,$formatted);
					$t_type = $types[$type];
					switch ($t_type) {
					case 'CM_MH' :
					case 'CM_MH_PP' :
					case 'CM_MH_PB' :
					case 'MH_VOC' :
						//add DAL link
/*
						$t_add_links[$t_type] = smaller('Add '.link_quick_dal(alt('DAL(s)','Multiple DALs'),
															array('client_id'=>$client,'performed_by'=>$UID)
															,'class="fancyLink"'),2);
*/
						break;
					case 'CM_IR':
/*						$t_add_links[$t_type] = link_multi_add('service_ir',
												   smaller('Add I&R Service(s)',2),
												   array('client_id'=>$client,'service_by'=>$UID),'class="fancyLink"');
*/
						break;
                                        case 'CM_CRP':
/*
                                                $t_add_links[$t_type] = link_multi_add('service_crp',
                                                                                                   smaller('Add CRP Service(s)',2),
                                                                                                   array('client_id'=>$client,'service_by'=>$UID),'class="fancyLink"');
                                                break;
*/						
					default:
					}
				}
				$color = $color=='1' ? '2' : '1';
				
				$add_service_links = !be_null($t_add_links)
					? div(implode(oline(),array_values($t_add_links))
						,'',' class="staffServiceLinks"')
					: '';
				
				
				$out .= row(cell($add_service_links . 
						     alt(smaller(client_link($client,client_name($client,$my ? 25 : 30),''
											   ,' class="fancyLink"'),2),'Click to view client record'),
						     ' style="padding: 0px 4px;"'),'class="generalData'.$color.'"')
					. ($activity ? row(cell(alt(smaller($activity)))) : "")
					. row(cell(smaller(implode(', ',$tmp),3),
						     ' style="padding-left: 1.2em; white-space: nowrap;"'),'class="generalData'.$color.'"');		
			}
			
		}
	}
	else {
		$out = row(cell('No staff assignments',' style="padding: 0px 4px 0px 4px; white-space: nowrap;"'),'class="generalData1"');
	}
	
		
	$width = $hide ? ' boxHeaderEmpty' : '';
	$title=row(cell(($my
			     ? 'My ' . ucwords(AG_MAIN_OBJECT) . ' List ('.orr($cnt,'0').')'
			     : 'Assignments ('.orr($cnt,'0').') for ' . staff_link($staff_id)).$show_hide_link
			    ,' style="color: red; " class="staff boxHeader'.$width.'"'));
	$out = table($title . $out,null,' bgcolor="" cellspacing="0" cellpadding="0" style=" border: 1px solid black;"');
	return $out;
}

function staff_links()
{
	global $UID;

	return client_elevated_concern_list()
//		. recent_staff_dals_missing_pn_f()
		. assignments_f($UID,true)
		. Calendar::my_calendar()
//		. recent_medical_dals_f()
//		. my_staff_dals_missing_pn_f()
		;
}

function nicotine_distribution_f($id)
{
	if (!$def = get_def('nicotine_distribution')) {
		return false;
	}

	$res = get_generic(client_filter($id),'dispensed_on DESC',1,$def);
	if (sql_num_rows($res) < 1) {
		return false;
	}
	$a = sql_fetch_assoc($res);
	$out = 'Received '.$a['nicotine_count'].' patches on '.value_generic($a['dispensed_on'],$def,'dispensed_on','list');

	// pre-populate new record with existing values for certain fields
	// note, this is redundant, as it is also accomplished by setting rec_init_from_previous in the
	// config file. However, this is a good example of how to set it for specific links
	// via the rec_init variable...
	$rec_init = client_filter($id);
	foreach ($a as $key=>$value) {
		if (in_array($key,array('nicotine_dosage_code',
						'nicotine_count',
						'is_client_still_smoking_code',
						'has_client_reduced_smoking_code',
						'expected_dosage_date_end',
						'expected_usage_date_end'))
		    ) {
			$rec_init[$key] = $value;
		}
	}

	$add_link = link_engine(array('object'=>'nicotine_distribution','action'=>'add','rec_init'=>$rec_init),smaller(' Add '.$def['singular'],2));
	return $out . $add_link;
}

function cd_reg_f($id)
{
	if (!$def = get_def('cd_reg')) {
		return '';
	}

	$res = get_generic(client_filter($id),'cd_reg_date DESC',1,$def);
	if (sql_num_rows($res) < 1) {
		$out = smaller('(No '.$def['plural'].')');
	} else {
		$a = sql_fetch_assoc($res);

		if (be_null($a['cd_reg_date_end']) 
		    || (dateof($a['cd_reg_date_end'],'SQL') >= dateof('now','SQL'))) {
			//current registration
			$out = 'Current '.$def['singular'].' beginning '.dateof($a['cd_reg_date']);
		} else {
			$out = red('Expired '.$def['singular'].' '.dateof($a['cd_reg_date'])
				     .' --> '.dateof($a['cd_reg_date_end']));
		}
		$out = link_engine(array('object'=>'cd_reg','id'=>$a[$def['id_field']]),$out);
	}

	return oline($out);
}

//fixme, this function belongs elsewhere
function generate_list_long_service($result,$fields,$max,$position,$total,$control,$def,$control_array_variable,&$rec_num)
{
	if ($control['format'] != 'long') {
		return generate_list_generic($result,$fields,$max,$position,$total,$control,$def,$control_array_variable,&$rec_num);
	}

	$pos=$control['list']['position'];
      $mx=$control['list']['max'];
	$reverse = $control['list']['reverse'];

      while ( $x<$mx and $pos<$total ) {
		$a = sql_fetch_assoc($result,$pos);

		if (!be_null($a['progress_note'])) {
			$out .= div(view_service($a,$def,'list',$control),'',' style="margin: 15px 0px;"'); 
	
			$x++;
		}
		$pos++;
	}

	$links = list_links($max,$position,$total,$control,$control_array_variable);
	return table(row(cell(list_control($control,$def,$control_array_variable,$format='CUSTOM'),'colspan="2"'))
			 . row(leftcell(list_total_records_text($control,$position,$total,$max,$def),'class="listHeader"')
				 .rightcell($links),'class="listHeader"')
			 . row(cell($out,'colspan="2"'))
			 . row(rightcell($links,'colspan="2"'),'class="listHeader"'),'','class="" cellpadding="0" cellspacing="0"');
			 
}

function view_service($rec,$def,$action,$control='',$control_array_variable='control')
{
	if ($control['format'] != 'long') {
		return view_generic($rec,$def,$action,$control,$control_array_variable);
	}

	foreach ($rec as $key => $value) {
		$x = $value;
		$rec[$key] = $def['fields'][$key]
			? eval('return '. $def['fields'][$key]['value_'.$action].';')
			: $value;
	}

	$summary = row(cell(value_generic($rec['service_date'],$def,'service_date',$action)) 
			    . cell(value_generic($rec['service_code'],$def,'service_code',$action))
			    . cell(value_generic($rec['service_minutes'],$def,'service_minutes',$action))
			    . cell(value_generic($rec['contact_type_code'],$def,'contact_type_code',$action)) 
			    );

	if (be_null($rec['progress_note'])) { //progress note exists in other record
		
	} elseif ( ($other_res = $def['fn']['get'](array('service_progress_note_id'=>$rec[$def['id_field']]),$def['id_field'],'',$def))
		     && (sql_num_rows($other_res) > 0) ) { //referencing records
		while ($a = sql_fetch_assoc($other_res)) {
			$summary .= row(cell(value_generic($a['service_date'],$def,'service_date',$action)) 
					    . cell(link_engine(array('object'=>$def['object'],'id'=>$a[$def['id_field']],'format'=>'data'),
								     value_generic($a['service_code'],$def,'service_code',$action)))
					    . cell(value_generic($a['service_minutes'],$def,'service_minutes',$action)) 
					    . cell(value_generic($a['contact_type_code'],$def,'contact_type_code',$action)) 
					    );
		}
		
	} else { //no referencing records

	}

	$total_rows = ($other_res ? sql_num_rows($other_res) : 0) +2;

	$filter=orr($control['list']['filter'],array());
	if (!array_key_exists('client_id',$filter)) { //not client-specific, add name
		$other_stuff = oline($def['singular'].' for '.client_link($rec['client_id']));
	}

	$other_stuff .= oline('Performed By: '.value_generic($rec['service_by'],$def,'service_by',$action))
		. 'Added At: '.value_generic($rec['added_at'],$def,'added_at','view')
		. ($action != 'view' ? html_no_print(oline('',2).link_engine(array('object'=>$def['object'],'id'=>$rec[$def['id_field']]),'View')) : '');



	$summary = row(topcell($other_stuff,'style="white-space: nowrap; border-right: solid 1px black;" rowspan="'.$total_rows.'"')
			   .centercell(bold(label_generic('service_date',$def,$action)))
			   .centercell(bold(label_generic('service_code',$def,$action)))
			   .centercell(bold(label_generic('service_minutes',$def,$action)))
			   .centercell(bold(label_generic('contact_type_code',$def,$action)))
			   ) . $summary;

	$out = table($summary
			 . row(cell(div(value_generic($rec['progress_note'],$def,'progress_note',$action),
					    '',' class="generalTable" style="font-size: 1.2em; padding: 10px; border-top: solid 1px black;"'),' colspan="5"'))
			 ,'','cellspacing="0" cellpadding="0" class="textHeader" style="border-left: solid 1px #efefef; border-top: solid 1px #efefef; border-right: solid 2px #afafaf; border-bottom: solid 2px #afafaf; padding: 2px; font-size: 85%;" width="100%"');
	return $out;
}

function service_progress_note_summary($rec,$def)
{
	if (be_null($rec[$def['id_field']])) {
		return '';
	}
	if (be_null($rec['progress_note'])) {
		return link_engine(array('object'=>$def['object'],'id'=>$rec['service_progress_note_id']),
					 'See '.$def['singular'].' '.$rec['service_progress_note_id']);
	} else {
		return 'Progress Note Exists';
	}
}

function client_elevated_concern_short_f($id)
{

	/*
	 * Everybody has permission for this
	 */

	$res = get_generic(client_filter($id),'','','elevated_concern_current');

	if (sql_num_rows($res) < 1) {
		//not on list
		return false;

	}

	$def = get_def('elevated_concern');
	$f_def = $def['fields'];

	$a = sql_to_php_generic(sql_fetch_assoc($res),$def);

	//a help box
	$help = link_wiki('Elevated_Concern_List',smaller(italic('Tell me more about the Elevated Concern List'),2),'target="_blank"');

	return div(html_heading_3('Client on Elevated Concern '.link_engine(array('object'=>'elevated_concern','action'=>'list'),'List').' starting '.dateof($a['elevated_concern_date'])) 
		     . center($help)
		     . oline('Due to: '.implode(', ',value_generic($a['elevated_concern_reason_codes'],$def,'elevated_concern_reason_codes','list',false)))
		     . smaller(oline('Detail: '.js_chop_and_hide(strip_tags(value_generic($a['elevated_concern_reason_detail'],$def,'elevated_concern_reason_detail','list')),90))
				   . oline('A Note to All Staff: '.js_chop_and_hide(strip_tags(value_generic($a['specific_directions_to_all_staff'],$def,'specific_directions_to_all_staff','list')),90)))
		     ,'','class="client_elevated_concern_short"');

}

function client_elevated_concern_f($id)
{

	/*
	 * Detailed list, only certain people see this
	 */
	if (! (staff_client_project($id) || has_perm('clinical') || has_perm('ecl_admin')   )) {
		return false;
	}

	$res = get_generic(client_filter($id),'','','elevated_concern_current');

	if (sql_num_rows($res) < 1) {
		//not on list
		return false;
	}

	$def = get_def('elevated_concern');
	$f_def = $def['fields'];

	$a = sql_to_php_generic(sql_fetch_assoc($res),$def);

	//a help box
//	$help = link_wiki('Elevated_Concern_List',smaller(italic('Tell me more about the Elevated Concern List'),2),'target="_blank"');

	// staff involved
	$fields = array('elevated_concern_reason_codes','elevated_concern_reason_detail',
			    'next_meeting_date',
			    'point_person','ecl_point_case_manager','other_team_members','custom3',
			    'elevated_concern_plan'
			    );
	$rec = $a; //for eval'd fields
	foreach ($fields as $field) {
		$value = $x = $a[$field];
 		$value = $f_def[$field] 
 			  ? eval('return '. $f_def[$field]['value_list'].';')
 			  : $value;
		$out .= rowrlcell(label_generic($field,$def,'list').': ' , value_generic($value,$def,$field,'list'));
	}


	$extra = html_heading_4('Jail/Hospital/LRA')
		. jail_status_f($id)
		. hospital_status_f($id,$security_override = true) //fake hospital permissions for people with permission to view the ECL detail
		. conditional_release_f($id)
		. html_heading_4('Past Meetings')
		. elevated_concern_past_meetings($a);


	//get engine list of elevated concern notes
	$js_hide = true;
	$control = array('object' => 'elevated_concern_note',
			     'action' => 'list',
			     'anchor' => 'elevated_concern_note',
			     'page' => $_SERVER['PHP_SELF'].'?id='.$id,
			     'list' => array('filter'=>array('client_id'=>$id,
									 '>=:elevated_concern_note_date' => $a['elevated_concern_date'])));
	$elevated_concern_notes = engine_java_wrapper($control,'elevated_concern_note_control',$js_hide,'Notes/Events');

	return div(
		     html_heading_4('Client on Elevated Concern '.link_engine(array('object'=>'elevated_concern','action'=>'list'),'List').' starting '.dateof($a['elevated_concern_date']))
		     . oline($help)

		     . qelink($a,$def,'View Details')

		     . table(row(cell(table($out,'',' class=""')).topcell($extra,' style="padding-left: 35px; "')),'',' class=""')

		     . $elevated_concern_notes
		     ,'',' class="client_elevated_concern"');

}

function client_elevated_concern_list()
{
	/*
	 * Returns a list for a staff home page of every client on the list
	 */

	global $AG_USER_OPTION;

	if (false) { //logic for who sees the list goes here

		return '';

	}

	//these settings are stored across sessions
	$hide = $AG_USER_OPTION->show_hide('client_elevated_concern_list');
	$show_hide_link = $AG_USER_OPTION->link_show_hide('client_elevated_concern_list');
	
	$def = get_def('elevated_concern');

	//everybody sees title
	$link_list = link_engine(array('object' => 'elevated_concern','action' => 'list'),'List');

	$width = $hide ? ' boxHeaderEmpty' : '';
	$help = center(link_wiki('Elevated_Concern_List',smaller(italic('Tell me more'),2),'target="_blank"'));
	$title = row(cell('Elevated Concern '.$link_list . $help . $show_hide_link,' style="color: #000; background-color: #ef4f4f" class="boxHeader'.$width.'"'));

	if (!$hide) {

		// get all clients
		$res = get_generic('','client_name(client_id)','','elevated_concern_current');

		while ($a = sql_to_php_generic(sql_fetch_assoc($res),$def)) {
			$color = $color=='1' ? '2' : '1';
//			$unit = ($tmp=unit_no($a['client_id'])) ? ' ('.$tmp.')':'';
			$out .= row(cell(div(smaller(bold(value_generic($a['elevated_concern_date'],$def,'elevated_concern_date','list')),2),'','style="float: right;"')
					     . smaller(client_link($a['client_id'],client_name($a['client_id'],30).$unit,'',' class="fancyLink"'),2)
					     ,' style="padding: 2px 5px;"'), 'class="generalData'.$color.'"');

		}
	}

	$out = table($title . $out,null,' bgcolor="" cellspacing="0" cellpadding="0" style=" border: 1px solid black;"');

	return $out;

}

function elevated_concern_past_meetings($rec)
{
	$code = '656'; //fixme, change this when appropriate code is available

	$end_date_f = be_null($rec['elevated_concern_date_end']) ? 'CURRENT_TIMESTAMP' : enquote1($rec['elevated_concern_date_end']);

	// get all services or dals with meeting code
	//fixme: this would be better done in a view in the db
	$filter_dal = $filter_service = client_filter($rec['client_id']);
	$filter_dal['dal_code'] = $filter_service['service_code'] = $code;
	$filter_dal['FIELDBETWEEN:dal_date'] = $filter_service['FIELDBETWEEN:service_date'] 
		= enquote1($rec['elevated_concern_date']).' AND '.$end_date_f;
	$filter_service['is_deleted'] = sql_false();

//	$res_dal = get_generic($filter_dal,'dal_date DESC','','dal');
	$res_service = get_generic($filter_service,'service_date DESC','','tbl_service');

	$meetings = array(); //an associative array indexed by date/time of meeting

	//loop through DALs
/*
	$dal_def = get_def('dal');
	while ($a = sql_fetch_assoc($res_dal)) {

		$meetings[$a['dal_date']] = qelink($a,$dal_def,smaller(dateof($a['dal_date'])));

	}
*/
	while ($a = sql_fetch_assoc($res_service)) {

		$type = strtolower($a['service_project_code']);
		$def = get_def('service_'.$type);
		$meetings[$a['service_date']] = qelink($a,$def,smaller(dateof($a['service_date'])));

	}

	ksort($meetings);

	return implode(oline(),array_reverse($meetings));
}

function elevated_concern_note_text($note_text,$rec)
{
	/*
	 * custom formatting of note_text field for different note/event types
	 */

	$def = get_def('elevated_concern_note');

	//webify, since is_html won't webify this field
	$note_text = webify($note_text);

	//determine type of note
	$id = $rec[$def['id_field']];
	list($id,$object) = get_table_switch_object_id($id,$def);

	switch ($object) {
	case 'log' :
		$note_text = html_heading_4($rec['additional_information']).$note_text;
		break;
	default:
	}

	return $note_text;
}

function elevated_concern_all_team_members($rec)
{

	$def = get_def('elevated_concern');
	$staff_fields = array('point_person','ecl_point_case_manager','other_team_members');

	foreach ($staff_fields as $key) {

		$out .= rowrlcell(smaller(label_generic($key,$def,'list'),2),
					smaller(value_generic($rec[$key],$def,$key,'list'),2));

	}

	return table($out,'',' class="" style="white-space: nowrap;"');

}

function elevated_concern_additional_team_members($rec)
{
	$def = get_def('elevated_concern');
	$staff_fields = array('point_person','ecl_point_case_manager','other_team_members');

	$out = $staffs = array();

	foreach ($staff_fields as $key) {

		if (is_array($rec[$key])) {
			$staffs = array_merge($staffs,$rec[$key]);
		} else {
			array_push($staffs,$rec[$key]);
		}
	}

	//additional assignments
	$staff_assigns = client_staff_assignments($rec[AG_MAIN_OBJECT.'_id']);
	
	while ($a = sql_fetch_assoc($staff_assigns)) {

		if (!in_array($a['staff_id_name'],$staffs)) {

 			$out[] = alt(smaller(staff_link($a['staff_id_name']),2),$a['description']);

		}

	}

	return implode(oline(),$out);

}


?>
