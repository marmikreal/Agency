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
//$DEBUG=true;
include "includes.php";

//uses AG_ID_CARD_CONFIG

$IP_ADDRESS = $_SERVER['REMOTE_ADDR'];
$id_card_config = $AG_ID_CARD_CONFIG[$IP_ADDRESS];
$tmp_directory = orr($id_card_config['directory'],'photo_upload');
$source_dir = "$AG_HOME_BY_FILE/agencylink/".$tmp_directory;
$source_url = "$AG_HOME_BY_URL/agencylink/".$tmp_directory;

// $client_id passed to page
$client_id=$_REQUEST["client_id"];
$title = "Uploading photo for " . client_link($client_id);
if (!$client_id)
{
		log_error("No client ID passed to take photo page");
		page_close();
		exit;
}
if ($_FILES["userfile"]["tmp_name"])
{
		$found = true;
		$upload=true;
		// $file=$_FILES["userfile"]["name"];
		$file=$_SERVER["REMOTE_ADDR"] . $_FILES["userfile"]["name"];
		$result=move_uploaded_file($_FILES["userfile"]["tmp_name"],"$source_dir/$file");
		if (!$result) 
		{
				log_error("Couldn't move uploaded file.  Stopping.");
				page_close();
				exit;
		}
}
		$out .= html_heading_1($title);
/*
if ($_REQUEST["action"]=="file_upload")
{
//		outline("Got this file: " . gettype($_REQUEST["file_data"]));
//		outline("Got this file: " . dump_array($_REQUEST));
		foreach ($_FILES as $key=>$value)
		{
				outline("Key $key, Value $value");
		}
		outline("Got this file: " . dump_array($_FILES));
}
*/

if ($_REQUEST["valid"]=="no")
{
		clearstatcache(); // clears cache of file info
		
		$file = $_REQUEST['file'];
		if (!$file)
		{
			$out .= oline("(warning: no file specified for delete.)",2);
		}
		elseif (is_file("$source_dir/$file"))
		{
			$deleted=unlink("$source_dir/$file");
			$out .= oline(bold(italic("(Deletion of $file was " . ($deleted ? "S" : "Uns" ) . "uccessful.)")),2);
		}
		else
		{
				$out .= oline("(note: photo $file already deleted)",2);
		}
}
elseif ($_REQUEST["valid"]=="yes")
{
		clearstatcache(); // clears cache of file info
		// copy photo into place, and generate thumbnail.
		$timestamp=datetimeof("now");
		$dest=client_photo_filename($client_id,"SOURCE",$timestamp);
		$dest_thumb=client_photo_filename($client_id,"THUMB");
		$dest_link=client_photo_filename($client_id);
		$DEBUG && outline("dest_link=$dest_link, dest_thumb=$dest_thumb, dest=$dest, timestamp=$timestamp");
		$file = $_REQUEST['file'];

		if (is_file($dest_link) && (!is_link($dest_link)))
		{
			// Old photo (w/ generic name, not link) should be preserved.
			// Get photo date & time from DB, and rename photo to that name.
			$client=client_get($client_id);
			$preserve_dest=client_photo_filename($client_id,"SOURCE",orr($client["last_photo_at"],"unknown-date"));
			$DEBUG && outline("Preserving generic filename $dest_link, renaming to $preserve_dest");
		}
		else
		{
			$preserve_dest=false;
			$DEBUG && outline("No generic photo to preserve");
		}
		if ( !copy("$source_dir/$file","$dest"))
		{
			$msg .= oline("Error Copying File into place ($dest, from $source_dir/$file).");
		}
		elseif ( exec("convert -geometry 120x160 \"$dest\" $dest_thumb"))
		{
			$msg .= oline("Error Creating Thumbnail ($dest_thumb, from $dest).");
		}
		// clearstatcache returns void, so need or (||) instead of and (&&)
		elseif ( clearstatcache() || (is_link($dest_link) && (!unlink($dest_link))))
		{
			$msg .= oline("Error Removing Symbolic Link ($dest_link).");
		}
		elseif ( $preserve_dest && (!rename($dest_link,$preserve_dest)))
		{
			$msg .= oline("Error Preserving Old Client Photo ($dest_link-->$preserve_dest).");
		}
		elseif ( clearstatcache() || is_file($dest_link))
		{
			$msg .= oline("File $dest_link still exists.  Something is terribly wrong.");
		}
//		elseif (!symlink(basename($dest),basename($dest_link)))
		// need to use exec because PHP function won't create link of just basenames
		elseif (shell_exec("cd " . escapeshellarg(dirname($dest)) . " ; ln -s " . escapeshellarg(basename($dest)) 
				. " " . escapeshellarg(basename($dest_link))))
		{
			$msg .= oline("Error creating symbolic link. ($dest --> " . basename($dest_link) . ")");
		}
		elseif (!agency_query(sql_update($client_table_post,
							 array("last_photo_at"=>$timestamp,'changed_by'=>$GLOBALS['UID']),
							 array("client_id"=>$client_id))))
		{
			$msg .= oline("Photo processing successful, but error updating database.");
		}
		else
		{
			$msg .= oline("Photo & Thumbnail successfully created.");
			$success=true;
		}
		if (!$success)
		{
			log_error("$msg, client $client_id");
			$msg=true;
		}
		else
		{
			$out .= oline(bigger(bold($msg)));
		}
		$deleted=unlink("$source_dir/$file");
		if (!$deleted)
		{
			$out .= oline(bigger(bold(red("Unable to delete temporary file $file"))),2);
		}
		$out .= oline("Return to client page for " . client_link($client_id));
		$completed=true;
}
		
if (!$completed)
{
//outline("Source Dir = $source_dir");
	if (is_dir($source_dir)) {
		$handle=opendir($source_dir);
		while ((!$found) && (false !== ($file_temp = readdir($handle)))) {
			if (!(preg_match('/jpg$/i',$file_temp)
				|| preg_match('/^([0-9]{1,3]\.){3}/',$file_temp)) ) {
				// (close enough test for an ip #, as used for uploaded files
				continue;
			} else {
				$file = $file_temp;
				$found=true;
				break;
			}
			$DEBUG && outline("Found File $file_temp");
		}
	} else {
		$out .= alert_mark('Error: directory '.$source_dir.' does not exist.');
	}

	if ($found ) {
		$out .= oline(bigger(bold("Here is your photo")),2);
		$out .= oline(httpimage("$source_url/$file",480,640));
		$commands .= oline(hlink($_SERVER['PHP_SELF']."?client_id=$client_id&file=$file&valid=yes","I want to use this photo."),2);
		$commands .= oline(hlink($_SERVER['PHP_SELF']."?client_id=$client_id&file=$file&valid=no","This photo is no good.  Please delete"));
	} else {
		$commands .= is_photo_station()
			? oline(bigger(bold(hlink($_SERVER['PHP_SELF']."?client_id=$client_id",$id_card_config['link_text']))))
			. link_wiki($id_card_config['wiki_help'],'Click here for detailed instructions')
					: 
					formto($_SERVER['PHP_SELF'],"file_form","enctype=multipart/form-data")
					. oline(bold("Use browse to choose a photo: ") 
					. hiddenvar("MAX_FILE_SIZE",3000000)
					. hiddenvar("client_id",$client_id)
					. hiddenvar("action","file_upload")
					. formfile("userfile"))
					. bold("And then Press this button->")
					. button("Send File")
					. formend();
		}
}

$title = strip_tags($title);
agency_top_header(array(cell($commands,'bgcolor="white"')));
out($out);
page_close();
exit;

?>
