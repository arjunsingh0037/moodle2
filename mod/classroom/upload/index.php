<?php

require_once('../../../config.php');
require_once('../lib.php');

define("MAX_USERS_PER_PAGE", 5000);

$s              = required_param('s', PARAM_INT); // classroom session ID
$add            = optional_param('add', 0, PARAM_BOOL);
$remove         = optional_param('remove', 0, PARAM_BOOL);
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_RAW); // search string
$suppressemail  = optional_param('suppressemail', false, PARAM_BOOL); // send email notifications
$previoussearch = optional_param('previoussearch', 0, PARAM_BOOL);
$upload         = optional_param('upload', PARAM_INT); // classroom session ID

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

/// Main page
$pagetitle = format_string($session->programename);
$navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
$navlinks[] = array('name' => $pagetitle, 'link' => "view.php?f=$classroom->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('attendees', 'classroom'), 'link' => "attendees.php?s=$session->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('addremoveattendees', 'classroom'), 'link' => '', 'type' => 'title');
$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', '', '', '', true,
                    update_module_button($cm->id, $course->id, $strclassroom), '');

//print_heading('Add attendees to '.$session->programename);

echo '<table align="center" border="0" cellpadding="5" cellspacing="0"><tr><td class="generalboxcontent">';

    print_heading($heading, 'center');
echo '<br/>';
   $toprow = array();

$user = get_record('user','id', $USER->id);
		echo '<table width="100%" >';
		echo '<tr>';
		echo '<td valign="top" width="100">';
		
		echo '</td>';
		echo '<td>';

		echo '<br/>';
		echo '<p style="width:700px"></p>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
        


	  
?>





<link href="style.css" rel="stylesheet" type="text/css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.form.js"></script>
<script>
$(document).ready(function()
{
    $('#FileUploader').on('submit', function(e)
    {
        e.preventDefault();
        $('#uploadButton').attr('disabled', ''); // disable upload button
        //show uploading message
        $("#output").html('<div ><img src="images/ajax-loader.gif" alt="Please Wait"/> <span>Uploading...</span></div>');
        $(this).ajaxSubmit({
        target: '#output',
        success:  afterSuccess //call function after success
        });
    });
});
 
function afterSuccess()
{
    $('#FileUploader').resetForm();  // reset form
    $('#uploadButton').removeAttr('disabled'); //enable submit button
}
</script>

<div id="theFormBlank">
<table class=MsoTableGrid border=0 cellspacing=0 cellpadding=0
 style='border-collapse:collapse;border:none;mso-yfti-tbllook:1184;mso-padding-alt:
 .1in .1in .1in .1in;mso-border-insideh:none;mso-border-insidev:none'>

 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td  rowspan=2 style='padding:.1in .1in .1in .1in'>
  <p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
  text-align:center;line-height:normal'><span>
  <?php echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&amp;course='.$COURSE->id.'"><img height="120" width="120" src="'.$CFG->wwwroot.'/user/pix.php?file=/'.$USER->id.'/f1.jpg" width="80px" height="80px" title="'.$USER->firstname.' '.$USER->lastname.'" alt="'.$USER->firstname.' '.$USER->lastname.'" /></a>'; ?>
  
  </span></p>
  </td>

 </tr>
 <tr style='mso-yfti-irow:1;height:28.25pt'>
  <td rowspan=2  valign=top style='width:350.4pt;
  height:28.25pt'>

<br/>
    <p style='text-align:justify'><span style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#17375E'>Please upload your proof of completion for <?php echo $session->programename;?>.</span><br/><span
style='color:#376092'> </span><span style='color:#558ED5'>The proof of completion can be a certificate or any
document related to attending/completion of event.You can also upload screen shots of results
or digital certificates</p>
  
  </td>

 </tr>
 <tr style='mso-yfti-irow:2;mso-yfti-lastrow:yes;height:9.0pt'>
  <td  valign=top style='width:220.4pt;padding:.1in .1in .1in .1in;
  height:9.0pt'>
  <p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
  text-align:center;line-height:normal'><span style='font-size:10.0pt;
  mso-fareast-font-family:"Arial Unicode MS";mso-bidi-font-family:Calibri;
  mso-bidi-theme-font:minor-latin;color:#953735;mso-themecolor:accent2;
  mso-themeshade:191;mso-style-textfill-fill-color:#953735;mso-style-textfill-fill-themecolor:
  accent2;mso-style-textfill-fill-alpha:100.0%;mso-style-textfill-fill-colortransforms:
  lumm=75000'>  </span><span style='font-size:10.0pt;mso-fareast-font-family:
  "Arial Unicode MS";mso-bidi-font-family:Calibri;mso-bidi-theme-font:minor-latin'><o:p></o:p></span></p>
  </td>
  <![if !supportMisalignedRows]>

  <![endif]>
 </tr>

 </table>

</div>

<div id="theForm">
<form action="uploader.php" id="FileUploader" enctype="multipart/form-data" method="post" >

<table align="center" cellpadding="5">


<tr valign="top">
    <td align="right"><label>Certificate Title<span class="small">Name of the certificate</span></label></td>
    <td>
	<input  type="hidden" name="sessionID" value="<?php echo $s;?>">
<a href="#" class="tooltip">
	<input  type="text" name="mName" id="mName" />
		
	 <span>
		<p align="left">This could be the title displayed on the certificate.</p>
		 <img class="callout" src="../icons/callout.gif" />       
    </span>
	</a>
   
	
    </td>
</tr>
<tr valign="top">
    <td align="right"><label>Certificate Issued by<span class="small">Authority issuing the certificate</span></label></td>
    <td>
	<input type="text" name="mIssue" id="mName" />
    </td>
</tr>	
	<tr valign="top">
	<td align="right">
	<label>Certificate File
    <span class="small">Choose a File to upload</span>
    </label>
	</td>
    <td>

<a href="#" class="tooltip">
<input type="file" name="mFile" id="mFile" /> <div class="spacer">

	 <span>
		<p align="left">You can upload jpg/gif or pdf. Please limit the file to 2 (MB).</p>
		 <img class="callout" src="../icons/callout.gif" />       
    </span>
	</a>
    </td>
</tr>
<tr valign="top">
    <td align="right">
	</td>
  
</tr>
<tr valign="top">
    <td align="right">
	<label>Comments<span class="small">Any additional comments</span></label>
	</td>
    <td><textarea name="mComments" rows="8" cols="65"></textarea></td>
</tr>
<tr valign="top">
<td></td>
    <td>
	<button type="reset" class="red-button" >Clear</button>
  <button type="submit" class="green-button" id="uploadButton">Submit</button>
    </td>
</tr>

</table>
</form>

</div>
<div id="theForm">
<table>
<tr >

    <td align="left" colspan="2">

    </td>
</tr>
</table>
  </div>
<?php

print_footer($course);

?>