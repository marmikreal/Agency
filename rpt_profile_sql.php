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

$sql_types = array('bed','gate_shelter','housing','dal', 'housed_own', 'housed_elsewhere');

$sql_bed="SELECT bed_date AS date,
                client_id,
                bed_group_code AS description
          FROM bed
          WHERE bed_date BETWEEN '$startdate' AND '$enddate'
                AND bed_group_code IN $GROUP";

$sql_gate_shelter="SELECT DATE(entered_at) AS date,
                 client_id,
                'Gate (Shelter)'::text AS description
           FROM entry
           WHERE
                scanner_location_code ~ '^SHEL' 
                AND DATE(entered_at) BETWEEN '$startdate' AND '$enddate'";

$sql_housing="SELECT residence_date AS date,
                  client_id,
                  housing_project_code AS description
              FROM residence_own
              WHERE residence_date <= '$enddate' 
                 AND (residence_date_end IS NULL OR residence_date_end >= '$startdate')
                 AND ( housing_project_code IN $H_GROUP )";
	
$sql_housed_own="SELECT	residence_date AS date,
	client_id,
	'Housed at " . org_name('short') . "'::TEXT AS description
FROM	residence_own rd1
WHERE	residence_date BETWEEN '$startdate' AND '$enddate'
AND	(move_in_type != 'Unit Transfer' OR move_in_type IS NULL)
AND 	client_id NOT IN 
	(
	SELECT 	client_id 
	FROM 	residence_own 
	WHERE 	(client_id=rd1.client_id) 
	AND 	((rd1.residence_date - residence_date_end) BETWEEN -3 and 3)
	)
AND    COALESCE(move_in_type, 'UNK') != 'Unit Transfer'";

$sql_housed_elsewhere="SELECT  residence_date AS date,
	client_id,
	'Housed Elsewhere'::TEXT AS description
FROM    residence_other
LEFT JOIN l_facility USING (facility_code)
WHERE   residence_date BETWEEN '$startdate' AND '$enddate'
AND     housing_status IN ('HOUSED', 'TRANSITION')
AND	moved_from_code IN 
	(
	SELECT	facility_code
	FROM	l_facility
	WHERE	housing_status IN ('HOMELESS', 'INSTITUT')
	)
 ";

$sql_dal="SELECT dal_date AS date,
             client_id,
             'CLINICAL'::text AS description
          FROM dal
          WHERE dal_date BETWEEN '$startdate' AND '$enddate' AND client_id IS NOT NULL";
	
$tmp_sql = array();
foreach ($sql_types as $tmp_type) {
	if (${strtoupper('sql_'.$tmp_type)}) {
		$tmp_sql[] = ${'sql_'.$tmp_type};
	}
}
$tmp_sql = implode(' UNION ALL ',$tmp_sql);

if ($further_constrain = $custom_submit_sql) {
	$tmp_sql .= ' UNION ALL '.$further_constrain;
	$sql[0]='CREATE TEMPORARY TABLE hold AS SELECT * FROM ('.$tmp_sql
		.') as hold_00 WHERE client_id IN (SELECT client_id FROM ('.$further_constrain.') AS hold_01)';
} else {
	$sql[0]="CREATE TEMPORARY TABLE hold AS " . $tmp_sql;
}

//Unique list of ids for time period
$sql[1]="CREATE TEMPORARY TABLE id_list AS
         SELECT DISTINCT client_id FROM hold";

// Total Unduplicated Clients
$sql['Unduplicated Clients']="SELECT 'Total' AS description , COUNT(client_id)
                              FROM id_list"; 

//bednights
$sql['bednights']="SELECT description,
                        COUNT(client_id) AS count
                   FROM hold
                   WHERE description IN $GROUP
                   GROUP BY description";

//ethnic breakdown

/*
 * Warning:  With implementation of multiple ethnicities, may total over 100%
 */

