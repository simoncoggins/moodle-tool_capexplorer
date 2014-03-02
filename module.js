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

    // Show/hide instance menus depending on context level.
    //Y.one('#id_contextlevel').on('change', M.tool_capexplorer.update_instance_visibility);

    // Initialise autocomplete on username and capability fields.
    M.tool_capexplorer.init_autocomplete(Y, args);

    var tree = new Y.TreeView({
        container : '#contexttree',
        nodes : [
            {
                label: M.util.get_string('systemcontext', 'tool_capexplorer'),
                data: {nodeType: 'system'}
            },
            {
                label: M.util.get_string('frontpagecourse', 'tool_capexplorer'),
                // TODO pass in SITEID as an argument.
                data: {nodeType: 'course', instanceId: 1},
                canHaveChildren: true
            },
            {
                label: M.util.get_string('usercontext', 'tool_capexplorer'),
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

    tree.plug(Y.Plugin.Tree.Lazy, {

        // Custom function that Plugin.Tree.Lazy will call when it needs to
        // load the children for a node.
        load: function (node, callback) {
            var nodeType = node.data.nodeType;

            // Depending on nodeType we might add nodes directly, or delegate task to
            // a handler function called via an AJAX request.
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
                var newnodes =  [
                    {
                        label: M.util.get_string('modulecontext', 'tool_capexplorer'),
                        data: {nodeType: 'moduledir', instanceId: node.data.instanceId},
                        canHaveChildren: true
                    },
                    {
                        label: M.util.get_string('blockcontext', 'tool_capexplorer'),
                        data: {nodeType: 'blockdir', instanceId: node.data.instanceId},
                        canHaveChildren: true
                    }
                ];
                node.append(newnodes);
                break;
            case 'moduledir':
                M.tool_capexplorer.menu_load_data(
                    'getmodules.php',
                    {courseid: node.data.instanceId},
                    M.tool_capexplorer.menu_load_data_module,
                    node
                );
                break;
            case 'blockdir':
                M.tool_capexplorer.menu_load_data(
                    'getblocks.php',
                    {courseid: node.data.instanceId},
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
                break;
            }

            callback();
        }

    });

    tree.render();
    /*
    // Update category menu when context level is changed.
    Y.one('#id_contextlevel').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getcategories.php', 'contextlevel', 'id_categoryinstances'
    );

    // Update course menu when category is changed.
    Y.one('#id_categoryinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getcourses.php', 'categoryid', 'id_courseinstances'
    );

    // Also reset module and block menus when category is changed.
    // The category id is passed as different behaviour is required
    // if selecting "No category (Front page)".
    Y.one('#id_categoryinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getmodules.php', 'categoryid', 'id_moduleinstances'
    );
    Y.one('#id_categoryinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getblocks.php', 'categoryid', 'id_blockinstances'
    );

    // Update module menu when course is changed.
    Y.one('#id_courseinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getmodules.php', 'courseid', 'id_moduleinstances'
    );

    // Update block menu when course is changed.
    Y.one('#id_courseinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getblocks.php', 'courseid', 'id_blockinstances'
    );
    */
}

M.tool_capexplorer.menu_load_data_categories = function(node, data) {
    var subcats =  [];
    for (var catid in data) {
        if (data.hasOwnProperty(catid)) {
            subcats.push({
                label: data[catid],
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
                label: data[courseid],
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
                label: data[key]['fullname'],
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
                label: data[key],
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
                label: data[key],
                data: {nodeType: 'block', instanceId: key}
            });
        }
    }
    node.append(blocks);
};

// Generic function for loading data via IO request.
M.tool_capexplorer.menu_load_data = function(ajaxfile, requestdata, handler, node) {
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

M.tool_capexplorer.update_menu = function(e, ajaxfile, ajaxarg, targetmenuid) {
    var requestdata = {}
    requestdata[ajaxarg] = this.get('value');

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
                M.tool_capexplorer.populate_menu(targetmenuid, parsedResponse.options);
                M.tool_capexplorer.set_menu_state(targetmenuid, parsedResponse.disabled);
            }
        },
        data: requestdata
    });
}

M.tool_capexplorer.set_menu_state = function(id, disabled) {
    var select = Y.one('#'+id);
    if (disabled) {
        select.setAttribute('disabled', 'disabled');
    } else {
        select.removeAttribute('disabled');
    }
}

M.tool_capexplorer.populate_menu = function(id, options) {
    // Cache select node and clear existing options.
    var select = Y.one('#'+id).empty();

    // Append new options.
    for (value in options) {
        var option = '<option value="' + value + '">' + options[value] + '</options>';
        Y.Node.create(option).appendTo(select);
    }

}

M.tool_capexplorer.init_autocomplete = function(Y, args) {
    Y.one('body').addClass('yui3-skin-sam');

    Y.one('#id_username').plug(Y.Plugin.AutoComplete, {
        resultFilters: 'phraseMatch',
        resultTextLocator: 'username',
        resultListLocator: 'options',
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

M.tool_capexplorer.update_instance_visibility = function() {
    // Value of the contextlevel menu.
    var value       = this.get('value');

    // Defines which menus to show under instance field when context menu changed.
    var states = {
        'system'   : { 'system' : 1, 'user' : 0, 'category' : 0, 'course' : 0, 'module' : 0, 'block' : 0 },
        'user'     : { 'system' : 0, 'user' : 1, 'category' : 0, 'course' : 0, 'module' : 0, 'block' : 0 },
        'category' : { 'system' : 0, 'user' : 0, 'category' : 1, 'course' : 0, 'module' : 0, 'block' : 0 },
        'course'   : { 'system' : 0, 'user' : 0, 'category' : 1, 'course' : 1, 'module' : 0, 'block' : 0 },
        'module'   : { 'system' : 0, 'user' : 0, 'category' : 1, 'course' : 1, 'module' : 1, 'block' : 0 },
        'block'    : { 'system' : 0, 'user' : 0, 'category' : 1, 'course' : 1, 'module' : 0, 'block' : 1 }
    };

    // Menu states for the current context choice.
    var menustate = states[value];

    // Add or remove 'hidden-field' class as required.
    for (var context in menustate) {
        var element = Y.one('#id_'+context+'instances');
        if (menustate[context]) {
            element.removeClass('hidden-field');
        } else {
            element.addClass('hidden-field');
        }
    }
}
