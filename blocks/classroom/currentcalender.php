<?php
require_once '../../config.php';
require_login();
global $DFC,$PAGE,$DB,$USER,$OUTPUT;
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/classroom/currentcalender.php');
$PAGE->set_title(get_string('ccal_title','block_classroom'));
$PAGE->set_heading(get_string('ccal_heading','block_classroom'));
$PAGE->requires->css('/blocks/classroom/style.css');
echo $OUTPUT->header();

$currentmonth = $DB->get_record_sql("select unix_timestamp(now()) as nextDate");
$startdate1=userdate($currentmonth->nextdate,'%Y/%m/%d');
$startdate= str_replace(' ','',$startdate1);
$curmonth=userdate($currentmonth->nextdate,'%m');
// To calculate the end date
if ($curmonth == '12'){
    $currentmonthEnd = $DB->get_record_sql("select concat 
        (year('$startdate')+1,'/',1,'/',1) as nextDate"); 
}
else{
    $currentmonthEnd = $DB->get_record_sql("select concat 
        (year('$startdate'),'/',month('$startdate')+1,'/',1) as 
        nextDate");
}
$enddate = $currentmonthEnd->nextdate;
$timenow = time();
$timelater = time(); 
$startyear  = optional_param('startyear',  strftime('%Y', $timenow), PARAM_INT);
$startmonth = optional_param('startmonth', strftime('%m', $timenow), PARAM_INT);
$startday   = optional_param('startday',   strftime('%d', $timenow), PARAM_INT);
$endyear    = optional_param('endyear',    strftime('%Y', $timelater), PARAM_INT);
$endmonth   = optional_param('endmonth',   strftime('%m', $timelater), PARAM_INT);
$endday     = optional_param('endday',     strftime('%d', $timelater), PARAM_INT);

$sortby = optional_param('sortby', 'timestart', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$format = optional_param('format', 'ods', PARAM_ALPHA);

$sortbylink = "currentcalender.php?sortby=";
$selectlocation=optional_param('location','All', PARAM_TEXT);
$loc=trim($selectlocation);
if($selectlocation==='All')
{
    $records = $DB->get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, s.programename as program,
        f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
        s.status,u.firstname,u.lastname,
        min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
        FROM {classroom_sessions_dates} d
        JOIN {classroom_sessions} s ON s.id = d.sessionid and s.status<>'Completed'  and s.status<>'Cancelled' and s.datetimeknown=1
        and s.trainingtype <>'Room Request' and s.trainingtype <>'Project Specific/Special Request' and s.trainingsource <> 'Non Calendar'
        LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
        LEFT JOIN mdl_user u on u.id=t.userid
        JOIN {classroom} f ON f.id = s.classroom
        LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
        FROM {classroom_submissions} su
        WHERE su.timecancelled = 0
        GROUP BY sessionid) su ON su.sessionid = d.sessionid
        JOIN {course} c ON f.course = c.id
        JOIN {course_modules} cm ON cm.course = f.course
        AND cm.instance = f.id
        JOIN {modules} m ON m.id = cm.module
        WHERE d.timestart >=  unix_timestamp('$startdate') AND d.timefinish <= unix_timestamp('$enddate')
        AND m.name = 'classroom' and s.trainingsource not in('External')
        group by s.id
        ORDER BY $sortby");
}else{
    $records = $DB->get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, s.programename as program,
        f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
        s.status,u.firstname,u.lastname,
        min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
        FROM {classroom_sessions_dates} d
        JOIN {classroom_sessions} s ON s.id = d.sessionid and s.status<>'Completed' and s.location='$selectlocation' and s.status<>'Cancelled' and s.datetimeknown=1
        and s.trainingtype <>'Room Request' and s.trainingtype <>'Project Specific/Special Request' and s.trainingsource <> 'Non Calendar'
        LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
        LEFT JOIN mdl_user u on u.id=t.userid
        JOIN {classroom} f ON f.id = s.classroom
        LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
        FROM {classroom_submissions} su
        WHERE su.timecancelled = 0
        GROUP BY sessionid) su ON su.sessionid = d.sessionid
        JOIN {course} c ON f.course = c.id 
        JOIN {course_modules} cm ON cm.course = f.course
        AND cm.instance = f.id
        JOIN {modules} m ON m.id = cm.module
        WHERE d.timestart >= unix_timestamp('$startdate') AND d.timefinish <= unix_timestamp('$enddate')
        AND m.name = 'classroom' and s.trainingsource not in('External')
        group by s.id
        ORDER BY $sortby");
}
// Get all Face-to-face session dates from the DB

print_object('check');
echo $OUTPUT->footer();
