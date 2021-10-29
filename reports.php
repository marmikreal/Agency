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


function report_generate_menu(&$navigation)
{
	 // open_query and profile report links here:
	 $out = html_list( 
				html_list_item(hlink_if('open_query.php','Direct SQL Query Page',has_perm('open_query'),'',' class="fancyLink"'),'class="reportFile"')
				. html_list_item(hlink_if('rpt_profile.php',org_name('short') . ' Profile Report',has_perm('agency_profile_rep'),'',' class="fancyLink"'),'class="reportFile"')
				,'class="reportList"') . $out;
	$perm = $tot_recs = NULL;
	 return $out . call_engine(array('object'=>'report','action'=>'list','format'=>''),'',true,true,$perm,$tot_recs);
}

function get_report_from_db( $report_id )
{
	$rpt=get_generic(array('report_id'=>$report_id),'','',get_def('report'));
	if (sql_num_rows($rpt) <> 1)
	{
		return false;
	}
	$rec=sql_fetch_assoc($rpt);
	/* Split multiple SQL statements into array */
	$rec['sql'] = preg_split( '/\n\s?SQL\s?\n/im',$rec['sql'] );
	if (!be_null($rec['output'])) {
		foreach(explode("\n",$rec['output']) as $line)
		{
			$out_a[] = explode('|',$line);
		}
		$rec['output']=$out_a;
	}
	else {
		$rec['output'] = array();
	}
	$rec['report_permission']=preg_split('/[,\s]/',$rec['report_permission']);
	return $rec;
}

function report_parse_var_text( $text ) {
	$vartypes=array('PICK','DATE','TEXT','TEXT_AREA','VALUE');
	$lines = preg_split('/\n/m',$text);
	while ($line = array_shift($lines)) {
		if (preg_match('/^\s$/',$line)) {
			continue; //skip blank lines
		}
		$var=array();
		$ex = explot($line);
		if (!in_array(strtoupper($ex[0]),$vartypes)) {	
			//fixme:  make me a pretty warning
			outline('Warning:  Unknown variable type ' .$ex[0]);
			continue;
		}
		$var['type']    = strtoupper($ex[0]);
        $var['name']    = $ex[1];
        $var['prompt']  = $ex[2];
        $var['default'] = $ex[3];
		if ($var['type']=='PICK') {
			while ($tmp_line = array_shift($lines))	{
				$tmp_line = explot($tmp_line);
				if (strtoupper($tmp_line[0])=='ENDPICK') {
					break;
				}
				if (strtoupper($tmp_line[0])=='SQL') {
					$tmp_sql = '';
					while ($tmp = array_shift($lines)) {
						$tmp_ex = explot($tmp);
						if (strtoupper($tmp_ex[0])=='ENDSQL') {
							// SQL query assembled and ready
							$tmp_query = agency_query($tmp_sql); 
							while ($t=sql_fetch_assoc($tmp_query)) {
								$var['options'][]='"'.$t['value']. '" "' .$t['label'].'"';
							}
							break;
						} else {
							$tmp_sql .= $tmp;
						}	
					}	
				} else {
					$var['options'][]=enquote2($tmp_line[0]) . ' ' . enquote2($tmp_line[1]);
				}
			}
		}
		$vars[]=$var;
	}
	return $vars;
}

function report_valid_request($report, &$mesg)
{
	/*
	 * validates a request for a report
     * currently, only checks for valid dates
	 */

	$valid = true;
	$report['variables']=orr($report['variables'],array());
	foreach ($report['variables'] as $var) {
		$type  = $var['type'];
		$name  = $var['name'];
		$value = report_get_user_var($name,$report['report_id']);
		switch ($type) {
		case 'DATE' :
			if (!dateof($value,'SQL')) {
				$mesg .= oline($value.' is an invalid date');
				$valid = false;
			}
			break;
		default: //no error checking
		}
	}
	$mesg .= 'error';
	return $valid;
}

function report_get_user_var($name,$report_id)
{
	$varname = AG_REPORTS_VARIABLE_PREFIX.$name;
	$val = $_SESSION['report_options_'.$report_id.'_'.$varname] =
		$_REQUEST[$varname];
	return $val;
}

