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
 * Return a list of users, filtered by search criteria.
 *
 * @package     tool_capexplorer
 * @author      Simon Coggins
 * @copyright   2013 Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');

$search = required_param('search', PARAM_TEXT);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/admin/tool/capexplorer/ajax/getusers.php');

require_login();
require_sesskey();

require_capability('tool/capexplorer:view', context_system::instance());

$sqlfullname = $DB->sql_fullname('u.firstname', 'u.lastname');
$autocompletefields = $DB->sql_concat_join("', '", array($sqlfullname, 'u.username', 'u.email'));

$sql = "SELECT u.id, u.username, {$autocompletefields} AS autocompletestr
    FROM {user} u
    WHERE
    u.deleted <> 1 AND "  . $DB->sql_like($autocompletefields, '?', false) . "
    ORDER BY {$sqlfullname}";
$params = array('%' . $DB->sql_like_escape($search) . '%');

$users = $DB->get_records_sql($sql, $params);

$OUTPUT->header();
echo json_encode(array_values($users));
$OUTPUT->footer();
