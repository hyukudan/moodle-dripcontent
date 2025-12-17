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
 * Front-end class.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_dripcontent;

defined('MOODLE_INTERNAL') || die();

/**
 * Frontend for the dripcontent availability condition.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Gets the strings needed for JavaScript.
     *
     * @return array Array of string identifiers.
     */
    protected function get_javascript_strings() {
        return [
            'conditiontitle',
            'mode',
            'mode_coursedays',
            'mode_coursestartdays',
            'mode_subscriptiondays',
            'mode_daterange',
            'unit',
            'unit_days',
            'unit_weeks',
            'unit_months',
            'value',
            'fromdate',
            'todate',
            'enrolmentmethods',
            'allenrolmentmethods',
            'error_invalidvalue',
            'error_invaliddate',
            'error_dateorder',
        ];
    }

    /**
     * Gets additional parameters for JavaScript.
     *
     * @param \stdClass $course Course object.
     * @param \cm_info|null $cm Course module info (null for section).
     * @param \section_info|null $section Section info (null for cm).
     * @return array Array of parameters.
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;

        // Get available enrolment methods for this course.
        $enrolmethods = [];
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            $plugin = enrol_get_plugin($instance->enrol);
            if ($plugin) {
                $enrolmethods[$instance->enrol] = $plugin->get_instance_name($instance);
            }
        }

        // Also get all known enrol plugins for flexibility.
        $allplugins = enrol_get_plugins(true);
        foreach ($allplugins as $name => $plugin) {
            if (!isset($enrolmethods[$name])) {
                $enrolmethods[$name] = get_string('pluginname', 'enrol_' . $name);
            }
        }

        // Return as indexed array - each element becomes a parameter to initInner.
        return [$enrolmethods];
    }

    /**
     * Returns true if the current user can add this condition.
     *
     * @param \stdClass $course Course object.
     * @param \cm_info|null $cm Course module.
     * @param \section_info|null $section Section info.
     * @return bool True if allowed.
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        return true;
    }
}
