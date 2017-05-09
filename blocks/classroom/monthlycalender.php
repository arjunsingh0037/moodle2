<?php

// Displays sessions for which the current user is a "teacher" (can see attendees' list)
// as well as the ones where the user is signed up (i.e. a "student")

require_once '../../config.php';
//require_once '$CFG->wwwroot/mod/classroom/lib.php';

require_login();

// Naga commented the existing code
// $checkmonth = get_records_sql("select month(DATE_ADD(CURDATE(),INTERVAL 0 MONTH)) as ckmonth");
 // foreach ($checkmonth  as $chckmonth) {
// $curmonth= $chckmonth->ckmonth;
        // }
// if($curmonth == '1'){
// $nextmonth = get_records_sql("select concat(year(DATE_ADD(CURDATE(),INTERVAL -1 MONTH)),'/',12,'/',31) as nextDate");		
// }
// else{
// $nextmonth = get_records_sql("select concat(year(DATE_ADD(CURDATE(),INTERVAL 0 MONTH)),'/',month(DATE_ADD(CURDATE(),INTERVAL 0 MONTH)),'/',31) as nextDate");		
// }
// if($curmonth == '11'){
// $nextmonthEnd = get_records_sql("select concat(year(DATE_ADD(CURDATE(),INTERVAL 2 MONTH)),'/',month(DATE_ADD(CURDATE(),INTERVAL 2 MONTH)),'/',1) as nextDate");
// }
// else{
// $nextmonthEnd = get_records_sql("select concat(year(DATE_ADD(CURDATE(),INTERVAL 1 MONTH)),'/',month(DATE_ADD(CURDATE(),INTERVAL 2 MONTH)),'/',1) as nextDate");
// }


 // foreach ($nextmonth  as $nxtmonth) {
// $startdate= $nxtmonth->nextDate;
        // }

// foreach ($nextmonthEnd  as $nxtmonthend) {
// $enddate = $nxtmonthend->nextDate;
        // }		

// commenting over

// naga added below code
$currentmonth = get_record_sql("select unix_timestamp(now()) as nextDate");  // give the current  system itme.


$startdate1=userdate($currentmonth->nextDate,'%Y/%m/%d');  //give the users time.

$startdate2= str_replace(' ','',$startdate1);  // to remove the spaces .

$curmonth=userdate($currentmonth->nextDate,'%m');  // To find the current month.


// To calculate the end date

  if ($curmonth == '12'){
$nextmonth = get_record_sql("select concat (year('$startdate2')+1,'/',1,'/',1) as nextDate"); 
}
else{
$nextmonth = get_record_sql("select concat (year('$startdate2'),'/',month('$startdate2')+1,'/',1) as nextDate");
}
      
$startdate = $nextmonth->nextDate;
 if ($curmonth == '12'){
$currentmonthEnd = get_record_sql("select concat (year('$startdate2')+1,'/',2,'/',1) as nextDate"); 
}
else if ($curmonth == '11'){
$currentmonthEnd = get_record_sql("select concat (year('$startdate2'),'/',month('$startdate2')+1,'/',31) as nextDate");
}
else{
$currentmonthEnd = get_record_sql("select concat (year('$startdate2'),'/',month('$startdate2')+2,'/',1) as nextDate");
}
$enddate = $currentmonthEnd->nextDate;

$startyear  = optional_param('startyear',  strftime('%Y', $timenow), PARAM_INT);
$startmonth = optional_param('startmonth', strftime('%m', $timenow), PARAM_INT);
$startday   = optional_param('startday',   strftime('%d', $timenow), PARAM_INT);
$endyear    = optional_param('endyear',    strftime('%Y', $timelater), PARAM_INT);
$endmonth   = optional_param('endmonth',   strftime('%m', $timelater), PARAM_INT);
$endday     = optional_param('endday',     strftime('%d', $timelater), PARAM_INT);

$sortby = optional_param('sortby', 'timestart', PARAM_ALPHA); // column to sort by
$action = optional_param('action',          '', PARAM_ALPHA); // one of: '', export
$format = optional_param('format',       'ods', PARAM_ALPHA); // one of: ods, xls



$sortbylink = "monthlycalender.php?sortby=";

$selectlocation=optional_param('location','All', PARAM_TEXT); // column to select location


//Printed


$loc=trim($selectlocation);


