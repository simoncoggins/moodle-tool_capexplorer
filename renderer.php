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
 * Output rendering of Capability Explorer admin tool
 *
 * @package    tool_capexplorer
 * @author     Simon Coggins
 * @copyright  2013 Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Rendering methods for the tool widgets
 */
class tool_capexplorer_renderer extends plugin_renderer_base {

    /**
     * Renders table of parent contexts.
     *
     * @param array $parentcontexts Array of contexts to be displayed.
     * @return string HTML of the table.
     */
    public function print_parent_context_table($parentcontexts) {
        $html = '';
        $table = new html_table();
        $table->head = array(
            get_string('contextlevel', 'tool_capexplorer'),
            get_string('instancename', 'tool_capexplorer')
        );
        $table->colclasses = array(
            'contextlevel',
            'instancename'
        );
        $table->data = array();
        foreach ($parentcontexts as $context) {
            $instance = isset($context->url) ? html_writer::link($context->url, $context->instance) : $context->instance;
            $row = new html_table_row(array(
                $context->contextlevel,
                $instance
            ));
            $table->data[] = $row;
        }
        $html .= html_writer::table($table);
        return $html;
    }


    /**
     * Displays a tables showing the role permissions for a particular capability for
     * a set of roles in a set of contexts.
     *
     * @param array $contexts An array of context objects.
     * @param array $roles An array of role objects.
     * @param string $capability A capability to display results for.
     * @param bool $includetotals If true include a row of role totals.
     * @param bool $includechangelinks If true include links to change the settings.
     *
     * @return string HTML to display the table.
     */
    public function print_role_permission_and_overrides_table($contexts, $roles, $capability,
        $includetotals = true, $includechangelinks = true) {

        $roleids = array_keys($roles);
        $contextids = array_map(function($context) {
            return $context->id;
        }, $contexts);
        $overridedata = \tool_capexplorer\capexplorer::get_role_override_info($contextids, $roleids, $capability);

        if ($includetotals) {
            $roletotals = \tool_capexplorer\capexplorer::merge_permissions_across_contexts(
                $contextids,
                $roleids,
                $overridedata
            );
        }

        $html = '';
        $table = new html_table();
        $table->head = array(
            get_string('contextlevel', 'tool_capexplorer'),
            get_string('instancename', 'tool_capexplorer'),
        );
        $table->colclasses = array(
            'contextlevel',
            'instancename',
        );
        foreach ($roles as $role) {
            $table->head[] = role_get_name($role);
            $table->colclasses[] = 'role-' . $role->id;
        }
        $table->data = array();

        $systemcontext = context_system::instance();
        $systemcontextid = $systemcontext->id;

        foreach ($contexts as $context) {
            $contextid = $context->id;
            $issystemcontext = ($contextid == $systemcontextid);
            $contextinfo = \tool_capexplorer\capexplorer::get_context_info($context);
            $instance = isset($contextinfo->url) ? html_writer::link($contextinfo->url,
                $contextinfo->instance) : $contextinfo->instance;
            $row = array($contextinfo->contextlevel, $instance);
            $overridableroles = get_overridable_roles($context);
            foreach ($roles as $role) {
                $roleid = $role->id;
                $cell = $this->print_permission_value($overridedata[$contextid][$roleid]);

                if ($issystemcontext) {
                    // Role definition.

                    $url = new moodle_url('/admin/roles/define.php',
                        array('action' => 'edit', 'roleid' => $roleid));
                    if (has_capability('moodle/role:manage', $systemcontext)) {
                        $error = '';
                    } else {
                        $error = 'nopermtodefinerole';
                    }
                } else {
                    // Role override.

                    $url = new moodle_url('/admin/roles/override.php',
                        array('contextid' => $contextid, 'roleid' => $roleid));

                    // Get capabilities associated with this context level.
                    $contextcaps = $context->get_capabilities();
                    // See if capability being checked is one of them.
                    $result = array_filter($contextcaps,
                        function($contextcap) use ($capability) {
                            return ($contextcap->name == $capability);
                        }
                    );
                    if (empty($result)) {
                        $error = 'notoverridable';
                    } else if (!array_key_exists($roleid, $overridableroles)) {
                        $error = 'nopermtooverride';
                    } else {
                        $error = '';
                    }
                }

                if ($includechangelinks) {
                    if (empty($error)) {
                        $cell .= $this->print_change_link($url);
                    } else {
                        $cell .= $this->print_message_with_help($error);
                    }
                }

                $row[] = $cell;

            }
            $table->data[] = new html_table_row($row);
        }
        if ($includetotals && count($roletotals)) {
            $cell1 = new html_table_cell();
            $cell1->text = html_writer::tag('strong', get_string('roletotals', 'tool_capexplorer'));
            $cell1->colspan = 2;
            $row = new html_table_row();
            $row->cells[] = $cell1;
            foreach ($roles as $role) {
                $roletotal = $roletotals[$role->id];
                $cell = new html_table_cell();
                $cell->text = html_writer::tag('strong', $this->print_permission_value($roletotal));
                $row->cells[] = $cell;
            }
            $table->data[] = $row;
        }
        $html .= html_writer::table($table);
        return $html;
    }

