<?php

/**
 * *************************************************************************
 * *                       CCI - Global Search                          **
 * *************************************************************************
 * @package     block                                                   **
 * @subpackage  cci globalsearch                                        **
 * @name        CCI Global Search                                       **
 * @copyright   Dhruv Infoline Pvt Ltd                                  **
 * @author      Arjun Singh (arjunsingh@elearn10.com)                   **
 * @license     http://lmsofindia.com                                   **
 * *************************************************************************
 * ************************************************************************ */




require('../../config.php');
require('lib.php');


//$courseid = required_param('courseid', PARAM_INT);
$q = required_param('q', PARAM_TEXT);
require_login(); //Use course 1 because this has nothing to do with an actual course, just like course 1
global $CFG, $PAGE, $OUTPUT, $DB, $USER;
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/blocks/cci_globalsearch/results.php');
$PAGE->set_title(get_string('pagetitle', 'block_cci_globalsearch'));
$PAGE->set_heading(get_string('pagetitle', 'block_cci_globalsearch'));

$PAGE->requires->css('/blocks/cci_globalsearch/styles.css');
echo $OUTPUT->header();
/* Added for course search based on course name and tags in it */
echo "<div class=\"clearfix\">";
echo "<div class=\"info\">";
echo "<h4>" . get_string('course_results', 'block_cci_globalsearch') . " '$q' : ".get_string('cr','block_cci_globalsearch')."</h4>";
$courses = $DB->get_records('course');
$categoroies = $DB->get_records('course_categories');
unset($courses[1]);
echo "<ul class='block_cci_globalsearch_result_list'>";
$count = 0;
$printed = false;
$flag = 0;
$course_tag_result =  array();
foreach ($courses as $courseid => $course) {
    $course_result = block_cci_globalsearch_search_course($course->id, $q);
    $course_tag_result = block_cci_globalsearch_course_tags($course->id, $q);
    if ($course_result != '')
    {
        $printed = true;
        $flag = 1;
        echo $course_result;
        $count = count($course_result);
        $count = $count + 1;
    }
    if ($flag == 0)
    {   
        if($course_tag_result != ''){
            echo $course_tag_result;
            $count = count($course_tag_result);
            $count = $count + 1;
        }
    }   
}
if ($count == 0) {
    echo $tag_head = '<i>' . get_string('no_result', 'block_cci_globalsearch') . '</i>';
}
echo "</ul>";
echo "<hr/>";
/* Added for module search based on mod name and tags in it */
echo "<h4>" . get_string('module_results', 'block_cci_globalsearch') . " '$q' : ".get_string('mr','block_cci_globalsearch')."</h4>";
$modules = $DB->get_records_sql('SELECT name FROM {modules}');
echo "<ul class='block_cci_course_search_result_list'>";
$i = 1;
$modcount =0;
$modtagcount = 0;
$res = 1;
$printed = false;
foreach ($courses as $courseid => $course) {
    $modinfo = get_fast_modinfo($course);
    /*search based on mobule names*/
    foreach ($modules as $module){
        //added for function to search within module contents
        //$functionname = "block_cci_globalsearch_search_module_" . $module->name;
        $module_result = '';
        //if (function_exists($functionname)){
            //$module_result = call_user_func($functionname, $courseid, $module, $q, $modinfo);
        //}else{
        $module_result = block_cci_globalsearch_search_module($courseid, $module, $q);
        //}
        if ($module_result != ''){
            $printed = true;
            echo $module_result;
            $modcount = count($course_tag_result);
            $i++;
        }        
    }
    /*search based on tags inside modules*/
    $mod_tag_result = block_cci_globalsearch_module_withtags($courseid, $q);
    if ($mod_tag_result != ''){
            $printed = true;
            echo $mod_tag_result;
            $modtagcount = count($mod_tag_result);
            $i++;
    }   
}
if ($modcount == 0 && $modtagcount == 0) {
    echo $tag_head = '<i>' . get_string('no_result', 'block_cci_globalsearch') . '</i>';
}
echo "</ul>";
echo "<hr/>";
echo "<h4>" . get_string('new_search', 'block_cci_globalsearch') . "</h3>";
echo "<div class=\"searchform\">";
echo "<form style='display:inline;' name='cci_globalsearch_form' id='block_cci_globalsearch_form' action='$CFG->wwwroot/blocks/cci_globalsearch/results.php' method='post'>";
echo "<fieldset class=\"invisiblefieldset\">";
echo get_string('searchfor', 'block_cci_globalsearch') . "<br>";
echo "<input type='text' name='q' id='q' value=''/>";
echo "<br><input type='submit' id='searchform_button' value='" . get_string('submit', 'block_cci_globalsearch') . "'>";
echo "</fieldset>";
echo "</form>";
echo "</div>";
echo "<br><button onclick='window.location = \"" . $CFG->wwwroot . "\"'>" . get_string('return_course', 'block_cci_globalsearch') . "</button>";
echo "</div>";
echo "</div>";
echo $OUTPUT->footer();
