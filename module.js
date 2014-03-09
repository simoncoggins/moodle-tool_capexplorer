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

    // Load tree with initial data from PHP.
    M.tool_capexplorer.tree = new Y.TreeView({
        container : '#contexttree',
        nodes : args.initialtree,
    });

    M.tool_capexplorer.tree.on('select', M.tool_capexplorer.menu_set_form_field);

    M.tool_capexplorer.tree.plug(Y.Plugin.Tree.Lazy, {

        // Custom function that Plugin.Tree.Lazy will call when it needs to
        // load the children for a node.
        load: function (node, callback) {
            var nodeType = node.data.nodeType;
            var instanceId = (node.data.instanceId !== undefined) ? node.data.instanceId : 0;
            var requestdata = {instanceid : instanceId, nodetype: nodeType};

            // TODO Pass admin via config.
            Y.io(M.cfg.wwwroot + '/admin/tool/capexplorer/ajax/get_child_nodes.php', {
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
                        node.append(parsedResponse);
                    }
                },
                data: requestdata
            });

            callback();
        }

    });

    M.tool_capexplorer.tree.render();

}


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

    // TODO include contextid in data so we don't need to do this.
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

M.tool_capexplorer.menu_select_node = function(Y, args) {

    // use this to locate node: http://smugmug.github.io/yui-gallery/api/classes/TreeView.html#method_findNode
    // then this to select it: http://smugmug.github.io/yui-gallery/api/classes/TreeView.html#method_selectNode
    // TODO this doesn't work because nodes have no children until expanded:
    var node = M.tool_capexplorer.tree.findNode(
        M.tool_capexplorer.tree.rootNode,
        M.tool_capexplorer.find_node_by_info,
        args
    );

    // TODO open tree to selected element (if any)
    // args should be parent context ids
    // nodetoselect = rootnode
    // foreach contextid
    //   nodetoselect = nodetoselect.findNode(uses current contextid)
    //   TODO might not work because expand is async?
    //   nodetoselect.expand();
    // end
    // nodetoselect.selectNode();
}

M.tool_capexplorer.find_node_by_info = function(node) {
    var contextLevel = M.tool_capexplorer.get_context_level_from_node(node);
    return (contextLevel == this.contextlevel &&
        node.data.instanceid == this.instanceid);
}


M.tool_capexplorer.init_autocomplete = function(Y, args) {
    Y.one('body').addClass('yui3-skin-sam');

    Y.one('#id_username').plug(Y.Plugin.AutoComplete, {
        resultFilters: 'phraseMatch',
        // TODO using 'autocompletestr' here fixes search but displays full string.
        resultTextLocator: 'username',
        // TODO Pass admin via config.
        source: M.cfg.wwwroot + '/admin/tool/capexplorer/ajax/getusers.php?search={query}',
        resultFormatter: function(query, results) {
            return Y.Array.map(results, function(result) {
                return Y.Highlight.all(result.raw.autocompletestr, query);
            });
        },
    });

    Y.one('#id_capability').plug(Y.Plugin.AutoComplete, {
        resultHighlighter: 'phraseMatch',
        resultFilters: 'phraseMatch',
        source: args['capabilities']
    });
}

