<?php

// Displays sessions for which the current user is a "teacher" (can see attendees' list)
// as well as the ones where the user is signed up (i.e. a "student")

require_once '../../config.php';
//require_once '$CFG->wwwroot/mod/classroom/lib.php';

 function csverror($message, $link='') {
        global $CFG, $SESSION;
    
        /// Print the header

    $pagetitle = format_string(get_string('listsessiondates', 'block_classroom'));
$navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation);
    
        $message = clean_text($message);
    
        print_simple_box('<span style="font-family:monospace;color:#000000;">'.$message.'</span>', 'center', '', '#FFBBBB', 5, 'errorbox');
    
        print_footer();
        die;
    }


require_login();

if (!isadmin()) {
        csverror('There are no training sessions for you to mark attendence.');
    }
	
$timenow = time();
$timelater = $timenow + 3 * WEEKSECS;

$startyear  = optional_param('startyear',  strftime('%Y', $timenow), PARAM_INT);
$startmonth = optional_param('startmonth', strftime('%m', $timenow), PARAM_INT);
$startday   = optional_param('startday',   strftime('%d', $timenow), PARAM_INT);
$endyear    = optional_param('endyear',    strftime('%Y', $timelater), PARAM_INT);
$endmonth   = optional_param('endmonth',   strftime('%m', $timelater), PARAM_INT);
$endday     = optional_param('endday',     strftime('%d', $timelater), PARAM_INT);

$sortby = optional_param('sortby', 'timestart', PARAM_ALPHA); // column to sort by
$action = optional_param('action',          '', PARAM_ALPHA); // one of: '', export
$format = optional_param('format',       'ods', PARAM_ALPHA); // one of: ods, xls

$startdate = make_timestamp($startyear, $startmonth, $startday);
$enddate = make_timestamp($endyear, $endmonth, $endday);

$urlparams = "startyear=$startyear&amp;startmonth=$startmonth&amp;startday=$startday&amp;";
$urlparams .= "endyear=$endyear&amp;endmonth=$endmonth&amp;endday=$endday";
$sortbylink = "iltsessions.php?{$urlparams}&amp;sortby=";

$selectlocation=optional_param('location','All', PARAM_TEXT); // column to select location
$selectcourse=optional_param('programename','All', PARAM_TEXT); // column to select location

//Printed


$loc=trim($selectlocation);


if($selectlocation==='All' && $selectcourse==='All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.room,
s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid 
LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
LEFT JOIN mdl_user u on u.id=t.userid
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}
else if($selectcourse==='All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.room,s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid and s.location='$selectlocation'
LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
LEFT JOIN mdl_user u on u.id=t.userid
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate 
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}
else if($selectlocation==='All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.room,s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid 
LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
LEFT JOIN mdl_user u on u.id=t.userid
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id and c.fullname='$selectcourse'
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}
else if ($selectlocation != 'All' && $selectcourse != 'All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.room,s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid  and s.location='$selectlocation'
LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
LEFT JOIN mdl_user u on u.id=t.userid
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id and c.fullname='$selectcourse'
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course 
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}
else
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.room,s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid  and s.location='$selectlocation'
LEFT JOIN mdl_classroom_trainners t on t.sessionid=s.id and t.timecancelled=0
LEFT JOIN mdl_user u on u.id=t.userid
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id and c.fullname='$selectcourse'
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course 
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}

// Get all Face-to-face session dates from the DB

// Only keep the sessions for which this user can see attendees
$dates = array();
if ($records) {
    $capability = 'mod/classroom:viewattendees';

    // Check the system context first
    $contextsystem = get_context_instance(CONTEXT_SYSTEM);
   // if (has_capability($capability, $contextsystem)) {
    //    $dates = $records;
  //  }
  //  else {
        foreach($records as $record) {
            // Check at course level first
            $contextcourse = get_context_instance(CONTEXT_COURSE, $record->courseid);
          //  if (has_capability($capability, $contextcourse)) {
                $dates[] = $record;
                continue;
          //  }

            // Check at module level if the first check failed
            $contextmodule = get_context_instance(CONTEXT_MODULE, $record->cmid);
          //  if (has_capability($capability, $contextmodule)) {
                $dates[] = $record;
          //  }
        }
   // }
}
$nbdates = count($dates);



/**
 * Print the session dates in a nicely formatted table.
 */
