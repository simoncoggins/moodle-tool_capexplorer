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
 * Capability Explorer context tree functions.
 *
 * @package     tool_capexplorer
 * @author      Simon Coggins
 * @copyright   2013 Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_capexplorer;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for generating the context tree.
 */
class tree {

    /**
     * Get a section of the context tree with the expanded nodes determined by
     * a set of context ids.
     *
     * This will call itself recursively, gradually shortening the $contextids
     * array until each level has been expanded.
     *
     * @param array $contextids Array of context IDs representing the 'open' nodes of the tree.
     * @param object $parentnode The node who's children should be calculated, or null for the whole tree.
     * @return array Array of nodes, potentially with one node at each level having its children expanded.
     */
    public static function get_selected_subtree($contextids, $parentnode = null) {

        // Start from the top if parentnode not set yet.
        if (is_null($parentnode)) {
            $parentnode = new \stdClass();
            $parentnode->data = new \stdClass();
            $parentnode->data->nodeType = 'root';
            return self::get_selected_subtree($contextids, $parentnode);
        }

        if (empty($contextids)) {
            return array();
        }

        $currentcontextid = array_shift($contextids);

        $nodetype = $parentnode->data->nodeType;
        $instanceid = isset($parentnode->data->instanceId) ? $parentnode->data->instanceId : 0;

        $nodes = self::get_child_nodes($nodetype, $instanceid);
        if (empty($nodes)) {
            return array();
        }

        foreach ($nodes as $key => $node) {
            if (isset($node->data->contextId) && $node->data->contextId == $currentcontextid) {
                $nodes[$key]->children = self::get_selected_subtree($contextids, $node);
            }
        }
        foreach ($nodes as $key => $node) {
            if ($node->data->nodeType == 'userdir') {
                $nodes[$key]->children = self::get_user_nodes();
            }
        }
        return $nodes;
    }

    /**
     * Get the child nodes of a particular node.
     *
     * The node is uniquely specified by nodetype and instanceid (not used in all cases).
     *
     * @param string $nodetype A string representing the type of node, e.g. 'course'.
     * @param int $instanceid The ID of the node.
     * @return array An array of child nodes.
     */
    public static function get_child_nodes($nodetype, $instanceid = 0) {
        switch ($nodetype) {
            case 'root':
                return self::get_system_node();
            case 'userdir':
                return self::get_user_nodes();
            case 'system':
                $frontpagenode = self::get_course_nodes(-1);
                $userdirnode = self::get_userdir_node();
                $toplevelcatnodes = self::get_category_nodes(0);
                $blocknodes = self::get_block_nodes($nodetype, $instanceid);
                return array_merge($frontpagenode, $userdirnode, $toplevelcatnodes, $blocknodes);
            case 'category':
                $categorynodes = self::get_category_nodes($instanceid);
                $coursenodes = self::get_course_nodes($instanceid);
                $blocknodes = self::get_block_nodes($nodetype, $instanceid);
                return array_merge($categorynodes, $coursenodes, $blocknodes);
            case 'course':
                $modulenodes = self::get_module_nodes($instanceid);
                $blocknodes = self::get_block_nodes($nodetype, $instanceid);
                return array_merge($modulenodes, $blocknodes);
        }
    }

    /**
     * Get data for user nodes and return in node format used by JS.
     *
     * @return array Array of user node objects to include in context tree.
     */
    public static function get_user_nodes() {
        global $DB;

        $sqlfullname = $DB->sql_fullname();
        $sql = "SELECT u.id, c.id AS contextid, {$sqlfullname} AS name
            FROM {user} u
            JOIN {context} c
            ON u.id = c.instanceid AND contextlevel = " . CONTEXT_USER . "
            WHERE
            u.deleted <> 1
            ORDER BY {$sqlfullname}";
        if ($users = $DB->get_records_sql($sql, array())) {
            $nodetypes = array_fill(0, count($users), 'user');
            return array_map(array('self', 'get_js_tree_node'), $users, $nodetypes);
        } else {
            return array();
        }

    }

    /**
     * Get data for module nodes and return in node format used by JS.
     *
     * @param int $parentcourseid Return modules that belong to this course.
     * @return array Array of module node objects to include in context tree.
     */
    public static function get_module_nodes($parentcourseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        if ($modules = get_array_of_activities($parentcourseid)) {
            $cmids = array_map(function($item) {
                return $item->cm;
            }, $modules);
            list($insql, $inparams) = $DB->get_in_or_equal($cmids);
            $sql = "SELECT cm.id, ctx.id AS contextid
                FROM {course_modules} cm
                LEFT JOIN {context} ctx ON cm.id = ctx.instanceid
                AND ctx.contextlevel = " . CONTEXT_MODULE . "
                WHERE cm.id {$insql}";
            $contextmap = $DB->get_records_sql_menu($sql, $inparams);

            $moduleswithcontext = array_map(function($item) use ($contextmap) {
                $item->contextid = $contextmap[$item->cm];
                return $item;
            }, $modules);

            $nodetypes = array_fill(0, count($modules), 'module');
            return array_map(array('self', 'get_js_tree_node'), $moduleswithcontext, $nodetypes);
        } else {
            return array();
        }
    }

    /**
     * Get data for course nodes and return in node format used by JS.
     *
     * @param int $parentcategoryid Return courses that belong to this category.
     * @return array Array of course node objects to include in context tree.
     */
    public static function get_course_nodes($parentcategoryid) {
        if ($parentcategoryid == -1) {
            return self::get_frontpage_node();
        }

        if ($courses = get_courses($parentcategoryid, 'c.sortorder ASC',
            'c.id,c.fullname AS name, ctx.id AS contextid')) {
            $nodetypes = array_fill(0, count($courses), 'course');
            return array_map(array('self', 'get_js_tree_node'), $courses, $nodetypes);
        } else {
            return array();
        }
    }

