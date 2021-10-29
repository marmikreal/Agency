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

  /*
   * AGENCY-specific functions
   */

function agency_menu_admin()
{
	/*
	 * AGENCY Administration
	 */

	switch ($_REQUEST['action']) {
	case 'admin_view_object_def' :
		$object = $_REQUEST['object_name'];
		if ($def = get_def($object)) {
			set_time_limit(240);
			if (!$_REQUEST['inc_field_def']) {
				unset($def['fields']);
			}
			$menu['Engine Definition Array for '.$def['singular']] = oline(link_admin('Back to AGENCY Admin page'))
				. oline('Viewing engine config array for object: '.$object)
				. html_pre(print_r($def,true));
			return array($menu);
		} else {
			$error .= alert_mark('No Engine Definition Array available for '.$object);
		}
		break;
	case 'enable_table_logging_all' :
	case 'enable_changed_at_trigger' :
		if (has_perm('super_user')) {
			$verbose = true;
			include 'scripts/'.$_REQUEST['action'].'.php';
			page_close();
			exit;
		}
		break;
	}

	$button = button('Go','','','','',' class="agencyForm"');

	//Update Engine Config options
	$menu['Update Engine Array Stored in DB'] = para('This should be run after any database or configuration update.')
		. update_engine_control();

	//DB-mod information
	$menu['Database Modifications'] = 
	para('These modifications have been applied to the database.  Database modification files are located in the \'database/pg/db_mods\' directory.')
	. call_engine(array('object'=>'db_revision_history','action'=>'list','format'=>''),'',true,true,$perm,$tot_recs);

	//Browse AGENCY tables via Engine
	$menu['Browse AGENCY Tables via Engine'] = para(oline(link_all_db_views())
									 .link_all_db_tables())
		. formto('display.php')
		. oline('Or, enter a table name:')
		. form_field('text','control[object]','','class="agencyForm"') . $button
		. hiddenvar('control[action]','list')
		. hiddenvar('control[format]','raw')
		. formend()
		. oline().link_engine(array('object'=>'db_agency_functions',
						    'action'=>'list'),'List all AGENCY functions');
	
	//Browse Engine Config Options Array
	$menu['Browse Engine Config Array'] = para('These pages may take some time to load')
		. formto(AG_AGENCY_ADMIN_URL)
		. oline('Enter a configured object name:')
		. form_field('text','object_name','','class="agencyForm"') . $button
		. oline() . formcheck('inc_field_def').' Include Field Definitions (these can be '.italic('long').')'
		. hiddenvar('action','admin_view_object_def')
		. formend();
	
	
	// Staff Accounts 
	$sdef = get_def('staff');
	$menu['Staff Account Administration'] = formto('display.php')
		. hiddenvar('control[action]','view')
		. hiddenvar('control[object]','staff')
		. hiddenvar('control[step]','')
		. para(link_engine(array('object'=>'staff','action'=>'add'),'Add a new staff account'))
		. pick_staff_to('control[id]')
		. button_if('View/edit this account', '', 'view_staff','','',has_perm($sdef['perm_view']))
		. formend()
		. show_all_permissions();
	
	// Client Unduplication
	$menu[ucwords(AG_MAIN_OBJECT).' Unduplication'] = html_list(
											html_list_item(link_unduplication('Unduplicate '.ucwords(AG_MAIN_OBJECT)))
											. html_list_item('This link will unduplicate '.AG_MAIN_OBJECT.'s who have already been processed and confirmed as duplicates, in case they were in tables which have only recently been added, or were not unduplicated from the DB for another reason: '.link_unduplication('Check for/unduplicate confirmed duplicate '.AG_MAIN_OBJECT.'s from tables','undup_overlooked')));
	
	
	// Database Maintenance
	$menu['Database Maintenance'] = html_list(
								html_list_item(hlink_if($_SERVER['PHP_SELF'].'?action=enable_table_logging_all','Enable Table Logging for all tbl_* and l_* tables',has_perm('super_user')))
								.html_list_item(hlink_if($_SERVER['PHP_SELF'].'?action=enable_changed_at_trigger','Enable changed_at trigger for all tables containing a changed_at field',has_perm('super_user'))));
	
	return array($menu,$error);
}

function show_all_permissions($title='Show All Staff Permission Records')
{
      $control=array('object'=>'permission',
		     'action'=>'list',
		     'list'=>array(
				   'fields'=>array('staff_id',
							 'permission_type_code',
							 'permission_date',
							 'permission_date_end',
							 'permission_read',
							 'permission_write',
							 'permission_super'),
				   'max'=>200)
		     );
      $control['page']=$_SERVER['PHP_SELF'];
      $control['anchor']='permission';

	//      return call_engine($CONTROL,'admin_perm_control');
	$js_hide = true;
	return engine_java_wrapper($control,'admin_perm_control',$js_hide,$title);
}

function link_all_db_views($label='List all DB Views')
{
	return link_engine(array('object'=>'pg_catalog',
					 'action'=>'list',
					 'format'=>'raw',
					 'list'=>array(
							   'filter'=>array('type'=>'view')
							   )
					 )
				 ,$label);
}

function link_all_db_tables($label='List all DB Tables')
{
	return link_engine(array('object'=>'pg_catalog',
					 'action'=>'list',
					 'format'=>'raw',
					 'list'=>array(
							   'filter'=>array('type'=>'table')
							   )
					 ),$label);
}

function agency_menu_mip()
{
	require_once('mip.php');
	$menu['MIP Export'] = html_list(
						  html_list_item(mip_link_export())
						  . oline()
						  . mip_import_files_list()
						  );
	return array($menu);
}

?>
