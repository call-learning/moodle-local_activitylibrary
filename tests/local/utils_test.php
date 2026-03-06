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
 * Unit tests for local utils.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\local;

use local_activitylibrary\customfield\coursemodule_handler;
use local_activitylibrary\test\testcase;

/**
 * Unit tests for utils.
 */
final class utils_test extends testcase {
    /**
     * Reset hidden fields static cache.
     */
    private function reset_hiddenfields_cache(): void {
        $property = new \ReflectionProperty(utils::class, 'hiddenfields');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    /**
     * Test handler component helpers.
     *
     * @covers \local_activitylibrary\local\utils::get_handler_full_component
     * @covers \local_activitylibrary\local\utils::get_hidden_filter_config_name
     */
    public function test_handler_component_helpers(): void {
        $handler = coursemodule_handler::create();

        $this->assertEquals('local_activitylibrary_coursemodule', utils::get_handler_full_component($handler));
        $this->assertEquals(
            'filter_hidden_local_activitylibrary_coursemodule',
            utils::get_hidden_filter_config_name($handler)
        );
    }

    /**
     * Test hidden fields retrieval returns empty when no config exists.
     *
     * @covers \local_activitylibrary\local\utils::get_hidden_fields_filters
     */
    public function test_get_hidden_fields_filters_returns_empty_without_config(): void {
        $handler = coursemodule_handler::create();
        $this->reset_hiddenfields_cache();

        $hidden = utils::get_hidden_fields_filters($handler);
        $this->assertSame([], $hidden);
    }

    /**
     * Test catalog URL helper returns expected local URL.
     *
     * @covers \local_activitylibrary\local\utils::get_catalog_url
     */
    public function test_get_catalog_url_returns_local_activitylibrary_url(): void {
        [$text, $url] = utils::get_catalog_url();

        $this->assertNotEmpty($text);
        $this->assertInstanceOf(\moodle_url::class, $url);
        $this->assertStringContainsString('/local/activitylibrary/index.php', $url->out(false));
    }
}
