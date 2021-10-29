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

//FIXME: Most, if not all of this could be done by engine

function ir_query($label = "",$format="full")
{
	$label=orr($label,"I&R Search");
	return tablestart("",'class=""') . row( cell ( formto('search.php')
								     . formvartext('QuickSearch',$_REQUEST['QuickSearch'])
								     . hiddenvar('QSType','iandr')
								     . button($label)
								     . formend()
								     ) . ( ($format=="full") ? cell( 
														formto("iandr_display.php")
														. " or " . selectto( "id" )
														. do_pick_sql( "SELECT agency_id as value, SUBSTRING(agency_name FROM 1 FOR 30) as label from agency WHERE in_sections <> 'see' order by agency_name" )
														. selectend()
														. button("Show this agency")
														. formend()
														):"")) . tableend();
}

function show_iandr( $iandr_entry )
{
	$row=sql_fetch_assoc( $iandr_entry );
	$result = oline(bigger($row["agency_name"]) . smaller( " (#" . $row["agency_id"] . ")" ));
	$result .= oline($row["address_street"])
	. oline($row["address_csz"])
	. oline("Phone: ".$row["phone_no"])
	. oline("Fax: " . $row["fax_no"])
	. oline("TDD: " . $row["tdd_no"])
	. ($row["Email"] ? oline("Email: " . $row["agency_email"]) : "")
	. oline($row["agency_url"] ? 
// for link, prepend "http://" if it's not there already, or else the link is local.
		hlink( 
		( strtolower(substr($row["agency_url"],0,7))=="http://" ? "" : "http://" )
		.$row["agency_url"], $row["agency_url"]) : "",2)

	. tablestart("","border=1 bgcolor=" . $GLOBALS['colors']['text'])
	. ($row["walk_in_location"] ? rowrlcell("Walk in Location: " , $row["walk_in_location"]) : "")
	. rowrlcell("Eligibility: " , $row["eligibility_info"])
	. rowrlcell("Services: " , $row["svc_info"])
	. rowrlcell("Access: " , $row["ref_and_access"])
	. rowrlcell("Contact: " , $row["contact_name"])
	. rowrlcell("Hours: " , $row["hours"])
	. rowrlcell("Fees: " , $row["fee_info"])
	. rowrlcell("Bus/Streets: " , $row["bus_lines"])
	. tableend()
	. oline("")
	. oline(smaller("(Entered on " . $row["update_date"] . ")" ))
	. hrule();
	return $result;
}

function iandr_display( $AgCode )
{
	global $iandr_select_sql;
	$iandr_sql = $iandr_select_sql . " AND agency_id=$AgCode";
	$entry=sql_query( $iandr_sql );
	return( show_iandr( $entry ) );
}

function show_iandr_heads( $iandrs )
{
        global $iandr_page;
 
	$result = tablestart("","border=1")
	. row(boldcell("#")
        . boldcell("Agency")
        . boldcell("Service Info") );
 
        for ($i=0; $i<sql_num_rows($iandrs); $i++)
                {
                $info = sql_fetch_assoc($iandrs);
                $result .= row(
                cell($i+1)
                .  cell( hlink($iandr_page . "?id=". $info["agency_id"],
                        $info["agency_name"]))
                . cell( $info["svc_info"] ) );
                }
        $result .= tableend();
	return $result;
}


function show_iandr_feed( $id )
{
	global $iandr_feed_table, $iandr_feed_id;
	$feed_query="SELECT * FROM $iandr_feed_table 
			WHERE $iandr_feed_id = $id
			ORDER BY added_at";
	$feeds = sql_query( $feed_query ) or sql_die( $feed_query );
	if (sql_num_rows($feeds)==0 )
	{
		return "no comments have been submitted for this agency";
	}
	$result ="";
	for ($x=0;$x<sql_num_rows($feeds);$x++)
	{
		$old_feed = $feed;
		$feed = sql_fetch_assoc($feeds);
		if ($feed["name"] || $feed["added_by"] )
		{
			$result .= ($feed["added_by"] ?
			staff_link( $feed["added_by"] ) :
			$feed["added_by"] ) . " wrote the following ";
		}
		$result .= oline("on " . dateof($feed["added_at"]) . " @ "
		. timeof($feed["added_at"]) . ": " 
		. smaller("(posting ID #" . $feed["agency_comment_id"] . ")"),2);
		$result .= oline( bigger(bold($feed["comment"])),2 );
		$result .= hrule();
	}
	$result .= oline( $text,2 );
	return $result;
}

function get_iandr_feed( $id, $next="" )
{
	out(formto(orr($next,$_SERVER["PHP_SELF"]))
	. bold("
Do you have comments, suggestions, corrections or clarifications <em>about this agency</em>?  Please submit them here.  They may be helpful for your co-workers, and they might help when it is time to update the book for next year.") . "<br><br>" 
	. formtextarea("comment","","wrap=physical") . "<br><br>"
	. oline("You are logged in as " . staff_link($GLOBALS["UID"]),2)
	. button("Submit")
	. hiddenvar("id",$id)
	. hiddenvar("action","Post")
	. formend());
}

function post_iandr_feed( $feed )
{
	global $iandr_feed_table, $UID;
	if (! $feed["comment"] )
	{
		outline("Blank Entry Ignored");
		return;
	}
	$check = "SELECT * FROM $iandr_feed_table 
		WHERE agency_id=" . $feed["agency_id"] .
		" AND added_by=" . $feed["added_by"] .
		" AND comment=" . enquote1(sqlify($feed["comment"]));
	$test = sql_query( $check ) or sql_die( $check );
	if (sql_num_rows( $test ) > 0 )
	{
		outline("This feedback has already been submitted");
		outline("Duplicate entry ignored.");
		return;
	}
	// $post="INSERT INTO $iandr_feed_table SET "
// 		. sqlsetify("agency_id", $feed["agency_id"] )
// 		. sqlsetify("added_at",date("Y-m-d H:i:s")) 
// 		. sqlsetify("comment", $feed["comment"])  
// 		. sqlsetify("added_by",$UID, "Last");
	$vals=array('agency_id'=>$feed['agency_id'],   //I'm guessing this has been broken since the switch from MySQL...should now work.
			'FIELD:added_at'=>'CURRENT_TIMESTAMP(0)', //obviously though, it isn't much used.
			'comment'=>$feed['comment'],
			'added_by'=>$UID);
	$post=sql_insert($iandr_feed_table,$vals);
	$result=sql_query($post);
	if ($result)
	{
		headtitle("comment Accepted.  Thank you");
	}
	else
	{
		headtitle("Error posting feedback");
		outline("There was a problem trying to save your feedback.  Sorry for the inconvenience.  Please try again later");
		sql_warn("This is what the server reported with the following query $post");
	}
	outline("");
}

?>
