<?php

// This file is part of the Accredible Certificate module for Moodle - http://moodle.org/
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
 * @subpackage accredible
 * @copyright  Accredible <dev@accredible.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/accredible/lib.php');

class mod_accredible_mod_form extends moodleform_mod {

    function definition() {
        global $DB, $OUTPUT, $CFG;
        // Make sure the API key is set
        if($CFG->accredible_api_key === "") {
            print_error('Please set your API Key first.');
        }
        // Update form init
        if (optional_param('update', '', PARAM_INT)) {
            $updatingcert = true;
            $cm_id = optional_param('update', '', PARAM_INT);
            if (!$cm = get_coursemodule_from_id('accredible', $cm_id)) {
                print_error('Course Module ID was incorrect');
            }
            $id = $cm->course;
            if (!$course = $DB->get_record('course', array('id'=> $id))) {
                print_error('Course ID is wrong');
            }
            if (!$accredible_certificate = $DB->get_record('accredible', array('id'=> $cm->instance))) {
                print_error('Course Module is incorrect');
            }
        } 
        // New form init
        elseif(optional_param('course', '', PARAM_INT)) {
            $id =  optional_param('course', '', PARAM_INT);
            if (!$course = $DB->get_record('course', array('id'=> $id))) {
                print_error('Course ID is wrong');
            }
            // see if other accredible certificates already exist for this course
            $alreadyexists = $DB->record_exists('accredible', array('course' => $id));
            if( $alreadyexists ) {
                $accredible_mod = $DB->get_record('modules', array('name' => 'accredible'));
                $cm = $DB->get_record('course_modules', array('course' => $id, 'module' => $accredible_mod->id));
                $url = new moodle_url('/course/modedit.php', array('update' => $cm->id));
                redirect($url, 'This course already has some certificates. Edit the activity to issue more certificates.');
            }
        }

        // Load user data
        $context = context_course::instance($course->id);
        $query = 'select u.id as id, firstname, lastname, email from mdl_role_assignments as a, mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id;';
        $users = $DB->get_recordset_sql( $query );

        // Form start
        $mform =& $this->_form;
        $mform->addElement('hidden', 'course', $id);

        $mform->addElement('header', 'general', get_string('general', 'form'));


        // TODO - language tag
        $mform->addElement('text', 'achievement_id', 'Achievement ID', array('disabled'=>''));
        $mform->setType('achievement_id', PARAM_TEXT);
        $mform->setDefault('achievement_id', $course->shortname);


        if($updatingcert) {
            $mform->addElement('text', 'name', get_string('certificatename', 'certificate'), array('disabled'=>''));
        } else {
            $mform->addElement('text', 'name', get_string('certificatename', 'certificate'));
            $mform->addRule('name', null, 'required', null, 'client');
        }
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', $course->fullname);


        // TODO - language tag
        if($updatingcert) {
            $mform->addElement('textarea', 'description', 'Description', array('cols'=>'64', 'rows'=>'4', 'wrap'=>'virtual', 'disabled'=>''));
        } else {
            $mform->addElement('textarea', 'description', 'Description', array('cols'=>'64', 'rows'=>'4', 'wrap'=>'virtual'));
            $mform->addRule('description', null, 'required', null, 'client');
        }
        $mform->setType('description', PARAM_RAW);
        $mform->setDefault('description', $course->summary);



        // TODO - language tag
        $mform->addElement('header', 'chooseusers', 'Manually Issue Certificates');

        $this->add_checkbox_controller(1, 'Select All/None');
        if($updatingcert) {
            // Grab existing certificates and cross-reference emails
            $certificates = accredible_get_issued($accredible_certificate->achievementid);
            foreach ($users as $user) {
                $cert_id = null;
                // check cert emails for this user
                foreach ($certificates as $certificate) {
                    if($certificate->recipient->email == $user->email) {
                        $cert_id = $certificate->id;
                        if($certificate->private) {
                            $cert_link = $certificate->id . '?key=' . $certificate->private_key;
                        }
                        else {
                            $cert_link = $cert_id;
                        }
                    }
                }
                // show the certificate if they have a certificate
                if( $cert_id ) {
                    $mform->addElement('static', 'certlink'.$user->id, $user->firstname . ' ' . $user->lastname, "Certificate $cert_id - <a href='https://accredible.com/$cert_link' target='_blank'>link</a>");
                } // show a checkbox if they don't
                else {
                    $mform->addElement('advcheckbox', 'users['.$user->id.']', $user->firstname . ' ' . $user->lastname, null, array('group' => 1));
                }
            }
        }
        // For new modules, just list all the users
        else {
            foreach( $users as $user ) { 
                $mform->addElement('advcheckbox', 'users['.$user->id.']', $user->firstname . ' ' . $user->lastname, null, array('group' => 1));
            }
        }



        // TODO - language tag
        $mform->addElement('header', 'autoissue', 'Automatic Issuing Criteria');
        $mform->addElement('text', 'passing_grade', 'Passing Grade');
        $mform->setType('passing_grade', PARAM_INT);
        $mform->setDefault('passing_grade', 70);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
