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
 * Given a context object, return formatted information about it.
 *
 * @param object $context Context object.
 * @return object Object containing formatted info about context.
 */
function tool_capexplorer_get_context_info($context) {
    global $DB;

        $item = new stdClass();
        $item->contextlevel = $context->get_level_name();
        switch ($context->contextlevel) {
        case CONTEXT_SYSTEM:
            $item->instance = get_string('none', 'tool_capexplorer');
            break;
        case CONTEXT_USER:
            $item->instance = format_string($DB->get_field('user',
                $DB->sql_fullname(), array('id' => $context->instanceid)));
            $item->url = new moodle_url('/user/profile.php',
                array('id' => $context->instanceid));
            break;
        case CONTEXT_COURSECAT:
            $item->instance = format_string($DB->get_field('course_categories',
                'name', array('id' => $context->instanceid)));
            $item->url = new moodle_url('/course/index.php',
                array('categoryid' => $context->instanceid));
            break;
        case CONTEXT_COURSE:
            $coursename = format_string($DB->get_field('course', 'fullname',
                array('id' => $context->instanceid)));
            if ($context->instanceid == 1) {
                $item->instance = get_string('xfrontpage', 'tool_capexplorer', $coursename);
            } else {
                $item->instance = $coursename;
            }
            $item->url = new moodle_url('/course/view.php',
                array('id' => $context->instanceid));
            break;
        case CONTEXT_MODULE:
            $sql = "SELECT cm.id,cm.instance,m.name
                FROM {course_modules} cm JOIN {modules} m
                ON m.id = cm.module
                WHERE cm.id = ?";
            $modinfo = $DB->get_record_sql($sql, array($context->instanceid));
            $item->instance = format_string($DB->get_field($modinfo->name, 'name',
                array('id' => $modinfo->instance)));
            $item->url = new moodle_url("/mod/{$modinfo->name}/view.php",
                array('id' => $modinfo->instance));
            break;
        case CONTEXT_BLOCK:
            $blockname = $DB->get_field('block_instances', 'blockname', array('id' => $context->instanceid));
            $item->instance = get_string('pluginname', "block_{$blockname}");
            break;
        }

    return $item;
}


/**
 * Given a context object, return information about all parent contexts
 *
 * @param object $context Context object.
 * @return array Array of parent context info.
 */
function tool_capexplorer_get_parent_context_info($context) {
    global $DB;
    $parentcontexts = $context->get_parent_contexts(true);
    $parentcontexts = array_reverse($parentcontexts);

    $out = array();
    foreach ($parentcontexts as $pcontext) {
        $out[] = tool_capexplorer_get_context_info($pcontext);
    }
    return $out;
}

/**
 * Given a set of contexts and a set of roles, determine if any roles override
 * a specific user is assigned to those roles in those contexts.
 *
 * The output array is a 2D array keyed on roleid then contextid, with
 * values of the permission constant for the role and capability if assigned
 * to the user in that context, or null otherwise.
 *
 * @param array $contextids Array of context ids.
 * @param array $roleids Array of role ids.
 * @param int $userid A userid to check for assignments.
 * @param string $capability A capability to check against.
 * @return array Array of role assignment info.
 */
function tool_capexplorer_get_role_assignment_info($contextids, $roleids, $userid, $capability) {
    global $DB;

    if (empty($contextids) || empty($roleids)) {
        return false;
    }

    $systemcontext = context_system::instance();
    $systemcontextid = $systemcontext->id;

    list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids);
    list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids);

    // Get the system level role permissions.
    $rolepermissions = $DB->get_records_select_menu('role_capabilities',
        "roleid {$rolesql} AND contextid = ? AND capability = ?",
        array_merge($roleparams, array($systemcontextid, $capability)),
        '', 'roleid, permission'
    );

    // Build a 2D array to store results.
    $out = array();
    foreach ($roleids as $roleid) {
        $out[$roleid] = array();
        foreach ($contextids as $contextid) {
            $out[$roleid][$contextid] = null;
        }
    }

    // Exclude the system context since we can't override at that level.
    $sql = "contextid {$contextsql} AND roleid {$rolesql} AND userid = ?";
    $params = array_merge($contextparams, $roleparams, array($userid));
    $rs = $DB->get_recordset_select('role_assignments', $sql, $params);

    foreach ($rs as $record) {
        $out[$record->roleid][$record->contextid] = isset($rolepermissions[$roleid]) ? $rolepermissions[$roleid] : null;
    }
    $rs->close();

    return $out;
}

/**
 * Given a set of contexts and a set of roles, determine if any roles override
 * a specific capability in any of the contexts.
 *
 * The output array is a 2D array keyed on roleid then contextid, with
 * values of the permission constant for that role and context, or null
 * if nothing set.
 *
 * @param array $contextids Array of context ids.
 * @param array $roleids Array of role ids.
 * @param string $capability A capability to check against.
 * @return array Array of role override info.
 */
function tool_capexplorer_get_role_override_info($contextids, $roleids, $capability) {
    global $DB;

    if (empty($contextids) || empty($roleids)) {
        return false;
    }

    // Build a 2D array to store results.
    $out = array();
    foreach ($roleids as $roleid) {
        $out[$roleid] = array();
        foreach ($contextids as $contextid) {
            $out[$roleid][$contextid] = null;
        }
    }

    list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids);
    list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids);

    // Exclude the system context since we can't override at that level.
    $systemcontext = context_system::instance();
    $systemcontextid = $systemcontext->id;
    $sql = "contextid {$contextsql} AND roleid {$rolesql} AND capability = ? AND contextid <> ?";
    $params = array_merge($contextparams, $roleparams, array($capability, $systemcontextid));
    $rs = $DB->get_recordset_select('role_capabilities', $sql, $params);
    foreach ($rs as $record) {
        $out[$record->roleid][$record->contextid] = $record->permission;
    }
    $rs->close();

    return $out;
}
