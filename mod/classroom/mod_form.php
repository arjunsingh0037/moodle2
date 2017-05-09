<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * print the form to add or edit a classroom-instance
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_classroom
 */

//It must be included from a Moodle page
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_classroom_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB;

        //$editoroptions = classroom_get_editor_options();

        $mform    =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('classroomname', 'classroom'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addElement('text', 'thirdparty', get_string('thirdpartyemailaddress', 'classroom'), array('size'=>'64'));
        $mform->setType('thirdparty', PARAM_TEXT);
        $mform->addRule('thirdparty', null, 'required', null, 'client');
        $mform->addRule('thirdparty', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addElement('textarea', 'description', get_string('introductiontext', 'classroom'));
        $mform->setDefault('description', '');
        $mform->addElement('checkbox', 'thirdpartywaitlist', get_string('thirdpartywaitlist', 'classroom'));
        $mform->setDefault('thirdpartywaitlist', 0);
        $mform->addElement('checkbox', 'multiplesession', get_string('multiplesession', 'classroom'));
        $mform->setDefault('multiplesession', 0);

        $mform->addElement('header', 'confirmation', get_string('confirmation', 'classroom'));
        $mform->addElement('text', 'confirmationsubject', get_string('email:subject', 'classroom'), array('size'=>'64'));
        $mform->setType('confirmationsubject', PARAM_TEXT);
        $mform->addElement('textarea', 'confirmationmessage', get_string('email:participantmessage', 'classroom'));
        $mform->setDefault('confirmationmessage', '');
        $mform->addElement('textarea', 'trainerconfirmationmessage', get_string('email:trainermessage', 'classroom'));
        $mform->setDefault('trainerconfirmationmessage', '');

        $mform->addElement('header', 'cancellation', get_string('cancellation', 'classroom'));

        $mform->addElement('text', 'cancelprogram', get_string('email:subject', 'classroom'), array('size'=>'64'));
        $mform->setType('cancelprogram', PARAM_TEXT);
        $mform->addElement('textarea', 'cancelprogrammessage', get_string('email:cancelmessage', 'classroom'));
        $mform->setDefault('cancelprogrammessage', '');

        $mform->addElement('header', 'managerconfirmation', get_string('managerconfirmation', 'classroom'));

        $mform->addElement('textarea', 'confirmationinstrmngr', get_string('email:cancelmessage', 'classroom'));
        $mform->setDefault('confirmationinstrmngr', '');
        $mform->addElement('checkbox', 'emailmanagerconfirmation', get_string('emailmanager', 'classroom'));
        $mform->setType('emailmanagerconfirmation', 0);
        

        $mform->addElement('header', 'remindersettings', get_string('remindersettings', 'classroom'));

        $mform->addElement('text', 'remindersubject', get_string('email:subject', 'classroom'), array('size'=>'64'));
        $mform->setType('remindersubject', PARAM_TEXT);
        $mform->addElement('textarea', 'remindermessage', get_string('email:message', 'classroom'));
        $mform->setDefault('remindermessage', '');
        $mform->addElement('textarea', 'reminderinstrmngr', get_string('email:instrmngr', 'classroom'));
        $mform->setDefault('reminderinstrmngr', '');
        $mform->addElement('checkbox', 'emailmanagerreminder', get_string('emailmanager', 'classroom'));
        $mform->setType('emailmanagerreminder', 0);
        

        $mform->addElement('header', 'waitlistsettings', get_string('waitlistsettings', 'classroom'));

        $mform->addElement('text', 'waitlistedsubject', get_string('email:subject', 'classroom'), array('size'=>'64'));
        $mform->setType('waitlistedsubject', PARAM_TEXT);
        $mform->addElement('textarea', 'waitlistedmessage', get_string('email:message', 'classroom'));
        $mform->setDefault('waitlistedmessage', '');

        $mform->addElement('header', 'cancellationmsgsettings', get_string('cancellationmsgsettings', 'classroom'));

        $mform->addElement('text', 'cancellationsubject', get_string('email:subject', 'classroom'), array('size'=>'64'));
        $mform->setType('cancellationsubject', PARAM_TEXT);
        $mform->addElement('textarea', 'cancellationmessage', get_string('email:message', 'classroom'));
        $mform->setDefault('cancellationmessage', '');
        $mform->addElement('textarea', 'cancellationinstrmngr', get_string('email:instrmngr', 'classroom'));
        $mform->setDefault('cancellationinstrmngr', '');
        $mform->addElement('checkbox', 'emailmanagercancellation', get_string('emailmanager', 'classroom'));
        $mform->setType('emailmanagercancellation', 0);
        

        $mform->addElement('header', 'absenteessettings', get_string('absenteessettings', 'classroom'));

        $mform->addElement('text', 'absenteessubject', get_string('email:subject', 'classroom'), array('size'=>'64'));
        $mform->setType('absenteessubject', PARAM_TEXT);
        $mform->addElement('textarea', 'absenteesmessage', get_string('email:message', 'classroom'));
        $mform->setDefault('absenteesmessage', '');
        $mform->addElement('checkbox', 'emailabsentees', get_string('emailabsentees', 'classroom'));
        $mform->setType('emailabsentees', 0);

        //-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$default_values) {

    }

    public function get_data() {
        
    }

    public function validation($data, $files) {
        
    }

    public function add_completion_rules() {
    }

    public function completion_rule_enabled($data) {
    }
}
