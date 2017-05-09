<?php

    require_once('../../config.php');
    require_once('lib.php');

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID
    $f = optional_param('f', 0, PARAM_INT); // classroom Module ID
    $s = optional_param('s', 0, PARAM_INT); // classroom session ID
    $takeattendance = optional_param('takeattendance', 0, PARAM_INT); // take attendance
    $cancelform = optional_param('cancelform'); // cancel request
    $backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // classroom activity to go back to

    if ($id) {

        if (! $cm = get_record('course_modules', 'id', $id)) {
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $classroom = get_record('classroom', 'id', $cm->instance)) {
            error(get_string('error:incorrectcoursemodule', 'classroom'));
        }

    } elseif ($s) {

        if (! $session = classroom_get_session($s)) {
            error(get_string('error:incorrectcoursemodulesession', 'classroom'));
        }
        if (! $classroom = get_record('classroom', 'id', $session->classroom)) {
            error(get_string('error:incorrectclassroomid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $classroom->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $cm = get_coursemodule_from_instance('classroom', $classroom->id, $course->id)) {
            error(get_string('error:incorrectcoursemodule', 'classroom'));
        }

    } else {

        if (! $classroom = get_record('classroom', 'id', $f)) {
            error(get_string('error:incorrectclassroomid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $classroom->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $cm = get_coursemodule_from_instance('classroom', $classroom->id, $course->id)) {
            error(get_string('error:incorrectcoursemodule', 'classroom'));
        }
    }

    $sessiondate = NULL;
    $datetimestart = make_timestamp(2000, 1, 1, 9, 0, 0);
    $datetimefinish = make_timestamp(2000, 1, 1, 12, 0, 0);

    require_course_login($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('mod/classroom:view', $context);

    if ($form = data_submitted()) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        require_capability('mod/classroom:takeattendance', $context);

        if ($cancelform) {
            redirect('trainners.php?s='.$s, '', '4');
        } elseif (classroom_take_attendance($form)) {
            add_to_log($course->id, 'classroom', 'take attendance', "view.php?id=$cm->id", $classroom->id, $cm->id);
        } else {
            add_to_log($course->id, 'classroom', 'take attendance (FAILED)', "view.php?id=$cm->id", $classroom->id, $cm->id);
        }

    }
    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');

    $pagetitle = format_string($session->programename);
    $navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
    $navlinks[] = array('name' => $pagetitle, 'link' => "view.php?f=$classroom->id", 'type' => 'activityinstance');
    $navlinks[] = array('name' => get_string('Trainners', 'classroom'), 'link' => '', 'type' => 'title');
    $navigation = build_navigation($navlinks);
    print_header_simple($pagetitle, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));

    if ($takeattendance && !has_capability('mod/classroom:takeattendance', $context)) {
        $takeattendance = 0;
    }

    if ($takeattendance) {
        $heading = get_string('takeattendance', 'classroom');
    } else {
        add_to_log($course->id, 'classroom', 'view trainners', "view.php?id=$cm->id", $classroom->id, $cm->id);
        $heading = get_string('Trainners', 'classroom');
    }
    if ($session->datetimeknown) {
        $allsessiondates = '';
        foreach ($session->sessiondates as $date) {
            $allsessiondates .= '<tr>';
            $allsessiondates .= '<td align="right">'.userdate($date->timestart, get_string('strftimedaydate')).
                ',</td><td align="left">'.userdate($date->timestart, get_string('strftimetime')).
                ' - '.userdate($date->timefinish, get_string('strftimetime')).'</td>';
            $allsessiondates .= '</tr>';
        }

        $subheading = $session->programename.' - '.$session->venue.'<br /><table border="0">'.$allsessiondates.'</table>';
    } else {
        $subheading = $session->programename.' - '.$session->location.'<br />'.get_string('wait-list', 'classroom');
    }

    print_heading($heading, 'center');
    echo '<center>';
    echo '<strong>'.$subheading.'</strong>';
    echo '</center>';
    echo '<br />';

    if ($takeattendance) {
        echo '<center><span style="font-size: 12px; line-height: 18px;">';
        echo get_string('attendanceinstructions', 'classroom');
        echo '</span></center><br />';
    }

    $table = '';

    if ($takeattendance) {
        $table .= '<form action="trainners.php?s='.$s.'" method="post">';
        $table .= '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';
        $table .= '<input type="hidden" name="s" value="'.$s.'" />';
        $table .= '<input type="hidden" name="action" value="1" />';
    }
    $table .= '<table align="center" cellpadding="3" cellspacing="0" width="600" style="border-color:#DDDDDD; border-width:1px 1px 1px 1px; border-style:solid;">';
    $table .= '<tr>';
    $table .= '<th class="header" align="left">&nbsp;'.get_string('name').'</th>';
    
    $table .= '</tr>';
//*************** Roy Philip:Call classroom_get_trainners to get trainners information ****************
    if ($attendees = classroom_get_trainners($session->id)) {

        foreach($attendees as $attendee) {
            $table .= '<tr>';
            $table .= '<td>&nbsp;<a href="'.$CFG->wwwroot.'/user/view.php?id='.$attendee->id.
                '&amp;course='.$course->id.'">'.$attendee->firstname.', '.$attendee->lastname.'</a></td>';
            if (has_capability('mod/classroom:viewattendees', $context)) {
                if ($takeattendance) {
                    $checkbox_id = 'submissionid_'.$attendee->submissionid;
                    $did_attend = ((int)($attendee->grade)==100)? 1 : 0;
                    $checkbox = print_checkbox($checkbox_id, $did_attend, $did_attend, '', '', '', true);
                    $table .= '<td align="center">'.$checkbox.'</td>';
                } else {
                    if (!get_config(NULL, 'classroom_hidecost')) {
                        $table .= '<td align="center">'.classroom_cost($attendee->id, $session->id, $session).'</td>';
                        if (!get_config(NULL, 'classroom_hidediscount')) {
                            $table .= '<td align="center">'.$attendee->discountcode.'</td>';
                        }
                    }
                    $did_attend = ((int)($attendee->grade)==100)? get_string("yes") : get_string("no");
                    $table .= '<td align="center">'.$did_attend.'</td>';
                }
            }
            $table .= '</tr>';
        }

    } else {
        $table .= '<tr>';
        if (has_capability('mod/classroom:viewattendees', $context)) {
            $table .= '<td colspan="2">&nbsp;'.get_string('nosigneduptrainer', 'classroom').'</td>';
        } else  {
            $table .= '<td>&nbsp;'.get_string('nosigneduptrainer', 'classroom').'</td>';
        }
        $table .= '</tr>';
        
    }
    $table .= '</table>';
    if ($takeattendance) {
        $table .= '<br /><center>';
        $table .= '<input type="submit" value="'.get_string('saveattendance', 'classroom').'" />';
        $table .= '&nbsp;<input type="submit" name="cancelform" value="'.get_string('cancel').'" />';
        $table .= '</center>';

        $table .= '</form>';
    }
    echo $table;

    // Actions
    print '<p style="text-align: center">';
    
    if (has_capability('mod/classroom:editattendees', $context)) {
        // Add/remove attendees
        echo '<a href="'.$CFG->wwwroot.'/mod/classroom/edittrainners.php?s='.$session->id.'">'.get_string('addremovetrainners', 'classroom').'</a> - ';
    }

    $url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    if ($backtoallsessions) {
        $url = $CFG->wwwroot.'/mod/classroom/view.php?f='.$classroom->id.'&amp;backtoallsessions='.$backtoallsessions;
    }
    print '<a href="'.$url.'">'.get_string('goback', 'classroom').'</a></p>';

    if (has_capability('mod/classroom:viewcancellations', $context) && classroom_get_num_cancellations($session->id)) {

        // View cancellations
        echo '<br />';
        print_heading(get_string('cancellations', 'classroom'), 'center');

        $table  = '<table align="center" cellpadding="3" cellspacing="0" width="600" style="border-color:#DDDDDD; border-width:1px 1px 1px 1px; border-style:solid;">';
        $table .= '<tr>';
        $table .= '<th class="header" align="left">&nbsp;'.get_string('name').'</th>';
        $table .= '<th class="header" align="cemter">'.get_string('timesignedup', 'classroom').'</th>';
        $table .= '<th class="header" align="cemter">'.get_string('timecancelled', 'classroom').'</th>';
        $table .= '</tr>';

        $attendees = classroom_get_trainer_cancellations($session->id);

        foreach($attendees as $attendee) {
            $table .= '<tr>';
            $table .= '<td>&nbsp;<a href="'.$CFG->wwwroot.'/user/view.php?id='.$attendee->id.
                '&amp;course='.$course->id.'">'.$attendee->lastname.', '.$attendee->firstname.'</a></td>';
            $table .= '<td align="center">'.userdate($attendee->timecreated, get_string('strftimedatetime')).'</td>';
            $table .= '<td align="center">'.userdate($attendee->timecancelled, get_string('strftimedatetime')).'</td>';
            $table .= '</tr>';
        }

        $table .= '</table>';
        echo $table;
    }

    print_footer($course);

?>
