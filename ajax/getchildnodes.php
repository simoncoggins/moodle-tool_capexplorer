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
 * Return a node's children as an object that can be used as in the JS
 * node children property after converting to JSON.
 *
 * @package     tool_capexplorer
 * @author   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/capexplorer/locallib.php");
require_once($CFG->dirroot . "/{$CFG->admin}/tool/capexplorer/treelib.php");

$nodetype = required_param('nodetype', PARAM_ALPHA);
$instanceid   = optional_param('instanceid', 0, PARAM_INT);

require_login();

if (!has_capability('tool/capexplorer:view', context_system::instance())) {
    print_error('nopermissiontoshow', 'error');
}

$OUTPUT->header();
echo json_encode(tool_capexplorer_get_child_nodes($nodetype, $instanceid));
$OUTPUT->footer();

