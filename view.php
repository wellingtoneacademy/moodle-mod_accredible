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
 * Handles viewing a certificate
 *
 * @package    mod
 * @subpackage accredible
 * @copyright  Accredible <dev@accredible.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("$CFG->dirroot/mod/accredible/lib.php");

$id = required_param('id', PARAM_INT);    // Course Module ID

if (!$cm = get_coursemodule_from_id('accredible', $id)) {
    print_error('Course Module ID was incorrect');
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('course is misconfigured');
}
if (!$accredible_certificate = $DB->get_record('accredible', array('id'=> $cm->instance))) {
    print_error('course module is incorrect');
}

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/accredible:view', $context);

// Initialize $PAGE, compute blocks
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/accredible/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($accredible_certificate->name));
$PAGE->set_heading(format_string($course->fullname));

// Get array of certificates
$certificates = accredible_get_issued($accredible_certificate->achievementid);

$table = new html_table();
// TODO - language tags
$table->head  = array ("ID", "Certificate URL");

foreach ($certificates as $certificate) {
    $table->data[] = array ( $certificate->id, "<a href='https://accredible.com/$certificate->id'>https://accredible.com/$certificate->id</a>" );
}

echo $OUTPUT->header();
echo "<h3>Certificates for ".$course->fullname."</h3>";
echo "<h5>Achievement ID: ".$accredible_certificate->achievementid."</h5>";
echo '<br />';
echo html_writer::table($table);
echo $OUTPUT->footer($course);
