<?php

	function badge_count_user($userid)
	{
		if($badges = get_record_sql("select count(badgeid) as counts from mdl_game_badge_track 
		where userid=$userid"))
		{
			return $badges->counts;
		}
		else
		{
			return 0;
		}	
	}
	function badge_count_useryear($userid,$sd,$ed)
	{
		if($badges = get_record_sql("select count(badgeid) as counts from mdl_game_badge_track 
		where userid=$userid and timeissued>unix_timestamp('$sd') and timeissued<unix_timestamp('$ed')"))
		{
			return $badges->counts;
		}
		else
		{
			return 0;
		}	
	}
	function badge_select_recent($userid) {

		if($badges = get_records_sql("select t.id as id,b.id as badgeid,b.name as name,b.file as badge,
		t.comment as comments,t.timeissued as timeissued from mdl_game_badge_track t 
		join mdl_game_badge b on b.id=t.badgeid and t.userid=$userid order by t.id desc limit 2"))
		{
		return ($badges);
		}
		else
		{
		return false;
		}
	}
	function badge_select_latest($userid) {

		if($badges = get_records_sql("select t.id as id,b.id as badgeid,b.name as name,b.file as badge,
		t.comment as comments,t.timeissued as timeissued from mdl_game_badge_track t 
		join mdl_game_badge b on b.id=t.badgeid and t.userid=$userid order by t.id desc limit 1"))
		{
		return ($badges);
		}
		else
		{
		return false;
		}
	}
	function badge_select($userid) {

		if($badges = get_records_sql("select t.id as id,b.id as badgeid,b.name as name,b.file as badge,
		t.comment as comments,t.timeissued as timeissued from mdl_game_badge_track t 
		join mdl_game_badge b on b.id=t.badgeid and t.userid=$userid"))
		{
		return ($badges);
		}
		else
		{
		return false;
		}
	}

	function process_system_badges($userid)
	{
		badge_identity($userid);
		badge_Gold_enrol($userid);
		badge_Star_enrol($userid);
		badge_Gold_completed($userid) ;
		badge_Star_completed($userid) ;
		badge_scout($userid);
		badge_cobc($userid);
		badge_cobc_2014($userid);
		badge_cobc_2015($userid);
		Time_Expense($userid);
		PME_NA($userid);
		PME_INDIA($userid);
		badge_security($userid);
		badge_security_2015($userid);
		badge_smart($userid);
		badge_mastery($userid);
		badge_nlci_bronze($userid);
		badge_nlci_silver($userid); 
		badge_csr($userid);
		badge_vision_value($userid);

	}
	function badge_issue($badge,$userid)
	{
		
		if($data=get_record('game_badge', 'name', $badge))
		{
			if(!get_record('game_badge_track', 'badgeid', $data->id,'userid',$userid))
			{
				return badge_insert($userid,$data->id,$data->issuer,$data->comment);
			}
		}
	}
	function badge_insert($userid,$badgeid,$issuer,$comment)
	{

		if (!$userid) {
        global $USER;
        $userid = $USER->id;
		}
	
		$return = false;
		$timenow = time();

		$badge = new stdclass;
		$badge->userid = $userid;
		$badge->badgeid = $badgeid;
		$badge->issuer = $issuer;
		$badge->comment = $comment;
		$badge->timeissued = $timenow;
		$badge->timemodified = $timenow;


		begin_sql();	
		if ($returnid = insert_record('game_badge_track', $badge)) 
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

function badge_Gold_enrol($userid) 
	{
$profile=get_record_sql("SELECT ra.id as assignid
                                FROM mdl_course AS c JOIN mdl_context AS ctx
                                ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
                                ON ra.contextid = ctx.id JOIN mdl_user AS u
                                ON u.id = ra.userid and u.id= $userid and c.id= 326");
						if($profile->assignid)
		{ 
			return badge_issue('Gold Club Member ',$userid);		
		}
		else
		{return false;		
								}
								}
								
function badge_Gold_completed($userid) 
	{
				$profile=get_record_sql("SELECT gg.finalgrade as grade
                                FROM mdl_course AS c JOIN mdl_context AS ctx
                                ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
                                ON ra.contextid = ctx.id JOIN mdl_user AS u
                                ON u.id = ra.userid JOIN mdl_grade_grades AS gg
                                ON gg.userid = u.id JOIN mdl_grade_items AS gi
                                ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
                                WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id= $userid and c.id= 326");
						if($profile->grade)
					{ 
							return badge_issue('Gold Club Graduate ',$userid);		
					}
						else
						{
						return false;		
								}
	}
function badge_Star_enrol($userid) 
	{
$profile=get_record_sql("SELECT ra.id as assignid
                                FROM mdl_course AS c JOIN mdl_context AS ctx
                                ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
                                ON ra.contextid = ctx.id JOIN mdl_user AS u
                                ON u.id = ra.userid and u.id= $userid and c.id= 327");
						if($profile->assignid)
		{ 
			return badge_issue('Star Club Member',$userid);		
		}
		else
		{return false;		
								}
								}
								
function badge_Star_completed($userid) 
	{
				$profile=get_record_sql("SELECT gg.finalgrade as grade
                                FROM mdl_course AS c JOIN mdl_context AS ctx
                                ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
                                ON ra.contextid = ctx.id JOIN mdl_user AS u
                                ON u.id = ra.userid JOIN mdl_grade_grades AS gg
                                ON gg.userid = u.id JOIN mdl_grade_items AS gi
                                ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
                                WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id= $userid and c.id= 327");
						if($profile->grade)
					{ 
							return badge_issue('Star Club Graduate ',$userid);		
					}
						else
						{
						return false;		
								}
	}
	function badge_identity($userid) 
	{
		$profile=get_record_sql("select max(picture) as picture from mdl_user where id=$userid");
		if($profile->picture>0)
		{ 
			return badge_issue('IDENTITY BADGE',$userid);		
		}
		else
		{return false;}

	}
	function badge_scout($userid) 
	{
		$fetchcourse=get_records_sql("SELECT distinct(course) FROM lms.mdl_log m where userid=$userid and action='view' and module='course' and course !=1 order by course");

		$array=array();
		foreach($fetchcourse as $fetchcourses)
		{
		$fetchdate=get_records_sql("SELECT from_unixtime(time) as fdate FROM lms.mdl_log m where userid=$userid and action='view' and module='course' and course =$fetchcourses->course order by time");
		$datess=array();
		foreach($fetchdate as $fetchdates)
		{
			$firstdate=$fetchdates->fdate;
			$datess[]=$firstdate;
		}
		$array[]=$datess;
		$firstdate=$datess[0];
		$fcount=0;
		for($n=0;$n<count($datess);$n++)
		{
		$fcount=0;

		for($k=$n+1;$k<count($datess);$k++)
		{

		$fdateobj=date("Y-m-d",strtotime($datess[$n]));
		$secondateobj=date("Y-m-d",strtotime($datess[$k]));
		$datetime1 = date_create($fdateobj);
		$datetime2 = date_create($secondateobj);
		$interval = date_diff($datetime2, $datetime1);
		$dcount=$interval->format('%a');
		
		if($dcount==7){
		$fcount=0;
		$fcount=$fcount+1;
		}
		
		if($dcount==14 &&  $fcount==1)
		{
		$fcount=$fcount+1;
		if($fcount==2)
		{
	
		return badge_issue('Scout Badge',$userid);
		exit;

		}else{
		continue;
		}

		}
		}
		if($fcount>=2)
		{
		
		return badge_issue('Scout Badge',$userid);
		exit;

		}else{
		continue;
		}
	
		}

	}
}
	function badge_cobc($userid) 
	{
		$cobc=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id=7044");
		if($cobc->grade>0)
		{ 
			return badge_issue('KNOW THE CODE BADGE',$userid);		
		}
		else
		{return false;}
	}
	function badge_cobc_2014($userid) 
	{
		$cobc=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id =8403");
		if($cobc->grade>0)
		{ 
			return badge_issue('KNOW THE CODE BADGE 2014',$userid);		
		}
		else
		{return false;}
	}
	function badge_cobc_2015($userid) 
	{
		$cobc=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id =8799");
		if($cobc->grade>0)
		{ 
			return badge_issue('KNOW THE CODE BADGE 2015',$userid);		
		}
		else
		{return false;}
	}
		function Time_Expense($userid) 
	{
		$cobc=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id=5735");
		if($cobc->grade>0)
		{ 
			return badge_issue('Time & Expense Course BADGE ',$userid);		
		}
		else
		{return false;}
	}	
	function PME_NA($userid) 
	{
		$cobc=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id=5970");
		if($cobc->grade>0)
		{ 
			return badge_issue('People Management Essentials (NA) BADGE ',$userid);		
		}
		else
		{return false;}
	}
	function PME_INDIA($userid) 
	{
		$cobc=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id=7267");
		if($cobc->grade>0)
		{ 
			return badge_issue('People Management Essentials (INDIA) BADGE ',$userid);		
		}
		else
		{return false;}
	}

	function badge_security($userid) 
	{
		$security=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id in(7335,6803)");
		if($security->grade>0)
		{ 
			return badge_issue('SECURITY BADGE',$userid);		
		}
		else
		{return false;}

	}
	function badge_security_2015($userid) 
	{
		$security=get_record_sql("SELECT gg.finalgrade as grade
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
		WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id=$userid and c.id in(8500)");
		if($security->grade>0)
		{ 
			return badge_issue('Global Information Security',$userid);		
		}
		else
		{return false;}

	}
	function badge_smart($userid) 
	{
	$points=get_record_sql("select sum(point) as point from mdl_game_point where userid=$userid");
		if($points->point >=5000)
		{ 
			return badge_issue('SMART STUDENT BADGE',$userid);		
		}
		else
		{return false;}

	}
	function badge_mastery($userid) 
	{
		$points=get_record_sql("select sum(point) as point from mdl_game_point where userid=$userid");
		if($points->point >=50000)
		{ 
			return badge_issue('MASTERY BADGE',$userid);		
		}
		else
		{return false;}
	}
	function badge_nlci_bronze($userid) 
	{
		$nlci=get_record_sql("SELECT count(u.id) As certified
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id and ra.roleid='5'  JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade = gi.grademax JOIN mdl_course_categories AS cc
		ON cc.id = c.category WHERE  gi.courseid = c.id
		AND gi.itemtype = 'course' and cc.path like '/9%' and u.id=$userid");
		if($nlci->certified >=2)
		{ 
			return badge_issue('NLCI BRONZE TROPHY',$userid);		
		}
		else
		{return false;}
	}
	function badge_nlci_silver($userid) 
	{
		$nlci=get_record_sql("SELECT count(u.id) As certified
		FROM mdl_course AS c JOIN mdl_context AS ctx
		ON c.id = ctx.instanceid JOIN mdl_role_assignments AS ra
		ON ra.contextid = ctx.id and ra.roleid='5'  JOIN mdl_user AS u
		ON u.id = ra.userid JOIN mdl_grade_grades AS gg
		ON gg.userid = u.id JOIN mdl_grade_items AS gi
		ON gi.id = gg.itemid and gg.finalgrade = gi.grademax JOIN mdl_course_categories AS cc
		ON cc.id = c.category WHERE  gi.courseid = c.id
		AND gi.itemtype = 'course' and cc.path like '/9%' and u.id=$userid");
		if($nlci->certified >=4)
		{ 
			return badge_issue('NLCI SILVER TROPHY',$userid);		
		}
		else
		{return false;}

	}
	function badge_csr($userid) 
	{
		$csr=get_record_sql("select count(sessionid) as count from mdl_csr_signups where userid=$userid");
		if($csr->count>0)
		{ 
			return badge_issue('CSR BADGE',$userid);		
		}
		else
		{return false;}
	}
	function badge_vision_value($userid) 
	{
				$profile=get_record_sql("SELECT gg.finalgrade as grade
                                FROM mdl_course AS c JOIN mdl_context AS ctx
                                ON c.id = ctx.instanceid and c.visible=1 JOIN mdl_role_assignments AS ra
                                ON ra.contextid = ctx.id JOIN mdl_user AS u
                                ON u.id = ra.userid JOIN mdl_grade_grades AS gg
                                ON gg.userid = u.id JOIN mdl_grade_items AS gi
                                ON gi.id = gg.itemid and gg.finalgrade >= gi.grademax
                                WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and u.id= $userid and c.id= 8529");
							
						if($profile->grade)
					{ 
							
							return badge_issue('Value and Visions Week 2015',$userid);		
					}
						else
						{
						
						return false;		
								}
	}

?>
