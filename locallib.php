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

// For composer dependencies
require __DIR__ . '/vendor/autoload.php';

use ACMS\Api;

/* LET'S GET CREDENTIALS FROM ACCREDDIBLE TO DISPLAY EXISTING CERTIFICATES FOR ADMINS AND USERS */
function accredible_get_credentials($group_id, $email= null) {
    global $CFG;

    $page_size = 50;
    $page = 1;
    // Maximum number of pages to request to avoid possible infinite loop.
    $loop_limit = 100;

    $api = new Api($CFG->accredible_api_key);

    try {

        $loop = true;
        $count = 0;
        $credentials = array();
        // Query the Accredible API and loop until it returns that there is no next page.
        while ($loop === true) {
            $credentials_page = $api->get_credentials($group_id, $email, $page_size, $page);

            foreach ($credentials_page->credentials as $credential) {
                $credentials[] = $credential;
            }

            $page++;
            $count++;

            if ($credentials_page->meta->next_page === null || $count >= $loop_limit) {
                // If the Accredible API returns that there
                // is no next page, end the loop.
                $loop = false;
            }
         }
        return $credentials;
	} catch (ClientException $e) {
	    // throw API exception
	  	// include the achievement id that triggered the error
	  	// direct the user to accredible's support
	  	// dump the achievement id to debug_info
        $exceptionparam = new stdClass();
        $exceptionparam->group_id = $group_id;
        $exceptionparam->email = $email;
        $exceptionparam->last_response = $credentials_page;
	  	throw new moodle_exception('getcredentialserror', 'accredible', 'https://help.accredible.com/hc/en-us', $exceptionparam);
	}
}	

/**
 * Get the groups for the issuer
 * @return type
 */
function accredible_get_groups() {
	global $CFG;

	$api = new Api($CFG->accredible_api_key);

	try {
		$response = $api->get_groups(10000,1);

		$groups = array();
		for($i = 0, $size = count($response->groups); $i < $size; ++$i) {
			$groups[$response->groups[$i]->id] = $response->groups[$i]->name;
		}
		return $groups;

	} catch (ClientException $e) {
	    // throw API exception
	  	// include the achievement id that triggered the error
	  	// direct the user to accredible's support
	  	// dump the achievement id to debug_info
	  	throw new moodle_exception('getgroupserror', 'accredible', 'https://help.accredible.com/hc/en-us');
	}
}


/**
 * Sync the selected course information with a group on Accredible - returns a group ID. Optionally takes a group ID so we can set it and change the assigned group.
 *
 * @param stdClass $course 
 * @param int|null $instance_id
 * @return int $groupid
 */
function sync_course_with_accredible($course, $instance_id = null, $group_id = null) {
	global $DB, $CFG;

	$api = new Api($CFG->accredible_api_key);

	$description = Html2Text\Html2Text::convert($course->summary);
	if(empty($description)){
		$description = "Recipient has compeleted the achievement.";
	}

	// Just use the saved group ID
	if($group_id == null){
		$group_id = $accredible_certificate->groupid;
	}

	// Update an existing
	if(null != $instance_id){
		// get the group id
		$accredible_certificate = $DB->get_record('accredible', array('id'=> $instance_id), '*', MUST_EXIST);

		try {
		    // Update the group
			$group = $api->update_group($group_id, null, $course->fullname, $description, new moodle_url('/course/view.php', array('id' => $course->id)));

			return $group->group->id;
		} catch (ClientException $e) {
		    // throw API exception
		  	// include the achievement id that triggered the error
		  	// direct the user to accredible's support
		  	// dump the achievement id to debug_info
		  	throw new moodle_exception('groupsyncerror', 'accredible', 'https://help.accredible.com/hc/en-us', $course->id, $course->id);
		}
	// making a new group
	} else {
		try {
		    // Make a new Group on Accredible - use a random number to deal with duplicate course names.
			$group = $api->create_group($course->shortname . mt_rand(), $course->fullname, $description, new moodle_url('/course/view.php', array('id' => $course->id)));

			return $group->group->id;
		} catch (ClientException $e) {
		    // throw API exception
		  	// include the achievement id that triggered the error
		  	// direct the user to accredible's support
		  	// dump the achievement id to debug_info
		  	throw new moodle_exception('groupsyncerror', 'accredible', 'https://help.accredible.com/hc/en-us', $course->id, $course->id);
		}
	}
}
