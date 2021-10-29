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
 * System variables available for merging into OO output
 * Should be in agency_config, but UID not available yet.
 */

$AG_OPENOFFICE_SYS_VARS = array(
    'confidential' => confidential('',0,'TEXT'),
    'staff_id'=>$GLOBALS['UID'],
	"today"=>dateof('now'),
	'USER'=>$GLOBALS['NICK'],
	'now'=>datetimeof(''), 
	'UID'=>$GLOBALS['UID']);

function ooify($value)
{
       $cg_oo = $GLOBALS['AG_OPEN_OFFICE_TRANSLATIONS'];
       return str_replace(array_keys($cg_oo),array_values($cg_oo),htmlspecialchars($value,ENT_QUOTES));
}

function xml_parse_row( $row )
{
// get the contents of a row, and return an array of cells
	$x=0;
	$row_start_regexp = "<table:table-row\s?[^<]*>";
	$cell_start_regexp = "<table:table-cell\s?[^<]*>";
	$cell_empty_regexp = "<table:table-cell\s?[^<]*\/>";
	$row_end_regexp = "<\/table:table-row>";
	$cell_end_regexp = "<\/table:table-cell>";
	while ($row) // parse until gone
	{
		if ( ! preg_match("/^($cell_empty_regexp)(.*)/i",$row,$matches))
		{
			preg_match("/^($cell_start_regexp.*?$cell_end_regexp)(.*)/i",$row,$matches);
		}
		$cells[$x++]=$matches[1];
		$row=$matches[2];
	}
	return $cells;
}

function xml_parse_cell( $cell )
{
// Take a cell, and parse it into array of tags / content
	$tag_regexp = "<.*?>";
	$text_regexp = ".*?";
	$x=0;
	while ($cell)
	{
		if ( ! preg_match("/^($tag_regexp)(.*)/i",$cell,$matches))
		{
			preg_match("/^($text_regexp)(<.*)/i",$cell,$matches);
		}
		$contents[$x++]=$matches[1]; 
		$cell=$matches[2];
	}
	return $contents;
}

function xml_parse_tag( $tag )
{
// Take a tag, and parse it into array of attributes (inc. name)
	while ($tag <> "<>")
	{
		preg_match('/^<(.[^ >]*)[ ]?(.*)?>$/',$tag,$matches);
		$attr=$matches[1];
		$tag="<" . $matches[2] . ">";
		$value="";
		if (preg_match('/^(.*?)(="?(.*?)"?)?$/',$attr,$matches))
		{
			$attr=strtolower($matches[1]);
			$value=$matches[3];
		}
		$tags[$attr]=$value;
	}
	return $tags;
}

function xml_assemble_tag( $attrs )
{
// take an array of tag attributes, and assemble a tag
	foreach( $attrs as $key=>$value)
	{
		$str[$key]=$key . ($value ? "=\"$value\"" : "");
	}
	return "<" . implode(" ",$str) . ">";
}

function xml_assemble_cell( $tags )
{
// take an array of tags, and assemble a tag
	return implode("",$tags) . "\n";
}

function unzip( $zip_file )
{
	if (!function_exists('zip_open')) { //check for php-zip libraries
		die('zip functions appear to be missing from PHP');
	}

	if (!is_readable($zip_file)) {
		log_error('Function unzip() doesn\'t have access to '.$zip_file);
		page_close($silent=true);
		exit;
	}
	$zip = zip_open($zip_file);

	if ($zip) {
    	while ($zip_entry = zip_read($zip)) 
		{
	        if (zip_entry_open($zip, $zip_entry, "r")) 
			{
				$zip_files[zip_entry_name($zip_entry)]= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
        	    zip_entry_close($zip_entry);
	        }
    	}
	}
	zip_close($zip);
	return $zip_files;
}


function oo_preg_pattern( $pattern )
{
	// silly little function to wrap patterns for preg_replacing
	return "/\\\${$pattern}/";
}

