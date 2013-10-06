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
    Y.one('#id_contextlevel').on('change', M.tool_capexplorer.update_instance_visibility);

    // Initialise autocomplete on username and capability fields.
    M.tool_capexplorer.init_autocomplete(Y, args);

    // Update course menu when category is changed.
    Y.one('#id_categoryinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getcourses.php', 'categoryid', 'id_courseinstances'
    );

    // Also reset module and block menus when category is changed.
    // Passing an unknown url parameter causes menus to reset back
    // to the right value.
    Y.one('#id_categoryinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getmodules.php', 'unused', 'id_moduleinstances'
    );
    Y.one('#id_categoryinstances').on(
        'change',
        M.tool_capexplorer.update_menu, null,
        'getblocks.php', 'unused', 'id_blockinstances'
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
}

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
        resultHighlighter: 'phraseMatch',
        resultFilters: 'phraseMatch',
        source: args['users']
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
