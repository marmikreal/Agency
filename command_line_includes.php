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

/* include only files here required for db connection */
include $off . 'sql_layer.php';
include $off . 'io.php';

define('AG_OUTPUT_MODE',orr($MODE,'HTML')); // set to "TEXT" to disable HTML output
switch (AG_OUTPUT_MODE) {
 case 'HTML' :
	 $NL = '<br />';
	 break;
 case 'TEXT' :
	 $NL = "\n";
	 break;
 default :
	 die('Unknown output mode: '.AG_OUTPUT_MODE);
}

//select default database here
if (!isset($WHICH_DB)) {
	 $WHICH_DB='pg';
}

/* Directory for configuration files */
define('AG_CONFIG_DIR','config');

$local_config_file = $off. AG_CONFIG_DIR . '/agency_config_local.php';
if (is_file($local_config_file)) {
	require $local_config_file;
} else {
	outline('Couldn\'t find <b>'.$local_config_file.
		  '</b> for inclusion.<br /><br /><br />'.
		  'You may need to copy a sample <b>agency_config_local</b> file to <b>agency_config_local.php</b> (and edit accordingly).');
	die();
}

/*
 * agency_config_db.php is new file to hold db username/password.
 * Formerly in agency_config_local.php
 * This is an attempt to stay backward compatible.  If info
 * is still in agency_config_local.php, then things should still work.
 */

$db_config_file = $off. AG_CONFIG_DIR . '/agency_config_db.php';
if (is_file($db_config_file)) {
	require $db_config_file;
}

if (!$AG_DB_CONNECTION = db_connect()) {
	die('Unable to connect to database.');
}


//only include files needed for command-line play
//or, files that won't choke on command-line

include $off . 'daterange.php';
include $off . 'sql.php';
include $off . 'engine_functions.php';
include $off . 'engine_list.php';
include $off . 'engine.php';
include $off . 'password.php';
include $off . 'bugzilla.php';
include $off . 'main_object_common.php';
include $off . 'log.php';
include $off . 'alerts.php';
include $off . 'staff.php';
include $off . 'query.php';
include $off . 'auth.php';
include $off . 'dates.php'; 
include $off . 'unduplication.php';
include $off . 'user_option.php'; //user options class
include $off . 'java_engine.php'; //java engine class
include $off . 'revision_history.php';
include $off . 'output.php';
include $off . 'calendar.php';
include $off . 'widget.php';
include $off . 'news.php';
include $off . 'id_card.php';
include $off . 'image.php';
include $off . 'reports.php';
include $off . 'attachments.php';
include $off . 'link.php';
include $off . 'multi_generics.php';
include $off . 'reference.php';
include $off . 'local.php';

/* read configuration */
include $off . AG_CONFIG_DIR . '/agency_config.php';
include $off . AG_CONFIG_DIR .'/'.AG_MAIN_OBJECT_DB.'_config.php';

switch (AG_MAIN_OBJECT_DB) {

 case 'client' :
	 include $off . 'clients.php';
	 include $off . 'assessments.php';
	 include $off . 'bars.php';
	 include $off . 'charges.php';
	 include $off . 'payments.php';
	 include $off . 'crp.php';
	 include $off . 'jail.php';
	 include $off . 'iandr.php';
	 include $off . 'bedgroup.php';
	 include $off . 'housing.php';
	 include $off . 'clinical.php';
	 include $off . 'entry.php';

	 break;
 case 'donor':
	 include $off . 'donors.php';

	 break;
}

// set AG_FULL_INI to false, prior to including command_line_includes.php
// if, for whatever reason, engine array is not needed. This would mainly
// be for scripts were quick performance is critical. Otherwise, simply
// include the engine array
$AG_FULL_INI = orrn($AG_FULL_INI,true);
if ($AG_FULL_INI) {
	include $off.AG_CONFIG_DIR . '/agency_config_post.php';
}

?>
