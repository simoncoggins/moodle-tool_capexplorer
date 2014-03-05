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

/**
 * @namespace
 */
M.tool_capexplorer = M.tool_capexplorer || {};

/**
 * YUI instance holder
 */
M.tool_capexplorer.Y = {};

/**
 * Initialize JS support for the form on index.php
 *
 * @param {Object} Y YUI instance
 */
M.tool_capexplorer.init = function(Y, args) {
    M.tool_capexplorer.Y = Y;

    // Initialise autocomplete on username and capability fields.
    M.tool_capexplorer.init_autocomplete(Y, args);

    var tree = new Y.TreeView({
        container : '#contexttree',
        nodes : [
            {
                label: M.tool_capexplorer.menu_label_with_icon(
                    M.util.get_string('systemcontext', 'tool_capexplorer'), 'system'),
                data: {nodeType: 'system'},
                canHaveChildren: true
            },
            {
                label: M.tool_capexplorer.menu_label_with_icon(
                    M.util.get_string('frontpagecourse', 'tool_capexplorer'), 'course'),
                // TODO pass in SITEID as an argument.
                data: {nodeType: 'course', instanceId: 1},
                canHaveChildren: true
            },
            {
                label: M.tool_capexplorer.menu_label_with_icon(
                    M.util.get_string('usercontext', 'tool_capexplorer'), 'users'),
                data: {nodeType: 'userdir'},
                canHaveChildren: true
            }
        ]
    });

    // Add top-level categories to top level of tree.
    M.tool_capexplorer.menu_load_data(
        'getcategories.php',
        {parentid: 0}, // Top level categories.
        M.tool_capexplorer.menu_load_data_categories,
        tree.rootNode
    );

    tree.on('select', M.tool_capexplorer.menu_set_form_field);

    tree.plug(Y.Plugin.Tree.Lazy, {

        // Custom function that Plugin.Tree.Lazy will call when it needs to
        // load the children for a node.
        load: function (node, callback) {
            var nodeType = node.data.nodeType;

            switch (nodeType) {
            case 'userdir':
                M.tool_capexplorer.menu_load_data(
                    'getusers.php',
                    {},
                    M.tool_capexplorer.menu_load_data_user,
                    node
                );
                break;
            case 'course':
                M.tool_capexplorer.menu_load_data(
                    'getmodules.php',
                    {courseid: node.data.instanceId},
                    M.tool_capexplorer.menu_load_data_module,
                    node
                );
                M.tool_capexplorer.menu_load_data(
                    'getblocks.php',
                    {contextlevel: 50, instanceid: node.data.instanceId},
                    M.tool_capexplorer.menu_load_data_block,
                    node
                );
                break;
            case 'category':
                M.tool_capexplorer.menu_load_data(
                    'getcategories.php',
                    {parentid: node.data.instanceId},
                    M.tool_capexplorer.menu_load_data_categories,
                    node
                );
                M.tool_capexplorer.menu_load_data(
                    'getcourses.php',
                    {categoryid: node.data.instanceId},
                    M.tool_capexplorer.menu_load_data_courses,
                    node
                );
                M.tool_capexplorer.menu_load_data(
                    'getblocks.php',
                    {contextlevel: 40, instanceid: node.data.instanceId},
                    M.tool_capexplorer.menu_load_data_block,
                    node
                );
                break;
            case 'system':
                M.tool_capexplorer.menu_load_data(
                    'getblocks.php',
                    {contextlevel: 10},
                    M.tool_capexplorer.menu_load_data_block,
                    node
                );
                break;
            }

            callback();
        }

    });

    tree.render();

    // TODO open tree to selected element (if any)
    // use this to locate node: http://smugmug.github.io/yui-gallery/api/classes/TreeView.html#method_findNode
    // then this to select it: http://smugmug.github.io/yui-gallery/api/classes/TreeView.html#method_selectNode
    // might need to open too.
}

M.tool_capexplorer.menu_label_with_icon = function(text, iconType) {
    var iconTypeClass = 'capexplorer-tree-'+iconType;
    return '<span class="capexplorer-tree-label ' + iconTypeClass+'">' + text + '</span>';
}