$sql['ethnicity']="SELECT COUNT(id.client_id), eth.description
         FROM id_list id
              LEFT JOIN ethnicity c ON (c.client_id=id.client_id)
              LEFT JOIN l_ethnicity eth ON (c.ethnicity_code=eth.ethnicity_code)
              GROUP BY eth.description
              ORDER BY eth.description";

$sql['Ethnicity (Simple)'] = "SELECT COUNT(id.client_id), ethnicity_simple(ethnicity_code) AS description
                          FROM id_list id
                                LEFT JOIN ethnicity c USING (client_id)
                          GROUP BY 2 ORDER BY 2";

//gender breakdown
$sql['gender']="SELECT COUNT(id.client_id), g.description
         FROM id_list id
               LEFT JOIN client c ON (c.client_id=id.client_id)
               LEFT JOIN l_gender g ON (c.gender_code=g.gender_code)
         GROUP BY g.description
         ORDER BY g.description";

//language breakdown
$sql['language']="SELECT COUNT(id.client_id), l.description,
         needs_interpreter_code AS \"Needs Interpreter\"
         FROM id_list id
               LEFT JOIN client c ON (c.client_id=id.client_id)
               LEFT JOIN l_language l ON (c.language_code=l.language_code)
         GROUP BY l.description, \"Needs Interpreter\"
         ORDER BY l.description";

/* This needs shelter registrations to be enabled */
/*
//last residence breakdown
$sql['last residence']="SELECT COUNT(id.client_id), l.description
         FROM id_list id
              LEFT JOIN shelter_reg inc USING (client_id)
              LEFT JOIN l_last_residence l USING (last_residence_code)
         WHERE shelter_reg_date <= '$enddate' AND (shelter_reg_date_end IS NULL OR shelter_reg_date_end >= '$startdate')
         GROUP BY l.description
         ORDER BY l.description";
*/

//monthly income breakdown
/*
$sql['income (HUD Guidlines)']="SELECT COUNT(id.client_id),
         CASE
                 WHEN (annual_income*12) < 16351 THEN '< 30%'
                 WHEN (annual_income*12) BETWEEN 16351 AND 27250 THEN '< 50%'
                 WHEN (annual_income*12) BETWEEN 27251 AND 38100 THEN '< 80%'
                 WHEN (annual_income*12) >= 38100 THEN '> 80%'
                 ELSE 'Unknown'
         END AS description
         FROM id_list id
         LEFT JOIN income_new USING (client_id)
         GROUP BY description
         ORDER BY description";
*/
$sql['income (HUD Guidlines)']="SELECT COUNT(id.client_id),
         CASE
                 WHEN (SELECT annual_income FROM income WHERE income.client_id=id.client_id AND income_date <= '$enddate' AND (income_date_end IS NULL OR income_date_end >='$startdate') ORDER BY income_date DESC LIMIT 1) < 16351 THEN '< 30%'
                 WHEN (SELECT annual_income FROM income WHERE income.client_id=id.client_id AND income_date <= '$enddate' AND (income_date_end IS NULL OR income_date_end >='$startdate') ORDER BY income_date DESC LIMIT 1) BETWEEN 16351 AND 27250 THEN '< 50%'
                 WHEN (SELECT annual_income FROM income WHERE income.client_id=id.client_id AND income_date <= '$enddate' AND (income_date_end IS NULL OR income_date_end >='$startdate') ORDER BY income_date DESC LIMIT 1) BETWEEN 27251 AND 38100 THEN '< 80%'
                 WHEN (SELECT annual_income FROM income WHERE income.client_id=id.client_id AND income_date <= '$enddate' AND (income_date_end IS NULL OR income_date_end >='$startdate') ORDER BY income_date DESC LIMIT 1) >= 38100 THEN '> 80%'
                 ELSE 'Unknown'
         END AS description
         FROM id_list id
         GROUP BY description
         ORDER BY description";

