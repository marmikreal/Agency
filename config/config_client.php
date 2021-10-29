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

global $agency_home_url;
$engine["client"] = array(
		'cancel_add_url'=>$agency_home_url,
		'list_fields'=>array('custom1','custom2','custom3','custom4','custom5'),
		'list_hide_view_links' => true,
		'child_records'=> array(
						//FIXME: this is hacky, and needs to be more configurable/generic for
						//other child record types
						//--------General-------//
						'log'                 => 'general',
						'client_note'         => 'general',
						'staff_assign'        => 'general',
						'immigrant'           => 'general',
						'disability'          => 'general',
						'ethnicity'			  => 'general',
						'hiv'                 => 'general',
						'income'              => 'general',
						'contact_information' => 'general',
						'phone'               => 'general',
//						'employment_status'   => 'general',
						'education_level'     => 'general',
						'housing_history'     => 'general',
						'jail'                => 'general',
//						'criminal_history'    => 'general',
						'bar'                 => 'general',
						'entry'               => 'general',
//						'medical_health'      => 'general',
//						'nicotine_distribution' => 'general',
//						'application_housing_other' => 'general',
						'elevated_concern'          => 'general',
//						'vocational_reg'            => 'general',
//						'conditional_release' => 'general',
//						'sh_additional_data'=>'general',
/*
 * These items logically go in other sections, and are
 * commented out in those areas.  For simplicity sake,
 * I've combined them all into the general section.
 */
						'shelter_reg' => 'general',
						'bed'         => 'general',
						'calendar_appointment' => 'general',
						'hospital'             => 'general',
						//-------Shelter------//
//						'shelter_reg' => 'shelter',
//						'assessment'  => 'shelter',
//						'bed'         => 'shelter',
//						'mail'        => 'shelter',
//						'client_locker_assignment' => 'shelter',
//						'service_ir'               => 'shelter',
						//-------Housing------//
//						'housing_rsp'         => 'housing',
//						'housing_notice'      => 'housing',
//						'application_housing' => 'housing',
//						'charge'              => 'housing',
//						'client_export_id'    => 'housing',
//						'payment'             => 'housing',
//						'payment_test'        => 'housing',
//						'service_housing'     => 'housing',
//						'data_gathering_1811' => 'housing',
						//-------HEET--------//
//						'heet_reg'      => 'HEET',
//						'event'         => 'HEET',
//						'service_heet'  => 'HEET',
//						'heet_resource' => 'HEET',
						//---CD---//
//						'cd_reg'       => 'CD',
//						'service_cd'   => 'CD',
						//---CRP---//
//						'crp_reg'     => 'CRP',
//						'service_crp' => 'CRP',
						//---Other---//
						//-------Clinical-------//
//						'address'                            => 'clinical',
//						'activity_evaluation'                => 'clinical',
//						'calendar_appointment'               => 'clinical',
//						'chart_archive'                      => 'clinical',
//						'client_description'                 => 'clinical',
//						'clinical_condition_at_assessment'   => 'clinical',
//						'clinical_impression'                => 'clinical',
//						'clinical_priority'                  => 'clinical',
//						'clinical_reg'                       => 'clinical',
//						'clinical_reg_request'               => 'clinical',
//						'clinical_screening_intake'          => 'clinical',
//						'cod_screening'                      => 'clinical',
//						'cod_assessment'                     => 'clinical',
//						'dal'                                => 'clinical',
//						'diagnosis'                          => 'clinical',
//						'disability_clinical'                => 'clinical',
//						'homeless_status_clinical'           => 'clinical',
//						'hospital'                           => 'clinical',
//						'import_client_dshs_medicaid_lookup' => 'clinical',
//						'medicaid'                           => 'clinical',
//						'path_tracking'                      => 'clinical',
//						'pss'                                => 'clinical',
//						'referral_clinical'                  => 'clinical',
//						'residence_clinical'                 => 'clinical',
//						'spenddown'                          => 'clinical',
//						'veteran_status_clinical'            => 'clinical',
						//-----Deceased-----//
						'client_death'             => 'general'
						),
		/* Multi configuration moved to config_client_multi.php.  Included at end. */

		"title" => 'ucwords($action) . "ing '.AG_MAIN_OBJECT.' record for " . client_link($rec["client_id"])',
		"title_add"=>'ucwords($action) . "ing a new '.AG_MAIN_OBJECT.' record."',
		"fields" => array(
					 'is_protected_id'=>array('display'=>'hide'),
					'comments'=>array('post'=>false, //until we drop the field all together
								'display'=>'hide'),
				  "issue_no" => array( 
						      "display"=>"display",
						      "display_add"=>"hide",
						      "post_add"=>false),
				  'resident_id'=>array(
								'display'=>'hide'),
				  "name_last" => array( 
						       "force_case"=>"upper"),
				  "name_first" => array( 
							"force_case"=>"upper"),
				  "name_middle" => array( 
							"force_case"=>"upper"),
 					 'name_suffix' => array(
 									'data_type'=>'lookup',
 									'lookup'=>array('table'=>'l_name_suffix',
 											    'value_field'=>'name_suffix_code',
 											    'label_field'=>'description')
 									),
					 'name_alias' => array(
								     'valid'=>array('!preg_match("/^\s*\(?\s*(no|none\.?|n\.?k\.?a\.?)\s*\)?\s*$/i",$x)'=>'"NO", "NONE" or "NKA" are not valid aliases'),
								     'confirm'=>array('!preg_match("/([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4})|([0-9]{3}-?[0-9]{2}-?[0-9]{4})/",$x)'=>'alternate DOB and SSN should be stored in a client note, not the alias field.')),
				  "dob" => array(			
						     "data_type" => "date_past",
						     'confirm' => array('days_interval(dateof("now"),$x)/365 >= 18'=>'{$Y} contains a value that places the '.AG_MAIN_OBJECT.' under 18')),
				  "ssn" => array(
						 "data_type"=>"ssn"),
				  "last_photo_at" => array(
							   'display'=>'display',
							   'display_add'=>'hide'),
				  "name_full"=>array(
						     "display"=>"hide"),
				  "name_full_alias"=>array(
							   "display"=>"hide"),
					 'needs_interpreter_code'=>array('lookup_order'=>'TABLE_ORDER',
										   'lookup_format'=>'radio'),
					 
					
				  //for use with the custom show_query_row_client() function
				  'custom1' => array(
							   'display'=>'hide',
							   'label_format_list'=>'smaller($x)',
							   'label_list'=>ucfirst(AG_MAIN_OBJECT).' / ID # /<br />Overnight Eligibility | Assessed Score<br />Housing Status'),
				  'custom2' => array(
							   'display'=>'hide',
							   'label_format_list'=>'smaller($x)',
							   'label_list'=>'Last Entry'),
				  'custom3' => array(
							   'display'=>'hide',
							   'label_format_list'=>'smaller($x)',
							   'label_list'=>'Bar Status'),
				  'custom4' => array(
							   'display'=>'hide',
							   'label_format_list'=>'smaller($x)',
							   'label_list'=>'Gender<br />Ethnicity<br />Date of Birth'),
				  'custom5' => array(
							   'display'=>'hide',
							   'display_list'=>'regular',
							   'value'=>'client_photo( $rec["client_id"], 0.5 )',
							   'is_html'=>true,
							   'label_format_list'=>'smaller($x)',
							   'label_list'=>'Picture'),
					 //clinical
/* Commenting out example of fancy ID handling.
					 'clinical_id' => array('label'=>'Clinical ID',
									'display_add' => 'hide',
									'display_edit' => 'hide',
									'is_html' => true,
									'value_view' => 'be_null($x) ? hlink_if("client_display.php?action=set_clinical_id&id=".$rec[AG_MAIN_OBJECT_DB],"Set Clinical ID",has_perm("clinical_admin","RW"),"", "onclick=\"".call_java_confirm("Are you sure you want to set this client\'s clinical ID?")."\"") : $x',
									'valid'=>array('be_null($x) || ($action=="edit" 
														 && sql_num_rows(get_generic(array("!client_id"=>$rec["client_id"],
																			     "clinical_id"=>$x),"","","client"))==0)
											   || ($action=="add" && sql_num_rows(get_generic(array("clinical_id"=>$x),"","","client"))==0)'
											   =>'This {$Y} already exists')),
*/
					 'king_cty_id' => array('label' => 'King County ID',
									'valid'=>array('be_null($x) || ($action=="edit" 
														 && sql_num_rows(get_generic(array("!client_id"=>$rec["client_id"],
																			     "king_cty_id"=>$x),"","","client"))==0)
											   || ($action=="add" && sql_num_rows(get_generic(array("king_cty_id"=>$x),"","","client"))==0)'
											   =>'This {$Y} already exists'),
									'confirm' => array('be_null($ox) || $x == $ox' => 'You have selected to change this client\'s KCID. Please verify that the new KCID is correct')),
					 'spc_id' => array('label' => 'SPC ID',
								 'valid'=>array('be_null($x) || ($action=="edit" 
														 && sql_num_rows(get_generic(array("!client_id"=>$rec["client_id"],
																			     "spc_id"=>$x),"","","client"))==0)
											   || ($action=="add" && sql_num_rows(get_generic(array("spc_id"=>$x),"","","client"))==0)'
										    =>'This {$Y} already exists'))
				  )
		);
include 'config_client_multi.php'; // Configure multi--ethnicity & disability
?>
