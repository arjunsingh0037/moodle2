
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
$id = $USER->id;


//if (!isadmin()) {
  //      csverror('You must be an administrator to edit page in this way.');
  //  }
	
$timelater = time();
$timenow = $timelater - 4 * WEEKSECS;

$startyear  = optional_param('startyear',  strftime('%Y', $timenow), PARAM_INT);
$startmonth = optional_param('startmonth', strftime('%m', $timenow), PARAM_INT);
$startday   = optional_param('startday',   strftime('%d', $timenow), PARAM_INT);
$endyear    = optional_param('endyear',    strftime('%Y', $timelater), PARAM_INT);
$endmonth   = optional_param('endmonth',   strftime('%m', $timelater), PARAM_INT);
$endday     = optional_param('endday',     strftime('%d', $timelater), PARAM_INT);

$sortby = optional_param('sortby', 'timestart', PARAM_ALPHA); // column to sort by


$startdate = make_timestamp($startyear, $startmonth, $startday);
$enddate = make_timestamp($endyear, $endmonth, $endday);

$urlparams = "startyear=$startyear&amp;startmonth=$startmonth&amp;startday=$startday&amp;";
$urlparams .= "endyear=$endyear&amp;endmonth=$endmonth&amp;endday=$endday";
$sortbylink = "ilttrainer.php?{$urlparams}&amp;sortby=";
$selectlocation=optional_param('location','All', PARAM_TEXT); // column to select location


//Printed


$loc=trim($selectlocation);


if($selectlocation=='All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid,
f.name, f.id as classroomid, s.id as sessionid,s.programename as program,s.duration,s.location,s.venue,s.room,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid and s.status<>'Completed' and s.status<>'Cancelled'
JOIN {$CFG->prefix}classroom_trainners ct on ct.sessionid=s.id and ct.userid=$id and ct.timecancelled='0'
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}
else
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid,
f.name, f.id as classroomid, s.id as sessionid,s.programename as program,s.duration,s.location,s.venue,s.room,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid and s.status<>'Completed' and s.status<>'Cancelled' and s.location='$selectlocation'
JOIN {$CFG->prefix}classroom_trainners ct on ct.sessionid=s.id and ct.userid=$id and ct.timecancelled='0'
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}


$completedrecords = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid,c.fullname,
f.name, f.id as classroomid, s.id as sessionid,s.programename as program,s.duration,s.location,s.venue,s.room,
min(d.timestart) as startdate, max(d.timefinish) as enddate,DATE_FORMAT(FROM_UNIXTIME(d.timestart),'%Y-%m-%d %H:%i') as reportdate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid and s.status='Completed'
JOIN {$CFG->prefix}classroom_trainners ct on ct.sessionid=s.id and ct.userid=$id and ct.timecancelled='0'
JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
LEFT OUTER JOIN (SELECT sessionid, count(sessionid) AS nbbookings
FROM {$CFG->prefix}classroom_submissions su
WHERE su.timecancelled = 0 and su.attend =1 
GROUP BY sessionid) su ON su.sessionid = d.sessionid
JOIN {$CFG->prefix}course c ON f.course = c.id 
JOIN {$CFG->prefix}course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN {$CFG->prefix}modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");

$dates = array();
if ($records) {
    $capability = 'mod/classroom:viewattendees';


    $contextsystem = get_context_instance(CONTEXT_SYSTEM);

        foreach($records as $record) {
  
            $contextcourse = get_context_instance(CONTEXT_COURSE, $record->courseid);
     
                $dates[] = $record;
                continue;

            $contextmodule = get_context_instance(CONTEXT_MODULE, $record->cmid);
    
                $dates[] = $record;

        }

}
$nbdates = count($dates);


$completeddates = array();
if ($completedrecords) {
    $capability = 'mod/classroom:viewattendees';


    $contextsystem = get_context_instance(CONTEXT_SYSTEM);

        foreach($completedrecords as $record) {
  
            $contextcourse = get_context_instance(CONTEXT_COURSE, $record->courseid);
     
                $completeddates[] = $record;
                continue;

            $contextmodule = get_context_instance(CONTEXT_MODULE, $record->cmid);
    
                $completeddates[] = $record;

        }

}
$nbcomplateddates = count($completeddates);


/**
 * Print the session dates in a nicely formatted table.
 */
function print_dates($dates, $includebookings) {
    global $sortbylink, $CFG;

    $courselink = $CFG->wwwroot.'/course/view.php?id=';
    $classroomlink = $CFG->wwwroot.'/mod/classroom/view.php?f=';
    $attendeelink = $CFG->wwwroot.'/mod/classroom/attendees.php?s=';
	$signuplink= $CFG->wwwroot.'/mod/classroom/attendees.php?s=';


    print '<table border="2"  CELLPADDING="3"  summary="'.get_string('sessiondatestable', 'block_classroom').'"><tr>';
    print '<th>Training</th>';
    print '<th><a href="'.$sortbylink.'location">'.get_string('location','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'venue">'.get_string('venue','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'room">'.get_string('room','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'startdate">'.get_string('startdate','block_classroom').'</a></th>';
    print '<th>'.get_string('enddate','block_classroom').'</th>';
	print '<th>'.get_string('time','block_classroom').'</th>';
    print '<th>Signed Up</th>';
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
        print '<td><a href="'.$courselink.$date->courseid.'">'.format_string($date->program).'</a></td>';
        print '<td>'.format_string($date->location).'</td>';
		print '<td>'.format_string($date->venue).'</td>';
		print '<td>'.format_string($date->room).'</td>';

        print '<td>'.userdate($date->startdate, '%d %B %Y').'</td>';

		print '<td>'.userdate($date->enddate, '%d %B %Y').'</td>';
        print '<td>'.userdate($date->startdate, '%I:%M %p').' - '.userdate($date->enddate, '%I:%M %p').'</td>';
		print '<td>'.format_string($date->nbbookings).'</td>';
		
		
		print '<td align="center"><a href="'.$signuplink.$date->sessionid.'" target="_blank"><img src="tick.jpg" alt="Take Attendence" /> </a></td>';
		
	
        print '</tr>';
    }
    print '</table>';
}




