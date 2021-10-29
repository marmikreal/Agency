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

/*
 * These awkwardly named functions are for multiple child records.
 * They are adapted from disability-specific functions for the
 * same purpose.
 */

function blank_generics_add($rec,$def,$child_object)
{
$multi=$def['multi'][$child_object];    
$multi_fields = $multi['multi_fields'];
$c_def = get_def($child_object);
$user_field = $multi['field'];
$f_def = $c_def['fields'][$user_field];
$a = array_keys($c_def['fields']);
$table = $f_def['lookup']['table'];
$order=is_array($multi['other_codes']) ? implode($multi['other_codes'],"','") : '';			
$sql=orr($multi['sel_sql'],"SELECT * FROM $table");
$codes=agency_query("$sql ORDER BY $user_field IN ('$order'), description"); 
while ($item=sql_fetch_assoc($codes))
{
$code=$item[$user_field];
$fake_field = "multi_{$child_object}_multi_$code";
foreach($a as $field)
{
	if ($field=="added_by" || $field == "changed_by")
	{
		$rec[$fake_field][$field]=$GLOBALS["UID"];
	}
	elseif($field==$user_field)
	{
		$rec[$fake_field][$field] = $code;
	}
			else
			{
				$rec[$fake_field][$field] = null;
			}
		}
	}
	if ($multi['confirm_none']) {
		$rec["multi_{$child_object}_multi_no_multi_records"] = array($user_field=>"multi_{$child_object}_multi_no_multi_records", $multi_fields=>false);
	}
	return $rec;
}

function add_generics_fields($def,$child_object)
{
	$c_def = get_def($child_object);
  	$multi=$def['multi'][$child_object];    
	$user_field = $multi['field'];
	$f_def = $c_def['fields'][$user_field];
	$table = $f_def['lookup']['table'];
	$codes=agency_query(orr($multi['sel_sql'],"SELECT * FROM $table"));
	$multi_fields=$multi['multi_fields'];
	while($rec=sql_fetch_assoc($codes))
	{
		$code=$rec[$user_field];
		$fake_field = "multi_$child_object". '_multi_' . $code;
		$label=$rec["description"];
		switch ( $rec['value_type_code'] ) {
			case 'YESNO' :
				$format = 'radio';
				break;
			default :
				$format = 'check';
		}
		$def["fields"][$fake_field]=array("data_type"=>"multi_rec",
							"display_add"=>"multi_disp",
							"label_add"=>$label,
// changing to array:
							"multi_field"=>$multi_fields,
							"multi_type"=>'boolean',
							'multi_format'=>$format,
							'null_ok'=>sql_true($rec['null_ok']));
	}
	if ($multi['confirm_none']) {
		$def['fields']["multi_{$child_object}_multi_no_multi_records"] = array("data_type"=>"multi_rec",
			    "display_add"=>"multi_disp",
			    "label_add"=>'Check this box ONLY if the '.$def['singular'].' has no ' .$c_def['plural'],
			    "multi_field"=>$multi_fields,
			    "multi_type"=>"boolean");
	}
	return $def;
}

