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

function get_crp_reg( $filter, $order="",$limit="" )
{
	$def = get_def('crp_reg');
	$select=$def['sel_sql'];
	$order=orr($order,implode(',',array_keys($def['list_order'])));
	return agency_query($select,$filter,$order,$limit);
}

function show_crp_reg( $regs )
{
	$out = tablestart("","border=5") . header_row("Start","End","Days");
	while ($reg=sql_fetch_assoc($regs))
	{
		$dates=new date_range($reg["crp_reg_date"],$reg["crp_reg_date_end"]);
		$out .= row(cell(dateof($dates->start))
					. cell(dateof($dates->end))
					. cell($dates->days())
			    . cell(smaller(link_engine(array("object"=>"crp_reg","id"=>$reg["crp_reg_id"])))));
	}
	$out .= tableend();
	return $out;
}

function crp_status_f( $id )
{
// returns a formatted string displaying CRP status
// never enrolled, last enrollment, or currently enrolled
// this currently relies on get_crp_reg returning records in descending date order

	$regs = get_crp_reg( array( "client_id"=>$id ),"crp_reg_date DESC");
	$mh_regs = tier_status($id,'75');
	if ((sql_num_rows($regs)==0) && (sql_num_rows($mh_regs)==0)) {
		$result = smaller("(never enrolled in CRP)");
	} else {

		$mh_reg_def = get_def('clinical_reg');

		if (($reg = sql_fetch_assoc($regs)) and be_null($reg["crp_reg_date_end"])) {
			$result = "Current " . blue("CRP") . " Registration Began " 
				. link_engine(array('object'=>'crp_reg','id'=>$reg['crp_reg_id']),dateof($reg["crp_reg_date"]));
		} elseif ($reg) {
			$result = "Last " . blue("CRP") . " Registration " 
				. link_engine(array('object'=>'crp_reg','id'=>$reg['crp_reg_id']),dateof($reg["crp_reg_date"])
						  . "-->" . dateof($reg["crp_reg_date_end"]));
		} else {
			$result = smaller('No CRP registrations');
		}

		$mh_reg = sql_fetch_assoc($mh_regs);
		if ( ($mh_reg['clinical_reg_date_end'] !== $reg['crp_reg_date_end'])
		     || ($mh_reg['clinical_reg_date'] !== $reg['crp_reg_date']) ) {

			$descrep = ($s = dateof($mh_reg['clinical_reg_date']))
				? qelink($mh_reg,$mh_reg_def,red( $s . (($e = dateof($mh_reg['clinical_reg_date_end'])) ? '-->'.$e : '')))
				: red('No registration found');
			$result .= oline().indent().smaller('King County Discrepancy: '.$descrep,2);

		}
			
	}
	return $result;
}

?>
