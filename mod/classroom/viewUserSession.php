<?php

require_once('../../config.php');
require_once('lib.php');


$s = required_param('s', PARAM_INT); // classroom session ID


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

$trecords = get_records_sql("SELECT u.id, s.id AS submissionid,u.username, u.firstname, u.lastname, u.email,s.discountcode, f.id AS classroomid, f.course, 0 AS grade
                                  FROM {$CFG->prefix}classroom f
                                  JOIN {$CFG->prefix}classroom_trainners s ON s.classroom = f.id
                                  JOIN {$CFG->prefix}user u ON u.id = s.userid
                                 WHERE s.sessionid=$session->id
                                   AND s.timecancelled = 0
                              ORDER BY u.firstname");

if (!$manager = get_record('user', 'username', $USER->manager_portalid)) {
    error('Unable to send conformation to your manager');
}
			$_user = get_records_sql("SELECT username from mdl_user WHERE BU = '5100' and id = $USER->id");
			if ($_user) 
			{
			$mail=classroom_registerilt_notice($classroom, $session, 17409);
			}
			else
			{
			$mail=classroom_registerilt_notice($classroom, $session, 1);	
			}
	
$mailUser=classroom_iltevent_user_notice($classroom, $session, $USER->id);	
$mailManager=classroom_registerilt_managernotice($classroom, $session, $manager->id);	
							
$url = "sessionsUser.php?f=$classroom->id";
$urlexit = $CFG->wwwroot;

/// Main page
$pagetitle = format_string($session->programename);
$navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
$navlinks[] = array('name' => $pagetitle, 'link' => "view.php?f=$classroom->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('attendees', 'classroom'), 'link' => "attendees.php?s=$session->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('addremoveattendees', 'classroom'), 'link' => '', 'type' => 'title');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));

print_heading('Thankyou for adding '.$session->programename);



echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';
echo '<table width="100%" >';
		echo '<tr>';
		echo '<td valign="top" width="100">';
		echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f1.jpg" width="80px" height="80px" title="'.$USER->firstname.' '.$USER->lastname.'" alt="'.$USER->firstname.' '.$USER->lastname.'" /></a>'; 
		echo '</td>';
		echo '<td>';

		echo '<p style="width:700px; height:100px">Thank you for adding the training event. The sessions details would be validated by the training team for consistency. After verification the training hours will be created to the attendees.</p>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
        

		 $toprow[] = new tabobject('addevent',$CFG->wwwroot.'/mod/classroom/sessionsUser.php?f='.$classroom->id, 'Step 1: Add Training Event');
		 $toprow[] = new tabobject('addusers','#' , 'Step 2: Add Attendees');
		 $toprow[] = new tabobject('viewsummary','#', 'Step 3: View Details');




    if (!empty($secondrow)) {
        $tabs = array($toprow, $secondrow);
    } else {
        $tabs = array($toprow);
    }

      /// Print out the tabs and continue!
      print_tabs($tabs, 'viewsummary', $inactive, $activetwo);