    /**
     * Displays a tables showing the role assignments for a particular user for
     * a set of roles in a set of contexts.
     *
     * @param array $contexts An array of context objects.
     * @param array $roles An array of role objects.
     * @param array $manualassignments 2D array as output by {@link \tool_capexplorer\capexplorer::get_role_assignment_info()}
     * @param array $autoassignments 2D array as output by {@link \tool_capexplorer\capexplorer::get_auto_role_assignment_info()}
     *
     * @return string HTML to display the table.
     */
    public function print_role_assignment_table($contexts, $roles, $manualassignments, $autoassignments) {
        $roleids = array_keys($roles);
        $contextids = array_map(function($context) {
            return $context->id;
        }, $contexts);
        $systemcontext = context_system::instance();
        $hassiteconfig = has_capability('moodle/site:config', $systemcontext);

        $html = '';
        $table = new html_table();
        $table->data = array();

        $cell = new html_table_cell();
        $cell->text = get_string('contextlevel', 'tool_capexplorer');
        $cell->header = true;
        $cell2 = new html_table_cell();
        $cell2->text = get_string('instancename', 'tool_capexplorer');
        $cell2->header = true;
        $rolerow = array($cell, $cell2);
        $assignrow = array('', '');

        foreach ($roles as $role) {
            $autoassigned = \tool_capexplorer\capexplorer::role_is_auto_assigned($role->id);
            $manualassigned = \tool_capexplorer\capexplorer::role_is_manually_assigned($role->id, $manualassignments);

            $rolecell = new html_table_cell();
            $rolecell->text = role_get_name($role);
            $rolecell->header = true;
            if ($autoassigned && $manualassigned) {
                $rolecell->colspan = 2;
            }
            $rolerow[] = $rolecell;

            if ($manualassigned) {
                $assigncell = new html_table_cell();
                $assigncell->header = true;
                $assigncell->text = get_string('manualassign', 'tool_capexplorer');
                $assigncell->text .= $this->help_icon('manualassignment', 'tool_capexplorer');
                $assignrow[] = $assigncell;
            }
            if ($autoassigned) {
                $assigncell2 = new html_table_cell();
                $assigncell2->header = true;
                $assigncell2->text = get_string('autoassign', 'tool_capexplorer');
                $assigncell2->text .= $this->help_icon('autoassignment', 'tool_capexplorer');
                $assignrow[] = $assigncell2;
            }
        }
        $table->data[] = $rolerow;
        $table->data[] = $assignrow;

        foreach ($contexts as $context) {
            $contextid = $context->id;
            $contextinfo = \tool_capexplorer\capexplorer::get_context_info($context);
            $assignableroles = get_assignable_roles($context);
            $instance = isset($contextinfo->url) ? html_writer::link($contextinfo->url,
                $contextinfo->instance) : $contextinfo->instance;
            $row = array($contextinfo->contextlevel, $instance);
            foreach ($roles as $role) {
                $roleid = $role->id;
                $autoassigned = \tool_capexplorer\capexplorer::role_is_auto_assigned($roleid);
                $manualassigned = \tool_capexplorer\capexplorer::role_is_manually_assigned($roleid, $manualassignments);

                if ($manualassigned) {
                    $cell = new html_table_cell();

                    if (isset($manualassignments[$contextid][$roleid])
                        && $manualassignments[$contextid][$roleid] != CAP_INHERIT) {

                        $textkey = 'assigned';
                        $class = 'roleassigned';
                    } else {
                        $textkey = 'notassigned';
                        $class = '';
                    }
                    $cell->text = $this->output->container(get_string($textkey, 'tool_capexplorer'), $class);

                    if (!array_key_exists($roleid, $assignableroles)) {
                        $error = 'notassignable';
                    } else if (!user_can_assign($context, $roleid)) {
                        $error = 'nopermtoassign';
                    } else {
                        $error = '';
                    }

                    if (empty($error)) {
                        $url = new moodle_url('/admin/roles/assign.php',
                            array('contextid' => $contextid, 'roleid' => $roleid));
                        $cell->text .= $this->print_change_link($url);
                    } else {
                        $cell->text .= $this->print_message_with_help($error);
                    }

                    $row[] = $cell;
                }

                if ($autoassigned) {
                    $cell2 = new html_table_cell();
                    if (isset($autoassignments[$contextid][$roleid])) {
                        $text = get_string($autoassignments[$contextid][$roleid], 'admin');
                        $cell2->text = $this->output->container($text, 'roleassigned');
                        if (!$hassiteconfig) {
                            $error = 'nopermtoautoassign';
                        } else {
                            $error = '';
                        }
                        if (empty($error)) {
                            $url = new moodle_url('/admin/settings.php', array('section' => 'userpolicies'));
                            $cell2->text .= $this->print_change_link($url);
                        } else {
                            $cell2->text .= $this->print_message_with_help($error);
                        }
                    }
                    $row[] = $cell2;
                }

            }
            $table->data[] = new html_table_row($row);
        }
        $html .= html_writer::table($table);
        return $html;
    }

