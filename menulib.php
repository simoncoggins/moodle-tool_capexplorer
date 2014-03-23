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
 * Capability Explorer context tree menu functions.
 *
 * @package     tool_capexplorer
 * @copyright   Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


// TODO PHPDocs.

function tool_capexplorer_get_selected_subtree($contextids, $currentnode = null) {

    // Start from the top if currentnode not set yet.
    if (is_null($currentnode)) {
        $currentnode = new stdClass();
        $currentnode->data = new stdClass();
        $currentnode->data->nodeType = 'root';
        return tool_capexplorer_get_selected_subtree($contextids, $currentnode);
    }

    $currentcontextid = array_shift($contextids);

    $nodetype = $currentnode->data->nodeType;
    $instanceid = isset($currentnode->data->instanceId) ? $currentnode->data->instanceId : 0;

    $nodes = tool_capexplorer_get_child_nodes($nodetype, $instanceid);
    if (empty($nodes)) {
        return array();
    }

    foreach ($nodes as $key => $node) {
        if (isset($node->data->contextId) && $node->data->contextId == $currentcontextid) {
            $nodes[$key]->children = tool_capexplorer_get_selected_subtree($contextids, $node);
        }
    }
    foreach ($nodes as $key => $node) {
        if ($node->data->nodeType == 'userdir') {
            $nodes[$key]->children = tool_capexplorer_get_user_nodes();
        }
    }
    return $nodes;
}

function tool_capexplorer_get_child_nodes($nodetype, $instanceid = 0) {
    switch ($nodetype) {
        case 'root':
            return tool_capexplorer_get_system_node();
        case 'userdir':
            return tool_capexplorer_get_user_nodes();
        case 'system':
            $frontpagenode = tool_capexplorer_get_course_nodes(-1);
            $userdirnode = tool_capexplorer_get_userdir_node();
            $toplevelcatnodes = tool_capexplorer_get_category_nodes(0);
            $blocknodes = tool_capexplorer_get_block_nodes($nodetype, $instanceid);
            return array_merge($frontpagenode, $userdirnode, $toplevelcatnodes, $blocknodes);
        case 'category':
            $categorynodes = tool_capexplorer_get_category_nodes($instanceid);
            $coursenodes = tool_capexplorer_get_course_nodes($instanceid);
            $blocknodes = tool_capexplorer_get_block_nodes($nodetype, $instanceid);
            return array_merge($categorynodes, $coursenodes, $blocknodes);
        case 'course':
            $modulenodes = tool_capexplorer_get_module_nodes($instanceid);
            $blocknodes = tool_capexplorer_get_block_nodes($nodetype, $instanceid);
            return array_merge($modulenodes, $blocknodes);
    }
}

function tool_capexplorer_get_user_nodes() {
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
        return array_map('tool_capexplorer_get_js_tree_node', $users, $nodetypes);
    } else {
        return array();
    }

}

function tool_capexplorer_get_module_nodes($parentcourseid) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($modules = get_array_of_activities($parentcourseid)) {
        $cmids = array_map(function($item) {
            return $item->cm;
        }, $modules);
        $sql = "SELECT cm.instance, ctx.id AS contextid
            FROM {course_modules} cm
            JOIN {context} ctx ON cm.instance = ctx.instanceid
            AND ctx.contextlevel = " . CONTEXT_MODULE;
        $contextmap = $DB->get_records_sql_menu($sql);

        $moduleswithcontext = array_map(function($item) use ($contextmap) {
            $item->contextid = $contextmap[$item->cm];
            return $item;
        }, $modules);

        $nodetypes = array_fill(0, count($modules), 'module');
        return array_map('tool_capexplorer_get_js_tree_node', $moduleswithcontext, $nodetypes);
    } else {
        return array();
    }
}

function tool_capexplorer_get_course_nodes($parentcategoryid) {
    if ($parentcategoryid == -1) {
        return tool_capexplorer_get_frontpage_node();
    }

    if ($courses = get_courses($parentcategoryid, 'c.sortorder ASC',
        'c.id,c.fullname AS name, ctx.id AS contextid')) {
        $nodetypes = array_fill(0, count($courses), 'course');
        return array_map('tool_capexplorer_get_js_tree_node', $courses, $nodetypes);
    } else {
        return array();
    }
}

function tool_capexplorer_get_category_nodes($parentcategoryid) {
    global $DB;
    $sql = "SELECT cc.id,cc.name,ctx.id AS contextid
        FROM {course_categories} cc
        JOIN {context} ctx ON ctx.instanceid = cc.id
            AND ctx.contextlevel = " . CONTEXT_COURSECAT . "
        WHERE cc.parent = ?
        ORDER BY cc.name";
    if ($categories = $DB->get_records_sql($sql, array($parentcategoryid))) {
        $nodetypes = array_fill(0, count($categories), 'category');
        return array_map('tool_capexplorer_get_js_tree_node', $categories, $nodetypes);
    } else {
        return array();
    }
}

function tool_capexplorer_get_block_nodes($parentnodetype, $parentinstanceid = 0) {
    global $DB;
    switch ($parentnodetype) {
    case 'system':
        $parentcontext = CONTEXT_SYSTEM::instance();
        break;
    case 'category':
        $parentcontext = CONTEXT_COURSECAT::instance($parentinstanceid);
        break;
    case 'course':
        $parentcontext = CONTEXT_COURSE::instance($parentinstanceid);
        break;
    default:
        throw new Exception("Invalid nodetype '{$parentnodetype}' passed to tool_capexplorer_get_block_nodes().");
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
            return tool_capexplorer_get_js_tree_node($blockinstance, 'block');
        }, $blockinstances);
    } else {
        return array();
    }
}

function tool_capexplorer_get_system_node() {
    $node = new stdClass();
    $node->name = get_string('systemcontext', 'tool_capexplorer');
    $node->contextid = 1;
    return array(tool_capexplorer_get_js_tree_node($node, 'system'));
}

function tool_capexplorer_get_frontpage_node() {
    global $DB;
    $sql = "SELECT c.id, c.fullname AS name, ctx.id AS contextid
        FROM {course} c
        JOIN {context} ctx ON c.id = ctx.instanceid
        AND ctx.contextlevel = " . CONTEXT_COURSE . "
        WHERE c.id = ?";
    $node = $DB->get_record_sql($sql, array(SITEID));
    $node->name = get_string('xfrontpage', 'tool_capexplorer', $node->name);
    return array(tool_capexplorer_get_js_tree_node($node, 'course'));
}

function tool_capexplorer_get_userdir_node() {
    $node = new stdClass();
    $node->name = get_string('usercontext', 'tool_capexplorer');
    return array(tool_capexplorer_get_js_tree_node($node, 'userdir'));
}

/**
 * Given a PHP node object, return an object that can be converted to a JS
 * node via JSON encoding.
 *
 * @param object $nodeobject A node object with name and optional id and contextid
 * i                         properties.
 * @return object An object that matches the JS node syntax when converted to JSON.
 */
function tool_capexplorer_get_js_tree_node($nodeobject, $nodetype) {

    // Only certain node types have children.
    $canhavechildren = in_array($nodetype,
        array('system', 'userdir', 'category', 'course'));

    $jsnode = new stdClass();
    $jsnode->label = html_writer::tag('span',
        format_string($nodeobject->name),
        array('class' => "capexplorer-tree-label capexplorer-tree-{$nodetype}")
    );
    $jsnode->data = new stdClass();
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

