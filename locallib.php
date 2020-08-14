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