function report_generate($report)
{
	/*
	 * Generates and returns an engine list,
	 * or merges query results with an open office template and exits
	 *
	 */

	$pattern_replace_header = $pattern_replace = array();
	$report['variables']=orr($report['variables'],array());
	foreach ($report['variables'] as $var) {
		$type  = $var['type'];
		$name  = $var['name'];
		$value = report_get_user_var($name,$report['report_id']);

		switch ($type) {
		case 'DATE' :
			$value = dateof($value,'SQL');
			$value_header = dateof($value);
			break;
		default:
			$value_header = $value;
		}
		$pattern_replace['$'.$name] = $value;
		$pattern_replace_header['$'.$name] = $value_header; //contains human-readable values for date

		//set labels
		if ($var['options']) { //a pick variable, determine which was picked
			foreach ($var['options'] as $opt) {
				$opte = explot($opt);
				if ($opte[0] === $value) {
					$label = $opte[1];
				}
			}
		} else {
			$label = $var['prompt'];
		}
		$pattern_replace['$'.$name.'_label'] = $label;
		$pattern_replace_header['$'.$name.'_label'] = $label;
	}

	// sort longest to shortest keys so, for example, "date_end" is replaced before "date"
	uksort($pattern_replace,'strlen_cmp');
	$pattern = array_keys($pattern_replace);
	$replace = array_values($pattern_replace);
	foreach ($report['sql'] as $k => $sql) {
		$report['sql'][$k] = str_replace($pattern,$replace,$sql);
	}

	// a separate, formatted, replace array for header and footer
	uksort($pattern_replace_header,'strlen_cmp');
	$pattern = array_keys($pattern_replace_header);
	$replace = array_values($pattern_replace_header);
	$report['report_header'] = str_replace($pattern,$replace,$report['report_header']);
	$report['report_footer'] = str_replace($pattern,$replace,$report['report_footer']);

	$template = $_REQUEST[AG_REPORTS_VARIABLE_PREFIX.'template'];

	if (be_null($template) || $template==='screen') { 
		if ($report['allow_output_screen']==sql_false()) {
			return 'Screen output not allowed for this report';
		}
		$footer = oline(webify($report['report_footer']),2);
		/* cfg and sql output */
		if ($_REQUEST[AG_REPORTS_VARIABLE_PREFIX.'showcfg']) {
			$hide_button = Java_Engine::hide_show_button('reportShowConfig',$hide = true);
			$footer .= oline($hide_button.'Config File: '. $report['report_title'] . ' (' . $report['report_id'] .')')
				. Java_Engine::hide_show_content(div(dump_array($report),'',' class="sqlCode"')
									   ,'reportShowConfig',$hide = true);
		}
		if ($_REQUEST[AG_REPORTS_VARIABLE_PREFIX.'showsql']) {
			$hide_button = Java_Engine::hide_show_button('reportShowSQL',$hide = true);
			$footer .= oline($hide_button.'Generated SQL')
				. Java_Engine::hide_show_content(div(webify_sql($report['sql']),'',' class="sqlCode"'),'reportShowSQL',$hide = true);
		}

		/* Output to screen via engine list */
		$control = array('object' => 'generic_sql_query',
				     'action' => 'list',
				     'list'   => array('fields'=>array(),'filter'=>array(),'order'=>array(),'show_totals'=>true,
							     'max' => orr($report['rows_per_page'],'-1')),
				     'sql_security_override' => $report['override_sql_security'],
				     'export_header' => $report['report_header'],
				     'sql'    => $report['sql']);

		$oo_templates = report_output_select($report,$engine_array = true);
		if (!be_null($oo_templates)) {
			$control['oo_templates'] = $oo_templates;
		}

		return html_heading_4(webify($report['report_header']),' class="reportHeader"')
			. call_engine($control,$control_array_variable='control',$NO_TITLE=true,$NO_MESSAGES=true,&$TOTAL_RECORDS,&$PERM)
			. (!be_null($footer) ? html_heading_4($footer,' class="reportFooter"') : '');
	}

	/* template handling */
	switch ($template) {

	case 'sql_data_csv':
	case 'sql_data_tab':
	case 'sql_dump_full':
	case 'sql_dump_copy':
	case 'sql_dump_inserts':

		return report_generate_export($report['sql'],$template); // if succesful, this will exit the script

	case 'spreadsheet' :

		if ($report['allow_output_spreadsheet']==sql_false()) {
			return 'Generic spreadsheet option not allowed for this report.';
		}
		if (!AG_OPEN_OFFICE_ENABLE_EXPORT) {
			return AG_OPEN_OFFICE_DISABLED_MESSAGE;
		}
		$template = AG_OPEN_OFFICE_CALC_TEMPLATE;
		break;
	default:
	}
	return report_generate_openoffice($report,$template);
}

