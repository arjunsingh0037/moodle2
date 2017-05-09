<?php
ini_set('max_execution_time',0);
ini_set('memory_limit',-1);
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/filelib.php');
//include_once('attendees.php');

$query=get_records_sql("select * from mdl_sendmail_temp where reg_date!='1'");
if(count($query)>0)
{
foreach($query as $values)
{
$idss=$values->id;
$sesssionids=$values->sessionid;
$classroom_id=$values->classroom_id;
$classroom_sesid=$values->classroom_sesid;
execute_sql("UPDATE mdl_sendmail_temp SET reg_date = '1' WHERE classroom_id='$classroom_id' and classroom_sesid='$classroom_sesid' ",false);
send_mailto_users($values->sessionid);


execute_sql("delete from mdl_sendmail_temp where classroom_id='$classroom_id' and classroom_sesid='$classroom_sesid'",false);
}
return true;
}
else
{
return true;
}
//redirect($CFG->wwwroot.'/mod/classroom/attendees.php?sendmail=1');
//exit;
?>