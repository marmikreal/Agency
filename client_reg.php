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
$quiet="Y";
include "includes.php";
$title='Register a new '.AG_MAIN_OBJECT;

$action=$_REQUEST['action'];
$rec = $_REQUEST['rec'];
$def = get_def(AG_MAIN_OBJECT_DB);
foreach ($main_object_reg_search_fields as $x) {
	$rec[$x]=dewebify($rec[$x]);
}

if ($action=='search') {
	$out .= client_reg_search_verify();
} else {
      $out .= client_reg_form();
}

agency_top_header();
out(bigger(bold(oline($title)),2));
out($main_object_reg_subtitle ? $main_object_reg_subtitle : oline());
out($mesg);
out($out);
page_close();


?>
