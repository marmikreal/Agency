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

// query.php
// functions for constructing queries

function quick_searches()
{
// provide other 3 quick search boxes, for chaser_top_header()
	global $colors;
	return array( 
		cell(log_query(),' class="log"'),
		cell(staff_query("","sidebar"),'class="staff"'),
		//temporarily hiding i&r searching until agency data is updated.
		/*	cell(ir_query("","sidebar"),' class="iandr"')*/);
}

function quick_searches_all()
{
	//provide all 4 quick searches in one box via JS
	//I am re-writing the qs functions so they all return a uniform format (like client_qs)

	//since this will be on all pages, the function already assumes that the appropriate JS is in the head tags...

	global $colors,$AG_QUICK_SEARCHES;

	$types = array_keys($AG_QUICK_SEARCHES);
	$current_type = orr($_REQUEST['QSType'],$types[0]);
	$current_type = array_key_exists($current_type,$AG_QUICK_SEARCHES) ? $current_type : $types[0];
	foreach ($AG_QUICK_SEARCHES as $type => $label) {
		$label = (!$label and $type==AG_MAIN_OBJECT_DB) ? ucfirst(AG_MAIN_OBJECT) : $label;
		$label = orr($label,ucfirst($type));
		$tabs .= div(hlink('#',$label,'','onclick="switchQuickSearch(\''.$type.'\'); document.getElementById(\'QuickSearchText\').value = (document.getElementById(\'QuickSearchText\').value==\''.AG_DEFAULT_QUICKSEARCH_TEXT.'\') ? \'\' : document.getElementById(\'QuickSearchText\').value; document.getElementById(\'QuickSearchText\').focus(); return false;" class="'.$type.'"')
				 ,'QuickSearch'.$type,' class="QuickSearchTab'.(($current_type==$type) ? '' : 'Inactive').'"');
	}
	global $off;
	$default = $_REQUEST['QuickSearch'];
	if (get_magic_quotes_gpc()) {
		$default = stripslashes($default);
	}
	$form = formto($off.'search.php','')
		. formvartext('QuickSearch',orr($default,AG_DEFAULT_QUICKSEARCH_TEXT),
				  'id ="QuickSearchText" onclick="this.value = (this.value==\''.AG_DEFAULT_QUICKSEARCH_TEXT.'\') ? \'\' : this.value"')
		. button('Go!')
		. hiddenvar('QSType',$current_type,'id="QuickSearchType"')
		. formend();

	$links = help('QuickSearch',null,'help',' class="fancyLink"',false,true) . ' | ' .hlink($GLOBALS['agency_search_url'],'advanced search');
	return div($tabs . div( $form . $links, 'QuickSearchBox',' class="'.$current_type.'"'), 'QuickSearch');

}

function send_quick_search_js()
{
	//send required js to head
	global $AG_HEAD_TAG,$AG_QUICK_SEARCHES;
	$types = array_keys($AG_QUICK_SEARCHES);
	foreach ($types as $type) {
		$searches[] = enquote1($type);
	}
	$searches = '['.implode(',',$searches).']';
	$current_type = 	$current_type = orr($_REQUEST['QSType'],$types[0]);
	$AG_HEAD_TAG .= Java_Engine::get_js('var whichQuickSearch=\''.$current_type.'\';'
							."\n".'var QuickSearches='.$searches.';');
}

function choose_search_type( $short="" )
{
// Short option for SQL pick lists, where
// only equals/doesn't equal will likely
// make sense.

	return( 
	selectitem( "na", "(not used in this search)" )
	. ($short ? 
	(selectitem( "equal", "Equals")
	. selectitem( "notequal", "does not Equal")
	)
	:
	(selectitem( "sub", "Contains" )
	. selectitem( "start", "Starts with" )
	. selectitem( "end", "Ends with" )
	. selectitem( "equal", "Equals")
	. selectitem( "notequal", "does not Equal")
	. selectitem( "greater", "is Greater than")
	. selectitem( "less", "is Less than")
	. selectitem( "greatereq", "is Greater than or equal to")
	. selectitem( "lesseq", "is Less than or equal to")
	)
	));
}

function search_type_to( $varname, $short="" )
{
	return( cell(
	selectto( $varname )
	. choose_search_type( $short )
	. selectend() ));
}

