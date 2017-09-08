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
 * Main page for this tool.
 *
 * @package    tool_capexplorer
 * @author     Simon Coggins
 * @copyright  2013 Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_capexplorer;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

$PAGE->set_url('/admin/tool/capexplorer/index.php');
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'tool_capexplorer'));
$PAGE->set_heading(get_string('pluginname', 'tool_capexplorer'));

admin_externalpage_setup('toolcapexplorer');

// First create the form.
$mform = new form_selector();

// Default tree for first page load.
$initialtree = tree::get_system_node();
$contextids = array();

// Re-open selected tree node if data passed to page.
if ($data = data_submitted()) {
    if (!empty($data->contextid)) {
        $contextid = clean_param($data->contextid, PARAM_INT);
        $context = \context::instance_by_id($contextid);
        $parentcontextids = $context->get_parent_context_ids(true);
        $contextids = array_reverse($parentcontextids);
        $initialtree = tree::get_selected_subtree($contextids);
    }
}

echo $OUTPUT->header();

if ($data = $mform->get_data()) {
    // Process data if submitted.
    $userid = $DB->get_field('user', 'id', array('username' => $data->username));
    $capability = $data->capability;
    $context = \context::instance_by_id($data->contextid);
} else {
    // No data yet, just display the form.

    // Load JS for autocomplete and context tree.
    $args = array(
        'admin' => $CFG->admin,
        'capabilities' => $DB->get_fieldset_select('capabilities', 'name', ''),
        'initialtree' => $initialtree,
        'contextids' => $contextids
    );
    $PAGE->requires->yui_module('moodle-tool_capexplorer-capexplorer', 'M.tool_capexplorer.capexplorer.init', array($args));

    echo $OUTPUT->heading(get_string('pluginname', 'tool_capexplorer'));
    echo $OUTPUT->container(get_string('capexplorersummary', 'tool_capexplorer'));
    echo $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Display results.

$result = has_capability($capability, $context, $userid, false);
$user = $DB->get_record('user', array('id' => $userid));
$parentcontextinfo = capexplorer::get_parent_context_info($context);

$parentcontexts = $context->get_parent_contexts(true);
$contexts = array_reverse($parentcontexts);

$manualassignments = capexplorer::get_role_assignment_info($contexts, $userid);
$autoassignments = capexplorer::get_auto_role_assignment_info($userid);
$assignedroles = capexplorer::get_assigned_roles($manualassignments, $autoassignments);

$roleids = array_keys($assignedroles);
$contextids = array_map(function($context) {
    return $context->id;
}, $contexts);
$overridedata = capexplorer::get_role_override_info($contextids, $roleids, $capability);
$roletotals = capexplorer::merge_permissions_across_contexts(
    $contextids,
    $roleids,
    $overridedata
);
$overallresult = capexplorer::merge_permissions_across_roles($roletotals);

$output = $PAGE->get_renderer('tool_capexplorer');

echo $output->print_back_link();
echo $output->heading(get_string('capexplorerresult', 'tool_capexplorer'));
echo $output->print_warning_messages($overallresult, $result, $user, $capability, $context);
echo $output->print_results_table($user, $capability, $context, $overallresult);

echo $output->heading_with_help(get_string('step1', 'tool_capexplorer'), 'parentcontexts', 'tool_capexplorer', '', '', 3);
echo $output->container(get_string('parentcontextssummary', 'tool_capexplorer'));
echo $output->print_parent_context_table($parentcontextinfo);

echo $output->heading(get_string('step2', 'tool_capexplorer'), 3);
echo $output->container(get_string('roleassignmentsummary', 'tool_capexplorer'));
echo $output->container(get_string('roleassignmentsforuserx', 'tool_capexplorer', fullname($user)));
echo $output->print_role_assignment_table($contexts, $assignedroles, $manualassignments, $autoassignments);

echo $output->heading(get_string('step3', 'tool_capexplorer'), 3);
echo $output->container(get_string('rolepermissionsummary', 'tool_capexplorer'));
echo $output->container(get_string('rolepermissionsandoverridesforcapx', 'tool_capexplorer', $capability));
echo $output->print_role_permission_and_overrides_table($contexts, $assignedroles, $capability, false);

echo $output->heading(get_string('step4', 'tool_capexplorer'), 3);
$contextaggrhelpicon = $output->help_icon('contextaggrrules', 'tool_capexplorer');
echo $output->container(get_string('combineusingcontextaggregation', 'tool_capexplorer', $contextaggrhelpicon));
echo $output->print_role_permission_and_overrides_table($contexts, $assignedroles, $capability, true, false);

echo $output->heading(get_string('step5', 'tool_capexplorer'), 3);
$roleaggrhelpicon = $output->help_icon('roleaggrrules', 'tool_capexplorer');
echo $output->container(get_string('finalresultsummary', 'tool_capexplorer', $roleaggrhelpicon));
echo $output->print_role_totals_table($roletotals, $overallresult);

echo $OUTPUT->footer();