function report_generate_openoffice($report,$template)
{
	$sql = $report['sql'];
	if (!is_array($sql)) {
		$sql = array($sql);
	}
	// security
	if (!$report['override_sql_security'] && !is_safe_sql($sql,$errors,$non_select = true)) {
		return div($errors);
	}
	// execute query
	$error = '';
	foreach ($sql as $s) {
		$result = agency_query($s);
		if (!$result) {
			$error .= oline('SQL error.');
		}
	}
	if ($error) {
		return div($error,'',' class="error"');
	}
	if (!AG_OPEN_OFFICE_ENABLE_EXPORT) {
		return div(AG_OPEN_OFFICE_DISABLED_MESSAGE,'',' class="error"');
	}

	require_once 'openoffice.php';
	require_once 'zipclass.php';
	set_time_limit(120); //fixme: can be set in report record
	// split out_form for group by (currently only used in writer)
	if (preg_match(AG_REPORTS_REGEX_TEMPLATES,$template,$m)) {
		$template = $m[1];
		$group_by = $m[4];
	}

	if ($type = is_oo_writer_doc($template)) {
		// oo writer document
        	$oofile=oowriter_merge($result,$template,NULL,NULL,$group_by);
	} elseif ($type = is_oo_calc_doc($template)) {
		// oo calc document
        	$oofile=office_merge($result,$template,
					   //fixme: report header has changed
					   array("HEADER-LABEL"=>dewebify($report['report_header']
										    . "\nGenerated at " . datetimeof("now","US"))));
	} else {
		log_error('Unknown openoffice type '.$template);
		return false;
	}
       
	office_mime_header($type);
	out($oofile->data());
	page_close($silent=true); //no footer on oo files
	exit;
}

function report_generate_from_posted()
{
	$report = array();
	$report['sql'] = dewebify($_REQUEST['sql1']);
	$report['report_header'] = dewebify($_REQUEST["report_header"]);

	//fixme: this still relies on sql being acquired from the browser. even though it is checked
	//       it is still a dangerous idea.
	//note: requested sql is tested in report_generate_openoffice() or report_generate_export()

	$template = $_REQUEST[AG_REPORTS_VARIABLE_PREFIX.'template'];
	switch ($template) {
		//fixme: this is currently in too many places. make common function
	case 'sql_data_csv':
	case 'sql_data_tab':
	case 'sql_dump_full':
	case 'sql_dump_copy':
	case 'sql_dump_inserts':
		return report_generate_export($report['sql'],$template); // if succesful, this will exit the script
	case 'spreadsheet' :
		$template = AG_OPEN_OFFICE_CALC_TEMPLATE;
		break;
	default:
	}
	return report_generate_openoffice($report,$template);
}

