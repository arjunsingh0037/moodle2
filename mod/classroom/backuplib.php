<?php
  //------------------------------------------------------------------
  // This is the "graphical" structure of the Facet-to-face module:
  //
  //          classroom                  classroom_sessions
  //         (CL, pk->id)-------------(CL, pk->id, fk->classroom)
  //              |                          |        |
  //              |                          |        |
  //              |                          |        |
  //              |            +-------------+        |
  //              |            |                      |
  //          classroom_submissions                  |
  //  (UL, pk->id, fk->classroom, fk->sessionid)     |
  //                                                  |
  //                                                  |
  //                                    classroom_sessions_dates
  //                                    (CL, pk->id, fk->session)
  //
  // Meaning: pk->primary key field of the table
  //          fk->foreign key to link with parent
  //          CL->course level info
  //          UL->user level info
  //
  //------------------------------------------------------------------

/**
 * API function called by the Moodle backup system to backup all of
 * the classroom activities
 */
function classroom_backup_mods($bf, $preferences) {

    $status = true;

    $classrooms = get_records ('classroom', 'course', $preferences->backup_course, 'id');
    if ($classrooms) {
        foreach ($classrooms as $classroom) {
            if (backup_mod_selected($preferences, 'classroom', $classroom->id)) {
                $status &= classroom_backup_one_mod($bf, $preferences, $classroom);
            }
        }
    }
    return $status;
}

/**
 * API function called by the Moodle backup system to backup a single
 * classroom activity
 */
function classroom_backup_one_mod($bf, $preferences, $classroom) {

    if (is_numeric($classroom)) {
        $classroom = get_record('classroom', 'id', $classroom);
    }

    $status = fwrite($bf, start_tag('MOD', 3, true)) > 0;

    // classroom table
    $status &= fwrite($bf, full_tag('ID', 4, false, $classroom->id)) > 0;
    $status &= fwrite($bf, full_tag('MODTYPE', 4, false, 'classroom')) > 0;
    $status &= fwrite($bf, full_tag('NAME', 4, false, $classroom->name)) > 0;
    $status &= fwrite($bf, full_tag('THIRDPARTY', 4, false, $classroom->thirdparty)) > 0;
    $status &= fwrite($bf, full_tag('DISPLAY', 4, false, $classroom->display)) > 0;
    $status &= fwrite($bf, full_tag('CONFIRMATIONSUBJECT', 4, false, $classroom->confirmationsubject)) > 0;
    $status &= fwrite($bf, full_tag('CONFIRMATIONINSTRMNGR', 4, false, $classroom->confirmationinstrmngr)) > 0;
    $status &= fwrite($bf, full_tag('CONFIRMATIONMESSAGE', 4, false, $classroom->confirmationmessage)) > 0;
    $status &= fwrite($bf, full_tag('WAITLISTEDSUBJECT', 4, false, $classroom->waitlistedsubject)) > 0;
    $status &= fwrite($bf, full_tag('WAITLISTEDMESSAGE', 4, false, $classroom->waitlistedmessage)) > 0;
    $status &= fwrite($bf, full_tag('CANCELLATIONSUBJECT', 4, false, $classroom->cancellationsubject)) > 0;
    $status &= fwrite($bf, full_tag('CANCELLATIONINSTRMNGR', 4, false, $classroom->cancellationinstrmngr)) > 0;
    $status &= fwrite($bf, full_tag('CANCELLATIONMESSAGE', 4, false, $classroom->cancellationmessage)) > 0;
    $status &= fwrite($bf, full_tag('REMINDERSUBJECT', 4, false, $classroom->remindersubject)) > 0;
    $status &= fwrite($bf, full_tag('REMINDERINSTRMNGR', 4, false, $classroom->reminderinstrmngr)) > 0;
    $status &= fwrite($bf, full_tag('REMINDERMESSAGE', 4, false, $classroom->remindermessage)) > 0;
    $status &= fwrite($bf, full_tag('REMINDERPERIOD', 4, false, $classroom->reminderperiod)) > 0;
    $status &= fwrite($bf, full_tag('TIMECREATED', 4, false, $classroom->timecreated)) > 0;
    $status &= fwrite($bf, full_tag('TIMEMODIFIED', 4, false, $classroom->timemodified)) > 0;

    $status &= backup_classroom_sessions($bf, $classroom->id);

    if (backup_userdata_selected($preferences, 'classroom', $classroom->id)) {
        $status &= backup_classroom_submissions($bf, $classroom->id);
    }

    $status &= fwrite($bf, end_tag('MOD', 3 , true)) > 0;
    return $status;
}

