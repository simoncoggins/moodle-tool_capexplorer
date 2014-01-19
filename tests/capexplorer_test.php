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

/**
 * Automated unit testing.
 *
 * @package tool_capexplorer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_generator_capexplorer_testcase extends advanced_testcase {
    /**
     * Tests that capexplorer gives the same results as the native {@link has_capability()}.
     */
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

    /**
     * Return a random permission with a fixed probability.
     *
     * Used to populate role permissions and overrides. Each individual
     * permission is weighted to ensure a reasonable distribution of
     * data to test.
     *
     * @return int|null Permission constant, e.g. CAP_* or null.
     */
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
}