function report_user_options_form($report)
{
	$out = formto();
	$out .= $report['report_id'] ? hiddenvar('report_id',$report['report_id']) : '';
	$out .= hiddenvar('action','generate');
	foreach( orr($report['variables'],array()) as $p ) {
		$varname    = AG_REPORTS_VARIABLE_PREFIX . $p['name'];	
		$userprompt = $p['prompt'];	
		$comment    = $p['comment']; // fixme in parse_cfg_file

		// store report variables for session
		$default = $_SESSION['report_options_'.$report['report_id'].'_'.$varname] = 
			orr($_REQUEST[$varname],$_SESSION['report_options_'.$report['report_id'].'_'.$varname]);
		
		switch ($p['type']) {
		case 'PICK' :
			$label =($userprompt ? bigger(bold($userprompt)) : '' ).  ($comment ? " $comment" : '');
			$cell = selectto($varname);

			foreach( $p['options'] as $li) {
				$li = explot($li);
				// default is set a) if default is passed, and equals current option
				$defaulti = $default===$li[0] 
					// or, b) no default is passed, but default is configured to current option
					|| (!$default && $li[0]==$p['default']);
				$cell .= selectitem( $li[0],$li[1],$defaulti);
			}
			$opt .= row(cell($userprompt) . cell($cell.selectend()));
			break;
		case 'DATE' :
				 $opt .= row(cell($userprompt) . cell(formdate($varname,orr($default,$p['default'],dateof('now')))));
				 break;
		case 'VALUE' :
		case 'TEXT' :
			$opt .= row(cell($userprompt) . cell(formvartext($varname,orr($default,$p['default']))));
			break;
		case 'TEXT_AREA':
			$opt .= row(cell($userprompt) . cell(formtextarea($varname,orr($default,$p['default']))));
			break;
		default :
			$opt .= row(cell(alert_mark('Don\'t know how to handle a ' . $p['type']),' colspan="2"'));
		}
	}

	// output options
	$opt .= row(cell('Choose Output Format').cell(report_output_select($report)));
	$opt .= row(cell(oline('',2).formcheck(AG_REPORTS_VARIABLE_PREFIX.'showcfg').smaller(' Show Config File on Results Page'),' colspan="2"'));
	$opt .= row(cell(formcheck(AG_REPORTS_VARIABLE_PREFIX.'showsql').smaller(' Show SQL on Results Page'),' colspan="2"'));

	$out .= table($opt);
	$out .= button();
	$out .= formend();

	return $out;
}

function report_output_select($report,$engine_array = false)
{
	// return either a select box, or a control array for use with engine list

	$out = selectto(AG_REPORTS_VARIABLE_PREFIX . 'template');
	$control_array = array();

	if (AG_OPEN_OFFICE_ENABLE_EXPORT) {
		foreach( $report['output'] as $o) {
			$opt = implode('|',$o);
			// for label, strip out group_by field, if extant (template|group_field)
			preg_match(AG_REPORTS_REGEX_TEMPLATES,$opt,$matches);
			$label=orr($matches[2],$matches[1]);
			$out .= selectitem($opt,$label);
			$control_array[$opt] = $label;
		}
	}

	if (!sql_false($report['allow_output_screen'])) {
		$out .= selectitem('screen', 'Show report on screen');
	}
	if ( ($report['allow_output_spreadsheet'] != sql_false()) && AG_OPEN_OFFICE_ENABLE_EXPORT) {
		$out .= selectitem('spreadsheet', 'Generate Spreadsheet File (generic)');
		$control_array['spreadsheet'] = 'Generate Spreadsheet File (generic)';
	}

	//get csv, tab, sql dump options, if permissions correct
	$control_array = array_merge($control_array,report_export_items($array = true));
	$out .= report_export_items();

	if ($engine_array) {
		return $control_array;
	}

	$out .=selectend();
	return $out;
}

function explot( $line )
{
	while ( preg_match('/^([^\s\"]+)\s?(.*)$/',$line,$matches) ||
		 preg_match('/^\s?\"(.*?)\"(.*)$/',$line,$matches)) {
			$split[]=$matches[1];
			$line=trim($matches[2]);
		} 
	return $split;
}

function report_generate_export($sql,$format)
{
	/*
	 *    Expects format to be one of:
	 *
	 *	case 'sql_data_csv':
	 *	case 'sql_data_tab':
	 *	case 'sql_dump_full':
	 *	case 'sql_dump_copy':
	 *	case 'sql_dump_inserts':
	 */
	if (!is_safe_sql($sql,$errors,$non_select = true)) {
		return $errors;
	}

	if (has_perm('sql_dump')) {
		preg_match('/^sql_(dump|data)_([a-z]*)$/',$format,$m);
		header("Content-Type: text; charset=ISO-8859-1");
		if ($m[1]=='data') {
			$delimiter = $m[2]=='csv' ? ',' : "\t";
			header('Content-Disposition: attachment; filename="agency_data.csv"');
			out(sql_data_export($sql,$delimiter));
		} elseif ($m[1]=='dump') {
			header('Content-Disposition: attachment; filename="agency_sql_dump.sql"');
			out(sql_commentify($GLOBALS['AG_TEXT']['CONFIDENTIAL_STATEMENT']));
			out(sql_dump($sql,strtoupper($m[2])));
		}
		page_close($silent=true);
		exit;
	}

	return oline(alert_mark('You aren\'t allowed to perform an SQL Dump'),4);
}

