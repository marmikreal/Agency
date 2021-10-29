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

function jail_status_f($id)
{
	$def = get_def('jail');
	$res = get_generic(client_filter($id),'jail_date DESC','1',$def);
	if (sql_num_rows($res) < 1) {
		return false;
	}
	$rec = sql_fetch_assoc($res);
	if (be_null($rec['jail_date_end'])) {
		$days = $rec['days_in_jail'] . ($rec['days_in_jail'] > 1 ? ' days' : ' day');
		$text = 'Incarcerated since '.dateof($rec['jail_date']).' ('.$days.')';
	} elseif (days_interval($rec['dal_due_date'],'now',true) < 8 ) { //show up to 7 days late
		// if dal done, we should say that
		// so wed need to query the dal table
		$range = new date_range( $rec['jail_date_end'], $rec['dal_due_date'] );
		$text = 'Released from jail on '.dateof($rec['jail_date_end']). '. ' 
		. bold(dal_due_between_date_f($id,$range));
	} else {
		return false;
	}
	return oline(link_engine(array('object'=>'jail','id'=>$rec['jail_id']),
					 red($text)));

}

?>
