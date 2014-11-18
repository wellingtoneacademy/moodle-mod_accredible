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
 * This file keeps track of upgrades to the certificate module
 *
 * @package    mod
 * @subpackage accredible
 * @copyright  Accredible <dev@accredible.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_accredible_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2014111800) {

        // Changing type of field description on table accredible to text.
        $table = new xmldb_table('accredible');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'achievementid');

        // Launch change of type for field description.
        $dbman->change_field_type($table, $field);

        // Accredible savepoint reached.
        upgrade_mod_savepoint(true, 2014111800, 'accredible');
    }

    return true;
}
