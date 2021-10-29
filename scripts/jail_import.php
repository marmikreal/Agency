#!/usr/bin/php -q 
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



   //fixme: this script can be removed

log_error('This script is no longer needed. The logic has been implemented in import_king_county_jail()');

exit;


$off = dirname(__FILE__).'/../';
include $off.'command_line_includes.php';

$UID = $GLOBALS['sys_user'];

$db_table="tbl_jail";
$db_start_field="jail_date";
$db_end_field="jail_date_end";

$jails= file('/dev/stdin');

while ($jail = array_pop($jails))
{
	$jail=rtrim($jail);
	if ($jail)
	{
		$new_rec=array();
		$rec = explode(' ',$jail);
		$case_id = $rec[0];
		$date_in = $rec[1];
		$date_out = $rec[2];

		//find client_id
		$client = get_generic(array('clinical_id'=>$case_id),'','','client');
		if (sql_num_rows($client) !== 1) {
			$error = 'There is no corresponding client_id for case_id '.$case_id.' in tbl_client';
			global $mail_errors_to;
			mail((is_array($mail_errors_to) ? implode(',',$mail_errors_to) : $mail_errors_to).',cedsinger@desc.org,vwhissiel@desc.org',
				'KC JAIL REPORT IMPORT ERROR',$error);
			log_error($error);
			continue;
		}
		$client = sql_fetch_assoc($client);
		$client_id = $client['client_id'];

		$rec = array('client_id'=>$client_id,
				 $db_start_field=>$date_in,
				 $db_end_field=>$date_out);

		$err = '';
		jail_record_insert_update($rec,'KC','E',$err,false);
		/* moved to jail_record_insert_update()
		$filter = array("client_id"=>$client_id, $db_start_field=>$date_in);
		$existing = get_generic($filter,'','',$db_table);
		// already in DB?
		// 0 Rows = no
		// 1 Rows = yes, check whether end date is missing (and gets added)
		//          or if it exists, in which case it should match
		// 2 Rows = problem!
		if (sql_num_rows($existing)>1)
		{
			log_error("Found more than one record for client ID $client_id on $date_in.");
			continue;
		}
		elseif (sql_num_rows($existing)==1)
		{
			$existing = sql_fetch_assoc($existing);
			if ($existing[$db_end_field]) // release record already exists
			{
				if (!$date_out)
				{
				// booking record, release already posted
				}
				elseif (dateof($date_out)<>dateof($existing[$db_end_field]))
				{
					log_error("Conflicting release dates for Client ID $client_id on $date_in");
					continue;
				}
			}
			// no errors, post release if not already
			elseif ($date_out)
			{
				// update, set release date
				$new_rec[$db_end_field]=dateof($date_out,"SQL");
				$new_rec['jail_date_end_source_code'] = 'KC';
				$new_rec['jail_date_end_accuracy'] = 'E';
				$new_rec['changed_by']=$GLOBALS['sys_user'];
				$new_rec['changed_at']=dateof('now','SQL');
				$result = sql_query(sql_update($db_table,$new_rec,array("client_id"=>$client_id,$db_start_field=>$date_in)));
			}
		}
		else // record doesn't yet exist in DB
		{
			// post record
			$new_rec=array("client_id"=>$client_id,
					   $db_start_field=>dateof($date_in,"SQL"),
					   $db_end_field=>dateof($date_out,"SQL"),
					   'added_by'=>$GLOBALS['sys_user'],
					   'changed_by'=>$GLOBALS['sys_user']);
			if (!be_null($new_rec[$db_end_field])) {
				$new_rec['jail_date_end_accuracy'] = 'E';
				$new_rec['jail_date_end_source_code'] = 'KC';
			}
			$result = sql_query(sql_insert($db_table,$new_rec));
		}
		*/
	}
}
			
page_close($silent=true);

?>
