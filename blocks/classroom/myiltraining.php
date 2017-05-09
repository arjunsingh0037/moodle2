<?php

// Displays sessions for which the current user is a "teacher" (can see attendees' list)
// as well as the ones where the user is signed up (i.e. a "student")

require_once '../../config.php';
//require_once '$CFG->wwwroot/mod/classroom/lib.php';

require_login();

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
//Naga added for queries only
$qenddate = make_timestamp($endyear, $endmonth, $endday+1);

$startyearext  = optional_param('startyearx',  strftime('%Y', $timenow), PARAM_INT);
$startmonthext = optional_param('startmonthx', strftime('%m', $timenow), PARAM_INT);
$startdayext   = optional_param('startdayx',   strftime('%d', $timenow), PARAM_INT);
$endyearext    = optional_param('endyearx',    strftime('%Y', $timelater), PARAM_INT);
$endmonthext   = optional_param('endmonthx',   strftime('%m', $timelater), PARAM_INT);
$enddayext     = optional_param('enddayx',     strftime('%d', $timelater), PARAM_INT);
$startdateext = make_timestamp($startyearext, $startmonthext, $startdayext);
$enddateext = make_timestamp($endyearext, $endmonthext, $enddayext);
//Naga added for queries only
$qenddateext = make_timestamp($endyearext, $endmonthext, $enddayext+1);

$urlparams = "startyear=$startyear&amp;startmonth=$startmonth&amp;startday=$startday&amp;";
$urlparams .= "endyear=$endyear&amp;endmonth=$endmonth&amp;endday=$endday";

$urlparams = "startyearx=$startyearext&amp;startmonthx=$startmonthext&amp;startdayx=$startdayext&amp;";
$urlparams .= "endyearx=$endyearext&amp;endmonthx=$endmonthext&amp;enddayx=$enddayext";
$sortbylink = "myiltraining.php?sortby=";

