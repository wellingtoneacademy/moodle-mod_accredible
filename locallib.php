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

require_once($CFG->libdir . '/eventslib.php');

/**
 * List all of the ceritificates with a specific achievement id
 *
 * @param string $achievement_id
 * @return array[stdClass] $certificates
 */
function accredible_get_issued($achievement_id) {
	global $CFG;

	$curl = curl_init('https://api.accredible.com/v1/credentials?full_view=true&achievement_id='.urlencode($achievement_id));
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	if(!$result = json_decode( curl_exec($curl) )) {
	  // throw API exception
	  // include the achievement id that triggered the error
	  // direct the user to accredible's support
	  // dump the achievement id to debug_info
	  throw new moodle_exception('getissuederror', 'accredible', 'https://accredible.com/contact/support', $achievement_id, $achievement_id);
	}
	curl_close($curl);
	return $result->credentials;
}

/*
 * accredible_issue_default_certificate
 * 
 */
function accredible_issue_default_certificate($certificate_id, $name, $email, $grade, $quiz_name) {
	global $DB, $CFG;

	// Issue certs
	$accredible_certificate = $DB->get_record('accredible', array('id'=>$certificate_id));

	$certificate = array();
  $course_url = new moodle_url('/course/view.php', array('id' => $accredible_certificate->course));
	$certificate['name'] = $accredible_certificate->certificatename;
	$certificate['achievement_id'] = $accredible_certificate->achievementid;
	$certificate['description'] = $accredible_certificate->description;
  $certificate['course_link'] = $course_url->__toString();
	$certificate['recipient'] = array('name' => $name, 'email'=> $email);
	if($grade) {
		$certificate['evidence_items'] = array( array('string_object' => $grade, 'description' => $quiz_name, 'custom'=> true, 'category' => 'grade' ));
	}

	$curl = curl_init('https://api.accredible.com/v1/credentials');
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$CFG->accredible_api_key.'"' ) );
	if(!$result = curl_exec($curl)) {
		// TODO - log this because an exception cannot be thrown in an event callback
	}
	curl_close($curl);
	return json_decode($result);
}

/*
 * accredible_log_creation
 */
function accredible_log_creation($certificate_id, $user_id, $course_id, $cm_id) {
	global $DB;

	// Get context
	$accredible_mod = $DB->get_record('modules', array('name' => 'accredible'), '*', MUST_EXIST);
	if($cm_id) {
		$cm = $DB->get_record('course_modules', array('id' => $cm_id), '*');
	} else { // this is an activity add, so we have to use $course_id
		$course_modules = $DB->get_records('course_modules', array('course' => $course_id, 'module' => $accredible_mod->id));
		$cm = end($course_modules);
	}
	$context = context_module::instance($cm->id);

	return \mod_accredible\event\certificate_created::create(array(
	  'objectid' => $certificate_id,
	  'context' => $context,
	  'relateduserid' => $user_id
	));
}

/*
 * Quiz submission handler (checks for a completed course)
 *
 * @param core/event $event quiz mod attempt_submitted event
 */
function accredible_quiz_submission_handler($event) {
	global $DB, $CFG;
	require_once($CFG->dirroot . '/mod/quiz/lib.php');

	$attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
	$quiz    = $event->get_record_snapshot('quiz', $attempt->quiz);
	$user 	 = $DB->get_record('user', array('id' => $event->relateduserid));
	if($accredible_certificates = $DB->get_records('accredible', array('course' => $event->courseid))) {
		foreach ($accredible_certificates as $accredible_certificate) {
			// check for the existence of a certificate and an auto-issue rule
			if( $accredible_certificate and ($accredible_certificate->finalquiz or $accredible_certificate->completionactivities) ) {

				// check which quiz is used as the deciding factor in this course
				if($quiz->id == $accredible_certificate->finalquiz) {
					$certificate_exists = accredible_check_for_existing_certificate (
						$accredible_certificate->achievementid, $user
					);

					// check for an existing certificate
					if(!$certificate_exists) {
						$users_grade = ( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100;
						$grade_is_high_enough = ($users_grade >= $accredible_certificate->passinggrade);

						// check for pass
						if($grade_is_high_enough) {
							// issue a ceritificate
							$api_response = accredible_issue_default_certificate( $accredible_certificate->id, fullname($user), $user->email, (string) $users_grade, $quiz->name);
							$certificate_event = \mod_accredible\event\certificate_created::create(array(
							  'objectid' => $api_response->credential->id,
							  'context' => context_module::instance($event->contextinstanceid),
							  'relateduserid' => $event->relateduserid
							));
							$certificate_event->trigger();
						}
					}
				}

				$completion_activities = unserialize_completion_array($accredible_certificate->completionactivities);
				// if this quiz is in the completion activities
				if( isset($completion_activities[$quiz->id]) ) {
					$completion_activities[$quiz->id] = true;
					$quiz_attempts = $DB->get_records('quiz_attempts', array('userid' => $user->id, 'state' => 'finished'));
					foreach($quiz_attempts as $quiz_attempt) {
						// if this quiz was already attempted, then we shouldn't be issuing a certificate
						if( $quiz_attempt->quiz == $quiz->id && $quiz_attempt->attempt > 1 ) {
							return null;
						}
						// otherwise, set this quiz as completed
						if( isset($completion_activities[$quiz_attempt->quiz]) ) {
							$completion_activities[$quiz_attempt->quiz] = true;
						}
					}

					// but was this the last required activity that was completed?
					$course_complete = true;
					foreach($completion_activities as $is_complete) {
						if(!$is_complete) {
							$course_complete = false;
						}
					}
					// if it was the final activity
					if($course_complete) {
						$certificate_exists = accredible_check_for_existing_certificate (
							$accredible_certificate->achievementid, $user
						);
						// make sure there isn't already a certificate
						if(!$certificate_exists) {
							// and issue a ceritificate
							$api_response = accredible_issue_default_certificate( $accredible_certificate->id, fullname($user), $user->email, null, null);
							$certificate_event = \mod_accredible\event\certificate_created::create(array(
							  'objectid' => $api_response->credential->id,
							  'context' => context_module::instance($event->contextinstanceid),
							  'relateduserid' => $event->relateduserid
							));
							$certificate_event->trigger();
						}
					}
				}
			}
		}
	}
}

function accredible_check_for_existing_certificate($achievement_id, $user) {
	global $DB;
	$certificate_exists = false;
	$certificates = accredible_get_issued($achievement_id);

	foreach ($certificates as $certificate) {
		if($certificate->recipient->email == $user->email) {
			$certificate_exists = true;
		}
	}
	return $certificate_exists;
}

function serialize_completion_array($completion_array) {
	return base64_encode(serialize( (array)$completion_array ));
}

function unserialize_completion_array($completion_object) {
	return (array)unserialize(base64_decode( $completion_object ));
}