function report_export_items($array = false)
{
	$options = array('sql_dump_inserts' => 'SQL Dump (insert commmands)',
			     'sql_dump_full'    => 'SQL Dump (column insert commands)',
			     'sql_dump_copy'    => 'SQL Dump (copy commands)',
			     'sql_data_csv'     => 'CSV file',
			     'sql_data_tab'     => 'Tab-delimited file');
	$perm = has_perm('sql_dump');
	$out = '';
	if ($array) {
		return $perm ? $options : array();
	} elseif ($perm) {
		foreach ($options as $val => $label) {
			$out .= selectitem($val,$label);
		}
	}
	return $out;
}

function link_report($report_id,$label,$init=array())
{
	/*
	 * Generate link to a report options page, with optional $init
	 * values pre-filled 
	 */
	if (!(is_numeric($report_id) and (intval($report_id)==$report_id))) { 
		out(div("warning: bad report ID $report_id passed to link_report",'','class="warning"'));
		return false; }
	
if (!be_null($init) && is_assoc_array($init)) {
		foreach ($init as $var => $val) {
			$url .= '&'.AG_REPORTS_VARIABLE_PREFIX.$var.'='.$val;
		}
	}
	$url = AG_REPORTS_URL . '?report_id='.$report_id . $url;
	$rep = get_report_from_db($report_id);
	return hlink_if($url,$label,(be_null($rep['report_permission']) || has_perm($rep['report_permission'])));
}

function link_report_output($report_id,$label,$init,$template=null)
{
	/*
	 * Generate a link to a report _output_ page. $init must contain all
	 * the variables required by the report, or errors will ensue. The optional
	 * $template variable is to use a template instead of outputting to the screen.
	 */
	$url = AG_REPORTS_URL.'?report_id='.$report_id.'&action=generate';
	foreach ($init as $var => $val) {
		$url .= '&'.AG_REPORTS_VARIABLE_PREFIX.$var.'='.$val;
	}
	$url .= $template ?  '&'.AG_REPORTS_VARIABLE_PREFIX.'template='. $template : '';
	return hlink($url,$label);

}

function track_report_usage($report_id, $report_name)
{
	global $UID;
	$IP = $_SERVER['REMOTE_ADDR'];
	$output = $_REQUEST[AG_REPORTS_VARIABLE_PREFIX.'template'];
	$record = array('generated_by' => $UID,
			    'report_id' => $report_id,
			    'report_name' => $report_name,
			    'output_format' => $output,
			    'generated_from' => $IP,
			    'added_by' => $GLOBALS["sys_user"],
			    'generated_at' => datetimeof("now","SQL"),
			    'changed_by' => $GLOBALS["sys_user"] );
	return agency_query(sql_insert('tbl_report_usage', $record));  
}

function list_report($control,$def,$control_array_variable='',&$REC_NUM)
{
		$order=array('report_category','report_title');
		$order='report_category_code,report_title';
		$result = list_query($def,array(),$order,$control);
		if (($REC_NUM=sql_num_rows($result)) == 0 ) {
			$out = oline('No reports found');
		} else {
			for ($count=1;$count<=$REC_NUM;$count++) {
				$rep=sql_fetch_assoc($result);
				$sortkey = orr($rep['report_category_code'],'General');
				if ($sortkey <> $old_sortkey) {
					$out .= $li ? html_list($li) : '';
					$out .= oline() . oline(bigger(bold($sortkey)),2);
					$li = '';
				}
				//$out .= oline(link_report($rep['report_id'],$rep['report_title']));
				$li .= html_list_item(link_report($rep['report_id'],$rep['report_title']));
				$old_sortkey=$sortkey;
				$out .= ($REC_NUM==$count) ? html_list($li) : '';
			}
		}
		$out .= oline() . smaller(italic(link_engine(array('action'=>'add','object'=>'report'),'Add a new report')));
		return $out;
}

?>
