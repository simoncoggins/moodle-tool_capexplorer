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
 * Output rendering of Language customization admin tool
 *
 * @package    tool_capexplorer
 * @copyright  Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Rendering methods for the tool widgets
 */
class tool_capexplorer_renderer extends plugin_renderer_base {

    /**
     * Renders table of parent contexts
     *
     * @return string HTML
     */
    public function print_parent_context_table($parentcontexts) {
        $html = '';
        $table = new html_table();
        $table->head = array(
            get_string('contextlevel', 'tool_capexplorer'),
            get_string('instancename', 'tool_capexplorer')
        );
        $table->colclasses = array(
            'contextlevel',
            'instancename'
        );
        $table->data = array();
        foreach ($parentcontexts as $context) {
            $instance = isset($context->url) ?
                html_writer::link($context->url, $context->instance) : $context->instance;
            $row = new html_table_row(array(
                $context->contextlevel,
                $instance
            ));
            $table->data[] = $row;
        }
        $html .= html_writer::table($table);
        return $html;
    }

}
