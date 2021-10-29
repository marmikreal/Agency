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

include 'includes.php';

//$START='2003-01-01';
$END=dateof("now","SQL");
$START=year_of($END) . "-01-01";
$startdate=dateof(orr($_REQUEST['startdate'],$START),'SQL');
$enddate=dateof(orr($_REQUEST['enddate'],$END),'SQL');
$action = $_REQUEST['action'];
$org=org_name('short');

//GROUPS
$group = orr($_REQUEST['group'],'ALL');
$bed_groups = sql_query('SELECT description,bed_group_code FROM l_bed_group');
while ($bed_group=sql_fetch_assoc($bed_groups)) {
		$GROUPS[$bed_group['description']] = enquote1($bed_group['bed_group_code']);
}
//$GROUPS['ALL']= '(' . implode(",",array_values($GROUPS)) . ')';
$housing_groups = sql_query('SELECT description,housing_project_code FROM l_housing_project');
while ($housing_group=sql_fetch_assoc($housing_groups)) {
		$H_GROUPS[$housing_group['description']] = enquote1($housing_group['housing_project_code']);
}

$non_beds = array_merge(array('Gatekeeping (Shelter)',
//				'Clinical Dals',
				'Housed at ' . $org,
				'Housed Elsewhere'),array_keys($H_GROUPS));
// future SCATTERED                 | Scattered Site Housing

// ----- custom sql group ----- //

// Note: any custom sql must return results as (date, client_id, description)
$custom_submit_sql = dewebify($_REQUEST['custom_submit_sql']);

// THE GROUP INFORMATION IS PASSED INTO rep_profile_sql.php 
// TO CONSTRUCT THE QUERY
if (!is_array($group))
{
      $GROUP='(' . implode(',',$GROUPS) . ')';
	$H_GROUP='(' . implode(',',$H_GROUPS) .')';
}
else
{
	$GROUP = $H_GROUP = array();
      foreach ($group as $key => $value)
      {
		if ($value && (!in_array($key,$non_beds))) {
			array_push($GROUP,$GROUPS[$key]);
		} elseif ($value && in_array($key,array_keys($H_GROUPS))) {
			array_push($H_GROUP,$H_GROUPS[$key]);
		}
	}
	$GROUP=empty($GROUP) ? false : '('.implode(',',$GROUP).')';
	$H_GROUP = empty($H_GROUP) ? false : '(' . implode(',',$H_GROUP) . ')';
}
$SQL_BED = $GROUP ? true : false;
$SQL_HOUSING = $H_GROUP ? true : false;
$SQL_HOUSED_OWN = ( $group['Housed at ' . $org] ) ? true : false;
$SQL_HOUSED_ELSEWHERE = $group['Housed Elsewhere'] ? true : false;
$SQL_GATE_SHELTER = $group['Gatekeeping (Shelter)'] ? true : false;
//$SQL_DAL = $group['Clinical Dals'] ? true : false;

$group_text = ( (count($group)==count(array_merge($non_beds,array_keys($GROUPS)))) || $group=='ALL')
     ? 'All Clients' : implode(', ',array_keys($group));
$title = oline("$org Profile Data for $group_text")
     . oline("During time period: ".dateof($startdate).' - '.dateof($enddate));

if (!$startdate || !$enddate)
{
      unset($action);
}

$js = <<<EOF

function checkUnCheck() {
	var els = document.getElementsByTagName('input');

	for (var i=0; i < els.length; i++) {
		var el = els[i];
		if (el.type == 'checkbox' && el.id != 'showSQLCheck') {
			if (!el.checked) {
				el.checked = true;
				var checking = true;
			} else {
				el.checked = false;
				var checking = false;
			}
		}
	}
	document.getElementById('checkAllLink').innerHTML = checking ? 'Uncheck All' : 'Check All';
}

EOF;
out(Java_Engine::get_js($js));

