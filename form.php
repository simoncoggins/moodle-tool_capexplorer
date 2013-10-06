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
        global $CFG, $PAGE, $DB;

        $mform    = $this->_form;

        $mform->addElement('header', 'selector', get_string('selectortitle', 'tool_capexplorer'));

        $mform->addElement('text', 'username', get_string('username', 'tool_capexplorer'), 'maxlength="254" size="50"');
        $mform->setType('username', PARAM_TEXT);

        $mform->addElement('text', 'capability', get_string('capability', 'tool_capexplorer'), 'maxlength="254" size="50"');
        $mform->setType('capability', PARAM_TEXT);

        $choices = array();
        $choices['system'] = get_string('systemcontext', 'tool_capexplorer');
        $choices['user'] = get_string('usercontext', 'tool_capexplorer');
        $choices['category'] = get_string('coursecatcontext', 'tool_capexplorer');
        $choices['course'] = get_string('coursecontext', 'tool_capexplorer');
        $choices['module'] = get_string('modulecontext', 'tool_capexplorer');
        $choices['block'] = get_string('blockcontext', 'tool_capexplorer');
        $mform->addElement('select', 'contextlevel', get_string('contextlevel', 'tool_capexplorer'), $choices);

        $instances = array();

        $nonestr = html_writer::tag('span', get_string('none', 'tool_capexplorer'), array('id' => 'id_systeminstances'));
        $instances[] = &$mform->createElement('static', 'systeminstances', '', $nonestr, array('class' => 'hidden-field'));

        // TODO AJAX auto-complete.
        $users = $DB->get_records_select_menu('user', 'deleted = 0', null, 'username', 'id, username');
        $options = array('0' => get_string('chooseauser', 'tool_capexplorer')) + $users;
        $instances[] = &$mform->createElement('select', 'userinstances', '', $options, array('class' => 'hidden-field'));

        $categories = make_categories_options();
        // TODO lang string.
        $options = array('0' => get_string('chooseacategory', 'tool_capexplorer')) + array('-1' => '(site)') + $categories;
        $instances[] = &$mform->createElement('select', 'categoryinstances', '', $options,
            array('class' => 'hidden-field'));

        $options = array('' => get_string('chooseacategoryfirst', 'tool_capexplorer'));
        $instances[] = &$mform->createElement('select', 'courseinstances', '', $options,
            array('class' => 'hidden-field', 'disabled' => 'disabled'));

        $options = array('' => get_string('chooseacoursefirst', 'tool_capexplorer'));
        $instances[] = &$mform->createElement('select', 'moduleinstances', '', $options,
            array('class' => 'hidden-field', 'disabled' => 'disabled'));

        $options = array('' => get_string('chooseacoursefirst', 'tool_capexplorer'));
        $instances[] = &$mform->createElement('select', 'blockinstances', '', $options,
            array('class' => 'hidden-field', 'disabled' => 'disabled'));

        $mform->addGroup($instances, 'instances', get_string('instances', 'tool_capexplorer'), array(' '), false);
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
