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
 * @author     Simon Coggins
 * @copyright  2013 Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_capexplorer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * Form for selecting capability to explore.
 */
class form_selector extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform    = $this->_form;

        $mform->addElement('text', 'username', get_string('username', 'tool_capexplorer'),
            'maxlength="254" size="75" placeholder="' . get_string('usernameplaceholder', 'tool_capexplorer') .
            '"');
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', get_string('required'), 'required');
        $mform->addHelpButton('username', 'username', 'tool_capexplorer');

        $mform->addElement('text', 'capability', get_string('capability', 'tool_capexplorer'),
            'maxlength="254" size="75" placeholder="' . get_string('capabilityplaceholder', 'tool_capexplorer') .
            '"');
        $mform->setType('capability', PARAM_TEXT);
        $mform->addRule('capability', get_string('required'), 'required');
        $mform->addHelpButton('capability', 'capability', 'tool_capexplorer');

        // Slight hack here: we want the 'contexttree' static element to be "required" so we need
        // the hidden input tag to prevent validation failing. Real validation of the context tree
        // is done in validation() below with errors applied to the 'contexttree' static element.
        $mform->addElement('static', 'contexttree', get_string('context', 'tool_capexplorer'),
            '<input type="hidden" name="contexttree" value="1">
            <div id="contexttree" class="yui3-skin-sam">
                <span id="tree-loading-message">Loading context tree...</span>
            </div>');
        $mform->addRule('contexttree', get_string('required'), 'required');
        $mform->addHelpButton('contexttree', 'context', 'tool_capexplorer');

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $this->add_action_buttons(false, get_string('submit'));
    }

    /**
     * Form validation.
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {
        global $DB;
        $errors = array();

        if (empty($data['username'])) {

            $errors['username'] = get_string('error:missingusername', 'tool_capexplorer');
        } else if (!$DB->record_exists('user', array('username' => $data['username']))) {
            $errors['username'] = get_string('error:invalidusername', 'tool_capexplorer', $data['username']);
        }

        if (empty($data['capability'])) {
            $errors['capability'] = get_string('error:missingcapability', 'tool_capexplorer');
        } else if (!$DB->record_exists('capabilities', array('name' => $data['capability']))) {
            $errors['capability'] = get_string('error:invalidcapability', 'tool_capexplorer', $data['capability']);
        }

        if (empty($data['contextid'])) {
            $errors['contexttree'] = get_string('error:invalidcontext', 'tool_capexplorer');
        }

        return $errors;
    }
}
