<?php
require_once('../../../config.php');
require_once('../lib.php');
$UploadDirectory	= $CFG->dataroot.'/1/externalilt/'; //Upload Directory, ends with slash & make sure folder exist


if (!@file_exists($UploadDirectory)) {
	//destination folder does not exist
	die("Make sure Upload directory exist!");
}

if($_POST)
{	
	if(!isset($_POST['mName']) || strlen($_POST['mName'])<1)
	{
		//required variables are empty
		die("<li>Please provide the title printed on the certificate.</li>");
	}
	
	
	if(!isset($_FILES['mFile']))
	{
		//required variables are empty
		die("<li>Please upload your completion certificate.</li>");
	}

	
	if($_FILES['mFile']['error'])
	{
		//File upload error encountered
		die(upload_errors($_FILES['mFile']['error']));
	}

	if(!isset($_POST['mIssue']) || strlen($_POST['mIssue'])<1)
	{
		//required variables are empty
		die("Certificate Issued by is empty!");
	}
	
	$sessionID			= mysql_real_escape_string($_POST['sessionID']); // file title
	$FileName			= strtolower($_FILES['mFile']['name']); //uploaded file name
	$FileTitle			= mysql_real_escape_string($_POST['mName']); // file title
	$FileIssued			= mysql_real_escape_string($_POST['mIssue']); // file title
	$ImageExt			= substr($FileName, strrpos($FileName, '.')); //file extension
	$FileType			= $_FILES['mFile']['type']; //file type
	$FileSize			= $_FILES['mFile']["size"]; //file size
	$RandNumber   		= rand(0, 9999999999); //Random number to make each filename unique.
	$uploaded_date		= date("Y-m-d H:i:s");
	
	/*switch(strtolower($FileType))
	{
		//allowed file types
		case 'image/png': //png file
		case 'image/gif': //gif file 
		case 'image/jpeg': //jpeg file
		case 'application/pdf': //PDF file
		case 'application/msword': //ms word file
		case 'application/vnd.ms-excel': //ms excel file
		case 'application/x-zip-compressed': //zip file
		case 'text/plain': //text file
		case 'text/html': //html file
			break;
		default:
			die('Unsupported file format. Upload png,gif,jpg and pdf !'); //output error
	}*/

  
	//File Title will be used as new File name
	$NewFileName = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), strtolower($FileTitle));
	$NewFileName = $NewFileName.'_'.$RandNumber.$ImageExt;
   //Rename and save uploded file to destination folder.
   if(move_uploaded_file($_FILES['mFile']["tmp_name"], $UploadDirectory . $NewFileName ))
   {
		
			$timenow = time();
		    $usersignup = new stdclass;
			$usersignup->userid = $USER->id;
			$usersignup->certificate = 1;
			$usersignup->eventnamefile = $NewFileName;
			$usersignup->issuedby = $FileIssued;
			$usersignup->timemodified = $timenow;

			begin_sql();	
			if ($returnid = insert_record('classroom_sessions_external', $usersignup)) {

			$_SESSION['uploadid']=$returnid;
			die('File sucessfully uploaded');
			}
		
   }else{
   		die('Provide a shorter certificate title');
   }
}

//function outputs upload error messages, http://www.php.net/manual/en/features.file-upload.errors.php#90522
function upload_errors($err_code) {
	switch ($err_code) { 
        case UPLOAD_ERR_INI_SIZE: 
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
        case UPLOAD_ERR_FORM_SIZE: 
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; 
        case UPLOAD_ERR_PARTIAL: 
            return 'The uploaded file was only partially uploaded'; 
        case UPLOAD_ERR_NO_FILE: 
            return 'No file was uploaded'; 
        case UPLOAD_ERR_NO_TMP_DIR: 
            return 'Missing a temporary folder'; 
        case UPLOAD_ERR_CANT_WRITE: 
            return 'Failed to write file to disk'; 
        case UPLOAD_ERR_EXTENSION: 
            return 'File upload stopped by extension'; 
        default: 
            return 'Unknown upload error'; 
    } 
} 
?>