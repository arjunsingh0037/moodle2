<?php

/**
 * API function called by the Moodle restore system
 */
function classroom_restore_mods($mod, $restore) {

    global $CFG;

    $status = true;

    $data = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);
    if ($data) {

        $info = $data->info;

        $classroom->course                = $restore->course_id;
        $classroom->name                  = backup_todb($info['MOD']['#']['NAME']['0']['#']);
        $classroom->thirdparty            = backup_todb($info['MOD']['#']['THIRDPARTY']['0']['#']);
        $classroom->display               = backup_todb($info['MOD']['#']['DISPLAY']['0']['#']);
        $classroom->confirmationsubject   = backup_todb($info['MOD']['#']['CONFIRMATIONSUBJECT']['0']['#']);
        $classroom->confirmationinstrmngr = backup_todb($info['MOD']['#']['CONFIRMATIONINSTRMNGR']['0']['#']);
        $classroom->confirmationmessage   = backup_todb($info['MOD']['#']['CONFIRMATIONMESSAGE']['0']['#']);
        $classroom->waitlistedsubject     = backup_todb($info['MOD']['#']['WAITLISTEDSUBJECT']['0']['#']);
        $classroom->waitlistedmessage     = backup_todb($info['MOD']['#']['WAITLISTEDMESSAGE']['0']['#']);
        $classroom->cancellationsubject   = backup_todb($info['MOD']['#']['CANCELLATIONSUBJECT']['0']['#']);
        $classroom->cancellationinstrmngr = backup_todb($info['MOD']['#']['CANCELLATIONINSTRMNGR']['0']['#']);
        $classroom->cancellationmessage   = backup_todb($info['MOD']['#']['CANCELLATIONMESSAGE']['0']['#']);
        $classroom->remindersubject       = backup_todb($info['MOD']['#']['REMINDERSUBJECT']['0']['#']);
        $classroom->reminderinstrmngr     = backup_todb($info['MOD']['#']['REMINDERINSTRMNGR']['0']['#']);
        $classroom->remindermessage       = backup_todb($info['MOD']['#']['REMINDERMESSAGE']['0']['#']);
        $classroom->reminderperiod        = backup_todb($info['MOD']['#']['REMINDERPERIOD']['0']['#']);
        $classroom->timecreated           = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
        $classroom->timemodified          = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

        $newid = insert_record ('classroom', $classroom);

        if (!defined('RESTORE_SILENTLY')) {
            echo '<li>'.get_string('modulename','classroom').' "'.format_string(stripslashes($classroom->name),true).'"</li>';
        }
        backup_flush(300);

        if ($newid) {
            backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);

            // Table: classroom_sessions
            $status &= restore_classroom_sessions($newid, $info, $restore);

            if (restore_userdata_selected($restore, 'classroom', $mod->id)) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo '<br />';
                }

                // Table: classroom_submissions
                $status &= restore_classroom_submissions($newid, $info, $restore);
            }
        } else {
            $status = false;
        }
    } else {
        $status = false;
    }

    return $status;
}

/**
 * Restore the classroom_submissions table entries for a given
 * classroom activity
 *
 * @param integer $newclassroomid ID of the classroom activity we're creating
 */
function restore_classroom_submissions($newclassroomid, $info, $restore) {

    $status = true;

    if (empty($info['MOD']['#']['SUBMISSIONS'])) {
        return $status; // Nothing to restore
    }

    $submissions = $info['MOD']['#']['SUBMISSIONS']['0']['#']['SUBMISSION'];
    foreach ($submissions as $submissioninfo) {
        $oldid = backup_todb($submissioninfo['#']['ID']['0']['#']);

        $submission->classroom         = $newclassroomid;
        $submission->sessionid          = backup_todb($submissioninfo['#']['SESSIONID']['0']['#']);
        $submission->userid             = backup_todb($submissioninfo['#']['USERID']['0']['#']);
        $submission->mailedconfirmation = backup_todb($submissioninfo['#']['MAILEDCONFIRMATION']['0']['#']);
        $submission->mailedreminder     = backup_todb($submissioninfo['#']['MAILEDREMINDER']['0']['#']);
        $submission->discountcode       = backup_todb($submissioninfo['#']['DISCOUNTCODE']['0']['#']);
        $submission->timecreated        = backup_todb($submissioninfo['#']['TIMECREATED']['0']['#']);
        $submission->timemodified       = backup_todb($submissioninfo['#']['TIMEMODIFIED']['0']['#']);
        $submission->timecancelled      = backup_todb($submissioninfo['#']['TIMECANCELLED']['0']['#']);
        $submission->notificationtype   = backup_todb($submissioninfo['#']['NOTIFICATIONTYPE']['0']['#']);

        // Fix the sessionid
        $session = backup_getid($restore->backup_unique_code, 'classroom_sessions', $submission->sessionid);
        if ($session) {
            $submission->sessionid = $session->new_id;
        }

        // Fix the userid
        $user = backup_getid($restore->backup_unique_code, 'user', $submission->userid);
        if ($user) {
            $submission->userid = $user->new_id;
        }

        // Fix the discount code
        if (empty($submission->discountcode)) {
            $submission->discountcode = null;
        }

        $newid = insert_record('classroom_submissions', $submission);

        // Progress bar
        if (!defined('RESTORE_SILENTLY')) {
            if ($newid) {
                echo '.';
            } else {
                echo 'X';
            }
        }
        backup_flush(300);

        if ($newid) {
            backup_putid($restore->backup_unique_code, 'classroom_submissions', $oldid, $newid);
        } else {
            $status = false;
        }
    }

    return $status;
}

