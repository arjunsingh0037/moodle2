<?php

	function point_count_user($userid)
	{
	$points = get_record_sql("select sum(point) as counts from mdl_game_point where userid=$userid");
	if($points->counts > 0)
		{
			return $points->counts;
		}
		else
		{
			return 0;
		}	
	}
	function point_count_useryear($userid,$sd,$ed)
	{
	if($points = get_record_sql("select sum(point) as counts from mdl_game_point where userid=$userid and timeissued>unix_timestamp('$sd') and timeissued<unix_timestamp('$ed')"))
		{
			return $points->counts;
		}
		else
		{
			return 0;
		}	
	}
	function point_user_recent($userid)
	{
		if($points = get_records_sql("select p.id,from_unixtime(timeissued, '%M %D %Y') as date,c.shortname,point,comments from mdl_game_point p join mdl_course c on p.course=c.id and p.userid=$userid order by p.id desc limit 3"))
		{
			return $points;
		}
		else
		{
			return false;
		}	
	}
	
	

	function point_issue($course,$moduleid,$type,$userid,&$message='') 
	{

		if (point_issued($course,$moduleid,$userid)>0)
		{
		return point_update($course,$moduleid,$type,$userid,$message);
		}
		else
		{
		return point_insert($course,$moduleid,$type,$userid,&$message);
		}
	}

    function point_issued($course,$module,$userid) {
        global $CFG;		
		$point=get_record_sql("SELECT sum(point) as points FROM mdl_game_point where course=$course 
		and module=$module and userid =$userid");						
        return $point->points;
    }
	
	function point_insert($course,$moduleid,$type,$userid,&$message='') {
	
		$comment='';
		if (!$userid) {
        global $USER;
        $userid = $USER->id;
		}
	
		if (!$module = get_record($type, 'id', $moduleid)) 
		{
			return false;
		}
		if($message=='')
		{
		$comment=point_comments($module->name,$module->point);
		}
		else
		{
		$comment=$message;
		}
	$return = false;
    $timenow = time();

    $point = new stdclass;
    $point->course = $course;
    $point->module = $moduleid;
    $point->type = $type;
    $point->userid = $userid;
    $point->point = $module->point;
    $point->issuer = $userid;
	$point->comments = $comment;
	$point->timeissued = $timenow;
	$point->timemodified = $timenow;
$message=$point->comments;

		begin_sql();	
		if ($returnid = insert_record('game_point', $point)) 
		{
			commit_sql();
            return $point->point;
		}
		else
		{
			rollback_sql();
			return false;
		}
	}
	
	function point_update($courseid,$moduleid,$type,$userid,$message='') {
	$timenow = time();
	
		if (!$pointrecord = get_record('game_point', 'course', $courseid,'module',$moduleid,'type',$type,'userid',$userid)) 
		{
			return false;
		}
		if (!$module = get_record($type, 'id', $moduleid)) 
		{
			return false;
		}
    $pointrecord->id = $pointrecord->id;
	$pointrecord->point=$module->point;
	$pointrecord->timemodified=$timenow;

		begin_sql();
		if ($returnid = update_record('game_point', $pointrecord)) 
		{
		commit_sql();
		return $returnid;
		}
		else
		{
		rollback_sql();
		return false;
		}
	}
	

	function point_delete($id) {

		global $CFG;

		if (!$point = get_record('game_point', 'id', $id)) 
		{
			return false;
		}   
			begin_sql();
		if (!delete_records('game_point', 'id', $id)) {
			rollback_sql();
			return false;
		}
		else
		{
			commit_sql();
			return true;
		}

	}
		function point_comments($modulename,$points)
		{
		return 'You have been awarded +'.$points.' points for viewing/completing '.$modulename.'.';
		}
?>
