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
 * Output rendering of Language customization admin tool
 *
 * @package    tool_capexplorer
 * @copyright  Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Rendering methods for the tool widgets
 */
class tool_capexplorer_renderer extends plugin_renderer_base {

    /**
     * Renders table of parent contexts
     *
     * @return string HTML
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
            $instance = isset($context->url) ?
                html_writer::link($context->url, $context->instance) : $context->instance;
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
     * Displays a tables showing the permissions for a particular capability for
     * a set of roles in a set of contexts.
     *
     * @param array $contexts An array of context objects.
     * @param array $roles An array of role objects.
     * @param int $userid A userid to display results for.
     * @param string $capability A capability to display results for.
     *
     * @return string HTML to display the table.
     */
    public function print_role_capability_table($contexts, $roles, $userid, $capability) {
        $roleids = array_keys($roles);
        $contextids = array_map(function($context) {return $context->id;}, $contexts);
        $assignmentdata = tool_capexplorer_get_role_assignment_info($contextids, $roleids, $userid, $capability);
        echo '<pre>';
        //var_dump($assignmentdata);
        echo '</pre>';
        $overridedata = tool_capexplorer_get_role_override_info($contextids, $roleids, $capability);

        $html = '';
        $table = new html_table();
        $table->head = array(
            'Role' //get_string('role', 'tool_capexplorer'),
        );
        $table->colclasses = array(
            'role',
        );
        foreach ($contexts as $context) {
            $contextinfo = tool_capexplorer_get_context_info($context);
            $table->head[] = $contextinfo->instance;
            $table->colclasses[] = 'context-' . $contextinfo->contextlevel;
        }
        $table->data = array();

        foreach ($roles as $role) {
            $roleid = $role->id;
            $row = array(role_get_name($role));
            foreach ($contexts as $context) {
                $contextid = $context->id;
                if (isset($assignmentdata[$roleid][$contextid]) ||
                    isset($overridedata[$roleid][$contextid])) {
                    $cell = '';
                    if (isset($assignmentdata[$roleid][$contextid])) {
                        $cell .= $this->print_permission($assignmentdata[$roleid][$contextid], $contextid, $roleid, $capability, 'assignment');
                    }
                    if (isset($overridedata[$roleid][$contextid], $contextid, $roleid, $capability)) {
                        $cell .= $this->print_permission($overridedata[$roleid][$contextid], $contextid, $roleid, $capability, 'override');
                    }
                } else {
                    $cell = $this->print_permission(null, $contextid, $roleid, $capability);
                }

                $row[] = $cell;
                //$this->print_permission($overridedata[$roleid][$contextid], $contextid, $roleid, $capability);
            }
            $table->data[] = new html_table_row($row);
        }
        $html .= html_writer::table($table);
        return $html;
    }

    public function print_permission($permission, $contextid, $roleid, $capability, $via = false) {
        global $CFG;
        switch ($via) {
        case 'assignment':
            $url = new moodle_url('/admin/roles/assign.php',
                array('contextid' => $contextid, 'roleid' => $roleid));
            $via = html_writer::tag('small', get_string('viaassignment', 'tool_capexplorer', $url->out()));
            break;
        case 'override':
            $url = new moodle_url('/admin/roles/override.php',
                array('contextid' => $contextid, 'roleid' => $roleid));
            $via = html_writer::tag('small', get_string('viaoverride', 'tool_capexplorer', $url->out()));
            break;
        default:
            $via = '';
        }

        // Need to be strict on values but not types so cast everything to strings.
        if ((string)$permission === (string)CAP_INHERIT) {
            $out = $this->output->container(
                get_string('permissioninherit', 'tool_capexplorer'),
                'perm-inherit'
            );
            $out .= $this->output->container($via);
        } else if ((string)$permission === (string)CAP_ALLOW) {
            $out = $this->output->container(
                get_string('permissionallow', 'tool_capexplorer'),
                'perm-allow'
            );
            $out .= $this->output->container($via);
        } else if ((string)$permission === (string)CAP_PREVENT) {
            $out = $this->output->container(
                get_string('permissionprevent', 'tool_capexplorer'),
                'perm-prevent'
            );
            $out .= $this->output->container($via);
        } else if ((string)$permission === (string)CAP_PROHIBIT) {
            $out = $this->output->container(
                get_string('permissionprohibit', 'tool_capexplorer'),
                'perm-prohibit'
            );
            $out .= $this->output->container($via);
        } else if (is_null($permission)) {
            $out = $this->output->container(
                get_string('permissionnotset', 'tool_capexplorer'),
                'perm-notset'
            );
            $assignurl = new moodle_url('/admin/roles/assign.php',
                array('contextid' => $contextid, 'roleid' => $roleid));
            $overrideurl = new moodle_url('/admin/roles/override.php',
                array('contextid' => $contextid, 'roleid' => $roleid));
            $a = new stdClass();
            $a->assignurl = $assignurl->out();
            $a->overrideurl = $overrideurl->out();
            $links = html_writer::tag('small', get_string('assignoverridelinks', 'tool_capexplorer', $a));
            $out .= $this->output->container($links);
        } else {
            $out = $this->output->container(
                get_string('permissionunknown', 'tool_capexplorer'),
                'perm-unknown'
            );
            $out .= $this->output->container($via);
        }
        return $out;
    }

    public function print_capability_check_result($result, $isadmin) {
        $html = '';

        $result = ($result) ? 'capgranted' : 'capdenied';
        $html .= $this->output->container(
            get_string($result, 'tool_capexplorer'),
            $result
        );

        if ($isadmin) {
            $url = new moodle_url('/admin/roles/admins.php');
            $html .= $this->output->container(get_string('userisadmin', 'tool_capexplorer', $url->out()), 'notifyproblem');
        }

        return $html;
    }
}
