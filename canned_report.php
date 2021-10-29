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

$quiet = true;
include 'includes.php';
$action = $_REQUEST['action'];

if ($report_id = $_REQUEST['report_id']) {
	if (!$report=get_report_from_db($report_id)) // fixme: just get_report()?
	{
		 $out .= alert_mark("Couldn't get report $report_id.");
		 $report_id=NULL;
	} else {
		$action = orr($action,'options');
	}
}

$navigation = array();
$main_reports_url = hlink(AG_REPORTS_URL.'?action=&report_id=','List Reports');

switch ($action) {
 case 'post_sql': //this is for generating an openoffice document from posted sql (via list)
	$dummy=agency_query("SET DATESTYLE TO SQL");
	$out .= report_generate_from_posted();
	break;

 case 'generate':
	$report['variables']=report_parse_var_text($report['variables']);
	 $title  = 'Report - ' . $report['report_name'];
	 if (!report_valid_request($report,$mesg)) {
		 $out .= div($mesg,'','class="error"');
	 } else {
			 // navigate back to user options
			 array_push($navigation,hlink($_SERVER['PHP_SELF'].'?action=options&report_id=' . $report_id,'Change options for this report'));

			 // valid request, generate report
		       $dummy=agency_query('SET DATESTYLE TO SQL');
			 
			 track_report_usage($report_id,$report['report_title']);
		
			 $out .= report_generate($report);
			 break;
	 }

 case 'options' : // set user options for report
	$report['variables']=report_parse_var_text($report['variables']);
	$title = 'Select Options ('. $report['report_title']. ')';
	// navigate back to list of reports
	array_push($navigation,$main_reports_url);
	// check permissions
	$perm_type = $report['report_permission'];
	if (!be_null($perm_type) && (!has_perm($perm_type))) {
		 $out .= alert_mark('You do not have the proper permissions ('.$perm_type.') to run this report');;
		 break;
	 }
	 $out .= report_user_options_form($report);
	 break;

 default: // report list
	 $out .= report_generate_menu($navigation);
}

$out = html_heading_1($title) . $out ;
$commands = array(bottomcell(implode(oline(),$navigation),' class="pick"'));
agency_top_header($commands);
out($out);
page_close();
?>
