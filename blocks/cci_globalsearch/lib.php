<?php

/**
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




defined('MOODLE_INTERNAL') || die();

/**
 * Search in a module content in the common fields (name, intro, content)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param stdClass $module The module object
 * @param string $q The string searched
 * @return string Return the result in HTML
 */
function block_cci_globalsearch_search_module($courseid, $module, $q)
{
    global $CFG, $DB, $OUTPUT;
    $ret = '';
    $sqlWere = 'course=? AND (false';
    $sqlParams = array($courseid);
    $onefield = false;
    $tagfields = false;
    $dbman = $DB->get_manager();
    if ($dbman->field_exists($module->name, 'name')){
        $sqlWere .= " OR name LIKE ?";
        $sqlParams[] = "%$q%";
        $onefield = true;
        $tagfields = true;
    }
    // if ($dbman->field_exists($module->name, 'intro')){
    //     $sqlWere .= " OR intro LIKE ?";
    //     $sqlParams[] = "%$q%";
    //     $onefield = true;
    //     $tagfields = true;
    // }
    // if ($dbman->field_exists($module->name, 'content')){
    //     $sqlWere .= " OR content LIKE ?";
    //     $sqlParams[] = "%$q%";
    //     $onefield = true;
    //     $tagfields = true;
    // }
    if ($onefield){
        $sql = "SELECT * FROM {" . $module->name . "} WHERE $sqlWere)";
        $results = $DB->get_records_sql($sql, $sqlParams);
        $modid = $DB->get_record('modules', array('name' => $module->name));
        foreach ($results as $result){
            $this_course_mod = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $modid->id, 'instance' => $result->id));
            if($this_course_mod){
                $coursename = $DB->get_record('course',array('id'=>$courseid));
                if(strlen($result->name) > 65){
                    $result->name = substr($result->name,0,65).'...';
                }
                $ret .= '<div class="coursebox clearfix odd first modbox"><div class="modinfo">';
                $ret .= "<h5><a href='$CFG->wwwroot/mod/$module->name/view.php?id=$this_course_mod->id'><img src='" . $OUTPUT->pix_url('icon', $module->name) . "' alt='$module->name -'/>&nbsp;$result->name</a></h5>";
                $ret .= "<div class='crsname'>
                            <h6>Course : </h6><a target='_blank' href='$CFG->wwwroot/course/view.php?id=$courseid'>&nbsp;$coursename->fullname</a></p>
                        </div>";
                $ret .= '</div></div>';
            }
        }
    }
    return $ret;
}


/**
 * Search in a module content in the common fields (name, intro, content)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param stdClass $module The module object
 * @param string $q The string searched
 * @param stdClass $modinfo The modinfo object
 * @return string Return the result in HTML
 */
function block_cci_globalsearch_search_module_label($courseid, $module, $q, $modinfo)
{
    global $CFG, $DB, $OUTPUT;

    $ret = '';
    $sqlParams = array($courseid, "%$q%", "%$q%");

    $sql = "SELECT * FROM {label} WHERE course=? AND (intro LIKE ? OR name LIKE ?)";
    //get sql

    $results = $DB->get_records_sql($sql, $sqlParams);
    //To create the link we need more info
    //find modid
    $modid = $DB->get_record('modules', array('name' => 'label'));

    //Get All sections
    $sections = $modinfo->get_sections();
           
    foreach ($results as $result)
    {
        $sectionfounded = null;
        $this_course_mod = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $modid->id, 'instance' => $result->id));

        foreach ($sections as $sectionnum => $section)
        {
            foreach ($section as $mod)
            {
                //If mod id == the course mod id
                if ($mod == $this_course_mod->id)
                {
                    //now find the name of the section
                    $sectionfounded = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionnum));
                    break 2;
                }
            }
        }

        if ($sectionfounded != null)
        {
            $ret .= "<li><a href='$CFG->wwwroot/course/view.php?id=$courseid#section-$sectionfounded->section'><img src='" . $OUTPUT->pix_url('icon', 'label') . "' alt='label - '/>&nbsp;$result->name</a></li>";
        }

    }
    return $ret;
}

