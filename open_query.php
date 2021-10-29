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

function process_open_query($result,$extra_vars="")
{
	//fixme: this should be merged with report_generate_openoffice()

	$extra_vars=orr($extra_vars,array());
 	$out_form = oo_get_upload_template('userfile');
 	if ($type = is_oo_writer_doc($out_form)) {
		$oo=oowriter_merge($result,$out_form);
 	} elseif ( ($type = is_oo_calc_doc($out_form)) or !$out_form) { 
 		$type = orr($type,'calc');
		$oo=office_merge($result,$out_form,$extra_vars);
 	} else {
 		out(alert_mark('Unknown OpenOffice Type: '.$out_form));
 		exit;
 	}
	office_mime_header($type);
	out($oo->data());
	page_close($silent=true); //no footer on oo files
	exit;
}

$quiet="Y";
include "includes.php";
include "openoffice.php";
include "zipclass.php";
if ( !has_perm('open_query') ) {
	agency_top_header();
	out(alert_mark('Sorry, you are not authorized to access this page'));
	page_close();
	exit;
}

$title="OPEN QUERY INTERFACE PAGE";

//$_SESSION['open_query_sql'] = $sql = orr(trim(dewebify($_REQUEST["sql_query"])),$_SESSION['open_query_sql'],false);
$sql = trim(dewebify($_REQUEST["sql_query"]));
if ($sql)
{

	$format = dewebify($_REQUEST['format']);
	$format = !be_null($_FILES['userfile']['tmp_name']) ? 'oo_merge_user' : $format;
	$security_passed = is_safe_sql($sql,$out);
	if ($security_passed) {
		switch ($format) {
		case 'oo_merge_user':
		case 'oo_merge':
			if ($result = sql_query($sql)) {
				process_open_query($result,array("HEADER-LABEL"=>"Spreadsheet Generated from Open Query:\n$sql"));
			}
			break;
		case 'sql_data_csv':
		case 'sql_data_tab':
		case 'sql_dump_full':
		case 'sql_dump_copy':
		case 'sql_dump_inserts':
			$out .= report_generate_export($sql,$format); // if succesful, this will exit the script
		case 'screen':
		default:
			// Tweak for friendly date format.  Only adding to screen,
			// because I don't want to break anything else!
			$dummy=agency_query("SET DATESTYLE TO SQL");
			$control = array(
					     'object'=>'generic_sql_query',
					     'action'=>'list',
					     'sql'=>$sql);
			$out .= call_engine($control,'',$no_title=false,$no_messages=false,$tot,$perm);
		}
	}
}

$out .= formto($_SERVER['PHP_SELF'],'file_form','enctype=multipart/form-data')
     . selectto('format')
     . selectitem('screen','Print results to screen')
     . selectitem('oo_merge','Generate OO spreadsheet')
/*
     . ( has_perm('sql_dump') 
	   ? ( selectitem('sql_dump_inserts','SQL Dump (insert commmands)') 
		 . selectitem('sql_dump_full','SQL Dump (column insert commands)') 
		 . selectitem('sql_dump_copy','SQL Dump (copy commands)')  
		 . selectitem('sql_data_csv','CSV file')
		 . selectitem('sql_data_tab','Tab-delimited file') )
	   : '')
*/
	. report_export_items()
     . selectend() . button('Process Query')
     . oline(formtextarea("sql_query",$sql,"",120,40))
     . hiddenvar('MAX_FILE_SIZE',3000000)
     . oline('Upload template (optional): '.formfile("userfile"))
     . button('Process Query')
     . formend()
     . bold(oline("Type (or paste) an SQL query in the box above")
		. oline("Press the button to generate a spreadsheet file"));
     
agency_top_header();
headline($title);
out($out);
page_close();

?>
