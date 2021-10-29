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


$engine['payment_test'] = array('singular' => 'Payment (Testing Only)',
					  'plural'   => 'Payments (Testing Only)',
					  'perm'     => 'rent,housing',
					  'subtitle_html' => 'These records are for testing purposes only. See '.bug_link(21631). ' for more details.',
					  'valid_record' => array('(sql_false($rec["is_void"]) && be_null(orr($rec["void_reason_code"],$rec["voided_by"],$rec["void_comment"],$rec["voided_at"]))) || (sql_true($rec["is_void"]) && !be_null($rec["void_reason_code"]) && !be_null($rec["voided_by"]) && !be_null($rec["void_comment"]) && !be_null($rec["voided_at"]))' => 'All void fields must be filled in when voiding a record'),
					  'fields'   => array(
								    'agency_project_code' => array('default'=>'EVAL: last_residence_own($rec_init["client_id"])',
													   'display_add' => 'hide'),
								    'is_subsidy' => array('display_add' => 'hide'),
								    'payment_type_code' => array('display_add' => 'hide'),
								    'payment_form_code' => array('valid' => array('($x == "CHECK_3P" && !be_null($rec["check_from"])) || $x != "CHECK_3P"' => 'Payment From must be filled in for 3rd-Party Checks')),
								    'is_void' => array('display_add' => 'hide'),
								    'void_reason_code' => array('display_add' => 'hide'),
								    'voided_by' => array('display_add' => 'hide',
												 'value_edit' => '$GLOBALS["UID"]',
												 'valid' => array('$action == "add" || $GLOBALS["UID"] == $x' => '{$Y} must be set to current user')
												 ),
								    'void_comment' => array('display_add' => 'hide'),
								    'voided_at' => array('display_add' => 'hide'),
								    'receipt' => array('display_add' => 'hide',
											     'is_html' => true,
											     'value' => 'link_report_output("testing/payment_receipt.cfg","Print Receipt",array("cid"=>$rec["client_id"],"pid" => $rec["payment_id"]),"payment_receipt.sxw")'
											     )
								    )
					  );

foreach ( array('agency_project_code','payment_date','payment_form_code','payment_document_number',
		   'amount','is_subsidy','posted_comment','check_from') as $tmp_f ) {

	$engine['payment_test']['fields'][$tmp_f]['display_edit'] = 'display';

}
?>
