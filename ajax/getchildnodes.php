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
 * Return a node's children as an object.
 *
 * This can be used as in the JS node children property after converting to JSON.
 *
 * @package     tool_capexplorer
 * @author      Simon Coggins
 * @copyright   2013 Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_capexplorer;

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');

$nodetype = required_param('nodetype', PARAM_ALPHA);
$instanceid   = optional_param('instanceid', 0, PARAM_INT);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/admin/tool/capexplorer/ajax/getchildnodes.php');

require_login();
require_sesskey();

require_capability('tool/capexplorer:view', \context_system::instance());

$OUTPUT->header();
echo json_encode(tree::get_child_nodes($nodetype, $instanceid));
$OUTPUT->footer();