/**
 * Backup the classroom_sessions table entries for a given classroom
 * activity
 */
function backup_classroom_sessions($bf, $classroomid) {

    $status = true;

    $sessions = get_records('classroom_sessions', 'classroom', $classroomid, 'id');
    if ($sessions) {

        $status &= fwrite($bf, start_tag('SESSIONS', 4, true)) > 0;

        foreach ($sessions as $session) {

            $status &= fwrite($bf, start_tag('SESSION', 5, true)) > 0;

            // classroom_sessions table
            $status &= fwrite($bf, full_tag('ID', 6, false, $session->id)) > 0;
            $status &= fwrite($bf, full_tag('CAPACITY', 6, false, $session->capacity)) > 0;
            $status &= fwrite($bf, full_tag('LOCATION', 6, false, $session->location)) > 0;
            $status &= fwrite($bf, full_tag('VENUE', 6, false, $session->venue)) > 0;
            $status &= fwrite($bf, full_tag('ROOM', 6, false, $session->room)) > 0;
            $status &= fwrite($bf, full_tag('DETAILS', 6, false, $session->details)) > 0;
            $status &= fwrite($bf, full_tag('DATETIMEKNOWN', 6, false, $session->datetimeknown)) > 0;
            $status &= fwrite($bf, full_tag('DURATION', 6, false, $session->duration)) > 0;
            $status &= fwrite($bf, full_tag('NORMALCOST', 6, false, $session->normalcost)) > 0;
            $status &= fwrite($bf, full_tag('DISCOUNTCOST', 6, false, $session->discountcost)) > 0;
            $status &= fwrite($bf, full_tag('CLOSED', 6, false, $session->closed)) > 0;
            $status &= fwrite($bf, full_tag('TIMECREATED', 6, false, $session->timecreated)) > 0;
            $status &= fwrite($bf, full_tag('TIMEMODIFIED', 6, false, $session->timemodified)) > 0;

            $status &= backup_classroom_sessions_dates($bf, $session->id);

            $status &= fwrite($bf, end_tag('SESSION', 5, true)) > 0;
        }

        $status &= fwrite($bf, end_tag('SESSIONS', 4, true)) > 0;
    }

    return $status;
}

/**
 * Backup the classroom_sessions_dates table entries for a given
 * classroom session
 */
function backup_classroom_sessions_dates($bf, $sessionid) {

    $status = true;

    $dates = get_records('classroom_sessions_dates', 'sessionid', $sessionid, 'id');
    if ($dates) {

        $status &= fwrite($bf, start_tag('DATES', 6, true)) > 0;

        foreach ($dates as $date) {

            $status &= fwrite($bf, start_tag('DATE', 7, true)) > 0;

            // classroom_sessions_dates table
            $status &= fwrite($bf, full_tag('ID', 8, false, $date->id)) > 0;
            $status &= fwrite($bf, full_tag('TIMESTART', 8, false, $date->timestart)) > 0;
            $status &= fwrite($bf, full_tag('TIMEFINISH', 8, false, $date->timefinish)) > 0;

            $status &= fwrite($bf, end_tag('DATE', 7, true)) > 0;
        }

        $status &= fwrite($bf, end_tag('DATES', 6, true)) > 0;
    }

    return $status;
}