function oo_preg_value( $value )
{
	// silly little function to wrap values for preg_replacing
	return preg_replace('/\n/s','</text:p><text:p>',htmlspecialchars(str_replace('$','\\$',$value)));
}

function oo_merge_set( $data_recs, $template_strings, $group_field="" )
{
		global $DEBUG;
		$result=array();
        $sys_vars=$GLOBALS['AG_OPENOFFICE_SYS_VARS'];
        $tot_recs=is_array($data_recs) ? count($data_recs) : sql_num_rows($data_recs);
        for ($count=0; $count < $tot_recs; $count++)
        {
			$template_values=array();
   	        $x = is_array($data_recs) ? array_shift($data_recs) : sql_fetch_assoc($data_recs);
			$x = array_merge($sys_vars,$x);
			$id = $x[$group_field];
			uksort(&$x,'strlen_cmp');
			$keys = array_map("oo_preg_pattern",array_keys($x));
			$values = array_map("oo_preg_value",array_values($x));
			foreach ($template_strings as $template_string)
			{
				$match=preg_replace($keys,$values,$template_string);
/*
if(preg_match('/between/i',$match))
{
	outline(webify("Matched: $match"));
	outline(webify("template: $template_string"));
	outline(red("Keys: " . dump_array($keys)));
	outline(green("Values: " . dump_array($values)));
}
*/
				$result["$id"].=$match;
			}
		}
$DEBUG && outline(red("RESULT: " . dump_array($result)));
		return $result;
}

function oowriter_merge_new( $data_recs, $template, $data_eval="",$file_replace="",$group_field="donor_id" )
{

	if (!AG_OPEN_OFFICE_ENABLE_EXPORT) {

		die(AG_OPEN_OFFICE_DISABLED_MESSAGE);

	}

// This is a quick, dirty hack to create an oowriter file
// Create a oowriter file that serves as a template
// put $START and $END tags in (i.e., on on each line)
// Will be replaced with the data in data recs, as
// processed by $data_eval string
// eval should refer to data elements in $x, as in $x["field"]

// If eval is null, it will loop through all the field in the record
// and replace $field with value, a la the current report writer

// eval doesn't work now with re-write
// it could be added back in, but we haven't been using it.

		$seps=array();
		$targs=array();
		// convert [legacy] result to array format:
		if (!is_array($data_recs))
		{
			$data_recs=array($data_recs);
		}
		$sets=count($data_recs);
		// Unpack file
        $file_replace=orr($file_replace,array());
        $zip_files=unzip(AG_TEMPLATE_DIRECTORY . "/$template");
        $contents=$zip_files["content.xml"];
		// Look for start and end tags
        if ( ! preg_match('/^(.*?)(\$START)(.*)(\$END)(.*?)$/s',$contents,$matches))
        {
                outline("I can't find \$START and \$END tags in the template file $template.  Unable to proceed");
                return false;
        }
        $doc_head=$matches[1];
        $doc_tail=$matches[5];
        $query_target=$matches[3];
        // Check for sub queries starting with $START.  ($REC-START and $REC-END OK as legacy)
        for ($x=0;$x<$sets;$x++)
		{
			if ( preg_match('/^(.*?)(\$(REC-)?START)(.*?)(\$(REC-)?END)(.*)$/s',$query_target,$matches))
			{
				array_push($seps,$matches[1]);
				array_push($targs,$matches[4]);
				$query_target=$matches[7];
			}
        }
		array_push($seps,$query_target); // final separator
		// do parent replacement first, special handling:
		$parent_recs=array_shift($data_recs);
		$sets--;
		if (is_array($parent_recs))
		{
			$save_parent_recs = $parent_recs;
		}
		else
		{
			$save_parent_recs=sql_fetch_to_array($parent_recs);
			$parent_recs=$save_parent_recs; // forces parent_recs to array, so that counter is not screwy
		}
		// Merge child records:
		for ( $x=0;$x<$sets;$x++)
		{
			$sep_values[$x]=oo_merge_set( $parent_recs, array($seps[$x]), $group_field );
			$recs=array_shift($data_recs);
			$child[$x]=oo_merge_set($recs, array($targs[$x]), $group_field );
		}	
		$sep_values[$x]=oo_merge_set( $parent_recs, array($seps[$x]), $group_field );
		// Assemble Document:
		$new_doc = $doc_head;
		for ($x=0;$x<count($save_parent_recs);$x++)
		{
			$id=$save_parent_recs[$x][$group_field];
			for ($y=0;$y<=$sets;$y++)
			{
				$new_doc .= $sep_values[$y][$id] . $child[$y][$id];
			}
			$new_doc .= $sep_values[$y][$id];
		}
		$new_doc .= $doc_tail;
        $zip_files["content.xml"] = $new_doc;
(0 or $DEBUG) && outline(webify("new contents = $new_doc"));
        $zip = new zipfile();
        foreach ($zip_files as $name=>$data)
        {
				//replace filenames (for photo attachments)
                if (array_key_exists($name,$file_replace))
                {
                        $data=$file_replace[$name];
                }
                $zip->addfile($data,$name);
        }
        return $zip;
}

