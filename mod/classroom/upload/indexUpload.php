<?php

require_once('../../../config.php');
require_once('../lib.php');

define("MAX_USERS_PER_PAGE", 5000);


/// Check essential permissions
require_course_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);


	  
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
    //$('#FileUploader').resetForm();  // reset form
    $('#uploadButton').removeAttr('disabled'); //enable submit button
}
</script>



<div id="theFormInner">
<form action="uploaderSession.php" id="FileUploader" enctype="multipart/form-data" method="post" >

<table align="center" cellpadding="5">


<tr valign="top">
    <td align="right"><label>Certificate Title<span class="small">Short Name of Certificate</span></label></td>
    <td>


	<input  type="text" name="mName" id="mName" size="50" maxlength="50" value="<?php echo isset($_POST['mName']) ? $_POST['mName'] : '' ?>" />

   
	
    </td>
</tr>
<tr valign="top">
    <td align="right"><label>Certificate Issued by<span class="small">Authority issuing the certificate</span></label></td>
    <td>
	<input type="text" name="mIssue" id="mIssue" size="50" maxlength="50" value="<?php echo isset($_POST['mIssue']) ? $_POST['mIssue'] : '' ?>" />
    </td>
</tr>	
	<tr valign="top">
	<td align="right">
	<label>Certificate File
    <span class="small">Choose a File to upload</span>
    </label>
	</td>
    <td>


<input type="file" name="mFile" id="mFile" /> <div class="spacer">


    </td>
</tr>

<tr valign="top">
<td align="left" >

    </td>
    <td align="right">
<div style="font-size: 11px; color:red; line-height: 18px;" id="output" ></div>
<?php
	echo '<button type="submit" class="upload-button" id="uploadButton">Upload (1 MB)</button>'; 	
?>
    </td>
</tr>

</table>
</form>

</div>


