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

// clean _GET and _POST
engine_control_array_security();

if (!$control = dewebify_array(unserialize_control($_REQUEST['control']))) { 
	//display.php generally uses the 'control' variable
	//if it is missing, this would indicate a generic child-record handling event
	$control = $_SESSION['LAST_CALLED_CONTROL_VARIABLE'];
} else {
	foreach ($engine['control_pass_elements'] as $key) {
		$tmp_control[$key] = $control[$key];
	}
	$_SESSION['LAST_CALLED_CONTROL_VARIABLE'] = $tmp_control;
}

$stuff=engine($control);

$formatted_title = $stuff['title'];
$commands   = $stuff['commands'];
$message    = $stuff['message'];
$help       = $stuff['help'];
$output     = $stuff['output'];
$menu       = $stuff['menu'];

$title = strip_tags($formatted_title);

agency_top_header($commands);

if ($menu) {
	out(div($menu,'engineMenu'));
}

if ($message) {
	$message = oline().box(bigger(red($message)));
}

out(div($formatted_title . $message . $help . $output,'engineMain'));

page_close();

?>
