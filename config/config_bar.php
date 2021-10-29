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

$gbar = 'Reminder:  Graduated Bar';
$abrc = 'Automatic BRC';
$engine["bar"]=array(
	"title" => '(be_null($rec["client_id"]))
	    ? ucwords($action)."ing Bar for non-client ".$rec["non_client_name_last"].", ".$rec["non_client_name_first"]
          : ucwords($action) . "ing Bar for " . client_link($rec["client_id"])',
	'title_list' => 'ucwords($action) . "ing Bar Records for " . client_link($rec["client_id"])',
 	'subtitle_eval_code' => "oline(smaller(help('BarCodes','','What do the bar codes mean?','',false,true)))",
	'list_fields'=>array('bar_date','bar_date_end','barred_from_summary','bar_type','flags','barred_by','comments'),
	'list_order'=>array('bar_date'=>true),
	'list_max'=>20,
	'valid_record'=>array( 'sql_true($rec["barred_from_admin"]) or sql_true($rec["barred_from_clinical"])
						or sql_true($rec["barred_from_housinga"]) or sql_true($rec["barred_from_housingb"])
						or sql_true($rec["barred_from_dropin"]) or sql_true($rec["barred_from_shelter"])'
				     =>'Must bar from at least one location',
				     //non-client vs client
				     '(be_null($rec["client_id"]) and (!be_null($rec["non_client_name_last"]) and
										 !be_null($rec["non_client_name_first"]) and
										  !be_null($rec["non_client_description"])))
				     or
				     (!be_null($rec["client_id"]) and (be_null($rec["non_client_name_last"]) and
										   be_null($rec["non_client_name_first"]) and
										   be_null($rec["non_client_description"])))'=>
				     'All non-client fields must be filled in for non-client bars'
				     ),
	"fields" => array(
				'bar_date' => array(
							  "default" => "NOW"),
				"bar_date_end" => array(
								"default" => "NOW",
								"label" => "Bar End Date",
								"valid" => array(
										     '($x >= $rec["bar_date"]) || empty($x)'  
										     => "Bar End Date must be greater than Bar Date."
										     ),
								"confirm" => array(
            // for brc bars, bar end date should be set only when client has
            // attended brc, so bar_date_end is OK when all auto brc flags
            // are false or bar_date_end and brc_client_attended_date are empty
            // OR bar_date_end and brc_client_attended_date are both set
                '((sql_false($rec["assault"]) ? true : false) 
                && (sql_false($rec["mc"]) ? true : false)
                && (sql_false($rec["ac"]) ? true: false)
                && (sql_false($rec["dv"]) ? true: false)
                && (sql_false($rec["ms"]) ? true: false)
                && (sql_false($rec["ft"]) ? true: false)
                && (sql_false($rec["ts"]) ? true: false)
                && (sql_false($rec["tc"]) ? true: false)
                && (sql_false($rec["th"]) ? true: false)
                && (sql_false($rec["pd"]) ? true: false)
                && (sql_false($rec["ct"]) ? true: false)
                && (sql_false($rec["police"]) ? true: false)
                && (sql_false($rec["ie"]) ? true: false)
                && (sql_false($rec["money_ex"]) ? true: false))
                || (empty($x) && empty($rec["brc_client_attended_date"]))
                || ($x && $rec["brc_client_attended_date"])'
                    => 'One of the Auto. BRC flags is set to Yes.  Are you sure you
                    want to set a Bar End Date when there is no Client Attended
                    BRC Date?'
                )
								), 
				"description" => array(
							     "label" => "Incident Description",
							     "null_ok" => false),
				"comments" => array(
							  "label" => "Additional Comments"),
				"nc" => array(
						  "display_view" => "hide",
						  "label" => "Non-Cooperative"
						  ),
				"va" => array(
						  "display_view" => "hide",
						  "label" => "Verbally Abusive"
						  ),
				"sib" => array(
						   "display_view" => "hide",
						   "label" => "Sexually Inappropriate Behavior",
						   "comment" => $gbar
						   ),
				"rl" => array(
						  "display_view" => "hide",
						  "label" => "Racist Language"
						  ),
				"wn" => array(
						  "display_view" => "hide",
						  "label" => "Weapons"
						  ),
				"rd" => array(
						  "display_view" => "hide",
						  "label" => "Redeeming"
						  ),
				"po" => array(
						  "display_view" => "hide",
						  "label" => "Drugs/Alcohol Involved",
						  "comment" => $gbar
						  ),
				"ts" => array(
						  "display_view" => "hide",
						  "label" => "Threatening Staff",
						  "comment" => $abrc
						  ),
				"tc" => array(
						  "display_view" => "hide",
						  "label" => "Threatening Client",
						  "comment" => $abrc
						  ),
				"ct" => array(
						  "display_view" => "hide",
						  "label" => "Criminal Trespass",
						  "comment" => "$abrc; Specify incident number in Incident Desc. (No number for CT requests)"  
						  ),
				"ie" => array(
						  "display_view" => "hide",
						  "label" => "Illegal Entry",
						  "comment" => $abrc
						  ),
				"police" => array(
						   "display_view" => "hide",
						   "label" => "Contacted Police",
						   "comment" => "$abrc; Specify incident number and law enforcement agency in Incident Description"
						   ),
				"th" => array(
						  "display_view" => "hide",
						  "label" => "Theft",
						  "comment" => $abrc
						  ),
				"pd" => array(
						  "display_view" => "hide",
						  "label" => "Property Destruction",
						  "comment" => $abrc
						  ),
				"assault" => array(
							 "display_view" => "hide",
							 "label" => "Assaulted Staff",
							 "comment" => $abrc
							 ),
				"ac" => array(
						  "display_view" => "hide",
						  "label" => "Assaulted Client",
						  "comment" => $abrc
						  ),
				"ms" => array(
						  "display_view" => "hide",
						  "label" => "Menaced Staff",
						  "comment" => $abrc
						  ),
				"mc" => array(
						  "display_view" => "hide",
						  "label" => "Menaced Client",
						  "comment" => $abrc
						  ),
				"dv" => array(
						  "display_view" => "hide",
						  "label" => "Domestic Violence",
						  "comment" => $abrc
						  ),
				"ft" => array(
						  "display_view" => "hide",
						  "label" => "Fighting",
						  "comment" => $abrc
						  ),
				"money_ex" => array(
							  "display_view" => "hide",
							  "label" => "Exchange of Money",
							  "comment" => $abrc
							  ),
				"therapeutic" => array(
							     "display_view" => "hide",
							     "label" => "Therapeutic",
							     "comment" => "Explain why in Incident Description"
							     ),
				"overdose" => array(
							  "display_view" => "hide",
							  "label" => "Confirmed Client Overdose"
							  ),
				"cdmhp" => array(
						     "display_view" => "hide",
						     "label" => "Contacted CDMHPs"
						     ),
				"reinstate_condition" => array(
									 "label" => "Conditions for Reinstatement",
									 "comment" => "Describe rules for reinstatement"
									 ),
				"gate_mail_date" => array( 'comment'=>'Provide mail service through this date'
								  ),
				'brc_elig_date' => array('label' => 'Date Eligible for BRC'),
				'brc_client_attended_date' => array('data_type' => 'date_past',
										'label' => 'Date Client Attended BRC'),
				'brc_resolution_code' => array('label' => 'BRC Resolution'), 
                        	"appeal_elig_date" => array(
							    "label" => "Date Eligible to Appeal",
							    "valid" => array(
									     '($x >= $rec["brc_client_attended_date"]) || empty($x)'  
									     => "Date Eligible to Appeal must be greater than Date Client Attended BRC."
									     )
							    ),  
				'brc_recommendation' => array('data_type' => 'text',
									'label' => 'Barring Staff Recommendations for BRC'),
				'bar_resolution_location_code' => array('label'=>'Where should client attend BRC?',
										    'comment'=>'(if applicable)',
										    'valid'=>array('(be_null($rec["bar_date_end"]) and !be_null($x))
												  or !be_null($rec["bar_date_end"])'=>'Field {$Y} required for BRC'),
										    'lookup_order'=>'TABLE_ORDER'
										    ),
				'bar_incident_location_code'=>array('label'=>'Where did the incident occur?',
										'lookup_order'=>'TABLE_ORDER'),
				'police_incident_number'=>array('label'=>'Police Incident Number',
								     'comment'=>'(if applicable)'),
				'barred_from_admin'=> array('display_view'=>'hide'),
				'barred_from_clinical'=> array('display_view'=>'hide'),
 				'barred_from_dropin'=> array('display_view'=>'hide'),
 				'barred_from_housinga'=> array('display_view'=>'hide'),
 				'barred_from_housingb'=> array('display_view'=>'hide'),
 				'barred_from_shelter'=> array('display_view'=>'hide'),
				'barred_from_summary'=>array(
								     'label'=>'Barred From'),
 				'non_client_description'=>array('textarea_width' => 40,
									  'textarea_height' =>1 
									  ),
				'non_client_name_full'=>array('label'=>'Non-Client Name')
				)
		     );

?>
