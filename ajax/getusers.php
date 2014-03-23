<?php
// This file is part of Moodle - http://moodle.org/
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
 * Return a list of users, optionally filtered by search criteria.
 *
 * @package     tool_capexplorer
 * @copyright   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/capexplorer/locallib.php");

$search = required_param('search', PARAM_ALPHANUMEXT);

require_login();

if (!has_capability('tool/capexplorer:view', context_system::instance())) {
    print_error('nopermissiontoshow', 'error');
}

$sqlfullname = $DB->sql_fullname('u.firstname', 'u.lastname');
$autocompletefields = $DB->sql_concat_join("', '", array($sqlfullname, 'u.username', 'u.email'));

$sql = "SELECT u.id, u.username, {$autocompletefields} AS autocompletestr,
    {$sqlfullname} AS name
    FROM {user} u
    WHERE
    u.deleted <> 1 AND "  . $DB->sql_like($autocompletefields, '?') . "
    ORDER BY {$sqlfullname}";

$users = $DB->get_records_sql($sql, array("%{$search}%"));

$OUTPUT->header();
echo json_encode(array_values($users));
$OUTPUT->footer();
