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

include "includes.php";
headtitle("AGENCY Feedback Page");
$text="
We want to know what <EM>you</EM> think!!!<br>
Are these pages useful?<br>
Do they work well?<br>
Do you have suggestions on how to improve it, or a wishlist of features you'd like to see?<br>
Please submit any and all comments and suggestions into this page.<br>
Your feedback will be very helpful as we continue to develop AGENCY.
";

$action = $_REQUEST['action'];
if ($action=="Submit")
{
	global $feedback_table,$feedback_table_post;
	$result=agency_query(sql_insert($feedback_table_post,array("feedback_text"=>dewebify($_REQUEST["comments"]),"added_by"=>$UID,"changed_by"=>$UID)));
	if ($result)
	{
		headtitle("Comments Accepted.  Thank you");
		$action="view";
	}
	else
	{
		headtitle("Error posting feedback");
		outline("There was a problem trying to save your feedback.  Sorry for the inconvenience.  Please try again later");
		sql_warn("This is what the server reported with the following query $post");
	}
	outline("");
}
if ($action=="view")
{
	$feed_query="SELECT * FROM $feedback_table ORDER BY added_at DESC";
	$feeds = sql_query( $feed_query ) or sql_die( $feed_query );
	outline( bigger("If you have comments or feature requests, please " . hlink($_SERVER['PHP_SELF'],"submit some feedback") ."."),2 );
	outline( bigger("If something is broken, please " . link_file_bug("file a bug report")."." ),2);
	outline(bigger(bold("Here is what other people have said about AGENCY:")),2);
	if (sql_num_rows($feeds)==0 )
	{
		Headline("Gosh darn it, there seems to be no feedback!");
	}
	else
	{
		for ($x=0;$x<sql_num_rows($feeds);$x++)
		{
			$old_feed = $feed;
			$feed = sql_fetch_assoc($feeds);
			if  ($feed["added_by"]==$old_feed["added_by"]
			&& $feed["feedback_text"]==$old_feed["feedback_text"])
			{
			//	outline(smaller("(duplicate omitted)"));
				continue; // skip loop if duplicate
			}
			outline( staff_link( $feed["added_by"] ) . " wrote the following on " 
				. dateof($feed["added_at"]) . " @ " . timeof($feed["added_at"]) . ": " 
				. smaller("(posting ID #" . $feed["feedback_id"] . ")"),2);
			if( in_array($feed["added_by"], array(83, 647, 543, 923) ) )
			{
				outline(box(bigger(bold(webify($feed["feedback_text"]))),$colors['staff']),2 );
			}
			else
			{
				outline( bigger(bold(webify($feed["feedback_text"]))),2 );
			}
			out(hrule() );
		}
	}
	outline( $text,2 );
	outline( bigger("Now would be a good time to " . hlink($_SERVER['PHP_SELF'],"submit some feedback of your own") . "!!") );
}
else
{
	outline(hlink($_SERVER['PHP_SELF'] . "?action=view","View Feedback"),2);
	outline( $text, 2);
	outline( bigger("If something is broken, please " . link_file_bug("file a bug report")."." ),2);

	out(formto($_SERVER['PHP_SELF'])
	. "Enter your comments here:" . "<br>" 
	. formtextarea("comments","","wrap=physical") . "<br><br>"
	. oline("You are logged in as " . staff_link($UID),2)
//	. "Enter your name here (optional): " 
//	. formvartext("name") . "<br><br>" 
	. button("Submit")
	. hiddenvar("action","Submit")
	. formend());
}
outline("");
page_close();

?>
