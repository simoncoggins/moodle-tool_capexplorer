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
 * Return a list of blocks in a specific course.
 *
 * @package     tool_capexplorer
 * @copyright   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/capexplorer/locallib.php");

// We are fetching blocks attached to the following contextlevel and instanceid.
$contextlevel = required_param('contextlevel', PARAM_INT);
$instanceid   = optional_param('instanceid', 0, PARAM_INT);

require_login();

if (!has_capability('tool/capexplorer:view', context_system::instance())) {
    print_error('nopermissiontoshow', 'error');
}

$contextinstance = context_helper::get_class_for_level($contextlevel);
$context = $contextinstance::instance($instanceid);

$sql = "SELECT
        bi.id,
        bi.blockname
    FROM {block_instances} bi
    JOIN {block} b ON bi.blockname = b.name
    WHERE
    bi.parentcontextid = ?
";
$params = array($context->id);

$blockinstances = $DB->get_records_sql($sql, $params);

$options = array();
foreach ($blockinstances as $blockinstance) {
    $options[$blockinstance->id] = get_string('pluginname', 'block_' . $blockinstance->blockname);
}

tool_capexplorer_render_json($options);