$sql['primary income source']="SELECT COUNT(id.client_id), 
         (SELECT i.description FROM income LEFT JOIN l_income i ON (i.income_code=income.income_primary_code)
          WHERE income.client_id=id.client_id AND income_date <= '$enddate' AND (income_date_end IS NULL OR income_date_end >= '$startdate')
          ORDER BY income_date DESC LIMIT 1) AS description
         FROM id_list id
         GROUP BY description
         ORDER BY description";
/*
$sql['average_income']="SELECT (SUM(annual_income)::numeric/COUNT(annual_income)::numeric)::numeric(10,2) as count,
                      'average income' as description
	FROM income WHERE client_id IN (SELECT client_id FROM id_list) AND (income_date_end >= '$startdate' 
                      OR income_date_end IS NULL) AND income_date <= '$enddate';";
*/ 
$sql['average_income']="SELECT (SUM(annual_income)::numeric/(select count(distinct client_id) from id_list)::numeric)::numeric(10,2) as count,
                      'average income' as description
	FROM (
             SELECT DISTINCT ON (client_id) client_id, annual_income 
             FROM (SELECT client_id,annual_income FROM income 
                     WHERE client_id IN (SELECT client_id FROM id_list) AND (income_date_end >= '$startdate' 
                      OR income_date_end IS NULL) AND income_date <= '$enddate' ORDER BY income_date DESC) as inc1) as inc2";
	 
//disability breakdown
$sql['disability']="SELECT COUNT(id.client_id), d.description
         FROM id_list id
              LEFT JOIN 
                   (SELECT DISTINCT ON (client_id,disability_code) * 
                    FROM disability 
                    WHERE disability_date <= '$enddate' AND (disability_date_end IS NULL OR disability_date_end >= '$startdate')
                    ORDER BY client_id, disability_code) dis ON (id.client_id=dis.client_id)
              LEFT JOIN l_disability d ON (dis.disability_code=d.disability_code)
         
         GROUP BY d.description
         ORDER BY d.description";

//grouped disability breakdown
$sql['disability_grouped']="SELECT COUNT(DISTINCT d.client_id),'substance abuse' AS description
	FROM id_list id LEFT JOIN disability d USING (client_id) WHERE disability_code IN ('2','3')
         AND  disability_date <= '$enddate' AND (disability_date_end IS NULL OR disability_date_end >= '$startdate')
UNION
	SELECT COUNT(DISTINCT d.client_id),'physical impairment' AS description
	FROM id_list id LEFT JOIN disability d USING (client_id) WHERE disability_code IN ('4','6','5','45','44','8','7')
         AND  disability_date <= '$enddate' AND (disability_date_end IS NULL OR disability_date_end >= '$startdate')
UNION
	SELECT COUNT(DISTINCT d.client_id),'any disability' AS description
	FROM id_list id LEFT JOIN disability d USING (client_id) WHERE disability_date <= '$enddate' 
         AND (disability_date_end IS NULL OR disability_date_end >= '$startdate')"
;

//age breakdown
$sql['age']="SELECT COUNT(id.client_id),
         CASE
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer < 18 THEN '< 18'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 18 AND 34 THEN '18 - 34'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 35 AND 59 THEN '35 - 59'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 60 AND 74 THEN '60 - 74'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 75 AND 84 THEN '75 - 84'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer >= 85 THEN '85+'
         END AS description
         FROM id_list id
               LEFT JOIN client c ON (c.client_id=id.client_id)
         GROUP BY description
         ORDER BY description";

//age breakdown
/* bug 10964
Birth to 5
6-11
12-17
18-21
22-44
45-54
55-69
70+
*/
$sql['age_alternate_ranges']="SELECT COUNT(id.client_id),
         CASE
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer < 5 THEN '< 5'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 6 AND 11 THEN '6 - 11'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 12 AND 17 THEN '12 - 17'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 18 AND 21 THEN '18 - 21'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 22 AND 44 THEN '22 - 44'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 45 AND 54 THEN '45 - 54'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer BETWEEN 55 AND 69 THEN '55 - 69'
                 WHEN ((DATE(current_timestamp) - dob)/365.24)::integer >= 70 THEN '70+'
         END AS description
         FROM id_list id
               LEFT JOIN client c ON (c.client_id=id.client_id)
         GROUP BY description
         ORDER BY description";

