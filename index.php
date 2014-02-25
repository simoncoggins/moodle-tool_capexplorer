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
 * @package    tool_capexplorer
 * @copyright  Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');
require_once('form.php');
require_once('locallib.php');

$PAGE->set_url('/admin/tool/capexplorer/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'tool_capexplorer'));
$PAGE->set_heading(get_string('pluginname', 'tool_capexplorer'));

$jsmodule = array(
    'name' => 'tool_capexplorer',
    'fullpath' => '/'.$CFG->admin.'/tool/capexplorer/module.js',
    'requires' => array('json', 'autocomplete', 'autocomplete-filters', 'autocomplete-highlighters')
);

$args = array(
    'admin' => $CFG->admin,
    'capabilities' => $DB->get_fieldset_select('capabilities', 'name', '', null)
);

$PAGE->requires->js_init_call('M.tool_capexplorer.init', array($args), false, $jsmodule);

admin_externalpage_setup('toolcapexplorer');

echo $OUTPUT->header();

// First create the form.
$mform = new capexplorer_selector_form();

if ($data = $mform->get_data()) {
    // Process data if submitted.
    $userid = $DB->get_field('user', 'id', array('username' => $data->username));
    $capability = $data->capability;
    switch ($data->contextlevel) {
    case 'system':
        $context = context_system::instance();
        break;
    case 'user':
        $context = context_user::instance($data->userinstances);
        break;
    case 'category':
        $context = context_coursecat::instance($data->categoryinstances);
        break;
    case 'course':
        $context = context_course::instance($data->courseinstances);
        break;
    case 'module':
        $context = context_module::instance($data->moduleinstances);
        break;
    case 'block':
        $context = context_block::instance($data->blockinstances);
        break;
    }

} else {
    // No data yet, just display the form.
    echo $mform->display();
    echo $OUTPUT->footer();
    exit;
}

echo $mform->display();

$output = $PAGE->get_renderer('tool_capexplorer');

$result = has_capability($capability, $context, $userid, false);
$user = $DB->get_record('user', array('id' => $userid));
$parentcontextinfo = tool_capexplorer_get_parent_context_info($context);

$parentcontexts = $context->get_parent_contexts(true);
$contexts = array_reverse($parentcontexts);

$manualassignments = tool_capexplorer_get_role_assignment_info($contexts, $userid);
$autoassignments = tool_capexplorer_get_auto_role_assignment_info($userid);
$assignedroles = tool_capexplorer_get_assigned_roles($manualassignments, $autoassignments);

$roleids = array_keys($assignedroles);
$contextids = array_map(function($context) {return $context->id;}, $contexts);
$overridedata = tool_capexplorer_get_role_override_info($contextids, $roleids, $capability);
$roletotals = tool_capexplorer_merge_permissions_across_contexts(
    $contextids,
    $roleids,
    $overridedata
);
$overallresult = tool_capexplorer_merge_permissions_across_roles($roletotals);

echo $output->print_warning_messages($overallresult, $result, $user);

echo $output->heading_with_help(get_string('contextlineage', 'tool_capexplorer'), 'contextlineage', 'tool_capexplorer');
echo $output->container(get_string('contextlineagesummary', 'tool_capexplorer'));
echo $output->print_parent_context_table($parentcontextinfo);

echo $output->heading(get_string('roleassignmentsforuserx', 'tool_capexplorer', fullname($user)));
echo $output->container(get_string('roleassignmentsummary', 'tool_capexplorer'));
echo $output->print_role_assignment_table($contexts, $assignedroles, $manualassignments, $autoassignments);

echo $output->heading(get_string('rolepermissionsandoverridesforcapx', 'tool_capexplorer', $capability));
$contextaggrhelpicon = $output->help_icon('contextaggrrules', 'tool_capexplorer');
echo $output->container(get_string('rolepermissionsummary', 'tool_capexplorer', $contextaggrhelpicon));
echo $output->print_role_permission_and_overrides_table($contexts, $assignedroles, $capability);

$roleaggrhelpicon = $output->help_icon('roleaggrrules', 'tool_capexplorer');
echo $output->container(get_string('finalresultsummary', 'tool_capexplorer', $roleaggrhelpicon));
echo $output->print_role_totals_table($roletotals, $overallresult);

echo $OUTPUT->footer();
