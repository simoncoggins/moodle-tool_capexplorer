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
 * @author     Simon Coggins
 * @copyright  2013 Simon Coggins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assigned'] = 'Assigned';
$string['autoassign'] = 'Automatically assigned';
$string['autoassignment'] = 'Automatic assignment';
$string['autoassignment_help'] = 'Some roles can be automatically assigned to users in the system context. The roles can be set from Site Administration > Users > Permissions > User Policies';
$string['capability'] = 'Capability';
$string['capability_help'] = '<p>Select a capability to check.</p><p>This field uses autocomplete so start typing the name of a capability then select from the options that appear.</p>';
$string['capabilityplaceholder'] = 'Enter a capability';
$string['capdenied'] = 'False (Denied)';
$string['capexplorer:view'] = 'View capability explorer';
$string['capexplorerresult'] = 'Overall result';
$string['capexplorersummary'] = '<p>Capability Explorer is a tool to help explain how Moodle\'s capability system works. Submit the form below to get an explanation of how the capability check is calculated.</p>';
$string['capgranted'] = 'True (Granted)';
$string['change'] = 'Change';
$string['combineusingcontextaggregation'] = '<p>Combine the individual permissions using context aggregation rules{$a} to get a set of role totals.</p>';
$string['context'] = 'Context';
$string['context_help'] = '<p>You must provide a <em>context instance</em> to check a capability against. The tree shown displays the hierarchy of all the context instances on your site.</p>
<p>Expand nodes by clicking the arrow to see more specific child contexts. Select an instance by clicking the name.</p>
<p>The icons represent the <em>context level</em> of each instance:</p>
<p>
<div class="capexplorer-tree-label capexplorer-tree-system">System (Site) context</div>
<div class="capexplorer-tree-label capexplorer-tree-user">User context</div>
<div class="capexplorer-tree-label capexplorer-tree-category">Category context</div>
<div class="capexplorer-tree-label capexplorer-tree-course">Course context</div>
<div class="capexplorer-tree-label capexplorer-tree-module">Module context</div>
<div class="capexplorer-tree-label capexplorer-tree-block">Block context</div>
</p>';
$string['contextaggrrules'] = 'Context aggregation rules';
$string['contextaggrrules_help'] = '<p>To determine the role total for a particular role, aggregate the permissions at each context using the rules below:</p>
<ol>
    <li>If "Prohibit" appears in any context, the role total is "Prohibit".</li>
    <li>If all contexts have the permission "Not set", the role total is "Not set".</li>
    <li>Otherwise, the role total is the same as the most specific permission that is set (i.e. the allow or prevent that\'s closest to the context in which the capability is being checked).</li>
</ol>';
$string['contextinfo'] = '{$a->contextstring} ({$a->contextlevel} context)';
$string['contextlevel'] = 'Context level';
$string['error:invalidcapability'] = 'There is no capability called "{$a}"';
$string['error:invalidcontext'] = 'You must select a context instance';
$string['error:invalidusername'] = 'There is no user with a username of "{$a}"';
$string['error:missingcapability'] = 'You must enter a capability';
$string['error:missingusername'] = 'You must enter a username';
$string['exploreanother'] = '&laquo; Explore another capability';
$string['finalresultsummary'] = '<p>Finally, combine the role totals using the role aggregation rules{$a} to get the overall result.</p>';
$string['guestaccessblocked'] = '<p>Note: As an additional safety measure Moodle prevents unprivileged users from being granted "risky" capabilities. "{$a->capability}" is deemed risky because it could be used to edit or remove data, modify site configuration, or add potentially malicious scripts into site pages. The results below show how their access would be calculated if the capability wasn\'t considered risky.</p>';
$string['instancename'] = 'Instance Name';
$string['manualassign'] = 'Manually assigned';
$string['manualassignment'] = 'Manual assignment';
$string['manualassignment_help'] = 'Roles assigned directly to a specific user, for example via \'Assign system roles\', or via course enrolments.';
$string['none'] = 'None';
$string['nopermtoassign'] = 'No permission';
$string['nopermtoassign_help'] = '<p>Not all users have permission to assign roles to other users. The ability to assign roles is dependent on your own roles and can be controlled here:</p><p><em>Site admin &gt; Users &gt; Permissions &gt; Define roles &gt; Allow role assignments</em></p><p>In addition the user must have the capability "moodle/role:assign" in the context where the role assignment is taking place.</p><p>Site administrators can assign all roles.</p>';
$string['nopermtoautoassign'] = 'No permission';
$string['nopermtoautoassign_help'] = 'Not all users have permission to change automatically assigned roles. The ability to change set these user policies is controlled by the capability "moodle/site:config" in the system context.';
$string['nopermtodefinerole'] = 'No permission';
$string['nopermtodefinerole_help'] = 'The ablity to define role permissions requires the capability "moodle/role:manage" in the system context. Users with permission can control role definitions here:</p><p><em>Site admin &gt; Users &gt; Permissions &gt; Define roles </p><p>The current user does not have this permission so is not able to change role definitions.</p>';
$string['nopermtooverride'] = 'No permission';
$string['nopermtooverride_help'] = 'Not all users have permission to override roles. The ability to override roles is dependent on your own roles and can be controlled here:</p><p><em>Site admin &gt; Users &gt; Permissions &gt; Define roles &gt; Allow role overrides</em></p><p>In addition the user must have the capability "moodle/role:override" or "moodle/role:safeoverride" in the context where the override is taking place.</p><p>Site administrators can override all roles.</p>';
$string['notoverridable'] = 'Not overridable';
$string['notoverridable_help'] = '<p>Each capability defines a context level which is the lowest level the context will be checked in. Below this level the capability is not available to override since the override will not have any effect.</p><p>This prevents capabilities that are clearly not applicable at more specific contexts from cluttering up the override page.</p><p>In this case the capability being checked has specified a higher context level so it is not possible to override this capability at this level.</p>';
$string['notassignable'] = 'Not assignable';
$string['notassignable_help'] = '<p>Each role defines the context levels where the role can be assigned.</p><p>This can be customised by changing the "Context types where this role may be assigned" setting in the role definition:</p><p><em>Site admin &gt; Users &gt; Permissions &gt; Define Roles &gt; [Role name] &gt; Edit</em>.</p>';
$string['notassigned'] = 'Not Assigned';
$string['overallresult'] = 'Overall result';
$string['parentcontexts'] = 'Parent contexts';
$string['parentcontexts_help'] = '<p>Because of the hierarchical nature of the permissions system, assignments at any parent context can impact a capability check in a child context. Therefore the first step is to determine all the contexts between the system level and the context being checked.</p>';
$string['parentcontextssummary'] = '<p>Determine all context levels between the system level and the context being checked.</p>';
$string['permission'] = 'Permission';
$string['permissionallow'] = 'Allow';
$string['permissioninherit'] = 'Inherit';
$string['permissionnotset'] = 'Not set';
$string['permissionprevent'] = 'Prevent';
$string['permissionprohibit'] = 'Prohibit';
$string['permissionunknown'] = 'Unknown';
$string['pluginname'] = 'Capability explorer';
$string['result'] = 'Result';
$string['resultdiffersfromaccesslib'] = '<p>The result calculated by this tool does not match the result from core code!</p><p>You could try <a href="{$a->cacheurl}">clearing your cache</a> but if that doesn\'t help this is probably a bug in Capability Explorer. Please <a href="{$a->bugurl}">let us know about it</a> and if you can include a screenshot of this page to help us track down the problem.</p>';
$string['role'] = 'Role';
$string['roleaggrrules'] = 'Role aggregation rules';
$string['roleaggrrules_help'] = '<p>To determine the overall result, aggregate the permissions from all role totals using the rules below:</p>
<ol>
    <li>If "Prohibit" appears in any role total, the overall result is "Denied".</li>
    <li>Otherwise, if any one role total is "Allow" the overall result is "Granted".</li>
    <li>If none of the role totals are "Allow", the overall result is "Denied".</li>
</ol>';
$string['roleassignmentsforuserx'] = '<p>Below are all roles that have assignments for "{$a}" in any of the contexts listed above:</p>';
$string['roleassignmentsummary'] = '<p>Determine which roles are assigned to the user in any of the parent contexts. Only roles assigned in one of the parent contexts contribute to the final result.</p>';
$string['rolepermissionsandoverridesforcapx'] = '<p>All role permissions and overrides for capability "{$a}"</p>';
$string['rolepermissionsummary'] = '<p>For each assigned role, list the permission from the role definition for the system context. Also list any role overrides in any of the parent contexts.</p>';
$string['roletotal'] = 'Role total';
$string['roletotals'] = 'Role totals';
$string['step1'] = 'Step 1: Parent contexts';
$string['step2'] = 'Step 2: Role assignments';
$string['step3'] = 'Step 3: Role permissions and overrides';
$string['step4'] = 'Step 4: Aggregate across contexts';
$string['step5'] = 'Step 5: Aggregate across roles';
$string['systemcontext'] = 'System (Site) context';
$string['user'] = 'User';
$string['usercontext'] = 'User context';
$string['userisadmin'] = '<p>Note: "{$a->user}" is a <a href="{$a->url}">site administrator</a>, and as such they are automatically granted all capabilities. The results below show how their access would be calculated without site administrator privileges.</p>';
$string['username'] = 'Username';
$string['username_help'] = '<p>Select a user to check.</p><p>This field uses autocomplete so start typing a username, email address or user\'s name and select from the options that appear.</p>';
$string['usernameplaceholder'] = 'Enter name, username or email';
$string['xfrontpage'] = '{$a} (Front page)';