    /**
     * Get data for category nodes and return in node format used by JS.
     *
     * @param int $parentcategoryid Return categories that are children of this category.
     * @return array Array of category node objects to include in context tree.
     */
    public static function get_category_nodes($parentcategoryid) {
        global $DB;
        $sql = "SELECT cc.id,cc.name,ctx.id AS contextid
            FROM {course_categories} cc
            JOIN {context} ctx ON ctx.instanceid = cc.id
                AND ctx.contextlevel = " . CONTEXT_COURSECAT . "
            WHERE cc.parent = ?
            ORDER BY cc.name";
        if ($categories = $DB->get_records_sql($sql, array($parentcategoryid))) {
            $nodetypes = array_fill(0, count($categories), 'category');
            return array_map(array('self', 'get_js_tree_node'), $categories, $nodetypes);
        } else {
            return array();
        }
    }

    /**
     * Get data for block nodes and return in node format used by JS.
     *
     * This function is a bit more complex as blocks can be children of several
     * different context levels.
     *
     * @param string $parentnodetype The node type of the parent node.
     * @param int $parentinstanceid The instanceid of the parent node.
     * @return array Array of block node objects to include in context tree.
     */
    public static function get_block_nodes($parentnodetype, $parentinstanceid = 0) {
        global $DB;
        switch ($parentnodetype) {
            case 'system':
                $parentcontext = \context_system::instance();
                break;
            case 'category':
                $parentcontext = \context_coursecat::instance($parentinstanceid);
                break;
            case 'course':
                $parentcontext = \context_course::instance($parentinstanceid);
                break;
            default:
                throw new \Exception("Invalid nodetype '{$parentnodetype}' passed to \\tool_capexplorer\\tree\\get_block_nodes().");
        }

        $sql = "SELECT bi.id, c.id AS contextid, bi.blockname AS name
            FROM {block_instances} bi
            JOIN {block} b ON bi.blockname = b.name
            JOIN {context} c ON c.instanceid = bi.id AND c.contextlevel = " . CONTEXT_BLOCK . "
            WHERE
            bi.parentcontextid = ?
        ";
        $params = array($parentcontext->id);

        if ($blockinstances = $DB->get_records_sql($sql, $params)) {

            // Get block names from lang files and convert to JS nodes.
            return array_map(function($blockinstance) {
                $blockinstance->name = get_string('pluginname', 'block_' . $blockinstance->name);
                return self::get_js_tree_node($blockinstance, 'block');
            }, $blockinstances);
        } else {
            return array();
        }
    }

    /**
     * Get the system node and return in node format used by JS.
     *
     * @return array Array containing just the system node.
     */
    public static function get_system_node() {
        $node = new \stdClass();
        $node->name = get_string('systemcontext', 'tool_capexplorer');
        $node->contextid = 1;
        return array(self::get_js_tree_node($node, 'system'));
    }

    /**
     * Get the front page node and return in node format used by JS.
     *
     * This is handled separately from get_course_node() because a slightly
     * different lang string is used when rendering the node.
     *
     * @return array Array containing just the front page node.
     */
    public static function get_frontpage_node() {
        global $DB;
        $sql = "SELECT c.id, c.fullname AS name, ctx.id AS contextid
            FROM {course} c
            JOIN {context} ctx ON c.id = ctx.instanceid
            AND ctx.contextlevel = " . CONTEXT_COURSE . "
            WHERE c.id = ?";
        $node = $DB->get_record_sql($sql, array(SITEID));
        $node->name = get_string('xfrontpage', 'tool_capexplorer', $node->name);
        return array(self::get_js_tree_node($node, 'course'));
    }

    /**
     * Get the 'userdir' node and return in node format used by JS.
     *
     * This is used as a container for the user nodes, to avoid all site
     * users appearing as direct children of the system node (there could
     * be a lot).
     *
     * @return array Array containing just the userdir node.
     */
    public static function get_userdir_node() {
        $node = new \stdClass();
        $node->name = get_string('usercontext', 'tool_capexplorer');
        return array(self::get_js_tree_node($node, 'userdir'));
    }

    /**
     * Given a PHP node object, return an object that can be converted to a JS
     * node via JSON encoding.
     *
     * This is used to decorate the PHP objects with some common properties
     * needed by the JS.
     *
     * @param object $nodeobject A node object with name and optional id and contextid
     * @param string $nodetype String describing the type of node
     * i                         properties.
     * @return object An object that matches the JS node syntax when converted to JSON.
     */
    public static function get_js_tree_node($nodeobject, $nodetype) {

        // Only certain node types have children.
        $canhavechildren = in_array($nodetype,
            array('system', 'userdir', 'category', 'course'));

        $jsnode = new \stdClass();
        $jsnode->label = \html_writer::tag('span',
            format_string($nodeobject->name),
            array('class' => "capexplorer-tree-label capexplorer-tree-{$nodetype}")
        );
        $jsnode->data = new \stdClass();
        $jsnode->data->nodeType = $nodetype;
        if (isset($nodeobject->id)) {
            $jsnode->data->instanceId = $nodeobject->id;
        }
        if (isset($nodeobject->contextid)) {
            $jsnode->data->contextId = $nodeobject->contextid;
        }
        $jsnode->canHaveChildren = $canhavechildren;
        return $jsnode;
    }

}
