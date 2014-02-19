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
 * Strings for component 'tool_capexplorer'.
 *
 * @package    tool_capexplorer
 * @copyright  Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assigned'] = 'Assigned';
$string['assignoverridelinks'] = '<a href="{$a->assignurl}">Assign</a> | <a href="{$a->overrideurl}">Override</a>';
$string['blockcontext'] = 'Block context';
$string['capability'] = 'Capability';
$string['capabilityplaceholder'] = 'Enter a capability';
$string['capdenied'] = 'False (Denied)';
$string['capexplorer:view'] = 'View Capability Explorer';
$string['capgranted'] = 'True (Granted)';
$string['change'] = 'Change';
$string['chooseablock'] = 'Choose a block...';
$string['chooseacategory'] = 'Choose a category...';
$string['chooseacategoryfirst'] = 'Choose a category first';
$string['chooseacourse'] = 'Choose a course...';
$string['chooseacoursefirst'] = 'Choose a course first';
$string['chooseamodule'] = 'Choose a module...';
$string['chooseauser'] = 'Choose a user...';
$string['contextaggrrules'] = 'Context aggregation rules';
$string['contextaggrrules_help'] = '<p>To determine the role total for a particular role, aggregate the permissions at each context using the rules below:</p>
<ol>
    <li>If "Prohibit" appears in any context, the role total is "Prohibit".</li>
    <li>If all contexts have the permission "Not set", the role total is "Not set".</li>
    <li>Otherwise, the role total is the same as the most specific permission that is set (i.e. the allow or prevent that\'s closest to the bottom of the context lineage).</li>
</ol>';
$string['contextlevel'] = 'Context level';
$string['contextlineage'] = 'Context lineage';
// TODO write this help:
$string['contextlineage_help'] = 'Context lineage help';
$string['contextlineagesummary'] = '<p>Determine all context levels between the system level and the context being checked.</p>';
$string['coursecatcontext'] = 'Course category context';
$string['coursecontext'] = 'Course context';
$string['error:invalidcapability'] = 'There is no capability called "{$a}"';
$string['error:invalidusername'] = 'There is no user with a username of "{$a}"';
$string['error:noblock'] = 'You must select a block instance.';
$string['error:nocategory'] = 'You must select a category instance.';
$string['error:nocourse'] = 'You must select a course instance.';
$string['error:nomodule'] = 'You must select a module instance.';
$string['error:nouser'] = 'You must select a user instance.';
$string['finalresultsummary'] = '<p>Finally, combine the role totals using the role aggregation rules{$a} to get the overall result.</p>';
$string['instancename'] = 'Instance Name';
$string['instances'] = 'Instance';
$string['modulecontext'] = 'Module (Activity) context';
$string['noblocksfound'] = 'No blocks found';
$string['nocatfrontpage'] = 'No category (Front page course)';
$string['nocoursesfound'] = 'No courses found';
$string['nomodulesfound'] = 'No modules found';
$string['none'] = 'None';
$string['nopermtoassign'] = ''; // TODO what string?
$string['nopermtooverride'] = ''; // TODO what string?
$string['notassigned'] = 'Not Assigned';
$string['overallresult'] = 'Overall result';
$string['permission'] = 'Permission';
$string['permissionallow'] = 'Allow';
$string['permissioninherit'] = 'Inherit';
$string['permissionnotset'] = 'Not set';
$string['permissionprevent'] = 'Prevent';
$string['permissionprohibit'] = 'Prohibit';
$string['permissionunknown'] = 'Unknown';
$string['pluginname'] = 'Capability Explorer';
$string['resultdiffersfromaccesslib'] = '<p>The result calculated by this tool does not match the result from core code!</p><p>You could try <a href="{$a->cacheurl}">clearing your cache</a> but if that doesn\'t help this is probably a bug in Capability Explorer. Please <a href="{$a->bugurl}">let us know about it</a> and if you can include a screenshot of this page to help us track down the problem.</p>';
$string['role'] = 'Role';
$string['roleaggrrules'] = 'Role aggregation rules';
$string['roleaggrrules_help'] = '<p>To determine the overall result, aggregate the permissions from all role totals using the rules below:</p>
<ol>
    <li>If "Prohibit" appears in any role total, the overall result is "Denied".</li>
    <li>Otherwise, if any one role total is "Allow" the overall result is "Granted".</li>
    <li>If none of the role totals are "Allow", the overall result is "Denied".</li>
</ol>';
$string['roleassignmentsforuserx'] = 'Role assignments for user "{$a}"';
$string['roleassignmentsummary'] = '<p>Determine which roles are assigned to the user in any of the parent contexts. Roles can either be assigned manually via role assignments or automatically based on system configuration. Only roles assigned in one of the parent contexts contribute to the final result.</p>';
$string['rolepermissionsandoverridesforcapx'] = 'All role permissions and overrides for capability "{$a}"';
$string['rolepermissionsummary'] = '<p>For each assigned role, list the permission from the role definition for the system context. Also list any role overrides in any other contexts in the context lineage.</p><p>Combine the individual permissions using context aggregation rules{$a} to get a set of role totals.</p>';
$string['roletotal'] = 'Role total';
$string['roletotals'] = 'Role totals';
$string['selectortitle'] = 'Select the capability to explore.';
$string['set'] = 'Set';
$string['systemcontext'] = 'System (Site) context';
$string['user'] = 'User';
$string['usercontext'] = 'User context';
$string['userisadmin'] = 'Note: "{$a->user}" is a <a href="{$a->url}">site administrator</a>, and as such they are automatically granted all capabilities. The results below show how their access would be calculated if they weren\'t an admin.';
$string['username'] = 'Username';
$string['usernameplaceholder'] = 'Enter name, username or email';
$string['viaassignment'] = 'Via <a href="{$a}">role assignment</a>';
$string['viaoverride'] = 'Via <a href="{$a}">role override</a>';
$string['xfrontpage'] = '{$a} (Front page)';

