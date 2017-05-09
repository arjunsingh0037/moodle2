<?php  //$Id: upgrade.php,v 1.5 2008/10/30 23:59:43 fmarier Exp $

// This file keeps track of upgrades to 
// the classroom module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_classroom_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2008050500) {
        $table = new XMLDBTable('classroom');
        $field = new XMLDBField('thirdpartywaitlist');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'thirdparty');
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008061000) {
        $table = new XMLDBTable('classroom_submissions');
        $field = new XMLDBField('notificationtype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timemodified');
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008080100) {
        notify('Processing Face-to-face grades, this may take a while if there are many sessions...', 'notifysuccess');
        require_once $CFG->dirroot.'/mod/classroom/lib.php';

        begin_sql();
        $db->debug = false; // too much debug output

        // Migrate the grades to the gradebook
        $sql = "SELECT f.id, f.name, f.course, s.grade, s.timegraded, s.userid,
                       cm.idnumber as cmidnumber
                  FROM {$CFG->prefix}classroom_submissions s
                  JOIN {$CFG->prefix}classroom f ON s.classroom = f.id
                  JOIN {$CFG->prefix}course_modules cm ON cm.instance = f.id
                  JOIN {$CFG->prefix}modules m ON m.id = cm.module
                 WHERE m.name='classroom'";
        if ($rs = get_recordset_sql($sql)) {
            while ($result and $classroom = rs_fetch_next_record($rs)) {
                $grade = new stdclass();
                $grade->userid = $classroom->userid;
                $grade->rawgrade = $classroom->grade;
                $grade->rawgrademin = 0;
                $grade->rawgrademax = 100;
                $grade->timecreated = $classroom->timegraded;
                $grade->timemodified = $classroom->timegraded;

                $result = $result && (GRADE_UPDATE_OK == classroom_grade_item_update($classroom, $grade));
            }
            rs_close($rs);
        }
        $db->debug = true;

        // Remove the grade and timegraded fields from mdl_classroom_submissions
        if ($result) {
            $table = new XMLDBTable('classroom_submissions');
            $field1 = new XMLDBField('grade');
            $field2 = new XMLDBField('timegraded');
            $result = $result && drop_field($table, $field1, false, true);
            $result = $result && drop_field($table, $field2, false, true);
        }

        if ($result) {
            commit_sql();
        } else {
            rollback_sql();
        }
    }

    if ($result && $oldversion < 2008090800) {

        // Define field timemodified to be added to classroom_submissions
        $table = new XMLDBTable('classroom_submissions');
        $field = new XMLDBField('timecancelled');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'timemodified');

        // Launch add field
        $result = $result && add_field($table, $field);
    }

    return $result;
}

?>
