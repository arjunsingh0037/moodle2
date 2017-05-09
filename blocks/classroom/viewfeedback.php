<?PHP
require_once '../../config.php';
require_login();
$id= optional_param('id', '', PARAM_INT);
$downloadlink='http://lms101.kds.keane.com:8080/Reports/frameset?__report=ILTFeedbackTrainer.rptdesign&id='.$id.'&__format=pdf';
echo '<iframe src="'.$downloadlink.'" width="100%" height="100%" frameborder="0" scrolling="no" />';
?>