    /**
     * Display a message with an associated help button in the format used by this tool.
     *
     * @param string $key String key for the message and help (help text should be '{$key}_help').
     * @return string HTML to display the message.
     */
    public function print_message_with_help($key) {
        $text = get_string($key, 'tool_capexplorer');
        $text .= $this->help_icon($key, 'tool_capexplorer');
        return html_writer::tag('small', $text, array('class' => 'option-disabled'));
    }

    /**
     * Display a link for changing a particular setting in the format used by this tool.
     *
     * @param string $url URL for link.
     * @return string HTML to display the message.
     */
    public function print_change_link($url) {
        $link = html_writer::link($url, get_string('change', 'tool_capexplorer'));
        return html_writer::tag('small', $link);
    }

    /**
     * Format a permission constant to print a formatted HTML string description.
     *
     * @param int $permission CAP_* integer permission.
     * @return string HTML to display the string by name.
     */
    public function print_permission_value($permission) {
        // Need to be strict on values but not types so cast everything to strings.
        if ((string)$permission === (string)CAP_INHERIT) {
            $permstr = 'inherit';
        } else if ((string)$permission === (string)CAP_ALLOW) {
            $permstr = 'allow';
        } else if ((string)$permission === (string)CAP_PREVENT) {
            $permstr = 'prevent';
        } else if ((string)$permission === (string)CAP_PROHIBIT) {
            $permstr = 'prohibit';
        } else if (is_null($permission)) {
            $permstr = 'notset';
        } else {
            $permstr = 'unknown';
        }

        $out = $this->output->container(
            get_string('permission' . $permstr, 'tool_capexplorer'),
            'perm-' . $permstr
        );

        return $out;
    }

    /**
     * Display a boolean permission result (true/false) as a nicely formatted string
     * (e.g. granted/denied). Optionally include HTML to style the output.
     *
     * @param bool $permission The boolean permission to display.
     * @param bool $includestyles Whether to include HTML to style the text or not.
     *
     * @return string Text or HTML containing the permission value.
     */
    public function print_boolean_permission_value($permission, $includestyles = true) {
        $result = ($permission) ? 'capgranted' : 'capdenied';
        $text = get_string($result, 'tool_capexplorer');
        if ($includestyles) {
            $text = html_writer::tag('span', $text, array('class' => $result));
        }

        return $text;
    }

    /**
     * Display a link back to the form.
     */
    public function print_back_link() {

        return $this->container(
            html_writer::link(
                new moodle_url('/admin/tool/capexplorer/'),
                get_string('exploreanother', 'tool_capexplorer')
            )
        );
    }

