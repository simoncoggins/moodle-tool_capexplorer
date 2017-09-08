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
 * Explore how Moodle's permission system works.
 *
 * @package    tool_capexplorer
 * @author     Simon Coggins
 * @copyright  2013 Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Speedup for non-admins add all caps used on this page.
if ($hassiteconfig or has_capability('tool/capexplorer:view', context_system::instance())) {
    $ADMIN->add('roles', new admin_externalpage(
        'toolcapexplorer',
        get_string('pluginname', 'tool_capexplorer'),
        "{$CFG->wwwroot}/{$CFG->admin}/tool/capexplorer/index.php",
        'tool/capexplorer:view'
    ));
}
