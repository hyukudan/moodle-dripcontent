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
 * Drip Content condition.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_dripcontent;

defined('MOODLE_INTERNAL') || die();

/**
 * Drip Content availability condition.
 *
 * Supports three modes:
 * - coursedays: Time since first enrolment (continuous)
 * - subscriptiondays: Only active subscription periods (gaps not counted)
 * - daterange: Specific date range
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var string Mode: coursedays, subscriptiondays, or daterange */
    private $mode;

    /** @var string Unit: days or months */
    private $unit;

    /** @var int Value (number of days/months) */
    private $value;

    /** @var int|null From date (Unix timestamp) for daterange mode */
    private $fromdate;

    /** @var int|null To date (Unix timestamp) for daterange mode */
    private $todate;

    /** Mode constants */
    const MODE_COURSEDAYS = 'coursedays';
    const MODE_SUBSCRIPTIONDAYS = 'subscriptiondays';
    const MODE_DATERANGE = 'daterange';

    /** Unit constants */
    const UNIT_DAYS = 'days';
    const UNIT_MONTHS = 'months';

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        $this->mode = $structure->mode ?? self::MODE_COURSEDAYS;
        $this->unit = $structure->unit ?? self::UNIT_DAYS;
        $this->value = isset($structure->value) ? (int)$structure->value : 0;
        $this->fromdate = isset($structure->fromdate) ? (int)$structure->fromdate : null;
        $this->todate = isset($structure->todate) ? (int)$structure->todate : null;
    }

    /**
     * Saves the condition data.
     *
     * @return \stdClass The saved data structure.
     */
    public function save() {
        $data = (object)[
            'type' => 'dripcontent',
            'mode' => $this->mode,
            'unit' => $this->unit,
            'value' => $this->value,
        ];

        if ($this->mode === self::MODE_DATERANGE) {
            if ($this->fromdate) {
                $data->fromdate = $this->fromdate;
            }
            if ($this->todate) {
                $data->todate = $this->todate;
            }
        }

        return $data;
    }

    /**
     * Checks if the condition is available for a specific user.
     *
     * @param bool $not Whether the condition is negated.
     * @param \core_availability\info $info Info about the item.
     * @param bool $grabthelot Whether to grab lots of data.
     * @param int $userid User ID to check.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $course = $info->get_course();
        $allow = $this->check_condition($course->id, $userid);

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Checks the condition for all users (used for caching).
     *
     * @param bool $not Whether negated.
     * @return bool True if available for all.
     */
    public function is_available_for_all($not = false) {
        // Date range mode can be checked globally.
        if ($this->mode === self::MODE_DATERANGE) {
            $now = self::get_time();
            $allow = true;

            if ($this->fromdate && $now < $this->fromdate) {
                $allow = false;
            }
            if ($this->todate && $now > $this->todate) {
                $allow = false;
            }

            return $not ? !$allow : $allow;
        }

        // Other modes are user-specific.
        return false;
    }

    /**
     * Check the condition for a specific user.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @return bool True if condition is met.
     */
    protected function check_condition($courseid, $userid) {
        $now = self::get_time();

        switch ($this->mode) {
            case self::MODE_COURSEDAYS:
                return $this->check_course_time($courseid, $userid, $now);

            case self::MODE_SUBSCRIPTIONDAYS:
                return $this->check_subscription_time($courseid, $userid, $now);

            case self::MODE_DATERANGE:
                return $this->check_date_range($now);

            default:
                return false;
        }
    }

    /**
     * Check time since first enrolment in course.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $now Current timestamp.
     * @return bool True if enough time has passed.
     */
    protected function check_course_time($courseid, $userid, $now) {
        $firstenrol = $this->get_first_enrolment_time($courseid, $userid);

        if (!$firstenrol) {
            return false;
        }

        $required = $this->calculate_required_time($firstenrol);
        return $now >= $required;
    }

    /**
     * Check active subscription time (only counting active periods).
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $now Current timestamp.
     * @return bool True if enough active subscription time.
     */
    protected function check_subscription_time($courseid, $userid, $now) {
        $activedays = $this->calculate_active_subscription_days($courseid, $userid, $now);

        if ($this->unit === self::UNIT_MONTHS) {
            // Convert required months to approximate days (30 days per month).
            $requireddays = $this->value * 30;
        } else {
            $requireddays = $this->value;
        }

        return $activedays >= $requireddays;
    }

    /**
     * Check if current time is within date range.
     *
     * @param int $now Current timestamp.
     * @return bool True if within range.
     */
    protected function check_date_range($now) {
        if ($this->fromdate && $now < $this->fromdate) {
            return false;
        }
        if ($this->todate && $now > $this->todate) {
            return false;
        }
        return true;
    }

    /**
     * Get the first enrolment time for a user in a course.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @return int|null Unix timestamp or null if not enrolled.
     */
    protected function get_first_enrolment_time($courseid, $userid) {
        global $DB;

        $sql = "SELECT MIN(ue.timestart) as firstenrol
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = :courseid
                  AND ue.userid = :userid
                  AND ue.timestart > 0";

        $result = $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid,
        ]);

        return $result && $result->firstenrol ? (int)$result->firstenrol : null;
    }

    /**
     * Calculate total active subscription days for a user.
     *
     * This method sums up all periods where the user had an active enrolment,
     * excluding gaps where they were not subscribed.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $now Current timestamp.
     * @return int Total active days.
     */
    protected function calculate_active_subscription_days($courseid, $userid, $now) {
        global $DB;

        // Get all enrolment periods for the user in this course.
        $sql = "SELECT ue.id, ue.timestart, ue.timeend, ue.status
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = :courseid
                  AND ue.userid = :userid
                  AND ue.timestart > 0
                ORDER BY ue.timestart ASC";

        $enrolments = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid,
        ]);

        if (empty($enrolments)) {
            return 0;
        }

        // Merge overlapping periods and sum active time.
        $periods = [];
        foreach ($enrolments as $enrol) {
            $start = (int)$enrol->timestart;
            // If timeend is 0 or in the future, use current time as end (if active).
            // If status is not 0 (suspended), the enrolment is not active.
            if ($enrol->status != 0) {
                // Suspended enrolment - use timeend or timemodified as actual end.
                $end = $enrol->timeend > 0 ? (int)$enrol->timeend : $start;
            } else {
                // Active enrolment.
                $end = ($enrol->timeend == 0 || $enrol->timeend > $now) ? $now : (int)$enrol->timeend;
            }

            if ($end > $start) {
                $periods[] = ['start' => $start, 'end' => $end];
            }
        }

        // Merge overlapping periods.
        $merged = $this->merge_periods($periods);

        // Calculate total days.
        $totalseconds = 0;
        foreach ($merged as $period) {
            $totalseconds += ($period['end'] - $period['start']);
        }

        return (int)floor($totalseconds / DAYSECS);
    }

    /**
     * Merge overlapping time periods.
     *
     * @param array $periods Array of periods with 'start' and 'end' keys.
     * @return array Merged periods.
     */
    protected function merge_periods(array $periods) {
        if (empty($periods)) {
            return [];
        }

        // Sort by start time.
        usort($periods, function($a, $b) {
            return $a['start'] - $b['start'];
        });

        $merged = [];
        $current = $periods[0];

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i]['start'] <= $current['end']) {
                // Overlapping - extend current period.
                $current['end'] = max($current['end'], $periods[$i]['end']);
            } else {
                // No overlap - save current and start new.
                $merged[] = $current;
                $current = $periods[$i];
            }
        }
        $merged[] = $current;

        return $merged;
    }

    /**
     * Calculate the required timestamp based on value and unit.
     *
     * @param int $starttime Starting timestamp.
     * @return int Required timestamp.
     */
    protected function calculate_required_time($starttime) {
        if ($this->unit === self::UNIT_MONTHS) {
            // Use PHP's date functions for accurate month calculation.
            $date = new \DateTime('@' . $starttime);
            $date->modify('+' . $this->value . ' months');
            return $date->getTimestamp();
        } else {
            return $starttime + ($this->value * DAYSECS);
        }
    }

    /**
     * Get description for display.
     *
     * @param bool $full Whether to show full description.
     * @param bool $not Whether condition is negated.
     * @param \core_availability\info $info Info object.
     * @return string Description.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        return $this->get_either_description($not, false);
    }

    /**
     * Get standalone description.
     *
     * @param bool $full Whether to show full description.
     * @param bool $not Whether condition is negated.
     * @param \core_availability\info $info Info object.
     * @return string Description.
     */
    public function get_standalone_description($full, $not, \core_availability\info $info) {
        return $this->get_either_description($not, true);
    }

    /**
     * Generate the description string.
     *
     * @param bool $not Whether negated.
     * @param bool $standalone Whether standalone.
     * @return string Description.
     */
    protected function get_either_description($not, $standalone) {
        $prefix = $standalone ? 'short_' : 'full_';

        switch ($this->mode) {
            case self::MODE_COURSEDAYS:
                $key = $prefix . $this->unit . '_course';
                $desc = get_string($key, 'availability_dripcontent', $this->value);
                break;

            case self::MODE_SUBSCRIPTIONDAYS:
                $key = $prefix . $this->unit . '_subscription';
                $desc = get_string($key, 'availability_dripcontent', $this->value);
                break;

            case self::MODE_DATERANGE:
                $desc = $this->get_daterange_description($prefix);
                break;

            default:
                $desc = '';
        }

        if ($not) {
            $desc = get_string('not', 'availability') . ' ' . $desc;
        }

        return $desc;
    }

    /**
     * Get description for date range mode.
     *
     * @param string $prefix String key prefix.
     * @return string Description.
     */
    protected function get_daterange_description($prefix) {
        $format = get_string('strftimedate', 'langconfig');

        if ($this->fromdate && $this->todate) {
            $a = new \stdClass();
            $a->from = userdate($this->fromdate, $format);
            $a->to = userdate($this->todate, $format);
            return get_string($prefix . 'daterange', 'availability_dripcontent', $a);
        } else if ($this->fromdate) {
            return get_string($prefix === 'full_' ? 'full_afterdate' : 'short_daterange',
                'availability_dripcontent', userdate($this->fromdate, $format));
        } else if ($this->todate) {
            return get_string($prefix === 'full_' ? 'full_beforedate' : 'short_daterange',
                'availability_dripcontent', userdate($this->todate, $format));
        }

        return '';
    }

    /**
     * Get debug string.
     *
     * @return string Debug info.
     */
    protected function get_debug_string() {
        return $this->mode . ':' . $this->unit . ':' . $this->value;
    }

    /**
     * Get current time (allows mocking in tests).
     *
     * @return int Current timestamp.
     */
    protected static function get_time() {
        return time();
    }

    /**
     * Update after course restore.
     *
     * @param int $restoreid Restore ID.
     * @param int $courseid Course ID.
     * @param \base_logger $logger Logger.
     * @param string $name Item name.
     * @return bool Success.
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        // No updates needed for this condition type.
        return true;
    }
}
