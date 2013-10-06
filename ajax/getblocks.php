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

$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();

if (!has_capability('tool/capexplorer:view', context_system::instance())) {
    print_error('nopermissiontoshow', 'error');
}

if (!$courseid) {
    $options = array(
        '0' => get_string('chooseacoursefirst', 'tool_capexplorer')
    );
    tool_capexplorer_render_json($options, true);
}

$blockinstances = get_block_instances($courseid);

if (empty($blockinstances)) {
    $options = array(
        '0' => get_string('noblocksfound', 'tool_capexplorer')
    );
    tool_capexplorer_render_json($options, true);
}

$options = array(
    '0' => get_string('chooseablock', 'tool_capexplorer')
);
foreach ($blockinstances as $blockinstance) {
    $options[$blockinstance->id] = $blockinstance->blockname;
}

tool_capexplorer_render_json($options);

/**
 * Return a list of blocks used in a particular course.
 *
 * @param $courseid int ID of the course.
 * @return array Array containing block instance ids/names.
 */
function get_block_instances($courseid) {
    global $DB;
    $context = context_course::instance($courseid);

    // Get blocks in this courses context, and any in parent contexts
    // if showinsubcontexts is set to 1.
    $contexttest = 'bi.parentcontextid = :contextid';
    $parentcontextparams = array();
    $parentcontextids = $context->get_parent_context_ids();
    if ($parentcontextids) {
        list($parentcontexttest, $parentcontextparams) =
                $DB->get_in_or_equal($parentcontextids, SQL_PARAMS_NAMED, 'parentcontext');
        $contexttest = "($contexttest OR (bi.showinsubcontexts = 1 AND bi.parentcontextid $parentcontexttest))";
    }

    $params = array(
        'contextid' => $context->id,
    );
    $sql = "SELECT
            bi.id,
            bi.blockname
        FROM {block_instances} bi
        JOIN {block} b ON bi.blockname = b.name
        WHERE
        $contexttest
    ";
    return $DB->get_records_sql($sql, $params + $parentcontextparams);
}
