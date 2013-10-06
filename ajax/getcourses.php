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
 * Return a list of courses in a specific category (or all courses).
 *
 * @package     tool_capexplorer
 * @copyright   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/capexplorer/locallib.php");

$categoryid = optional_param('categoryid', 0, PARAM_INT);

require_login();

if (!has_capability('tool/capexplorer:view', context_system::instance())) {
    print_error('nopermissiontoshow', 'error');
}

if (!$categoryid) {
    $options = array(
        '0' => get_string('chooseacategoryfirst', 'tool_capexplorer')
    );
    tool_capexplorer_render_json($options, true);
}

if ($categoryid == -1) {
    $sitename = $DB->get_field('course', 'fullname', array('id' => SITEID));
    $options = array(
        0 => get_string('chooseacourse', 'tool_capexplorer'),
        SITEID => get_string('xfrontpage', 'tool_capexplorer', format_string($sitename))
    );
    tool_capexplorer_render_json($options);
}

$courses = get_courses($categoryid, 'c.sortorder ASC', 'c.id,c.fullname');

if (empty($courses)) {
    $options = array(
        '0' => get_string('nocoursesfound', 'tool_capexplorer')
    );
    tool_capexplorer_render_json($options, true);
}

$options = array(0 => get_string('chooseacourse', 'tool_capexplorer'));
foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    } else {
        $options[$course->id] = format_string($course->fullname);
    }
}

tool_capexplorer_render_json($options);