out(bigger(bold($title)));
if ($action<>'submit')
{
      //GROUP SELECT
	$check_all_link = js_link('Check All','checkUnCheck()','id="checkAllLink"');

      $select = oline( bold('Groups to Include ('.$check_all_link.'):'));
      //gatekeeping
      $select .= html_fieldset('Shelter',oline(form_field('boolcheck','group[Gatekeeping (Shelter)]',false).' Shelter Entry'));
	//clinical
//      $select .= html_fieldset('Clinical',oline(form_field('boolcheck','group[Clinical Dals]',false).' Clinical Dals'));
      //beds
      foreach (array_keys($GROUPS) as $label ) {
      
	    $bed_select .= oline(form_field('boolcheck','group['.$label.']',false).' '.$label);
      }
	//housing
	foreach (array_keys($H_GROUPS) as $label) {
		$housing_select .= oline(form_field('boolcheck','group['.$label.']',false).' '.$label);
	}
	//housed from homeless
	$housing_homeless_select .= oline(form_field('boolcheck',"group[Housed at $org]",false)." Housed at $org");
	$housing_homeless_select .= oline(form_field('boolcheck','group[Housed Elsewhere]',false).' Housed Elsewhere');
	
	$select .= html_fieldset('Beds',$bed_select) . html_fieldset('Housing',$housing_select). html_fieldset('Housed From Homelessness',$housing_homeless_select);
   	$select .= html_fieldset('Show SQL Query',form_field('boolcheck','show_sql',false,' id="showSQLCheck"') . 'Display SQL Query');


	$select = div($select,'',' style="width: 30%;"');

      $message = oline(red('This report may take a few minutes.'));
      $out = formto()
		. hiddenvar('action','submit')
		. hiddenvar('pfriendly',$pfriendly)
		. tablestart_blank()
		. rowrlcell('Start Date',form_field('date','startdate',$startdate))
		. rowrlcell('End Date',form_field('date','enddate',$enddate))
		. $select
		. oline(button('Submit'))
		. row(centercell(oline('',3).oline(italic(bold('-- optional --')),3),'colspan="2"'))
	        . row(cell(oline('Enter a custom SQL statement in the box below. When using a custom')
			   . ('SQL statement, all of the options above will be ignored and have no effect.'),'colspan="2"'))
	        . row(cell(oline(italic('Query results must return rows as (date,client_id,description)')),'colspan="2"'))
		. row(cell(formtextarea('custom_submit_sql',$custom_submit_sql),'colspan="2"'))
		. tableend()
		. oline(button('Submit'))
		. formend()
		. $message;
      out($out);
 }
else
{
      require 'rpt_profile_sql.php'; //sets $sql
      $x=0;
      foreach($sql as $label=>$query)
      {
 	    if (!$SQL_BED && $label==='bednights')
 	    {     // DON'T PERFORM BEDNIGHT QUERY.
 		  $x++;
 		  continue;
 	    }
		$show_queries .= oline($query,2);
		$result=sql_query($query) or sql_warn($query);
	    if ($x>1)
	    {
		  $out = subhead(ucfirst($label));
		  // 	    while($a=sql_fetch_assoc($result));
		  // 	    {
		  //  		  print_r($a);
		  // 	    }
		  $num=sql_num_rows($result);
		  $out.=tablestart('','border="1"');
		  if ($label=='language')
		  {
			$out .= row( cell(left(bold($label)))
				     . cell(right(bold('Needs Interpreter')))
				     . cell(right(bold('Count'))));
		  }
		  else
		  {
			$out .= rowrlcell(left(bold($label)),right(bold('Count')));
		  }
		  for ($j=0;$j<$num;$j++) //FOR SOME REASON while($a=sql_fetch_assoc($result)) WASN'T WORKING!!
		  {
			//		  print_r(sql_fetch_assoc($result,$j));
			$rec=sql_fetch_assoc($result,$j);
			$type=orr($rec['description'],bold('No Record'));
			$count=$rec['count'];
			if ($label=='language')
			{
			      $out .=row(cell(left($type))
					 . cell(left($rec['Needs Interpreter']))
					 . cell(right($count))
					 );
			}
			else
			{
			      $out .= rowrlcell(left($type),right($count));
			}
		  }
		  $out .= tableend();
	    }
		out($out);
	    $x++;
      }
	 if ($_REQUEST["show_sql"])
	 {
		out($show_queries);
	 }
}



page_close();
?>
