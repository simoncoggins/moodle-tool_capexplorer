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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class capexplorer_selector_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform    = $this->_form;

        $mform->addElement('header', 'selector', get_string('selectortitle', 'tool_capexplorer'));

        $mform->addElement('text', 'username', get_string('username', 'tool_capexplorer'),
            'maxlength="254" size="75" placeholder="' . get_string('usernameplaceholder', 'tool_capexplorer') .
            '"');
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', get_string('required'), 'required');

        $mform->addElement('text', 'capability', get_string('capability', 'tool_capexplorer'),
            'maxlength="254" size="75" placeholder="' . get_string('capabilityplaceholder', 'tool_capexplorer') .
            '"');
        $mform->setType('capability', PARAM_TEXT);
        $mform->addRule('capability', get_string('required'), 'required');

        $mform->addElement('static', 'contexttree', get_string('context', 'tool_capexplorer'), '<div id="contexttree" class="yui3-skin-sam"><div>');

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

        if (!$DB->record_exists('user', array('username' => $data['username']))) {
            $errors['username'] = get_string('error:invalidusername', 'tool_capexplorer', $data['username']);
        }

        if (!$DB->record_exists('capabilities', array('name' => $data['capability']))) {
            $errors['capability'] = get_string('error:invalidcapability', 'tool_capexplorer', $data['capability']);
        }

        if (empty($data['contextid'])) {
            $errors['contexttree'] = get_string('error:invalidcontext', 'tool_capexplorer');
        }

        return $errors;
    }
}
