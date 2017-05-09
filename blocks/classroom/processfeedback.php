<?PHP
require_once '../../config.php';
require_login();
$id = optional_param('id', '', PARAM_INT);
$downloadlink="viewfeedback.php?id=".$id;
echo '<iframe src="'.$downloadlink.'" width="100%" height="100%" frameborder="0" scrolling="no" />';
?>