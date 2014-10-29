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
        foreach ($post->users as $user_id => $issue_certificate) {
            if($issue_certificate) {
                $user = $DB->get_record('user', array('id'=>$user_id));

                $certificate = array();
                $certificate['name'] = $post->name;
                $certificate['achievement_id'] = $post->achievementid;
                $certificate['description'] = $post->description;
                $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);

                $curl = curl_init('https://staging.accredible.com/v1/credentials');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
                curl_exec($curl);
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
    $db_record->passinggrade = $post->passinggrade;
    $db_record->timecreated = time();

    return $DB->insert_record('accredible', $db_record);
}

/**
 * Update certificate instance.
 *
 * @param stdClass $certificate
 * @return stdClass $certificate updated 
 */
function accredible_update_instance($post) {
    // To update your certificate details, go to accredible.com.
    global $DB, $CFG;

    // Issue certs
    if( isset($post->users) ) {
        foreach ($post->users as $user_id => $issue_certificate) {
            if($issue_certificate) {
                $user = $DB->get_record('user', array('id'=>$user_id));

                $certificate = array();
                $certificate['name'] = $post->name;
                $certificate['achievement_id'] = $post->achievementid;
                $certificate['description'] = $post->description;
                $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);

                $curl = curl_init('https://staging.accredible.com/v1/credentials');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
                curl_exec($curl);
                curl_close($curl);
            }
        }
    }

    // Save record
    $db_record = new stdClass();
    $db_record->passinggrade = $post->passinggrade;
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
 * List all of the ceritificates with a specific achievement id
 *
 * @param string $achievement_id
 * @return array[stdClass] $certificates
 */
function accredible_get_issued($achievement_id) {
    global $CFG;

    $curl = curl_init('https://staging.accredible.com/v1/credentials?full_view=true&achievement_id='.urlencode($achievement_id));
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = json_decode( curl_exec($curl) );
    curl_close($curl);
    return $result->credentials;
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