function oowriter_merge( $data_recs, $template, $data_eval="",$file_replace="",$group_field="" )
{

	/*
	 * Create a oowriter file that serves as a template
	 * put $START and $END tags in (i.e., on on each line)
	 * Will be replaced with the data in data recs, as
	 * processed by $data_eval string
	 * eval should refer to data elements in $x, as in $x["field"]
	 *
	 */

	if (!AG_OPEN_OFFICE_ENABLE_EXPORT) {

		die(AG_OPEN_OFFICE_DISABLED_MESSAGE);

	}

	// If eval is null, it will loop through all the field in the record
	// and replace $field with value, a la the current report writer

	$file_replace=orr($file_replace,array());
	$zip_files=unzip(AG_TEMPLATE_DIRECTORY . '/' . $template);
	$contents=$zip_files['content.xml'];
	$styles=$zip_files['styles.xml'];

	if ( ! preg_match('/^(.*)(\$START)(.*?)(\$END)(.*)$/s',$contents,$matches)) {

		outline("I can't find \$START and \$END tags in the template file $template.  Unable to proceed");
		return false;
	}

	$head = $matches[1];
	$tail = $matches[5];
	$sep  = $matches[3];

	if (preg_match('/^(.*)?(\$SKIP-START)(.*)(\$SKIP-END)(.*)?$/s',$sep,$matches)) {

		$tail = $matches[5] . $tail;
		$skip_last=$matches[3];
		$sep=$matches[1];

	}

	// Check for $REC-START and $REC-END tags, for grouping
	if ( $group_field && preg_match('/^(.*)(\$REC-START)(.*?)(\$REC-END)(.*)$/s',$sep,$matches)) {

		$grp_head = $matches[1];
		$grp_tail = $matches[5];
		$sep      = $matches[3];

		if (preg_match('/^(.*)?(\$SKIP-REC-START)(.*)(\$SKIP-REC-END)(.*)?$/s',$sep,$matches)) {

			$grp_rec_tail=$matches[3];
			$grp_tail=$matches[5] . $grp_tail;
			$sep=$matches[1];

		}

    }

	/*
	 outline(webify("Here is the orig: $contents"));
	 outline("Here are my matches: " . dump_array($matches));
	 outline("Data rec count: " . sql_num_rows($data_recs));
	 outline("Data eval = $data_eval");
	 outline("Group field = $group_field");
	 outline("Group head = " . webify($grp_head));
	 outline("Group tail = " . webify($grp_tail));
	 outline(webify("Sep = $sep"));
	*/

	$tot_recs = is_array($data_recs) ? count($data_recs) : sql_num_rows($data_recs);
	for ($count = 1; $count <= $tot_recs; $count++) {

		$x = is_array($data_recs) ? array_shift($data_recs) : sql_fetch_assoc($data_recs);
		$is_last=($count==$tot_recs);

		if ($count == 1) {

			/*
			 * Run this once, to replace in headers and footers
			 * You could use this to, for example, pass 'global vars' from query (runtime)
			 * That were the same for all records.
			 * Right now this will use the first record, but there should be no assumption
			 * that any particular record from the set will be the one evaluated.
			 * Example:
			 * SELECT 'HEADER' AS my_header_string,'SESSION ID' AS session_id,client_id, added_at FROM foo...
			 */

			foreach($x as $field=>$value) {

				$styles=preg_replace('/\$' . $field . '/',ooify($value),orr($styles,$styles));

			}

		}

		if ($group_field && ($x[$group_field]<>$old[$group_field])) { // Create new Header

			$new_group = true;
			$old_head  = $new_head;
			$old_tail  = $new_tail;

			if ($data_eval) {

				$new_head = ooify(eval( 'return ' . $data_eval . ';' )) . $grp_head;
				$new_tail = ooify(eval( 'return ' . $data_eval . ';' )) . $grp_tail;

			} else {

				$new_head = $grp_head;
				$new_tail = $grp_tail;

				foreach ($x as $field=>$value) {

					//outline("Field = $field, Value = $value");
					$new_head=preg_replace('/\$' . $field . '/',ooify($value),$new_head);
					$new_tail=preg_replace('/\$' . $field . '/',ooify($value),$new_tail);

				}

			}

		} else {

			$new_group = false;

		}

		if ($data_eval) {

			$new_rec = htmlspecialchars(eval( 'return ' . $data_eval . ';' )) . $sep;

		} else {

			$new_rec = $sep;

			foreach ($x as $field=>$value) {

				$new_rec = str_replace('$' . $field ,ooify($value),$new_rec);

			}

		}

		if (!$group_field) {

			$new_contents .= $new_rec;

		} elseif ( $is_last ) {

			if ($new_group) {

				// assemble previous group, plus new, last group
				$new_contents .= $old_head . $new_recs . $old_tail . $new_head . $new_rec . $new_tail;
				$new_recs=""; // shouldn't be needed

			} else {

				// get last record into group and assemble
				$new_recs .= $grp_rec_tail . $new_rec;
				$old_head = $new_head;
				$old_tail = $new_tail;
				$new_contents .= $old_head . $new_recs . $old_tail;
				$new_recs = "";

			}

		} elseif ($new_group) {

			// Assemble old group
			$new_contents .= $old_head . $new_recs . $old_tail;
			$new_recs = "";

		} else {

			// simply add new record within group
			$new_recs .= $grp_rec_tail;

		}

		$new_recs .= $new_rec;
		$old = $x;

		if ( !( $is_last || ($count==1) || (!$new_group) )) {

			$new_contents .= $skip_last;

		}

	}

	global $UID;
	$sys_vars=array('now'      => dateof('now') . ' ' . timeof('now'),
			    'today'    => dateof('now'),
			    'UID_NAME' => staff_name($UID),
			    'UID'      => $UID
			    );

	$new_full= $head . $new_contents . $tail;
	foreach( $sys_vars AS $key=>$value ) {

		$new_full=preg_replace('/\$' . "$key/i",ooify($value),$new_full);
		$styles=preg_replace('/\$' . "$key/i",ooify($value),$styles);

	}

	// outline(webify("new contents = $new_contents"));
	$zip_files["content.xml"] = $new_full;

	$zip_files["styles.xml"] = $styles;

	//	outline("Here is content.xml: " . webify($zip_files["content.xml"]));

	$zip = new zipfile();
	//outline("File replacement array = " . dump_array($file_replace));

	foreach ($zip_files as $name=>$data) {

		//outline("Processing $name");
		if (array_key_exists($name,$file_replace)) {

			//outline("Replacing $name");
			$data=$file_replace[$name];

		}

		$zip->addfile($data,$name);

	}

	return $zip;

}

