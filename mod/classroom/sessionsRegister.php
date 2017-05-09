

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

		if ($session->programename == '') $errorstr .= '<li>Please provide the training you attended.</li>';
        if ($session->location == '') $errorstr .= '<li>Please select the country where you attended the training.</li>';
        if ($session->venue == '') $errorstr .= '<li>Please specify the venue of your training.</li>';
		if ($session->durationh == '') $errorstr .= '<li>Please specify the duration of the training.</li>';
    $sessiondates = array();
        if (empty($errorstr)) {

	//Naga Added for the duration round off.
		// if($session->durationm<15)
		// {
		// $session->durationm=15;
		// }
		// if($session->durationm>15 || $session->durationm<30){
		// $session->durationm=30;
		// }
		
		//Naga Added for the duration round where hours=0 and minutes<30 off.
		if($session->durationm<15 &&($session->durationh<=0 || empty($session->durationh)))
		{
		$session->durationm=15;
		}
		if(($session->durationm>15 || $session->durationm<30)&&($session->durationh<=0 || empty($session->durationh))){
		$session->durationm=30;
		}
		
		$sessiondates[0]->timestart = strtotime($_POST['date3']);
			$sessiondates[0]->timefinish = strtotime($_POST['date4']);
 
/*Roy: Fix for duration */
				$session->duration=$session->durationh + $session->durationm/60;
				$session->datetimeknown = 1;
				$session->trainingtype='External Training Event';
				$session->location = $_POST['location'];
				$session->sessioncategory = $_POST['trainingType'];
				$session->datetimeknown = 1;
				$session->requestor=$USER->username;
				$session->capacity=1;
				$session->room='Others';
				$session->trainingsource='External';
				if($_SESSION['uploadid']>0)
				{				
                if ($session->id = classroom_add_session($session, $sessiondates)) {
                    add_to_log($course->id, 'classroom', 'add session', 'classroom', 'sessionsRegister.php?s='.$session->id, $classroom->id, $cm->id);
					
					if (classroom_user_signup($session, $classroom, $course, '',MDL_F2F_ICAL,
                                            $USER->id, false, false)) 
					{
						
						$usersignup->id =  $_SESSION['uploadid'];

						$usersignup->sessionid=$session->id;

						if ($returnid = update_record('classroom_sessions_external', $usersignup)) {

						$_user = get_records_sql("SELECT username from mdl_user WHERE BU = '5100' and id = $USER->id");
						if ($_user) 
						{
						$mail=classroom_externalevent_notice($classroom, $session, 17409);
						}
						else
						{
						$mail=classroom_externalevent_notice($classroom, $session, 1);	
						}
							$mailUser=classroom_externalevent_user_notice($classroom, $session, $USER->id);							
							$_SESSION['uploadid']=0;
							$url = "sessionsRegister.php?f=$classroom->id&s=$session->id";
							$errorstr .= redirect($url);
							}
					
						else
						{
							$errorstr .= '<li>Please upload your completion of proof.</li>';
						}
           
					}
					else
					{
					add_to_log($course->id, 'classroom', 'add session (FAILED)', 'sessionsRegister.php?s='.$session->id, $classroom->id, $cm->id);
                    error(get_string('error:couldnotaddsession', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);					
					}
 
                } else {
                    add_to_log($course->id, 'classroom', 'add session (FAILED)', 'sessionsRegister.php?s='.$session->id, $classroom->id, $cm->id);
                    error(get_string('error:couldnotaddsession', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
						}
					}	
				else
				{
					$errorstr .= '<li>Please upload your completion of proof.</li>';
                }
            }
        } 
    

    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');

    
	$heading = 'Adding A New Training Event';
    
	/*Fix Make it backward compactable to IE 9 */
  echo '<meta http-equiv="X-UA-Compatible" content="IE=9">';
    

    $pagetitle = format_string($classroom->name);

    print_header_simple($pagetitle, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));


    echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';


echo '<br/>';
   $toprow = array();

$user = get_record('user','id', $USER->id);
		echo '<table width="100%" >';
		echo '<tr>';
		echo '<td valign="top" width="100">';
		
		echo '</td>';
		echo '<td>';
		echo '<br/>';
		echo '<p style="width:700px"></p></tr>';
		echo '</table>';
        


	
    

      
	   
    if (!empty($errorstr)) {
	?>
	
	<table  border=0 cellspacing=0 cellpadding=0
 style='border-collapse:collapse;border:none;mso-yfti-tbllook:1184;mso-padding-alt:
 .1in .1in .1in .1in;mso-border-insideh:none;mso-border-insidev:none'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td  align="center" style='width:220.4pt;padding:.1in .1in .1in .1in'>
<img src="icons/error.png" alt="Input error" title="Input error" height="60" width="60">
  </td>
  <td  valign=top style='width:239.4pt;padding:.1in .1in .1in .1in'>
  
  <p class=MsoNormal align="left" >
<?php echo '<div  align="left"><span style="font-size: 11px; color:red; ">'.$errorstr.'</span></div>'; ?>
</p>
  </td>

 </tr>
 


 </table>
	
	<?php       
    }
	 require('sessionsRegister.html');
    echo '</td>
	
	</tr></table>';
    print_footer($course);

?>
