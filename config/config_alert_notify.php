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


$engine['alert_notify'] = array(
					  'singular' =>'Alert Notify Record',
					  'add_another' => true,
					  'list_fields' => array('alert_object','alert_notify_action_code','alert_notify_field','alert_notify_value','alert_notify_date'),
					  'fields' => array(
								  'alert_object'=>array(
												'data_type'=>'lookup',
												'lookup' => array(
															'table'=>'alert_notify_enabled_objects',
															'value_field'=>'alert_object_code',
															'label_field'=>'description'
															)
												),
								  'alert_notify_value'=>array('data_type'=>'varchar'),
								  'alert_notify_reason'=>array('null_ok'=>false,
													 'label'=>'Reason for notification?')
								  )
					  );

?>
