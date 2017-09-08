YUI.add('moodle-tool_capexplorer-capexplorer', function (Y, NAME) {

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
 * @author     Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @namespace
 */
M.tool_capexplorer = M.tool_capexplorer || {};

M.tool_capexplorer.capexplorer = {
    /**
     * Initialize JS support for the form on index.php
     */
    init: function(args) {
        var parsedResponse, nodeType;

        // Initialise autocomplete on username and capability fields.
        this.init_autocomplete(args);

        // Load tree with initial data from PHP.
        this.tree = new Y.TreeView({
            container: '#contexttree',
            nodes: args.initialtree
        });

        this.tree.on('select', this.tree_set_form_field);

        this.tree.plug(Y.Plugin.Tree.Lazy, {

            // Custom function that Plugin.Tree.Lazy will call when it needs to
            // load the children for a node.
            load: function(node, callback) {
                var instanceId, requestdata;
                if (node.children !== undefined && node.children.length !== 0) {
                    // Children already expanded.
                    // This prevents duplicate nodes when initial tree is expanded.
                    return;
                }
                nodeType = node.data.nodeType,
                    instanceId = (node.data.instanceId !== undefined) ? node.data.instanceId : 0,
                    requestdata = {instanceid: instanceId, nodetype: nodeType, sesskey: M.cfg.sesskey};

                Y.io(M.cfg.wwwroot + '/' + args.admin +
                    '/tool/capexplorer/ajax/getchildnodes.php', {
                    on:   {success:
                        function(id, r) {
                            try {
                                parsedResponse = Y.JSON.parse(r.responseText);
                            } catch (e) {
                                window.alert("JSON Parse failed!");
                                return;
                            }
                            if (parsedResponse.error !== undefined) {
                                window.alert(parsedResponse.error);
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

        this.tree.render();

        Y.one('#tree-loading-message').hide();

        this.tree_select_node(Y, args.contextids);
    },

    tree_set_form_field: function(e) {
        // 'users' node is not a context, so instead of selecting
        // it when clicked, open/close it.
        if (e.node.data.nodeType === 'userdir') {
            e.preventDefault();
            e.node.toggleOpen();
            return;
        }
        // Otherwise, select the node and store the context id.
        var contextid = e.node.data.contextId,
            input = Y.one('input[name=contextid]');
        input.set('value', contextid);
    },

    // Recursive function to expand, and then select a node in the tree.
    tree_select_node: function(Y, parentcontextids, currentNode) {
        var currentContextId, i, j;

        // Start from the top if currentNode not set yet.
        if (currentNode === undefined) {
            currentNode = this.tree.rootNode;
            this.tree_select_node(Y, parentcontextids, currentNode);
            return;
        }

        currentContextId = parentcontextids.shift();

        // The problem is, open() is async for Lazy tree, so children don't
        // exist in time for loop below. Therefore we need to create expanded tree in PHP
        // and only use recursive function to find selected node.
        currentNode.open();

        for (i = 0; i < currentNode.children.length; i++) {
            // We've found the right node.
            if (currentNode.children[i].data.contextId === currentContextId) {
                if (parentcontextids.length === 0) {
                    // Always open the system node.
                    if (currentNode.children[i].data.nodeType === 'system') {
                        currentNode.children[i].open();
                    }
                    // If we've reached the last node, select it.
                    currentNode.children[i].select();
                } else {
                    // Otherwise, repeat the process for the child.
                    this.tree_select_node(Y, parentcontextids, currentNode.children[i]);
                }
                // No need to check remaining children.
                return;
            }
        }
        // If previous loop didn't match anything do some further checks.
        for (j = 0; j < currentNode.children.length; j++) {
            // Handle userdir.
            if (currentNode.children[j].data.nodeType === 'userdir') {
                parentcontextids.unshift(currentContextId);
                this.tree_select_node(Y, parentcontextids, currentNode.children[j]);
            }
            // Ensure system node is opened.
            if (currentNode.children[j].data.nodeType === 'system') {
                this.tree_select_node(Y, parentcontextids, currentNode.children[j]);
            }
        }
    },

    init_autocomplete: function(args) {
        var usernameInput = Y.one('#id_username');
        Y.one('body').addClass('yui3-skin-sam');

        usernameInput.plug(Y.Plugin.AutoComplete, {
            source: M.cfg.wwwroot + '/' + args.admin +
                '/tool/capexplorer/ajax/getusers.php?search={query}&sesskey=' + M.cfg.sesskey,
            resultTextLocator: 'username',
            resultFilters: function(query, results) {
                query = query.toLowerCase();
                return Y.Array.filter(results, function(result) {
                    return result.raw.autocompletestr.toLowerCase().indexOf(query) !== -1;
                });
            },
            resultFormatter: function(query, results) {
                return Y.Array.map(results, function(result) {
                    return Y.Highlight.all(result.raw.autocompletestr, query);
                });
            }
        });

        Y.one('#id_capability').plug(Y.Plugin.AutoComplete, {
            resultHighlighter: 'phraseMatch',
            resultFilters: 'phraseMatch',
            source: args.capabilities
        });
    }
};



}, '@VERSION@', {
    "requires": [
        "json",
        "autocomplete",
        "autocomplete-filters",
        "autocomplete-highlighters",
        "gallery-sm-treeview",
        "tree-lazy"
    ]
});
