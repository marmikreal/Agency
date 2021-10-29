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
$engine['report'] = array(
					'perm'=>'reports',
					'perm_add'=>'admin',
					'perm_edit'=>'admin',
					'add_link_show'=>true,
					'list_fields' => array( 'report_id','report_title','report_category_code','report_comment'),

					'fields' => array(
						'report_id' => array(
								'value_view' => '$x . smaller(link_report($rec["report_id"],"Run this report"))',
								'value_list' => '$x ." ". smaller(link_report($rec["report_id"],"Run"))',
								'is_html' => true),

						'output' => array(
								'label' => 'Additional Output Options',
								'comment' => 'One option per line, format filename|label'),
						'sql' => array(
								'comment' => 'Multiple SQL statements can be used, separated by "SQL" on its own line.'),
						'report_permission' => array(
								'comment' => 'Can specify multiple perms.  User must have one. Separate with space or comma.'),
						'variables' => array(
								'comment' => 'Specify: Type, name, prompt, default (Example: VALUE min_gift "Specify minimum gift amount" 50)'),
								'report_comment' => array( 
								'value_list' => '$x ? help("",webify($x),"Show") : ""',
								'value_view' => 'webify($x)',
								'is_html' => true
								// FIXME:  is_html_list should be an option
								// list_in_click_box would also be a nice option to have
								)
					)
);
