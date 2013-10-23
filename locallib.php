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
 * Capability Explorer tool functions.
 *
 * @package     tool_capexplorer
 * @copyright   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper function to output a JSON reponse.
 *
 * This function renders the output and exits.
 *
 * @param $options array Array of options to convert to JSON.
 * @param $disabled bool Whether select menu should be disabled (default false).
 */
function tool_capexplorer_render_json($options, $disabled = false) {
    global $OUTPUT;
    $response = array(
        'options' => $options,
        'disabled' => (int)$disabled
    );
    $OUTPUT->header();
    echo json_encode($response);
    $OUTPUT->footer();
    exit;
}

/**
 * Given a context object, return information about all parent contexts
 *
 * @param object $context Context object.
 * @return array Array of parent context info.
 */
function tool_capexplorer_get_parent_context_info($context) {
    global $CFG, $DB;
    $parentcontexts = $context->get_parent_contexts(true);
    $parentcontexts = array_reverse($parentcontexts);

    $out = array();
    foreach ($parentcontexts as $pcontext) {
        $item = new stdClass();
        $item->contextlevel = $pcontext->get_level_name();
        switch ($pcontext->contextlevel) {
        case CONTEXT_SYSTEM:
            $item->instance = get_string('none', 'tool_capexplorer');
            break;
        case CONTEXT_USER:
            $item->instance = format_string($DB->get_field('user',
                $DB->sql_fullname(), array('id' => $pcontext->instanceid)));
            $item->url = new moodle_url('/user/profile.php',
                array('id' => $pcontext->instanceid));
            break;
        case CONTEXT_COURSECAT:
            $item->instance = format_string($DB->get_field('course_categories',
                'name', array('id' => $pcontext->instanceid)));
            $item->url = new moodle_url('/course/index.php',
                array('categoryid' => $pcontext->instanceid));
            break;
        case CONTEXT_COURSE:
            $coursename = format_string($DB->get_field('course', 'fullname',
                array('id' => $pcontext->instanceid)));
            if ($pcontext->instanceid == 1) {
                $item->instance = get_string('xfrontpage', 'tool_capexplorer', $coursename);
            } else {
                $item->instance = $coursename;
            }
            $item->url = new moodle_url('/course/view.php',
                array('id' => $pcontext->instanceid));
            break;
        case CONTEXT_MODULE:
            $sql = "SELECT cm.id,cm.instance,m.name
                FROM {course_modules} cm JOIN {modules} m
                ON m.id = cm.module
                WHERE cm.id = ?";
            $modinfo = $DB->get_record_sql($sql, array($pcontext->instanceid));
            $item->instance = format_string($DB->get_field($modinfo->name, 'name',
                array('id' => $modinfo->instance)));
            $item->url = new moodle_url("/mod/{$modinfo->name}/view.php",
                array('id' => $modinfo->instance));
            break;
        case CONTEXT_BLOCK:
            $blockname = $DB->get_field('block_instances', 'blockname', array('id' => $pcontext->instanceid));
            $item->instance = get_string('pluginname', "block_{$blockname}");
            break;
        }
        $out[] = $item;

    }
    return $out;
}
