<?php

    require_once('../../config.php');
    require_once('lib.php');
	require_login();

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID
    $f = optional_param('f', 0, PARAM_INT); // classroom Module ID
    $s = optional_param('s', 0, PARAM_INT); // classroom session ID
    $c = optional_param('c', 0, PARAM_INT); // copy session
    $d = optional_param('d', 0, PARAM_INT); // copy session
    $ca = optional_param('ca', 0, PARAM_INT); // cancel session
	//RoyPhilip: Updated 30/4/2012 seperate edit parameter.
	$ed = optional_param('ed', 0, PARAM_INT); // Editing a session
    $cancelform = optional_param( 'cancel' );
	

    $maxnbdays = 30; // number of session date/time blocks in the form
    $nbdays = 1; // default number to show

    $session = null;
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
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }

        $nbdays = count($session->sessiondates);

    } else {

        if (! $classroom = get_record('classroom', 'id', $f)) {
            error(get_string('error:incorrectclassroomid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $classroom->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $cm = get_coursemodule_from_instance('classroom', $classroom->id, $course->id)) {
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }
    }

    $sessiondate = array();
    $datetimestart = array();
    $datetimefinish = array();
    for ($i = 0; $i < $maxnbdays; $i++) {
        $sessiondate[$i] = NULL;
        $datetimestart[$i] = make_timestamp(2000, 1, 1, 9, 0, 0);
        $datetimefinish[$i] = make_timestamp(2000, 1, 1, 12, 0, 0);
    }

    if ($s) {
        $form = $session;
        if($d) {
            for ($i=0; $i < count($session->sessiondates); $i++) {
                $sessiondate[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimedate'));
                $datetimestart[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimetime'));
                $datetimefinish[$i] = userdate($session->sessiondates[$i]->timefinish, get_string('strftimetime'));
            }
        } else {
            for ($i=0; $i < count($session->sessiondates); $i++) {
                $sessiondate[$i] = $session->sessiondates[$i]->timestart;
                $datetimestart[$i] = $session->sessiondates[$i]->timestart;
                $datetimefinish[$i] = $session->sessiondates[$i]->timefinish;
            }
        }
    }

	//******************* Display cancelling a session ***************************
	if ($s) {

	$cancelreason=$_POST['cancelreason'];

        $form = $session;
		$forwardsession=$session;
        if($ca) {
            for ($i=0; $i < count($session->sessiondates); $i++) {
                $sessiondate[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimedate'));
                $datetimestart[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimetime'));
                $datetimefinish[$i] = userdate($session->sessiondates[$i]->timefinish, get_string('strftimetime'));
            }
        } else {
            for ($i=0; $i < count($session->sessiondates); $i++) {
                $sessiondate[$i] = $session->sessiondates[$i]->timestart;
                $datetimestart[$i] = $session->sessiondates[$i]->timestart;
                $datetimefinish[$i] = $session->sessiondates[$i]->timefinish;
            }
        }
    }
    if (empty($form->classroom)) {
        $form->classroom = $classroom->id;
    }
	
	if (empty($form->programename)) {
        $form->programename = $classroom->name;
    }
	
	if (empty($form->location)) {
        $form->location = '';
    }

    if (empty($form->venue)) {
        $form->venue = '';
    }
	//srinu added for TEF form
	if (empty($form->externaltraining)) {
        $form->externaltraining = '';
    }
	//srinu ended for TEF form
    if (empty($form->room)) {
        $form->room = '';
    }

    if (empty($form->datetimeknown)) {
        if ($session && $session->datetimeknown) {
            $form->datetimeknown = 1;
        } else {
            $form->datetimeknown = 0;
        }
    }

    if (empty($form->capacity)) {
        $form->capacity = "10";
    }

    if (!empty($form->sessyr)) {
        for ($i = 0; $i < count($form->sessyr); $i++) {
            if (!empty($form->sessday[$i]) && !empty($form->sessmon[$i]) && !empty($form->sessyr[$i])) {
                $sessiondate[$i] = make_timestamp($form->sessyr[$i], $form->sessmon[$i], $form->sessday[$i], 0, 0, 0);
            }

            if (!empty($form->starthr[$i]) && !empty($form->startmin[$i])) {
                $datetimestart[$i] = make_timestamp(2000, 1, 1, $form->starthr[$i], $form->startmin[$i], 0);
            }

            if (!empty($form->endhr[$i]) && !empty($form->endmin[$i])) {
                $datetimefinish[$i] = make_timestamp(2000, 1, 1, $form->endhr[$i], $form->endmin[$i], 0);
            }
        }
    }

    if (empty($form->duration)) {
        $form->duration = '';
    }

    if (empty($form->normalcost)) {
        $form->normalcost = '';
    }
	
	if (empty($form->requestor)) {
        $form->requestor = '';
    }
	
	/*if (empty($form->account)) {
        $form->account = '';
    }*/

    if (empty($form->discountcost)) {
        $form->discountcost = '';
    }

    if (empty($form->details)) {
        $form->details = '';
    }

    if (empty($form->closed)) {
        $form->closed = "0";
    }

    require_course_login($course);
    $errorstr = '';
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('mod/classroom:editsessions', $context);

    if ($session = data_submitted()) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        if ($cancelform) {
            redirect($CFG->wwwroot.'/mod/classroom/view.php?f='.$classroom->id);
        }

        if ($session->d) {
            if (classroom_delete_session($session)) {
                add_to_log($course->id, 'classroom', 'delete session', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                $url = "view.php?f=$classroom->id";
                redirect($url);
            } else {
                add_to_log($course->id, 'classroom', 'delete session (FAILED)', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                error(get_string('error:couldnotcancelsession', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
            }
        }

	   
        if (empty($session->classroom)) {
            // Only the "Copy" form allows users to specify a different classroom ID
            $session->classroom = $classroom->id;
			//$session->programename = $classroom->name;
        }
				
        if ($session->location == '' ) $errorstr .= get_string('error:emptylocation', 'classroom');
        if ($session->venue == '' ) $errorstr .= get_string('error:emptyvenue', 'classroom');
		//naga added for duration field is mandatory
	if(($session->d) || ($session->ca)){
				}else {if ($session->duration == '' ) $errorstr .= get_string('error:emptyduration', 'classroom');}
		//naga completed

		if ($session->sessioncategory == '' ) $errorstr .= get_string('error:emptysessioncategory', 'classroom');
		//if ($session->requestor == '') $errorstr .= get_string('error:emptyrequestor', 'classroom');
       // if ($session->account == '') $errorstr .= get_string('error:emptyaccount', 'classroom');

        if (empty($errorstr)) {

            $sessiondates = array();
            $j = 0;
            $nbdays = count($session->sessyr) - 1; // skip the last one (template)
            for ($i = 0; $i < $nbdays; $i++) {
                $sessiondates[$j]->timestart = make_timestamp($session->sessyr[$i], $session->sessmon[$i],
                                                              $session->sessday[$i], $session->starthr[$i],
                                                              $session->startmin[$i]);
                $sessiondates[$j]->timefinish = make_timestamp($session->sessyr[$i], $session->sessmon[$i],
                                                               $session->sessday[$i], $session->endhr[$i],
                                                               $session->endmin[$i]);
                $j++;
            }
// Naga added for copying the attendees while copying the session
			if(isset($_POST['copyingattendees'])) {             // copyingattendees is radio button name in sessions.html page
				if($_POST['copyingattendees'] == 1){			// if radio button value is 'yes' i.e 1 then only the attendees will be copying.
			$records = get_records_sql("SELECT * FROM mdl_classroom_submissions s
                                  JOIN  mdl_classroom f ON s.classroom = f.id
                                  JOIN mdl_user u ON u.id = s.userid
                                 WHERE s.sessionid= '$s' AND s.timecancelled = 0 ");
								 }
								 }
				$timenow = time();	
            if ($s  && !($session->c) && !($session->d) && !($session->ca)){
                $session->id = $s;
                if (empty($session->duration)) $session->duration = '0';
                if (empty($session->normalcost)) $session->normalcost = '0';
                if (empty($session->discountcost)) $session->discountcost = '0';
				if (empty($session->externaltraining)) $session->externaltraining = '0';
				//Roy Philip: Updated on 30/4/2012 for reminder notification
				if($session->programename == null){
				$session->programename = $form->programename;}
                if ($edit = classroom_update_session($classroom,$session, $sessiondates,$session->sendnotification)) {			
                    add_to_log($course->id, 'classroom', 'update session', "sessions.php?s=$session->id", $classroom->id, $cm->id);
                    redirect("view.php?f=$session->classroom", '', '5');
                } else {
                    add_to_log($course->id, 'classroom', 'update session (FAILED)', "sessions.php?s=$session->id", $classroom->id, $cm->id);
                    $errordestination = $CFG->wwwroot . "/mod/classroom/view.php?f=$session->classroom";
                    error(get_string('error:couldnotupdatesession', 'classroom'), $errordestination);
                }

            } elseif ($session->c ) { // Adding a new copied session

                if ($session->id = classroom_add_session($session, $sessiondates)) {
					if($records){
				foreach($records as $re)				
				{
				$re->sessionid = $session->id;
				$re->attend = 0;
				$re->mailedconfirmation='null';
				$re->mailedreminder='null';
				$re->mailedfeedback='null';
				$re->mailedabsentees='null';
				$re->timecreated=$timenow;
				$re->timemodified=0;
				$re->timecancelled=0;
					 //$attendee->id=execute_sql("insert into  mdl_classroom_submissions (classroom,sessionid,userid) values ($re->classroom,$session->id,$re->userid)");
				$attendees->id = insert_record('classroom_submissions', $re);
				}
				}
                    add_to_log($course->id, 'classroom', 'copy session', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                    $url = "view.php?f=$session->classroom";
                    redirect($url);
					
                } else {
                    add_to_log($course->id, 'classroom', 'copy session (FAILED)', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                    $errordestination = $CFG->wwwroot . "/mod/classroom/view.php?f=$session->classroom";
                    error(get_string('error:couldnotcopysession', 'classroom'), $errordestination);
                }

            } elseif ($session->ca) {

			if (classroom_session_cancel($s,$classroom,$cancelreason)) {
                add_to_log($course->id, 'classroom', 'cancel session', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                $url = "view.php?f=$classroom->id";
                redirect($url);			    
			  
            } else {
                add_to_log($course->id, 'classroom', 'cancel session (FAILED)', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                error(get_string('error:couldnotcancelsession', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
            }
            	
        } else { // Adding a new session
		
                if ($session->id = classroom_add_session($session, $sessiondates)) {
				    add_to_log($course->id, 'classroom', 'add session', 'classroom', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                    $url = "view.php?f=$session->classroom";
                    redirect($url);
					
                } else {
                    add_to_log($course->id, 'classroom', 'add session (FAILED)', 'sessions.php?s='.$session->id, $classroom->id, $cm->id);
                    error(get_string('error:couldnotaddsession', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
                }
				
            }
		} 
	}

    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');

    if ($c) {
        $heading = get_string('copyingsession', 'classroom', $classroom->name);
		
    } else if ($d) {
        $heading = get_string('deletingsession', 'classroom', $classroom->name);
	}else if ($ca) {
        $heading = get_string('cancellingsession', 'classroom', $classroom->name);
	//RoyPhilip: Updated 30/4/2012 heading label according to add/edit.
    } else if ($ed) {
	    $heading = get_string('editingsession', 'classroom', $classroom->name);
        
    } else {
		$heading = get_string('addingsession', 'classroom', $classroom->name);
    
    }

    $pagetitle = format_string($classroom->name);
    $navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
    $navlinks[] = array('name' => $pagetitle, 'link' => "view.php?f=$classroom->id", 'type' => 'activityinstance');
    $navlinks[] = array('name' => $heading, 'link' => '', 'type' => 'title');
    $navigation = build_navigation($navlinks);
    print_header_simple($pagetitle, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));

    echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';

    print_heading($heading, 'center');
	echo $_POST["x"];
	echo $session->sub;
    if (!empty($errorstr)) {
        echo '<div class="notifyproblem" align="center"><span style="font-size: 12px; line-height: 18px;">'.$errorstr.'</span></div>';
    }

    if ($d) {
        echo '<span style="font-size: 12px; line-height: 18px;">'.get_string('deletesessionconfirm', 'classroom').'<BR /><BR /></span>';
        require('sessions_delete.html');
    } else if ($ca) {
		echo '<span style="font-size: 12px; line-height: 18px;">'.get_string('cancelsessionconfirm', 'classroom').'<BR /><BR /></span>';
		


        require('sessions_cancel.html');
	}
	//Roy Philip: Updated 30/4/2012 - Seperate edit session page.
	else if ($ed) {
		require('editsessions.html');
	}
	else {
       require('sessions.html');
    }
    echo '</td></tr></table>';
    print_footer($course);

?>
