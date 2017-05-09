<?php

// Displays sessions for which the current user is a "teacher" (can see attendees' list)
// as well as the ones where the user is signed up (i.e. a "student")

require_once '../../config.php';
//require_once '$CFG->wwwroot/mod/classroom/lib.php';

require_login();

$timenow = time() - 9 * WEEKSECS;
$timelater = time();

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
$sortbylink = "myiltstatus.php?{$urlparams}&amp;sortby=";

$selectcourse=optional_param('programename','All', PARAM_TEXT); // column to select location
$id = $USER->id;
//Printed





if($selectcourse==='All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
s.status,su.timecancelled,
min(d.timestart) as startdate, max(d.timefinish) as enddate
FROM mdl_classroom_sessions_dates d
JOIN mdl_classroom_sessions s ON s.id = d.sessionid and s.status<>'Completed'
and s.status<>'Cancelled' and s.datetimeknown=1
join mdl_classroom_submissions su on su.sessionid = s.id and su.timecancelled=0
JOIN mdl_user u on u.id=su.userid and u.id=$USER->id
JOIN mdl_classroom f ON f.id = s.classroom
JOIN mdl_course c ON f.course = c.id
JOIN mdl_course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN mdl_modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $enddate 
AND m.name = 'classroom'
group by s.id
ORDER BY $sortby");
}
else
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, c.fullname AS coursename,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
s.status,su.timecancelled,
min(d.timestart) as startdate, max(d.timefinish) as enddate
FROM mdl_classroom_sessions_dates d
JOIN mdl_classroom_sessions s ON s.id = d.sessionid and s.status<>'Completed'
and s.status<>'Cancelled' and s.datetimeknown=1
join mdl_classroom_submissions su on su.sessionid = s.id and su.timecancelled=0
JOIN mdl_user u on u.id=su.userid and u.id=$USER->id
JOIN mdl_classroom f ON f.id = s.classroom
JOIN mdl_course c ON f.course = c.id and c.fullname='$selectcourse'
JOIN mdl_course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN mdl_modules m ON m.id = cm.module
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
 * Export the given session dates into an ODF/Excel spreadsheet
 */
function export_spreadsheet($dates, $format, $includebookings) {
    global $CFG;

    $timenow = time();
    $timeformat = str_replace(' ', '_', get_string('strftimedate'));
    $downloadfilename = clean_filename('classroom_'.userdate($timenow, $timeformat));

    if ('ods' === $format) {
        // OpenDocument format (ISO/IEC 26300)
        require_once($CFG->dirroot.'/lib/odslib.class.php');
        $downloadfilename .= '.ods';
        $workbook = new MoodleODSWorkbook('-');
    }
    else {
        // Excel format
        require_once($CFG->dirroot.'/lib/excellib.class.php');
        $downloadfilename .= '.xls';
        $workbook = new MoodleExcelWorkbook('-');
    }

    $workbook->send($downloadfilename);
    $worksheet =& $workbook->add_worksheet(get_string('sessionlist', 'block_classroom'));

    // Heading (first row)
    $worksheet->write_string(0, 0, get_string('course','block_classroom'));
    $worksheet->write_string(0, 1, get_string('location','block_classroom'));
    $worksheet->write_string(0, 2, get_string('venue','block_classroom'));
    $worksheet->write_string(0, 3, get_string('room','block_classroom'));
    $worksheet->write_string(0, 4, get_string('startdate','block_classroom'));
	$worksheet->write_string(0, 5, get_string('enddate','block_classroom'));
	$worksheet->write_string(0, 6, get_string('Trainer','block_classroom'));

    if ($includebookings) {
        $worksheet->write_string(0, 7, get_string('nbbookings', 'block_classroom'));
    }

    if (!empty($dates)) {
        $i = 0;
        foreach ($dates as $date) {
            $i++;

            $worksheet->write_string($i, 0, $date->coursename);
            $worksheet->write_string($i, 1, $date->location);
            $worksheet->write_string($i, 2, $date->venue);
			$worksheet->write_string($i, 3, $date->room);

			
            if ('ods' == $format) {
                $worksheet->write_date($i, 4, $date->startdate);
                $worksheet->write_date($i, 5, $date->enddate);
            }
            else {
                $worksheet->write_string($i, 4, trim(userdate($date->startdate, get_string('strftimedatetime'))));
                $worksheet->write_string($i, 5, trim(userdate($date->enddate, get_string('strftimedatetime'))));
            }
			$worksheet->write_string($i, 6, $date->firstname.','.$date->lastname);
            if ($includebookings) {
                $worksheet->write_number($i, 7, isset($date->nbbookings) ? $date->nbbookings : 0);
            }
        }
    }

    $workbook->close();
}

/**
 * Print the session dates in a nicely formatted table.
 */