/**
 * Search in a module tab content
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param stdClass $module The module object
 * @param string $q The string searched
 * @param stdClass $modinfo The modinfo object
 * @return string Return the result in HTML
 */
function block_cci_globalsearch_search_module_tab($courseid, $module, $q, $modinfo)
{
    global $CFG, $DB, $OUTPUT;

    $ret = '';
    $sqlParams = array($courseid, "%$q%", "%$q%", "%$q%", "%$q%");

    $sql = "SELECT {tab_content}.id as tabcontentid, {tab}.id as id,{tab}.name, {tab}.intro, {tab}.course, {tab_content}.tabname, {tab_content}.tabcontent
                    FROM {tab_content} 
                        INNER JOIN {tab} ON {tab_content}.tabid = {tab}.id AND {tab}.course = ?
                    WHERE {tab}.name LIKE ? OR {tab}.intro LIKE ?
                          OR {tab_content}.tabname LIKE ? OR {tab_content}.tabcontent LIKE ?";
    //get sql
    $results = $DB->get_records_sql($sql, $sqlParams);
    //To create the link we need more info
    //find modid
    $modid = $DB->get_record('modules', array('name' => 'tab'));
    $c = 1;
    foreach ($results as $result)
    {
        $this_course_mod = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $modid->id, 'instance' => $result->id));
        $ret .= "<li><a href='$CFG->wwwroot/mod/tab/view.php?id=$this_course_mod->id'><img src='" . $OUTPUT->pix_url('icon', 'tab') . "' alt=''/>&nbsp;$result->name</a></li>";
        $c++;
    }

    return $ret;
}

/**
 * Search in a module content in the common fields (tags)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param string $q The string searched
 * @return string Return the result in HTML
 */
function block_cci_globalsearch_module_withtags($courseid, $q)
{
    global $CFG, $DB, $OUTPUT;
    $ret = '';
    $flag = 0;
    $cms = array();
    $tagsinmods = array();
    $tagsall = array();
    $modsall = array();
    $tagmodules = array();
    $result_course_mods = array();
    $sql2 = "SELECT * FROM mdl_course_modules WHERE course =".$courseid;
    $result_course_mods = $DB->get_records_sql($sql2);
    foreach($result_course_mods as $course_mod){
        $tagobj = $DB->get_records('tag_instance',array('itemid'=>$course_mod->id,'itemtype'=>'course_modules'),'id,itemid,tagid');
        foreach($tagobj as $tid => $tags){
            $tagnames = $DB->get_record('tag',array('id'=>$tags->tagid),'id,name');
            $tagsall[$tagnames->id] = $tagnames->name;
        }
    }
    foreach ($tagsall as $tagkey => $tagvalue) {
        if (strchr(strtolower($tagvalue),strtolower($q))) {
            $flag =1;
            $tagmodule = $DB->get_records('tag_instance',array('tagid'=>$tagkey,'itemtype'=>'course_modules'),'id,itemid');
            foreach($tagmodule as $tagm){
                $tagmodules[$tagm->id] = $tagm->itemid;
            }
        }
    }
    
    if($flag == 1){
        foreach($tagmodules as $tkey => $tagmods){
            $this_course_mod = $DB->get_records('course_modules',array('id'=>$tagmods,'course'=>$courseid),'id');
            foreach ($this_course_mod as $key3 => $value3) {
                $cms[$tkey] = $value3;
            }
        }
        foreach ($cms as $key=> $mods) {
            $mod_type = $DB->get_record('modules',array('id'=>$mods->module),'name');
            $mod_name = $DB->get_record($mod_type->name,array('id'=>$mods->instance));
            $taginstance = $DB->get_record('tag_instance',array('id'=>$key,'itemtype'=>'course_modules'));
            $tagname = $DB->get_record('tag',array('id'=>$taginstance->tagid));
            
            if(strlen($mod_name->name) > 65){
                    $mod_name->name = substr($mod_name->name,0,65).'...';
            }
            $ret .= '<div class="coursebox clearfix odd first modbox"><div class="modinfo">';
            $ret .= "<h5><a href='$CFG->wwwroot/mod/$mod_type->name/view.php?id=$mods->id'><img src='" . $OUTPUT->pix_url('icon', $mod_type->name) . "' alt='$mod_type->name -'/>&nbsp;$mod_name->name</a></h5>";
            $ret .= "<div class='crsname'>
                        <h6>Tag Name : </h6>
                            <a target='_blank' class='label label-info standardtag' href='$CFG->wwwroot/tag/index.php?tc=$tagname->tagcollid&tag=$tagname->name&from=$taginstance->contextid#core_course'>
                                &nbsp;$tagname->name
                            </a>
                    </div>";
            $ret .= '</div></div>';
        }
    }
    return $ret;
}
/**
 * Search in a course fullname/shortname
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param string $q The string searched
 * @return string Return the result in HTML
 */
