<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
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
* Instance add/edit form
*
* @package    mod
* @subpackage certificate
* @copyright  Mark Nelson <markn@moodle.com>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/certificate/lib.php');

class mod_certificate_mod_form extends moodleform_mod {

    function definition() {
        global $DB, $OUTPUT;

        $id = required_param('course', PARAM_INT);    // Course Module ID
        if (!$course = $DB->get_record('course', array('id'=> $id))) {
            print_error('Course ID is wrong');
        }


        $context = context_course::instance($course->id);
        $query = 'select u.id as id, firstname, lastname, email from mdl_role_assignments as a, mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id;';
        $users = $DB->get_recordset_sql( $query );

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('certificatename', 'certificate'), array('value'=>$course->fullname));
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', $course->fullname);
        $mform->addRule('name', null, 'required', null, 'client');

        // TODO - language tag
        $mform->addElement('text', 'achievement_id', 'Achievement ID');
        $mform->setType('achievement_id', PARAM_TEXT);
        $mform->setDefault('name', $course->shortname);
        $mform->addRule('achievement_id', null, 'required', null, 'client');

        // TODO - language tag
        $mform->addElement('textarea', 'description', 'Description', array('cols'=>'64', 'rows'=>'4', 'wrap'=>'virtual'));
        $mform->setType('description', PARAM_RAW);
        $mform->setDefault('name', $course->summary);
        $mform->addRule('description', null, 'required', null, 'client');

        // TODO - language tag
        $mform->addElement('header', 'chooseusers', 'Choose Recipients');
        // make an array of the users' names
        $this->add_checkbox_controller(1, 'Select All/None');
        foreach( $users as $user ) { 
            $mform->addElement('advcheckbox', 'users['.$user->id.']', $user->firstname . ' ' . $user->lastname, null, array('group' => 1));
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}