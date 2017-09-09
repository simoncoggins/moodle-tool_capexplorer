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
 * Automated unit testing of classes/tree.php functions.
 *
 * @package     tool_capexplorer
 * @author      Simon Coggins
 * @copyright   2013 Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for capexplorer treeview.
 *
 * @group tool_capexplorer
 */
class tool_capexplorer_tree_testcase extends advanced_testcase {

    /**
     * Helper method to check some common properties of a node.
     *
     * @param object $item Tree node item.
     * @param string $nodetype Type of this node.
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

        $result = \tool_capexplorer\tree::get_user_nodes();
        // Should return admin and guest user.
        $this->assertCount(2, $result);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $result = \tool_capexplorer\tree::get_user_nodes();

        // Should include newly created users.
        $this->assertCount(5, $result);

        $user4 = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $result = \tool_capexplorer\tree::get_user_nodes();

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

        $result = \tool_capexplorer\tree::get_module_nodes($course->id);
        // Should find the two modules in specified course, but not others.
        $this->assertCount(2, $result);

        // Check structure of first item.
        $item = current($result);
        $this->check_node_structure($item, 'module');
    }

    public function test_get_course_nodes() {
        $this->resetAfterTest();

        $cat = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(array('category' => $cat->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $cat->id));
        $course3 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));

        // Should only count courses in the selected category.
        $courses = \tool_capexplorer\tree::get_course_nodes($cat->id);
        $this->assertCount(2, $courses);

        // Check the structure of the first item.
        $this->check_node_structure(current($courses), 'course');
    }

    public function test_get_category_nodes() {
        $this->resetAfterTest();

        $cat = $this->getDataGenerator()->create_category();
        $subcat1 = $this->getDataGenerator()->create_category(array('parent' => $cat->id));
        $subcat2 = $this->getDataGenerator()->create_category(array('parent' => $cat->id));
        $cat2 = $this->getDataGenerator()->create_category();
        $subcat3 = $this->getDataGenerator()->create_category(array('parent' => $cat2->id));
        // Should only count categories in the selected category.
        $categories = \tool_capexplorer\tree::get_category_nodes($cat->id);
        $this->assertCount(2, $categories);

        // Check the structure of the first item.
        $this->check_node_structure(current($categories), 'category');
    }

    public function test_get_block_nodes() {
        $this->resetAfterTest();

        // Blocks can be children of different contexts.
        $cat = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($cat->id);
        $course = $this->getDataGenerator()->create_course(array('category' => $cat->id));
        $coursecontext = context_course::instance($course->id);

        $blocksbefore = \tool_capexplorer\tree::get_block_nodes('course', $course->id);
        $courseblock = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $coursecontext->id));
        $blocksafter = \tool_capexplorer\tree::get_block_nodes('course', $course->id);

        $blocksdiff = array_diff(array_map('serialize', $blocksafter), array_map('serialize', $blocksbefore));
        $this->assertCount(1, $blocksdiff);
        $this->check_node_structure(unserialize(current($blocksdiff)), 'block');

        $blocksbefore = \tool_capexplorer\tree::get_block_nodes('category', $cat->id);
        $catblock = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $categorycontext->id));
        $blocksafter = \tool_capexplorer\tree::get_block_nodes('category', $cat->id);

        $blocksdiff = array_diff(array_map('serialize', $blocksafter), array_map('serialize', $blocksbefore));
        $this->assertCount(1, $blocksdiff);
        $this->check_node_structure(unserialize(current($blocksdiff)), 'block');

    }

    public function test_get_system_node() {
        $systemnode = \tool_capexplorer\tree::get_system_node();

        $this->assertCount(1, $systemnode);
        $this->check_node_structure(current($systemnode), 'system');
    }

    public function test_get_frontpage_node() {
        $frontpagenode = \tool_capexplorer\tree::get_frontpage_node();

        $this->assertCount(1, $frontpagenode);
        $this->check_node_structure(current($frontpagenode), 'course');
    }

    public function test_get_userdir_node() {
        $userdirnode = \tool_capexplorer\tree::get_userdir_node();

        $this->assertCount(1, $userdirnode);
        $this->check_node_structure(current($userdirnode), 'userdir');
    }

    public function test_get_js_tree_node() {
        $nodeobject = new stdClass();
        $nodeobject->name = 'Node name';
        $nodeobject->id = 123;
        $nodeobject->contextid = 456;

        $jsnode = \tool_capexplorer\tree::get_js_tree_node($nodeobject, 'course');

        $this->assertObjectHasAttribute('canHaveChildren', $jsnode);
        $this->assertObjectHasAttribute('label', $jsnode);

        $this->assertEquals(123, $jsnode->data->instanceId);
        $this->assertEquals(456, $jsnode->data->contextId);
        $this->assertEquals('course', $jsnode->data->nodeType);
    }
}