function search_row( $field_name, $field_label="", $hint="", $type="",$sql="" )
{
// returns a row for search with variables assigned to
// field_nameType and field_nameText
// I.E., pass a field_name of "Ethnicity",
// It will post variables of EthnicityType (search type)
// and EthnicityText (search text/value)

	$field_label = $field_label ? $field_label : $field_name;
	$row = cell( $field_label )
	. search_type_to( $field_name . "Type",($type=="sql") );

// (little hack--if type equals sql, search_type_to does short pick list)

	switch ($type)
	{
		case "sql" :
			$row .= cell(selectto( $field_name . "Text" )
				. do_pick_sql( $sql )
				. selectend());
			break;
		case "date" :
			$row .= cell( formdate( $field_name . "Text" ));
			break;
		default :
			$row .= cell( formvartext( $field_name . "Text" ));
	}
	return row( $row . cell( $hint ));
}

function build_where_string( $fields, $alias )
// This function builds the "WHERE" string to 
// be used in the query.
{
	foreach ($fields as $name)
	{
		$type=$name."Type";
		$text=$name."Text";
		global $$type, $$text;
//outline("Name: $name<br>  Text: $text: "
//. $$text ."<br>Type: $type: " . $$type);

		if (isset($$type) && isset ($$text) )
		{
			switch ($$type)
			{
				case "sub":
					$operator = "ILIKE";
					$$text = "%" . $$text . "%";
					break;
				case start:
					$operator = "ILIKE";
					$text = $text . "%";
					break;
				case "end":
					$operator = "ILIKE";
					$text = "%" . $text;
					break;
				case "equal":
					$operator = "=";
					break;
				case "notequal":
					$operator = "<>";
					break;
				case "greater":
					$operator = ">";
					break;
				case "less":
					$operator = "<";
					break;
				case "greatereq":
					$operator = ">=";
					break;
				case "lesseq":
					$operator = "<=";
					break;
				case "na":
				//	$operator = "1=1";
				//	$$text = "";
					$name = "";
					break;
				default:
					outline("Warning--ignoring unknown search type: " 
					. $$type );
					$name = "";
					break;
			}	
			if ($name)
			{
				// This if statement is all a hack to make staff search work again
				// after removing the name_last field from the staff table.
				// See bug 4715.
				if (($alias=="staff") && ($name=="name_full"))
				{
					$name = "staff.name_last || ', ' || staff.name_first";
				}
				else
				{
					$name = $alias . "." . $name;
				}
				$$text = "'" . sqlify($$text) . "'";
				$where .= "AND " . $name  . " " .  $operator . " " 
					. $$text . "\n";
			}
		}	
	}
	$where = substr($where,4,strlen($where)-4); // take off first "AND "
	return $where;
}		


function search( $fields, $select_sql, $alias, $show_heads_func, $noun="record", $plural_noun="records",$allow_other="N")
{
// This page performs a search based on global variables

// The fields that can be searched on are defined in the $fields array.
// In order for a field to be searched, the field name must be included
// in this array.  Additionally, the variables $fieldnameText and
// $fieldnameType (i.e., for "Gender" field "GenderText" and "GenderType"
// must be passed.  The Text is the value to search for, and the Type
// is the type of comparison test being made (see the switch on "type"
// in build_where_string for the possible types.

// the following additional variables are used:
// $order : sort order field
// $limit : limit number of queries
// $revorder : boolean to reverse sort order
// $showsql : display sql query

// $select_to_url : if this is set, it will enable the selection
// of displayed clients, with the results being passed to this url.


	global $order, $limit, $revorder, $showsql, $select_to_url;

	$select_to_url = orr($select_to_url,$_REQUEST['select_to_url']);
	$where = build_where_string( $fields, $alias );

	$query= $select_sql . "\n";

// Note this depends on the select sql having (and ending with)
// a WHERE clause (even if only where 1=1).
	$query .= $where ? " AND $where" : "";
	$query .= $order ? (" ORDER BY $order" . ($revorder ? " DESC" : "")) : "";
	$query .= $limit ? " LIMIT $limit " : "";
//	$query = eregi_replace("'", "\"", $query);
//	$query = stripslashes($query);

	$a=sql_query( $query ) or sql_die("Couldn't query<br>Query was: $query");
	$count = sql_num_rows($a);
	if ($count > 0 )
	{
		$result .= oline(bigger("The following $count " . (($count==1) ? $noun : $plural_noun )
		. " matched your search:"),2);
		$result .= oline( $show_heads_func( $a , $select_to_url, $allow_other ))
		. oline("Click on a " . $noun . " to see a full entry");
	}
	else
	{
		$result = oline("Sorry, no $plural_noun matched your search criteria",2);
		$result .= oline( $show_heads_func( $a , $select_to_url, $allow_other ));
	}

	if ($showsql)
	{
		$result .= hrule()
		. oline("Query is $query");
	}
	return $result;
}