$records = get_records_sql("SELECT d.id, c.id AS courseid, s.programename as program,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
s.status,min(d.timestart) as startdate, max(d.timefinish) as enddate
from mdl_classroom_submissions su join mdl_classroom_sessions_dates d
on su.sessionid = d.sessionid
and su.userid=$USER->id and su.timecancelled = 0 and su.attend is null
JOIN mdl_classroom_sessions s ON s.id = d.sessionid and s.timecancelled=0 and s.trainingtype <> 'Internal Training Event'
JOIN mdl_classroom f ON f.id = s.classroom
JOIN mdl_course c ON f.course = c.id
group by s.id
ORDER BY $sortby");

$completedrecords = get_records_sql("SELECT d.id, cm.id AS cmid, c.id AS courseid, s.programename as program,
f.name, f.id as classroomid, s.id as sessionid,s.duration,s.location,s.venue,s.room,
s.status,
min(d.timestart) as startdate, max(d.timefinish) as enddate
FROM mdl_classroom_sessions_dates d
JOIN mdl_classroom_sessions s ON s.id = d.sessionid and s.status='Completed'
and s.status<>'Cancelled' and s.datetimeknown=1 and s.feedbackname !='' 
JOIN mdl_classroom f ON f.id = s.classroom
join mdl_classroom_submissions su on su.sessionid = d.sessionid and su.userid=$USER->id and su.attend=1
JOIN mdl_course c ON f.course = c.id
JOIN mdl_course_modules cm ON cm.course = f.course
AND cm.instance = f.id
JOIN mdl_modules m ON m.id = cm.module
WHERE d.timestart >= $startdate AND d.timefinish <= $qenddate
AND m.name = 'classroom'
group by s.id");



$pendingexternaltraining = get_records_sql("SELECT  s.programename as program,s.location ,s.venue  ,s.room ,
min(d.timestart) as startdate, max(d.timefinish) as enddate,s.status,se.eventnamefile 
FROM mdl_classroom_sessions_dates d
JOIN mdl_classroom_sessions s ON s.id = d.sessionid
join mdl_classroom_sessions_external se ON s.id = d.sessionid and se.sessionid = s.id
WHERE s.trainingtype = 'Internal Training Event' and d.timestart >= $startdateext AND d.timefinish <= $qenddateext and se.userid=$USER->id group by s.id");
// Get all Face-to-face session dates from the DB

// Only keep the sessions for which this user can see attendees
$dates = array();
$completeddates = array();


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
if ($completedrecords) {
    $capability = 'mod/classroom:viewattendees';

    // Check the system context first
    $contextsystem = get_context_instance(CONTEXT_SYSTEM);
   // if (has_capability($capability, $contextsystem)) {
    //    $dates = $records;
  //  }
  //  else {
        foreach($completedrecords as $completedrecord) {
            // Check at course level first
            $contextcourse = get_context_instance(CONTEXT_COURSE, $completedrecord->courseid);
          //  if (has_capability($capability, $contextcourse)) {
                $completeddates[] = $completedrecord;
                continue;
          //  }

            // Check at module level if the first check failed
            $contextmodule = get_context_instance(CONTEXT_MODULE, $completedrecord->cmid);
          //  if (has_capability($capability, $contextmodule)) {
                $completeddates[] = $completedrecord;
          //  }
        }
   // }
}


$nbdates = count($dates);
$nbcomplateddates = count($completeddates);


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
    print '<th class="header">'.get_string('trainingprogramname','block_classroom').'</th>';
    print '<th class="header"><a href="'.$sortbylink.'location">'.get_string('location','block_classroom').'</a></th>';
	print '<th class="header"><a href="'.$sortbylink.'venue">'.get_string('venue','block_classroom').'</a></th>';
	print '<th class="header"><a href="'.$sortbylink.'room">'.get_string('room','block_classroom').'</a></th>';
    print '<th class="header"><a href="'.$sortbylink.'startdate">'.get_string('startdate','block_classroom').'</a></th>';
    print '<th class="header">'.get_string('enddate','block_classroom').'</th>';
	print '<th class="header">'.get_string('time','block_classroom').'</th>';
	print '<th width="5%" class="header">'.get_string('Trainer','block_classroom').'</th>';	
   
	print '<th class="header">'.get_string('actions').'</th>';
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
        print '<td align="center">'.format_string($date->program).'</td>';
        print '<td align="center">'.format_string($date->location).'</td>';
		print '<td align="center">'.format_string($date->venue).'</td>';
		print '<td align="center">'.format_string($date->room).'</td>';
        print '<td align="center">'.userdate($date->startdate, '%d %B %Y').'</td>';

		print '<td align="center">'.userdate($date->enddate, '%d %B %Y').'</td>';
        print '<td align="center">'.userdate($date->startdate, '%I:%M %p').'-'.userdate($date->enddate, '%I:%M %p').'</td>';
		$trainners = get_records_sql("SELECT u.firstname,u.lastname
		from mdl_classroom_trainners t JOIN mdl_user u ON u.id = t.userid and t.sessionid=$date->sessionid
		and t.timecancelled = 0");
		if($trainners)
		{
			print '<td align="center" >';
			foreach ($trainners as $trainner) 
			{
			print format_string($trainner->firstname).' '.format_string($trainner->lastname);
			print '<br/>';
			}
			print '</td>';
		}
		else
		{
			print '<td align="center"> </td>';
		}
		
       
		$feedbacklink= $CFG->wwwroot.'/mod/classroom/signup.php?s='.$date->sessionid.'&cancelbooking=1';
		print '<td align="center"><a href="'.$feedbacklink.$completeddate->sessionid.'"  target="_blank"><img src="cancel.png" align="middle" height="30" width="30" title="Cancel Session" alt="Cancel Session" /></a></td>';


        print '</tr>';
    }
    print '</table>';
}


/**
 * Print the completed session dates in a nicely formatted table.
 */
function print_dates_completed($completedrecords, $includebookings) {
    global $sortbylink, $CFG;

    
	
	

    print '<table border="1" width="100%"  CELLPADDING="2"  summary="'.get_string('sessiondatestable', 'block_classroom').'"><tr>';
    print '<th class="header">'.get_string('trainingprogramname','block_classroom').'</th>';
    print '<th class="header">'.get_string('location','block_classroom').'</th>';
	print '<th class="header">'.get_string('venue','block_classroom').'</th>';
	print '<th class="header">'.get_string('room','block_classroom').'</th>';
    print '<th class="header">'.get_string('startdate','block_classroom').'</th>';
    print '<th class="header">'.get_string('enddate','block_classroom').'</th>';
	print '<th class="header">'.get_string('time','block_classroom').'</th>';
	print '<th width="5%" class="header">'.get_string('Trainer','block_classroom').'</th>';	
   
	print '<th class="header">'.get_string('actions').'</th>';
    print '</tr>';

    $even = false; // used to colour rows
    foreach ($completedrecords as $completeddate) {
        if ($even) {
            print '<tr style="background-color: #eeeeee">';
        }
        else {
            print '<tr>';
        }
        $even = !$even;
       print '<td align="center">'.format_string($completeddate->program).'</td>';
	// print '<td align="center">'.format_string($completeddate->program).'<a><img src="icon.png" height="30" width="30" alt="Download Certificate " background="transparent"/> </a></td>';
        print '<td align="center">'.format_string($completeddate->location).'</td>';
		print '<td align="center">'.format_string($completeddate->venue).'</td>';
		print '<td align="center">'.format_string($completeddate->room).'</td>';
        print '<td align="center">'.userdate($completeddate->startdate, '%d %B %Y').'</td>';

		print '<td align="center">'.userdate($completeddate->enddate, '%d %B %Y').'</td>';
        print '<td align="center">'.userdate($completeddate->startdate, '%I:%M %p').'-'.userdate($completeddate->enddate, '%I:%M %p').'</td>';
		$trainners = get_records_sql("SELECT u.firstname,u.lastname
		from mdl_classroom_trainners t JOIN mdl_user u ON u.id = t.userid and t.sessionid=$completeddate->sessionid
		and t.timecancelled = 0");
		if($trainners)
		{
			print '<td align="center" >';
			foreach ($trainners as $trainner) 
			{
			print format_string($trainner->firstname).' '.format_string($trainner->lastname);
			print '<br/>';
			}
			print '</td>';
		}
		else
		{
			print '<td > </td>';
		}
		
		$feedback = get_records_sql("SELECT id FROM mdl_feedback_user where userid=$USER->id and course_id=$completeddate->sessionid");
		
		
		$feedbackviewid = get_record_sql("SELECT m.id FROM mdl_course_modules m
										join mdl_feedback f where f.id=m.instance 
										and m.module=22 and f.name=(select feedbackname from 
										mdl_classroom_sessions where id=$completeddate->sessionid)");
	
		$feedbacklink= $CFG->wwwroot.'/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid=';
			
		
		if($feedback)
		{
		print '<td>Completed</td>';
		
		}
		else
		{
		print '<td align="center"><a href="'.$feedbacklink.$completeddate->sessionid.'" target="_blank"><img src="feedback.png" align="middle" height="30" width="30" title="Provide Feedback" alt="Provide Feedback" /></a></td>';
		}
       
			
        print '</tr>';
		
    }
    print '</table>';
}

$pagetitle = 'ILT calendar';
$navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation);


print '<form method="get" action=""><p>';

print '<center><h2>'.'MY INSTRUCTOR LED TRAINING SESSIONS'.'</h2></center>';

print '<ul>
<li>Please make sure your respected time zone is set using Update profile before you cancel/provide feedback for any session.</li>
<li>In case of any queries, please contact Training.Helpdesk@nttdata.com</li>
</ul>';



// Show all session dates
print '<h3>'.'Upcoming sessions'.'</h3>';
if ($nbdates > 0) {
    print_dates($dates, true);

}
else {
    print '<p>'.get_string('sessiondatesviewattendeeszero', 'block_classroom').'</p>';
}
print '<br/>';

print '<h3>'.'Completed Sessions'.'</h3>';
print ' Start date from: ';
print_date_selector('startday', 'startmonth', 'startyear', $startdate);
print ' to ';
print_date_selector('endday', 'endmonth', 'endyear', $enddate);

print ' <input type="submit" value="Search" /></p>';
if ($nbcomplateddates > 0) {
    print_dates_completed($completeddates, true);

}
else {
    print '<p>'.get_string('sessiondatesviewattendeeszero', 'block_classroom').'</p>';
}

print '<br/>';


//naga added


function print_dates_completed_status( $pendingexternaltraining, $includebookings) {
    global $sortbylink, $CFG;

    
	
	

    print '<table border="1" width="100%"  CELLPADDING="2"  summary="'.get_string('sessiondatestable', 'block_classroom').'"><tr>';
    print '<th class="header">'.get_string('trainingprogramname','block_classroom').'</th>';
    print '<th class="header">'.get_string('location','block_classroom').'</th>';
	print '<th class="header">'.get_string('venue','block_classroom').'</th>';
	print '<th class="header">'.get_string('room','block_classroom').'</th>';
    print '<th class="header">'.get_string('startdate','block_classroom').'</th>';
    print '<th class="header">'.get_string('enddate','block_classroom').'</th>';
	print '<th class="header">'.get_string('time','block_classroom').'</th>';
	  
	print '<th class="header">'.get_string('status').'</th>';
    print '</tr>';

    $even = false; // used to colour rows
    foreach ( $pendingexternaltraining as $completeddate) {
        if ($even) {
            print '<tr style="background-color: #eeeeee">';
        }
        else {
            print '<tr>';
        }
        $even = !$even;
		$downloadlinkID='/file.php/1/externalilt/'.$completeddate->eventnamefile;
      //print '<td align="center">'.format_string($completeddate->program).'</td>';
		print '<td align="center">'.format_string($completeddate->program).'<a href="'.$downloadlinkID.'" target="_blank"><img src="icon.png" height="30" width="30" alt="Download Certificate"  background="transparent" /> </a></td>';
        print '<td align="center">'.format_string($completeddate->location).'</td>';
		print '<td align="center">'.format_string($completeddate->venue).'</td>';
		print '<td align="center">'.format_string($completeddate->room).'</td>';
        print '<td align="center">'.userdate($completeddate->startdate, '%d %B %Y').'</td>';
		
		print '<td align="center">'.userdate($completeddate->enddate, '%d %B %Y').'</td>';
        print '<td align="center">'.userdate($completeddate->startdate, '%I:%M %p').'-'.userdate($completeddate->enddate, '%I:%M %p').'</td>';
				
		if($completeddate->status=='Open'){
		$status = 'Pending Approval';
		}
		else if($completeddate->status=='Completed'){
		$status = 'Approved';
		}
		else
		{
		$status = $completeddate->status;
		}
		print '<td align="center">'.$status.'</td>';
		$feedback = get_records_sql("SELECT id FROM mdl_feedback_user where userid=$USER->id and course_id=$completeddate->sessionid");
		
		
		$feedbackviewid = get_record_sql("SELECT m.id FROM mdl_course_modules m
										join mdl_feedback f where f.id=m.instance 
										and m.module=22 and f.name=(select feedbackname from 
										mdl_classroom_sessions where id=$completeddate->sessionid)");
	
		$feedbacklink= $CFG->wwwroot.'/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid=';
			
		
       
			
        print '</tr>';
		
         print '</tr>';
    }
    print '</table>';
}

print '<h3>'.'External Training Sessions'.'</h3>';
print ' Start date from: ';
print_date_selector('startdayx', 'startmonthx', 'startyearx', $startdateext);
print ' to ';
print_date_selector('enddayx', 'endmonthx', 'endyearx', $enddateext);

print ' <input type="submit" value="Search" /></p></form>';
print_dates_completed_status($pendingexternaltraining, true);
// if ($nbcomplateddates > 0) {
    // print_dates_completed_status($pendingexternaltraining, true);

// }
// else {
    // print '<p>'.get_string('sessiondatesviewattendeeszero', 'block_classroom').'</p>';
// }

print '<br/>';

print_footer();

?>