function valid_generics($action,$big_rec,&$def,&$mesg,&$valid,$child_object)
{
	if ($action !=='add') { return; }
	$subdef = get_def($child_object);
  	$multi=$def['multi'][$child_object];    
	$multi_fields=$multi['multi_fields'];
	$user_field=$def['multi'][$child_object]['field'];
	$any_subs = false;
	foreach($big_rec as $key=>$rec) {
		if (!($def['fields'][$key]['data_type']=='array') and is_array($rec) && $key !== "multi_{$child_object}_multi_no_multi_records") {
			$any_subs = $any_subs ? true : sql_true($rec[$multi_fields]);
			foreach($rec as $skey=>$value) {
				if ( ($val=$subdef['fields'][$skey]['valid']) && sql_true($rec[$multi_fields])) {
					$x=$value;  
					// field can have multiple tests and multiple messages to display
					foreach ($val as $test => $msg)  
					{
						if (!eval( "return $test;" ))
						{
							$mesg .= empty($msg) 
								? oline("Field $label has an invalid value.")
								: oline(str_replace('{$Y}',$label,$msg));
							$valid=false;
							$def['fields'][$key]['not_valid_flag']=true;
						}
					}
				}
			}
			//if ( (be_null($rec[$multi_fields]) or (
			if ( ( ($def['fields'][$key]['multi_format']=='radio' and be_null($rec[$multi_fields]))
				or ($def['fields'][$key]['multi_format']=='checkbox' and ~!sql_true($rec[$multi_fields])))
				and (!$def['fields'][$key]['null_ok']===true)) {
				$mesg .= oline('Must specify ' . value_generic($rec[$user_field],$subdef,$user_field,$action));
				$valid=false;
				$def['fields'][$key]['not_valid_flag']=true;
			}
			if (sql_true($rec[$multi_fields]) and in_array($rec[$user_field],$subdef['fields'][$user_field]['require_comment_codes']) and be_null($rec['comment'])) {
				$mesg .= oline('Comment required for ' . $subdef['singular'] . ' of ' . value_generic($rec[$user_field],$subdef,$user_field,$action));
				$valid=false;
				$def['fields'][$key]['not_valid_flag']=true;
			}
		}
	}
	if (!$any_subs && $multi['allow_none'] && $multi['confirm_none'] && !sql_true($big_rec["multi_{$child_object}_multi_no_multi_records"][$multi_fields])) {
		$def['fields']["multi_{$child_object}_multi_no_multi_records"]['not_valid_flag']=true;
		$valid = false;
		$mesg .= oline('Choose ' . $subdef['plural'] . ' or specify "No ' . $subdef['plural'] . '"');
	} elseif ($multi['confirm_none'] && $any_subs && sql_true($big_rec["multi_{$child_object}_multi_no_multi_records"][$multi_fields])) {
		$def['fields']["multi_{$child_object}_multi_no_multi_records"]['not_valid_flag']=true;
		$valid = false;
		$mesg .= oline('You can\'t specify "No ' . $subdef['plural'] . '" in addition to another ' . $subdef['singular'] . '!');
	} elseif (!$any_subs && !$multi['allow_none']) {
		$valid = false;
		$mesg .= oline('You must specify at least one ' . $subdef['singular'] . '!');
	}
}

function form_generics_row($key,$rec,$def,$child_object)
{
	$c_def = get_def($child_object);
	$multi=$def['multi'][$child_object];    
	$multi_fields=$multi['multi_fields'];
	$user_field = $multi['field'];
	$sub = $rec[$user_field];
	$sub2 = strstr($sub,'no_multi_records') ? $sub : "multi_{$child_object}_multi_$sub";
	$label = $def["fields"][$key]["label_add"];
	if ($def['fields'][$key]['not_valid_flag']) { 
		$label = span($label,'class="engineFormError"');
	}
	if (in_array($sub,$c_def['fields'][$user_field]['require_comment_codes'])) {
		foreach($rec as $key=>$value) {
			if (in_array($key,array($multi_fields,'comment'))) {
				continue;
			}
			$out .= hiddenvar("rec[$sub2][$key]",$value);
		}
				$out .= rowrlcell(
					formcheck("rec[$sub2][$multi_fields]",sql_true($rec[$multi_fields]))
					,$label.smaller(' (please describe) ')
					. formvartext("rec[$sub2][comment]",$rec['comment']) 
);
	} else {
		foreach($rec as $key=>$value)
		{
			if ($key==$multi_fields) {
				switch ($def['fields'][$sub2]['multi_format']) {
					case 'radio' :
						$formval = sql_true($value) ? 'Y' : (sql_false($value) ? 'N' : NULL);
						if ($def['fields'][$sub2]['null_ok']) {
							$wipeout = formradio_wipeout("rec[$sub2][$key]");
						}
						$out .= rowrlcell($wipeout . yes_no_radio("rec[$sub2][$key]",'',$formval),$label);
						break;
					default :
						$out .= rowrlcell(formcheck("rec[$sub2][$key]",sql_true($value)),"$label");
				}
			} else {
				$out .= hiddenvar("rec[$sub2][$key]",$value);
			}
		}
	}
	return $out;
}

