<?php

    require_once('../../config.php');
    require_once('lib.php');

    $id = required_param('id', PARAM_INT);           // Course Module ID 

    if ($id) {

        if (! $course = get_record('course', 'id', $id)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
    }

    require_course_login($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('mod/classroom:view', $context);

    add_to_log($course->id, 'classroom', 'view all', "index.php?id=$course->id");

    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');
    $strclassroomname = get_string('classroomname', 'classroom');
    $strweek = get_string('week');
    $strtopic = get_string('topic');
    $strcourse = get_string('course');
    $strname = get_string('name');

    $pagetitle = format_string($strclassrooms);
    $navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'title');
    $navigation = build_navigation($navlinks);
    print_header_simple($pagetitle, '', $navigation, '', '', true, '', navmenu($course));

    if (! $classrooms = get_all_instances_in_course('classroom', $course)) {
        notice(get_string('noclassrooms', 'classroom'), "../../course/view.php?id=$course->id");
        die;
    }

    $timenow = time();

    if ($course->format == 'weeks' && has_capability('mod/classroom:viewattendees', $context)) {
        $table->head  = array ($strweek, $strclassroomname, get_string('sign-ups', 'classroom'));
        $table->align = array ('center', 'left', 'center');
    } elseif ($course->format == 'weeks') {
        $table->head  = array ($strweek, $strclassroomname);
        $table->align = array ('center', 'left', 'center', 'center');
    } elseif ($course->format == 'topics' && has_capability('mod/classroom:viewattendees', $context)) {
        $table->head  = array ($strcourse, $strclassroomname, get_string('sign-ups', 'classroom'));
        $table->align = array ('center', 'left', 'center');
    } elseif ($course->format == 'topics') {
        $table->head  = array ($strcourse, $strclassroomname);
        $table->align = array ('center', 'left', 'center', 'center');
    } else {
        $table->head  = array ($strclassroomname);
        $table->align = array ('left', 'left');
    }

    $currentsection = '';

    foreach ($classrooms as $classroom) {

        $submitted = get_string('no');

        if (!$classroom->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?f=$classroom->id\">$classroom->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?f=$classroom->id\">$classroom->name</a>";
        }

        $printsection = '';
        if ($classroom->section !== $currentsection) {
            if ($classroom->section) {
                $printsection = $classroom->section;
            }
            $currentsection = $classroom->section;
        }

        $totalsignupcount = 0;
        if ($sessions = classroom_get_sessions($classroom->id)) {
            foreach($sessions as $session) {
                if (!classroom_has_session_started($session, $timenow)) {
                    $signupcount = classroom_get_num_attendees($session->id);
                    $totalsignupcount += $signupcount;
                }
            }
        }
        
        $courselink = '<a title="'.$course->shortname.'" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a>';
        if ($course->format == 'weeks' or $course->format == 'topics') {
            if (has_capability('mod/classroom:viewattendees', $context)) {
                $table->data[] = array ($courselink, $link, $totalsignupcount);
            } else {
                $table->data[] = array ($courselink, $link);
            }
        } else {
            $table->data[] = array ($link, $submitted);
        }
    }

    echo "<br />";

    print_table($table);

    print_footer($course);
?>
