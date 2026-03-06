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
 * This file defines settingpages and externalpages under the "courses" category
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
if ($hassiteconfig) {
    $settings = new admin_category('activitylibrary', get_string('pluginname', 'local_activitylibrary'));

    $settings->add('activitylibrary',
        new admin_externalpage('activitylibrary_customfield',
            new lang_string('activitylibrary_customfield', 'local_activitylibrary'),
            $CFG->wwwroot . '/local/activitylibrary/activityfields.php',
            ['local/activitylibrary:manage']
        )
    );
    $mainsettings = new admin_settingpage('activitylibrarymainsettings',
        get_string('activitylibrarymainsettings', 'local_activitylibrary'),
        ['local/activitylibrary:manage'],
        empty($CFG->enableactivitylibrary));

    $samplemenutext = '';
    $stringmanager = get_string_manager();
    foreach (['en', 'fr'] as $lang) {
        $text = $stringmanager->get_string('activitylibrary', 'local_activitylibrary', null, 'en');
        $samplemenutext .= \html_writer::tag('p', "\"{$text}\"|{$lang}\n");
    }
    $mainsettings->add(
        new admin_setting_configtextarea('local_activitylibrary/menutextoverride',
            get_string('activitylibrary:menutextoverride', 'local_activitylibrary'),
            get_string('activitylibrary:menutextoverride:desc', 'local_activitylibrary', $samplemenutext),
            ''
        )
    );

    $settings->add('activitylibrary', $mainsettings);

    if (!empty($CFG->enableactivitylibrary) && $CFG->enableactivitylibrary) {
        $ADMIN->add('courses', $settings); // Add it to the course menu.
    }
    // Create a global Advanced Feature Toggle.
    $enableoption = new admin_setting_configcheckbox('enableactivitylibrary',
        new lang_string('enableactivitylibrary', 'local_activitylibrary'),
        new lang_string('enableactivitylibrary', 'local_activitylibrary'),
        1);
    $enableoption->set_updatedcallback('local_activitylibrary_enable_disable_plugin_callback');

    $optionalsubsystems = $ADMIN->locate('optionalsubsystems');
    $optionalsubsystems->add($enableoption);
}
