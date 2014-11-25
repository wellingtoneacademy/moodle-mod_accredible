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
 * Certificate module core interaction API
 *
 * @package    mod
 * @subpackage accredible
 * @copyright  Accredible <dev@accredible.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/accredible/locallib.php");

/**
 * Add certificate instance.
 *
 * @param array $certificate
 * @return array $certificate new certificate object
 */
function accredible_add_instance($post) {
    global $DB, $CFG;

    // Issue certs
    if( isset($post->users) ) {
        // Checklist array from the form comes in the format:
        // int user_id => boolean issue_certificate
        foreach ($post->users as $user_id => $issue_certificate) {
            if($issue_certificate) {
                $user = $DB->get_record('user', array('id'=>$user_id), '*', MUST_EXIST);

                $certificate = array();
                $certificate['name'] = $post->name;
                $certificate['achievement_id'] = $post->achievementid;
                $certificate['description'] = $post->description;
                $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);
                if($post->finalquiz) {
                    $quiz = $DB->get_record('quiz', array('id'=>$post->finalquiz), '*', MUST_EXIST);
                    $users_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
                    $certificate['evidence_items'] = array( array('string_object' => (string) $users_grade, 'description' => $quiz->name, 'custom'=> true, 'category' => 'grade'));
                }

                $curl = curl_init('https://api.accredible.com/v1/credentials');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
                if(!curl_exec($curl)) {
                    // throw API exception
                    // include the user id that triggered the error
                    // direct the user to accredible's support
                    // dump the post to debug_info
                    throw new moodle_exception('manualadderror:add', 'accredible', 'https://accredible.com/contact/support', $user_id, var_dump($post));
                }
                curl_close($curl);
            }
        }
    }

    // Save record
    $db_record = new stdClass();
    $db_record->name = $post->name;
    $db_record->course = $post->course;
    $db_record->description = $post->description;
    $db_record->achievementid = $post->achievementid;
    $db_record->finalquiz = $post->finalquiz;
    $db_record->passinggrade = $post->passinggrade;
    $db_record->timecreated = time();

    return $DB->insert_record('accredible', $db_record);
}

/**
 * Update certificate instance.
 *
 * @param stdClass $post
 * @return stdClass $certificate updated 
 */
function accredible_update_instance($post) {
    // To update your certificate details, go to accredible.com.
    global $DB, $CFG;

    // Issue certs
    if( isset($post->users) ) {
        // Checklist array from the form comes in the format:
        // int user_id => boolean issue_certificate
        foreach ($post->users as $user_id => $issue_certificate) {
            if($issue_certificate) {
                $user = $DB->get_record('user', array('id'=>$user_id), '*', MUST_EXIST);

                $certificate = array();
                $certificate['name'] = $post->name;
                $certificate['achievement_id'] = $post->achievementid;
                $certificate['description'] = $post->description;
                $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);
                if($post->finalquiz) {
                    $quiz = $DB->get_record('quiz', array('id'=>$post->finalquiz), '*', MUST_EXIST);
                    $users_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
                    $certificate['evidence_items'] = array( array('string_object' => (string) $users_grade, 'description' => $quiz->name, 'custom'=> true, 'category' => 'grade'));
                }

                $curl = curl_init('https://api.accredible.com/v1/credentials');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
                if(!curl_exec($curl)) {
                    // throw API exception
                    // include the user id that triggered the error
                    // direct the user to accredible's support
                    // dump the post to debug_info
                    throw new moodle_exception('manualadderror:edit', 'accredible', 'https://accredible.com/contact/support', $user_id, var_dump($post));
                }
                curl_close($curl);
            }
        }
    }

    // Save record
    $db_record = new stdClass();
    $db_record->name = $post->name;
    $db_record->description = $post->description;
    $db_record->passinggrade = $post->passinggrade;
    $db_record->finalquiz = $post->finalquiz;
    $db_record->id = $post->instance;

    return $DB->update_record('accredible', $db_record);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance.
 *
 * @param int $id
 * @return bool true if successful
 */
function accredible_delete_instance($id) {
    global $DB;

    // Ensure the certificate exists
    if (!$certificate = $DB->get_record('accredible', array('id' => $id))) {
        return false;
    }

    return $DB->delete_records('accredible', array('id' => $id));
}

/**
 * Supported feature list
 *
 * @uses FEATURE_MOD_INTRO
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function accredible_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:               return false;
        default: return null;
    }
}
