<?php

require_once('../../config.php');
require_once('lib.php');

define("MAX_USERS_PER_PAGE", 5000);

$s              = required_param('s', PARAM_INT); // classroom session ID
$add            = optional_param('add', 0, PARAM_BOOL);
$remove         = optional_param('remove', 0, PARAM_BOOL);
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_RAW); // search string
$suppressemail  = optional_param('suppressemail', false, PARAM_BOOL); // send email notifications
$previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);

if (!$session = classroom_get_session($s)) {
    error(get_string('error:incorrectcoursemodulesession', 'classroom'));
}
if (!$classroom = get_record('classroom', 'id', $session->classroom)) {
    error(get_string('error:incorrectclassroomid', 'classroom'));
}
if (!$course = get_record('course', 'id', $classroom->course)) {
    error(get_string('error:coursemisconfigured', 'classroom'));
}
if (!$cm = get_coursemodule_from_instance('classroom', $classroom->id, $course->id)) {
    error(get_string('error:incorrectcoursemodule', 'classroom'));
}

/// Check essential permissions
require_course_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);


/// Get some language strings
$strsearch = get_string('search');
$strshowall = get_string('showall');
$strsearchresults = get_string('searchresults');
$strclassrooms = get_string('modulenameplural', 'classroom');
$strclassroom = get_string('modulename', 'classroom');

$errors = array();

/// Handle the POST actions sent to the page
if ($frm = data_submitted()) {

    // Add button
    if ($add and !empty($frm->addselect) and confirm_sesskey()) {


        foreach ($frm->addselect as $adduser) {
            if (!$adduser = clean_param($adduser, PARAM_INT)) {
                continue; // invalid userid
            }


            if (!classroom_user_signup($session, $classroom, $course, '',MDL_F2F_ICAL,
                                            $adduser, !$suppressemail, false)) {
                $erruser = get_record('user', 'id', $adduser, '','','','', 'id, firstname, lastname');
                $errors[] = get_string('error:addattendee', 'classroom', fullname($erruser));
            }
        }
    }
    // Remove button
    else if ($remove and !empty($frm->removeselect) and confirm_sesskey()) {


        foreach ($frm->removeselect as $removeuser) {
            if (!$removeuser = clean_param($removeuser, PARAM_INT)) {
                continue; // invalid userid
            }
			$cancelreasons=' ';
            if (classroom_user_cancel($session, $cancelreasons, $removeuser)) {
                // Notify the user of the cancellation if the session hasn't started yet
                $timenow = time();
                if (!$suppressemail and !classroom_has_session_started($session, $timenow)) {
                    classroom_send_cancellation_notice($classroom, $session, $removeuser);
                }
            }
            else {
                $erruser = get_record('user', 'id', $removeuser, '','','','', 'id, firstname, lastname');
                $errors[] = get_string('error:removeattendee', 'classroom', fullname($erruser));
            }
        }
    }
    // "Show All" button
    elseif ($showall) {
        $searchtext = '';
        $previoussearch = 0;
    }

}

/// Main page
$pagetitle = format_string($session->programename);
$navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
$navlinks[] = array('name' => $pagetitle, 'link' => "view.php?f=$classroom->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('attendees', 'classroom'), 'link' => "attendees.php?s=$session->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('addremoveattendees', 'classroom'), 'link' => '', 'type' => 'title');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));

print_heading('Add attendees to '.$session->programename);


	  
	  
/// Get the list of currently signed-up users
$existingusers = classroom_get_attendees($session->id);
$existingcount = $existingusers ? count($existingusers) : 0;

$select  = "username <> 'guest' AND deleted = 0 AND confirmed = 1";

/// Apply search terms
$searchtext = trim($searchtext);
if ($searchtext !== '') {   // Search for a subset of remaining users
    $LIKE      = sql_ilike();
    $FULLNAME  = sql_fullname();

    $selectsql = " AND ($FULLNAME $LIKE '%$searchtext%' OR email $LIKE '%$searchtext%' OR username $LIKE '%$searchtext%') ";
    $select  .= $selectsql;
}

/// All non-signed up system users
$availableusers = get_recordset_sql('SELECT id,username, firstname, lastname, email
                                       FROM '.$CFG->prefix.'user
                                      WHERE '.$select.'
                                        AND id NOT IN
                                          (
                                            SELECT u.id
                                              FROM '.$CFG->prefix.'classroom_submissions s
                                              JOIN '.$CFG->prefix.'user u ON u.id=s.userid
                                             WHERE s.sessionid='.$session->id.'
                                               AND s.timecancelled = 0
                                          )
                                          ORDER BY lastname ASC, firstname ASC');

$usercount = count_records_select('user', $select) - $existingcount;

echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';
echo '<table width="100%" >';
		echo '<tr>';
		echo '<td valign="top" width="100">';
		echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f1.jpg" width="80px" height="80px" title="'.$USER->firstname.' '.$USER->lastname.'" alt="'.$USER->firstname.' '.$USER->lastname.'" /></a>'; 
		echo '</td>';
		echo '<td>';

		echo '<p style="width:700px; height:100px">Search for employee using portal id, email or their names. Use the arrow buttons to add and remove employees. Once you have added the attendees click on the next button.</p>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
        

		 $toprow[] = new tabobject('addevent','#', 'Step 1: Add Training Event');
		 $toprow[] = new tabobject('addusers','#' , 'Step 2: Add Attendees');
		 $toprow[] = new tabobject('viewsummary','#', 'Step 3: View Details');




    if (!empty($secondrow)) {
        $tabs = array($toprow, $secondrow);
    } else {
        $tabs = array($toprow);
    }

      /// Print out the tabs and continue!
      print_tabs($tabs, 'addusers', $inactive, $activetwo);

echo '</table>';

/// Prints a form to add/remove users from the session
print_simple_box_start('center');

include('editattendeesUser.html');
print_simple_box_end();

if (!empty($errors)) {
    $msg = '<p>';
    foreach ($errors as $e) {
        $msg .= $e.'<br />';
    }
    $msg .= '</p>';
    print_simple_box_start('center');
    notify($msg);
    print_simple_box_end();
}

// Bottom of the page links
print '<p style="text-align: center">';
$url = $CFG->wwwroot.'/mod/classroom/attendees.php?s='.$session->id;

print_footer($course);

?>