/**
 * Restore the classroom_sessions table entries for a given
 * classroom activity
 *
 * @param integer $newclassroomid ID of the classroom activity we're creating
 */
function restore_classroom_sessions($newclassroomid, $info, $restore) {

    $status = true;

    if (empty($info['MOD']['#']['SESSIONS'])) {
        return $status; // Nothing to restore
    }

    $sessions = $info['MOD']['#']['SESSIONS']['0']['#']['SESSION'];
    foreach ($sessions as $sessioninfo) {
        $oldid = backup_todb($sessioninfo['#']['ID']['0']['#']);

        $session->classroom    = $newclassroomid;
        $session->capacity      = backup_todb($sessioninfo['#']['CAPACITY']['0']['#']);
        $session->location      = backup_todb($sessioninfo['#']['LOCATION']['0']['#']);
        $session->venue         = backup_todb($sessioninfo['#']['VENUE']['0']['#']);
        $session->room          = backup_todb($sessioninfo['#']['ROOM']['0']['#']);
        $session->details       = backup_todb($sessioninfo['#']['DETAILS']['0']['#']);
        $session->datetimeknown = backup_todb($sessioninfo['#']['DATETIMEKNOWN']['0']['#']);
        $session->duration      = backup_todb($sessioninfo['#']['DURATION']['0']['#']);
        $session->normalcost    = backup_todb($sessioninfo['#']['NORMALCOST']['0']['#']);
        $session->discountcost  = backup_todb($sessioninfo['#']['DISCOUNTCOST']['0']['#']);
        $session->closed        = backup_todb($sessioninfo['#']['CLOSED']['0']['#']);
        $session->timecreated   = backup_todb($sessioninfo['#']['TIMECREATED']['0']['#']);
        $session->timemodified  = backup_todb($sessioninfo['#']['TIMEMODIFIED']['0']['#']);

        $newid = insert_record('classroom_sessions', $session);

        // Progress bar
        if (!defined('RESTORE_SILENTLY')) {
            if ($newid) {
                echo '.';
            } else {
                echo 'X';
            }
        }
        backup_flush(300);

        if ($newid) {
            backup_putid($restore->backup_unique_code, 'classroom_sessions', $oldid, $newid);

            // Table: classroom_sessions_dates
            $status &= restore_classroom_sessions_dates($newid, $sessioninfo, $restore);
        } else {
            $status = false;
        }
    }

    return $status;
}

/**
 * Restore the classroom_sessions_dates table entries for a given
 * classroom session
 *
 * @param integer $newsessionid ID of the session we are creating
 */
function restore_classroom_sessions_dates($newsessionid, $sessioninfo, $restore) {

    $status = true;

    if (empty($sessioninfo['#']['DATES'])) {
        return $status; // Nothing to restore
    }

    $dates = $sessioninfo['#']['DATES']['0']['#']['DATE'];
    foreach ($dates as $dateinfo) {
        $oldid = backup_todb($dateinfo['#']['ID']['0']['#']);

        $date->sessionid  = $newsessionid;
        $date->timestart  = backup_todb($dateinfo['#']['TIMESTART']['0']['#']);
        $date->timefinish = backup_todb($dateinfo['#']['TIMEFINISH']['0']['#']);

        $newid = insert_record('classroom_sessions_dates', $date);

        // Progress bar
        if (!defined('RESTORE_SILENTLY')) {
            if ($newid) {
                echo '.';
            } else {
                echo 'X';
            }
        }
        backup_flush(300);

        if ($newid) {
            backup_putid($restore->backup_unique_code, 'classroom_sessions_dates', $oldid, $newid);
        } else {
            $status = false;
        }
    }

    return $status;
}

?>
