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

$engine['payment'] = array(
				   'add_link_show'=>false,
				  'perm'=>'rent',
				  'subtitle_eval_code'=>'balance_by_project($id)',
				  'list_fields'=>array(
							     'payment_date',
							     'agency_project_code',
							     'payment_type_code',
							     'amount',
							     'qb_no',
							     'qb_memo',
							     'qb_account'),
				  'fn'=>array(
						  'get'=>'get_generic',
 						  'post'=>'post_generic'
						  ),
				  'fields'=>array(
							'amount'=>array(
									    'data_type'=>'currency',
									    'total_value_list'=>'sql_true($rec["is_void"]) ? 0 : $x',
									    'is_html'=>true,
									    'value'=> '($x < 0 )
									    ? div(currency_of($x) . (sql_true($rec["is_void"]) 
														    ? " (void)" : null),""," style=\"background-color: red; padding: 3px;\"")
									    : currency_of($x) . (sql_true($rec["is_void"]) 
														    ? " (void)" : null)',
									    'value_format'=>'($rec["amount"] < 0) ? span($x) : $x'),
							'qb_no'=>array('label'=>'No.'),
							'qb_memo'=>array('label'=>'Memo',
									     'value_format_list' => 'smaller($x)'),
							'qb_account'=>array('label'=>'Account',
									     'value_format_list' => 'smaller($x)')
							)
				  );
?>
