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

function engine($control='',$control_array_variable='control')
{
      /*
	 * Documentation: http://www.desc.org/chasers_wiki/index.php/Engine_Documentation
	 */
	global $engine;

      $commands=array();

	$control_array_variable = orr($control_array_variable,'control');

	/*
	 * clean _GET and _POST
	 */
	engine_control_array_security();

	/*
	 * Items required for operation, and passed on every instance
	 */
      $CONTROL_PASS=$engine['control_pass_elements'];

	/*
	 * Get control array, if not passed to function
	 */
	if (!is_array($control)) {
		$control=dewebify_array(unserialize_control($_REQUEST[$control_array_variable]));
      }

	/*
	 *determine which session variable to use
	 */
	$object = $control['object'];
	$action = $control['action'];
	$control['id'] = $id = engine_set_control_id($control);

 	if (!$object or !$action or !$id ) {
 		return array('message'=>'ERROR: engine() must have an action, object and id passed.');
 	}

	$session_identifier = generate_session_identifier($object,$id);

      $CONTROL=$_SESSION['CONTROL'.$session_identifier];

	/*
	 * MAINTAIN OLD STATE SO THAT STEP IS PROPERLY SET BELOW
	 */
	$old_action = $CONTROL['action'];
	$old_object = $CONTROL['object'];
	$old_id     = $CONTROL['id'];

	/*
	 * update session variable -- from here on out, $control is used
	 * $control['list'] is merged separately
	 */
	$control['list'] = array_merge(orr($CONTROL['list'],array()),orr($control['list'],array()));
	$control['page'] = $page = orr($control['page'],'display.php');

	/*
	 * Obtain $REC and $REC_LAST from saved session
	 */
 	$REC = $_SESSION['REC'.$session_identifier];
	$control['rec_last'] = $REC_LAST = $_SESSION['REC_LAST'.$session_identifier];

	$_SESSION['CONTROL'.$session_identifier] = $control = array_merge(orr($CONTROL,array()),orr($control,array()));

      $REC_INIT = $control['rec_init'];

	/*
	 * internal audit
	 */
	if ( ($old_action != $action) or ($old_id != $id) or ($old_object != $object) ) {
		$control['step']='';
		$control['object_references']=array();
      }

	$step   = $control['step'];
	$format = $control['format'];

	/*	
	 * Determine if we are dealing w/ an object w/ protect enabled.
	 * This is a failsafe - link_engine() should prevent most people from getting here
	 *
	 * $action must be checked prior to calling is_protected_generic or an error will
	 * be thrown on adds.
	 */
	if ( in_array($action,array('edit','delete')) and is_protected_generic($object,$id) ) {
		return array('message'=>oline('Protected Record'));
	}

	/*
	 * Get the object definition array.
	 */
      if (!$def = get_def($object)) {
		return array('message' => 'Error: Engine cannot work without a $def array. Object: '.$object);
	}

	/*
	 * Add link for non-standard/undefined objects
	 */
	if ($action == 'list' && $def['add_link_always']) {
		$output .= oline(link_engine(array('object'=>$object,'action'=>'add'),'Add a record to '.$def['table_post'],'',''),2);
	}

	/*
	 * Object merging
	 */
	$def = $def['fn']['object_merge']($def,$control);

	/*
	 * Permissions
	 */
	$super_user_perm = engine_perm(null);
      $super_perm      = engine_perm($control,'S'); // all permissions
      $any_perm        = $def['perm_'.$action]=='any';
      $read_perm       = engine_perm($control,'R') or $super_perm or $any_perm;
      $write_perm      = engine_perm($control,'W') or $any_perm or $super_perm;

	/*
	 * Multi-records
	 */

      if ( ($def['multi_records']) and ($action=='add') ) {
		foreach( $def['multi'] as $m=>$opts ) {
			$def=$opts["add_fields_fn"]($def,$m);
		}
      }

	/*
	 * Send any custom css
	 */
	if ($css = $def['custom_css']) {
		global $AG_HEAD_TAG;
 		$AG_HEAD_TAG .= style($css);
	}

	/*
	 * TEXT LABELS
	 */
	$text_options = $engine['text_options'];

      $reset_text       = $text_options['reset_button'];
      $submit_text      = $text_options['submit_button'];
      $cancel_text      = $text_options['cancel_button'];
      $edit_text        = $text_options['edit_text'];
      $delete_text      = $text_options['delete_text'];
	$view_text        = $text_options['view_text'];
      $post_text        = $text_options['post'];
      $add_another_text = $text_options['add_another'];

	$required_fields_text = $text_options['required_fields'];

	/*
	 * Passwords
	 */
	$re_enter_pwd = engine_password_cycle($def,$action,$step);

      $button_variable  = 'postingbutton';
      $require_password = $re_enter_pwd ? $def['require_password'] : false;

	global $AG_AUTH;
	$post_button           = button($post_text,'','','','','class="engineButton"');
	$post_another_button   = button($add_another_text,'SUBMIT',$button_variable,'','',' class="engineButton"');
	$remember_values_check = $def['add_another_and_remember'] ?
		table(
			row(rightcell(smaller('Remember Values for Next Record'))
				.leftcell(formcheck('engineAddAndRemember',true)))
			,null,' class=""')
		: null;

      $passed_password = $require_password 
		? $AG_AUTH->reconfirm_password()
		: true;  // mimic a valid password in the event that the 'require_password' option is set to false

	/*      
	 * Button-based script navigation
	 */
      $ADD_ANOTHER = ($_REQUEST[$button_variable]==$add_another_text);

	/*
	 * Allow add,edit and delete flags
	 */
      $allow_add    = orr($def['allow_add'],$super_user_perm);
      $allow_edit   = orr($def['allow_edit'],$super_user_perm);
      $allow_delete = orr($def['allow_delete'],$super_user_perm);

	/*
	 * Process form $rec and $REC
	 *
	 * $rec is only used for processing. Anything beyond here uses $REC
	 */
	$rec = $_REQUEST['rec'];
	$def['fn']['process']($REC,$rec,$def);
	$_SESSION['REC'.$session_identifier] = $REC;

      /*
	 * Process quicksearch
	 */
	$qs_results = engine_process_quicksearch($step,$REC,$control);
	if ($qs_results and !strpos($qs_results,'Sorry, no clients matched your search criteria')) {
		return array('output'=>$qs_results);
	} else {
		$output .= $qs_results;
	}

	/*
	 * Process auto-close
	 */
	$message .= $def['fn']['auto_close']($def,$action,$_REQUEST['auto_close_id'],$_REQUEST['auto_close_date']);

	/*
	 * Process staff alert
	 */
	$message .= $def['fn']['process_staff_alert']($def,$REC,$control);
	$_SESSION['CONTROL'.$session_identifier]['staff_alerts'] = $control['staff_alerts'];

	/*
	 * Process object references
	 */

	//$def['fn']['process_object_reference']='process_object_reference_generic';//FIXME
	$message .= process_object_reference_generic($def,$REC,$control);
	merge_object_reference_db($object,$id,$control);
	$_SESSION['CONTROL'.$session_identifier]['object_references'] = orr($control['object_references'],array());

	/*
	 * Any messages from last page
	 */
	$message .= $control['message'];
	$_SESSION['CONTROL'.$session_identifier]['message'] = null;

	/*
	 * Switch on action
	 */
      switch ($action) {
      case 'add' :
	    if (!$write_perm) {
		  $message .= oline("You aren't allowed to $action $object records. Contact your system administrator.");
		  break;
	    }
	    if (!$allow_add) {
		  $message .= oline(ucfirst($object).' records cannot be added. Contact your system administrator.');
		  break;
	    }

	    $add_another = $def['add_another'];

	    /*
	     * Fall through to edit
	     */
      case 'edit' :

		if (!$write_perm) {
		  $message .= oline("You aren't allowed to $action $object records. Contact your system administrator.");
		  break;
		}

		if ( ($action == 'edit') && !$allow_edit) {
			/*
			 * added security for savy link hackers - must check for edit action to allow adds
			 */
			$message .= oline('This record is not editable.');
			break;
		}

	    if ($step=='submit') {
		    if (!$def['fn']['valid']($REC,$def,$message,$action,$REC_LAST)) { 
			    /*
			     * Not valid
			     */
			    $step='continued';
		    } elseif (!$def['fn']['rec_changed']($REC,$REC_LAST,$def)) {
			    /*
			     * No changes made, abort edit and go to view
			     */
			    $message .= 'No changes made during edit.  Record untouched.';
			    $action = 'view';
		    } elseif ( ($action=='add') and $def['single_active_record']
				   and ($res = $def['fn']['get_active']($filter=$REC_INIT,$REC,$def))
				   and (sql_num_rows($res) > 0) ) {
			    /*
			     * Verify/close active record
			     */
			    $a = sql_to_php_generic(sql_fetch_assoc($res),$def);
			    $out_control='';
			    foreach ($CONTROL_PASS as $key) {
				    $out_control .= hiddenvar($control_array_variable.'['.$key.']',$$key);
			    }

			    $conflicting_id = $a[$def['id_field']];

			    /*
			     * This will enforce only authorized closings
			     */
			    $_SESSION['approved_auto_close_'.md5($object . $conflicting_id)] = true;

			    $message .= oline('Overlapping record must be closed prior to adding a new one. You can do one of the following:')
				    . html_list(
						    html_list_item('Close the record with this date: '
									 . formto()
									 . $out_control
									 . hiddenvar($control_array_variable.'[step]',$step)
									 . hiddenvar('auto_close_id',$conflicting_id)
									 . formdate('auto_close_date',prev_day($REC[$object.'_date']))
									 . button($submit_text,'','','','','class="engineButton"')
									 . formend())
						    . html_list_item(link_engine(array('object'=>$object,'action'=>'edit','id'=>$conflicting_id),
											   'Edit overlapping record','',' target="_blank"'))
						    . html_list_item('Modify new record '.hlink('#below','below').' such that it no longer overlaps existing record')
						    )
				    . $def['fn']['view']($a,$def,$action) . anchor('below');
			    $step='continued';
		    } else {
			    /*
			     * Valid record
			     */
			    $step='confirm';
		    }
	    }
	    if ($step=='confirm_pass' or $step=='post') {
		    if ($passed_password) {
			    $step='post';
		    } else {
			    $message .= oline(red('Incorrect password for ' . staff_link($GLOBALS['UID']) ) );
			    $step='confirm';
		    }
	    }
	    if ($step=='post') {
		    if ( $action=='edit' and (!be_null($id)) 
			   and (isset($REC[$def['id_field']])) and (!be_null($REC[$def['id_field']]))
			   and ($id<>$REC[$def['id_field']]) ) {

			    /*
			     * Sanity check. this should NEVER happen
			     */

			    $message .= 'You tried to post a record with a different ID than the control ID.'
				    . 'POST FAILED';
			    log_error('CONTROL ID (' .$id 
					  . ')  NOT MATCHING RECORD ID (' . $REC[$def['id_field']] 
					  . ') Here is the control array: ' . dump_array($control)
					  . 'And here is the record:' . dump_array($REC));
			    break;
		    }
		    
		    /*
		     * Check password
		     */
		    if ($passed_password) {

			    /*
			     * BEGIN transaction
			     */
			  $res = $def['post_with_transactions'] ? sql_begin() : '';

			  if ($changed_rec = $def['fn']['rec_collision']($REC,$REC_LAST,$def,$action,&$message)) {
				  /*
				   * Record collision
				   */
				  $action      = 'view';
				  $step        = 'done';
				  $post_failed = true;
			  } elseif ($def['verify_on_post'] && !$def['fn']['valid']($REC,$def,$message,$action,$REC_LAST)) {
				  /*
				   * Not valid
				   */
				  $step        = 'continued';
				  $post_failed = true;
			  } elseif ($action=='add') {
				  /*
				   * Insert
				   */
				  $a = $def['fn']['post']($REC,$def,$message);
				  if (!$a) { $post_failed = true; }
			  } elseif ($action=='edit') {
				  /*
				   * Update
				   */
				  $filter = array($def['id_field']=>$id);
				  $a      = $def['fn']['post']($REC,$def,$message,$filter,$control);
				  if (!$a) { $post_failed = true; }
			  } else {
				  $message    .= oline('Asked to post, but not in add or edit.  Something is wrong.');
				  $post_failed = true;
			  }

			  /*
			   * Post staff alerts
			   */
			  if (!$post_failed && $alerts = $control['staff_alerts']) {
				  $posted_alerts = true;
				  $adef = get_def('alert');
				  foreach ($alerts as $alert_staff_id) {
					  $n_alert = array();
					  $n_alert['staff_id']   = $alert_staff_id;
					  $n_alert['ref_table']  = $def['object'];
					  $n_alert['ref_id']     = $a[$def['id_field']];
					  $n_alert['added_by']   = $a['added_by'];
					  $n_alert['changed_by'] = $a['changed_by'];
					  if (!$n_alert = $adef['fn']['post']($n_alert,$adef,$message)) {
						  $posted_alerts = false;
					  }
				  }
				  if (!$posted_alerts) {
					  $post_failed = true;
				  }
			  }

			  /*
			   * Post object references
			   */
			  if (!$post_failed && ($refs = $control['object_references']['to'])) {
				  $post_failed = !post_object_references($a,$def,$refs,$message);
			  }
			  
			  if ($post_failed) {
				  /*
				   * Post failed, rollback transaction
				   */
				  if ($def['post_with_transactions']) {
					  sql_abort(); 
					  $message .= oline(red('Transaction has been aborted.'));
				  }
				  $title = 'AGENCY - Posting Record Failed';
				  
			  } else {
				  /*
				   * Record successfully posted, COMMIT transaction
				   */
				  $res = $def['post_with_transactions'] ? sql_end() : '';
				  
				  $REC = orr($a,$REC);

				  /*
				   * Reset session so no double posts occur
				   */
				  $_SESSION['CONTROL'.$session_identifier] = null;
				  if ($ADD_ANOTHER) {
					  /*
					   * Adding another record of the same type, so engine is initiallized
					   */
					  $control['id']      = null;
					  $id                 = engine_set_control_id($control);
					  $session_identifier = generate_session_identifier($object,$id);
					  $step               = '';
					  $_SESSION['CONTROL'.$session_identifier] = array('object'=>$object,'action'=>$action,'id'=>$id,'rec_init'=>$REC_INIT);
					  if ($def['add_another_and_remember']) {
						  add_another_set_rec_init($REC,$def,$REC_INIT,$session_identifier);
					  }
					  $message .= oline('You have requested to add another '.$def['singular'].' record.');
				  } else {
					  $id                 = $REC[$def['id_field']];
					  $step               = 'successful_add_edit';
					  $action             = 'view';
					  $session_identifier = generate_session_identifier($object,$id);
					  // modify session var
					  $_SESSION['CONTROL'.$session_identifier] = array('object'=>$object,'action'=>$action,'id'=>$id);
				  }
				  /*
				   * Re-directing to make cleaner transitions in case of a back or page-reload.
				   */
				  $query_string = $control_array_variable.'[object]='.$object
					  . '&'.$control_array_variable.'[action]='.$action
					  . '&'.$control_array_variable.'[id]='.$id
					  . '&'.$control_array_variable.'[step]='.$step
						;
				  // Long messages can't be passed through URL.	
				  //. '&'.$control_array_variable.'[message]='.urlencode($message);
				  $_SESSION['successful_add_edit_message']= $message;
				  header('Location: '.$page.'?'.$query_string);
				  exit;
			  }
		    } else {
			    /*
			     * Failed to provide correct password
			     */
			    $message .= oline(red('Incorrect password for ' . staff_link($GLOBALS['UID']) ) )
				    . oline(red('However, you shouldn\'t be here at all...'));
		    }
	    }

	    if ($step=='confirm') {
		    /*
		     * Valid record, user is prompted to confirm record before posting
		     */
		    $def['fn']['confirm']($REC,$def,&$message,$action,$REC_LAST);
		    $message = ($message
				    ? (black(oline('Please review these warnings: ',2))
					 . $message . oline(hrule()))
				    : 'Please review your record.');

		    /*
		     * Pass control array variables in form
		     */
		    foreach ($CONTROL_PASS as $key) {
			    $out_control .= hiddenvar($control_array_variable.'['.$key.']',$$key);
			    $link_control .= '&'.$control_array_variable.'['.$key.']='.$$key;
		    }

		    $return_to_edit = hlink($_SERVER['PHP_SELF'].'?'.$control_array_variable.'[step]=continued'.$link_control,
						    'Return to edit','',' class="linkButton"');
			$object_refs = populate_object_references($control) . object_reference_container();
		    $view_rec = ($format=='data') 
			    ? view_generic($REC,$def,$action,$control) 
			    : $def['fn']['view']($REC,$def,$action,$control);
		    $title = oline($def['fn']['title']($action,$REC,$def));

		    if ($control['break_confirm']) {
			    $message .= ' '.$return_to_edit;
			    $output .= $object_refs . $view_rec;
			    break;
		    }
		    
		    $message .= oline('You can either post your record, or return to editing.',2)
			    . formto($page,'',$require_password ? $AG_AUTH->get_onsubmit('') : '')
			    . tablestart()
			    . ( ($require_password) 
				  ? row(red('Enter password for '.staff_link($GLOBALS['UID']).' to confirm '.$action.' ')
					  . $AG_AUTH->get_password_field($auto_focus=true))
				  : '')
			    . row( 
				    ($add_another 
				     ? cell(oline($post_another_button).$remember_values_check)
				     : '')
				    . cell($post_button)
				    . cell($return_to_edit)
				    )
			    . tableend()
			    . hiddenvar($control_array_variable.'[step]','confirm_pass')
			    . $out_control
			    . formend();

		    $output .= oline().$required_fields_text;
		    $output .= $object_refs . $view_rec;

		    if ($def['enable_staff_alerts_'.$action] 
			  && $alerts = get_staff_alerts_generic($REC,$action,$def,$control)) {
			    $output .= div(oline(bold('Staff Alerts:')).$alerts,'',
						 'class="staff" style="width: 22em; margin: 10px; border: solid 1px black; padding: 5px;"');
		    }
	    }
	    if ($step=='new' || (!$step)) {
		    if ($action=='add') {
			    /*
			     * Get blank record
			     */
			    $REC=$def['fn']['blank']($def,$REC_INIT,$control);
			    unset($REC_LAST);
			    $_SESSION['REC_LAST'.$session_identifier]=null;
			    $step='continued';	
		    } elseif ($action=='edit') {
			    /*
			     * Get existing record
			     * For generic get, call function w/ select sql:
			     */
			    $filter = array($def['id_field']=>$id);
			    $REC = ($format=='data')
				    ? get_generic($filter,'','',$def,$def['use_table_post_edit'])
				    : $def['fn']['get']($filter,'','',$def,$def['use_table_post_edit']); //optional order and limit parameters
			    $cnt=sql_num_rows($REC);
			    if ($cnt == 1) {

				    $REC = sql_to_php_generic(sql_fetch_assoc($REC),$def);
				    $control['rec_last'] = $REC_LAST = $_SESSION['REC_LAST'.$session_identifier] = $REC;
 				    $REC = grab_append_only_fields($REC,$def);

				    $step='continued';	

			    } elseif ($cnt == 0) {
				    /*
				     * Record not found
				     */
				    $message .= "ID $id not found for record type $object.  Can't $action.";  
				    $step='error';
			    } elseif ($cnt > 1) {
				    /*
				     * Duplicate primary key - shouldn't ever happen, but just in case
				     */
				    $message .= "More than one record found for ID $id for record type $object. This is very bad!!";
				    $step='error';
			    } else {
				    $message .='The AGENCY Engine is most confused. No record count available.';
				    $step='error';
			    }
		    }
	    }

	    if ($step == 'continued') {
		    $cancel_url = $def['fn']['cancel_url']($REC,$def,$action,$control_array_variable);
		    $cancel_button = hlink($cancel_url,$cancel_text,''
						   ,' class="linkButton" onclick="'.call_java_confirm('Are you sure you want cancel?').'"');
		    $reset_button = hlink($page.'?'.$control_array_variable.'[step]=new'
						  . '&'.$control_array_variable.'[action]='.$action
						  . '&'.$control_array_variable.'[object]='.$object
						  . '&'.$control_array_variable.'[id]='.$id
						  ,$reset_text,''
						  ,' class="linkButton" onclick="'.call_java_confirm('Are you sure you want to reset the form?').'"');
		    /*		    
		     * Pass control array variables in form
		     */
		    foreach ($CONTROL_PASS as $key) {
			    $out_control .= hiddenvar($control_array_variable.'['.$key.']',$$key);
		    }

		    /*
		     * Add staff alert form
		     */
		    if ($def['enable_staff_alerts_'.$action]) {
			    $staff_alerts = add_staff_alert_form_generic($def,$REC,$control);
		    }
			/* Pass on already-selected objects */
			$pre_refs = populate_object_references($control);
			$show_selected = object_reference_container();

			/* Object References Form */
			if ($objs = $def['allow_object_references']) {

				foreach ($objs as $obj ) {
					$t_def=get_def($obj);
					$div_id='';
					$object_refs .= object_selector_generic($obj,$div_id);
					$tab_links .= html_list_item(hlink("#$div_id",$t_def['plural']));
				}
				$tabs = html_list($tab_links,'class="'.$obj.'"');	
				$object_refs_show_link = hlink('','Refer to other records...',NULL,'class="fancyLink" id="objectSelectorShowLink"');
				$object_refs = div($tabs . $object_refs,'objectSelectorForm','class=objectSelectorForm');
			} // end O. R. Form

		    $title = $def['fn']['title']($action,$REC,$def);

		    foreach ($def['fields'] as $field) {
			    if ($field['data_type'] == 'attachment') {
				    $enctype = 'enctype="multipart/form-data"';
			    }
		    }

		    // Set any fields to null if they are attachments which the user
		    // wants removed:
		    for ($i = 0;  $_REQUEST['remove_attachment'.$i]; $i++) {
			    $REC[$_REQUEST['remove_attachment'.$i]] = null;
		    }
		    $output .= formto($page, '', $enctype) //for attachment upload 
				. span(
				div($object_refs,'objectSelector')
				. $show_selected)
			    . div(button($submit_text,'','','','','class="engineButton"') . $reset_button . $cancel_button,'','style="clear: both"')
				. div($pre_refs,'preSelectedObjects')
				. $required_fields_text
				. ' | ' . $object_refs_show_link
			    . div($def['fn']['form']($REC,$def,$control),'','')  //GENERATE THE FORM
			    . $staff_alerts
			    . hiddenvar($control_array_variable.'[step]','submit')
			    . div(button($submit_text,'','','','','class="engineButton"') . $reset_button . $cancel_button,'','')
			    . $out_control
			    . formend();
	    }

	    /*
	     * After post, fall through to view
	     */
	    if ($action != 'view') {
		    break;
	    }
      case 'view' :
		if ( $read_perm ) {
			$filter = array($def['id_field']=>$id);
			$REC = sql_fetch_assoc($def['fn']['get']($filter,'','',$def));
			if (!$REC) {
				$message .= oline("ID $id not found for record type $object.  Can't $action.");
			} else {
				
				/*
				 * For sql arrays, the record must be converted from sql to php
				 */
				$REC = sql_to_php_generic($REC,$def);

				/*
				 * Catch add/edit redirects
				 */
				if ($step=='successful_add_edit') {
					$message = $_SESSION['successful_add_edit_message'] . $message;
					unset($_SESSION['successful_add_edit_message']);
					if ($prepend_add_eval=$def['prepend_finished_add_eval']) {
						$rec = $REC;
						$prepend_add_html = eval('return '.$prepend_add_eval.';');
					}
				}
				$title = oline($def['fn']['title']($action,$REC,$def));
				$output .= $prepend_add_html; //this will be coming from 'add' or 'edit'
				$output .= populate_object_references($control) . object_reference_container();
				$output .= ($format == 'data') 
					? view_generic($REC,$def,$action,$control)
					: $def['fn']['view']($REC,$def,$action,$control);

				//THIS ISN'T READY YET
// 				//-- general child record handling --//
//     				if ( ($format != 'data') and !be_null($def['child_records']) ) {
//     					$output .= list_all_child_records($object,$REC[$def['id_field']],$def);
//     				}
				
                        /*
				 * Client and staff refs/alerts
				 */
				if ($def['display_client_refs']) {
					$client_refs = get_client_refs_generic($REC,$action,$def);
				}
				$staff_alerts = get_staff_alerts_generic($REC,$action,$def,$control);
				$menu = $staff_alerts . $client_refs;

				/*
				 * Make control arrays for various actions
				 */				
				$view_control_array=array('object' => $object,
								  'id'     => $id,
								  'page'   => 'display.php',
								  'format' => 'data',
								  'step'   => 'new',
								  'action' => 'view');
				$edit_control_array=array('action' => 'edit',
								  'step'   => 'new',
								  'page'   => 'display.php',
								  'object' => $object,
								  'id'     => $id,
								  'format' => 'data');
				$delete_control_array=array('action' => 'delete',
								    'object' => $object,
								    'id'     => $id,
								    'page'   => 'display.php',
								    'format' => 'data',
								    'list'   => unserialize(urldecode(stripslashes($list)))
								    );

				if ( ($format=='data') or ($def['fn']['view'] == $engine['functions']['view']) ) {
					if (be_null($def['object_union'])) {
						/*
						 * Normal record view
						 */
						$links = oline(link_engine($edit_control_array,$edit_text));
						$links .= link_engine($delete_control_array,$delete_text);
					} else {

						/*
						 * Object union view, create link to actual data record
						 */
						$identifier = $def['fields'][$def['id_field']]['table_switch']['identifier'];
						$tmp = explode($identifier,$REC[$def['id_field']]);
						if (count($tmp)==2) {
							$tmp_singular = ucwords($engine[$tmp[1]]['singular']);
							$links = link_engine(array('object'=>$tmp[1],
											   'action'=>'view',
											   'id'=>$tmp[0]),'View/Edit '.$tmp_singular.' Data Record');
						}

					}

					/*
					 * Revision history
					 */
					$revision_history_link = oline(smaller(Revision_History::link_history($object,$id)));

					/*
					 * King County Data transmission
					 */
					if (function_exists('clinical_kc_transmission_link')) {
						$clinical_kc_transmission_link = oline(smaller(clinical_kc_transmission_link($object,$id)),2);
					}

				} else {
					/*
					 * Only get a view link if not currently viewing data-view
					 */
					$links = link_engine($view_control_array,$view_text);
				}

				/*
				 * Staff alert form
				 */
				if ($def['enable_staff_alerts_'.$action]) {
					$staff_alert_form = oline() . add_staff_alert_form_generic($def,$REC,$control);
				}

				array_push($commands,cell(table(row(topcell($revision_history_link . $clinical_kc_transmission_link),'height="100%"')
									  . row(bottomcell($links . $staff_alert_form)))));
			}
		} else {
			$message .= oline("You aren't allowed to $action $object records. Contact your system administrator");
			if (has_perm($def['perm_list'])) {
				$message .= link_engine(array('object'=>$object,'action'=>'list'),'List '.$def['plural']);
			}
		}
		break;
		
      case 'list' :
		/*
		 * Permission check
		 */
		if ( $read_perm ) {
			$list=unserialize(urldecode(stripslashes($list)));
			$title =  $def['fn']['list_title']($control,$def);

			/*
			 * Check for available alternate formats
			 */
			$l_control = $m_control = $d_control = $n_control = $control;
			$l_control['format'] = 'long';
			$m_control['format'] = 'medium';
			$d_control['format'] = 'data';
			$n_control['format'] = ''; // normal
			$list_func = $def['fn']['list'];
			$list_func_norm = $engine['functions']['list'];
			$normal_link = link_engine($n_control,smaller('View normal list'),$control_array_variable);
			$data_link = link_engine($d_control,smaller('View data list'),$control_array_variable);

			if ($def['fn']['generate_list_long'] != $engine['functions']['generate_list_long']) {
				$long_listing = true;
				$long_link = link_engine($l_control,smaller('View full text of these '.$def['plural']),$control_array_variable);
			}

			if ($def['fn']['generate_list_medium'] != $engine['functions']['generate_list_medium']) {
				$med_listing = true;
				$med_link = link_engine($m_control,smaller('View expanded list of these '.$def['plural']),$control_array_variable);
			}
			if ($format=='data') {
				$list_func = $engine['functions']['list'];
			} 
			if ($format) {
				$use_link[] = $normal_link;
			}
			if ( ($format=='') and ($list_func != $list_func_norm)) {
				$use_link[] = $data_link;
			}
			if ($format != 'medium' && $med_listing) {
				$use_link[] = $med_link;
			}
			if ($format != 'long' && $long_listing) {
				$use_link[] = $long_link;
			}
			if ($use_link) {
				$use_link = div(implode(' | ',$use_link),'','style="white-space: nowrap; margin: 2px; "');
			}
			/*
			 * Generate list
			 */
			$result = $list_func($control,$def,$control_array_variable,&$total_records,$sid);
			$output .= (($total_records==0) ? '' : $use_link ) . $result;
		} else {

			$message .= oline("You aren't allowed to $action $object records. Contact your system administrator");

		}

		break;
	
      case 'delete' :
		if (!$write_perm) {
			$message .= oline("You aren't allowed to $action $object records. Contact your system administrator");
			break;
		}
		if (!$allow_delete) {
			$message .= oline('This record cannot be deleted.');
			break;
		}

		$filter = array($def['id_field']=>$id);
		$REC = sql_to_php_generic(sql_fetch_assoc(get_generic($filter,'','',$def)),$def);
		if ($step=='confirm_pass' || $step=='delete_confirmed') { //foil fakers trying to bypass password
			if ($passed_password && (!be_null(trim($_REQUEST['delete_comment'])) || !$def['require_delete_comment'] )) {
				$step='delete_confirmed';
			} else {
				$message .= (!$passed_password ? oline(red('Incorrect password for ' . staff_link($GLOBALS['UID']) ) ) : '');
				$message .= ($def['require_delete_comment'] && be_null(trim($_REQUEST['delete_comment']))) 
					? oline(red('A comment is required to delete this record.')) : '';
				$step = 'confirm';
			}
		}
		if ($step != 'delete_confirmed') {
			//PASS CONTROL ARRAY VARIABLES IN FORM
			foreach ($CONTROL_PASS as $key) {
				if ($key != 'action') { //this is set below
					$out_control .= hiddenvar($control_array_variable.'['.$key.']',$$key);
				}
			}
			$message .= 'Are you sure you want to delete this record?'
				. formto('','',$require_password ? $AG_AUTH->get_onsubmit('') : '') 
				. hiddenvar($control_array_variable.'[step]','confirm_pass') 
				. hiddenvar($control_array_variable.'[action]','delete')
				. $out_control
				. tablestart_blank()
				. ($def['require_delete_comment'] 
				   ? rowrlcell('Deletion Comment ('.red('*').')',formvartext('delete_comment',$_REQUEST['delete_comment']))
				   :'')
				. (($require_password) 
				   ? rowrlcell(red('Enter password for '.staff_link($GLOBALS['UID']).' to confirm '.$action ),
						   $AG_AUTH->get_password_field())
				   :'')
				. row(rightcell(
						    button('Yes','','','','','class="engineButton"')
						    . hlink($page.'?'.$control_array_variable.'[action]=view'
								. '&'.$control_array_variable.'[object]='.$object
								. '&'.$control_array_variable.'[id]='.$id
								. '&'.$control_array_variable.'[step]='
								,'No','','class="linkButton"')
						    ,'colspan="2"'))
				. tableend();

			// attempt to create a meaningful list
			if (be_null($control['list']) && $filter = engine_delete_filter($REC,$def)) {
				$message .= form_encode($filter,$control_array_variable.'[list][filter]');
			}

			$message .= formend();

			$output .= $def['fn']['view']($REC,$def,$action);

		} else {
			$control['step']='';

			$res = $def['fn']['delete']($filter,$def,$_REQUEST['delete_comment']);
			$message .= oline($res ?
						'Record '.$id.' successfully deleted from '.$def['table'].'.'
						: 'Error.  Record not deleted.');
			/* generate a list of records as fall-through */
			if ($list = $control['list']) {
				$list['fields']=$def['list_fields'];
				$list['position']=floor($list['position']/orr($list['max'],1))*$list['max'];
				$list['order']=$def['list_order'];
				$control['action']='list';
				$control['list']=$list;
				$output .= $list 
					? oline(list_title_generic($control,$def),2)
					. $def['fn']['list']($control,$def,$control_array_variable,$total_records)
					: '';
			}
		}
		break;
	case 'widget':
		/*
		 * fixme: either make this work or get rid of it
		 */
		return Widget::get_engine_output($control,$def);
		break;
	case 'download':
		
		$attachment_info = get_attachment_content($id, &$message);
		
		if (!$attachment_info) {				
			break;
		}
		
		//check to make sure filesize is as expected:
		$actual_size = strlen($attachment_info['attachment_contents']);
		if ($attachment_info['expected_size'] != 	$actual_size) {
			$message .= oline('Attachment size does not match. Expected attachment size is: '. $attachment_info['expected_size']. ' and actual attachment size is: '. 	$actual_size );
			log_error('Attachment size for id ' . $id . ' does not match. Expected attachment size is: '. $attachment_info['expected_size']. ' and actual attachment size is: '. $actual_size.'.', true ); 
			break;
		} 			
		
		//check to make sure md5 is as expected:
		$actual_md5 = md5($attachment_info['attachment_contents']);
		if ($attachment_info['expected_md5'] != $actual_md5) {
			$message .= oline('Attachment hash does not match. Expected attachment hash is: '. $attachment_info['expected_md5']. ' and actual attachment hash is: '. $actual_md5);
			log_error('Attachment hash for id ' . $id . ' does not match. Expected attachment hash is '. $attachment_info['expected_md5']. ' and actual attachment hash is '. $actual_md5. '.', true );
			break;
		} 
		
		//output headers with file name to display to user and mime_type
		header('Content-Disposition: attachment; filename="'.$attachment_info['output_filename'].'"');
		header('Content-type: '.$attachment_info['mime_type']);
		//output contents of file:
		echo $attachment_info['attachment_contents'];
		exit;
		
		break;
      default :
		$message .= oline( ($action ? "Action $action unknown" : 'No action specified')
					 . '.  I\'m dazed and confused.');
      }

      $result=array('title'         => $title,
			  'total_records' => $total_records,
			  'output'        => $output,
			  'message'       => $message,
			  'commands'      => $commands,
			  'menu'          => $menu,
			  'control'       => $control);
	$_SESSION['REC'.$session_identifier]=$REC; //for robustness...
      return $result;
}

?>
