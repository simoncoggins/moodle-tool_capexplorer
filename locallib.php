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
 * Given a set of contexts, determine if a specific user is assigned to
 * any roles in those contexts.
 *
 * The output array is a 2D array keyed on contextid then roleid, with
 * boolean true values to indicate a role assignment.
 *
 * @param array $contexts Array of context objects.
 * @param int $userid A userid to check for assignments.
 * @return array Sparse array of role assignment data.
 */
function tool_capexplorer_get_role_assignment_info($contexts, $userid) {
    global $DB;

    $out = array();

    if (empty($contexts)) {
        return $out;
    }

    $contextids = array_map(function($context) {return $context->id;}, $contexts);
    list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids);

    $sql = "contextid {$contextsql} AND userid = ?";
    $params = array_merge($contextparams, array($userid));
    $rs = $DB->get_recordset_select('role_assignments', $sql, $params);

    foreach ($rs as $record) {
        if (!isset($out[$record->contextid])) {
            $out[$record->contextid] = array();
        }
        $out[$record->contextid][$record->roleid] = true;
    }
    $rs->close();

    return $out;
}

/**
 * Given a set of contextids and roleids and some override data as generated
 * by {@link tool_capexplorer_get_role_override_info()}, return an array
 * keyed on roleids with the per-role result of calculating each role's
 * overall permission.
 *
 * @param array $contextids Array of integer context ids.
 * @param array $roleids Array of integer role ids.
 * @param array $overridedata 2D array of override data for a set of contexts/roles.
 *
 * @return array Array with roleids as keys, merged permissions for each role as values.
 */
function tool_capexplorer_merge_permissions_across_contexts($contextids, $roleids, $overridedata) {
    // Each role starts with not set.
    $roletotals = array_fill_keys($roleids, null);
    foreach ($contextids as $contextid) {
        // Go through each context, starting from least specific.
        foreach ($roleids as $roleid) {
            // Aggregate to get overall permission for the role in the lowest context.
            $roletotals[$roleid] = tool_capexplorer_merge_permissions(
                $roletotals[$roleid],
                $overridedata[$contextid][$roleid]
            );
        }
    }
    return $roletotals;
}


/**
 * Given a pair of permissions, combine them using the appropriate rules, returning
 * a single permission.
 *
 * @param int $permission1 The first (least specific) permission constant (CAP_*).
 * @param int $permission2 The second (more specific) permission constant (CAP_*).
 * @return int The calculated combined permission.
 */
function tool_capexplorer_merge_permissions($permission1, $permission2) {

    // Prohibit always wins.
    if ($permission1 == CAP_PROHIBIT || $permission2 == CAP_PROHIBIT) {
        return CAP_PROHIBIT;
    }
    // If one permission not set, return the other.
    // This will return not set if neither is set which is correct.
    if ($permission2 == CAP_INHERIT) {
        return $permission1;
    }
    if ($permission1 == CAP_INHERIT) {
        return $permission2;
    }
    // Otherwise return the most specific one.
    return $permission2;
}


/**
 * Given an array of role total permissions, determine if the user should
 * be granted the capability or not. It is assumed that the user is assigned
 * to all roles in the specified context or above.
 *
 * Note only PROHIBIT and ALLOW are of any consequence when aggregating here,
 * PREVENT is only useful for nullifying a less specific ALLOW within a role.
 * Only one ALLOW is required but all roles must be checked for PROHIBITS.
 *
 * @param array $roletotals Array keyed on roleid with role total permission as value.
 * @return bool True if the user should be granted the capability based on the totals.
 */
function tool_capexplorer_merge_permissions_across_roles($roletotals) {
    $status = false;
    foreach ($roletotals as $roleid => $permission) {
        switch ($permission) {
        case CAP_PROHIBIT:
            // Any prohibit prevents access.
            return false;
        case CAP_ALLOW:
            // Any allow gives access (as long as there isn't a PROHIBIT).
            $status = true;
        }
    }
    return $status;
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
 * Given a manual and automatic assignment arrays (as generated by
 * {@link tool_capexplorer_get_role_assignment_info()} and
 * {@link tool_capexplorer_get_auto_role_assignment_info()}, return an array
 * of role objects for each role that has an assignment.
 */
function tool_capexplorer_get_assigned_roles($manualassignments, $autoassignments) {
    global $DB;
    $roleids = array();

    // Collect manual assigned role ids.
    foreach ($manualassignments as $contextid => $roledata) {
        foreach ($roledata as $roleid => $assigned) {
            if ($assigned) {
                $roleids[$roleid] = $roleid;
            }
        }
    }
    // Collect automatically assigned role ids.
    foreach ($autoassignments as $contextid => $roledata) {
        foreach ($roledata as $roleid => $assigned) {
            if ($assigned) {
                $roleids[$roleid] = $roleid;
            }
        }
    }

    if (empty($roleids)) {
        return array();
    }

    list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids);
    return $DB->get_records_select('role', "id $rolesql", $roleparams);
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

/**
 * Local implementation of {@link has_capability()} using the logic implemented
 * in this module.
 *
 * Useful for testing results match real method.
 *
 * @param string $capability The capability to check.
 * @param context $context The context to check the capability in.
 * @param int $userid The ID of the user to check.
 * @return bool True if the user should be granted the capability in the specified context.
 */
function tool_capexplorer_has_capability($capability, $context, $userid) {
    // Obtain all parent contexts.
    $parentcontexts = $context->get_parent_contexts(true);
    $contexts = array_reverse($parentcontexts);

    // Calculate role assignments.
    $manualassignments = tool_capexplorer_get_role_assignment_info($contexts, $userid);
    $autoassignments = tool_capexplorer_get_auto_role_assignment_info($userid);
    $assignedroles = tool_capexplorer_get_assigned_roles($manualassignments, $autoassignments);

    // Calculate any role overrides.
    $roleids = array_keys($assignedroles);
    $contextids = array_map(function($context) {return $context->id;}, $contexts);
    $overridedata = tool_capexplorer_get_role_override_info($contextids, $roleids, $capability, false);

    // Aggregate role totals.
    $roletotals = tool_capexplorer_merge_permissions_across_contexts(
        $contextids,
        $roleids,
        $overridedata
    );

    // Aggregate across roles.
    $overallresult = tool_capexplorer_merge_permissions_across_roles($roletotals);

    // Return result.
    return $overallresult;
}