/**
 * Backup the classroom_submissions table entries for a given
 * classroom activity
 */
function backup_classroom_submissions($bf, $classroomid) {

    $status = true;

    $submissions = get_records('classroom_submissions', 'classroom', $classroomid, 'id');
    if ($submissions) {

        $status &= fwrite($bf, start_tag('SUBMISSIONS', 4, true)) > 0;

        foreach ($submissions as $submission) {

            $status &= fwrite($bf, start_tag('SUBMISSION', 5, true)) > 0;

            // classroom_submissions table
            $status &= fwrite($bf, full_tag('ID', 6, false, $submission->id)) > 0;
            $status &= fwrite($bf, full_tag('classroom', 6, false, $submission->classroom)) > 0;
            $status &= fwrite($bf, full_tag('SESSIONID', 6, false, $submission->sessionid)) > 0;
            $status &= fwrite($bf, full_tag('USERID', 6, false, $submission->userid)) > 0;
            $status &= fwrite($bf, full_tag('MAILEDCONFIRMATION', 6, false, $submission->mailedconfirmation)) > 0;
            $status &= fwrite($bf, full_tag('MAILEDREMINDER', 6, false, $submission->mailedreminder)) > 0;
            $status &= fwrite($bf, full_tag('DISCOUNTCODE', 6, false, $submission->discountcode)) > 0;
            $status &= fwrite($bf, full_tag('TIMECREATED', 6, false, $submission->timecreated)) > 0;
            $status &= fwrite($bf, full_tag('TIMEMODIFIED', 6, false, $submission->timemodified)) > 0;
            $status &= fwrite($bf, full_tag('TIMECANCELLED', 6, false, $submission->timecancelled)) > 0;
            $status &= fwrite($bf, full_tag('NOTIFICATIONTYPE', 6, false, $submission->notificationtype)) > 0;

            $status &= fwrite($bf, end_tag('SUBMISSION', 5, true)) > 0;
        }

        $status &= fwrite($bf, end_tag('SUBMISSIONS', 4, true)) > 0;
    }

    return $status;
}

/**
 * API function called by the Moodle backup system to describe the
 * contents of the given backup instances
 */
function classroom_check_backup_mods_instances($instance, $backup_unique_code) {

    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';

    $info[$instance->id.'1'][0] = get_string('sessions', 'classroom');
    $info[$instance->id.'1'][1] = count_records('classroom_sessions', 'classroom', $instance->id);

    if (!empty($instance->userdata)) {
        $info[$instance->id.'2'][0] = get_string('submissions', 'classroom');
        $info[$instance->id.'2'][1] = count_records('classroom_submissions', 'classroom', $instance->id);
    }

    return $info;
}

/**
 * API function called by the Moodle backup system to describe the
 * contents of backup instances for the given course
 */
function classroom_check_backup_mods($course, $user_data=false, $backup_unique_code, $instances=null) {

    global $CFG;

    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += classroom_check_backup_mods_instances($instance, $backup_unique_code);
        }
        return $info;
    }

    $info[0][0] = get_string('modulenameplural', 'classroom');
    $info[0][1] = count_records('classroom', 'course', $course);

    $info[1][0] = get_string('sessions', 'classroom');
    $info[1][1] = count_records_sql("SELECT COUNT(*)
                                         FROM {$CFG->prefix}classroom f,
                                              {$CFG->prefix}classroom_sessions s
                                         WHERE f.id = s.classroom
                                           AND f.course = $course");

    if ($user_data) {
        $info[2][0] = get_string('submissions', 'classroom');
        $info[2][1] = count_records_sql("SELECT COUNT(*)
                                             FROM {$CFG->prefix}classroom f,
                                                  {$CFG->prefix}classroom_submissions s
                                             WHERE f.id = s.classroom
                                               AND f.course = $course");
    }

    return $info;
}

?>
