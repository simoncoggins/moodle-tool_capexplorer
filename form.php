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
        global $CFG, $PAGE;

        $mform    = $this->_form;

        $mform->addElement('header', 'selector', get_string('selectortitle', 'tool_capexplorer'));

        $mform->addElement('text', 'username', get_string('username', 'tool_capexplorer'), 'maxlength="254" size="50"');

        $mform->setType('username', PARAM_TEXT);

        $mform->addElement('text', 'capability', get_string('capability', 'tool_capexplorer'), 'maxlength="254" size="50"');
        $mform->setType('capability', PARAM_TEXT);

        $choices = array();
        $choices['0'] = get_string('systemcontext', 'tool_capexplorer');
        $mform->addElement('select', 'context_system', get_string('context', 'tool_capexplorer'), $choices);

        /*
         * Need to represent this via selectors:
         *
         *        _______ System_______
         *       /           |         \
         *    Course     Front page    User
         *    Category  (Site Course)
         *      |            |
         *    Course      Activity
         *      |            |
         *   Activity      Block
         *      |
         *    Block
         *
         */
    }
}
