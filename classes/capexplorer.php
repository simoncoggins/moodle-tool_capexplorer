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
 * @author      Simon Coggins
 * @copyright   2013 Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_capexplorer;

defined('MOODLE_INTERNAL') || die();

/**
 * Main class for this admin tool.
 */
class capexplorer {

    /**
     * Given a context object, return formatted information about it.
     *
     * @param object $context Context object.
     * @return object Object containing formatted info about context.
     */
    public static function get_context_info($context) {
        global $DB;

        $item = new \stdClass();
        $item->contextlevel = $context->get_level_name();
        switch ($context->contextlevel) {
        case CONTEXT_SYSTEM:
            $item->instance = get_string('none', 'tool_capexplorer');
            break;
        case CONTEXT_USER:
            $item->instance = format_string($DB->get_field('user',
                $DB->sql_fullname(), array('id' => $context->instanceid)));
            $item->url = new \moodle_url('/user/profile.php',
                array('id' => $context->instanceid));
            break;
        case CONTEXT_COURSECAT:
            $item->instance = format_string($DB->get_field('course_categories',
                'name', array('id' => $context->instanceid)));
            $item->url = new \moodle_url('/course/index.php',
                array('categoryid' => $context->instanceid));
            break;
        case CONTEXT_COURSE:
            $coursename = format_string($DB->get_field('course', 'fullname',
                array('id' => $context->instanceid)));
            if ($context->instanceid == SITEID) {
                $item->instance = get_string('xfrontpage', 'tool_capexplorer', $coursename);
            } else {
                $item->instance = $coursename;
            }
            $item->url = new \moodle_url('/course/view.php',
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
            $item->url = new \moodle_url("/mod/{$modinfo->name}/view.php",
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
    public static function get_parent_context_info($context) {
        $parentcontexts = $context->get_parent_contexts(true);
        $parentcontexts = array_reverse($parentcontexts);
        return array_map(array('self', 'get_context_info'), $parentcontexts);
    }


    /**
     * Given a set of contexts, determine if the specified user is assigned to
     * any roles in those contexts.
     *
     * The output array is a 2D array keyed on contextid then roleid, with
     * boolean true values to indicate a role assignment.
     *
     * @param array $contexts Array of context objects.
     * @param int $userid A userid to check for assignments.
     * @return array Sparse array of role assignment data.
     */
    public static function get_role_assignment_info($contexts, $userid) {
        global $DB;

        $out = array();

        if (empty($contexts)) {
            return $out;
        }

        $contextids = array_map(function($context) {
            return $context->id;
        }, $contexts);
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
     * by {@link self::get_role_override_info()}, return an array
     * keyed on roleids with the per-role result of calculating each role's
     * overall permission.
     *
     * @param array $contextids Array of integer context ids.
     * @param array $roleids Array of integer role ids.
     * @param array $overridedata 2D array of override data for a set of contexts/roles.
     *
     * @return array Array with roleids as keys, merged permissions for each role as values.
     */
    public static function merge_permissions_across_contexts($contextids, $roleids, $overridedata) {
        // Each role starts with not set.
        $roletotals = array_fill_keys($roleids, null);
        foreach ($contextids as $contextid) {
            // Go through each context, starting from least specific.
            foreach ($roleids as $roleid) {
                // Aggregate to get overall permission for the role in the lowest context.
                $roletotals[$roleid] = self::merge_permissions(
                    $roletotals[$roleid],
                    $overridedata[$contextid][$roleid]
                );
            }
        }
        return $roletotals;
    }


    /**
     * Check system config to see if the specified user should be assigned any additional
     * roles.
     *
     * The output array is a 2D array keyed on contextid then roleid, with
     * strings as values to indicate a role assignment. The string text describes
     * the name of the config variable that caused the assignment, e.g. 'guestroleid'.
     *
     * Currently all auto assignments assign in the system context so this is the only
     * context filled.
     *
     * @param int $userid A userid to check for automatic assignments.
     * @return array Sparse array of automatic role assignment data.
     */
    public static function get_auto_role_assignment_info($userid) {
        global $CFG;

        $out = array();

        $systemcontext = \context_system::instance();
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
     * Determine if a particular role ID is assigned automatically at the system context
     * due to site policies.
     *
     * @param int $roleid The role ID to check.
     * @return bool True if the role is automatically assigned.
     */
    public static function role_is_auto_assigned($roleid) {
        global $CFG;
        return in_array($roleid, array($CFG->guestroleid, $CFG->defaultuserroleid));
    }

    /**
     * Determine if a particular role ID is manually assigned in any of the
     * parent contexts (based on $manualassignments array that is passed in).
     *
     * @param int $roleid The role ID to check.
     * @param array $manualassignments The assignments array as generated by
     *              {@link self::get_role_assignment_info()}.
     * @return bool True if the role is automatically assigned.
     */
    public static function role_is_manually_assigned($roleid, $manualassignments) {
        foreach ($manualassignments as $contextid => $roleinfo) {
            if (array_key_exists($roleid, $roleinfo)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Given a manual and automatic assignment arrays (as generated by
     * {@link self::get_role_assignment_info()} and
     * {@link self::get_auto_role_assignment_info()}, return an array
     * of role objects for each role that has an assignment.
     *
     * @param array $manualassignments 2D array of manual assignment data.
     * @param array $autoassignments 2D array of auto assignment data.
     *
     * @return array Array of role objects for roles assigned to this user.
     */
    public static function get_assigned_roles($manualassignments, $autoassignments) {
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
     * @return array Array of role override info.
     */
    public static function get_role_override_info($contextids, $roleids, $capability) {
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

        $rs = $DB->get_recordset_select('role_capabilities', $sql, $params);
        foreach ($rs as $record) {
            $out[$record->contextid][$record->roleid] = $record->permission;
        }
        $rs->close();

        return $out;
    }


    /**
     * Given a pair of permissions, combine them using the appropriate rules, returning
     * a single permission.
     *
     * @param int $permission1 The first (least specific) permission constant (CAP_*).
     * @param int $permission2 The second (more specific) permission constant (CAP_*).
     * @return int The calculated combined permission.
     */
    public static function merge_permissions($permission1, $permission2) {

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
     * Given an array of role total permissions, as returned by
     * {@link self::merge_permissions_across_contexts} determine if
     * the user should be granted the capability or not. It is assumed that the
     * user is assigned to all roles provided in the specified context or above.
     *
     * Note only PROHIBIT and ALLOW are of any consequence when aggregating here,
     * PREVENT is only useful for nullifying a less specific ALLOW within a role.
     * Only one ALLOW is required but all roles must be checked for PROHIBITS.
     *
     * @param array $roletotals Array keyed on roleid with role total permission as value.
     * @return bool True if the user should be granted the capability based on the totals.
     */
    public static function merge_permissions_across_roles($roletotals) {
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
     * Local implementation of {@link has_capability()} using the logic implemented
     * in this tool.
     *
     * Useful for testing results match real method.
     *
     * @param string $capability The capability to check.
     * @param context $context The context to check the capability in.
     * @param int $userid The ID of the user to check.
     *
     * @return bool True if the user should be granted the capability in the specified context.
     */
    public static function has_capability($capability, $context, $userid) {
        // Obtain all parent contexts.
        $parentcontexts = $context->get_parent_contexts(true);
        $contexts = array_reverse($parentcontexts);

        // Calculate role assignments.
        $manualassignments = self::get_role_assignment_info($contexts, $userid);
        $autoassignments = self::get_auto_role_assignment_info($userid);
        $assignedroles = self::get_assigned_roles($manualassignments, $autoassignments);

        // Calculate any role overrides.
        $roleids = array_keys($assignedroles);
        $contextids = array_map(function($context) {
            return $context->id;
        }, $contexts);
        $overridedata = self::get_role_override_info($contextids, $roleids, $capability);

        // Aggregate role totals.
        $roletotals = self::merge_permissions_across_contexts(
            $contextids,
            $roleids,
            $overridedata
        );

        // Aggregate across roles.
        $overallresult = self::merge_permissions_across_roles($roletotals);

        // Return result.
        return $overallresult;
    }

    /**
     * Test to see if has_capability() will short-circuit the normal process
     * and deny access to the user. This function returns true if that will occur
     * for this combination of user/capability, false otherwise.
     *
     * This will occur if the user is guest/not logged in AND the capability
     * is "risky". "Risky" means a 'write' capability or one with a risk of XSS,
     * dataloss or site config.
     *
     * @param string $capability The capability being checked.
     * @param int $userid The ID of the user being checked.
     *
     * @return bool True if the user is guest and the capability is "risky".
     */
    public static function is_guest_access_blocked($capability, $userid) {
        $capinfo = get_capability_info($capability);
        if (($capinfo->captype === 'write') or ($capinfo->riskbitmask & (RISK_XSS | RISK_CONFIG | RISK_DATALOSS))) {
            if (isguestuser($userid) or $userid == 0) {
                return true;
            }
        }
        return false;
    }

}
