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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/capexplorer/locallib.php');

/**
 * Automated unit testing of locallib.php functions.
 *
 * @package tool_capexplorer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_generator_capexplorer_testcase extends advanced_testcase {

    public function test_get_context_info() {
        $systemcontext = context_system::instance();
        $result = tool_capexplorer_get_context_info($systemcontext);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertObjectHasAttribute('contextlevel', $result);
        $this->assertObjectHasAttribute('instance', $result);
    }

    public function test_get_parent_context_info() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $usercontextinfo = tool_capexplorer_get_context_info($usercontext);
        $systemcontext = context_system::instance();
        $systemcontextinfo = tool_capexplorer_get_context_info($systemcontext);

        $parentinfo = tool_capexplorer_get_parent_context_info($usercontext);
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

        $result = tool_capexplorer_get_role_assignment_info($contexts, $user->id);

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

        $result = tool_capexplorer_get_auto_role_assignment_info($user->id);

        $expectedresult = array(
            $systemcontext->id => array(
                $role1 => 'defaultuserroleid'
            )
        );
        $this->assertEquals($expectedresult, $result);

        $this->setGuestUser();

        $result = tool_capexplorer_get_auto_role_assignment_info($USER->id);

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

        $this->assertTrue(tool_capexplorer_role_is_auto_assigned($role1));
        $this->assertTrue(tool_capexplorer_role_is_auto_assigned($role2));
        $this->assertFalse(tool_capexplorer_role_is_auto_assigned($role3));
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

        $manualassignments = tool_capexplorer_get_role_assignment_info($contexts, $user->id);

        // Set a default role.
        $defaultrole = create_role('Default Role', 'defaultrole', 'Default Role description');
        set_config('defaultuserroleid', $defaultrole);

        $autoassignments = tool_capexplorer_get_auto_role_assignment_info($user->id);

        $result = tool_capexplorer_get_assigned_roles($manualassignments, $autoassignments);
        $assignedroleids = array_keys($result);

        // Assigned roles should be in the results.
        $this->assertContains($role1, $assignedroleids);
        $this->assertContains($role2, $assignedroleids);
        $this->assertContains($role3, $assignedroleids);
        $this->assertContains($defaultrole, $assignedroleids);
        // Roles that haven't been assigned shouldn't be in the results.
        $this->assertNotContains($unassigned, $assignedroleids);

        // The elements should contain a role database object.
        $role1object = $DB->get_record('role', array('id'=>$role1), '*', MUST_EXIST);
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

        $result = tool_capexplorer_get_role_override_info($contextids, $roleids, $capability);

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
     * @dataProvider permissions_data
     */
    public function test_merge_permissions($p1, $p2, $expectedresult) {
        $this->assertEquals($expectedresult,
            tool_capexplorer_merge_permissions($p1, $p2));
    }

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
     * @dataProvider permissions_across_roles_data
     */
    public function test_merge_permissions_across_roles($roletotals, $expectedresult) {
        $this->assertEquals($expectedresult,
            tool_capexplorer_merge_permissions_across_roles($roletotals));
    }

    /**
     * TODO reenable this test but in a less random fashion.
     *
     * Tests that capexplorer gives the same results as the native {@link has_capability()}.
     */
    /*
    public function test_tool_capexplorer_has_capability() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $catids = $DB->get_fieldset_select('course_categories', 'id', '');

        // Create category contexts to test:
        $contextstotest = array();
        foreach (range(1, 10) as $i) {
            if (rand(1, 100) <= 15) {
                // New top level category.
                $parent = 0;
            } else {
                // Child of existing category.
                $parent = $catids[array_rand($catids)];
            }
            $cat = $this->getDataGenerator()->create_category(array('parent' => $parent));
            // Add new category to list of existing categories.
            $catids[] = $cat->id;

            // Track as a context to test.
            $contextstotest[] = context_coursecat::instance($cat->id);
        }

        // Create course and module contexts to test.
        foreach (range(1, 10) as $i) {
            $course = $this->getDataGenerator()->create_course(array('category' => $catids[array_rand($catids)]));
            $contextstotest[] = context_course::instance($course->id);

            foreach (range(1, 10 as $j) {
                $module = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
                $contextstotest[] = context_module::instance($module->id);
            }
        }

        // Create users and user contexts to test.
        $userstotest = array();
        foreach (range(1, 10) as $i) {
            $user = $this->getDataGenerator()->create_user();
            $contextstotest[] = context_user::instance($user->id);
            $userstotest[] = $user;
        }
        // TODO Always test admin and guest users.

        // Create roles.
        $rolestotest = array();
        foreach (range(1, 10) as $i) {
            $rolestotest[] = create_role("Role {$i}", "role{$i}", "Role {$i} description");
        }

        // Always test the system context.
        $systemcontext = context_system::instance();
        $contextstotest[] = $systemcontext;

        $allcaps = $DB->get_records_menu('capabilities', array(), 'id, name');
        // Assign system permissions and overrides.
        foreach ($contextstotest as $ctxid) {
            $issystemcontext = ($ctxid == $systemcontext->id);
            $context = context::instance_by_id($ctxid);
            foreach ($rolestotest as $roleid) {
                foreach ($allcaps as $cap) {
                    $perm = $this->get_random_permission_value($issystemcontext);
                    if (!is_null($perm)) {
                        assign_capability($cap, $perm, $roleid, $context);
                    }
                }
            }
        }

        // Assigning roles.
        foreach ($userstotest as $user) {
            foreach ($rolestotest as $roleid) {
                foreach ($contextstotest as $context) {
                    // Only assign in 1/4 of contexts.
                    if (rand(1, 100) >= 75) {
                        $this->getDataGenerator()->role_assign($roleid, $user->id, $context->id);
                    }
                }
            }
        }

        // Ensure caches are reset.
        reload_all_capabilities();

        // Actual tests.
        foreach ($userstotest as $user) {
            // TODO Could do this but still need to ignore admin rights.
            // Run test as a user.
            //$this->setUser($user);
            foreach ($rolestotest as $roleid) {
                foreach ($contextstotest as $context) {
                    // Only test in 1/4 of contexts.
                    if (rand(1, 100) >= 75) {
                        // Test 10 caps for every user/role/context combination.
                        shuffle($allcaps);
                        $capstotest = array_slice($allcaps, 0, 10);
                        foreach ($capstotest as $cap) {
                            $hc = has_capability($cap, $context, $user->id, true);
                            $cehc = tool_capexplorer_has_capability($cap, $context, $user->id);
                            $this->assertEquals($hc, $cehc
                                "Mismatch when checking capability '{$cap}' in context '{$context->id}' for user '{$user->id}'."
                            );
                        }
                    }
                }
            }
        }
    }
     */

    /**
     * Return a random permission with a fixed probability.
     *
     * Used to populate role permissions and overrides. Each individual
     * permission is weighted to ensure a reasonable distribution of
     * data to test.
     *
     * @return int|null Permission constant, e.g. CAP_* or null.
     */
    /*
    public function get_random_permission_value($systemcontext = true) {

        // Assignment is more likely in the system context than for overrides.
        if ($systemcontext) {
            $probmap = array(
                5 => CAP_PROHIBIT,
                20 => CAP_PREVENT,
                50 => CAP_ALLOW,
                // CAP_INHERIT and null both mean not set, but include both separately
                // so we test both cases work as expected.
                75 => CAP_INHERIT,
                100 => null
            );
        } else {
            $probmap = array(
                5 => CAP_PROHIBIT,
                15 => CAP_PREVENT,
                30 => CAP_ALLOW,
                // CAP_INHERIT and null both mean not set, but include both separately
                // so we test both cases work as expected.
                65 => CAP_INHERIT,
                100 => null
            );
        }

        $rand = rand(1, 100);
        foreach ($probmap as $chance => $value) {
            if ($rand <= $chance) {
                return $value;
            }
        }

        return null;
    }
     */
}
