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

// Display a staff member
// cn passed as variable

$quiet = true;
include 'includes.php';
agency_top_header(quick_searches());

$action = $_REQUEST['action'];

$ID = orr($_REQUEST['id'],$_SESSION['IANDR_ID']);
$_SESSION['IANDR_ID']=$ID;

if (!$ID) {

	out(alert_mark(webify('No ID passed to I&R display')));

} else {

	if ($action=='Post') {


		$post['agency_id'] = $ID;
		$post['comment'] = dewebify($_REQUEST['comment']);
		$post['added_by'] = $UID;
		post_iandr_feed( $post );
	}

	html_header("I&R Agency Display");

	out( iandr_display( $ID ) );
	out( show_iandr_feed( $ID ) );
	out( get_iandr_feed( $ID ));
}
page_close();

?>