function print_dates($dates, $includebookings) {
    global $sortbylink, $CFG;

    $courselink = $CFG->wwwroot.'/course/view.php?id=';
    $classroomlink = $CFG->wwwroot.'/mod/classroom/view.php?f=';
    $attendeelink = $CFG->wwwroot.'/mod/classroom/attendees.php?s=';
	$signuplink= $CFG->wwwroot.'/mod/classroom/attendees.php?s=';


    print '<table border="2" width="100%" CELLPADDING="3"  summary="'.get_string('sessiondatestable', 'block_classroom').'"><tr>';
    print '<th><a href="'.$sortbylink.'coursename">'.get_string('course','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'location">'.get_string('location','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'room">'.get_string('room','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'startdate">'.get_string('startdate','block_classroom').'</a></th>';
    print '<th>'.get_string('enddate','block_classroom').'</th>';
	print '<th width="8%>'.get_string('time','block_classroom').'</th>';
	print '<th width="10%">'.get_string('Trainer','block_classroom').'</th>';
	print '<th><a href="'.$sortbylink.'status">'.get_string('status','block_classroom').'</a></th>';
	
    if ($includebookings) {
        print '<th><a href="'.$sortbylink.'nbbookings">'.get_string('nbbookings', 'block_classroom').'</a></th>';
    }
	print '<th>'.get_string('actions').'</th>';
    print '</tr>';

    $even = false; // used to colour rows
    foreach ($dates as $date) {
        if ($even) {
            print '<tr style="background-color: #eeeeee">';
        }
        else {
            print '<tr>';
        }
        $even = !$even;
        print '<td><a href="'.$courselink.$date->courseid.'">'.format_string($date->coursename).'</a></td>';
        print '<td>'.format_string($date->location).'</td>';
		print '<td>'.format_string($date->room).'</td>';

        print '<td>'.userdate($date->startdate, '%d %B %Y').'</td>';

		print '<td>'.userdate($date->enddate, '%d %B %Y').'</td>';
        print '<td>'.userdate($date->startdate, '%I:%M %p').' - '.userdate($date->enddate, '%I:%M %p').'</td>';
		print '<td>'.format_string($date->firstname).', '.format_string($date->lastname).'</td>';
		print '<td>'.format_string($date->status).'</td>';
		
        if ($includebookings) {
            print '<td>'.(isset($date->nbbookings)? format_string($date->nbbookings) : 0).'</td>';
        }
		
		$usersignup = get_records_sql("SELECT s.id
		from {$CFG->prefix}classroom_sessions s
		JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
		JOIN {$CFG->prefix}classroom_submissions su ON su.sessionid = s.id
		JOIN {$CFG->prefix}course c ON f.course = c.id
		WHERE sid=$date->sessionid AND
		su.userid = $USER->id AND su.timecancelled = 0
		group BY su.sessionid");
		
	
		print '<td><a href="'.$signuplink.$date->sessionid.'" target="_blank"><img src="tick.jpg" alt="Take Attendence" /> </a></td>';
		

        print '</tr>';
    }
    print '</table>';
}



/*
// Get all Face-to-face signups from the DB
$signups = get_records_sql("SELECT d.id, c.id as courseid, c.fullname AS coursename, f.name,
                                   f.id as classroomid, s.id as sessionid, s.location,
                                   d.timestart, d.timefinish
                              FROM {$CFG->prefix}classroom_sessions_dates d
                              JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid
                              JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
                              JOIN {$CFG->prefix}classroom_submissions su ON su.sessionid = s.id
                              JOIN {$CFG->prefix}course c ON f.course = c.id
                             WHERE d.timestart >= $startdate AND d.timefinish <= $enddate AND
                                   su.userid = $USER->id AND su.timecancelled = 0
                          ORDER BY $sortby");
$nbsignups = 0;
if ($signups and count($signups) > 0) {
    $nbsignups = count($signups);
}
*/
$pagetitle = format_string(get_string('listsessiondates', 'block_classroom'));
$navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation);

// Date range form
print '<h2>'.get_string('selectdaterange', 'block_classroom').'</h2>';
print '<form method="get" action=""><p>';
print ' Select start date: ';
print_date_selector('startday', 'startmonth', 'startyear', $startdate);
print ' to ';
print_date_selector('endday', 'endmonth', 'endyear', $enddate);

function session_get_location() { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT content FROM {$CFG->prefix}data_content where fieldid=4;")) 
									
									{

        $i=2;

		$typemenu['All'] = 'All';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->content] = $session->content;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
}



print '<h2>'.get_string('locationandcourse', 'block_classroom').'</h2>';
print ' Select location: ';

$choices = session_get_location();
if (!empty($choices)) {
    choose_from_menu($choices,'location', $form->location, '');
	}
print ' Select course: ';
	function session_get_courses() { 
    global $CFG; 
    if ($setfullname = get_records_sql("SELECT fullname
from mdl_classroom_sessions s
JOIN mdl_classroom f ON f.id = s.classroom
JOIN mdl_course c ON f.course = c.id
group by fullname")) 							
	{
        $i=0;

		$typemenu2['All'] = 'All';
        foreach ($setfullname as $fulname) {
           // $f = $session->id;
            $typemenu2[$fulname->fullname] = $fulname->fullname;
            $i++;
        }

        return $typemenu2;

    } else {
        
        return '';

    }
}

	
$choicecourses = session_get_courses();
if (!empty($choicecourses)) {
    choose_from_menu($choicecourses,'programename','', '');
	}	

print ' <input type="submit" value="Search" /></p></form>';

// Show all session dates
print '<h2>'.get_string('sessiondatestakeattendees', 'block_classroom').'</h2>';
if ($nbdates > 0) {
    print_dates($dates, true);



  //  print get_string('format', 'classroom').':&nbsp;';
 //   print '<select name="format">';
 //   print '<option value="excel" selected="selected">'.get_string('excelformat', 'classroom').'</option>';
 //   print '<option value="ods">'.get_string('odsformat', 'classroom').'</option>';
 //   print '</select>';

 //   print ' <input type="submit" value="'.get_string('exporttofile', 'classroom').'" /></p></form>';
}
else {
    print '<p>'.get_string('sessiondatesviewattendeeszero', 'block_classroom').'</p>';
}

// Show sign-ups
//print '<h2>'.get_string('signedupin', 'block_classroom').'</h2>';
//if ($nbsignups > 0) {
 //   print_dates($signups, false);
//}
//else{
  //  print '<p>'.get_string('signedupinzero', 'block_classroom').'</p>';
//}

print_footer();

?>
