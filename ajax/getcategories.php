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
 * Return a list of categories, optionally including the "Front page" option.
 *
 * @package     tool_capexplorer
 * @copyright   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . "/{$CFG->admin}/tool/capexplorer/locallib.php");
require_once($CFG->dirroot . '/course/lib.php');

$contextlevel = optional_param('contextlevel', '', PARAM_ALPHA);

require_login();

if (!has_capability('tool/capexplorer:view', context_system::instance())) {
    print_error('nopermissiontoshow', 'error');
}

$chooseoption = array('0' => get_string('chooseacategory', 'tool_capexplorer'));

$categoryoptions = make_categories_options();

$frontpageoption = array();
if ($contextlevel != 'category') {
    $frontpageoption['-1'] = get_string('nocatfrontpage', 'tool_capexplorer');
}

$options = $chooseoption + $frontpageoption + $categoryoptions;

tool_capexplorer_render_json($options);