/**
 * Print completed sessions.
 */
function print_dates_completed($completeddates, $includebookings) {
    global $sortbylink, $CFG;

    $courselink = $CFG->wwwroot.'/course/view.php?id=';
    $classroomlink = $CFG->wwwroot.'/mod/classroom/view.php?f=';
    $attendeelink = $CFG->wwwroot.'/mod/classroom/attendees.php?s=';
	$signuplink= $CFG->wwwroot.'/mod/classroom/attendees.php?s=';	
	

    print '<table border="2"  CELLPADDING="3"  summary="'.get_string('sessiondatestable', 'block_classroom').'"><tr>';
    print '<th>Training</th>';
    print '<th><a href="'.$sortbylink.'location">'.get_string('location','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'venue">'.get_string('venue','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'room">'.get_string('room','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'startdate">'.get_string('startdate','block_classroom').'</a></th>';
    print '<th>'.get_string('enddate','block_classroom').'</th>';
	print '<th>'.get_string('time','block_classroom').'</th>';
	print '<th>Attended</th>';
	print '<th>'.get_string('actions').'</th>';
    print '</tr>';

    $even = false; // used to colour rows
    foreach ($completeddates as $date) {
        if ($even) {
            print '<tr style="background-color: #eeeeee">';
        }
        else {
            print '<tr>';
        }
        $even = !$even;
        print '<td><a href="'.$courselink.$date->courseid.'">'.format_string($date->program).'</a></td>';
        print '<td>'.format_string($date->location).'</td>';
		print '<td>'.format_string($date->venue).'</td>';
		print '<td>'.format_string($date->room).'</td>';

        print '<td>'.userdate($date->startdate, '%d %B %Y').'</td>';

		print '<td>'.userdate($date->enddate, '%d %B %Y').'</td>';
        print '<td>'.userdate($date->startdate, '%I:%M %p').' - '.userdate($date->enddate, '%I:%M %p').'</td>';
		print '<td>'.format_string($date->nbbookings).'</td>';			
		$reportParams=array($date->fullname,$date->location,$date->reportdate);   		
		//$downloadlink='processfeedback.php?c='.$reportParams[0].'&d='.$reportParams[2].'&l='.$reportParams[1];
		$downloadlinkID='processfeedback.php?id='.$date->sessionid;
		//$downloadlink='selectCourse='.$name.'&selectLocation='.$location.'&selectDate='.$date.'&__format=pdf';
		$Feedbackrecord = get_records_sql("SELECT distinct course_id as id FROM mdl_feedback_value where course_id=$date->sessionid group by course_id");
		 if($Feedbackrecord)
		 {		
		print '<td align="center"><a href="'.$downloadlinkID.'" target="_blank"><img src="pdf.png" height="30" width="30" alt="Download feedback" /> </a></td>';
		
		}
		else
		{
		print '<td align="center"><img src="pdfgrey.png" height="30" width="30" alt="No feedback" /></td>';
		}
        print '</tr>';
    }
    print '</table>';
}


$pagetitle = format_string(get_string('listsessiondates', 'block_classroom'));
$navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation);


print '<form method="get" action=""><p>';


function session_get_location() { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT content FROM {$CFG->prefix}data_content where fieldid=4;")) 
									
									{

        $i=2;

		$typemenu['All'] = 'All';
        foreach ($sessions as $session) {           
            $typemenu[$session->content] = $session->content;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
}



print '<h2>Current Sessions</h2>';
print ' Select location: ';

$choices = session_get_location();
if (!empty($choices)) {
    choose_from_menu($choices,'location', $form->location, '');
	}

print ' <input type="submit" value="Search" /></p>';

if ($nbdates > 0) {
print '<h3>'.get_string('sessiondatestakeattendees', 'block_classroom').'</h3>';
    print_dates($dates, true);

}
else {
    print '<p>'.get_string('sessiondatesviewattendeeszero', 'block_classroom').'</p>';
}

print '<h2>'.'Completed Sessions'.'</h2>';
print ' Start date from: ';
print_date_selector('startday', 'startmonth', 'startyear', $startdate);
print ' to ';
print_date_selector('endday', 'endmonth', 'endyear', $enddate);

print ' <input type="submit" value="Search" /></p></form>';
if ($nbcomplateddates > 0) {
print '<h3>You can download feedback for the below sessions</h3>';
    print_dates_completed($completeddates, true);
}
else {
    print '<p>There are no sessions completed by you on the given dates</p>';
}

print '<br/>';
print_footer();

?>
