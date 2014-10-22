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
 * Certificate module core interaction API
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Accredible <dev@accredible.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');

/**
 * Add certificate instance.
 *
 * @param array $certificate
 * @return array $certificate new certificate object
 */
function certificate_add_instance($post) {
    global $DB;

    foreach ($post->users as $user_id => $issue_certificate) {
        if($issue_certificate) {
            $user = $DB->get_record('user', array('id'=>$user_id));

            $certificate = array();
            $certificate['name'] = $post->name;
            $certificate['achievement_id'] = $post->achievement_id;
            $certificate['description'] = $post->description;
            $certificate['recipient'] = array('name' => fullname($user), 'email'=> $user->email);

            $curl = curl_init('https://api.accredible.com/v1/credentials');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( array('credential' => $certificate) ));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="accredible_secret123"' ) );
            curl_exec($curl);
            curl_close($curl);
        }
    }
}

/**
 * Update certificate instance.
 *
 * @param stdClass $certificate
 * @return stdClass $certificate updated 
 */
function certificate_update_instance($certificate, $api_key) {
    $curl = curl_init('https://api.accredible.com/v1/credentials/'.$certificate->id);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($certificate));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$api_key.'"' ) );
    $result = json_decode( curl_exec($curl) );
    curl_close($curl);
    return $result;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool true if successful
 */
function certificate_delete_instance($id, $api_key) {
    $curl = curl_init('https://api.accredible.com/v1/credentials/'.$certificate->id);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="'.$api_key.'"' ) );
    $result = json_decode( curl_exec($curl) );
    curl_close($curl);
    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param string $api_key - provided by accredible.com
 * @return array[stdClass] $certificates
 */
function certificate_get_issued($api_key) {
    // TODO - don't have this API method yet. Just simulating a response here
    $curl = curl_init('https://api.accredible.com/v1/credentials/10000005');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = json_decode( curl_exec($curl) );
    $cert_array = array( $result );
    curl_close($curl);
    return $cert_array;
}

?>