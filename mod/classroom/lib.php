<?php

require_once($CFG->libdir.'/gradelib.php');
//Naga added for duartion arch changes
require_once($CFG->libdir . '/game/durationlib.php');
    //Send notification to mobile device
/**
 * Definitions for setting notification types
 */
/**
 * Utility definitions
 */
define('MDL_F2F_ICAL',          1);
define('MDL_F2F_TEXT',          2);
define('MDL_F2F_BOTH',          3);
define('MDL_F2F_INVITE',        4);
define('MDL_F2F_CANCEL',        8);

/**
 * Definitions for use in forms
 */
define('MDL_F2F_INVITE_BOTH',       7);     // Send a copy of both 4+1+2
define('MDL_F2F_INVITE_TEXT',       6);     // Send just a plain email 4+2
define('MDL_F2F_INVITE_ICAL',       5);     // Send just a combined text/ical message 4+1
define('MDL_F2F_CANCEL_BOTH',       11);    // Send a copy of both 8+2+1
define('MDL_F2F_CANCEL_TEXT',       10);    // Send just a plan email 8+2
define('MDL_F2F_CANCEL_ICAL',       9);     // Send just a combined text/ical message 8+1

// Name of the custom field where the manager's email address is stored
define('MDL_MANAGERSEMAIL_FIELD', 'managersemail');

/**
 * Prints the cost amount along with the appropriate currency symbol.
 *
 * To set your currency symbol, set the appropriate 'locale' in
 * lang/en_utf8/langconfig.php (or the equivalent file for your
 * language).
 *
 * @param $amount      Numerical amount without currency symbol
 * @param $htmloutput  Whether the output is in HTML or not
 */
function format_cost($amount, $htmloutput=true) {
    setlocale(LC_MONETARY, get_string('locale'));
    $localeinfo = localeconv();

    $symbol = $localeinfo['currency_symbol'];
    if (empty($symbol)) {
        // Cannot get the locale information, default to en_US.UTF-8
        return '$' . $amount;
    }

    // Character between the currency symbol and the amount
    $separator = '';
    if ($localeinfo['p_sep_by_space']) {
        $separator = $htmloutput ? '&nbsp;' : ' ';
    }

    // The symbol can come before or after the amount
    if ($localeinfo['p_cs_precedes']) {
        return $symbol . $separator . $amount;
    }
    else {
        return $amount . $separator . $symbol;
    }
}

