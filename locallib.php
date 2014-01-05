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
 * @param array $roles Array of role objects keyed by roleid.
 * @param string $capability A capability to check against.
 * @return array Array of roleid/permission pairs for the specified capability.
 */
function tool_capexplorer_get_system_role_permissions($roles, $capability) {
    global $DB;

    $systemcontext = context_system::instance();
    $systemcontextid = $systemcontext->id;
    $roleids = array_keys($roles);

    list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids);

    // Get the system level role permissions.
    $rolepermissions = $DB->get_records_select_menu('role_capabilities',
        "roleid {$rolesql} AND contextid = ? AND capability = ?",
        array_merge($roleparams, array($systemcontextid, $capability)),
        '', 'roleid, permission'
    );

    return $rolepermissions;
}

/**
 * Given a set of contexts and a set of roles, determine if
 * a specific user is assigned to those roles in those contexts.
 *
 * The output array is a 2D array keyed on contextid then roleid, with
 * boolean true values to indicate a role assignment.
 *
 * @param array $contextids Array of context ids.
 * @param array $roleids Array of role ids.
 * @param int $userid A userid to check for assignments.
 * @return array Sparse array of role assignment data.
 */
function tool_capexplorer_get_role_assignment_info($contextids, $roleids, $userid) {
    global $DB;

    $out = array();

    if (empty($contextids) || empty($roleids)) {
        return $out;
    }

    list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids);
    list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids);

    $sql = "contextid {$contextsql} AND roleid {$rolesql} AND userid = ?";
    $params = array_merge($contextparams, $roleparams, array($userid));
    $rs = $DB->get_recordset_select('role_assignments', $sql, $params);

    foreach ($rs as $record) {
        $out[$record->contextid][$record->roleid] = true;
    }
    $rs->close();

    return $out;
}


/**
 * Check system config to see if the specified user should be assigned any additional
 * roles.
 *
 * The output array is a 2D array keyed on contextid then roleid, with
 * strings as values to indicate a role assignment. The string text describes
 * the name of the config variable that caused the assignment, e.g. 'guestroleid'.
 *
 * @param int $userid A userid to check for automatic assignments.
 * @return array Sparse array of automatic role assignment data.
 */
function tool_capexplorer_get_auto_role_assignment_info($userid) {
    global $CFG;

    $out = array();

    $systemcontext = context_system::instance();
    $out[$systemcontext->id] = array();

    // User is guest user.
    if (isguestuser($userid)) {
        // Assign guest role in system context.
        if (!empty($CFG->guestroleid)) {
            $out[$systemcontext->id][$CFG->guestroleid] = 'guestroleid';
        }
    } else {
        // Treat them as if they are logged in and give them the default role
        // in the system context.
        if (!empty($CFG->defaultuserroleid)) {
            $out[$systemcontext->id][$CFG->defaultuserroleid] = 'defaultuserroleid';
        }
    }

    return $out;
}


/**
 * Given a set of contexts and a set of roles, determine if any roles override
 * a specific capability in any of the contexts.
 *
 * The output array is a 2D array keyed on contextid then roleid, with
 * values of the permission constant for that role and context, or null
 * if nothing set.
 *
 * @param array $contextids Array of context ids.
 * @param array $roleids Array of role ids.
 * @param string $capability A capability to check against.
 * @param bool $excludesystemcontext If true, exclude system level permissions (overrides only).
 * @return array Array of role override info.
 */
function tool_capexplorer_get_role_override_info($contextids, $roleids, $capability, $excludesystemcontext = true) {
    global $DB;

    // Build a 2D array to store results.
    $out = array();
    foreach ($contextids as $contextid) {
        $out[$contextid] = array();
        foreach ($roleids as $roleid) {
            $out[$contextid][$roleid] = null;
        }
    }

    if (empty($contextids) || empty($roleids)) {
        return $out;
    }

    list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids);
    list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids);

    $sql = "contextid {$contextsql} AND roleid {$rolesql} AND capability = ?";
    $params = array_merge($contextparams, $roleparams, array($capability));

    // Exclude the system context if specified.
    if ($excludesystemcontext) {
        $systemcontext = context_system::instance();
        $sql .= " AND contextid <> ?";
        $params = array_merge($params, array($systemcontext->id));
    }

    $rs = $DB->get_recordset_select('role_capabilities', $sql, $params);
    foreach ($rs as $record) {
        $out[$record->contextid][$record->roleid] = $record->permission;
    }
    $rs->close();

    return $out;
}
