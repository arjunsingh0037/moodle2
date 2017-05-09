<?php


	function duration_issue($COURSEID,$ASSETINSTANCE,$type,$userid) 
	{

	
		if (duration_issued($COURSEID,$ASSETINSTANCE,$userid))
		{
		
		return duration_update($COURSEID,$ASSETINSTANCE,$type,$userid);
		}
		else
		{
		
		return duration_insert($COURSEID,$ASSETINSTANCE,$type,$userid);
		}
	}
	//For classrooms
	function duration_issue_classroom($COURSEID,$ASSETINSTANCE,$type,$userid) 
	{

		 if (duration_issued($COURSEID,$ASSETINSTANCE,$userid))
		 {
		 
		
		 return duration_update_classroom($COURSEID,$ASSETINSTANCE,$userid);
		 }
		 else
		 {
			
		return duration_insert_classroom($COURSEID,$ASSETINSTANCE,$type,$userid);
		}
	}
	//completed
	//to revert the classroom duartion
	
	//For classrooms
	function duration_revert_classroom($COURSEID,$ASSETINSTANCE,$type,$userid) 
	{

		 if (duration_issued($COURSEID,$ASSETINSTANCE,$userid))
		 {
		 
		
		 return duration_revert_update_classroom($COURSEID,$ASSETINSTANCE,$userid);
		 }
		 
	}
	//completed
    function duration_issued($COURSEID,$ASSETINSTANCE,$userid) {
        global $CFG;	

		$duration=get_record_sql("SELECT sum(duration) as duration FROM MDL_USER_MODULE_COMPLETION where courseid=$COURSEID 
								and ASSETINSTANCE=$ASSETINSTANCE and userid =$userid");		
													
        return  $duration->duration;
    }
	
	function duration_insert($COURSEID,$ASSETINSTANCE,$type,$userid) {
	
		
		if (!$userid) {
        global $USER;
        $userid = $USER->id;
		}
		if ($COURSEID=='8503')
		{
			$type='skillsoft_nonmatched';
		}
		if (!$module = get_record($type, 'id', $ASSETINSTANCE)) 
		{
			return false;
		}
		
		 if ($type=='quiz')
		 {
		 $module->duration=$module->timelimit;
		 }
	$return = false;
    $timenow = time();

    $point = new stdclass;
    $point->COURSEID = $COURSEID;
    $point->ASSETINSTANCE = $ASSETINSTANCE;
    $point->type = $type;
    $point->userid = $userid;
	$point->ASSETNAME=$module->name;
    $point->duration = $module->duration;
   	$point->LASTMODIFIEDDATE = $timenow;
	
	
		begin_sql();	
		if ($returnid = insert_record('USER_MODULE_COMPLETION', $point)) 
		{
			commit_sql();
          //  return $point->point;
		  //Naga commented above line to fix the issue with pop up message
		   return $point;
		}
		else
		{
			rollback_sql();
			return false;
		}
	}
	
	function duration_update($COURSEID,$ASSETINSTANCE,$type,$userid) {
		$timenow = time();
			$pointrecord = array();
		$pointrecord=get_record_sql("select id from  mdl_user_module_completion where courseid='$COURSEID'  and ASSETINSTANCE='$ASSETINSTANCE'  and type='$type'  and userid ='$userid'");
	
		if (!$pointrecord) 
		{
			return false;
		}
		
		if ($COURSEID=='8503')
		{
			$type='skillsoft_nonmatched';
		}
		if (!$module = get_record($type, 'id', $ASSETINSTANCE)) 
		{
			return false;
		}
			
			$pointrecord->id = $pointrecord->id;
			$pointrecord->DURATION=$module->duration;
			$pointrecord->LASTMODIFIEDDATE=$timenow;
			
			
		begin_sql();
		if ($returnid = update_record('USER_MODULE_COMPLETION',$pointrecord)) 
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
	//For claassrooms
	
	function duration_insert_classroom($COURSEID,$ASSETINSTANCE,$type,$userid) {
	
		
		if (!$userid) {
        global $USER;
        $userid = $USER->id;
		}
	
		if (!$module = get_record('classroom_sessions', 'id', $ASSETINSTANCE)) 
		{
			return false;
		}
		
		
			$return = false;
			$timenow = time();

			$point = new stdclass;
			$point->COURSEID = $COURSEID;
			$point->ASSETINSTANCE = $ASSETINSTANCE;
			$point->type = $type;
			$point->userid = $userid;
			$point->ASSETNAME=$module->programename;
			$point->duration = $module->duration;
			$point->LASTMODIFIEDDATE = $timenow;

		begin_sql();	
		if ($returnid = insert_record('USER_MODULE_COMPLETION', $point)) 
		{
			commit_sql();
          //  return $point->point;
		  //Naga commented above line to fix the issue with pop up message
		   return $point;
		}
		else
		{
			rollback_sql();
			return false;
		}
	}
	
	function duration_update_classroom($COURSEID,$ASSETINSTANCE,$userid) {
		
	$timenow = time();
	
		$pointrecord = array();
		$pointrecord=get_record_sql("select id from  mdl_user_module_completion where courseid='$COURSEID'  and ASSETINSTANCE='$ASSETINSTANCE'    and userid ='$userid'");
	
		if (!$pointrecord) 
		{
			return false;
		}
		if (!$module = get_record('classroom_sessions', 'id', $ASSETINSTANCE)) 
		{
			return false;
		}
			
			$pointrecord->id = $pointrecord->id;
			$pointrecord->DURATION=$module->duration;
			$pointrecord->LASTMODIFIEDDATE=$timenow;
		
		
		begin_sql();
		if ($returnid = update_record('USER_MODULE_COMPLETION', $pointrecord)) 
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
	//To revert the duartion for the classrooms if the user's attendence changes
	function duration_revert_update_classroom($COURSEID,$ASSETINSTANCE,$userid) {
	$timenow = time();
	
		$pointrecord = array();
		$pointrecord=get_record_sql("select id from  mdl_user_module_completion where courseid='$COURSEID'  and ASSETINSTANCE='$ASSETINSTANCE'    and userid ='$userid'");
	
		if (!$pointrecord) 
		{
			return false;
		}
		if (!$module = get_record('classroom_sessions', 'id', $ASSETINSTANCE)) 
		{
			return false;
		}
			
			$pointrecord->id = $pointrecord->id;
			$pointrecord->DURATION=0;
			$pointrecord->LASTMODIFIEDDATE=$timenow;
		
		
		begin_sql();
		if ($returnid = update_record('USER_MODULE_COMPLETION', $pointrecord)) 
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
		/*
	function duration_revert_classroom($COURSEID,$ASSETINSTANCE,$userid) {
	$timenow = time();
	
		if (!$pointrecord = get_record('USER_MODULE_COMPLETION', 'courseid', $COURSEID,'ASSETINSTANCE',$ASSETINSTANCE,'userid',$userid)) 
		{
			return false;
		}
		
			$pointrecord->id = $pointrecord->id;
			$pointrecord->DURATION=0;
			$pointrecord->LASTMODIFIEDDATE=$timenow;

		begin_sql();
		if ($returnid = update_record('USER_MODULE_COMPLETION', $pointrecord)) 
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
	*/
	//completed
	

	function duration_delete($id) {

		global $CFG;

		if (!$point = get_record('USER_MODULE_COMPLETION', 'id', $id)) 
		{
			return false;
		}   
			begin_sql();
		if (!delete_records('USER_MODULE_COMPLETION', 'id', $id)) {
			rollback_sql();
			return false;
		}
		else
		{
			commit_sql();
			return true;
		}

	}
		
?>