function block_cci_globalsearch_search_course($courseid,$q)
{
    global $CFG, $DB, $OUTPUT;
    $cat = '';
    $ret = '';
    $set = '';
    $result = $DB->get_record('course',array('id'=>$courseid));
    $category = $DB->get_record('course_categories',array('id'=>$result->category));
    /* done for category search */
    // $catname_search = strchr(strtolower($category->name),strtolower($q));
    // if (!empty($catname_search)) {
    //     $set = true;
    // }
    
    // if($set){
    //     $ret .= '<div class="coursebox clearfix odd first"><div class="info">';
    //     $ret .= "<h4 class='coursename'><a href='$CFG->wwwroot/course/view.php?id=$result->id'>&nbsp;$result->fullname</a></h4>";
    //     $ret .= "<div class='enrolmenticons'>
    //                 <img class='smallicon' src='http://localhost/cci/theme/image.php/clean/enrol_self/1487166706/withoutkey'>
    //                 <h5 class='coursename'>
    //                     <a href='$CFG->wwwroot/course/index.php?categoryid=$category->id'>&nbsp;$category->name</a>
    //                 </h5></div>";  
    //     $ret .= '</div></div>';
    // }else{
        $fullname_search = strchr(strtolower($result->fullname),strtolower($q));
        $shortname_search = strchr(strtolower($result->shortname),strtolower($q));
        if ($fullname_search || $shortname_search) {
            $ret .= '<div class="coursebox clearfix odd first"><div class="info">';
            $ret .= "<h4 class='coursename'><a href='$CFG->wwwroot/course/view.php?id=$result->id'>&nbsp;$result->fullname</a></h4>";
            $ret .= "<div class='categoryname'>
                        <p><h6>Category : </h6></p>
                        <p><a target='_blank' href='$CFG->wwwroot/course/index.php?categoryid=$category->id'>&nbsp;$category->name</a></p>
                    </div>";
            $ret .= '</div></div>';
        //} 
        }
    return $ret;
}
function block_cci_globalsearch_course_tags($courseid,$q)
{
    global $CFG, $DB, $OUTPUT;
    $cat = '';
    $ret = '';
    $all_tags = array();
    $tagresults = $DB->get_records('tag_instance',array('itemid'=>$courseid,'itemtype' => 'course'));
    if($tagresults){
        foreach ($tagresults as $tagins => $tagresult) {
            $tagname = $DB->get_record('tag',array('id'=>$tagresult->tagid));
            $tagname_search = strchr(strtolower($tagname->name),strtolower($q));
            if($tagname_search){
                $all_tags[$tagresult->tagid] = $courseid;
                $coursename = $DB->get_record('course',array('id'=>$courseid));
                $ret .= '<div class="coursebox clearfix odd first"><div class="info">';
                $ret .= "<h4 class='coursename'><a href='$CFG->wwwroot/course/view.php?id=$courseid'>&nbsp;$coursename->fullname</a></h4>";
                $ret .= "<div class='tagname'>
                            <p><h6>Tag Name: </h6></p>
                            <p><a target='_blank' class='label label-info standardtag' href='$CFG->wwwroot/tag/index.php?tc=$tagname->tagcollid&tag=$tagname->name&from=$tagresult->contextid#core_course'>
                                &nbsp;$tagname->name
                                </a>
                            </p>
                        </div>";
                $ret .= '</div></div>';
            }
        }
    }
    return $ret;
}