M.tool_capexplorer.menu_load_data_categories = function(node, data) {
    var subcats =  [];
    for (var catid in data) {
        if (data.hasOwnProperty(catid)) {
            subcats.push({
                label: M.tool_capexplorer.menu_label_with_icon(data[catid], 'category'),
                data: {nodeType: 'category', instanceId: catid},
                canHaveChildren: true
            });
        }
    }
    node.append(subcats);
};

M.tool_capexplorer.menu_load_data_courses = function(node, data) {
    var courses =  [];
    for (var courseid in data) {
        if (data.hasOwnProperty(courseid)) {
            courses.push({
                label: M.tool_capexplorer.menu_label_with_icon(data[courseid], 'course'),
                data: {nodeType: 'course', instanceId: courseid},
                canHaveChildren: true
            });
        }
    }
    node.append(courses);
};

M.tool_capexplorer.menu_load_data_user = function(node, data) {
    var users =  [];
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            users.push({
                label: M.tool_capexplorer.menu_label_with_icon(data[key]['fullname'], 'user'),
                data: {nodeType: 'user', instanceId: data[key]['id']}
            });
        }
    }
    node.append(users);
};

M.tool_capexplorer.menu_load_data_module = function(node, data) {
    var modules =  [];
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            modules.push({
                label: M.tool_capexplorer.menu_label_with_icon(data[key], 'module'),
                data: {nodeType: 'module', instanceId: key}
            });
        }
    }
    node.append(modules);
};

M.tool_capexplorer.menu_load_data_block = function(node, data) {
    var blocks =  [];
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            blocks.push({
                label: M.tool_capexplorer.menu_label_with_icon(data[key], 'block'),
                data: {nodeType: 'block', instanceId: key}
            });
        }
    }
    node.append(blocks);
};

// Generic function for loading data via IO request.
M.tool_capexplorer.menu_load_data = function(ajaxfile, requestdata, handler, node) {
    // TODO Pass admin via config.
    Y.io(M.cfg.wwwroot + '/admin/tool/capexplorer/ajax/' + ajaxfile, {
        on:   {success:
            function(id, r) {
                try {
                    parsedResponse = Y.JSON.parse(r.responseText);
                }
                catch (e) {
                    alert("JSON Parse failed!");
                    return;
                }
                if (parsedResponse.error !== undefined) {
                    alert(parsedResponse.error);
                    return;
                }
                // Call handler method to add nodes based on data.
                handler(node, parsedResponse.options);
            }
        },
        data: requestdata
    });
};

M.tool_capexplorer.get_context_level_from_node = function(node) {
    var nodeType = node.data.nodeType;
    // TODO pass constants in from PHP?
    switch (nodeType) {
    case 'system':
        return 10;
    case 'user':
    case 'userdir':
        return 30;
    case 'category':
        return 40;
    case 'course':
        return 50;
    case 'module':
        return 70;
    case 'block':
        return 80;
    }
    return null;
}

M.tool_capexplorer.menu_set_form_field = function(e) {
    var contextLevel = M.tool_capexplorer.get_context_level_from_node(e.node);
    var instanceId = (e.node.data.instanceId === undefined) ? 0 : e.node.data.instanceId;

    // TODO Pass admin via config.
    Y.io(M.cfg.wwwroot + '/admin/tool/capexplorer/ajax/getcontextid.php', {
        on:   {success:
            function(id, r) {
                var contextid = r.responseText;
                var input = Y.one('input[name=contextid]');
                input.set('value', contextid);
            }
        },
        data: {contextlevel: contextLevel, instanceid: instanceId}
    });
}

M.tool_capexplorer.init_autocomplete = function(Y, args) {
    Y.one('body').addClass('yui3-skin-sam');

    Y.one('#id_username').plug(Y.Plugin.AutoComplete, {
        resultFilters: 'phraseMatch',
        resultTextLocator: 'username',
        resultListLocator: 'options',
        // TODO Pass admin via config.
        source: M.cfg.wwwroot + '/admin/tool/capexplorer/ajax/getusers.php?search={query}',
        resultFormatter: function(query, results) {
            return Y.Array.map(results, function(result) {
                return Y.Highlight.all(result.raw.data, query);
            });
        },
    });

    Y.one('#id_capability').plug(Y.Plugin.AutoComplete, {
        resultHighlighter: 'phraseMatch',
        resultFilters: 'phraseMatch',
        source: args['capabilities']
    });
}

