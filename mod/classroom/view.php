<?php

    require_once('../../config.php');
    require_once('lib.php');

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID
    $f = optional_param('f', 0, PARAM_INT); // classroom ID
    $location = optional_param('location'); // location 
    $download = optional_param('download', '', PARAM_ALPHA); // download attendance
    $page     = optional_param('page', 0, PARAM_INT);
	$perpage  = optional_param('perpage', 10, PARAM_INT);        // how many per page

    if ($id) {
        if (! $cm = get_record('course_modules', 'id', $id)) {
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $cm->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $classroom = get_record('classroom', 'id', $cm->instance)) {
            error(get_string('error:incorrectcoursemodule', 'classroom'));
        }
    } else if ($f) {
        if (! $classroom = get_record('classroom', 'id', $f)) {
            error(get_string('error:incorrectclassroomid', 'classroom'));
        }
        if (! $course = get_record('course', 'id', $classroom->course)) {
            error(get_string('error:coursemisconfigured', 'classroom'));
        }
        if (! $cm = get_coursemodule_from_instance('classroom', $classroom->id, $course->id)) {
            error(get_string('error:incorrectcoursemoduleid', 'classroom'));
        }

    } else {
        error(get_string('error:mustspecifycoursemoduleclassroom', 'classroom'));
    }

    if (empty($form->location)) {
        $form->location = '';
    }

    $location = '';

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if ($form = data_submitted()) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        $location = $form->location;
		$view = $form->view;
        if (!empty($form->download)) {
            require_capability('mod/classroom:viewattendees', $context);
       //     classroom_download_attendance($classroom->name, $classroom->id, $location, $view, $download);
	   //Naga added to download attendance
		   classroom_download_attendance($classroom->name, $classroom->id, $view, $download);
            exit();
        }
    }

    $strclassrooms = get_string('modulenameplural', 'classroom');
    $strclassroom = get_string('modulename', 'classroom');

    require_course_login($course);
    require_capability('mod/classroom:view', $context);

    add_to_log($course->id, 'classroom', 'view', "view.php?id=$cm->id", $classroom->id, $cm->id);

    $pagetitle = format_string($classroom->name);
    $navlinks[] = array('name' => $strclassrooms, 'link' => "index.php?id=$course->id", 'type' => 'title');
    $navlinks[] = array('name' => $pagetitle, 'link' => '', 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);
    print_header_simple($pagetitle, '', $navigation, '', '', true,
                        update_module_button($cm->id, $course->id, $strclassroom), navmenu($course, $cm));


    if (empty($cm->visible) and !has_capability('mod/classroom:viewemptyactivities', $context)) {
        notice(get_string('activityiscurrentlyhidden'));
    }

//Updated:RoyPhilip 30/4/2012 for session view page.

    require('view.html');
	
	classroom_print_sessions($course->id, $classroom->id, $location,$view);
    if (has_capability('mod/classroom:viewattendees', $context)) {
        require('view_download.html');
    }

    print_footer($course);

?>