/**
 * Returns the effective cost of a session depending on the presence
 * or absence of a discount code.
 *
 * @param class $sessiondata contains the discountcost and normalcost
 */
 
 //function added by srinu maridu
  function send_mailto_users($sessionvalue)
{
$classsessids = get_records_sql("select * from mdl_sendmail_temp where sessionid='$sessionvalue'");
foreach($classsessids as $classsessid)
{
$clrid=$classsessid->classroom_id; 
$clsid=$classsessid->classroom_sesid;
}
if(count($classsessids)>0)
{
$classroom = get_record('classroom', 'id', $clrid);
//$session = get_record("classroom_sessions","id",$clsid);
$session=classroom_get_session($clsid);
$idss=$session->id;
$timenow = time();
 
$record1=get_records_sql("SELECT id,userid,sessionid,mailedfeedback,attend,timecancelled,mailedabsentees    from mdl_classroom_submissions   where sessionid=$idss and timecancelled =0 group by userid order by userid");

 
foreach( $record1 as  $record){

if($record->attend==1)
{
if(($session->feedbackname !='') && ($record->mailedfeedback ==0))
        {
            $error = classroom_feedback_notice($classroom, $session, $record->userid);
            if(!$error)
                {       
                $execrecord= execute_sql("
                UPDATE
                    mdl_classroom_submissions
                SET
                    mailedfeedback = $timenow
                WHERE
                    userid = $record->userid and sessionid= $session->id and classroom= $classroom->id
            ",false);
                }               
        }
        else if($session->trainingsource=='External')
        {
        if($record->mailedfeedback == 0 || $record->mailedfeedback == null)
            {
            $error = classroom_externalevent_user_attendence_notice($classroom, $session, $record->userid);
            if(!$error)
                {       
                $execrecord= execute_sql("
                UPDATE
                    mdl_classroom_submissions
                SET
                    mailedfeedback = $timenow
                WHERE
                    userid = $record->userid and sessionid= $session->id and classroom= $classroom->id
            ",false);
                }
            }
        }
}
        
        else
            {
            
            //Send absentees mail only if the user has not received the notice
            if($record->mailedabsentees == 0 || $record->mailedabsentees == null)
            {
            $error = classroom_absentees_notice($classroom, $session, $record->userid);
            if(!$error)
                {       
                $execrecord= execute_sql("
                UPDATE
                    mdl_classroom_submissions
                SET
                    mailedabsentees = $timenow
                WHERE
                    userid = $record->userid
                    and sessionid= $session->id and classroom= $classroom->id
            ",false);
                }               
            }
            
            }
            
         
         }
         return true;
         }
         else
         {
         return true;
         }
        
}
 //function ended by srinu maridu
 
function classroom_cost($userid, $sessionid, $sessiondata, $htmloutput=true) {

    global $CFG;

    if (count_records_sql("SELECT COUNT(*)
                               FROM {$CFG->prefix}classroom_submissions su,
                                    {$CFG->prefix}classroom_sessions se
                              WHERE su.sessionid=$sessionid
                                AND su.userid=$userid
                                AND su.discountcode IS NOT NULL
                                AND su.sessionid = se.id
                                AND su.timecancelled = 0") > 0) {
        return format_cost($sessiondata->discountcost, $htmloutput);
    } else {
        return format_cost($sessiondata->normalcost, $htmloutput);
    }
}

/**
 * Human-readable version of the duration field used to display it to
 * users
 *
 * @param integer $duration duration in minutes
 */
function classroom_duration($duration) {

    $minutes = round(($duration - floor($duration)) * 60);
    $hours = floor($duration);

    $string = '';

    if (1 == $hours) {
        $string = get_string('onehour', 'classroom');
    } elseif ($hours > 1) {
        $string = get_string('xhours', 'classroom', $hours);
    }

    // Insert separator between hours and minutes
    if ($string != '') {
        $string .= ' ';
    }

    if (1 == $minutes) {
        $string .= get_string('oneminute', 'classroom');
    } elseif ($minutes > 0) {
        $string .= get_string('xminutes', 'classroom', $minutes);
    }

    return $string;
}

/**
 * Converts minutes to hours
 */
function classroom_minutes_to_hours($minutes) {

    return round($minutes / 60.0, 2);
}

/**
 * Converts hours to minutes
 */
function classroom_hours_to_minutes($hours) {

    return round($hours * 60.0);
}

/**
 * Turn undefined manager messages into empty strings
 */
function classroom_fix_manager_messages($classroom) {

    if (empty($classroom->emailmanagerconfirmation)) {
        $classroom->confirmationinstrmngr = '';
    }
    // if (empty($classroom->emailmanagerreminder)) {
        // $classroom->reminderinstrmngr = '';
    // }
    if(isset($classroom->emailmanagerreminder))
    {
    if (empty($classroom->emailmanagerreminder)) {
        $classroom->reminderinstrmngr = '';
    }
    }
    else
    {
    $classroom->reminderinstrmngr = '';
    }
    
    if (empty($classroom->emailmanagercancellation)) {
        $classroom->cancellationinstrmngr = '';
    }
    if (empty($classroom->thirdpartywaitlist)) {
        $classroom->thirdpartywaitlist = 0;
    }
     
     //Naga added for multiplesignups
    if (empty($classroom->multiplesession)) {
        $classroom->multiplesession = 0;
    }
    //Naga added for multiplesignups
    
}

/**
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will create a new instance and
 * return the id number of the new instance.
 */
function classroom_add_instance($classroom) {

    $classroom->timemodified = time();

    classroom_fix_manager_messages($classroom);
    if (empty($classroom->emailabsentees)) {
        $classroom->absenteesmessage = '';
    }
    if ($returnid = insert_record('classroom', $classroom)) {
        classroom_grade_item_update($classroom);
    }
    return $returnid;
}

/**
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will update an existing instance
 * with new data.
 */
function classroom_update_instance($classroom) {

    $classroom->id = $classroom->instance;

    classroom_fix_manager_messages($classroom);
    if (empty($classroom->emailabsentees)) {
        $classroom->absenteesmessage = '';
    }
    if ($returnid = update_record('classroom', $classroom)) {
        classroom_grade_item_update($classroom);
    }
    return $returnid;
}

/**
 * Given an ID of an instance of this module, this function will
 * permanently delete the instance and any data that depends on it.
 */
function classroom_delete_instance($id) {

    global $CFG;

    if (!$classroom = get_record('classroom', 'id', $id)) {
        return false;
    }

    $result = true;
    begin_sql();

    if (!delete_records('classroom_submissions', 'classroom', $classroom->id)) {
        $result = false;
    }

    if (!delete_records_select('classroom_sessions_dates', "sessionid in (SELECT id FROM {$CFG->prefix}classroom_sessions WHERE classroom = $classroom->id)")) {
        $result = false;
    }

    if (!delete_records('classroom_sessions', 'classroom', $classroom->id)) {
        $result = false;
    }

    if (!delete_records('classroom', 'id', $classroom->id)) {
        $result = false;
    }

    if (!delete_records('event', 'modulename', 'classroom', 'instance', $classroom->id)) {
        $result = false;
    }

    if (!classroom_grade_item_delete($classroom)) {
        $result = false;
    }

    if ($result) {
        commit_sql();
    } else {
        rollback_sql();
    }

    return $result;
}

/**
 * Prepare the user data to go into the database.
 */
function cleanup_session_data($session) {

    // Convert hours (expressed like "1.75" or "2" or "3.5") to minutes
    $session->duration = classroom_hours_to_minutes($session->duration);

    // Only numbers allowed here
    $session->capacity = preg_replace('/[^\d]/', '', $session->capacity);
    $MAX_CAPACITY = 100000;
    if ($session->capacity < 1) {
        $session->capacity = 1;
    }
    elseif ($session->capacity > $MAX_CAPACITY) {
        $session->capacity = $MAX_CAPACITY;
    }

    // Get the decimal point separator
    setlocale(LC_MONETARY, get_string('locale'));
    $localeinfo = localeconv();
    $symbol = $localeinfo['decimal_point'];
    if (empty($symbol)) {
        // Cannot get the locale information, default to en_US.UTF-8
        $symbol = '.';
    }

    // Only numbers or decimal separators allowed here
    $session->normalcost = round(preg_replace("/[^\d$symbol]/", '', $session->normalcost));
    $session->discountcost = round(preg_replace("/[^\d$symbol]/", '', $session->discountcost));

    return $session;
}

/**
 * Create an event each time a classroom session is created
 * Tibi Custom Function #1
 */
 
 function addEvent_forSession($session,$sessiondates){
    $idref="";
    $sql_tibi="select max(id) as maxid from mdl_classroom_sessions";
    $rstibi=mysql_query($sql_tibi);
    while($rowt=mysql_fetch_array($rstibi)){
    $idref=$rowt["maxid"];
    }

$sql_tibi="select a.id,a.name,b.fullname from mdl_classroom a , mdl_course b where a.id='".$session->classroom."' and a.course=b.id";
$rstibi=mysql_query($sql_tibi);
while($rowt=mysql_fetch_array($rstibi)){
if(sizeof($sessiondates)>0 && $session->datetimeknown!=0 )
for($i=0;$i<sizeof($sessiondates);$i++){

    $tibi_f2f_events = new StdClass;
    
    $tibi_f2f_events->name="$rowt[name]";
    $tibi_f2f_events->description="To go to the classroom session <a href=\"../mod/classroom/signup.php?s=$idref\">click here</a>";
    $tibi_f2f_events->format="1";
    $tibi_f2f_events->courseid="1";
    $tibi_f2f_events->groupid="0";
    $tibi_f2f_events->userid="0";
    $tibi_f2f_events->repeatid="0";
    $tibi_f2f_events->modulename="";
    $tibi_f2f_events->instance="1";
    $tibi_f2f_events->eventtype="open";//verifica datele ca sa vad
    $tibi_f2f_events->timeduration="0";
    $tibi_f2f_events->timestart=$sessiondates[$i]->timestart;
    $tibi_f2f_events->visible="1";
    $tibi_f2f_events->uuid="".$idref;
    $tibi_f2f_events->sequence="1";
    $tibi_f2f_events->timemodified="";
    insert_record('event', $tibi_f2f_events);
    }
}
 }
 
 /**
 * Delete event when classroom session is deleted
 * Tibi Custom Function #2
 */
 
 function delete_event_session($dorel){
 delete_records('event','uuid',$dorel);
 }


/**
 * Create a new entry in the classroom_sessions table
 */
function classroom_add_session($session, $sessiondates) {


    $session->timecreated = time();
    $session = cleanup_session_data($session);
    $session->status='Open';
  //  $feedbackid = get_record_sql("SELECT id FROM {$CFG->prefix}feedback 
//       where name=$session->feedbackname");
    //  $session->feedbackname=$feedbackid;
    begin_sql();
    if ($session->id = insert_record('classroom_sessions', $session)) {
        foreach ($sessiondates as $date) {
            $date->sessionid = $session->id;
            if (!insert_record('classroom_sessions_dates', $date)) {
                rollback_sql();
                return false;
            }
        }
        
        //tibi: every time a f2f session is created we also add it to the events table. 
        addEvent_forSession($session,$sessiondates);
        
                commit_sql();
                
                $userrecords = get_records_sql("SELECT userid FROM mdl_classroom_submissions where sessionid=$session->id");
            
                
        return $session->id;
    } else {
        rollback_sql();
        return false;
    }
}

/**
 * Modify an entry in the classroom_sessions table
 * Royphilip: Updated on 30/4/2012 for email notification
 */
function classroom_update_session($classroom,$session, $sessiondates,$sendnotification) {

    $session->timemodified = time();
    $sessionid = $session->id;
        //tibi remember the session id in variable
    $dorel=$sessionid;
    $session = cleanup_session_data($session);
   // $session = cleanup_session_data($session);

    begin_sql();
    if (!$session->id = update_record('classroom_sessions', $session)) {
        rollback_sql();
        return false;
    }

    if (!delete_records('classroom_sessions_dates', 'sessionid', $sessionid)) {
        rollback_sql();
        return false;
    }
    
    //tibi we delete the events and add them again with the new dates
        delete_event_session($dorel);
        addEvent_forSession($session,$sessiondates);
        
        
    foreach ($sessiondates as $date) {
        $date->sessionid = $sessionid;
        if (!insert_record('classroom_sessions_dates', $date)) {
            rollback_sql();
            return false;
        }
    }

    commit_sql();

    $session->sessiondates=$sessiondates;
    if($sendnotification==1)
    {
        $userrecords = get_records_sql("SELECT userid FROM mdl_classroom_submissions where sessionid=$sessionid and timecancelled=0");
                foreach ($userrecords as $sessionuser) {
            $error = classroom_reschedule_notice($classroom, $session, $sessionuser->userid);
        }

        $userrecords = get_records_sql("SELECT userid FROM mdl_classroom_trainners where sessionid=$sessionid and timecancelled=0");
                foreach ($userrecords as $sessiontrainner) {
            $error = classroom_reschedule_notice($classroom, $session, $sessiontrainner->userid);
        }
    }


    return $session->id;
}

/**
 * Return an array of all classroom activities in the current course
 */
function classroom_get_classroom_menu() {

    global $CFG;
    if ($classrooms = get_records_sql("SELECT f.id, c.shortname, f.name
                                            FROM {$CFG->prefix}course c, {$CFG->prefix}classroom f
                                            WHERE c.id = f.course
                                            ORDER BY c.shortname, f.name")) {
        $i=1;
        foreach ($classrooms as $classroom) {
            $f = $classroom->id;
            $classroommenu[$f] = $classroom->shortname.' --- '.$session->programename;
            $i++;
        }

        return $classroommenu;

    } else {
        
        return '';

    }
}

/**
 * Delete entry from the classroom_sessions table along with all
 * related details in other tables
 */
function classroom_delete_session($session) {
//tibi remember the session id in a variable
    $dorel=$session->s;

    begin_sql();
    if (!delete_records('classroom_sessions', 'id', $session->s)) {
        rollback_sql();
        return false;
    }
    if (!delete_records('classroom_submissions', 'sessionid', $session->s)) {
        rollback_sql();
        return false;
    }
    if (!delete_records('classroom_sessions_dates', 'sessionid', $session->s)) {
        rollback_sql();
        return false;
    }
    
        //tibi we delete the event when the session is deleted
        delete_event_session($dorel);

    commit_sql();
    return true;
}

/**
 * Subsitute the placeholders in email templates for the actual data
 */
 
 function tzdelta ( $iTime = 0 ) 
{ 
if ( 0 == $iTime ) { $iTime = time(); } 
$ar = localtime ( $iTime ); 
$ar[5] += 1900; $ar[4]++; 
$iTztime = gmmktime ( $ar[2], $ar[1], $ar[0], $ar[4], $ar[3], $ar[5], $ar[8] ); 
return ( $iTztime - $iTime )/3600; 
}

function classroom_email_substitutions($msg, $classroomname, $reminderperiod, $user, $session, $sessionid) {
global $USER;
$timezonul=$user->timezone;
if (($timezonul == '99') AND (tzdelta()>=0) ) { $timezonul = "+".tzdelta() ;}
else if (($timezonul == '99') AND (tzdelta()<0)) {$timezonul = tzdelta();}


    if ($session->datetimeknown) {
        // Scheduled session
        $sessiondate = userdate($session->sessiondates[0]->timestart, get_string('strftimedate'),$timezonul);
        $starttime = userdate($session->sessiondates[0]->timestart, get_string('strftimetime'),$timezonul);
        $finishtime = userdate($session->sessiondates[0]->timefinish, get_string('strftimetime'),$timezonul);

        $alldates = '';
        foreach ($session->sessiondates as $date) {
            if ($alldates != '') {
                $alldates .= "\n";
            }
            $alldates .= userdate($date->timestart, get_string('strftimedate'),$timezonul).', ';
            $alldates .= userdate($date->timestart, get_string('strftimetime'),$timezonul).
                ' to '.userdate($date->timefinish, get_string('strftimetime'),$timezonul);
                $alldates .=' UTC '.$timezonul;
        }
    }
    else if ($session->sessiondates) {
        foreach ($session->sessiondates as $date) {
            if ($alldates != '') {
                $alldates .= "\n";
            }
            $alldates .= userdate($date->timestart, get_string('strftimedate'),$timezonul).', ';
            $alldates .= userdate($date->timestart, get_string('strftimetime'),$timezonul).
                ' to '.userdate($date->timefinish, get_string('strftimetime'),$timezonul);
        }
        
    }

    else {
        // Wait-listed session
        $sessiondate = get_string('unknowndate', 'classroom');
    //    $alldates    = 'no date';
        $starttime   = get_string('unknowntime', 'classroom');
        $finishtime  = get_string('unknowntime', 'classroom');
        
    }
    //naga added to include feedback link in feed back remider mail
    $feedbackviewid = get_record_sql("SELECT m.id FROM mdl_course_modules m
                                        join mdl_feedback f where f.id=m.instance 
                                        and m.module=22 and f.name=(select feedbackname from 
                                        mdl_classroom_sessions where id=$sessionid)");
    
    
    $msg = str_replace(get_string('placeholder:sessionid', 'classroom'), $sessionid,$msg);
    $msg = str_replace(get_string('placeholder:feedbackid', 'classroom'), $feedbackviewid->id,$msg);
    // complted
    $msg = str_replace(get_string('placeholder:classroomname', 'classroom'), $classroomname,$msg);
    $msg = str_replace(get_string('placeholder:firstname', 'classroom'), $user->firstname,$msg);
    $msg = str_replace(get_string('placeholder:lastname', 'classroom'), $user->lastname,$msg);
    $msg = str_replace(get_string('placeholder:registerfirstname', 'classroom'), $USER->firstname,$msg);
    $msg = str_replace(get_string('placeholder:registerlastname', 'classroom'), $USER->lastname,$msg);
    $msg = str_replace(get_string('placeholder:cost', 'classroom'), classroom_cost($user->id, $sessionid, $session, false),$msg);
    $msg = str_replace(get_string('placeholder:alldates', 'classroom'), $alldates,$msg);
    $msg = str_replace(get_string('placeholder:sessiondate', 'classroom'), $sessiondate,$msg);
    $msg = str_replace(get_string('placeholder:starttime', 'classroom'), $starttime,$msg);
    $msg = str_replace(get_string('placeholder:finishtime', 'classroom'), $finishtime.' UTC '.$timezonul,$msg);

   // $msg = str_replace(get_string('placeholder:finishtime', 'classroom'), $finishtime,$msg);
    $msg = str_replace(get_string('placeholder:duration', 'classroom'), classroom_duration($session->duration),$msg);
    $msg = str_replace(get_string('placeholder:location', 'classroom'), $session->location,$msg);
    $msg = str_replace(get_string('placeholder:venue', 'classroom'), $session->venue,$msg);
    $msg = str_replace(get_string('placeholder:room', 'classroom'), $session->room,$msg);
    $msg = str_replace(get_string('placeholder:details', 'classroom'), $session->details,$msg);
    $msg = str_replace(get_string('placeholder:reminderperiod', 'classroom'), $reminderperiod,$msg);
    

    return $msg;
}



function classroom_email_substitutions_reminder($msg, $classroomname, $reminderperiod, $user, $session, $sessionid) {




    if ($session->datetimeknown) {
        // Scheduled session
        $sessiondate = userdate($session->sessiondates[0]->timestart, get_string('strftimedate'),$user->timezone);
        $starttime = userdate($session->sessiondates[0]->timestart, get_string('strftimetime'),$user->timezone);
        $finishtime = userdate($session->sessiondates[0]->timefinish, get_string('strftimetime'),$user->timezone);

        $alldates = '';
        foreach ($session->sessiondates as $date) {
            if ($alldates != '') {
                $alldates .= "\n";
            }
            $alldates .= userdate($date->timestart, get_string('strftimedate'),$user->timezone).', ';
            $alldates .= userdate($date->timestart, get_string('strftimetime'),$user->timezone).
                ' to '.userdate($date->timefinish, get_string('strftimetime'),$user->timezone);
            
        }
    }
    else if ($session->sessiondates) {
        foreach ($session->sessiondates as $date) {
            if ($alldates != '') {
                $alldates .= "\n";
            }
            $alldates .= userdate($date->timestart, get_string('strftimedate')).', ';
            $alldates .= userdate($date->timestart, get_string('strftimetime')).
                ' to '.userdate($date->timefinish, get_string('strftimetime'));
        }
        
    }

    else {
        // Wait-listed session
        $sessiondate = get_string('unknowndate', 'classroom');
    //    $alldates    = 'no date';
        $starttime   = get_string('unknowntime', 'classroom');
        $finishtime  = get_string('unknowntime', 'classroom');
        
    }

    $msg = str_replace(get_string('placeholder:classroomname', 'classroom'), $classroomname,$msg);
    $msg = str_replace(get_string('placeholder:firstname', 'classroom'), $user->firstname,$msg);
    $msg = str_replace(get_string('placeholder:lastname', 'classroom'), $user->lastname,$msg);
    $msg = str_replace(get_string('placeholder:cost', 'classroom'), classroom_cost($user->id, $sessionid, $session, false),$msg);
    $msg = str_replace(get_string('placeholder:alldates', 'classroom'), $alldates,$msg);
    $msg = str_replace(get_string('placeholder:sessiondate', 'classroom'), $sessiondate,$msg);
    $msg = str_replace(get_string('placeholder:starttime', 'classroom'), $starttime,$msg);
    $msg = str_replace(get_string('placeholder:finishtime', 'classroom'), $finishtime.' UTC '.$timezonul,$msg);

   // $msg = str_replace(get_string('placeholder:finishtime', 'classroom'), $finishtime,$msg);
    $msg = str_replace(get_string('placeholder:duration', 'classroom'), classroom_duration($session->duration),$msg);
    $msg = str_replace(get_string('placeholder:location', 'classroom'), $session->location,$msg);
    $msg = str_replace(get_string('placeholder:venue', 'classroom'), $session->venue,$msg);
    $msg = str_replace(get_string('placeholder:room', 'classroom'), $session->room,$msg);
    $msg = str_replace(get_string('placeholder:details', 'classroom'), $session->details,$msg);
    $msg = str_replace(get_string('placeholder:reminderperiod', 'classroom'), $reminderperiod,$msg);
    

    return $msg;
}
/**
 * Function to be run periodically according to the moodle cron
 * Finds all classroom notifications that have yet to be mailed out, and mails them.
 */
function classroom_cron () {

    global $CFG, $USER;

    if ($submissionsdata = classroom_get_unmailed_reminders()) {

        $timenow   = time();

        foreach ($submissionsdata as $submissiondata) {

            if (classroom_has_session_started($submissiondata, $timenow)) {
                // Too late, the session already started
                continue;
            }

            $reminderperiod = $submissiondata->reminderperiod;

            // Convert the period from business days (no weekends) to calendar days
            for ($reminderday = 0; $reminderday < $reminderperiod + 1; $reminderday++ ) {
                $reminderdaytime = $submissiondata->sessiondates[0]->timestart - ($reminderday * 24 * 3600);
                $reminderdaycheck = userdate($reminderdaytime, '%u');
                if ($reminderdaycheck > 5) {
                    // Saturdays and Sundays are not included in the
                    // reminder period as entered by the user, extend
                    // that period by 1
                    $reminderperiod++;
                }
            }

            $remindertime = $submissiondata->sessiondates[0]->timestart - ($reminderperiod * 24 * 3600);

            if ($timenow < $remindertime) {
                // Too early to send reminder
                continue;
            }

            if (! $user = get_record('user', 'id', "$submissiondata->userid")) {
                continue;
            }

            $USER->lang = $user->lang;

            if (! $course = get_record('course', 'id', "$submissiondata->course")) {
                continue;
            }

            if (! $classroom = get_record('classroom', 'id', "$submissiondata->classroomid")) {
                continue;
            }

            $postsubject = '';
            $posttext = '';
            $posttextmgrheading = '';

            if (empty ($submissiondata->mailedreminder)) {
                $postsubject = $classroom->remindersubject;
                $posttext = $classroom->remindermessage;
                $posttextmgrheading = $classroom->reminderinstrmngr;
            }

            if (! empty($postsubject) && ! empty ($posttext) ) {

                $postsubject = classroom_email_substitutions_reminder($postsubject, $submissiondata->classroomname, $submissiondata->reminderperiod, $user, $submissiondata, $submissiondata->sessionid);
                $posttext = classroom_email_substitutions_reminder($posttext, $submissiondata->classroomname, $submissiondata->reminderperiod, $user, $submissiondata, $submissiondata->sessionid);

                if (! empty($posttextmgrheading) ) {
                    $posttextmgrheading = classroom_email_substitutions_reminder($posttextmgrheading, $submissiondata->classroomname, $submissiondata->reminderperiod, $user, $submissiondata, $submissiondata->sessionid);
                }

                $posthtml = '';
                $fromaddress = get_config(NULL, 'classroom_fromaddress');
                if (!$fromaddress) {
                    $fromaddress = '';
                }

                require_once($CFG->dirroot.'/lib/adminlib.php');

                if (email_to_user($user, $fromaddress, $postsubject, $posttext, $posthtml)) {

                    echo get_string('sentreminderuser', 'classroom').": $user->firstname $user->lastname $user->email<BR />\n";

                    $submission = new object;
                    $submission->id = $submissiondata->id;
                    $submission->mailedreminder = $timenow;
                    update_record('classroom_submissions', $submission);

                    if (!empty($posttextmgrheading)) {
                        $managertext = $posttextmgrheading.$posttext;

                        $usercheck = get_record('user', 'id', $user->id);
                        $manager = $user;
                        $manager->email = classroom_get_manageremail($user->id);

                        if (!empty($manager->email) && email_to_user($manager, $fromaddress, $postsubject, $managertext, $posthtml)) {

                            echo get_string('sentremindermanager', 'classroom').": $user->firstname $user->lastname $manager->email<BR />\n";

                        } else {
                            $errormsg = array();
                            $errormsg['submissionid'] = $submissiondata->id;
                            $errormsg['userid'] = $user->id;
                            $errormsg['manageremail'] = $manager->email;
                            echo get_string('error:cronprefix', 'classroom').' '.get_string('error:cannotemailmanager', 'classroom', $errormsg)."\n";
                        }
                    }

                } else {
                    $errormsg = array();
                    $errormsg['submissionid'] = $submissiondata->id;
                    $errormsg['userid'] = $user->id;
                    $errormsg['useremail'] = $user->email;
                    echo get_string('error:cronprefix', 'classroom').' '.get_string('error:cannotemailuser', 'classroom', $errormsg)."\n";
                }
            }
        }
    } else {
        echo get_string('noremindersneedtobesent', 'classroom');
    }
    return true;
}


/**
 * Returns true if the session has started, that is if one of the
 * session dates is in the past.
 *
 * @param class $session record from the classroom_sessions table
 * @param integer $timenow current time
 */
function classroom_has_session_started($session, $timenow) {

    if (!$session->datetimeknown) {
        return false; // no date set
    }
    
    foreach ($session->sessiondates as $date) {
        if ($date->timestart < $timenow) {
          if($session->timecancelled == 0)
             {
               return true;
             }
        }
    }
    return false;
}
//**************Function:To check session end time ***********
function classroom_has_session_ended($session, $timenow) {

   $my=get_records_sql("select max(timefinish) as mytime from mdl_classroom_sessions_dates where sessionid=$session->id");
     foreach ($my as $date) {
       if ($date->mytime < $timenow) {
        
       return true ;
    }

    return false;
}
}



/********* Display cancelled sessions ****************
function classroom_print_sessions($courseid, $classroomid, $location,$page, $recordsperpage) {

    global $CFG, $USER;

    $context = get_context_instance(CONTEXT_COURSE, $courseid, $USER->id);

    $bookedsession = '0';
    $spanclass = '';

    $submissions = classroom_get_user_submissions($classroomid, $USER->id);

    $bookedsession = null;
    if ($submissions) {
        $submission = array_shift($submissions);
        $bookedsession = $submission->sessionid;
    }

    $timenow = time();

    $tableheader = '';

    if (has_capability('mod/classroom:viewattendees', $context)) {
        $tableheader = '<thead>'
                    . '<tr>'
                    . '<th class="header" align="left">'.get_string('location', 'classroom').'</th>'
                    . '<th class="header" align="left">'.get_string('venue', 'classroom').'</th>'
                    . '<th class="header">'.get_string('date', 'classroom').'</th>'
                    . '<th class="header">'.get_string('time', 'classroom').'</th>'             
                    . '<th class="header">'.get_string('capacity', 'classroom').'</th>'
                    . '<th class="header">'.get_string('status', 'classroom').'</th>'
                    . '<th class="header">'.get_string('options', 'classroom').'</th>'                              
                    . '</tr>'
                    . '</thead>';
    } else {
        $tableheader = '<thead>'
                    . '<tr>'
                    . '<th class="header" align="left">'.get_string('location', 'classroom').'</th>'
                    . '<th class="header" align="left">'.get_string('venue', 'classroom').'</th>'
                    . '<th class="header" >'.get_string('date', 'classroom').'</th>'
                    . '<th class="header" >'.get_string('time', 'classroom').'</th>'                    
                    . '<th class="header">'.get_string('seats available', 'classroom').'</th>'
                    . '<th class="header">'.get_string('status', 'classroom').'</th>'
                    . '<th class="header">'.get_string('options', 'classroom').'</th>'
                    . '</tr>'
                    . '</thead>';
    }

    $tableupcoming = '';
    $tableupcomingtbd = '';
    $tableprevious = '';

    if ($sessions = classroom_get_sessions($classroomid, $location,$page, $recordsperpage) ) {

        foreach($sessions as $session) {

            $status  = get_string('bookingopen', 'classroom');
            $options = '';
            $spanclass = '';
            if ($session->status=='Completed')
            {
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'Completed' WHERE id = $session->id ",false);
            }
            else
            {
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'Planned-Bookingopen' WHERE id = $session->id ",false);
            }
            $signupcount = classroom_get_num_attendees($session->id);
            
            if ($session->timecancelled) {
                //Setting cancel status for a program

                $status = get_string('sessioncancelled', 'classroom');
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'Cancelled' WHERE id = $session->id ",false);
                $options = '';
            }elseif ($session->closed) {

                $status = get_string('bookingclosed', 'classroom');

            }elseif ($signupcount >= $session->capacity) {

                $status = get_string('bookingfull', 'classroom');
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'Planned-Bookingfull' WHERE id = $session->id ",false);
            }

            $allsessiondates = '';
            $allsessiontimes = '';
            foreach ($session->sessiondates as $date) {
                if (!empty($allsessiondates)) {
                        $allsessiondates .= '<br />';
                }
                $allsessiondates .= userdate($date->timestart, get_string('strftimedate'));
                if (!empty($allsessiontimes)) {
                    $allsessiontimes .= '<br />';
                }
                $allsessiontimes .= userdate($date->timestart, get_string('strftimetime')).
                    ' - '.userdate($date->timefinish, get_string('strftimetime'));
            }

            // Defaults for normal users
            $stats = $session->capacity - $signupcount;
            $options = '';

            if ($session->datetimeknown && classroom_has_session_ended($session, $timenow)) {
                
                if ($session->timecancelled) {
                    //Setting cancel status for a program

                $status = get_string('sessioncancelled', 'classroom');
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'Cancelled' WHERE id = $session->id ",false);
                    $options = '';
                        }
                else if ($session->status=='Completed')
                    {
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'Completed' WHERE id = $session->id ",false);
                $status='Completed';
                    }
                else {
                $status = get_string('sessionover', 'classroom');
                $spanclass = ' class="dimmed_text"';
                execute_sql("UPDATE mdl_classroom_sessions SET status = 'notdone' WHERE id = $session->id ",false);
                $status='Not Done';
                
                    }
                
                if (has_capability('mod/classroom:editsessions', $context)) {
                
                if ($session->status=='Completed')
                {           
                $options .= '<a href="sessions.php?s='.$session->id.'&amp;c=1" title="'.get_string('copysession', 'classroom').'">'.get_string('copy', 'classroom').'</a> ';                                
                }
                else{
                    $options .= ' <a href="sessions.php?s='.$session->id.'" title="'.get_string('editsession', 'classroom').'">'.get_string('edit/reschedule', 'classroom').'</a> '
                        . '<a href="sessions.php?s='.$session->id.'&amp;c=1" title="'.get_string('copysession', 'classroom').'">'.get_string('copy', 'classroom').'</a> '
                       // . '<a href="sessions.php?s='.$session->id.'&amp;d=1" title="'.get_string('deletesession', 'classroom').'">'.get_string('delete').'</a> '
                        . '<a href="sessions.php?s='.$session->id.'&amp;ca=1" title="'.get_string('cancelsession', 'classroom').'">'.get_string('cancel').'</a> ';
                    }
                }
                if (has_capability('mod/classroom:viewattendees', $context)){
                    $stats = $signupcount.' / '.$session->capacity;
                    $options .= '<a href="attendees.php?s='.$session->id.'" title="'.get_string('seeattendees', 'classroom').'">'.get_string('attendees', 'classroom').'</a> '
                    . '<a href="trainners.php?s='.$session->id.'" title="'.get_string('seetrainers', 'classroom').'">'.get_string('Trainners', 'classroom').'</a> ';
                }

                if (empty($options)) {
     
                  $checkfeedbackexist=get_record_sql("SELECT sub.userid FROM {$CFG->prefix}classroom_submissions sub
                                            join {$CFG->prefix}classroom_sessions s on s.id=sub.sessionid
                                            and sub.sessionid=$session->id and sub.userid=$USER->id
                                            join {$CFG->prefix}feedback f on f.name=s.feedbackname
                                            join {$CFG->prefix}feedback_completed fc on f.id=fc.feedback 
                                            and fc.userid=$USER->id");
                  if(!$checkfeedbackexist)
                  {
                    $checkisuser=get_record_sql("SELECT sub.userid,f.course,f.id AS classroomid,mdl_grade_grades.finalgrade AS finalgrade
                                    FROM mdl_classroom_submissions sub
                                    join mdl_classroom_sessions s on s.id=sub.sessionid
                                    and sub.sessionid=$session->id and sub.userid=$USER->id join mdl_classroom f
                                    on f.id=s.classroom
                                    JOIN mdl_grade_items
                                    ON f.id = mdl_grade_items.iteminstance
                                    JOIN mdl_grade_grades
                                    ON mdl_grade_items.id = mdl_grade_grades.itemid
                                    JOIN mdl_course
                                    ON mdl_grade_items.courseid = mdl_course.id
                                    group by sub.userid"
                                            );
                  

                             if(!$checkisuser->userid)
                            {
                                $options .='<b>'.$checkisuser->userid.'</b>';
                            }
                            elseif ($checkisuser->finalgrade >=100||$checkisuser->finalgrade=="Completed")
                            {
                                $feedbackviewid = get_record_sql("SELECT m.id FROM {$CFG->prefix}course_modules m
                                        join {$CFG->prefix}feedback f where f.id=m.instance 
                                        and m.module=22 and f.name=(select feedbackname from 
                                        mdl_classroom_sessions where id=$session->id)");
                                        
                                $options .= '<a href="http://klci.keane.com/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid='.$session->id.'" title="'.get_string('givefeedbacksession', 'classroom').'">'.get_string('givefeedback', 'classroom').'</a> ';
                            
                            }
                    }
                    else
                    {
                    $options .="Feedback given";
                    }
            
                }

                $tableprevious .= '<tr>'
                            . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->location.'</span></td>'
                            . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->room.'</span></td>'
                            . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$allsessiondates.'</span></td>'
                            . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$allsessiontimes.'</span></td>'
                            . '<td class="content" style="width: 10%" align="center"><span '.$spanclass.'>'.$stats.'</span></td>'
                            . '<td class="content" style="width: 10%" align="center"><span '.$spanclass.'>'.$status.'</span></td>'
                            . '<td class="content" style="width: 20%" align="center"><span '.$spanclass.'>'.$options.'</span></td>'
                            
                            . '</tr>';

            } else {
            
                    if ($session->status=='Completed')
                    {
                    $status='Completed';
                    }
                    
                $trclass = '';
                $spanclass = '';

                if (has_capability('mod/classroom:editsessions', $context)) {
                
                 if ($session->status=='Completed')
                        {           
                        $options = '<a href="sessions.php?s='.$session->id.'&amp;c=1" title="'.get_string('copysession', 'classroom').'">'.get_string('copy', 'classroom').'</a> ';                                 
                        }
                else{
                    $options .= '<a href="sessions.php?s='.$session->id.'" title="'.get_string('editsession', 'classroom').'">'.get_string('edit/reschedule', 'classroom').'</a> '
                        . '<a href="sessions.php?s='.$session->id.'&amp;c=1" title="'.get_string('copysession', 'classroom').'">'.get_string('copy', 'classroom').'</a> '
                        . '<a href="sessions.php?s='.$session->id.'&amp;d=1" title="'.get_string('deletesession', 'classroom').'">'.get_string('delete').'</a> '
                        . '<a href="sessions.php?s='.$session->id.'&amp;ca=1" title="'.get_string('cancelsession', 'classroom').'">'.get_string('cancel').'</a> ';
               }
                }
                if (has_capability('mod/classroom:viewattendees', $context)) {
                    $stats = $signupcount.' / '.$session->capacity;
                    $options .= ' <a href="attendees.php?s='.$session->id.'" title="'.get_string('seeattendees', 'classroom').'">'.get_string('attendees', 'classroom').'</a> ';
                }
//****** Roy Philip:Add trainers link to be added in the view page.

                if (has_capability('mod/classroom:editsessions', $context)){
                    $stats = $signupcount.' / '.$session->capacity;
                    $options .= '<a href="trainners.php?s='.$session->id.'" title="'.get_string('seetrainers', 'classroom').'">'.get_string('Trainners', 'classroom').'</a> ';
                }
                if ($session->id == $bookedsession) {
                
                    $trclass = ' class="highlight"';
                    $tableupcoming .= '<tr'.$trclass.'><td class="content" colspan="7"><span style="font-size: 12px; line-height: 12px;"><b>'.get_string('youarebooked', 'classroom').':</b></span></td></tr>';

                    $options .= '<a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'" alt="'.get_string('moreinfo', 'classroom').'" title="'.get_string('moreinfo', 'classroom').'">'.get_string('moreinfo', 'classroom').'</a><br />'
                        . '<a href="'.$CFG->wwwroot.'/mod/classroom/attendees.php?s='.$session->id.'&amp;viewall='.$classroomid.'" alt="'.get_string('seeattendees', 'classroom').'" title="'.get_string('seeattendees', 'classroom').'">'.get_string('seeattendees', 'classroom').'</a><br />'
                        . '<a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;cancelbooking=1&amp;viewall='.$classroomid.'" alt="'.get_string('cancelbooking', 'classroom').'" title="'.get_string('cancelbooking', 'classroom').'">'.get_string('cancelbooking', 'classroom').'</a>';
                } else {
                    if ($bookedsession || ($status == get_string('bookingfull', 'classroom'))) {
                        $spanclass = ' class="dimmed_text"';
                    } else {
                        if ($session->timecancelled) 
                        {
                        $options = '';
                         $options .= '<a href="sessions.php?s='.$session->id.'&amp;d=1" title="'.get_string('deletesession', 'classroom').'">'.get_string('delete').'</a> ';
                       
                        }
                        
                        else
                        {
                            $checkistrainer=get_record_sql("SELECT t.userid FROM {$CFG->prefix}classroom_trainners t
                                            join {$CFG->prefix}classroom_sessions s on s.id=t.sessionid
                                            and t.sessionid=$session->id and t.userid=$USER->id and t.timecancelled=0"
                                            );
                        //RoyPhilip:Allow trainer to take attendance.           
                             if($checkistrainer->userid)
                            {
                            $options.= '<a href="attendees.php?s='.$session->id.'&amp;takeattendance=1">'.get_string('takeattendance', 'classroom').'</a>';
                        //  http://klci.keane.com/mod/classroom/attendees.php?s=87&takeattendance=1
                            }
                            else
                            {
                        
                            $options .= '<a href="signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'">'.get_string('signup', 'classroom').'</a>';
                            }
                       }
                    }
                }

                if (empty($options)) {
                    $options = get_string('none', 'classroom');
                }
$sessionTrainers=get_records_sql("SELECT firstname FROM mdl_user u join mdl_classroom_trainners t on t.userid=u.id join mdl_classroom_sessions s on s.id=t.sessionid and t.id=$session->id");
 $arrayTrainers = array();
    foreach ($sessionTrainers as $sessionTrainer) {
        $arrayTrainers[] = $sessionTrainer->firstname;
        
    }
                        
                if ($session->datetimeknown) {

                    $tableupcoming .= '<tr'.$trclass.'>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->location.'</span></td>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->room.'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$allsessiondates.'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$allsessiontimes.'</span></td>'
                                
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$stats.'</span></td>'
                                . '<td class="content" style="width: 10%" align="center"><span '.$spanclass.'>'.$status.'</span></td>'
                                . '<td class="content" style="width: 20%" align="center"><span '.$spanclass.'>'.$options.'</span></td>'
                                . '</tr>';

                } else {

                    $tableupcomingtbd .= '<tr'.$trclass.'>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->location.'</span></td>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->room.'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.get_string('wait-listed', 'classroom').'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.get_string('wait-listed', 'classroom').'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$stats.'</span></td>'
                                . '<td class="content" style="width: 10%" align="center"><span '.$spanclass.'>'.get_string('wait-listed', 'classroom').'</span></td>'
                                . '<td class="content" style="width: 20%" align="center"><span '.$spanclass.'>'.$options.'</span></td>'
                                . '</tr>';

                }

            }

        }

    }

    print_heading(get_string('upcomingsessions', 'classroom'), 'center');
        echo '<table align="center" cellpadding="3" cellspacing="0" width="90%" style="border-color:#DDDDDD; border-width:1px 1px 1px 1px; border-style:solid;">';
        echo $tableheader;
        if (empty($tableupcoming) and empty($tableupcomingtbd) ) {
            echo '<tr><td colspan="7" align="center">'.get_string('noupcoming', 'classroom').'</td></tr>';
        } else {
            echo $tableupcoming;
            echo $tableupcomingtbd;
        }
        if (has_capability('mod/classroom:editsessions', $context)) {
            echo '<tr><td colspan="7" align="center"><a href="'.$CFG->wwwroot.'/mod/classroom/sessions.php?f='.$classroomid.'" title="'.get_string('addsession', 'classroom').'">'.get_string('addsession', 'classroom').'</a></td></tr>';
        }
        echo '</table>';

    if (! empty($tableprevious)) {
        print_heading(get_string('previoussessions', 'classroom'), 'center');
        echo '<table align="center" cellpadding="3" cellspacing="0" width="90%" style="border-color:#DDDDDD; border-width:1px 1px 1px 1px; border-style:solid;">';
        echo $tableheader;
        echo $tableprevious;
        echo '</table>';
    } 

}
/***********************************************************/
/* Roy Philip updated on 27/4/2012
/***********************************************************/

function classroom_print_sessions($courseid, $classroomid, $location,$view) {
    
    global $CFG, $USER;

    $context = get_context_instance(CONTEXT_COURSE, $courseid, $USER->id);

    $bookedsession = '0';
    $spanclass = '';

    $submissions = classroom_get_user_submissions($classroomid, $USER->id);
    //Naga added for the multiple signups
    $multiplesession=get_record_sql("select multiplesession  from mdl_classroom where id='$classroomid' ");
    //Naga completed
    $bookedsession = null;
    if ($submissions) {
        $submission = array_shift($submissions);
        $bookedsession = $submission->sessionid;
    }

    $timenow = time();

    $tableheader = '';

    if (has_capability('mod/classroom:viewattendees', $context)) {
        $tableheader = '<thead>'
                    . '<tr>'
                    . '<th class="header" align="left">'.get_string('programenamehead', 'classroom').'</th>'
                    . '<th class="header" align="left">'.get_string('location', 'classroom').'</th>'
                    . '<th class="header" align="left">'.get_string('venue', 'classroom').'</th>'
                    . '<th class="header">'.get_string('date', 'classroom').'</th>'
                    . '<th class="header">'.get_string('time', 'classroom').'</th>'             
                    . '<th class="header">'.get_string('capacity', 'classroom').'</th>'                    
                    . '<th class="header">'.get_string('options', 'classroom').'</th>'                              
                    . '</tr>'
                    . '</thead>';
    } else {
        $tableheader = '<thead>'
                    . '<tr>'
                    . '<th class="header" align="left">'.get_string('programenamehead', 'classroom').'</th>'
                    . '<th class="header" align="left">'.get_string('location', 'classroom').'</th>'
                    . '<th class="header" align="left">'.get_string('venue', 'classroom').'</th>'
                    . '<th class="header" >'.get_string('date', 'classroom').'</th>'
                    . '<th class="header" >'.get_string('time', 'classroom').'</th>'                    
                    . '<th class="header">'.get_string('seats available', 'classroom').'</th>'                 
                    . '<th class="header">'.get_string('options', 'classroom').'</th>'
                    . '</tr>'
                    . '</thead>';
    }

    $tableupcoming = '';
    $tableupcomingtbd = '';
    $tableprevious = '';
    
    if ($sessions = classroom_get_view_sessions($classroomid, $location,$view) ) {
    
    foreach($sessions as $session) 
    {

            $options = '';
            $spanclass = '';
        
            $signupcount = classroom_get_num_attendees($session->id);
            
            if($view==0)
            {
                $status  = get_string('bookingopen', 'classroom');
                if ($signupcount >= $session->capacity) 
                {
                    $status = get_string('bookingfull', 'classroom');
                    execute_sql("UPDATE mdl_classroom_sessions SET status = $status WHERE id = $session->id ",false);
                }
            }
        
            if($view==1)
                {
                $status = get_string('sessioncancelled', 'classroom');
                execute_sql("UPDATE mdl_classroom_sessions SET status = $status WHERE id = $session->id ",false);
                }
                
                
            $allsessiondates = '';
            $allsessiontimes = '';
            foreach ($session->sessiondates as $date) {
                if (!empty($allsessiondates)) {
                        $allsessiondates .= '<br />';
                }
                $allsessiondates .= userdate($date->timestart, get_string('strftimedate'));
                if (!empty($allsessiontimes)) {
                    $allsessiontimes .= '<br />';
                }
                $allsessiontimes .= userdate($date->timestart, get_string('strftimetime')).
                    ' - '.userdate($date->timefinish, get_string('strftimetime'));
            }

            // Defaults for normal users
            $stats = $session->capacity - $signupcount;
            $options = '';
    
            $trclass = '';
            $spanclass = '';
$stats = $signupcount.' / '.$session->capacity;
                
                

                if (has_capability('mod/classroom:editsessions', $context)){
                    $statsDisplay = $signupcount.' / '.$session->capacity;
                    if((($signupcount/$session->capacity)*100)<=0)
                   {
                    $stats='<img src="icons\empty.PNG" align="middle" width="150" title="'.$statsDisplay.'" alt="'.$statsDisplay.'" />';
                   }
                   else if((($signupcount/$session->capacity)*100)<=20)
                   {
                    $stats='<img src="icons\red.PNG" align="middle" width="150" title="'.$statsDisplay.'" alt="'.$statsDisplay.'" />';
                   }
                   else if((($signupcount/$session->capacity)*100)<=50)
                   {
                    $stats='<img src="icons\orange.PNG" align="middle" width="150" title="'.$statsDisplay.'" alt="'.$statsDisplay.'" />';
                   }
                   else if((($signupcount/$session->capacity)*100)<100)
                   {
                    $stats='<img src="icons\amber.PNG" align="middle" width="150" title="'.$statsDisplay.'" alt="'.$statsDisplay.'" />';
                   }
                   else if((($signupcount/$session->capacity)*100)>=100)
                   {
                    $stats='<img src="icons\green.PNG" align="middle" width="150" title="'.$statsDisplay.'" alt="'.$statsDisplay.'" />';
                   }
                    
                   
                }
                if (has_capability('mod/classroom:editsessions', $context)) {
                
                if($view==0)
                {
                    $options .= ' <a href="attendees.php?s='.$session->id.'" title="'.get_string('seeattendees', 'classroom').'"><img src="icons\attendees.jpg" align="middle" height="25" width="25" title="Attendees" alt="Attendees" /></a>'
                    . '<a href="sessions.php?s='.$session->id.'&amp;ed=1" title="'.get_string('editsession', 'classroom').'"><img src="icons\reschedule.jpg" align="middle" height="25" width="25" title="Reschedule" alt="Reschedule" /></a>'
                        . '<a href="sessions.php?s='.$session->id.'&amp;ca=1" title="'.get_string('cancelsession', 'classroom').'"><img src="icons\cancel.jpg" align="middle" height="25" width="25" title="Cancel" alt="Cancel" /></a>'
                        . '<a href="sessions.php?s='.$session->id.'&amp;c=1" title="'.get_string('copysession', 'classroom').'"><img src="icons\copy.jpg" align="middle" height="25" width="25" title="Copy" alt="Copy" /></a>'                 
                        . '<a href="sessions.php?s='.$session->id.'&amp;d=1" title="'.get_string('deletesession', 'classroom').'"><img src="icons\delete.jpg" align="middle" height="25" width="25" title="Delete" alt="Delete" /></a>'
                        . '<a href="trainners.php?s='.$session->id.'" title="'.get_string('seetrainers', 'classroom').'"><img src="icons\trainer.jpg" align="middle" height="25" width="25" title="Trainer" alt="Trainer" /></a>';
                }
                if($view==1)
                {
                    $options .= '<a href="sessions.php?s='.$session->id.'&amp;ed=1" title="'.get_string('editsession', 'classroom').'"><img src="icons\reschedule.jpg" align="middle" height="25" width="25" title="Reschedule" alt="Edit" /></a>'
                     .'<a href="sessions.php?s='.$session->id.'&amp;d=1" title="'.get_string('deletesession', 'classroom').'"><img src="icons\delete.jpg" align="middle" height="25" width="25" title="Delete" alt="Delete" /></a>';
                }
                if($view==2)
                {
                    $options .= ' <a href="attendees.php?s='.$session->id.'" title="'.get_string('seeattendees', 'classroom').'"><img src="icons\attendees.jpg" align="middle" height="25" width="25" title="Attendees" alt="Attendees" /></a>'
                    .'<a href="trainners.php?s='.$session->id.'" title="'.get_string('seetrainers', 'classroom').'"><img src="icons\trainer.jpg" align="middle" height="25" width="25" title="Trainer" alt="Trainer" /></a>'
                    . '<a href="sessions.php?s='.$session->id.'&amp;ed=1" title="'.get_string('editsession', 'classroom').'"><img src="icons\reschedule.jpg" align="middle" height="25" width="25" title="Reschedule" alt="Edit" /></a>'
                        . '<a href="sessions.php?s='.$session->id.'&amp;c=1" title="'.get_string('copysession', 'classroom').'"><img src="icons\copy.jpg" align="middle" height="25" width="25" title="Copy" alt="Copy" /></a>'                 
                        . '<a href="sessions.php?s='.$session->id.'&amp;d=1" title="'.get_string('deletesession', 'classroom').'"><img src="icons\delete.jpg" align="middle" height="25" width="25" title="Delete" alt="Delete" /></a>';
                }
                }
               //Naga added for multiple signups
                
                if($multiplesession->multiplesession==1)
                {
                    $session_sub_check=get_record_sql("select sessionid from mdl_classroom_submissions where sessionid='$session->id' and userid='$USER->id' and classroom='$classroomid'");
                    $session_can_check=get_record_sql("select sessionid from mdl_classroom_submissions where sessionid='$session->id' and userid='$USER->id' and classroom='$classroomid' and timecancelled=0");
                    if($session_can_check ){
                        
                    $trclass = ' class="highlight"';
                    $tableupcoming .= '<tr><td class="content" colspan="7"><span style="font-size: 12px; color:#229911; line-height: 12px;"><b>'.get_string('youarebooked', 'classroom').':</b></span></td></tr>';

                    $options .= '<a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'"><img src="icons\info.jpg" align="middle" height="25" width="25" title="More info" alt="More info" /></a>'                        
                        . '<a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;cancelbooking=1&amp;viewall='.$classroomid.'"><img src="icons\usercancel.png" align="middle" height="25" width="25" title="Cancel signup" alt="Cancel" />';
               
                    }
                    else {
                    if ($bookedsession || ($status == get_string('bookingfull', 'classroom')) || ($session->timecancelled) || ($session->status=='Completed')) {
                        $spanclass = ' class="dimmed_text"';
                        $options .= '';
                        //Naga added for multiple signups
                    
                    $options .= '<a href="signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'"><img src="icons\signup.png" align="middle" height="25" width="25" title="Sign up" alt="Sign up" /></a>';
                                        //Naga completed
                    
                    } else {
                            $options .= '<a href="signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'"><img src="icons\signup.png" align="middle" height="25" width="25" title="Sign up" alt="Sign up" /></a>';
                    
                    }
                    
                    
                    }
                }
                //Naga completed
                else
                {
                
                if ($session->id == $bookedsession) {
                
                    $trclass = ' class="highlight"';
                    $tableupcoming .= '<tr><td class="content" colspan="7"><span style="font-size: 12px; color:#229911; line-height: 12px;"><b>'.get_string('youarebooked', 'classroom').':</b></span></td></tr>';

                    $options .= '<a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'"><img src="icons\info.jpg" align="middle" height="25" width="25" title="More info" alt="More info" /></a>'                        
                        . '<a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;cancelbooking=1&amp;viewall='.$classroomid.'"><img src="icons\usercancel.png" align="middle" height="25" width="25" title="Cancel signup" alt="Cancel" />';
                } else {
                    if ($bookedsession || ($status == get_string('bookingfull', 'classroom')) || ($session->timecancelled) || ($session->status=='Completed')) {
                        $spanclass = ' class="dimmed_text"';
                        $options .= '';
                    
                    
                    } else {
                            $options .= '<a href="signup.php?s='.$session->id.'&amp;viewall='.$classroomid.'"><img src="icons\signup.png" align="middle" height="25" width="25" title="Sign up" alt="Sign up" /></a>';
                    
                    }
                    
                    
                    }
                }

                if (empty($options)) {
                    $options = '';
                }

                        
                if ($session->datetimeknown) {

                    $tableupcoming .= '<tr'.$trclass.'>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->programename.'</span></td>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->location.'</span></td>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->room.'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$allsessiondates.'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$allsessiontimes.'</span></td>'
                                
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$stats.'</span></td>'
                                . '<td class="content" style="width: 20%" align="center"><span '.$spanclass.'>'.$options.'</span></td>'
                                . '</tr>';

                } else {

                    $tableupcomingtbd .= '<tr'.$trclass.'>'
                    . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->programename.'</span></td>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->location.'</span></td>'
                                . '<td class="content" style="width: 10%"><span '.$spanclass.'>'.$session->room.'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.get_string('wait-listed', 'classroom').'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.get_string('wait-listed', 'classroom').'</span></td>'
                                . '<td class="content" style="width: 15%" align="center"><span '.$spanclass.'>'.$stats.'</span></td>'                               
                                . '<td class="content" style="width: 20%" align="center"><span '.$spanclass.'>'.$options.'</span></td>'
                                . '</tr>';

                }

            

        
    }

}

        echo '<table align="center" cellpadding="3" cellspacing="0" width="90%" style="border-color:#DDDDDD; border-width:1px 1px 1px 1px; border-style:solid;">';
        if (has_capability('mod/classroom:editsessions', $context)) {
        echo '<div style="text-align: center;vertical-align:middle">
        <a href="'.$CFG->wwwroot.'/mod/classroom/sessions.php?f='.$classroomid.'" title="'.get_string('addsession', 'classroom').'">
        <img src="icons\add.JPG" align="middle" height="25" width="25" title="Add a session" alt="Add a session" /> Add a new session </a>  
        <a href="'.$CFG->wwwroot.'/mod/classroom/sessionsUser.php?f='.$classroomid.'" title="Register training session">
        <img src="icons\add.JPG" align="middle" height="25" width="25" title="Registering a new session" alt="Registering a new session" />Registering a new session</a>
        
        <a href="'.$CFG->wwwroot.'/mod/classroom/sessionsRegister.php?f='.$classroomid.'" title="Register an external event">
        <img src="icons\add.JPG" align="middle" height="25" width="25" title="Register an external event" alt="Register an external event" />Register an external event</a>
        </div>';
        }


        echo $tableheader;
     
           echo $tableupcoming;
            echo $tableupcomingtbd;
        
        
        echo '</table>';
        

}




/**
 * Get all of the dates for a given session
 */
function classroom_get_session_dates($sessionid) {

    $ret = array();

    if ($dates = get_records('classroom_sessions_dates', 'sessionid', $sessionid)) {
        $i = 0;
        foreach ($dates as $date) {
            $ret[$i++] = $date;
        }
    }

    return $ret;
}

function classroom_get_session_trainers($sessionid) {

    $ret = array();

    if ($dates = get_records('classroom_trainners')) {
        $i = 0;
        foreach ($dates as $date) {
            $ret[$i++] = $date;
        }
    }

    return $ret;
}

/**
 * Get a record from the classroom_sessions table
 *
 * @param integer $sessionid ID of the session
 */
function classroom_get_session($sessionid) {

    $session = get_record('classroom_sessions', 'id', $sessionid);

    if ($session) {
        $session->sessiondates = classroom_get_session_dates($sessionid);
        $session->duration = classroom_minutes_to_hours($session->duration);
    }

    return $session;
}

/**
 * Get all records from classroom_sessions for a given classroom activity and location
 *
 * Roy Philip updated on 27/4/2012
 */
 /*******************************************************************************************************/
function classroom_get_sessions($classroomid, $location='') {

    global $CFG;
    if (empty($location)) {
        $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.status <>'Completed' and s.timecancelled=0
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

        $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
    } else {
        $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.status <>'Completed' and s.timecancelled=0
                                            AND s.location='$location'
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

        $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid
                                                   AND s.location='$location'
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
    }

    // Broken sessions are sessions which have no dates associated
    // with them, they are only returned so that they are visible and
    // can be fixed by users.  The cause of these broken sessions
    // should be investigated and a bug should be filed.
    if ($brokensessions) {
        $courseid = get_field('classroom', 'course', 'id', $classroomid);
        add_to_log($courseid, 'classroom', 'broken sessions found', '', "classroomid=$classroomid");
        $sessions = array_merge($sessions, $brokensessions);
    }

    if ($sessions) {
        foreach ($sessions as $key => $value) {
            $sessions[$key]->duration = classroom_minutes_to_hours($sessions[$key]->duration);
            $sessions[$key]->sessiondates = classroom_get_session_dates($value->id);
        }
    }

    return $sessions;
}
/**
 * Get all records from classroom_sessions for a given classroom activity and location
 *
 * Roy Philip updated on 27/4/2012
 */
 /*******************************************************************************************************/
function classroom_get_view_sessions($classroomid, $location='',$view) {

    global $CFG;
    if (empty($location)) {
        if($view==0)
        {
            $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.status <>'Completed' and s.timecancelled=0
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

            $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid and s.status <>'Completed' and s.timecancelled=0
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
        }
        else if($view==1)
        {
            $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.timecancelled>0
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

            $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid and s.timecancelled>0
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
        }
        else if($view==2)
        {
            $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.status='Completed'
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

            $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid and s.status='Completed'
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
        }
    } 
    else 
    {
        if($view==0)
        {
    
            $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.status <>'Completed' and s.timecancelled=0
                                            AND s.location='$location'
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

            $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid and s.status <>'Completed' and s.timecancelled=0
                                                   AND s.location='$location'
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
        }
        if($view==1)
        {
    
            $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.timecancelled>0
                                            AND s.location='$location'
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

            $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid and s.timecancelled>0
                                                   AND s.location='$location'
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
        }
        if($view==2)
        {
    
            $sessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s,
                                        (SELECT sessionid, min(timestart) AS mintimestart
                                            FROM {$CFG->prefix}classroom_sessions_dates GROUP BY sessionid) d
                                        WHERE s.classroom=$classroomid AND d.sessionid = s.id and s.status='Completed'
                                            AND s.location='$location'
                                        ORDER BY s.datetimeknown, d.mintimestart desc");

            $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               WHERE s.classroom=$classroomid and s.status='Completed'
                                                   AND s.location='$location'
                                                   AND NOT EXISTS
                                          (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              WHERE sessionid = s.id)");
        }
    }

    // Broken sessions are sessions which have no dates associated
    // with them, they are only returned so that they are visible and
    // can be fixed by users.  The cause of these broken sessions
    // should be investigated and a bug should be filed.
    if ($brokensessions) {
        $courseid = get_field('classroom', 'course', 'id', $classroomid);
        add_to_log($courseid, 'classroom', 'broken sessions found', '', "classroomid=$classroomid");
        $sessions = array_merge($sessions, $brokensessions);
    }

    if ($sessions) {
        foreach ($sessions as $key => $value) {
            $sessions[$key]->duration = classroom_minutes_to_hours($sessions[$key]->duration);
            $sessions[$key]->sessiondates = classroom_get_session_dates($value->id);
        }
    }

    return $sessions;
}






/**
 * Get a grade for the given user from the gradebook.
 *
 * @param integer $userid        ID of the user
 * @param integer $courseid      ID of the course
 * @param integer $classroomid  ID of the Face-to-face activity
 *
 * @returns object String grade and the time that it was graded
 */
function classroom_get_grade($userid, $courseid, $classroomid) {

    $ret = new object;
    $ret->grade = 0;
    $ret->dategraded = 0;

    $grading_info = grade_get_grades($courseid, 'mod', 'classroom', $classroomid, $userid);
    if (!empty($grading_info->items)) {
        $ret->grade = $grading_info->items[0]->grades[$userid]->str_grade;
        $ret->dategraded = $grading_info->items[0]->grades[$userid]->dategraded;
    }

    return $ret;
}

/**
 * Get list of users attending a given session
 */


 
function classroom_get_attendees($sessionid) {
    global $CFG;

    $records = get_records_sql("SELECT u.id, s.id AS submissionid, u.firstname, u.lastname, u.email,
                                       s.discountcode,s.attend, f.id AS classroomid, f.course, 0 AS grade
                                  FROM {$CFG->prefix}classroom f
                                  JOIN {$CFG->prefix}classroom_submissions s ON s.classroom = f.id
                                  JOIN {$CFG->prefix}user u ON u.id = s.userid
                                 WHERE s.sessionid=$sessionid
                                   AND s.timecancelled = 0
                              ORDER BY u.firstname");

    if (!$records) {
        return $records;
    }

    // Get all grades at once
    $userids = array();
    foreach ($records as $record) {
        $userids[] = $record->id;
    }
    $grading_info = grade_get_grades(reset($records)->course, 'mod', 'classroom',
                                     reset($records)->classroomid, $userids);

    // Update the records
    foreach ($records as $record) {
        $record->grade = $grading_info->items[0]->grades[$record->id]->str_grade;
    }

    return $records;
}

//************ Roy Philip: Function to get the list of trainers for a course****************

function classroom_get_trainners($sessionid) {
    global $CFG;

    $records = get_records_sql("SELECT u.id, s.id AS submissionid,u.username, u.firstname, u.lastname, u.email,
                                       s.discountcode, f.id AS classroomid, f.course, 0 AS grade
                                  FROM {$CFG->prefix}classroom f
                                  JOIN {$CFG->prefix}classroom_trainners s ON s.classroom = f.id
                                  JOIN {$CFG->prefix}user u ON u.id = s.userid
                                 WHERE s.sessionid=$sessionid
                                   AND s.timecancelled = 0
                              ORDER BY u.firstname");

    if (!$records) {
        return $records;
    }

    // Get all grades at once
    $userids = array();
    foreach ($records as $record) {
        $userids[] = $record->id;
    }
    $grading_info = grade_get_grades(reset($records)->course, 'mod', 'classroom',
                                     reset($records)->classroomid, $userids);

    // Update the records
    foreach ($records as $record) {
        $record->grade = $grading_info->items[0]->grades[$record->id]->str_grade;
    }

    return $records;
}
//********** End of function ***************

/**
 * Download the list of users attending at least one of the sessions
 * for a given classroom activity
 */
//Naga added to download attendance
function classroom_download_attendance($classroomname, $classroomid, $location, $view ,$format) {
    global $CFG;

        $timenow = time();
        $timeformat = str_replace(' ', '_', get_string('strftimedate'));
        $downloadfilename = clean_filename($classroomname.'_'.userdate($timenow, $timeformat));
        require_once($CFG->dirroot.'/lib/excellib.class.php');

      //  $export_tracking = $this->track_exports();

        $strgrades = $classroomname.' '.'Attendance';

    /// Calculate file name
     //   $downloadfilename = clean_filename("naga.xls");
      $downloadfilename .= '.xls';
    /// Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
    /// Sending HTTP headers
        $workbook->send($downloadfilename);
    /// Adding the worksheet
        $myxls =& $workbook->add_worksheet($strgrades);

   /// Print names of all the fields
  
        $myxls->write_string(0,0,"Session Name");
        $myxls->write_string(0,1,"Location");
        $myxls->write_string(0,2,"venue");
        $myxls->write_string(0,3,"Room");
        $myxls->write_string(0,4,"Session Start Time");
        $myxls->write_string(0,5,"Session End Time");
        $myxls->write_string(0,6,"Trainer Name");
        $myxls->write_string(0,7,"Employee Portal ID");
        $myxls->write_string(0,8,"Employee FirstName");
        $myxls->write_string(0,9,"Employee LastName");
        $myxls->write_string(0,10,"Employee Status");
        $myxls->write_string(0,11,"Employee Email");
        $myxls->write_string(0,12,"Employee City");
        $myxls->write_string(0,13,"Employee Country");
        $myxls->write_string(0,14,"Cancelled");
        $myxls->write_string(0,15,"Cancel Reason");
        
        

    $i = 0;
    $records=get_records_sql("SELECT m.id AS submissionid,u.id as userid,u.city as city, u.country as country,u.auth as auth,u.username as username,s.id as sessionid ,s.programename as programename,s.location as location,s.venue as venue,s.duration as duration,s.room as room,
                              u.firstname as firstname, u.lastname as lastname, u.email as email,m.timecancelled as timecancelled,m.cancelreasons as cancelreasons,
                               m.discountcode as discountcode ,m.attend as attend
                                        FROM mdl_classroom_submissions m
                                join mdl_user u on u.id=m.userid
                                join mdl_classroom_sessions s on s.id=m.sessionid
                                where s.classroom=$classroomid  and s.status='Open' order by s.id ;");
                

    if (! empty ($records) ) {

        foreach ($records as $session) {
         
             
          
                    
                    $cancel=get_records_sql("select from_unixtime(min(timestart)) as timestart,from_unixtime(max(timefinish)) as timefinish from mdl_classroom_sessions_dates where sessionid=$session->sessionid ");
                    foreach($cancel as $sessiondates)
                    {
                        $starttime   = ($sessiondates->timestart);
                        $finishtime  = ($sessiondates->timefinish);
                    }
                    $trainer=get_record_sql("select  concat(u.firstname,' ',u.lastname) as   username from mdl_user u join mdl_classroom_trainners t on t.sessionid=$session->sessionid and u.id=t.userid");

                           $i++;
                        
                        $myxls->write_string($i,0,$session->programename);
                        $myxls->write_string($i,1,$session->location);
                        $myxls->write_string($i,2,$session->venue);
                        $myxls->write_string($i,3,$session->room);
                        $myxls->write_string($i,4,$starttime);
                        $myxls->write_string($i,5,$finishtime);
                        $myxls->write_string($i,6,$trainer->username);
                        $myxls->write_string($i,7,$session->username);
                        $myxls->write_string($i,8,$session->firstname);
                        $myxls->write_string($i,9,$session->lastname);
                        if ($session->auth=='ldap' || $session->auth=='ldap2')
                        {
                        $session->auth= 'Active';
                        }
                        else
                        {
                        $session->auth='Exited';
                        }
                        $myxls->write_string($i,10,$session->auth);
                        $myxls->write_string($i,11,$session->email);
                        $myxls->write_string($i,12,$session->city);
                        $myxls->write_string($i,13,$session->country);
                        if($session->timecancelled !=0)
                        {
                        $session->timecancelled='Cancelled';
                        }
                        else {
                        $session->timecancelled='';}
                        $myxls->write_string($i,14,$session->timecancelled);
                        $myxls->write_string($i,15,$session->cancelreasons);
                      
                        
                    }
                    

                }


   

    $workbook->close();
    exit;

}
/*Naga added for Export attendees for the open session */
function classroom_get_sessions_open($classroomid) {

    global $CFG;
   
        $sessions = get_records_sql("SELECT s.id,s.programename,s.location,s.venue,s.duration,s.room FROM {$CFG->prefix}classroom_sessions s
                                        
                                        WHERE s.classroom=$classroomid  and s.status='Open'
                                       ");

        // $brokensessions = get_records_sql("SELECT s.* FROM {$CFG->prefix}classroom_sessions s
                                               // WHERE s.classroom=$classroomid and s.status='Open'
                                                   // AND NOT EXISTS
                                          // (SELECT 1 FROM {$CFG->prefix}classroom_sessions_dates
                                              // WHERE sessionid = s.id)");
 

    // // Broken sessions are sessions which have no dates associated
    // // with them, they are only returned so that they are visible and
    // // can be fixed by users.  The cause of these broken sessions
    // // should be investigated and a bug should be filed.
    // if ($brokensessions) {
        // $courseid = get_field('classroom', 'course', 'id', $classroomid);
        // add_to_log($courseid, 'classroom', 'broken sessions found', '', "classroomid=$classroomid");
        // $sessions = array_merge($sessions, $brokensessions);
    // }

    // if ($sessions) {
        // foreach ($sessions as $key => $value) {
            // $sessions[$key]->duration = classroom_minutes_to_hours($sessions[$key]->duration);
            // $sessions[$key]->sessiondates = classroom_get_session_dates($value->id);
        // }
    // }

    return $sessions;
}
/*

Naga completed
*/
//Naga added for export attendenace
function classroom_get_attendees_all($sessionid) {
    global $CFG;

    $records = get_records_sql("SELECT u.id, s.id AS submissionid, u.firstname, u.lastname, u.email,s.timecancelled,s.cancelreasons,
                                       s.discountcode,s.attend
                                  FROM {$CFG->prefix}classroom_submissions s 
                                  JOIN {$CFG->prefix}user u ON u.id = s.userid
                                 WHERE s.sessionid=$sessionid
                                  
                              ");

  
    return $records;
}
//naga done


function classroom_submission_attendance($session, $userid) {

$record=get_record_sql("SELECT max(attend) as taken FROM mdl_classroom_submissions
        where sessionid=$session->id and userid=$userid");

if($record->taken==1)
    {           
        return 'Completed';         
    }
    else if($session->timecancelled > 0)
    {
        return 'Cancelled';
    }
    else
    {
        return 'Not Completed';
    }
}

/**
 * Return an array of all of the locations where the given classroom
 * activity has sessions
 */
function classroom_get_locations($classroomid) { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT DISTINCT location, id, venue
                                         FROM {$CFG->prefix}classroom_sessions
                                         WHERE classroom = $classroomid
                                         ORDER BY location")) {

        $i=1;
        $locationmenu[''] = get_string('alllocations', 'classroom');
        foreach ($sessions as $session) {
            $f = $session->id;
            $locationmenu[$session->location] = $session->location;
            $i++;
        }

        return $locationmenu;

    } else {
        
        return '';

    }
}

//***********RoyPhilip:Getting feedback for classroom sessions ************************************
function session_get_feedback($classroomid) { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT name from {$CFG->prefix}feedback where 
                                    course=1")) {

        $i=1;
        $feedbackmenu[''] = 'Select feedback';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $feedbackmenu[$session->name] = $session->name;
            $i++;
        }

        return $feedbackmenu;

    } else {
        
        return '';

    }
}

//***********RoyPhilip:Getting location for classroom sessions ************************************
function session_get_location() { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT content FROM {$CFG->prefix}data_content where fieldid=4;")) 
                                    
                                    {

        $i=1;
        $typemenu[''] = 'Select Location';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->content] = $session->content;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
}
//***********RoyPhilip:Getting venue for classroom sessions ************************************
function session_get_venue() { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT content FROM {$CFG->prefix}data_content where fieldid=2;")) 
                                    
                                    {

        $i=1;
        $typemenu[''] = 'Select Venue';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->content] = $session->content;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
}
//***********SnehaFlora:Getting venue for selected location classroom sessions ************************************
function session_get_venue_forLocation($getLocation) { 
    global $CFG; 
    $str=$_GET["str"];
    
    if ($sessions = get_records_sql("SELECT distinct b.content FROM mdl_data_content a
                                        join mdl_data_content b on a.recordid=b.recordid
                                        and b.fieldid=2 where a.fieldid=3 and a.content='".$getLocation."';")){
                
        $i=1;
        $typemenu[''] = 'Select Venue';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->content] = $session->content;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
    
}

//***********RoyPhilip:Getting room for classroom sessions ************************************
function session_get_room() { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT content FROM {$CFG->prefix}data_content where fieldid=1;")) 
                                    
                                    {

        $i=1;
        $typemenu[''] = 'Select Room';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->content] = $session->content;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
}
//***********SnehaFlora:Getting room for selected venue in sessions ************************************
function session_get_room_forVenue($getVenue) { 
    global $CFG; 
    if ($sessions = get_records_sql("SELECT distinct b.content FROM mdl_data_content a join mdl_data_content b on 
                                     a.recordid=b.recordid and b.fieldid=1 where a.fieldid=2 and a.content='".$getVenue."';")) 
{
        $i=1;
        $typemenu[''] = 'Select Room';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->content] = $session->content;
            $i++;
        }
        return $typemenu;
    } else {
          return '';
    }
}
//************** Getting sessioncategory for classroom sessions ****************************
function session_get_category() { 
    global $CFG; 
    if ($sessions = get_records_sql("select distinct(sessioncategory) from {$CFG->prefix}classroom_detailsmaster")) 
        {
        $i=1;
        $typemenu[''] = 'Select Category';
        foreach ($sessions as $session) {
            $typemenu[$session->sessioncategory] = $session->sessioncategory;
            $i++;
        }
        return $typemenu;

    } else {
        
        return '';

    }
}

//***********RoyPhilip:Getting trainingtype for classroom sessions ************************************
function session_get_trainingtype() { 
    global $CFG; 
    if ($sessions = get_records_sql("select distinct(trainingtype) from 
                                    {$CFG->prefix}classroom_detailsmaster ")) 
                                    
                                    {

        $i=1;
        $typemenu[''] = 'Select Trainingtype';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $typemenu[$session->trainingtype] = $session->trainingtype;
            $i++;
        }

        return $typemenu;

    } else {
        
        return '';

    }
}
//***********RoyPhilip:Function to get training source ************************************
function session_get_trainingsource() { 
    global $CFG; 
    if ($sessions = get_records_sql("select distinct(trainingsource) from 
                                    mdl_classroom_detailsmaster")) 
                                    
                                    {

        $i=1;
        $sourcemenu[''] = 'Select Trainingsource';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $sourcemenu[$session->trainingsource] = $session->trainingsource;
            $i++;
        
}
        return $sourcemenu;

    } else {

        
        return '';

    }
}
//***********RoyPhilip:Getting account details for classroom sessions ************************************
function session_get_account() { 
    global $CFG; 
    if ($sessions = get_records_sql("select distinct(account) from {$CFG->prefix}classroom_detailsmaster ")) 
        {
        $i=1;
        $practisemenu[''] = 'Select account';
        foreach ($sessions as $session) {
           // $f = $session->id;
            $practisemenu[$session->account] = $session->account;
            $i++;
        }
        return $practisemenu;

    } else {
        
        return '';

    }
}
/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 */
function classroom_get_unmailed_reminders() {

    global $CFG;

    $submissions = get_records_sql("SELECT su.*, f.course, f.id as classroomid, f.name as classroomname,
                                           f.reminderperiod, se.duration, se.normalcost, se.discountcost,
                                           se.location, se.venue, se.room, se.details, se.datetimeknown
                                       FROM {$CFG->prefix}classroom_submissions su,
                                            {$CFG->prefix}classroom_sessions se,
                                            {$CFG->prefix}classroom f,
                                            {$CFG->prefix}course c
                                       WHERE su.mailedreminder = 0 AND se.datetimeknown=1 AND se.timecancelled = 0 AND
                                             f.course=c.id AND su.sessionid=se.id AND
                                             se.classroom=f.id AND f.id=su.classroom AND
                                             su.timecancelled = 0");

    if ($submissions) {
        foreach ($submissions as $key => $value) {
            $submissions[$key]->duration = classroom_minutes_to_hours($submissions[$key]->duration);
            $submissions[$key]->sessiondates = classroom_get_session_dates($value->sessionid);
        }
    }

    return $submissions;
}

/**
 * Add a record to the classroom submissions table and sends out an
 * email confirmation
 *
 * @param class $session record from the classroom_sessions table
 * @param class $classroom record from the classroom table
 * @param class $course record from the course table
 * @param string $discountcode code entered by the user
 * @param integer $notificationtype type of notifications to send to user
 * @see {{MDL_F2F_INVITE}}
 * @param integer $userid user to signup
 * @param bool $notifyuser whether or not to send an email confirmation
 * @param bool $displayerrors whether or not to return an error page on errors
 */
function classroom_user_signup($session, $classroom, $course, $discountcode,
                                $notificationtype, $userid=false,
                                $notifyuser=true, $displayerrors=true) {
    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }
    
    $return = false;
    $timenow = time();

    $usersignup = new stdclass;
    $usersignup->sessionid = $session->id;
    $usersignup->userid = $userid;
    $usersignup->classroom = $session->classroom;
    $usersignup->timecreated = $timenow;
    $usersignup->timemodified = $timenow;
    $usersignup->discountcode = trim(strtoupper($discountcode));
    if (empty($usersignup->discountcode)) {
        $usersignup->discountcode = null;
    }    

    $usersignup->notificationtype = $notificationtype;

    begin_sql();    
    if ($returnid = insert_record('classroom_submissions', $usersignup)) {

        $return = $returnid;

        if (!$notifyuser or classroom_has_session_started($session, $timenow)) {
            // If the session has already started, there's no need to notify the user
            commit_sql();
            return $return;
        }
        else {
            $error = classroom_send_confirmation_notice($classroom, $session, $userid, $notificationtype);
            if (empty($error)) {
                $usersignup->id = $returnid;
                $usersignup->mailedconfirmation = $timenow;

                if (update_record('classroom_submissions', $usersignup)) {
                    commit_sql();
                    return $return;
                }
            }
            elseif ($displayerrors) {
                error($error);
            }
        }
    

    rollback_sql();
    return false;
    }
 
}

/** RoyPhilip: Code for adding trainner to the session activity **/

function classroom_trainner_signup($session, $classroom, $course, $discountcode,
                                $notificationtype, $userid=false,
                                $notifyuser=true, $displayerrors=true) {
    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }

    $return = false;
    $timenow = time();

    $usersignup = new stdclass;
    $usersignup->sessionid = $session->id;
    $usersignup->userid = $userid;
    $usersignup->classroom = $session->classroom;
    $usersignup->timecreated = $timenow;
    $usersignup->timemodified = $timenow;
    $usersignup->discountcode = trim(strtoupper($discountcode));
    if (empty($usersignup->discountcode)) {
        $usersignup->discountcode = null;
    }

    $usersignup->notificationtype = $notificationtype;

    begin_sql();
    if ($returnid = insert_record('classroom_trainners', $usersignup)) {

        $return = $returnid;

        if (!$notifyuser or classroom_has_session_started($session, $timenow)) {
            // If the session has already started, there's no need to notify the user
            commit_sql();
            return $return;
        }
        else {
            $error = classroom_send_trainerconfirmation_notice($classroom, $session, $userid, $notificationtype);
            if (empty($error)) {
                $usersignup->id = $returnid;
                $usersignup->mailedconfirmation = $timenow;

                if (update_record('classroom_trainners', $usersignup)) {
                    commit_sql();
                    return $return;
                }
            }
            elseif ($displayerrors) {
                error($error);
            }
        }
    }

    rollback_sql();
    return false;
}
//********* End of Function **********************


//********* RoyPhilip: Adding manager by employee *************
/** RoyPhilip: Code for adding trainner to the session activity **/

function classroom_manager_add($session, $classroom, $user, $userid,$displayerrors=true) 
{

    $Employeeid = $USER->id;
  

    $return = false;
    $timenow = time();

    $usersignup = new stdclass;
    $usersignup->classroom = $classroom;
    $usersignup->sessionid = $session->id;  
    $usersignup->employeeid = $user;
    $usersignup->managerid = $userid;   
    $usersignup->timecreated = $timenow;
  

    begin_sql();
    if ($returnid = insert_record('classroom_managers', $usersignup)) {

        $return = true;

        
    }

    rollback_sql();
    return $return;
}
/**
 * Cancel a user who signed up earlier
 *
 * @param class $session record from the classroom_sessions table
 * @param integer $userid ID of the user to remove from the session
 */
function classroom_user_cancel($session,$cancelreasons,$userid=false) {

    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }   
    $record=get_record_sql("SELECT max(attend) as taken FROM mdl_classroom_submissions
        where sessionid=$session->id and userid=$userid");

    if($record->taken==1)
    {           
        return false;           
    }
    else
    {
        return classroom_user_cancel_submission($session->id,$cancelreasons,$userid);
    }
}

//***** Roy Philip:Code to cancel a trainer who signed up earlier **********

function classroom_trainner_cancel($session, $userid=false) {

    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }

    return classroom_trainner_cancel_submission($session->id, $userid);
}

/**
 * Common code for sending confirmation and cancellation notices
 *
 * @param string $postsubject Subject of the email
 * @param string $posttext Plain text contents of the email
 * @param string $posttextmgrheading Header to prepend to $posttext in manager email
 * @param string $notificationtype The type of notification to send
 * @see {{MDL_F2F_INVITE}}
 * @param class $classroom record from the classroom table
 * @param class $session record from the classroom_sessions table
 * @param integer $userid ID of the recipient of the email
 * @returns string Error message (or empty string if successful)
 */
//srinu added to send feed back notice
function classroom_send_tef_notice($postsubject, $posttext, $posttextmgrheading,
                                $notificationtype, $classroom, $session, $userid) {
    global $CFG;

    $user = get_record('user', 'id', $userid);
    if (!$user) {
        return get_string('error:invaliduserid', 'classroom');
    }

    if (empty($postsubject) || empty($posttext)) {
        return '';
    }


    //srinu added for training effectiveness feedback form
    $erge=get_record_sql("select programename,externaltraining from mdl_classroom_sessions where id='$session->id'");
    if($erge->externaltraining=='1')
    {
                //$addusers = get_record('user', 'id', $adduser, '','','','', 'id, firstname, lastname,username');
$addusers = get_record('user', 'id', $userid, '','','','', 'id, firstname, lastname,username,country');
$fullname = fullname($addusers, true);
$portalid = $addusers->username;
$subject1='You have been nominated to attend the training';
$sub=$erge->programename;
$fromaddress = 'Training Helpdesk';
$subject= $subject1.'   '.$sub;
                   $dear='Hi';
                   $name=$fullname.' PortalID '.$portalid.",";
                    $message="<br>$subject.<br>As part of the process of measuring the effectiveness of training, we have developed a training effectiveness form. This form need to be filled before the training start on below link..<br>
                    $CFG->wwwroot/teff/feedback1.php?s=$session->id&u=$userid";

    $erolmessage= '<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40"><head><META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=us-ascii"><meta name=Generator content="Microsoft Word 14 (filtered medium)"><style><!--
/* Font Definitions */
@font-face
    {font-family:Calibri;
    panose-1:2 15 5 2 2 2 4 3 2 4;}
/* Style Definitions */
p.MsoNormal, li.MsoNormal, div.MsoNormal
    {margin:0in;
    margin-bottom:.0001pt;
    font-size:11.0pt;
    font-family:"Calibri","sans-serif";}
a:link, span.MsoHyperlink
    {mso-style-priority:99;
    color:blue;
    text-decoration:underline;}
a:visited, span.MsoHyperlinkFollowed
    {mso-style-priority:99;
    color:purple;
    text-decoration:underline;}
span.EmailStyle17
    {mso-style-type:personal-compose;
    font-family:"Calibri","sans-serif";
    color:windowtext;}
.MsoChpDefault
    {mso-style-type:export-only;
    font-family:"Calibri","sans-serif";}
    font-family:"Calibri","sans-serif";}
@page WordSection1
    {size:8.5in 11.0in;
    margin:1.0in 1.0in 1.0in 1.0in;}
div.WordSection1
    {page:WordSection1;}
--></style><!--[if gte mso 9]><xml>

<o:shapedefaults v:ext="edit" spidmax="1026" />
</xml><![endif]--><!--[if gte mso 9]><xml>
<o:shapelayout v:ext="edit">
<o:idmap v:ext="edit" data="1" />
</o:shapelayout></xml><![endif]--></head><body lang=EN-US link=blue vlink=purple><div class=WordSection1><p class=MsoNormal>'.$dear.'    '.$name.'                                                                           '.$message.'<o:p></o:p></p></div></body></html>';
$touser = get_record('user','id',$addusers->id);  // to give touser mail address

email_to_user( $touser,$fromaddress ,trim($subject), $erolmessage,$erolmessage);    
}
}
//srinu ended to send feed back notice
 
 
function classroom_send_notice($postsubject, $posttext, $posttextmgrheading,
                                $notificationtype, $classroom, $session, $userid) {
    global $CFG;

    $user = get_record('user', 'id', $userid);
    if (!$user) {
        return get_string('error:invaliduserid', 'classroom');
    }

    if (empty($postsubject) || empty($posttext)) {
        return '';
    }


    // If no notice type is defined (TEXT or ICAL)
    if (!($notificationtype & MDL_F2F_BOTH)) {
        // If none, make sure they at least get a text email
        $notificationtype |= MDL_F2F_TEXT;
    }

    // If we are cancelling, check if ical cancellations are disabled
    if (($notificationtype & MDL_F2F_CANCEL) &&
        get_config(NULL, 'classroom_disableicalcancel')) {
        $notificationtype |= MDL_F2F_TEXT; // add a text notification
        $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    }

    // If we are sending an ical attachment, set file name
    if ($notificationtype & MDL_F2F_ICAL) {
        if ($notificationtype & MDL_F2F_INVITE) {
            $attachmentfilename = 'invite.ics';
        }
        elseif ($notificationtype & MDL_F2F_CANCEL) {
            $attachmentfilename = 'cancel.ics';
        }

    }

    // Do iCal attachement stuff
    $icalattachments = array();
    if ($notificationtype & MDL_F2F_ICAL) {
        if (get_config(NULL, 'classroom_oneemailperday')) {
            // Keep track of all sessiondates
            $sessiondates = $session->sessiondates;

            foreach ($sessiondates as $sessiondate) {
                $session->sessiondates = array($sessiondate); // one day at a time

                $filename = classroom_get_ical_attachment($notificationtype, $classroom, $session, $user);
                $subject = classroom_email_substitutions($postsubject, $session->programename, $classroom->reminderperiod,
                                                          $user, $session, $session->id);
                $body = classroom_email_substitutions($posttext, $session->programename, $classroom->reminderperiod,
                                                       $user, $session, $session->id);
                $htmlbody = ''; // TODO
                $icalattachments[] = array('filename' => $filename, 'subject' => $subject,
                                           'body' => $body, 'htmlbody' => $htmlbody);
            }

            // Restore session dates
            $session->sessiondates = $sessiondates;
        }
        else {
            $filename = classroom_get_ical_attachment($notificationtype, $classroom, $session, $user);
            $subject = classroom_email_substitutions($postsubject, $session->programename, $classroom->reminderperiod,
                                                      $user, $session, $session->id);
            $body = classroom_email_substitutions($posttext, $session->programename, $classroom->reminderperiod,
                                                   $user, $session, $session->id);
            $htmlbody = ''; // TODO
            $icalattachments[] = array('filename' => $filename, 'subject' => $subject,
                                       'body' => $body, 'htmlbody' => $htmlbody);
        }
    }

    // Fill-in the email placeholders
    $postsubject = classroom_email_substitutions($postsubject, $session->programename, $classroom->reminderperiod,
                                                  $user, $session, $session->id);
    $posttext = classroom_email_substitutions($posttext, $session->programename, $classroom->reminderperiod,
                                               $user, $session, $session->id);

    if (!empty($posttextmgrheading)) {
        $posttextmgrheading = classroom_email_substitutions($posttextmgrheading, $session->programename,
                                                             $classroom->reminderperiod, $user, $session,
                                                             $session->id);
    }

    $posthtml = ''; // TODO: provide an HTML version of these notices
    $fromaddress = get_config(NULL, 'classroom_fromaddress');
    if (!$fromaddress) {
        $fromaddress = '';
    }

    $usercheck = get_record('user', 'id', $userid);

    // Send email with iCal attachment
    if ($notificationtype & MDL_F2F_ICAL) {
        foreach ($icalattachments as $attachment) {
            if (!email_to_user($user, $fromaddress, $attachment['subject'], $attachment['body'],
                               $attachment['htmlbody'], $attachment['filename'], $attachmentfilename)) {

                return get_string('error:cannotsendconfirmationuser', 'classroom');
            }
            unlink($CFG->dataroot . '/' . $attachment['filename']);
        }
    }

    // Send plain text email
    if ($notificationtype & MDL_F2F_TEXT) {
        if (!email_to_user($user, $fromaddress, $postsubject, $posttext, $posthtml)) {
            return get_string('error:cannotsendconfirmationuser', 'classroom');
        }
    }

    // Manager notification
    /*$manageremail = classroom_get_manageremail($userid);
    if (!empty($posttextmgrheading) and !empty($manageremail) and $session->datetimeknown) {
        if($session->timecancelled==0)
        {
        $managertext = $posttextmgrheading.$posttext;
        }
        else
        {
        $managertext = $posttext;
        }
        
        $manager = $user;
        $manager->email = $manageremail;

        // Leave out the ical attachments in the managers notification\
        if($classroom->reminderinstrmngr !="")
        {
        if (!email_to_user($manager, $fromaddress, $postsubject, $managertext, $posthtml)) {
            return get_string('error:cannotsendconfirmationmanager', 'classroom');
        }
        }
    }

    // Third-party notification
    if (!empty($classroom->thirdparty) &&
        ($session->datetimeknown || !empty($classroom->thirdpartywaitlist))) {

        $thirdparty = $user;
        $thirdparty->email = $classroom->thirdparty;

        // Leave out the ical attachments in the 3rd parties notification
        if (!email_to_user($thirdparty, $fromaddress, $postsubject, $posttext, $posthtml)) {
            return get_string('error:cannotsendconfirmationthirdparty', 'classroom');
        }
    }*/
    
}

/*Processing messages like feedback*/


function classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                $notificationtype, $classroom, $session, $userid) {
    global $CFG;

    $user = get_record('user', 'id', $userid);
    if (!$user) {
        return get_string('error:invaliduserid', 'classroom');
    }

    if (empty($postsubject) || empty($posttext)) {
        return '';
    }


    // If no notice type is defined (TEXT or ICAL)
    if (!($notificationtype & MDL_F2F_BOTH)) {
        // If none, make sure they at least get a text email
        $notificationtype |= MDL_F2F_TEXT;
    }


    // Fill-in the email placeholders
    $postsubject = classroom_email_substitutions($postsubject, $session->programename, $classroom->reminderperiod,
                                                  $user, $session, $session->id);
    $posttext = classroom_email_substitutions($posttext, $session->programename, $classroom->reminderperiod,
                                               $user, $session, $session->id);

    if (!empty($posttextmgrheading)) {
        $posttextmgrheading = classroom_email_substitutions($posttextmgrheading, $session->programename,
                                                             $classroom->reminderperiod, $user, $session,
                                                             $session->id);
    }

    $posthtml = ''; // TODO: provide an HTML version of these notices
    $fromaddress = get_config(NULL, 'classroom_fromaddress');
    if (!$fromaddress) {
        $fromaddress = '';
    }

    $usercheck = get_record('user', 'id', $userid);



    // Send plain text email
    if ($notificationtype & MDL_F2F_TEXT) {
        if (!email_to_user($user, $fromaddress, $postsubject, $posttext, $posthtml)) {
            return get_string('error:cannotsendconfirmationuser', 'classroom');
        }
    }
  
}

/**
 * Send a confirmation email to the user and manager
 *
 * @param class $classroom record from the classroom table
 * @param class $session record from the classroom_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
 * @returns string Error message (or empty string if successful)
 */
function classroom_send_confirmation_notice($classroom, $session, $userid, $notificationtype) {

    $posttextmgrheading = $classroom->confirmationinstrmngr;
    if ($session->datetimeknown) {
        $postsubject = $classroom->confirmationsubject;
        $posttext = $classroom->confirmationmessage;
    } else {
        $postsubject = $classroom->waitlistedsubject;
        $posttext = $classroom->waitlistedmessage;

        // Don't send an iCal attachement when we don't know the date!
        $notificationtype |= MDL_F2F_TEXT; // add a text notification
        $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    }

    // Set invite bit
    $notificationtype |= MDL_F2F_INVITE;
    
    classroom_send_tef_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
    return classroom_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

//********************************* RoyPhilip:Trainer Confirmation Message ************************
function classroom_send_trainerconfirmation_notice($classroom, $session, $userid, $notificationtype) {

    $posttextmgrheading = $classroom->confirmationinstrmngr;
    if ($session->datetimeknown) {
        $postsubject = $classroom->confirmationsubject;
        $posttext = $classroom->trainerconfirmationmessage;
    } else {
        $postsubject = $classroom->waitlistedsubject;
        $posttext = $classroom->waitlistedmessage;

        // Don't send an iCal attachement when we don't know the date!
        $notificationtype |= MDL_F2F_TEXT; // add a text notification
        $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    }

    // Set invite bit
    $notificationtype |= MDL_F2F_INVITE;

    return classroom_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

//************************************RoyPhilip:Event cancell Message **********************************

function classroom_send_cancelevent_notice($classroom, $session, $userid) {

    $posttextmgrheading = $classroom->cancelprogrammessage;

        $postsubject = $classroom->cancelprogram;
        $posttext = $classroom->cancelprogrammessage;

      

        // Don't send an iCal attachement when we don't know the date!
        $notificationtype |= MDL_F2F_TEXT; // add a text notification
     //   $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    

    // Set invite bit
    $notificationtype |= MDL_F2F_INVITE;

    return classroom_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

function classroom_reschedule_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomrescheduled', 'classroom');

        $postsubject = get_string('classroomrescheduled', 'classroom');
        $posttext = get_string('setting:defaultrescheduledmessage', 'classroom');

      

        // Don't send an iCal attachement when we don't know the date!
      //  $notificationtype |= MDL_F2F_TEXT; // add a text notification
     //   $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    

    // Set invite bit
    $notificationtype |= MDL_F2F_INVITE;
    $notificationtype |= MDL_F2F_ICAL;

    


    return classroom_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}
//Send a mail asking the employee to enter feedback.
function classroom_feedback_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomfeedback', 'classroom');

        $postsubject = get_string('classroomfeedback', 'classroom');
        $posttext = get_string('setting:defaultfeedbackmessage', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}
//Mail sent to help desk when a new session is registered
function classroom_registerilt_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomexternalevent', 'classroom');

        $postsubject = get_string('classroomexternalevent', 'classroom');
        $posttext = get_string('setting:defaultregisteriltmessage', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

function classroom_iltevent_user_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomregisteriltuser', 'classroom');

        $postsubject = get_string('classroomregisteriltuser', 'classroom');
        $posttext = get_string('setting:defaultregisteriltmessageUser', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}
//Mail sent to user manager when a new session is registered
function classroom_registerilt_managernotice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomregistermanagerilt', 'classroom');

        $postsubject = get_string('classroomregistermanagerilt', 'classroom');
        $posttext = get_string('setting:defaultregisteriltmanagermessage', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}
//Mail sent to help desk when a external event is registered
function classroom_externalevent_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomexternalevent', 'classroom');

        $postsubject = get_string('classroomexternalevent', 'classroom');
        $posttext = get_string('setting:defaultexternaleventtmessage', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

function classroom_externalevent_user_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomexternaleventUser', 'classroom');

        $postsubject = get_string('classroomexternaleventUser', 'classroom');
        $posttext = get_string('setting:defaultexternaleventtmessageuser', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

function classroom_externalevent_user_attendence_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomexternaleventattendence', 'classroom');

        $postsubject = get_string('classroomexternalevent', 'classroom');
        $posttext = get_string('setting:defaultexternaleventtmessageuserattendence', 'classroom');

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}


//RoyPhilip:Send absentees mail added on 24/4/2012 
function classroom_absentees_notice($classroom, $session, $userid) {

    $posttextmgrheading = get_string('classroomabsentees', 'classroom');

        $postsubject = get_string('classroomabsentees', 'classroom');
        $posttext = $classroom->absenteesmessage;

    $notificationtype |= MDL_F2F_TEXT;

    
    return classroom_send_user_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}
/**
 * Send a confirmation email to the user and manager regarding the
 * cancellation
 *
 * @param class $classroom record from the classroom table
 * @param class $session record from the classroom_sessions table
 * @param integer $userid ID of the recipient of the email
 * @returns string Error message (or empty string if successful)
 */
function classroom_send_cancellation_notice($classroom, $session, $userid) {

    $postsubject = $classroom->cancellationsubject;
    $posttext = $classroom->cancellationmessage;
    $posttextmgrheading = $classroom->cancellationinstrmngr;

    // Lookup what type of notification to send
    $notificationtype = get_field('classroom_submissions', 'notificationtype',
                                  'sessionid', $session->id, 'userid', $userid);

    // Set cancellation bit
    $notificationtype |= MDL_F2F_CANCEL;

    return classroom_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $classroom, $session, $userid);
}

/**
 * Returns true if the user has registered for a session in the given
 * classroom activity
 *
 * @global class $USER used to get the current userid
 */
function classroom_check_signup($classroomid) {

    global $USER;

    if ($submissions = classroom_get_user_submissions($classroomid, $USER->id)) {
        return true;
    } else {
        return false;
    }
}

function classroom_session_check_signup($sessionid) {

    global $USER;

    if ($submissions = classroom_session_get_user_submissions($sessionid, $USER->id)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Return the email address of the user's manager if it is
 * defined. Otherwise return an empty string.
 *
 * @param integer $userid User ID of the staff member
 */
 
 
 function classroom_get_manageremail($userid) {
    $fieldid = get_field('user_info_field', 'id', 'shortname', MDL_MANAGERSEMAIL_FIELD);
    if ($fieldid) {
        return get_field('user_info_data', 'data', 'userid', $userid, 'fieldid', $fieldid);
    }
    else {
        return ''; // No custom field => no manager's email
    }
}
 
 /*
 //************ RoyPhilip
function classroom_get_manageremail($session,$user) {
   
    if ($session) {
    $sql_tibi="select firstname from mdl_user u
            join mdl_classroom_managers m on m.managerid=u.id
            and m.sessionid=$session and m.employeeid=$user";
    $rstibi=get_records_sql($sql_tibi);
    foreach ($rstibi as $manager) {
            $fullname = $manager->firstname;

        }
    return $fullname;
    }
    else {
        return ''; // No custom field => no manager's email
    }
}
*/



/**
 * Human-readable version of the format of the manager's email address
 */
function classroom_get_manageremailformat() {

    $addressformat = get_config(NULL, 'classroom_manageraddressformat');

    if (!empty($addressformat)) {
        $readableformat = get_config(NULL, 'classroom_manageraddressformatreadable');
        return get_string('manageremailformat', 'classroom', $readableformat);
    }

    return '';
}

/**
 * Returns true if the given email address follows the format
 * prescribed by the site administrator
 *
 * @param string $manageremail email address as entered by the user
 */
function classroom_check_manageremail($manageremail) {

    $addressformat = get_config(NULL, 'classroom_manageraddressformat');

    if (empty($addressformat) || strpos($manageremail, $addressformat)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Set the user's manager email address using a custom field, creating
 * the custom field if it did not exist already.
 *
 * @global class $USER used to get the current userid
 */
 
 function classroom_set_manageremail($manageremail) {

    global $USER;

    begin_sql();

    if (!$fieldid = get_field('user_info_field', 'id', 'shortname', MDL_MANAGERSEMAIL_FIELD)) {
        // Create the custom field

        $categoryname = clean_param(get_string('modulename', 'facetoface'), PARAM_TEXT);
        if (!$categoryid = get_field('user_info_category', 'id', 'name', $categoryname)) {
            $category = new object();
            $category->name = $categoryname;
            $category->sortorder = 1;

            if (!$categoryid = insert_record('user_info_category', $category)) {
                rollback_sql();
                error_log('F2F: could not create new custom field category');
                return false;
            }
        }

        $record = new stdclass();
        $record->datatype = 'text';
        $record->categoryid = $categoryid;
        $record->shortname = MDL_MANAGERSEMAIL_FIELD;
        $record->name = clean_param(get_string('manageremail', 'facetoface'), PARAM_TEXT);

        if (!$fieldid = insert_record('user_info_field', $record)) {
            rollback_sql();
            error_log('F2F: could not create new custom field');
            return false;
        }
    }

    $data = new stdclass();
    $data->userid = $USER->id;
    $data->fieldid = $fieldid;
    $data->data = $manageremail;

    if ($dataid = get_field('user_info_data', 'id', 'userid', $USER->id, 'fieldid', $fieldid)) {
        $data->id = $dataid;
        if (!update_record('user_info_data', $data)) {
            error_log('F2F: could not update existing custom field data');
            rollback_sql();
            return false;
        }
    }
    else {
        if (!insert_record('user_info_data', $data)) {
            rollback_sql();
            error_log('F2F: could not insert new custom field data');
            return false;
        }
    }

    commit_sql();
    return true;
}
 
 
 /*
function classroom_set_manageremail($manageremail) {

    global $USER;

    begin_sql();

    if (!$fieldid = get_field('user_info_field', 'id', 'shortname', MDL_MANAGERSEMAIL_FIELD)) {
        // Create the custom field

        $categoryname = clean_param(get_string('modulename', 'classroom'), PARAM_TEXT);
        if (!$categoryid = get_field('user_info_category', 'id', 'name', $categoryname)) {
            $category = new object();
            $category->name = $categoryname;
            $category->sortorder = 1;

            if (!$categoryid = insert_record('user_info_category', $category)) {
                rollback_sql();
                error_log('F2F: could not create new custom field category');
                return false;
            }
        }

        $record = new stdclass();
        $record->datatype = 'text';
        $record->categoryid = $categoryid;
        $record->shortname = MDL_MANAGERSEMAIL_FIELD;
        $record->name = clean_param(get_string('manageremail', 'classroom'), PARAM_TEXT);

        if (!$fieldid = insert_record('user_info_field', $record)) {
            rollback_sql();
            error_log('F2F: could not create new custom field');
            return false;
        }
    }

    $data = new stdclass();
    $data->userid = $USER->id;
    $data->fieldid = $fieldid;
    $data->data = $manageremail;

    if ($dataid = get_field('user_info_data', 'id', 'userid', $USER->id, 'fieldid', $fieldid)) {
        $data->id = $dataid;
        if (!update_record('user_info_data', $data)) {
            error_log('F2F: could not update existing custom field data');
            rollback_sql();
            return false;
        }
    }
    else {
        if (!insert_record('user_info_data', $data)) {
            rollback_sql();
            error_log('F2F: could not insert new custom field data');
            return false;
        }
    }

    commit_sql();
    return true;
}

*/
/**
 * Mark the fact that the user attended the classroom session by
 * giving that user a grade of 100
 *
 * @param array $data array containing the sessionid under the 's' key
 *                    and every submission ID to mark as attended
 *                    under the 'submissionid_XXXX' keys where XXXX is
 *                    the ID of the signup
 */
/* Status is set to completed once the attendence is marked
*RoyPhilip:Updated on 24/4/2012 */
function classroom_take_attendance($data,$classroom,$session) {
    $sessionid = $data->s;
    $submission = new object;
    $sesid=session_id();
    
    $timenow = time();
    
    $classroom_id=$classroom->id;
    $classroom_sesid=$session->id;
    execute_sql("INSERT INTO mdl_sendmail_temp(sessionid, classroom_id, classroom_sesid,reg_date) values('$sesid','$classroom_id','$classroom_sesid','$timenow')",false);
    $previousattendees = classroom_get_attendees($sessionid);
    
    // Record the selected attendees from the user interface - the other attendees will need their grades set
    // to zero, to indicate non attendance, but only the ticked attendees come through from the web interface.
    // Hence the need for a diff
    $selectedsubmissionids = array();
    
    foreach ($data as $key => $value) {
        $submissionidcheck = substr($key, 0, 13);
        if ($submissionidcheck == 'submissionid_') {
            $submissionid = substr($key, 13);
            $selectedsubmissionids[$submissionid]=$submissionid;

        
            if (!classroom_take_individual_attendance($classroom,$session,$submissionid, true)) {
                error_log("Classroom: could not mark '$submissionid' as attended");
                return false;
            }
    else{

    }
        }
    }
    
    foreach ($previousattendees as $attendee) {
        $submissionid=$attendee->submissionid;
        if (!array_key_exists($submissionid, $selectedsubmissionids)) {
            if (!classroom_take_individual_attendance($classroom,$session,$submissionid, false)) {
                error_log("F2F: could not mark '$submissionid' as non-attended");
                return false;
            }
        }
    }
    execute_sql("UPDATE mdl_classroom_sessions SET status = 'Completed' WHERE id = $session->id ",false);
    return true;
}

/*
 * Set the grading for an individual submission, to either 0 or 100 to indicate attendance
 * @param $submissionid The id of the submission inthe database
 * @param $didattend Set to true to indicate that a user did attend, and false to indicate
 *                   a lack of attendance.  This sets the grade to 100 or 0 respectively
 *RoyPhilip:Updated 24/12/2012
 */
function classroom_take_individual_attendance($classroom,$session,$submissionid,$didattend) {
    global $USER, $CFG;
    
    $timenow = time();
    
    // Indicate attendance by setting the grading to 0 (did not attend) or 100 (did attend)


    $record = get_record_sql("SELECT f.*, s.userid,s.sessionid,s.mailedfeedback
                                FROM {$CFG->prefix}classroom_submissions s
                                JOIN {$CFG->prefix}classroom f ON f.id = s.classroom
                                JOIN {$CFG->prefix}course_modules cm ON cm.instance = f.id
                                JOIN {$CFG->prefix}modules m ON m.id = cm.module
                               WHERE s.id = $submissionid AND m.name='classroom'");

    
        $grade = new stdclass();
        $grade->userid = $record->userid;
        $grade->rawgrademin = 0;
        $grade->rawgrademax = 100;
        $grade->timecreated = $timenow;
        $grade->timemodified = $timenow;
        $grade->usermodified = $USER->id;
        
    if ($didattend) 
    {
        $grade->rawgrade = 100;
        $attend=1;

        $execrecord= execute_sql("
            UPDATE
                {$CFG->prefix}classroom_submissions
            SET
                attend = $attend
            WHERE
                userid = $record->userid
                and id = $submissionid
                AND timecancelled = 0
            ",false);
//Naga added to update the duration in single table
            $returnpoint=0;
             if (duration_issued($record->course,$record->sessionid,$record->userid))
             {
            
            $itemmodule="classroom_sessions";
            $returnpoint=duration_issue_classroom($record->course,$record->sessionid,$itemmodule,$record->userid);  
                    
            }
            else{
            $returnpoint= duration_update_classroom($record->course,$record->sessionid,$record->userid);
            }
            
            //Naga completed
        //Send feedback mail only if the user has not received the notice
        /*if(($session->feedbackname !='') && ($record->mailedfeedback ==0))
        {
            $error = classroom_feedback_notice($classroom, $session, $record->userid);
            if(!$error)
                {       
                $execrecord= execute_sql("
                UPDATE
                    {$CFG->prefix}classroom_submissions
                SET
                    mailedfeedback = $timenow
                WHERE
                    userid = $record->userid
            ",false);
                }               
        }
        else if($session->trainingsource=='External')
        {
            $error = classroom_externalevent_user_attendence_notice($classroom, $session, $record->userid);
        }*/
        return classroom_grade_item_update($record, $grade);
    }
    else
    {

        $grade->rawgrade = 0;
        $attend=0;
        $execrecord= execute_sql("
            UPDATE
                {$CFG->prefix}classroom_submissions
            SET
                attend = $attend
            WHERE
                userid = $record->userid
                and id = $submissionid
                AND timecancelled = 0
            ",false);
            
            $graderecord = get_record_sql("SELECT max(attend) as attend FROM {$CFG->prefix}classroom_submissions
            where userid=$record->userid and classroom=$record->id and sessionid !=$record->sessionid group by userid");
            //echo $graderecord->attend;
            if($graderecord->attend<1)
            {
            classroom_grade_item_update($record, $grade);
            //Naga added for duration arch changes
            
            $itemmodule="classroom_sessions";
            $returnpoint=duration_revert_classroom($record->course,$record->sessionid,$itemmodule,$record->userid);
            //Naga done
            //Send absentees mail only if the user has not received the notice
            /*if($record->mailedabsentees == 0 || $record->mailedabsentees == null)
            {
            $error = classroom_absentees_notice($classroom, $session, $record->userid);
            if(!$error)
                {       
                $execrecord= execute_sql("
                UPDATE
                    {$CFG->prefix}classroom_submissions
                SET
                    mailedabsentees = $timenow
                WHERE
                    userid = $record->userid
                    and id = $submissionid
            ",false);
                }               
            }*/
            return true;
            }
            else
            {
            return true;
            }

    }    
//***********************************************************************************************************//
}

/**
 * Used by course/lib.php to display a few sessions besides the
 * classroom activity on the course page
 *
 * @global class $USER used to get the current userid
 */
function classroom_print_coursemodule_info($coursemodule) {
    global $CFG, $USER;

    $info = NULL;
    $table = '';

    $timenow = time();
    $classroomid = $coursemodule->instance;

    if ($classroom = get_record('classroom', 'id', $classroomid)) {

        $context = get_context_instance(CONTEXT_MODULE, $coursemodule->id);
        if (has_capability('mod/classroom:view', $context)) {

            if ($submissions = classroom_get_user_submissions($classroomid, $USER->id)) {
                // User has signedup for the instance

                $submission = array_shift($submissions);

                if ($session = classroom_get_session($submission->sessionid)) {

                    if ($session->datetimeknown) {

                        $sessiondate = '';
                        $sessiontime = '';
                        foreach ($session->sessiondates as $date) {
                            if (!empty($sessiondate)) {
                                $sessiondate .= '<br />';
                            }
                            $sessiondate .= userdate($date->timestart, get_string('strftimedate'));
                            if (!empty($sessiontime)) {
                                $sessiontime .= '<br />';
                            }
                            $sessiontime .= userdate($date->timestart, get_string('strftimetime')).
                                ' - '.userdate($date->timefinish, get_string('strftimetime'));
                        }

                    } else {

                        $sessiondate = get_string('wait-listed', 'classroom');
                        $sessiontime = get_string('wait-listed', 'classroom');

                    }

                            $feedbackviewid = get_record_sql("SELECT m.id FROM {$CFG->prefix}course_modules m
                                        join {$CFG->prefix}feedback f where f.id=m.instance 
                                        and m.module=22 and f.name=(select feedbackname from 
                                        mdl_classroom_sessions where id=$session->id)");

//************************************                  
        //If feedback exists
        
        /******* Roy Philip:View feedback code ***************/
            
                  $record=get_record_sql("SELECT max(attend) as taken FROM mdl_classroom_submissions
        where sessionid=$session->id and userid=$USER->id");

        
                             if($record->taken != 1)
                            {
                                 $table = '<table border="0" cellpadding="1" cellspacing="0" width="90%">'
                            .'<tr>'
                            .'<td colspan="4"><span style="font-size: 11px; font-weight: bold; line-height: 14px;">'.get_string('bookingstatus', 'classroom').':</span></td>'
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;">'.get_string('options', 'classroom').':</span></td>'
                            .'</tr>'
                            .'<tr>'
                            .'<td>'.$session->location.'</td>' 
                            .'<td>'.$session->room.'</td>' 
                            .'<td>'.$sessiondate.'</td>' 
                            .'<td>'.$sessiontime.'</td>'
                            .'<td><table border="0"><tr><td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'" alt="'.get_string('moreinfo', 'classroom').'" title="'.get_string('moreinfo', 'classroom').'">'.get_string('moreinfo', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'<tr>'
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/attendees.php?s='.$session->id.'" alt="'.get_string('seeattendees', 'classroom').'" title="'.get_string('seeattendees', 'classroom').'">'.get_string('seeattendees', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'<tr>'
    
        
               
                                //$options .= '<a href="http://10.252.5.95/KeaneLMS/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid='.$session->id.'" title="'.get_string('givefeedbacksession', 'classroom').'">'.get_string('givefeedback', 'classroom').'</a> ';
                        //  .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid='.$session->id.'" title="'.get_string('givefeedbacksession', 'classroom').'">'.get_string('givefeedback', 'classroom').'</a> </span></td>'                           
                        
                            
                            //Cancel a user session
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;cancelbooking=1" alt="'.get_string('cancelbooking', 'classroom').'" title="'.get_string('cancelbooking', 'classroom').'">'.get_string('cancelbooking', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'<tr>'
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px"><a href="'.$CFG->wwwroot.'/mod/classroom/view.php?f='.$classroomid.'" alt="'.get_string('viewallsessions', 'classroom').'" title="'.get_string('viewallsessions', 'classroom').'">'.get_string('viewallsessions', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'</table></td></tr>'
                            .'</table>';
                            }
                            else if ($record->taken== 1)
                            {
                                $feedbackviewid = get_record_sql("SELECT m.id FROM {$CFG->prefix}course_modules m
                                        join {$CFG->prefix}feedback f where f.id=m.instance 
                                        and m.module=22 and f.name=(select feedbackname from 
                                        mdl_classroom_sessions where id=$session->id)");
                                        
                                 $table = '<table border="0" cellpadding="1" cellspacing="0" width="90%">'
                            .'<tr>'
                            .'<td colspan="4"><span style="font-size: 11px; font-weight: bold; line-height: 14px;">'.get_string('bookingstatus', 'classroom').':</span></td>'
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;">'.get_string('options', 'classroom').':</span></td>'
                            .'</tr>'
                            .'<tr>'
                            .'<td>'.$session->location.'</td>' 
                            .'<td>'.$session->room.'</td>' 
                            .'<td>'.$sessiondate.'</td>' 
                            .'<td>'.$sessiontime.'</td>'
                            .'<td><table border="0"><tr><td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'" alt="'.get_string('moreinfo', 'classroom').'" title="'.get_string('moreinfo', 'classroom').'">'.get_string('moreinfo', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'<tr>'
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/attendees.php?s='.$session->id.'" alt="'.get_string('seeattendees', 'classroom').'" title="'.get_string('seeattendees', 'classroom').'">'.get_string('seeattendees', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'<tr>'
    
        
               
                                //$options .= '<a href="http://10.252.5.95/KeaneLMS/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid='.$session->id.'" title="'.get_string('givefeedbacksession', 'classroom').'">'.get_string('givefeedback', 'classroom').'</a> ';
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/feedback/view.php?id='.$feedbackviewid->id.'&classid='.$session->id.'" title="'.get_string('givefeedbacksession', 'classroom').'">'.get_string('givefeedback', 'classroom').'</a> </span></td>'                           
        
                            
                            //Cancel a user session
                           // .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'&amp;cancelbooking=1" alt="'.get_string('cancelbooking', 'classroom').'" title="'.get_string('cancelbooking', 'classroom').'">'.get_string('cancelbooking', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'<tr>'
                            .'<td><span style="font-size: 11px; font-weight: bold; line-height: 14px"><a href="'.$CFG->wwwroot.'/mod/classroom/view.php?f='.$classroomid.'" alt="'.get_string('viewallsessions', 'classroom').'" title="'.get_string('viewallsessions', 'classroom').'">'.get_string('viewallsessions', 'classroom').'</a></span></td>'
                            .'</tr>'
                            .'</table></td></tr>'
                            .'</table>';
                            
                            }
                    
        
        
        
        
        
        
        
        
        
        
//**************************************
        
                   


                }

            } elseif ($sessions = classroom_get_sessions($classroomid)) {

                if ($classroom->display == 0) {

                    $table .= '<table border="0" cellpadding="1" cellspacing="0" width="100%">'
                            .'   <tr>'
                            .'       <td colspan="2"><span style="font-size: 11px; font-weight: bold; line-height: 14px;">'.get_string('signupforsession', 'classroom').':</span></td>'
                            .'   </tr>';
                    $table .= '   <tr>'
                            .'     <td colspan="2"><span style="font-size: 11px; font-weight: bold; line-height: 14px"><a href="'.$CFG->wwwroot.'/mod/classroom/view.php?f='.$classroomid.'" alt="'.get_string('viewallsessions', 'classroom').'" title="'.get_string('viewallsessions', 'classroom').'">'.get_string('viewallsessions', 'classroom').'</a></span></td>'
                            .'   </tr>'
                            .'</table>';

                } else {

                    $table = '<table border="0" cellpadding="1" cellspacing="0" width="100%">'
                            .'   <tr>'
                            .'       <td colspan="2"><span style="font-size: 11px; font-weight: bold; line-height: 14px;">'.get_string('signupforsession', 'classroom').':</span></td>'
                            .'   </tr>';

                    $i=0;

                    foreach($sessions as $session) {

                        if ($session->datetimeknown && (classroom_has_session_ended($session, $timenow))) {
                            continue;
                         }

                        $signupcount = classroom_get_num_attendees($session->id);
                        if ($signupcount >= $session->capacity) continue;

                        $multiday = '';
                        if ($session->datetimeknown) {

                            if (empty($session->sessiondates)) {
                                $sessiondate = get_string('unknowndate', 'classroom');
                                $sessiontime = get_string('unknowntime', 'classroom');
                            } else {
                                $sessiondate = userdate($session->sessiondates[0]->timestart, get_string('strftimedate'));
                                $sessiontime = userdate($session->sessiondates[0]->timestart, get_string('strftimetime')).
                                    ' - '.userdate($session->sessiondates[0]->timefinish, get_string('strftimetime'));
                                if (count($session->sessiondates) > 1) {
                                    $multiday = ' ('.get_string('multiday', 'classroom').')';
                                }
                            }

                        } else {

                            $sessiondate = get_string('wait-listed', 'classroom');
                            $sessiontime = "";

                        }

                        if ($i == 0) {
                            $table .= '   <tr>';
                            $i++;
                        } else if ($i++ % 2 == 0) {
                            if ($i > $classroom->display) {
                                break;
                            }
                            $table .= '   </tr>';
                            $table .= '   <tr>';
                        }
                        $table .= '      <td><span style="font-size: 11px; line-height: 14px;"><a href="'.$CFG->wwwroot.'/mod/classroom/signup.php?s='.$session->id.'">'.$session->location.', '.$sessiondate.'<br />'.$sessiontime.$multiday.'</a></span></td>';
                    }
                    if ($i++ % 2 == 0) {
                        $table .= '<td><span style="font-size: 11px; line-height: 14px;">&nbsp;</span></td>';
                    }
                    $table .= '   </tr>'
                        .'   <tr>'
                        .'     <td colspan="2"><span style="font-size: 11px; font-weight: bold; line-height: 14px"><a href="'.$CFG->wwwroot.'/mod/classroom/view.php?f='.$classroomid.'" alt="'.get_string('viewallsessions', 'classroom').'" title="'.get_string('viewallsessions', 'classroom').'">'.get_string('viewallsessions', 'classroom').'</a></span></td>'
                        .'   </tr>'
                        .'</table>';
                }

            } else {

                if (has_capability('mod/classroom:viewemptyactivities', $context)) {

                    $strdimmed = '';

                    if (!$coursemodule->visible) {
                        $strdimmed = ' class="dimmed"';
                    }

                    $table = '<img src="'.$CFG->wwwroot.'/mod/classroom/icon.gif" class="activityicon" alt="'.get_string('classroom', 'classroom').'" /> <a title="'.$classroom->name.'"'.$strdimmed.' href="'.$CFG->wwwroot.'/mod/classroom/view.php?f='.$classroomid.'">'.$classroom->name.'</a>';

                }

            }

        }

    } 

    return $table;
}

/**
 * Returns the ICAL data for a classroom meeting.
 *
 * @param integer $method The method, @see {{MDL_F2F_INVITE}}
 * @return string Filename of the attachment in the temp directory
 */
function classroom_get_ical_attachment($method, $classroom, $session, $user) {
    global $CFG;

    // First, generate all the VEVENT blocks
    $VEVENTS = '';
    foreach ($session->sessiondates as $date) {
        // Date that this representation of the calendar information was created - 
        // we use the time the session was created
        // http://www.kanzaki.com/docs/ical/dtstamp.html
        $DTSTAMP = classroom_ical_generate_timestamp($session->timecreated);

        // UIDs should be globally unique
        $urlbits = parse_url($CFG->wwwroot);
        $UID =
            $DTSTAMP .
            '-' . substr(md5($CFG->siteidentifier . $session->id . $date->id), -8) .   // Unique identifier, salted with site identifier
            '@' . $urlbits['host'];                                                    // Hostname for this moodle installation

        $DTSTART = classroom_ical_generate_timestamp($date->timestart);
        $DTEND   = classroom_ical_generate_timestamp($date->timefinish);

        // TODO: currently we are not sending updates if the times of the 
        // sesion are changed. This is not ideal!
        $SEQUENCE = ($method & MDL_F2F_CANCEL) ? 1 : 0;

        // TODO: escape these: must wrap at 75 octets and some characters must 
        // be backslash escaped
        $SUMMARY     = classroom_ical_escape($session->programename);
    //  $DESCRIPTION = $input = trim( preg_replace( '/\s+/', ' ', $session->details ) );  
        //$DESCRIPTION = classroom_ical_escape($session->details);
        
        //Naga Added to fix the calendar issue
        
        $session->detailsid   = str_replace('\n', '\n ', classroom_ical_escape("{$session->details}\n"));
        $DESCRIPTION = $input = ( preg_replace( '/\s/', ' ', $session->detailsid ) );  

        // NOTE: Newlines are meant to be encoded with the literal sequence 
        // '\n'. But evolution presents a single line text field for location, 
        // and shows the newlines as [0x0A] junk. So we switch it for commas 
        // here. Remember commas need to be escaped too.
        $LOCATION    = str_replace('\n', '\, ', classroom_ical_escape("{$session->room}\n{$session->venue}\n{$session->location}"));

        $ORGANISEREMAIL = get_config(NULL, 'classroom_fromaddress');

        $ROLE = 'REQ-PARTICIPANT';
        $CANCELSTATUS = '';
        if ($method & MDL_F2F_CANCEL) {
            $ROLE = 'NON-PARTICIPANT';
            $CANCELSTATUS = "\nSTATUS:CANCELLED";
        }

        $icalmethod = ($method & MDL_F2F_INVITE) ? 'REQUEST' : 'CANCEL';

        // TODO: if the user has input their name in another language, we need 
        // to set the LANGUAGE property parameter here
        $USERNAME = fullname($user);
        $MAILTO   = $user->email;

        // The extra newline at the bottom is so multiple events start on their 
        // own lines. The very last one is trimmed outside the loop
        $VEVENTS .= <<<EOF
BEGIN:VEVENT
UID:{$UID}
DTSTAMP:{$DTSTAMP}
DTSTART:{$DTSTART}
DTEND:{$DTEND}
SEQUENCE:{$SEQUENCE}
SUMMARY:{$SUMMARY}
LOCATION:{$LOCATION}
DESCRIPTION:{$DESCRIPTION}
CLASS:PRIVATE
TRANSP:OPAQUE{$CANCELSTATUS}
ORGANIZER;CN={$ORGANISEREMAIL}:MAILTO:{$ORGANISEREMAIL}
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE={$ROLE};PARTSTAT=NEEDS-ACTION;
 RSVP=FALSE;CN={$USERNAME};LANGUAGE=en:MAILTO:{$MAILTO}
END:VEVENT

EOF;
    }

    $VEVENTS = trim($VEVENTS);

    $template = <<<EOF
BEGIN:VCALENDAR
CALSCALE:GREGORIAN
PRODID:-//Moodle//NONSGML classroom//EN
VERSION:2.0
METHOD:{$icalmethod}
BEGIN:VTIMEZONE
TZID:/softwarestudio.org/Tzfile/Pacific/Auckland
X-LIC-LOCATION:Pacific/Auckland
BEGIN:STANDARD
TZNAME:NZST
DTSTART:19700405T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=4
TZOFFSETFROM:+1300
TZOFFSETTO:+1200
END:STANDARD
BEGIN:DAYLIGHT
TZNAME:NZDT
DTSTART:19700928T030000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=9
TZOFFSETFROM:+1200
TZOFFSETTO:+1300
END:DAYLIGHT
END:VTIMEZONE
{$VEVENTS}
END:VCALENDAR
EOF;

    $tempfilename = md5($template);
    $tempfilepathname = $CFG->dataroot . '/' . $tempfilename;
    file_put_contents($tempfilepathname, $template);
    return $tempfilename;
}

function classroom_ical_generate_timestamp($timestamp) {
    return gmdate('Ymd', $timestamp) . 'T' . gmdate('His', $timestamp) . 'Z';
}

/**
 * Escapes data of the text datatype in ICAL documents.
 *
 * See RFC2445 or http://www.kanzaki.com/docs/ical/text.html or a more readable definition
 */
function classroom_ical_escape($text) {
    $text = str_replace(
        array('\\',   "\n", ';',  ','),
        array('\\\\', '\n', '\;', '\,'),
        $text
    );

    // Text should be wordwrapped at 75 octets, and there should be one 
    // whitespace after the newline that does the wrapping
    $text = wordwrap($text, 75, "\n ", true);

    return $text;
}

/**
 * Update grades by firing grade_updated event
 *
 * @param object $classroom null means all classroom activities
 * @param int $userid specific user only, 0 mean all (not used here)
 */
function classroom_update_grades($classroom=null, $userid=0) {

    if ($classroom != null) {
            classroom_grade_item_update($classroom);
    }
    else {
        $sql = "SELECT f.*, cm.idnumber as cmidnumber
                  FROM {$CFG->prefix}classroom f
                  JOIN {$CFG->prefix}course_modules cm ON cm.instance = f.id
                  JOIN {$CFG->prefix}modules m ON m.id = cm.module
                 WHERE m.name='classroom'";
        if ($rs = get_recordset_sql($sql)) {
            while ($classroom = rs_fetch_next_record($rs)) {
                classroom_grade_item_update($classroom);
            }
            rs_close($rs);
        }
    }
}

/**
 * Create grade item for given Face-to-face session
 *
 * @param int classroom  Face-to-face activity (not the session) to grade
 * @param mixed grades    grades objects or 'reset' (means reset grades in gradebook)
 * @return int 0 if ok, error code otherwise
 */
function classroom_grade_item_update($classroom, $grades=NULL) {
    global $CFG;

    if (!isset($classroom->cmidnumber)) {

        $sql = "SELECT cm.idnumber as cmidnumber
                  FROM {$CFG->prefix}course_modules cm
                  JOIN {$CFG->prefix}modules m ON m.id = cm.module
                 WHERE m.name='classroom' AND cm.instance = $classroom->id";
        $classroom->cmidnumber = get_field_sql($sql);
    }

    $params = array('itemname'=>$classroom->name,
                    'idnumber'=>$classroom->cmidnumber);

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademin']  = 0;
    $params['gradepass'] = 100;
    $params['grademax']  = 100;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    $retcode = grade_update('mod/classroom', $classroom->course, 'mod', 'classroom',
                            $classroom->id, 0, $grades, $params);
    return ($retcode === GRADE_UPDATE_OK);
}

/**
 * Delete grade item for given classroom
 *
 * @param object $classroom object
 * @return object classroom
 */
function classroom_grade_item_delete($classroom) {
    $retcode = grade_update('mod/classroom', $classroom->course, 'mod', 'classroom',
                            $classroom->id, 0, NULL, array('deleted'=>1));
    return ($retcode === GRADE_UPDATE_OK);
}

/**
 * Return number of attendees signed up to a classroom session
 *
 * @param integer $session_id
 * @return integer
 */
function classroom_get_num_attendees($session_id) {
    return (int) count_records_sql("SELECT count(distinct userid) FROM mdl_classroom_submissions where sessionid=$session_id and timecancelled=0");
}

/**
 * Return all of a users' submissions to a classroom
 *
 * @param integer $classroomid
 * @param integer $userid
 * @param boolean $includecancellations
 * @return array submissions | false No submissions
 */
function classroom_get_user_submissions($classroomid, $userid, $includecancellations=false) {
    global $CFG;

    $whereclause = "classroom=$classroomid AND userid=$userid";
    if (!$includecancellations) {
        $whereclause .= ' AND timecancelled=0';
    }

    return get_records_sql("SELECT *
                              FROM {$CFG->prefix}classroom_submissions
                             WHERE $whereclause
                          ORDER BY timecreated desc");
}

function classroom_session_get_user_submissions($sessionid, $userid, $includecancellations=false) {
    global $CFG;

    $whereclause = "sessionid=$sessionid AND userid=$userid";
    if (!$includecancellations) {
        $whereclause .= ' AND timecancelled=0';
    }

    return get_records_sql("SELECT *
                              FROM {$CFG->prefix}classroom_submissions
                             WHERE $whereclause
                          ORDER BY timecreated");
}

function check_available_seats($sessionid) {
    global $CFG;
    
      $signupcount = classroom_get_num_attendees($session->id);

                    if ($signupcount >= $session->capacity) {
                    //return true;
                    error(get_string('bookingfull', 'classroom'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
                        }                       
                        else{
                        $confirm=true;
                        }
                    
    
    
    }
/*function classroom_admin_cancel($session_id,$cancelreasons,$user_id,$didcancel){
    global $CFG;*/
    
    


/**
 * Cancel users' submission to a classroom session
 *
 * @param integer $session_id
 * @param integer $user_id
 * @return boolean success
 */
function classroom_user_cancel_submission($session_id,$cancelreasons,$user_id) {
    global $CFG;
    $timenow = time();
    
    
    return execute_sql("
            UPDATE
                {$CFG->prefix}classroom_submissions
            SET
                timecancelled = $timenow,
                timemodified = $timenow,
                cancelreasons = '$cancelreasons'
                
            WHERE
                sessionid = $session_id
                AND userid = $user_id
                AND timecancelled = 0
            ",
            false);
}

//*************** Roy Philip : Cancel a session after booking ***************************
function classroom_session_cancel($s,$classroom,$cancelreason) {
    global $CFG;
    $timenow = time();
    $error="";
     execute_sql("
            UPDATE
                {$CFG->prefix}classroom_sessions
            SET
                timecancelled = $timenow,
                timemodified = $timenow,
                cancelreason = '$cancelreason',
                status = 'Cancelled'
          
            WHERE
                id = $s            
            ",false);
            $session = classroom_get_session($s);
            $userrecords = get_records_sql("SELECT userid FROM mdl_classroom_submissions where sessionid=$s and timecancelled=0");
            foreach ($userrecords as $sessionuser) {
         $error = classroom_send_cancelevent_notice($classroom, $session, $sessionuser->userid);
                                                    }

            $userrecordsT = get_records_sql("SELECT userid FROM mdl_classroom_trainners where sessionid=$s and timecancelled=0");
            foreach ($userrecordsT as $sessionuserT) {
         $error = classroom_send_cancelevent_notice($classroom, $session, $sessionuserT->userid);
                                                        }
    if (empty($error)) {
             return true;
            }
            else {
                return false;
            }


                    
            
}
//*************** end of function ****************************************************

/**
 * Cancel trainer submission to a classroom session
 *
 * @param integer $session_id
 * @param integer $user_id
 * @return boolean success
 */
function classroom_trainner_cancel_submission($session_id, $user_id) {
    global $CFG;
    $timenow = time();
    return execute_sql("
            UPDATE
                {$CFG->prefix}classroom_trainners
            SET
                timecancelled = $timenow,
                timemodified = $timenow
            WHERE
                sessionid = $session_id
                AND userid = $user_id
                AND timecancelled = 0
            ",
            false);
            
        
}

//********** End of Function

/**
 * A list of actions in the logs that indicate view activity for participants
 */
function classroom_get_view_actions() {
    return array('view', 'view all');
}

/**
 * A list of actions in the logs that indicate post activity for participants
 */
function classroom_get_post_actions() {
    return array('cancel booking', 'signup');
}

/**
 * Get list of users that signed up then cancelled a classroom session
 *
 * @param integer $session_id
 * @return array users
 */
function classroom_get_cancellations($session_id) {
    global $CFG;

    $records = get_records_sql("
        SELECT
            s.id AS submissionid,
            u.id,
            u.firstname,
            u.lastname,
            u.email,
            s.timecreated,
            s.timecancelled,
            s.cancelreasons
        FROM
            {$CFG->prefix}classroom_submissions s
        JOIN
            {$CFG->prefix}user u ON u.id = s.userid
        WHERE
            s.sessionid = $session_id
            AND s.timecancelled > 0
        ORDER BY
            u.lastname,
            u.firstname,
            s.timecancelled
    ");

    return $records;
}

/**
 * Get list of trainers that signed up then cancelled a classroom session
 *
 * @param integer $session_id
 * @return array users
 */
 
 function classroom_get_trainer_cancellations($session_id) {
    global $CFG;

    $records = get_records_sql("
        SELECT
            t.id AS trainerid,
            u.id,
            u.firstname,
            u.lastname,
            u.email,
            t.timecreated,
            t.timecancelled         
        FROM
            {$CFG->prefix}classroom_trainners  t
        JOIN
            {$CFG->prefix}user u ON u.id = s.userid
        WHERE
            t.sessionid = $session_id
            AND t.timecancelled > 0
        ORDER BY
            u.lastname,
            u.firstname,
            t.timecancelled
    ");

    return $records;
}

/**
 * Get number of users that signed up then cancelled a classroom session
 *
 * @param integer $session_id
 * @return interger
 */
function classroom_get_num_cancellations($session_id) {
    return (int) count_records_select('classroom_submissions', "sessionid = $session_id AND timecancelled >  0");
}

/**
 * Return a small object with summary information about what a user
 * has done with a given particular instance of this module (for user
 * activity reports.)
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 */
function classroom_user_outline($course, $user, $mod, $classroom) {

    $result = new stdClass;

    $grade = classroom_get_grade($user->id, $course->id, $classroom->id);
    if ($grade->grade > 0) {
        $result = new stdClass;
        $result->info = get_string('grade') . ': ' . $grade->grade;
        $result->time = $grade->dategraded;
    }
    elseif ($submissions = classroom_get_user_submissions($classroom->id, $user->id)) {
        $result->info = get_string('usersignedup', 'classroom');
        $result->time = reset($submissions)->timecreated;
    }
    else {
        $result->info = get_string('usernotsignedup', 'classroom');
    }

    return $result;
}

/**
 * Print a detailed representation of what a user has done with a
 * given particular instance of this module (for user activity
 * reports).
 */
function classroom_user_complete($course, $user, $mod, $classroom) {

    $grade = classroom_get_grade($user->id, $course->id, $classroom->id);

    if ($submissions = classroom_get_user_submissions($classroom->id, $user->id, true)) {
        print get_string('grade').': '.$grade->grade . '<br />';
        if ($grade->dategraded > 0) {
            $timegraded = trim(userdate($grade->dategraded, get_string('strftimedatetime')));
            print '('.format_string($timegraded).')<br />';
        }
        print '<br />';

        foreach ($submissions as $submission) {
            $timesignedup = trim(userdate($submission->timecreated, get_string('strftimedatetime')));
            print get_string('usersignedupon', 'classroom', format_string($timesignedup)) . '<br />';

            if ($submission->timecancelled > 0) {
                $timecancelled = userdate($submission->timecancelled, get_string('strftimedatetime'));
                print get_string('usercancelledon', 'classroom', format_string($timecancelled)) . '<br />';
            }
        }
    }
    else {
        print get_string('usernotsignedup', 'classroom');
    }

    return true;
}

?>
