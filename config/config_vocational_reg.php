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


$engine['vocational_reg'] = array('singular' => 'Vocational Registration',
					    'list_fields' => array('vocational_reg_date','vocational_reg_date_end','hours_worked_while_enrolled','vocational_reg_completion_code'),
					    'fields' => array(
								    'agency_project_code' => array('display' => 'hide',
													   'default' => 'SAGE'),
								    'hours_worked_while_enrolled' => array('display_add' => 'hide'),
								    'vocational_reg_date_end' => array('display_add' => 'hide'),
								    'vocational_reg_completion_code' => array('display_add' => 'hide')
								    )
					    );

?>
