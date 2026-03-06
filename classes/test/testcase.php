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
 * Base testcase for local_activitylibrary.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\test;

use local_activitylibrary\local\utils;

/**
 * Shared base testcase for local_activitylibrary tests.
 */
abstract class testcase extends \advanced_testcase {
    /**
     * @var int|null timestamp used for date custom field assertions
     */
    protected $now = null;

    /**
     * Set up.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        self::generate_category_and_fields($this->getDataGenerator());
    }

    /**
     * Generate basic custom fields used across tests.
     *
     * @param \testing_data_generator $dg
     * @return void
     */
    protected static function generate_category_and_fields(\testing_data_generator $dg): void {
        $generator = $dg->get_plugin_generator('local_activitylibrary');
        foreach (['local_activitylibrary' => 'coursemodule'] as $component => $area) {
            $catid = $generator->create_category(['component' => $component, 'area' => $area])->get('id');
            $generator->create_field(['name' => 'Field 1', 'categoryid' => $catid, 'type' => 'text', 'shortname' => 'f1',
                'area' => $area, 'component' => $component, ]);
            $generator->create_field(['name' => 'Field 2', 'categoryid' => $catid, 'type' => 'checkbox', 'shortname' => 'f2',
                'area' => $area, 'component' => $component, ]);
            $generator->create_field(['name' => 'Field 3', 'categoryid' => $catid, 'type' => 'date', 'shortname' => 'f3',
                'configdata' => ['startyear' => 2000, 'endyear' => 3000, 'includetime' => 1], 'area' => $area,
                'component' => $component, ]);
            if (utils::is_multiselect_installed()) {
                $generator->create_field(['name' => 'Field 4', 'categoryid' => $catid, 'type' => 'multiselect', 'shortname' => 'f4',
                    'configdata' => ['options' => "a\nb\nc"], 'area' => $area, 'component' => $component, ]);
            }
            $generator->create_field(['name' => 'Field 5', 'categoryid' => $catid, 'type' => 'select', 'shortname' => 'f5',
                'configdata' => ['options' => "a\nb\nc"], 'area' => $area, 'component' => $component, ]);
            $generator->create_field(['name' => 'Field 6', 'categoryid' => $catid, 'type' => 'textarea', 'shortname' => 'f6',
                'area' => $area, 'component' => $component, ]);
        }
    }

    /**
     * Setup an array of simple custom field definition.
     *
     * @return array
     */
    protected function get_simple_cf_data(): array {
        if (!$this->now) {
            $this->now = time();
        }
        $simpledata = ['customfield_f1' => 'some text',
            'customfield_f2' => 1,
            'customfield_f3' => $this->now,
            'customfield_f5' => 2,
            'customfield_f6_editor' => ['text' => 'test', 'format' => FORMAT_HTML], ];
        if (utils::is_multiselect_installed()) {
            $simpledata['customfield_f4'] = [1, 2];
        }
        return $simpledata;
    }

    /**
     * Simple assertion for the custom field data.
     *
     * @param \stdClass $data
     * @return void
     */
    protected function assert_check_simple_cf_data(\stdClass $data): void {
        $this->assertEquals('some text', $data->customfield_f1);
        $this->assertEquals(1, $data->customfield_f2);
        $this->assertEquals($this->now, $data->customfield_f3);
        if (utils::is_multiselect_installed()) {
            $this->assertEquals('1,2', $data->customfield_f4);
        }
        $this->assertEquals(2, $data->customfield_f5);
        $this->assertEquals('test', $data->customfield_f6);
    }

    /**
     * Simple assertion for the exported custom field data.
     *
     * @param \stdClass $data
     * @return void
     * @throws \coding_exception
     */
    protected function assert_check_simple_cf_data_exported(\stdClass $data): void {
        $this->assertEquals('some text', $data->f1);
        $this->assertEquals('Yes', $data->f2);
        $this->assertEquals(userdate($this->now, get_string('strftimedaydatetime')), $data->f3);
        if (utils::is_multiselect_installed()) {
            $this->assertEquals('b, c', $data->f4);
        }
        $this->assertEquals('b', $data->f5);
        $this->assertEquals('test', $data->f6);
    }
}
