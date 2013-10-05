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
    'users' => $DB->get_fieldset_select('user', 'username', 'deleted = 0', null, 'username'),
    'capabilities' => $DB->get_fieldset_select('capabilities', 'name', '', null)
);

$PAGE->requires->js_init_call('M.tool_capexplorer.init', array($args), false, $jsmodule);

admin_externalpage_setup('toolcapexplorer');

echo $OUTPUT->header();

// First create the form.
$mform = new capexplorer_selector_form();

if ($mform->is_cancelled()) {
    // TODO.
    echo 'Cancelled';
} else if ($data = $mform->get_data()) {
    // Process data if submitted.
    echo 'Processed';
}

echo $mform->display();
echo $OUTPUT->footer();