function office_merge( $data_recs, $template="",$extra_vars=array())
{
	global $DEBUG;

	if (!AG_OPEN_OFFICE_ENABLE_EXPORT) {

		die(AG_OPEN_OFFICE_DISABLED_MESSAGE);

	}
	$template = orr($template,AG_OPEN_OFFICE_CALC_TEMPLATE);
	$default_template = preg_match('/'. AG_OPEN_OFFICE_CALC_TEMPLATE.'/',$template);
	$zip_files=unzip(AG_TEMPLATE_DIRECTORY . '/'. $template);
	$contents=$zip_files["content.xml"];
$DEBUG && outline("contents before: " . webify($contents));
	
	// replacement of extra vars
	// not sure if these should be done first, or last
	// newlines converted to OO format
	foreach( array_merge($extra_vars,$GLOBALS['AG_OPENOFFICE_SYS_VARS']) as $key=>$value) 
	//	foreach( $extra_vars as $key=>$value) 
	{
		// FIXME:  This is actually doing a strip extra new lines
		// and a newlines-> OO format conversion
		// This should be run through ooify
		// for the NL conversion, and to pick up binary data
		// a la bug 6009.
		// For the moment I'm not touching this, simple as it is
		// as I don't feel like breaking anything else today.
		$contents=preg_replace('/\$' . "$key/",
					preg_replace('/\n\n?/s','</text:p><text:p>',htmlspecialchars($value)),$contents);
	}
$DEBUG && outline(red("contents after: " . webify($contents)));
	$row_start_regexp = "<table:table-row\s?[^<]*>";
	$cell_start_regexp = "<table:table-cells?[^<]*>";
	$row_end_regexp = "<\/table:table-row>";
	$cell_end_regexp = "<\/table:table-cell>";
    $blank_cell_regexp="<table:table-cell.*?\/>";
    $cell_regexp = "($cell_start_regexp.*?$cell_end_regexp)|$blank_cell_regexp";
	$data_regexp = "/(.*)(($row_start_regexp)($cell_start_regexp(.*?\\\$DATA-ROW.*?)$cell_end_regexp)(($cell_regexp)*$row_end_regexp))(.*)/i";
	$header_regexp="/(.*)(($row_start_regexp($cell_regexp)*)($cell_start_regexp(.*?\\\$HEADER-CELL.*?)$cell_end_regexp)($cell_regexp)*($row_end_regexp))/i";
	$header_regexp="/(.*)($cell_start_regexp.*?\\\$HEADER-CELL.*?$cell_end_regexp)(.*)/i";
	if ($default_template)
	{
       $data_regexp=preg_replace("/ROW/","CELL",$data_regexp);
        if (! preg_match($data_regexp,$contents,$matches))
        {
            outline("Sorry, I tried to use the default template, but couldn't find \$DATA-CELL in the template");
			outline(blue(webify("Contents = $contents")));
			outline(red(webify("Regexp = $data_regexp")));
            return false;
        }
	}
	preg_match($data_regexp,$contents,$matches);
	$data_template_start=$matches[1];
	$data_template_end=$matches[9];
	$data_template_row=$matches[4];
	$data_template_row_start=$matches[3];
	$data_template_row_end=$matches[6];
	$data_template_cells = xml_parse_row($matches[4]);
$DEBUG && outline((!$default_template ? "Not " : "") . "Using Default Template");
$DEBUG && outline("Matches: " . dump_array($matches));
$DEBUG && outline("data_template_cells: " . dump_array($data_template_cells));

$DEBUG && outline(red(webify("Before doing header cell, data_template_start=$data_template_start")));
$DEBUG && outline(red(webify("Header regexp = $header_regexp")));
	if (preg_match($header_regexp,$data_template_start,$matches))
    {
        $data_template_start=$matches[1];
//        $header_template_row_start=$matches[3];
        $header_template_cell=$matches[2];
        $header_template_row_end=$matches[3];
$DEBUG && outline(blue("Here are my HEADER-CELL matches: " . dump_array($matches)));
    }
	else
	{
$DEBUG && outline(blue("No HEADER-CELL matches found"));
	}
	// parse data cells into array
	for ($y=0;isset($data_template_cells[$y]);$y++) //go through cells
	{
		$name="dummy";
		$cell_contents = xml_parse_cell($data_template_cells[$y]);
$DEBUG && outline(blue("Cell contents: " . dump_array($cell_contents)));
		// go through tags in cell
		// look for varname, field type, and extract table cell tag.
		foreach ($cell_contents as $key=>$value) 
		{
	 		// the value, $tag field
			if (preg_match('/^[$](.*?)( +[(]?(.*?)[)]?)?$/',$value,$matches))
			{
				$name = $matches[1];
				$field_formats[$name] = $matches[3];
				$cell_contents[$key]='$' . $name;
			}
			elseif (preg_match('/^<table:table-cell.*?>/i',$value)) // the table-cell tag
			{
				$table_cell_tag=$cell_contents[$key]; // save for later processing
				$table_cell_key=$key;
			}
		}
$DEBUG && outline("Name=$name");
		// create a date template cell for each format
		$string_tags = xml_parse_tag($table_cell_tag);
		$date_tags = $string_tags;
		$number_tags = $string_tags;
		$currency_tags = $string_tags;
		$blank_tags = $string_tags;

		// value types	
		$date_tags["table:value-type"]="date";
		$number_tags["table:value-type"]="float";
		$currency_tags["table:value-type"]="currency";

		// The _date marks the variable for needing replacement w/ date in ISO format
		// literal $, not variable, for later replacement
		$date_tags["table:date-value"]= '$' . $name . '_date'; 
		$number_tags["table:value"]= '$' . $name;
		$currency_tags["table:value"]= '$' . $name; 
		unset($blank_tags["table:value"]);

		// assemble tags into cells, and cells into strings
		foreach (array("string","date","number","currency","blank") as $a)
		{
			$b=$cell_contents; // new working copy
			$tag_ar = $a . "_tags";
			$b[$table_cell_key]=xml_assemble_tag($$tag_ar);
			$c[$a]=xml_assemble_cell($b);
		}
		$parsed_cells[$name]=$c;
		unset($c);
$DEBUG && outline(red("Parsed Cells here: " . dump_array($parsed_cells["DATA-CELL"])));
	}
	// construct data table here:
	$tot_recs = is_array($data_recs) ? count($data_recs) : sql_num_rows($data_recs);
	for ($x=0;$x<$tot_recs;$x++)
	{
$DEBUG && outline("Looping through data");
		set_time_limit(30);

		$rec=is_array($data_recs) ? array_shift($data_recs) : sql_fetch_assoc($data_recs);
//		foreach($field_formats as $field=>$format)
		foreach($rec as $field=>$value)
		{
//			$value = $rec[$field];
			// add backslashes to $ so they don't misfire in preg_replace;
			$value = preg_replace('/\$/s','\\\$',trim($value));
			$format = $field_formats[$field];
			if ((!$value) && (!is_numeric($value)))
			{
					$format="blank";
			}
			elseif (!$format) // if not explicitly set, try to determine from value
			{
				if (is_numeric($value) && (!preg_match('/^0[^\.]+/',$value)))
				{
					$format="number";
				}
				elseif (dateof($value) && (!is_numeric($value)))
				{
					$format="date";
				}
				else
				{
					$format="string";
				}
			}
			$dt_rpl='/[$]'.$field.'_date/i';
			$t_rpl='/[$]'.$field.'/i';
            $def_rpl='/[$]DATA-CELL/i';
            $def_dt_rpl='/[$]DATA-CELL' . "_date/i";
            $head_rpl='/[$]HEADER-CELL/i';
			// date in table-cell tag (act. value)

           if (!$default_template)
            {
                $stuff=preg_replace($dt_rpl,dateof($value,"SQL"),$parsed_cells[$field][$format]);
                // the remaining replacements
                $row_output.=preg_replace($t_rpl,htmlspecialchars($value,ENT_QUOTES),$stuff);
            }
            else
            {
                $stuff=preg_replace($def_dt_rpl,htmlspecialchars(dateof($value,"SQL")),$parsed_cells["DATA-CELL"][$format]);
				if ($format=="date")
				{
					$value=dateof($value);
				}
$DEBUG && outline("Stuff=" . webify($stuff));
//                 $row_output.=preg_replace($def_rpl,htmlspecialchars($value,ENT_QUOTES),$stuff);
                   $row_output .= preg_replace($def_rpl,ooify($value),$stuff);

$DEBUG && outline(webify("VALUE = $value, DATA-CELL-TEMPLATE= " . $parsed_cells["DATA-CELL"][$format]));
                if ($x==0) // first time through, do header
                {
//outline(webify("My header row cell is $header_row_cell"));
//outline(webify("Now my header is: $header_stuff"));
//                     $header_stuff.=preg_replace($head_rpl,htmlspecialchars($field,ENT_QUOTES),$header_template_cell);
                    $header_stuff.=preg_replace($head_rpl,ooify($field),$header_template_cell);
                }
            }
		}
$DEBUG && outline(webify("row output = $row_output"));
		$table_output .= $data_template_row_start . $row_output . $data_template_row_end;
		$row_output="";
	}
	$header_stuff = $header_stuff . $header_template_row_end;
	$zip_files["content.xml"]=$data_template_start . $header_stuff . $table_output . $data_template_end;
	if ($GLOBALS["DEBUG"])
	{
		outline("Here is content.xml: " . webify($zip_files["content.xml"]));
	}
	$zip = new zipfile();
	foreach ($zip_files as $name=>$data)
	{
		$zip->addfile($data,$name);
	}
	return $zip;
}

