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

$engine["crp_reg"] = 
array(
	'add_link_label' => 'Register this client for CRP',
	"table" => "crp_reg",
	"table_post" => "tbl_crp_reg",
	"singular" => "CRP Registration",
	"allow_edit"=>true,
	"allow_delete"=>true,
	"stamp_deletes"=>true,
	"stamp_changes"=>true,
	'list_fields'=>array('crp_reg_date','crp_reg_date_end','days'),
	'list_order'=>array('client_id'=>false,
				  'crp_reg_date'=>true),//most recent first
	"title" => 'ucwords($action) . "ing CRP Registration 
            for " . client_link($rec["client_id"])',
	"perm"=>"clinical", // good enough for perms???
	"fields" => array(
				"crp_reg_id" => array(
							    "display" => "display",
							    "display_add" => "hide"),
				"client_id" => array("display" => "display"),
				"king_cty_auth_no" => array("display" => "hide",
								'display_view'=>'display',
								'display_list'=>'view'),
				"crp_reg_date" => array(
								"label" => "Start Date",
								"data_type" => "date",
								"default" => "NOW",
								"null_ok" => false            
								),
				"registered_by" => array( "null_ok" => false ),
				"crp_reg_date_end" => array("label" => "End Date",
								    "data_type"=>"date"),
				//         "reg_referred_from_code" => array(
				//             "data_type" => "lookup",            
				//             "null_ok" => false,
				//             "label" => "Referred From"), 
				"reg_referral_comment" => array("data_type" => "text"),
				"hispanic_code" => array(
								 "null_ok" => false,
								 "data_type" => "lookup"),
				"has_medicaid" => array("data_type" => "boolean",
								"null_ok" => false),
				"has_veteran_benefit" => array("data_type" => "boolean"),
				"was_service_disabled" => array(
									  "label" => "Disabled Due to Military Service",
									  "data_type" => "boolean"),
				"has_veteran_pension" => array("data_type" => "boolean"),
				"has_veteran_hospital_care" => array(
										 "label" => "Has received care at Veterans (VA) Hospital",
										 "data_type" => "boolean"),
				"imp_schizophrenia" => array(
								     "label" => "Schizophrenia Impression",
								     "data_type" => "boolean",
								     "null_ok" => false,
								     "comment" => "Clinical Impressions"),
				"imp_psychotic" => array(
								 "label" => "Other Psychotic Disorders Impression",
								 "data_type" => "boolean",
								 "null_ok" => false,
								 "comment" => "Clinical Impressions"),
				"imp_affective" => array(
								 "label" => "Affective Disorders Impression",
								 "data_type" => "boolean",
								 "null_ok" => false,
								 "comment" => "Clinical Impressions"),
				"imp_depressive" => array(
								  "label" => "Depressive Disorders Impression",
								  "data_type" => "boolean",
								  "null_ok" => false,
								  "comment" => "Clinical Impressions"),
				"imp_bipolar" => array(
							     "label" => "Bi-polar Disorders Impression",
							     "data_type" => "boolean",
							     "null_ok" => false,
							     "comment" => "Clinical Impressions"),
				"imp_affect_nos" => array(
								  "label" => "Affective Disorders - Not Specified Impression",
								  "data_type" => "boolean",
								  "null_ok" => false,
								  "comment" => "Clinical Impressions"),
				"imp_personality" => array(
								   "label" => "Personliaty Disorders Impression",
								   "data_type" => "boolean",
								   "null_ok" => false,
								   "comment" => "Clinical Impressions"),
				"imp_other" => array(
							   "label" => "Other Serious Mental Illness Impression",
							   "data_type" => "boolean",
							   "null_ok" => false,
							   "comment" => "Clinical Impressions (e.g. obsessive/compulsive 
                        disorder, PTSD, anxiety disorder)"),
				"imp_alcohol_abuse" => array(
								     "label" => "Alcohol Abuse or Dependence Impression",
								     "data_type" => "boolean",
								     "null_ok" => false,
								     "comment" => "Clinical Impressions"),
				"imp_drug_abuse" => array(
								  "label" => "Drug Abuse or Dependence Impression",
								  "data_type" => "boolean",
								  "null_ok" => false,
								  "comment" => "Clinical Impressions"),
				"imp_medical_condition" => array(
									   "label" => "Serious Medical Condition Impression",
									   "data_type" => "boolean",
									   "null_ok" => false,
									   "comment" => "Clinical Impressions"),
				"imp_unknown" => array(
							     "label" => "Unknown or Undiagnosed Mental Illness",
							     "data_type" => "boolean",
							     "null_ok" => false,
							     "comment" => "Clinical Impressions"),
				"imp_none" => array(
							  "label" => "Lacks Serious Mental Illness Impression",
							  "data_type" => "boolean",
							  "null_ok" => false,
							  "comment" => "(YES=Impression of Lacking Serious Mental Illness, NO=Impression of Does not Lack SMI)"),
				"imp_comment" => array(
							     "label" => "Clinical Impressions Comments",
							     "null_ok" => false,
							     "data_type" => "text"),
				"enrolled_cd" => array(
							     "label" => "Currently Enrolled in CD",
							     "null_ok" => false,
							     "data_type" => "boolean"),
				"enrolled_other" => array("data_type" => "text"),
				"enrolled_phpvendor_code" => array(      
									     "label" => "Enrolled in PHP",
									     "data_type" => "lookup",
									     "lookup" => array(
												     "table" => "l_phpvendor",
												     "value_field" => "phpvendor_code",
												     "label_field" => "description")),
				"exit_reason_code" => array(
								    "null_ok" => "false"),
				"exit_referral_1_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_1_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_2_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_2_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_3_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_3_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_4_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_4_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_5_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_5_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_6_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_6_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_7_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_7_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_8_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_8_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description")),
				"exit_referral_9_code" => array(
									  "data_type" => "lookup",
									  "lookup" => array(
												  "table" => "l_exit_referral",
												  "value_field" => "exit_referral_code",
												  "label_field" => "description")),
				"exit_linkage_9_code" => array(
									 "data_type" => "lookup",
									 "lookup" => array(
												 "table" => "l_exit_linkage",
												 "value_field" => "exit_linkage_code",
												 "label_field" => "description"))
				)
	);
?>
