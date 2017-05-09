<?php

    require_once('../../config.php');
    require_once('lib.php');
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID
    $f = optional_param('f', 0, PARAM_INT); // classroom Module ID
    $s = optional_param('s', 0, PARAM_INT); // classroom session ID
    $confirmcancel = optional_param('confirm',0,PARAM_INT);
    $cancelform = optional_param( 'cancelform' );
    $cancelbooking = optional_param('cancelbooking', 0, PARAM_INT);
    $confirmmanager = optional_param('confirmmanager');
    $confirm = optional_param('confirm');
    $changemanager = optional_param('changemanager');
    $backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);
    $addmanager = optional_param('addmanager', 0, PARAM_INT);
    $discountcode = optional_param('discountcode', '', PARAM_SAFEDIR);
    $notificationtype = optional_param('notificationtype', MDL_F2F_INVITE, PARAM_INT);
	$cancelreasons = $_POST['cancelreasons'];

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
		if (! $sessiont = classroom_get_session_trainers($s)) {
            error(get_string('error:incorrectcoursemodulesession', 'classroom'));
        }
        if (! $classroom = get_record('classroom', 'id', $session->classroom)) {
            error(get_string('error:incorrectclassroomid', 'classroom'));
        }
		
        if (! $course = get_record('course', 'id', $classroom->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $cm = get_coursemodule_from_instance("classroom", $classroom->id, $course->id)) {
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }

    } else {

        if (! $classroom = get_record('classroom', 'id', $f)) {
            error(get_string('error:incorrectclassroomid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $classroom->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $cm = get_coursemodule_from_instance("classroom", $classroom->id, $course->id)) {
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }
    }

    require_course_login($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('mod/classroom:view', $context);

    $confirm = false;
    $errorstr = '';

    if ($form = data_submitted()) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        require_capability('mod/classroom:signup', $context);

        if (!empty($form->confirm)) $confirm=true;

        if ($cancelform) {

            if ($backtoallsessions) {
                redirect($CFG->wwwroot.'/mod/classroom/view.php?f='.$backtoallsessions);
            } else {
                redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
            }

        } else if ($cancelbooking) {
		
			$cancelreasons = $_POST['cancelreasons'];
			
			if (!empty($cancelreasons)) {

				if (classroom_user_cancel($session,$form->cancelreasons)) {

					add_to_log($course->id, 'classroom', 'cancel booking', "signup.php?id=$cm->id", $classroom->id, $cm->id);

					$url = '';
					if ($backtoallsessions) {
						$url = $CFG->wwwroot.'/mod/classroom/view.php?f='.$backtoallsessions;
					} else {
						$url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
					}

					$message = get_string('bookingcancelled', 'classroom');
					$timemessage = 4;

					if ($session->datetimeknown) {
						$error = classroom_send_cancellation_notice($classroom, $session, $USER->id, $notificationtype);
						if (empty($error)) {

							if ($session->datetimeknown && $classroom->cancellationinstrmngr) {
								$message .= '<BR /><BR />'.get_string('cancellationsentmgr', 'classroom');
							} else {
								$message .= '<BR /><BR />'.get_string('cancellationsent', 'classroom');
							}
						}
						else {
							error($error);
						}
					}
	
					redirect($url, $message, $timemessage);

				} else {
					add_to_log($course->id, 'classroom', 'cancel booking (FAILED)', "signup.php?id=$cm->id", $classroom->id, $cm->id);

					$errorstr = get_string('error:cancelbooking', 'classroom');
				}
				
			}else{			
				echo '<p>'.$errorstr.'</p>';
				$errorstr = 'You need to enter a valid cancellation reason.';			
				$err++;
			}
		

        } elseif (!empty($addmanager)) {

            if (!empty($form->manageremail)) {

                if (classroom_check_manageremail($form->manageremail)) {

                    if (classroom_set_manageremail($form->manageremail)) {
                        add_to_log($course->id, 'classroom', 'update manageremail', "signup.php?id=$cm->id", $classroom->id, $cm->id);
                        $confirm = true;
                    } else {
                        add_to_log($course->id, 'classroom', 'update manageremail (FAILED)', "signup.php?id=$cm->id", $classroom->id, $cm->id);
                        $errorstr = get_string('error:couldnotupdatemanageremail', 'classroom');
                    }

                } else {
                    $errorstr = classroom_get_manageremailformat();
                }

            } else {
                $errorstr = get_string('error:emptymanageremail', 'classroom');
            }

        } elseif (!empty($changemanager)) {

            if (!empty($form->manageremail)) {
                if(classroom_set_manageremail($form->manageremail)) {
                    add_to_log($course->id, 'classroom', 'update manageremail', "signup.php?id=$cm->id", $classroom->id, $cm->id);
                    $confirm = true;
                } else {
                    add_to_log($course->id, 'classroom', 'update manageremail (FAILED)', "signup.php?id=$cm->id", $classroom->id, $cm->id);
                    $errorstr = get_string('error:couldnotupdatemanageremail', 'classroom');
                }
            } elseif (!empty($confirmmanager)) {
                $confirmmanager = 0;
            } else {
                $errorstr = get_string('error:emptymanageremail', 'classroom');
            }

        } elseif (!$session->datetimeknown || !get_config(NULL, 'classroom_addchangemanageremail')) {
            $confirm=true;
        }
		$signupcount = classroom_get_num_attendees($session->id);
        if ($confirm) {

            if (classroom_session_get_user_submissions($session->id, $USER->id)) {
                error(get_string('alreadysignedup', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
            }
			elseif($signupcount >= $session ->capacity)	{
				error(get_string('bookingfull', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
				
            }elseif ($submissionid = classroom_user_signup($session, $classroom, $course, $discountcode, $notificationtype)) {

                add_to_log($course->id, 'classroom','signup',"signup.php?d=$submissionid", "$submissionid", $cm->id);

                $url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
                $message = '';

                if ($addmanager) $message .= get_string('manageradded', 'classroom').' ';
                if ($changemanager) $message .= get_string('managerchanged', 'classroom').' ';

                $message .= get_string('bookingcompleted', 'classroom');

                if ($session->datetimeknown && $classroom->confirmationinstrmngr) {
                    $message .= '<BR /><BR />'.get_string('confirmationsentmgr', 'classroom');
                } else {
                    $message .= '<BR /><BR />'.get_string('confirmationsent', 'classroom');
                }
                $timemessage = 4;

                redirect($url, $message, $timemessage);
            } else {
                add_to_log($course->id, 'classroom','signup (FAILED)',"signup.php?d=$submissionid", "$submissionid", $cm->id);
                $errorstr = get_string('error:problemsigningup', 'classroom');
            }

        } else {
            $manageremail = classroom_get_manageremail($USER->id);

            if (!empty($changemanager)) {
            } elseif ($manageremail) {
                $confirmmanager = 1;
            } else {
                $addmanager = 1;
            }
        }
    }

    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');

    $sessiondate = array();
    $datetimestart = array();
    $datetimefinish = array();
    for ($i = 0; $i < count($session->sessiondates); $i++) {
        $sessiondate[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimedate'));
        $datetimestart[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimetime'));
        $datetimefinish[$i] = userdate($session->sessiondates[$i]->timefinish, get_string('strftimetime'));
    }
    $signedup = false;

    $pagetitle = format_string($session->programename);
    $navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
    $navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);
    print_header_simple($pagetitle, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strclassroom));

    echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';

    if ($cancelbooking) {
        $heading = get_string('cancelbookingfor', 'classroom', $session->programename);
    } else {
        $heading = get_string('signupfor', 'classroom', $session->programename);
        $descriptionc = get_record_sql("SELECT description  FROM  mdl_classroom WHERE id =$session->classroom");
		echo "<tr ><td align=left><font size=3 color=black style=font-family:Century  ><left>$descriptionc->description</left></font></td></tr>";
		echo "<tr><td><font color=red><b><center>Make sure your respective timezone is updated before you signup for the session. </br> Click on Update Profile to update your timezone.</center></b></td></font>";
    }

    print_heading($heading, 'center');

    if (classroom_session_check_signup($session->id)) {
    // User is currently signed-up for this session

        echo '<center><span style="font-size: 12px; line-height: 18px;">';
        echo get_string('bookingstatus', 'classroom');
        $signedup = true;

        if (!empty($cancelbooking)) {
            // User has asked to cancel their booking to this instance
            echo '. ';
            echo get_string('cancellationconfirm', 'classroom');

        } else {
            echo ': ';
        }
        echo '<br /><br /></span></center>';
 
    }

    if (!empty($errorstr)) {
        echo '<div class="notifyproblem" align="center"><span style="font-size: 12px; line-height: 18px;">'.$errorstr.'</span></div>';
    }
	
	if(empty($classroom->confirmationinstrmngr) && empty($classroom->reminderinstrmngr) && 
		empty($classroom->cancellationinstrmngr)){
	$sendemailtomanager = 0;
	}
	else
	{
	$sendemailtomanager = 1;
	}

    $querystr = '';

    if ($cancelbooking) $querystr .= '&amp;cancelbooking=1';
    if ($addmanager) $querystr .= '&amp;addmanager=1';
    if ($changemanager) $querystr .= '&amp;changemanager=1';
    if ($confirmmanager) $querystr .= '&amp;confirmmanager=1';
    if ($backtoallsessions) $querystr .= '&amp;backtoallsessions='.$backtoallsessions;

    require('signup.html');
    echo '</td></tr></table>';
    print_footer($course);

?>
