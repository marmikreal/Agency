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

// payments.php, stuff relating to payments
include AG_CONFIG_DIR . '/payments_config.php';

function get_payments( $filter,$order="payment_date desc" )
{
	global $payments_select_sql;
	$pay=agency_query($payments_select_sql,$filter,$order) or 
		log_error("Couldn't query to get payments.  Query was $sql");
	return $pay;
}

function total_payments( $filter )
{
	global $payments_table;
    if (! isset($filter["is_void"]))
    {
        $filter["is_void"]=sql_false();
    }
    $result = sql_fetch_assoc(agency_query("SELECT SUM(amount) AS total FROM $payments_table",$filter));
    return $result["total"];
}


function get_payment( $pay_id )
{
	$pay=get_payments( array("payment_id"=>$pay_id));
	if (sql_num_rows($pay)==0)
	{
		return false;
	}
	elseif (sql_num_rows($pay)==1)
	{
		return sql_fetch_assoc($pay);
	}
	else
	{
		log_error("ERROR.  Get_payment looked for Payment #$pay_id.  The Query should return 0 or 1 rows, but instead we got this many: " . sql_num_rows($pay));
		die;
	}
}

function post_payment( $payment )
{
// this is copied almost verbatim from post_log.
// Need to take the generic stuff and make a post_record function.

	global $UID, $payments_table;
	if (! $payment["client_id"])
	{
		log_error("Tried to post payment, no client_id: " . $payment["qb_name"]);
		return false;
	}
	$payment["added_by"] = $UID;
	$sql = sql_insert($payments_table,$payment);
	$a = sql_query( $sql ) or log_error("Tried to post payment, but the query failed:  $sql.");
	return $a;
}

function void_payment( $payment_id, $comment )
{
    global $payments_table, $UID;
    if (! $comment)
    {
        outline("You must supply a comment to void a payment.");
        return false;
    }
    $pay = get_payment( $payment_id );
    if ($pay)
    {
		$amt = $pay["amount"];
		unset ($pay["amount"]);
		$pay["amount"]=0;
        $sql = "UPDATE $payments_table SET is_void='Y', amount='0.00',
                        void_comment='$comment  (Original Amount was $amt)',
			voided_at=" . enquote1(datetimeof("now","SQL")) . ", 
                        voided_by='$UID' WHERE payment_id = $payment_id";
            $a = sql_query( $sql ) or log_error("Couldn't void payment #$payment_id");
            return $a;
    }
    else
    {
		log_error("Tried to void payment #$payment_id, but it was not found.");
        return false;
    }
}

function get_payments_for( $client="ALL", $type="ALL" )
{
	global $payments_table;
        $sql = "
                SELECT PAY.*
                FROM $payments_table as PAY
                WHERE 1=1 ";


	if ($client=="NOTNULL")
	{
		$filter["<>:client_id"]="";
	}
	elseif ($client <> "ALL")
        {
                $filter["client_id"]=$client;
        }
        if ($type <> "ALL")
        {
                $filter["payment_type_code"]=$type;
        }
        $result = get_payments($filter) or sql_warn( "Could get payments for.  Query was $sql");
        return $result;
}

function show_payments( $payments, $format="short" )
{
	global $colors;
	if (sql_num_rows($payments)==0)
	{
		return oline("No Payments to Display");
	}
	$total = 0;
	$res = tablestart("","border=1");
	if ($format=="full")
	{
		$res .= header_row( "Type", "Date","Amount","No","Memo","Account");
	}
	else
	{
		$res .= header_row( "Type", "Date","Amount");
	}
	for ($x=0;$x<sql_num_rows( $payments );$x++)
	{
		$p = sql_fetch_assoc( $payments );
		$restemp =
			cell( $p["payment_type_code"] )
			. cell( dateof($p["payment_date"]) )
			. cell( $p["amount"] . (sql_true($p["is_void"]) ? " (void)" : ""),
			($p["amount"] < 0) ? "bgcolor={$colors["red"]}" : "");
		if ($format=="full")
		{
			$restemp .= cell($p["qb_no"]) . cell( $p["qb_memo"] ) . cell($p["qb_account"]);
		}
		$res.=row($restemp);
		$total += $p["amount"];
	}
	$res .= row(cell(bold("Total payments displayed: $total"),"colspan=3"));	$res .= tableend();
	return $res;
}

?>