//average age
$sql['average age'] = "
		  SELECT (SUM(('$enddate' - c.dob)/365.24)/COUNT(client_id))::numeric(3,1) AS count,h.description
		  FROM hold h
		  LEFT JOIN tbl_client c USING(client_id)
		  GROUP BY h.description
		  ORDER BY h.description";

//veteran status breakdown
$sql['veteran status']="SELECT COUNT(id.client_id), v.description
   FROM id_list id
       LEFT JOIN client c ON (c.client_id=id.client_id)
       LEFT JOIN l_veteran_status v ON (v.veteran_status_code=c.veteran_status_code)
   GROUP BY v.description
   ORDER BY v.description";
     

//chronic homeless status breakdown
$sql['chronic homeless status']=
"SELECT COUNT(id.client_id), 'Documented as YES' as description
FROM id_list id 
LEFT JOIN residence_own r_d ON (r_d.client_id=id.client_id)  WHERE chronic_homeless_status_code LIKE 'YES%'
AND residence_date = (SELECT min(residence_date) from residence_own rd2 where rd2.client_id = id.client_id AND 
(residence_date <= '$enddate' AND (residence_date_end >= '$startdate' OR residence_date_end IS NULL)))
UNION 
SELECT COUNT(id.client_id), 'Self-Reported as YES' as description
FROM id_list id 
LEFT JOIN chronic_homeless_status_asked ch ON (ch.client_id=id.client_id)  WHERE chronic_homeless_status_code LIKE 'YES%'
AND ch.client_id NOT IN 
  (SELECT client_id FROM residence_own WHERE (chronic_homeless_status_code LIKE 'YES%' or chronic_homeless_status_code = 'NO') AND residence_date = (SELECT min(residence_date) from residence_own rd2 where rd2.client_id = id.client_id AND 
(residence_date <= '$enddate' AND (residence_date_end >= '$startdate' OR residence_date_end IS NULL))))
UNION
SELECT COUNT(id.client_id), 'Documented as NO' as description
FROM id_list id 
LEFT JOIN residence_own r_d ON (r_d.client_id=id.client_id)  WHERE chronic_homeless_status_code = 'NO'
AND residence_date = (SELECT min(residence_date) from residence_own rd2 where rd2.client_id = id.client_id AND 
(residence_date <= '$enddate' AND (residence_date_end >= '$startdate' OR residence_date_end IS NULL)))
UNION
SELECT COUNT(id.client_id), 'Self-Reported as NO' as description
FROM id_list id 
LEFT JOIN chronic_homeless_status_asked ch ON (ch.client_id=id.client_id)  WHERE chronic_homeless_status_code = 'NO'
AND ch.client_id NOT IN 
   (SELECT client_id FROM residence_own WHERE (chronic_homeless_status_code LIKE 'YES%' or chronic_homeless_status_code = 'NO') AND residence_date = (SELECT min(residence_date) from residence_own rd2 where rd2.client_id = id.client_id AND 
(residence_date <= '$enddate' AND (residence_date_end >= '$startdate' OR residence_date_end IS NULL))))
UNION 
SELECT COUNT(id.client_id), null as description
FROM id_list id 
WHERE client_id NOT IN 
  (SELECT client_id FROM residence_own WHERE (chronic_homeless_status_code LIKE 'YES%' or chronic_homeless_status_code = 'NO') AND residence_date = (SELECT min(residence_date) from residence_own rd2 where rd2.client_id = id.client_id AND 
(residence_date <= '$enddate' AND (residence_date_end >= '$startdate' OR residence_date_end IS NULL))) UNION SELECT client_id FROM chronic_homeless_status_asked WHERE chronic_homeless_status_code LIKE 'YES%' or chronic_homeless_status_code = 'NO')
GROUP BY description
	ORDER BY description"
     
?>
