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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/capexplorer/treelib.php');

/**
 * Automated unit testing of treelib.php functions.
 *
 * @package tool_capexplorer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_capexplorer_treelib_testcase extends advanced_testcase {

    /**
     * Helper method to check some common properties of a node.
     */
    protected function check_node_structure($item, $nodetype) {
        $this->assertObjectHasAttribute('label', $item);
        $this->assertObjectHasAttribute('data', $item);
        $this->assertObjectHasAttribute('nodeType', $item->data);
        $this->assertEquals($nodetype, $item->data->nodeType);
        $this->assertObjectHasAttribute('canHaveChildren', $item);
    }

    public function test_get_user_nodes() {
        $this->resetAfterTest();

        $result = tool_capexplorer_get_user_nodes();
        // Should return admin and guest user.
        $this->assertCount(2, $result);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $result = tool_capexplorer_get_user_nodes();

        // Should include newly created users.
        $this->assertCount(5, $result);

        $user4 = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $result = tool_capexplorer_get_user_nodes();

        // Should ignore deleted users.
        $this->assertCount(5, $result);

        // Check structure of first item.
        $item = current($result);
        $this->check_node_structure($item, 'user');
    }

    public function test_get_module_nodes() {
        $this->resetAfterTest();

        // Create some modules to be found.
        $cat = $this->getDataGenerator()->create_category();
        $subcat = $this->getDataGenerator()->create_category(array('parent' => $cat->id));
        $course = $this->getDataGenerator()->create_course(array('category' => $subcat->id));

        $module = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
        $module2 = $this->getDataGenerator()->create_module('wiki', array('course' => $course->id));

        // Create second course with a module that shouldn't be found.
        $course2 = $this->getDataGenerator()->create_course(array('category' => $subcat->id));
        $module3 = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));

        $result = tool_capexplorer_get_module_nodes($course->id);
        // Should find the two modules in specified course, but not others.
        $this->assertCount(2, $result);

        // Check structure of first item.
        $item = current($result);
        $this->check_node_structure($item, 'module');
    }

    public function test_get_course_nodes() {
    }

    public function test_get_category_nodes() {
    }

    public function test_get_block_nodes() {
    }

    public function test_get_system_node() {
    }

    public function test_get_frontpage_node() {
    }

    public function test_get_userdir_node() {
    }

    public function test_get_js_tree_node() {
    }

    public function test_get_child_nodes() {
    }

    public function test_get_selected_subtree() {
    }
}