function post_generics($multi_records,$new_rec,$def,&$mesg,$child_object)
{
	$c_def = get_def($child_object);
	$table = $c_def["table_post"];
  	$multi=$def['multi'][$child_object];    
	$multi_fields=$multi['multi_fields'];
	$sing = $c_def['singular'];
	$cid=$new_rec[$def['id_field']];
	if (!(is_numeric($cid)))
	{
		$mesg .=oline("Error: Cannot post $sing records, no ".$def['singular']." ID given to post_generics");
		return false;
	}
	$count=0;
	foreach($multi_records as $sub=>$rec)
	{
		$post_by_reference = in_array($def['id_field'], array_keys($rec)) ? FALSE : TRUE;
		//if (sql_true($rec[$multi_fields]) && $sub !== "multi_{$child_object}_multi_no_records")
		if (!be_null($rec[$multi_fields]) && $sub !== "multi_{$child_object}_multi_no_records")
		{
			$fields=$def["fields"][$sub];
			$label=$fields["label"];
			
			if (!$post_by_reference) { 
				$rec[$def['id_field']]=$cid;
			} else {
				$ref_def=get_def('reference');
			}
			unset($rec[$c_def['id_field']]);
			unset($rec['source']); //genericize?
			unset($rec['is_deleted']);
			
			foreach($rec as $key=>$value)
			{
				//if ($key=="added_at" || $key=="changed_at" || $key==$multi_fields)
				if ($key==$multi_fields and stristr($key,'_date'))
				{
					//unset($rec[$key]);
					//$rec["FIELD:$key"]="CURRENT_TIMESTAMP";
					$rec[$key]=dateof('now','SQL');
				}
			}			
			$c_def['post_with_transactions']=false; // already in transaction from parent record
			//$result=agency_query(sql_insert($table,$rec));
			$result=post_generic($rec,$c_def,$mesg);
			if (!$result)
			{
				$mesg .= oline("Your attempt to post a {$label} record failed.");
				log_error("Error in {$action}ing, using post_generics_multi(). Here was the record: " 
					    . dump_array($rec));
				//continue;
				return false;
			}
			if ($post_by_reference) {
				$ref_rec=array(
					'from_table'=>$def['object'],
					'from_id_field'=>$def['id_field'],
					'from_id'=>$cid,
					'to_table'=>$c_def['object'],
					'to_id_field'=>$c_def['id_field'],
					'to_id'=>$result[$c_def['id_field']],
					'added_by'=>$result['added_by'],
					'changed_by'=>$result['changed_by']);
				$ref_def['post_with_transactions']=false; // already in transaction from parent record
				$result=post_generic($ref_rec,$ref_def,$mesg);
				if (!$result) {
					$mesg .= oline("Error posting by reference.  Here was the reference: " . dump_array($ref_rec));
					//continue;
					return false;
				}
			}
			$count++;
		}
		else
		{
			//don't post a new record if there is nothing to post
		}
	}
	
	$mesg .= oline("You succesfully posted $count $sing records.");
	return true;
}

function info_additional_config_array( &$def ) {
	$multi=array('sub_title' => 'Additional Information',
	   	'multi_fields'=>'info_additional_value',
		'object' => 'info_additional',
		'field' => 'info_additional_type_code',
		'blank_fn'=>'blank_generics_add',
		'add_fields_fn'=>'add_generics_fields',
		'form_row_fn'=>'form_generics_row',
		'valid_fn'=>'valid_generics',
		'post_fn'=>'post_generics',
		'sel_sql'=>"SELECT * FROM l_info_additional_type WHERE '{$def['object']}' = ANY(applicable_tables)",
		'allow_none'=>true);

	$infos=agency_query($multi['sel_sql']);
		if (sql_num_rows($infos) > 0 ) {
			$def['multi_records']=true;
			$def['multi']['info_additional']=$multi;
			return true;
		} else {
			return false;
		}
}
?>