function print_dates($dates, $includebookings) {
    global $sortbylink, $CFG;

    $courselink = $CFG->wwwroot.'/course/view.php?id=';
    $classroomlink = $CFG->wwwroot.'/mod/classroom/view.php?f=';
    $attendeelink = $CFG->wwwroot.'/mod/classroom/attendees.php?s=';
	$signuplink= $CFG->wwwroot.'/mod/classroom/signup.php?s=';

    print '<table border="1" width="100%"  CELLPADDING="2"  summary="'.get_string('sessiondatestable', 'block_classroom').'"><tr>';
    print '<th><a href="'.$sortbylink.'coursename">'.get_string('course','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'location">'.get_string('location','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'venue">'.get_string('venue','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'room">'.get_string('room','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'startdate">'.get_string('startdate','block_classroom').'</a></th>';
    print '<th>'.get_string('enddate','block_classroom').'</th>';
	print '<th>'.get_string('time','block_classroom').'</th>';
	print '<th width="5%">'.get_string('Trainer','block_classroom').'</th>';	
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
		print '<td>'.format_string($date->venue).'</td>';
		print '<td>'.format_string($date->room).'</td>';
        print '<td>'.userdate($date->startdate, '%d %B %Y').'</td>';

		print '<td>'.userdate($date->enddate, '%d %B %Y').'</td>';
        print '<td>'.userdate($date->startdate, '%I:%M-%p').'</td>';
		print '<td >'.format_string($date->firstname).', '.format_string($date->lastname).'</td>';
		
		
		$usersignup = get_records_sql("SELECT s.id
		from {$CFG->prefix}classroom_sessions s
		JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
		JOIN {$CFG->prefix}classroom_submissions su ON su.sessionid = s.id
		JOIN {$CFG->prefix}course c ON f.course = c.id
		WHERE sid=$date->sessionid AND
		su.userid = $USER->id AND su.timecancelled = 0
		group BY su.sessionid");
		if($usersignup)
		{
		print '<td><a href="'.$signuplink.$date->sessionid.'">'.format_string("Select").'</a></td>';
		
		}
		else
		{
		if($date->nbbookings<$date->capacity)
		{
		print '<td><a href="'.$signuplink.$date->sessionid.'">'.format_string("Sign Up").'</a></td>';
		}
		else
		{
		print '<td>'.format_string("Booking Full").'</td>';
		//print '<td>'.format_string($USER->id).'</td>';

		}
		}
        print '</tr>';
    }
    print '</table>';
}


// Process actions if any
if ('export' == $action) {
    export_spreadsheet($dates, $format, true);
    exit;
}


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

$pagetitle = format_string(get_string('listsessiondates', 'block_classroom'));
$navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation);

// Date range form
print '<h2>'.get_string('selectdaterange', 'block_classroom').'</h2>';
print '<form method="get" action=""><p>';
print ' Start date from: ';
print $startday.",".$startmonth.",".$startyear." ";

//print_date_selector('startday', 'startmonth', 'startyear', $startdate);
print ' to ';
print_date_selector('endday', 'endmonth', 'endyear', $enddate);


print '<h2>'.get_string('locationandcourse', 'block_classroom').'</h2>';

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
print '<h2>'.get_string('sessiondatesviewattendees', 'block_classroom').'</h2>';

    print_dates($dates, true);

    // Export form
    print '<h3>'.get_string('exportsessiondates', 'block_classroom').'</h3>';
    print '<form method="post" action=""><p>';
    print '<input type="hidden" name="startyear" value="'.$startyear.'" />';
    print '<input type="hidden" name="startmonth" value="'.$startmonth.'" />';
    print '<input type="hidden" name="startday" value="'.$startday.'" />';
    print '<input type="hidden" name="endyear" value="'.$endyear.'" />';
    print '<input type="hidden" name="endmonth" value="'.$endmonth.'" />';
    print '<input type="hidden" name="endday" value="'.$endday.'" />';
    print '<input type="hidden" name="sortby" value="'.$sortby.'" />';
    print '<input type="hidden" name="action" value="export" />';

    print get_string('format', 'classroom').':&nbsp;';
    print '<select name="format">';
    print '<option value="excel" selected="selected">'.get_string('excelformat', 'classroom').'</option>';
    print '<option value="ods">'.get_string('odsformat', 'classroom').'</option>';
    print '</select>';

    print ' <input type="submit" value="'.get_string('exporttofile', 'classroom').'" /></p></form>';


// Show sign-ups
print '<h2>'.get_string('signedupin', 'block_classroom').'</h2>';
if ($nbsignups > 0) {
   print_dates($signups, false);
}
else{
    print '<p>'.get_string('signedupinzero', 'block_classroom').'</p>';
}

print_footer();

?>
