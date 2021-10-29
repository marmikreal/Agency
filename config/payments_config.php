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

// configuration of stuff relating to payments

$payments_import_table = "qb_payment";
$qb_client_table = "qb_client";
$payments_table = "payment";

/*
Configure account codes and matching projects here.
FIXME:  This should be moved to a table

$accounts_rent_residents = array( "0000.00"	=>	"LYON" );
$accounts_rent_subsidy  = array( "0000.00"	=>	"LYON");
$accounts_security      = array( "0000.00"	=>	"LYON");
*/




$payments_import_select_sql = "
	SELECT 	DISTINCT qb_id	AS	qb_id,
		qb_type		AS	qb_type,
		qb_name		AS	qb_name,
		qb_account	AS	qb_account,
		qb_memo		AS	qb_memo,
		qb_no		AS	qb_no,
		client_id	AS	client_id,
		qb_date		AS	payment_date,
		qb_amount	AS	amount
	FROM	$payments_import_table
	LEFT JOIN $qb_client_table using (qb_name)
	WHERE 1=1 ";

$payments_select_sql = "SELECT * FROM $payments_table AS PAY ";

?>
