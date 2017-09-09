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
 * Automated unit testing of \capexplorer\capexplorer functions.
 *
 * @package     tool_capexplorer
 * @author      Simon Coggins
 * @copyright   2013 Simon Coggins
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for capexplorer.
 *
 * @group tool_capexplorer
 */
class tool_capexplorer_capexplorer_testcase extends advanced_testcase {

    public function test_get_context_info() {
        $systemcontext = context_system::instance();
        $result = \tool_capexplorer\capexplorer::get_context_info($systemcontext);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertObjectHasAttribute('contextlevel', $result);
        $this->assertObjectHasAttribute('instance', $result);
    }

    public function test_get_parent_context_info() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $usercontextinfo = \tool_capexplorer\capexplorer::get_context_info($usercontext);
        $systemcontext = context_system::instance();
        $systemcontextinfo = \tool_capexplorer\capexplorer::get_context_info($systemcontext);

        $parentinfo = \tool_capexplorer\capexplorer::get_parent_context_info($usercontext);
        // Should contain system and user context info.
        $this->assertCount(2, $parentinfo);
        // The first item should be the system context info.
        $firstitem = array_shift($parentinfo);
        $this->assertEquals($systemcontextinfo, $firstitem);
        // The second item should be the user context info.
        $seconditem = array_shift($parentinfo);
        $this->assertEquals($usercontextinfo, $seconditem);
    }

    public function test_get_role_assignment_info() {
        $this->resetAfterTest();

        // Create a user and some contexts.
        $systemcontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $cat = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($cat->id);
        $course = $this->getDataGenerator()->create_course(array('category' => $cat->id));
        $coursecontext = context_course::instance($course->id);
        $contexts = array($systemcontext, $usercontext, $categorycontext, $coursecontext);

        // Create some roles.
        $role1 = create_role('Role 1', 'role1', 'Role 1 description');
        $role2 = create_role('Role 2', 'role2', 'Role 2 description');
        $role3 = create_role('Role 3', 'role3', 'Role 3 description');
        $role4 = create_role('Role 4', 'role4', 'Role 4 description');
        $role5 = create_role('Role 5', 'role5', 'Role 5 description');
        $unassigned = create_role('Unassigned Role', 'unassignedrole', 'Unassigned role description');

        // Assign the roles to the test user in various contexts.
        $this->getDataGenerator()->role_assign($role1, $user->id, $systemcontext->id);
        $this->getDataGenerator()->role_assign($role2, $user->id, $systemcontext->id);
        $this->getDataGenerator()->role_assign($role2, $user->id, $usercontext->id);
        $this->getDataGenerator()->role_assign($role3, $user->id, $categorycontext->id);
        $this->getDataGenerator()->role_assign($role4, $user->id, $coursecontext->id);
        $this->getDataGenerator()->role_assign($role5, $user->id, $coursecontext->id);

        $result = \tool_capexplorer\capexplorer::get_role_assignment_info($contexts, $user->id);

        $expectedresult = array(
            $systemcontext->id => array(
                $role1 => true,
                $role2 => true,
            ),
            $usercontext->id => array(
                $role2 => true
            ),
            $categorycontext->id => array(
                $role3 => true
            ),
            $coursecontext->id => array(
                $role4 => true,
                $role5 => true
            )
        );
        $this->assertEquals($expectedresult, $result);
    }

    public function test_get_auto_role_assignment_info() {
        global $USER;
        $this->resetAfterTest();
        $systemcontext = context_system::instance();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $role1 = create_role('Role 1', 'role1', 'Role 1 description');
        $role2 = create_role('Role 2', 'role2', 'Role 2 description');
        $unassigned = create_role('Unassigned Role', 'unassignedrole', 'Unassigned role description');
        set_config('defaultuserroleid', $role1);
        set_config('guestroleid', $role2);

        $result = \tool_capexplorer\capexplorer::get_auto_role_assignment_info($user->id);

        $expectedresult = array(
            $systemcontext->id => array(
                $role1 => 'defaultuserroleid'
            )
        );
        $this->assertEquals($expectedresult, $result);

        $this->setGuestUser();

        $result = \tool_capexplorer\capexplorer::get_auto_role_assignment_info($USER->id);

        $expectedresult = array(
            $systemcontext->id => array(
                $role2 => 'guestroleid'
            )
        );
        $this->assertEquals($expectedresult, $result);

    }

    public function test_role_is_auto_assigned() {
        $this->resetAfterTest();

        $role1 = create_role('Role 1', 'role1', 'Role 1 description');
        $role2 = create_role('Role 2', 'role2', 'Role 2 description');
        $role3 = create_role('Role 3', 'role3', 'Role 3 description');
        set_config('defaultuserroleid', $role1);
        set_config('guestroleid', $role2);

        $this->assertTrue(\tool_capexplorer\capexplorer::role_is_auto_assigned($role1));
        $this->assertTrue(\tool_capexplorer\capexplorer::role_is_auto_assigned($role2));
        $this->assertFalse(\tool_capexplorer\capexplorer::role_is_auto_assigned($role3));
    }

    public function test_get_assigned_roles() {
        global $DB;
        $this->resetAfterTest();

        // Create a user and some contexts.
        $systemcontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $cat = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($cat->id);
        $course = $this->getDataGenerator()->create_course(array('category' => $cat->id));
        $coursecontext = context_course::instance($course->id);
        $contexts = array($systemcontext, $usercontext, $categorycontext, $coursecontext);
        $this->setUser($user);

        // Create some roles.
        $role1 = create_role('Role 1', 'role1', 'Role 1 description');
        $role2 = create_role('Role 2', 'role2', 'Role 2 description');
        $role3 = create_role('Role 3', 'role3', 'Role 3 description');
        $unassigned = create_role('Unassigned Role', 'unassignedrole', 'Unassigned role description');

        // Assign the roles to the test user in various contexts.
        $this->getDataGenerator()->role_assign($role1, $user->id, $systemcontext->id);
        $this->getDataGenerator()->role_assign($role2, $user->id, $systemcontext->id);
        $this->getDataGenerator()->role_assign($role2, $user->id, $usercontext->id);
        $this->getDataGenerator()->role_assign($role3, $user->id, $categorycontext->id);
        $this->getDataGenerator()->role_assign($role2, $user->id, $coursecontext->id);
        $this->getDataGenerator()->role_assign($role3, $user->id, $coursecontext->id);

        $manualassignments = \tool_capexplorer\capexplorer::get_role_assignment_info($contexts, $user->id);

        // Set a default role.
        $defaultrole = create_role('Default Role', 'defaultrole', 'Default Role description');
        set_config('defaultuserroleid', $defaultrole);

        $autoassignments = \tool_capexplorer\capexplorer::get_auto_role_assignment_info($user->id);

        $result = \tool_capexplorer\capexplorer::get_assigned_roles($manualassignments, $autoassignments);
        $assignedroleids = array_keys($result);

        // Assigned roles should be in the results.
        $this->assertContains($role1, $assignedroleids);
        $this->assertContains($role2, $assignedroleids);
        $this->assertContains($role3, $assignedroleids);
        $this->assertContains($defaultrole, $assignedroleids);
        // Roles that haven't been assigned shouldn't be in the results.
        $this->assertNotContains($unassigned, $assignedroleids);

        // The elements should contain a role database object.
        $role1object = $DB->get_record('role', array('id' => $role1), '*', MUST_EXIST);
        $this->assertEquals($role1object, $result[$role1]);
    }

    public function test_get_role_override_info() {
        $this->resetAfterTest();

        // Create a user and some contexts.
        $systemcontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $cat = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($cat->id);
        $course = $this->getDataGenerator()->create_course(array('category' => $cat->id));
        $coursecontext = context_course::instance($course->id);
        $contextids = array($systemcontext->id, $usercontext->id,
            $categorycontext->id, $coursecontext->id);

        $this->setUser($user);

        // Create some roles.
        $role1 = create_role('Role 1', 'role1', 'Role 1 description');
        $role2 = create_role('Role 2', 'role2', 'Role 2 description');
        $role3 = create_role('Role 3', 'role3', 'Role 3 description');
        $roleids = array($role1, $role2, $role3);

        // Test with 'moodle/site:config' as it isn't set in any role by default.
        $capability = 'moodle/site:config';

        // Assign permissions to our capability in some roles/contexts.
        // System context.
        assign_capability($capability, CAP_ALLOW, $role1, $systemcontext);
        assign_capability($capability, CAP_PROHIBIT, $role3, $systemcontext);
        // User context.
        assign_capability($capability, CAP_PREVENT, $role1, $usercontext);
        assign_capability($capability, CAP_INHERIT, $role2, $usercontext);
        assign_capability($capability, CAP_ALLOW, $role3, $usercontext);
        // Category context.
        assign_capability($capability, CAP_PREVENT, $role2, $categorycontext);
        // Course context.
        assign_capability($capability, CAP_INHERIT, $role1, $coursecontext);
        assign_capability($capability, CAP_ALLOW, $role2, $coursecontext);
        assign_capability($capability, CAP_PROHIBIT, $role3, $coursecontext);

        $result = \tool_capexplorer\capexplorer::get_role_override_info($contextids, $roleids, $capability);

        $expectedresult = array(
            $systemcontext->id => array(
                $role1 => CAP_ALLOW,
                $role2 => null,
                $role3 => CAP_PROHIBIT,
            ),
            $usercontext->id => array(
                $role1 => CAP_PREVENT,
                $role2 => CAP_INHERIT,
                $role3 => CAP_ALLOW,
            ),
            $categorycontext->id => array(
                $role1 => null,
                $role2 => CAP_PREVENT,
                $role3 => null,
            ),
            $coursecontext->id => array(
                $role1 => CAP_INHERIT,
                $role2 => CAP_ALLOW,
                $role3 => CAP_PROHIBIT,
            ),
        );

        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Return data for use by {@link test_merge_permissions()}.
     *
     * @return array Test data.
     */
    public function permissions_data() {
        return array(
            // Prohibit should always win.
            array(CAP_PROHIBIT, CAP_INHERIT, CAP_PROHIBIT),
            array(CAP_PROHIBIT, CAP_ALLOW, CAP_PROHIBIT),
            array(CAP_PREVENT, CAP_PROHIBIT, CAP_PROHIBIT),
            // Other permission should win if one is inherited.
            array(CAP_PREVENT, CAP_INHERIT, CAP_PREVENT),
            array(CAP_ALLOW, CAP_INHERIT, CAP_ALLOW),
            array(CAP_INHERIT, CAP_ALLOW, CAP_ALLOW),
            array(CAP_INHERIT, CAP_PREVENT, CAP_PREVENT),
            // If both inherited, return inherit.
            array(CAP_INHERIT, CAP_INHERIT, CAP_INHERIT),
            // Otherwise use the most specific.
            array(CAP_PREVENT, CAP_ALLOW, CAP_ALLOW),
            array(CAP_ALLOW, CAP_PREVENT, CAP_PREVENT),
        );
    }

    /**
     * Test merge_permissions() function.
     *
     * @param integer $p1 First permission to merge.
     * @param integer $p2 Second permission to merge.
     * @param integer $expectedresult Resulting permission.
     * @dataProvider permissions_data
     */
    public function test_merge_permissions($p1, $p2, $expectedresult) {
        $this->assertEquals($expectedresult,
            \tool_capexplorer\capexplorer::merge_permissions($p1, $p2));
    }

    /**
     * Return data for use by {@link test_merge_permissions_across_roles()}.
     *
     * @return array Test data.
     */
    public function permissions_across_roles_data() {
        return array(
            // Any prohibit results in false.
            array(array(CAP_PROHIBIT, CAP_ALLOW, CAP_ALLOW), false),
            array(array(CAP_ALLOW, CAP_INHERIT, CAP_PROHIBIT), false),
            array(array(CAP_INHERIT, CAP_INHERIT, CAP_PROHIBIT), false),
            array(array(CAP_PROHIBIT, CAP_INHERIT, CAP_PROHIBIT), false),
            // Any one allow without prohibit results in true.
            array(array(CAP_ALLOW, CAP_INHERIT, CAP_INHERIT), true),
            array(array(CAP_INHERIT, CAP_INHERIT, CAP_ALLOW), true),
            array(array(CAP_ALLOW, CAP_ALLOW, CAP_ALLOW), true),
            array(array(CAP_PREVENT, CAP_ALLOW, CAP_INHERIT), true),
            array(array(CAP_PREVENT, CAP_ALLOW, CAP_PREVENT), true),
            array(array(CAP_INHERIT, CAP_INHERIT, CAP_ALLOW), true),
            // None set or prevent only results in false.
            array(array(CAP_INHERIT, CAP_INHERIT, CAP_INHERIT), false),
            array(array(CAP_PREVENT, CAP_INHERIT, CAP_INHERIT), false),
            array(array(CAP_PREVENT, CAP_PREVENT, CAP_INHERIT), false),
        );
    }

    /**
     * Test merge_permissions_across_roles() function.
     *
     * @param array $roletotals Array of permissions from each role.
     * @param integer $expectedresult Expected result of merge across roles.
     * @dataProvider permissions_across_roles_data
     */
    public function test_merge_permissions_across_roles($roletotals, $expectedresult) {
        $this->assertEquals($expectedresult,
            \tool_capexplorer\capexplorer::merge_permissions_across_roles($roletotals));
    }

    public function test_capexplorer_has_capability() {
        global $DB;
        $this->resetAfterTest();

        // Create a user and some contexts.
        $systemcontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $cat = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($cat->id);
        $subcat = $this->getDataGenerator()->create_category(array('parent' => $cat->id));
        $subcatcontext = context_coursecat::instance($subcat->id);
        $course = $this->getDataGenerator()->create_course(array('category' => $subcat->id));
        $coursecontext = context_course::instance($course->id);
        $module = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
        $modulecontext = context_module::instance($module->cmid);
        $block = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $coursecontext->id));
        $blockcontext = context_block::instance($block->id);
        $frontpagecoursecontext = context_course::instance(SITEID);

        $this->setUser($user);

        // Test with 'moodle/site:config' as it isn't set in any role by default.
        $capability = 'moodle/site:config';

        // Create a bunch of test roles for assigning.
        $roles = array();

        // Loop through different combinations of permission settings.
        $systemlevelperms = $overrideperms = array(
            'prohibit' => CAP_PROHIBIT,
            'prevent' => CAP_PREVENT,
            'allow' => CAP_ALLOW,
            'inherit' => CAP_INHERIT,
            'notset' => null
        );
        foreach ($systemlevelperms as $systempermstr => $systemperm) {
            $roles[$systempermstr] = array();
            foreach ($overrideperms as $overridepermstr => $overrideperm) {
                $role = create_role(
                    "system {$systempermstr}, override {$overridepermstr}",
                    "{$systempermstr}system{$overridepermstr}override",
                    "Role with '{$systempermstr}' at system level and '{$overridepermstr}' overriding at course level."
                );
                assign_capability($capability, $systemperm, $role, $systemcontext);
                assign_capability($capability, $overrideperm, $role, $coursecontext);
                $roles[$systempermstr][$overridepermstr] = $role;
            }
        }
        // Ensure caches are reset.
        reload_all_capabilities();

        // No default role.
        set_config('defaultuserroleid', null);

        // With no roles assigned, should not have permission.
        $this->assertFalse(\tool_capexplorer\capexplorer::has_capability($capability, $systemcontext, $user->id));

        // Now test assigning a single role at a time with different permissions and overrides.
        // First check capability in block context (below override).
        $expectedresults = array(
            'prohibit' => array (
                'prohibit' => false,
                'prevent'  => false,
                'allow'    => false,
                'inherit'  => false,
                'notset'   => false,
            ),
            'prevent' => array (
                'prohibit' => false,
                'prevent'  => false,
                'allow'    => true,
                'inherit'  => false,
                'notset'   => false,
            ),
            'allow' => array (
                'prohibit' => false,
                'prevent'  => false,
                'allow'    => true,
                'inherit'  => true,
                'notset'   => true,
            ),
            'inherit' => array (
                'prohibit' => false,
                'prevent'  => false,
                'allow'    => true,
                'inherit'  => false,
                'notset'   => false,
            ),
            'notset' => array (
                'prohibit' => false,
                'prevent'  => false,
                'allow'    => true,
                'inherit'  => false,
                'notset'   => false,
            ),
        );
        foreach ($systemlevelperms as $systempermstr => $systemperm) {
            foreach ($overrideperms as $overridepermstr => $overrideperm) {
                $this->getDataGenerator()->role_assign(
                    $roles[$systempermstr][$overridepermstr],
                    $user->id,
                    $systemcontext->id);
                $this->assertEquals($expectedresults[$systempermstr][$overridepermstr],
                    \tool_capexplorer\capexplorer::has_capability($capability, $blockcontext, $user->id),
                    "Capability check failed with system permission '{$systempermstr}' and " .
                    "course override '{$overridepermstr}' in block context");
                role_unassign($roles[$systempermstr][$overridepermstr], $user->id, $systemcontext->id);
            }
        }

        // Now repeat the test for the 'allow' override only, but check capability at
        // the category level - override should not be applied.
        $expectedresults = array(
            'prohibit' => array('allow' => false),
            'prevent'  => array('allow' => false),
            'allow'    => array('allow' => true),
            'inherit'  => array('allow' => false),
            'notset'   => array('allow' => false),
        );
        $overridepermstr = 'allow';
        $overrideperm = CAP_ALLOW;
        foreach ($systemlevelperms as $systempermstr => $systemperm) {
            $this->getDataGenerator()->role_assign(
                $roles[$systempermstr][$overridepermstr],
                $user->id,
                $systemcontext->id);
            $this->assertEquals($expectedresults[$systempermstr][$overridepermstr],
                \tool_capexplorer\capexplorer::has_capability($capability, $categorycontext, $user->id),
                "Capability check failed with system permission '{$systempermstr}' and " .
                "course override '{$overridepermstr}' in category context");
            role_unassign($roles[$systempermstr][$overridepermstr], $user->id, $systemcontext->id);
        }

        // Make sure scope is limited within a subtree.
        // Assign allowed role in one part of tree and check in another part.
        // Assign in course inside category.
        $this->getDataGenerator()->role_assign(
            $roles['allow']['notset'],
            $user->id,
            $coursecontext->id);
        // Check in front page course.
        $this->assertFalse(\tool_capexplorer\capexplorer::has_capability($capability,
            $frontpagecoursecontext, $user->id));
        role_unassign($roles['allow']['notset'], $user->id, $coursecontext->id);

        // Test default role applies correctly.
        // First check that prohibit in a default role removes access.

        // Allow in system first.
        $this->getDataGenerator()->role_assign(
            $roles['allow']['notset'],
            $user->id,
            $systemcontext->id);

        // Should have access.
        $this->assertTrue(\tool_capexplorer\capexplorer::has_capability($capability,
            $systemcontext, $user->id));

        // Prohibit via default role.
        set_config('defaultuserroleid', $roles['prohibit']['notset']);

        // Shouldn't have access once default role added.
        $this->assertFalse(\tool_capexplorer\capexplorer::has_capability($capability,
            $systemcontext, $user->id));

        role_unassign($roles['allow']['notset'], $user->id, $systemcontext->id);

        // With no roles assigned I shouldn't have access.
        // Shouldn't have access once default role added.
        $this->assertFalse(\tool_capexplorer\capexplorer::has_capability($capability,
            $blockcontext, $user->id));

        // Allow via default role.
        set_config('defaultuserroleid', $roles['allow']['notset']);

        // We should now have access.
        $this->assertTrue(\tool_capexplorer\capexplorer::has_capability($capability,
            $blockcontext, $user->id));

        set_config('defaultuserroleid', null);

        // Check behaviour with multiple conflicting roles.
        // First role just straight prevent at system level.
        $this->getDataGenerator()->role_assign(
            $roles['prevent']['notset'],
            $user->id,
            $systemcontext->id);
        // Shouldn't have access.
        $this->assertFalse(\tool_capexplorer\capexplorer::has_capability($capability,
            $modulecontext, $user->id));
        // Second role defined as prevent but with an allow override.
        // Assigned at course level.
        $this->getDataGenerator()->role_assign(
            $roles['prevent']['allow'],
            $user->id,
            $coursecontext->id);
        // Should have overridden other role due to allow.
        $this->assertTrue(\tool_capexplorer\capexplorer::has_capability($capability,
            $modulecontext, $user->id));
        // Add a prohibit with attempt to override with allow (should not work).
        $this->getDataGenerator()->role_assign(
            $roles['prohibit']['allow'],
            $user->id,
            $categorycontext->id);
        // Prohibit in any role should always prevent access.
        $this->assertFalse(\tool_capexplorer\capexplorer::has_capability($capability,
            $modulecontext, $user->id));

        role_unassign($roles['prevent']['notset'], $user->id, $systemcontext->id);
        role_unassign($roles['prevent']['allow'], $user->id, $coursecontext->id);
        role_unassign($roles['prohibit']['allow'], $user->id, $categorycontext->id);

        // Check that overrides apply even when assigned below the override (e.g.
        // an override is applied to the role, not the assignment).
        //
        // system <- define prevent.
        // course <- override with allow.
        // block <- assign and check.
        $this->getDataGenerator()->role_assign(
            $roles['prevent']['allow'],
            $user->id,
            $blockcontext->id);
        $this->assertTrue(\tool_capexplorer\capexplorer::has_capability($capability,
            $blockcontext, $user->id));
        role_unassign($roles['prevent']['allow'], $user->id, $blockcontext->id);
    }

    public function test_capexplorer_is_guest_access_blocked() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $writecapability = 'moodle/site:config';
        $readcapability = 'moodle/course:view';
        $riskcapability = 'report/security:view';
        // Real user shouldn't be overridden for any capability type.
        $this->assertFalse(\tool_capexplorer\capexplorer::is_guest_access_blocked(
            $writecapability, $user->id));
        $this->assertFalse(\tool_capexplorer\capexplorer::is_guest_access_blocked(
            $readcapability, $user->id));
        // Guest user shouldn't be overridden for read capability.
        $this->assertFalse(\tool_capexplorer\capexplorer::is_guest_access_blocked(
            $readcapability, 1));
        // Guest user should be overridden for write capability.
        $this->assertTrue(\tool_capexplorer\capexplorer::is_guest_access_blocked(
            $writecapability, 1));
        // Guest user should be overridden for a read capability that
        // has config risk.
        $this->assertTrue(\tool_capexplorer\capexplorer::is_guest_access_blocked(
            $riskcapability, 1));
    }
}