function iandr_search()
{
// This performs an i & r agency search based on global variables
// like client_search, but currently much simpler.

	global $iandr_select_sql, $agency_nameText, $agency_nameType;

	//I am hacking this to customize the query, after not being able to get the search function to easily accept 'OR' queries
	// JH 11/11/2004
	$agency_nameText = orr($agency_nameText,$_REQUEST['QuickSearch']);
	if (!get_magic_quotes_gpc()) {
		$agency_nameText = addslashes($agency_nameText);
	}

	$agency_nameType = 'sub'; //$_REQUEST['agency_nameType'];

	$sql = $iandr_select_sql . ' AND (agency_name ILIKE \'%'.$agency_nameText.'%\' OR svc_info ILIKE \'%'.$agency_nameText.'%\')';
	$res = agency_query($sql);
	$count = sql_num_rows($res);
	if ($count > 0) {
		$result .= oline(bigger("The following $count " . (($count==1) ? 'agency' : 'agencies' )
						. " matched your search:"),2);
	} else {
		$result = oline("Sorry, no $plural_noun matched your search criteria",2);
	}
	$result .= oline( show_iandr_heads( $res ));

	return $result;
}

function log_search()
{
	
	$query_string = $_REQUEST['QuickSearch'];
	if (is_numeric($query_string) && ($query_string < AG_POSTGRESQL_MAX_INT)) {
		$log = sql_num_rows(get_log($query_string));
		if ($log > 0) {
			global $log_page;
			header('Location: '.$log_page . '?action=show&id='.$query_string);
			page_close($silent=true);
			exit;
		}
	}
	$filter=array();
	// this filter will match names either in "first last" or "last, first"
	foreach (array('log_text','subject') as $field) {
		$filter['ILIKE:'.$field] = '%'.$query_string.'%';
	}
	$filter = array($filter);
	$control = array_merge(array( 'object'=> ('log'),
						'action'=>'list',
						'list'=>array('filter'=>$filter),
						'page'=>'display.php'
						),
				     orr($_REQUEST['control'],array()));
	$result = call_engine($control,'control',true,true,&$TOTAL,&$PERM);
	if (!$PERM) { return 'No Permissions'; }
	$sub = oline('Found '.$TOTAL.' results for '.bold($query_string),2);
	return $sub . $result;

}

function staff_search()
{
	$s = $_REQUEST['QuickSearch'];
	if (is_numeric($s) && is_staff($s)) {

		header('Location: ' . AG_STAFF_PAGE . '?id='.$s);
		page_close();
		exit;

	}

	$filter=array();
	// this filter will match names either in "first last" or "last, first"
	foreach (array('agency_project_code','agency_program_code',
			   'name_last','name_first','name_first||\' \'||name_last','name_last||\', \'||name_first') as $field) {
		$filter['ILIKE:'.$field] = '%'.$s.'%';
	}
	$filter = array($filter);
	$control = array_merge(array( 'object'=> ('staff'),
						'action'=>'list',
						'list'=>array('filter'=>$filter),
						'page'=>'display.php'
						),
				     orr($_REQUEST['control'],array()));
	$result = call_engine($control,'control',true,true,&$TOTAL,&$PERM);
	if (!$PERM) { return 'No Permissions'; }
	$sub = oline('Found '.$TOTAL.' results for '.bold($s),2);
	return $sub . $result;
}

function search_engine_object($obj,$id,$redirect=true)
{
	global $AG_ENGINE_TABLES,$engine;
	$len = strlen($obj);
	$found = array();
	foreach ($AG_ENGINE_TABLES as $o) {
		$t_o = substr($o,0,$len);
		if ($t_o === $obj) {
			$found[] = $o;
		}
	}
	if ( $redirect && (count($found) == 1) ) {
		$def =& $engine[$found[0]];
		$res = get_generic(array($def['id_field']=>$id),'','',$def);
		if (sql_num_rows($res) > 0) {
			header('Location: display.php?control[object]='.$found[0].'&control[action]=view&control[id]='.$id);
			page_close();
			exit;
		}
	} elseif (count($found) > 0) {
		foreach ($found as $o) {
			$def =& $engine[$o];
			$res = get_generic(array($def['id_field']=>$id),'','',$def);
			if (sql_num_rows($res) > 0) {
				$out .= oline(link_engine(array('object'=>$o,'id'=>$id),'View '.$o.' id #'.$id));
			}
		}
		return $out;
	}
	return 'No objects found for '.bold($obj.':'.$id);
}
?>