echo '</table>';
 $sessiondate = array();
    $datetimestart = array();
    $datetimefinish = array();
    for ($i = 0; $i < count($session->sessiondates); $i++) {
        $sessiondate[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimedate'));
        $datetimestart[$i] = userdate($session->sessiondates[$i]->timestart, get_string('strftimetime'));
        $datetimefinish[$i] = userdate($session->sessiondates[$i]->timefinish, get_string('strftimetime'));
		}
?>
<center>
<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
 style='border-collapse:collapse;mso-yfti-tbllook:1184;mso-padding-alt:0in 0in 0in 0in'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td valign=top style='padding:0in 0in 0in 0in'>
  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%"
   style='width:100.0%;border-collapse:collapse;mso-yfti-tbllook:1184;
   mso-padding-alt:0in 0in 0in 0in'>
   <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
    <td width="100%" valign=top style='width:100.0%;padding:0in 0in 0in 0in'>
    <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width=791
     style='width:593.2pt;border-collapse:collapse;mso-yfti-tbllook:1184;
     mso-padding-alt:0in 0in 0in 0in'>
     <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes;
      height:22.5pt'>
      <td width="99%" valign=top style='width:99.0%;background:#F3F3F3;
      padding:0in 7.5pt 0in 7.5pt;height:22.5pt'>
      <p class=MsoNormal style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
      auto;line-height:22.5pt'><span style='font-size:13.5pt;font-family:"Arial","sans-serif";
      mso-fareast-font-family:"Times New Roman";color:#333333'><?php echo $session->programename; ?></span><span style='font-size:12.0pt;font-family:"Times New Roman","serif";
      mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></p>
      </td>
     </tr>
    </table>
    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;
    line-height:normal'><span style='font-size:10.0pt;font-family:"Arial","sans-serif";
    mso-fareast-font-family:"Times New Roman";display:none;mso-hide:all'><o:p>&nbsp;</o:p></span></p>
    <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width=865
     style='width:648.75pt;border-collapse:collapse;mso-yfti-tbllook:1184;
     mso-padding-alt:0in 0in 0in 0in'>
     <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
      <td valign=top style='padding:0in 7.5pt 0in 7.5pt'>
      <p class=MsoNormal style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
      auto;line-height:10.5pt'><span style='font-size:8.5pt;font-family:"Verdana","sans-serif";
      mso-fareast-font-family:"Times New Roman";mso-bidi-font-family:"Times New Roman";
      color:#666666'><?php for ($i=0; $i < count($session->sessiondates); $i++) { ?>
        <tr>
        <td align="left"><?php echo $sessiondate[$i];?> : <?php echo $datetimestart[$i]; ?> - <?php echo $datetimefinish[$i]; ?></td>
        </tr>
		<?php } ?></span><span style='font-size:
      12.0pt;font-family:"Times New Roman","serif";mso-fareast-font-family:
      "Times New Roman"'><o:p></o:p></span></p>
      </td>
     </tr>
     <tr style='mso-yfti-irow:1'>
      <td valign=top style='padding:0in 7.5pt 0in 7.5pt'>
      <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
       style='border-collapse:collapse;mso-yfti-tbllook:1184;mso-padding-alt:
       0in 0in 0in 0in'>
       <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
        <td valign=top style='padding:0in 7.5pt 0in 7.5pt'>
        <p class=MsoNormal style='mso-margin-top-alt:auto;margin-bottom:11.25pt;
        line-height:12.0pt'><span
        style='font-size:10.0pt;font-family:"Arial","sans-serif";mso-fareast-font-family:
        "Times New Roman"'><o:p></o:p></span></p>
        <p class=MsoNormal style='mso-margin-top-alt:auto;margin-bottom:11.25pt;
        line-height:12.0pt'><span style='font-size:10.0pt;font-family:"Verdana","sans-serif";
        mso-fareast-font-family:"Times New Roman";mso-bidi-font-family:Arial'><img src="icons\training_event.png" alt= "Train Event" width="273" height="183"></span><span
        style='font-size:10.0pt;font-family:"Arial","sans-serif";mso-fareast-font-family:
        "Times New Roman"'><o:p></o:p></span></p>
        </td>
		

        <td width=471 align="left" valign=top style='width:353.4pt;padding:0in 7.5pt 0in 7.5pt'>
        <p class=MsoNormal style='mso-margin-top-alt:auto;margin-bottom:11.25pt;
        line-height:12.0pt'><span style='font-size:10.0pt;font-family:"Verdana","sans-serif";
        mso-fareast-font-family:"Times New Roman";mso-bidi-font-family:Arial'><b><?php echo $session->location;?> - <?php echo $session->venue;?></b>
		
		<br><br><b>Trainer</b><br><ul>
          <?php 			
        foreach($trecords as $record) {
           echo '<li>'.$record->firstname;?>  <?php echo $record->lastname.'</li>';
		} 
		?>
		</ul>
		
		<br><b>Attendees</b><br><ul>
          <?php if ($attendees = classroom_get_attendees($session->id)) {			
        foreach($attendees as $attendee) {
           echo '<li>'.$attendee->firstname;?>  <?php echo $attendee->lastname.'</li>';
		} 
		}?>
		</ul><br><br><b>Details</b><br><?php echo $session->details;?><br>
        <span
        style='font-size:10.0pt;font-family:"Arial","sans-serif";mso-fareast-font-family:
        "Times New Roman"'><o:p></o:p></span></p>
        </td>
       </tr>
      </table>
      </td>
     </tr>
     <tr style='mso-yfti-irow:2;mso-yfti-lastrow:yes'>
      <td valign=top style='padding:0in 7.5pt 0in 7.5pt'>
      <p class=MsoNormal style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
      auto;line-height:10.5pt'><span style='font-size:8.5pt;font-family:"Verdana","sans-serif";
      mso-fareast-font-family:"Times New Roman";mso-bidi-font-family:"Times New Roman";
      color:#666666'>Last Updated on <?php echo userdate($session->timecreated, get_string('strftimedate')); ?></span><span
      style='font-size:12.0pt;font-family:"Times New Roman","serif";mso-fareast-font-family:
      "Times New Roman"'><o:p></o:p></span></p>
      </td>
     </tr>
    </table>
    <p class=MsoNormal style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
    auto;line-height:12.0pt'><span style='font-size:12.0pt;font-family:"Verdana","sans-serif";
    mso-fareast-font-family:"Times New Roman";mso-bidi-font-family:"Times New Roman"'>&nbsp;</span><span
    style='font-size:12.0pt;font-family:"Times New Roman","serif";mso-fareast-font-family:
    "Times New Roman"'><o:p></o:p></span></p>
    <p class=MsoNormal style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
    auto;line-height:12.0pt'><span style='font-size:12.0pt;font-family:"Verdana","sans-serif";
    mso-fareast-font-family:"Times New Roman";mso-bidi-font-family:"Times New Roman"'>&nbsp;</span><span
    style='font-size:12.0pt;font-family:"Times New Roman","serif";mso-fareast-font-family:
    "Times New Roman"'><o:p></o:p></span></p>
    </td>
   </tr>
  </table>
  </td>
 </tr>
 <tr style='mso-yfti-irow:1;mso-yfti-lastrow:yes'>
  <td valign=top style='padding:0in 0in 0in 0in'></td>
 </tr>
 <tr style='mso-yfti-irow:1;mso-yfti-lastrow:yes'>

	<td colspan="2" align="right">
	<a href="<?php echo $url; ?>"><img border="0" src="icons\addmore.png" alt="Add more training event" ></a>&nbsp&nbsp
	<a href="<?php echo $urlexit; ?>"><img border="0" src="icons\done.png" alt="Exit" ></a>

    </td>
</tr>
</table>
</center>
<?

print_footer($course);

?>