if($selectlocation==='All')
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, s.programename as program,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid and s.status<>'Completed'  and s.status<>'Cancelled' and s.datetimeknown=1
and s.trainingtype <>'Room Request' and s.trainingtype <>'Project Specific/Special Request' and s.trainingsource <> 'Non Calendar'
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
WHERE d.timestart >=  unix_timestamp('$startdate') AND d.timefinish < unix_timestamp('$enddate')
AND m.name = 'classroom' and s.trainingsource not in('External')
group by s.id
ORDER BY $sortby");
}
else
{
$records = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, s.programename as program,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
s.status,u.firstname,u.lastname,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.capacity,su.nbbookings
FROM {$CFG->prefix}classroom_sessions_dates d
JOIN {$CFG->prefix}classroom_sessions s ON s.id = d.sessionid and s.status<>'Completed' and s.location='$selectlocation' and s.status<>'Cancelled' and s.datetimeknown=1
and s.trainingtype <>'Room Request' and s.trainingtype <>'Project Specific/Special Request' and s.trainingsource <> 'Non Calendar'
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
WHERE d.timestart >= unix_timestamp('$startdate') AND d.timefinish < unix_timestamp('$enddate')
AND m.name = 'classroom' and s.trainingsource not in('External')
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
    $worksheet->write_string(0, 0, get_string('trainingprogramname','block_classroom'));
    $worksheet->write_string(0, 1, get_string('location','block_classroom'));
    $worksheet->write_string(0, 2, get_string('venue','block_classroom'));
    $worksheet->write_string(0, 3, get_string('room','block_classroom'));
    $worksheet->write_string(0, 4, get_string('startdate','block_classroom'));
	$worksheet->write_string(0, 5, get_string('enddate','block_classroom'));
	$worksheet->write_string(0, 6, get_string('Trainer','block_classroom'));

  

    if (!empty($dates)) {
        $i = 0;
        foreach ($dates as $date) {
            $i++;

            $worksheet->write_string($i, 0, $date->program);
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
			$worksheet->write_string($i, 6, $date->firstname.' '.$date->lastname);
      
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
    print '<th><a href="'.$sortbylink.'program">'.get_string('trainingprogramname','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'location">'.get_string('location','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'venue">'.get_string('venue','block_classroom').'</a></th>';
	print '<th><a href="'.$sortbylink.'room">'.get_string('room','block_classroom').'</a></th>';
    print '<th><a href="'.$sortbylink.'startdate">'.get_string('startdate','block_classroom').'</a></th>';
    print '<th>'.get_string('enddate','block_classroom').'</th>';
	print '<th>'.get_string('time','block_classroom').'</th>';
	print '<th width="5%">'.get_string('Trainer','block_classroom').'</th>';
	print '<th><a href="'.$sortbylink.'seats">Seats</a></th>';
   
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
        print '<td>'.format_string($date->program).'</a></td>';
        print '<td>'.format_string($date->location).'</td>';
		print '<td>'.format_string($date->venue).'</td>';
		print '<td>'.format_string($date->room).'</td>';
        print '<td>'.userdate($date->startdate, '%d %B %Y').'</td>';

		print '<td>'.userdate($date->enddate, '%d %B %Y').'</td>';
        print '<td>'.userdate($date->startdate, '%I:%M %p').'-'.userdate($date->enddate, '%I:%M %p').'</td>';
		print '<td >'.format_string($date->firstname).'  '.format_string($date->lastname).'</td>';
		print '<td>'.format_string($date->capacity).'</td>';
		
       
		
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


$pagetitle = 'Upcoming ILT calendar';
$navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation);


print '<form method="get" action=""><p>';

print '<center><h2>'.'UPCOMING TRAINING CALENDAR'.'</h2></center>';

print '<ul>
<li>This is the home page for the next monthly calendar.</li>
<li>Please make sure your respected time zone is set using Update profile before you sign up for any session.</li>
<li>Please refer to the list of programs with their details and sign up.</li>
<li>Do mention your manager"s email address so that he/she is updated of the training plan.</li>
<li>After registering for a program, if you wish to cancel your nomination for any reason, please do log back into LMS and cancel your nomination.</li>
<li>In case of any queries, please contact Training.Helpdesk@nttdata.com</li>
</ul>';



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



print '<h3>'.'Select your Location'.'</h3>';
print ' Select location: ';

$choices = session_get_location();
if (!empty($choices)) {
    choose_from_menu($choices,'location', $form->location, '');
	}

	

print ' <input type="submit" value="Search" /></p></form>';

// Show all session dates
print '<h3>'.'You can sign up for the Upcoming ILT sessions:'.'</h3>';
if ($nbdates > 0) {
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
}
else {
    print '<p>'.get_string('sessiondatesviewattendeeszero', 'block_classroom').'</p>';
}



print_footer();

?>
