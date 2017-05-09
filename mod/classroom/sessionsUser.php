
<?php

    require_once('../../config.php');
    require_once('lib.php');

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID
    $f = optional_param('f', 0, PARAM_INT); // classroom Module ID
    $s = optional_param('s', 0, PARAM_INT); // classroom session ID

 
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


    if (empty($form->classroom)) {
        $form->classroom = $classroom->id;
    }
	
	
	if (empty($form->location)) {
        $form->location = '';
    }

    if (empty($form->venue)) {
        $form->venue = '';
    }

    if (empty($form->room)) {
        $form->room = '';
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
	
	if (empty($form->account)) {
        $form->account = '';
    }

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

    if ($session = data_submitted()) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        if ($cancelform) {
            redirect($CFG->wwwroot.'/mod/classroom/view.php?f='.$classroom->id);
        }

	   
        if (empty($session->classroom)) {
            // Only the "Copy" form allows users to specify a different classroom ID
            $session->classroom = $classroom->id;

        }

        if ($session->location == '') $errorstr .= get_string('error:emptylocation', 'classroom');
        if ($session->venue == '') $errorstr .= get_string('error:emptyvenue', 'classroom');

		
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
															   
					if (time() < $sessiondates[$j]->timefinish)
					{
						error('Currently we accept only training events that have been completed. Kindly select dates prior to today.');
					}
                $j++;
				
				
            }

	//Naga Added for the duration round off.
		if($session->duration<15)
		{
		$session->duration=15;
		}
		if($session->duration>15 || $session->duration<30){
		$session->duration=30;
		}
 
				$session->datetimeknown = 1;
				$session->trainingtype='Team Training Event';
				$session->room='Others';
				$session->sessioncategory='Team Training';
				$session->trainingsource='Internal';
				$session->requestor=$USER->username;
				$session->duration=$session->duration/60;

				 if (! $trainer = get_record('user', 'username', $session->trainer)) {
            	error('Invalid trainer Portal ID, Unable to add training event');
        		}
                if ($session->id = classroom_add_session($session, $sessiondates)) {
                    add_to_log($course->id, 'classroom', 'add session', 'classroom', 'sessionsUser.php?s='.$session->id, $classroom->id, $cm->id);
					
				if (!classroom_trainner_signup($session, $classroom, $course, '', MDL_F2F_ICAL,
                                            $trainer->id, false, false)) {
                
				add_to_log($course->id, 'classroom', 'add trainer (FAILED)', 'sessionsUser.php?s='.$session->id, $classroom->id, $cm->id);
                    error(get_string('error:couldnotaddtrainer', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
            	}
				else{
					$url = "editattendeesUser.php?s=$session->id";
                    redirect($url);
				}
					
                    
                } else {
                    add_to_log($course->id, 'classroom', 'add session (FAILED)', 'sessionsUser.php?s='.$session->id, $classroom->id, $cm->id);
                    error(get_string('error:couldnotaddsession', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
                }
            
        } 
    }

    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');

    
	$heading = 'Adding A New Training Event';
    
    

    $pagetitle = format_string($classroom->name);
    $navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
    $navlinks[] = array('name' => $pagetitle, 'link' => "view.php?f=$classroom->id", 'type' => 'activityinstance');
    $navlinks[] = array('name' => $heading, 'link' => '', 'type' => 'title');

    print_header_simple($pagetitle, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));


    echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';

    print_heading($heading, 'center');
echo '<br/>';
   $toprow = array();

$user = get_record('user','id', $USER->id);
		echo '<table width="100%" >';
		echo '<tr>';
		echo '<td valign="top" width="100">';
		echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f1.jpg" width="80px" height="80px" title="'.$USER->firstname.' '.$USER->lastname.'" alt="'.$USER->firstname.' '.$USER->lastname.'" /></a>'; 
		echo '</td>';
		echo '<td>';
		echo 'Dear ' .$USER->firstname.' '.$USER->lastname;
		echo '<br/>';
		echo '<p style="width:700px; height:100px">Training and development activities happen every day in venues maintained by practices, engagements and projects.</br>  NTT DATA wants to ensure that we recognize the development efforts of all employees and will include these in the completion report when you enter the records here.  This form is used for managers to enter their team training and development events here. A manager may appoint any team member to enter the records.  A confirmation email will be sent to the manager of the person that enters the team training.<b>
		<br/>Currently we accept only training events that have been completed. Kindly select dates prior to today.</p>';
		?>
		<p align="right">
<a href="<?php echo $CFG->wwwroot; ?>/file.php/7007/Project_How_To/Entering%20Project%20Training%20Demo.htm" onclick="javascript:void window.open('<?php echo $CFG->wwwroot; ?>/file.php/7007/External_Training_HowTo/Entering%20External%20Training%20Demo.htm','1371620881908','width=800,height=600,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">

<img src="icons/tour.png" alt="Take a tour" title="See tutorial video" height="35" width="100">
</a></p>
<?php
		echo '</td>';
		echo '</tr>';
		echo '</table>';
        

		 $toprow[] = new tabobject('addevent', $CFG->wwwroot.'/mod/classroom/sessionsUser.php?f='.$classroom->id, 'Step 1: Add Training Event');
		 $toprow[] = new tabobject('addusers','#' , 'Step 2: Add Attendees');
		 $toprow[] = new tabobject('viewsummary','#', 'Step 3: View Details');


    if (!empty($secondrow)) {
        $tabs = array($toprow, $secondrow);
    } else {
        $tabs = array($toprow);
    }

      /// Print out the tabs and continue!
      print_tabs($tabs, 'addevent', $inactive, $activetwo);
	
    if (!empty($errorstr)) {
        echo '<div class="notifyproblem" align="center"><span style="font-size: 12px; line-height: 18px;">'.$errorstr.'</span></div>';
    }

       require('sessionsUser.html');
    
    echo '</td></tr></table>';
    print_footer($course);

?>