    /**
     * Display warning messages if:
     * 1/ The result from this tool does not match the result from has_capability().
     * 2/ The user being tested is an admin (so is automatically granted all capabilities).
     * 3/ The capability being checked can't be assigned in the context provided.
     *
     * @param bool $overallresult The result calculated by this tool.
     * @param bool $result The result calculated by has_capability().
     * @param object $user User object for the user being tested.
     * @param string $capability The capability being checked.
     * @param object $context The context being checked.
     *
     * @return string HTML to display any warning messages.
     */
    public function print_warning_messages($overallresult, $result, $user, $capability,
        $context) {
        $html = '';

        $guestaccessblocked = \tool_capexplorer\capexplorer::is_guest_access_blocked($capability, $user->id);

        if (!$guestaccessblocked && $overallresult != $result) {
            $cacheurl = new moodle_url('/admin/purgecaches.php');
            $a = new stdClass();
            $a->cacheurl = $cacheurl->out();
            $a->bugurl = 'https://tracker.moodle.org/browse/CONTRIB';
            $html .= $this->container(get_string('resultdiffersfromaccesslib', 'tool_capexplorer', $a), 'notifyproblem');
        }

        if (is_siteadmin($user)) {
            $url = new moodle_url('/admin/roles/admins.php');
            $a = new stdClass();
            $a->url = $url->out();
            $a->user = fullname($user);
            $html .= $this->output->container(get_string('userisadmin', 'tool_capexplorer', $a), 'notifyproblem');
        }

        if ($guestaccessblocked) {
            $a = new stdClass();
            $a->capability = $capability;
            $a->user = fullname($user);
            $html .= $this->output->container(get_string('guestaccessblocked', 'tool_capexplorer', $a), 'notifyproblem');
        }

        return $html;
    }

    /**
     * Return information about a specific context.
     *
     * @param context $context Context object to return info about.
     */
    public function print_human_readable_context_info($context) {

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            return get_string('systemcontext', 'tool_capexplorer');
        }

        $contextinfo = \tool_capexplorer\capexplorer::get_context_info($context);

        $a = new stdClass();
        $a->contextstring = isset($contextinfo->url) ? html_writer::link($contextinfo->url,
            $contextinfo->instance) : $contextinfo->instance;
        $a->contextlevel = $contextinfo->contextlevel;
        return get_string('contextinfo', 'tool_capexplorer', $a);

    }

    /**
     * Generate a table displaying the results of a particular capability check.
     *
     * @param object $user User object of user being checked.
     * @param string $capability Capability to check.
     * @param context $context Context object of context being checked.
     * @param boolean $result Result of check (true for granted, false for denied).
     * @return string HTML for table displaying result.
     */
    public function print_results_table($user, $capability, $context, $result) {
        $html = '';
        $table = new html_table();
        $table->data = array();

        $cell = new html_table_cell();
        $cell->text = get_string('user', 'tool_capexplorer');
        $cell->header = true;
        $cell2 = new html_table_cell();
        $cell2->text = html_writer::link(
            new moodle_url('/user/profile.php', array('id' => $user->id)),
            fullname($user)
        );
        $userrow = array($cell, $cell2);
        $table->data[] = new html_table_row($userrow);

        $cell = new html_table_cell();
        $cell->text = get_string('capability', 'tool_capexplorer');
        $cell->header = true;
        $cell2 = new html_table_cell();
        $cell2->text = $capability;
        $capabilityrow = array($cell, $cell2);
        $table->data[] = new html_table_row($capabilityrow);

        $cell = new html_table_cell();
        $cell->text = get_string('context', 'tool_capexplorer');
        $cell->header = true;
        $cell2 = new html_table_cell();
        $cell2->text = $this->print_human_readable_context_info($context);
        $contextrow = array($cell, $cell2);
        $table->data[] = new html_table_row($contextrow);

        $cell = new html_table_cell();
        $cell->text = get_string('result', 'tool_capexplorer');
        $cell->header = true;
        $cell2 = new html_table_cell();
        $cell2->text = $this->print_boolean_permission_value($result);
        $resultrow = array($cell, $cell2);
        $table->data[] = new html_table_row($resultrow);

        $html .= html_writer::table($table);
        return $html;
    }

    /**
     * Displays a tables showing the role totals and the final overall result.
     *
     * @param array $roletotals An array of roles and their individual totals.
     * @param int $overallresult The overall aggregated result.
     *
     * @return string HTML to display the table.
     */
    public function print_role_totals_table($roletotals, $overallresult) {
        $roles = role_get_names();

        $html = '';
        $table = new html_table();
        $table->head = array(
            get_string('role', 'tool_capexplorer'),
            get_string('roletotal', 'tool_capexplorer'),
        );
        $table->colclasses = array(
            'role',
            'roletotal',
        );
        $table->data = array();

        foreach ($roletotals as $roleid => $permission) {
            $role = $roles[$roleid];
            $row = array(
                $role->localname,
                $this->print_permission_value($permission)
            );

            $table->data[] = new html_table_row($row);
        }

        $totalrow = array(
            html_writer::tag('strong', get_string('overallresult', 'tool_capexplorer')),
            $this->print_boolean_permission_value($overallresult)
        );
        $table->data[] = new html_table_row($totalrow);

        $html .= html_writer::table($table);
        return $html;
    }
}