function office_mime_header($type="writer")
{
	switch ($type) {
		case "writer" :
			$ext = AG_OPEN_OFFICE_WRITER_TYPE_DEFAULT;
			break;
		case "calc" :
			$ext = AG_OPEN_OFFICE_CALC_TYPE_DEFAULT;
			break;
		case "idcard" :
			$ext = "card";
			$text="application/idcard";
			break;
	default:
		$ext = $type;
		$text = in_array($type,array('odt','sxw')) ? 'application/vnd.sun.xml.writer' : 'application/vnd.sun.xml.calc';
	}
	if ($type=="idcard") {
		header ( "Content-type: idcard/idcard");
		header ( "Content-Disposition: attachment; filename=idcard.idcard" );
		header ( "Content-Description: AGENCY Generated ID Card" );
	} else {
		header ( "Content-type: " . orr($text,"application/vnd.stardivision.$type"));
		header ( "Content-Disposition: attachment; filename=openoffice.$ext" );
		header ( "Content-Description: AGENCY Generated Open Office Data" );
	}
	return;
}

function oo_get_upload_template($var_name)
{
	//returns the out_form variable, suitable for the office_merge() function
	global $UID;
	if ($_FILES[$var_name]['error']=='0') { //successful upload
		$name = $_FILES[$var_name]['name'];
		$type = array_pop(explode('.',$name));
		$file = '/tmp/'.$UID.'AgencyTemplate.'.$type;
		$res = move_uploaded_file($_FILES[$var_name]['tmp_name'],$file);
		if (!$res) {
			out('Failed to move uploaded file');
			exit;
		}
	} elseif ($_FILES[$var_name]['error'] == UPLOAD_ERR_NO_FILE) { //no file uploaded
		$file = null;
	} else {
		out('File upload error '.$_FILES[$var_name]['error']);
		exit;
	}
	return $file;
}

function is_oo_writer_doc($file)
{
	if (preg_match('/.(sxw|odt)$/i',$file,$m)) {
		return $m[1];
	}
	return false;
}

function is_oo_calc_doc($file)
{
	if (preg_match('/.(sxc|ods)$/i',$file,$m)) {
		return $m[1];
	}
	return false;
}

?>
