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
            if (node.children != undefined && node.children.length != 0) {
                // Children already expanded.
                // This prevents duplicate nodes when initial tree is expanded.
                return;
            }
            var nodeType = node.data.nodeType;
            var instanceId = (node.data.instanceId !== undefined) ? node.data.instanceId : 0;
            var requestdata = {instanceid : instanceId, nodetype: nodeType};

            // TODO Pass admin via config.
            Y.io(M.cfg.wwwroot + '/admin/tool/capexplorer/ajax/getchildnodes.php', {
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

    Y.one('#tree-loading-message').hide();
}


M.tool_capexplorer.menu_set_form_field = function(e) {
    // 'users' node is not a context, so instead of selecting
    // it when clicked, open/close it.
    if (e.node.data.nodeType == 'userdir') {
        e.preventDefault();
        e.node.toggleOpen();
        return;
    }
    // Otherwise, select the node and store the context id.
    var contextid = e.node.data.contextId;
    var input = Y.one('input[name=contextid]');
    input.set('value', contextid);
}


// Recursive function to expand, and then select a node in the tree.
M.tool_capexplorer.menu_select_node = function(Y, parentcontextids, currentNode) {

    // Start from the top if currentNode not set yet.
    if (currentNode == undefined) {
        currentNode = M.tool_capexplorer.tree.rootNode;
        M.tool_capexplorer.menu_select_node(Y, parentcontextids, currentNode);
        return;
    }

    var currentContextId = parentcontextids.shift();

    // The problem is, open() is async for Lazy tree, so children don't
    // exist in time for loop below. Therefore we need to create expanded tree in PHP
    // and only use recursive function to find selected node.
    currentNode.open();

    for (i in currentNode.children) {
        // We've found the right node.
        if (currentNode.children[i].data.contextId == currentContextId) {
            if (parentcontextids.length == 0) {
                // Always open the system node.
                if (currentNode.children[i].data.nodeType == 'system') {
                    currentNode.children[i].open();
                }
                // If we've reached the last node, select it.
                currentNode.children[i].select();
            } else {
                // Otherwise, repeat the process for the child.
                M.tool_capexplorer.menu_select_node(Y, parentcontextids, currentNode.children[i]);
            }
            // No need to check remaining children.
            return;
        }
    }
    // Handle userdir.
    // If previous loop didn't match anything, open the userdir node.
    for (i in currentNode.children) {
        if (currentNode.children[i].data.nodeType == 'userdir') {
            parentcontextids.unshift(currentContextId);
            M.tool_capexplorer.menu_select_node(Y, parentcontextids, currentNode.children[i]);
        }
    }
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

