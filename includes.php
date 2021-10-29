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

// all the includes needed, lumped together
// $offset to allow access from reports directory
//error_reporting(E_ALL); //use to find undefined variables
error_reporting(E_ALL ^ E_NOTICE);

// uncomment this line to disable AGENCY and give a "we're down" message instead;
// $agency_down = 'AGENCY is currently down for maintenance. Thanks for your patience.';
if ($agency_down){
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
	echo "<html>\n";
	echo "\t<head>\n";
	echo "\t\t<title>AGENCY Unavailable</title>\n";
	echo "\t</head>\n";
	echo "\t<body>\n";
	echo "\t\t<div style=\"margin: 50px;\">\n";
	echo "\t\t\t<h1>AGENCY Unavailable</h1>\n";
	echo "\t\t\t<div>$agency_down</div>\n";
	echo "\t\t\t<br />\n";
	echo "\t\t\t<br />\n";
	echo "\t\t</div>\n";
	echo "\t</body>\n";
	echo "</html>";
	exit;
}

ini_set('session.use_trans_sid',0);
//$off = $off ? $off : ".";


// Included files have been moved into the below file
include_once $off . 'command_line_includes.php'; //add any new files to this included file (which doesn't involve authentication).

// per the PHP manual for header, header's must come before any output.
// This code to disable caching lifted from the header manual page:
//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0
if (AG_OUTPUT_MODE === 'HTML') {
	header("Content-Type: text/html; charset=ISO-8859-1");
 } else {
	header("Content-Type: text;");
}

//link style sheet
$AG_HEAD_TAG .= Java_Engine::get_js("var printStyleSheetPath = '{$off}schema/print.css'");
link_style_sheet('layout.css','screen,print',' class="styleSheetBoth"');
link_style_sheet('agency.css','screen',' class="styleSheetScreen"');
link_style_sheet('print.css','print',' class="styleSheetPrint"');
link_style_sheet(AG_MAIN_OBJECT_DB.'.css','screen',' class="styleSheetScreen"');
link_style_sheet('object_reference.css','screen',' class="styleSheetScreen"');
link_style_sheet('custom.css','screen',' class="styleSheetScreen"');

link_javascript('agency.js');

require_once('jquery_includes.php');  //all the jquery stuff

// if the user has requested refreshing, then set the meta tag here
// Not sure what this is all about. As far as I know, we never pass a refresh variable... - JH 5/2/05
if ($refresh) {
	header("Refresh: $refresh");
}

//------checking authentication--------//
$AG_AUTH = new Auth();
$AG_AUTH->authenticate();

//INCLUDE ITEMS REQUIRING $engine ARRAY, or DB CONNECTION
//include AG_CONFIG_DIR . '/agency_config_post.php';

//------management for super-user identity switching------//
user_identity_management();


//-------non-output initialization------//

page_open();

$title = orr($title,'AGENCY'); //set default title

//-------starting page output------//
if (!$quiet) {    
     	agency_top_header();
}

//------session var updates-------//

//demo mode//
$AG_DEMO_MODE = orr($_REQUEST['demoMode'],$_SESSION['AG_DEMO_MODE'],$AG_DEMO_MODE);
$AG_DEMO_MODE = $AG_DEMO_MODE===true || $AG_DEMO_MODE==='Y';
$_SESSION['AG_DEMO_MODE'] = $AG_DEMO_MODE;

?>
