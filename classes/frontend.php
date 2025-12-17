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
            'mode_subscriptiondays',
            'mode_daterange',
            'unit',
            'unit_days',
            'unit_months',
            'value',
            'fromdate',
            'todate',
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
        return [
            'modes' => [
                condition::MODE_COURSEDAYS,
                condition::MODE_SUBSCRIPTIONDAYS,
                condition::MODE_DATERANGE,
            ],
            'units' => [
                condition::UNIT_DAYS,
                condition::UNIT_MONTHS,
            ],
        ];
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
