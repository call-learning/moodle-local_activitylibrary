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
 * Unit tests for lib.php coursemodule hooks.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_activitylibrary;

use global_navigation;
use local_activitylibrary\test\testcase;
use moodle_url;
use navigation_node;

/**
 * Unit tests for plugin lib hooks.
 */
final class lib_hooks_test extends testcase {
    /**
     * local_activitylibrary_coursemodule_standard_elements returns immediately when disabled.
     *
     * @covers ::local_activitylibrary_coursemodule_standard_elements
     */
    public function test_coursemodule_standard_elements_when_disabled(): void {
        global $CFG;
        $CFG->enableactivitylibrary = 0;
        \local_activitylibrary_coursemodule_standard_elements(null, null);
        $this->assertTrue(true);
    }

    /**
     * local_activitylibrary_coursemodule_standard_elements returns when user has no capability.
     *
     * @covers ::local_activitylibrary_coursemodule_standard_elements
     */
    public function test_coursemodule_standard_elements_without_capability(): void {
        global $CFG;
        $CFG->enableactivitylibrary = 1;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $formwrapper = new class {
            /**
             * Return system context for hook invocation.
             *
             * @return \context_system
             */
            public function get_context() {
                return \context_system::instance();
            }
        };

        \local_activitylibrary_coursemodule_standard_elements($formwrapper, null);
        $this->assertTrue(true);
    }

    /**
     * Extend navigation is a no-op when disabled.
     *
     * @covers ::local_activitylibrary_extend_navigation
     */
    public function test_extend_navigation_when_disabled(): void {
        global $CFG, $PAGE;
        $CFG->enableactivitylibrary = 0;
        $PAGE->set_url(new moodle_url('/local/activitylibrary/index.php'));
        $nav = new global_navigation($PAGE);
        $nav->add('My courses', new moodle_url('/my/courses.php'), navigation_node::TYPE_SYSTEM, null, 'mycourses');

        \local_activitylibrary_extend_navigation($nav);
        $this->assertFalse((bool)$nav->find('activitylibrary', null));
    }

    /**
     * Extend navigation adds activitylibrary node when enabled.
     *
     * @covers ::local_activitylibrary_extend_navigation
     */
    public function test_extend_navigation_adds_activitylibrary_node_when_enabled(): void {
        global $CFG, $PAGE;
        $CFG->enableactivitylibrary = 1;
        $PAGE->set_url(new moodle_url('/local/activitylibrary/index.php'));
        $nav = new global_navigation($PAGE);
        $nav->add('My courses', new moodle_url('/my/courses.php'), navigation_node::TYPE_SYSTEM, null, 'mycourses');

        \local_activitylibrary_extend_navigation($nav);

        $node = $nav->find('activitylibrary', null);
        $this->assertInstanceOf(navigation_node::class, $node);
        $this->assertTrue($node->showinflatnavigation);
    }

    /**
     * Validation returns empty list when no cm id is provided.
     *
     * @covers ::local_activitylibrary_coursemodule_validation
     */
    public function test_coursemodule_validation_returns_empty_when_no_id(): void {
        $errors = \local_activitylibrary_coursemodule_validation(null, []);
        $this->assertSame([], $errors);
    }

    /**
     * Validation returns empty list when plugin is disabled.
     *
     * @covers ::local_activitylibrary_coursemodule_validation
     */
    public function test_coursemodule_validation_returns_empty_when_disabled(): void {
        global $CFG;
        $CFG->enableactivitylibrary = 0;
        $errors = \local_activitylibrary_coursemodule_validation(null, ['id' => 123]);
        $this->assertSame([], $errors);
    }

    /**
     * Validation delegates to handler when enabled and a valid cm id is provided.
     *
     * @covers ::local_activitylibrary_coursemodule_validation
     */
    public function test_coursemodule_validation_with_valid_id(): void {
        global $CFG;
        $CFG->enableactivitylibrary = 1;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $activity = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Validation target',
        ] + $this->get_simple_cf_data()));
        $cm = get_coursemodule_from_instance('label', $activity->id, $course->id, false, MUST_EXIST);

        $errors = \local_activitylibrary_coursemodule_validation(null, ['id' => $cm->id]);
        $this->assertIsArray($errors);
        $this->assertSame([], $errors);
    }

    /**
     * Edit post actions should be a no-op when plugin is disabled.
     *
     * @covers ::local_activitylibrary_coursemodule_edit_post_actions
     */
    public function test_coursemodule_edit_post_actions_when_disabled(): void {
        global $CFG;
        $CFG->enableactivitylibrary = 0;

        $data = (object)[
            'coursemodule' => 42,
            'name' => 'Unchanged',
        ];

        $result = \local_activitylibrary_coursemodule_edit_post_actions($data, (object)['id' => 1]);
        $this->assertSame($data, $result);
        $this->assertFalse(property_exists($result, 'id'));
    }
}
