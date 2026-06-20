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
 * Supports four modes:
 * - coursedays: Time since first enrolment (continuous)
 * - coursestartdays: Time since course start date
 * - subscriptiondays: Only active subscription periods (gaps not counted)
 * - daterange: Specific date range
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var string Mode: coursedays, coursestartdays, subscriptiondays, or daterange */
    private $mode;

    /** @var string Unit: days, weeks, or months */
    private $unit;

    /** @var int Value (number of days/weeks/months) */
    private $value;

    /** @var int|null From date (Unix timestamp) for daterange mode */
    private $fromdate;

    /** @var int|null To date (Unix timestamp) for daterange mode */
    private $todate;

    /** @var array|null Enrolment methods to filter (null = all) */
    private $enrolmentmethods;

    /** @var array|null Enrol instance IDs to filter (null = all). Takes priority over enrolmentmethods. */
    private $enrolinstanceids;

    /** @var array Per-request cache: ["courseid:userid" => enrolment rows]. Avoids N+1 across CMs/sections. */
    protected static $enrolmentcache = [];

    /** @var array Per-request cache: [enrol instance id => display name]. */
    protected static $enrolnamescache = [];

    /** @var int|null Stable "now" for the whole request (consistent evaluation + better cache reuse). */
    protected static $requesttime = null;

    /** Mode constants */
    const MODE_COURSEDAYS = 'coursedays';
    const MODE_COURSEDAYS_WITHIN = 'coursedays_within';
    const MODE_COURSESTARTDAYS = 'coursestartdays';
    const MODE_SUBSCRIPTIONDAYS = 'subscriptiondays';
    const MODE_DATERANGE = 'daterange';

    /** Unit constants */
    const UNIT_DAYS = 'days';
    const UNIT_WEEKS = 'weeks';
    const UNIT_MONTHS = 'months';

    /** Seconds per week */
    const WEEKSECS = 604800;

    /** @var array Valid modes */
    const VALID_MODES = [
        self::MODE_COURSEDAYS,
        self::MODE_COURSEDAYS_WITHIN,
        self::MODE_COURSESTARTDAYS,
        self::MODE_SUBSCRIPTIONDAYS,
        self::MODE_DATERANGE,
    ];

    /** @var array Valid units */
    const VALID_UNITS = [
        self::UNIT_DAYS,
        self::UNIT_WEEKS,
        self::UNIT_MONTHS,
    ];

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Validate and set mode with fallback.
        $mode = $structure->mode ?? self::MODE_COURSEDAYS;
        $this->mode = in_array($mode, self::VALID_MODES, true) ? $mode : self::MODE_COURSEDAYS;

        // Validate and set unit with fallback.
        $unit = $structure->unit ?? self::UNIT_DAYS;
        $this->unit = in_array($unit, self::VALID_UNITS, true) ? $unit : self::UNIT_DAYS;

        // Validate value (must be non-negative integer).
        $this->value = isset($structure->value) ? max(0, (int)$structure->value) : 0;

        // Validate dates (must be positive timestamps or null).
        $this->fromdate = isset($structure->fromdate) && $structure->fromdate > 0
            ? (int)$structure->fromdate : null;
        $this->todate = isset($structure->todate) && $structure->todate > 0
            ? (int)$structure->todate : null;

        // Validate enrolment methods array.
        $this->enrolmentmethods = isset($structure->enrolmentmethods) && is_array($structure->enrolmentmethods)
            ? array_filter($structure->enrolmentmethods, 'is_string')
            : null;

        // Validate enrol instance IDs array.
        $this->enrolinstanceids = isset($structure->enrolinstanceids) && is_array($structure->enrolinstanceids)
            ? array_map('intval', array_filter($structure->enrolinstanceids, function($v) {
                return is_numeric($v) && (int)$v > 0;
            }))
            : null;
        if (empty($this->enrolinstanceids)) {
            $this->enrolinstanceids = null;
        }
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

        if (!empty($this->enrolmentmethods)) {
            $data->enrolmentmethods = $this->enrolmentmethods;
        }

        if (!empty($this->enrolinstanceids)) {
            $data->enrolinstanceids = array_values($this->enrolinstanceids);
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
        $allow = $this->check_condition($course, $userid);

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

        // Course start days mode can also be checked globally.
        if ($this->mode === self::MODE_COURSESTARTDAYS) {
            global $COURSE;
            $now = self::get_time();
            $required = $this->calculate_required_time($COURSE->startdate);
            $allow = $now >= $required;
            return $not ? !$allow : $allow;
        }

        // Other modes are user-specific.
        return false;
    }

    /**
     * Check the condition for a specific user.
     *
     * @param \stdClass $course Course object.
     * @param int $userid User ID.
     * @return bool True if condition is met.
     */
    protected function check_condition($course, $userid) {
        $now = self::get_time();

        switch ($this->mode) {
            case self::MODE_COURSEDAYS:
                return $this->check_enrolment_time($course->id, $userid, $now);

            case self::MODE_COURSEDAYS_WITHIN:
                return $this->check_enrolment_time_within($course->id, $userid, $now);

            case self::MODE_COURSESTARTDAYS:
                return $this->check_course_start_time($course, $now);

            case self::MODE_SUBSCRIPTIONDAYS:
                return $this->check_subscription_time($course->id, $userid, $now);

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
    protected function check_enrolment_time($courseid, $userid, $now) {
        $firstenrol = $this->get_first_enrolment_time($courseid, $userid);

        if (!$firstenrol) {
            return false;
        }

        $required = $this->calculate_required_time($firstenrol);
        return $now >= $required;
    }

    /**
     * Check if user is WITHIN the first N days/weeks/months of enrolment.
     * Inverse of check_enrolment_time: available DURING the window, not AFTER.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $now Current timestamp.
     * @return bool True if within the time window.
     */
    protected function check_enrolment_time_within($courseid, $userid, $now) {
        $firstenrol = $this->get_first_enrolment_time($courseid, $userid);

        if (!$firstenrol) {
            return false; // Not enrolled at all.
        }

        $deadline = $this->calculate_required_time($firstenrol);
        return $now < $deadline; // Available while BEFORE the deadline.
    }

    /**
     * Check time since course start date.
     *
     * @param \stdClass $course Course object.
     * @param int $now Current timestamp.
     * @return bool True if enough time has passed.
     */
    protected function check_course_start_time($course, $now) {
        $required = $this->calculate_required_time($course->startdate);
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
        // Special case: value=0 means "must have an active subscription RIGHT NOW"
        // (not "has accumulated 0+ seconds of subscription time", which is always true).
        if ($this->value == 0) {
            return $this->has_active_subscription_now($courseid, $userid, $now);
        }

        $activeseconds = $this->calculate_active_subscription_seconds($courseid, $userid, $now);
        $requiredseconds = $this->get_required_seconds();

        return $activeseconds >= $requiredseconds;
    }

    /**
     * Check if the user has an active subscription enrolment right now.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $now Current timestamp.
     * @return bool True if user has an active matching enrolment.
     */
    protected function has_active_subscription_now($courseid, $userid, $now) {
        foreach ($this->filter_enrolments($this->get_course_user_enrolments($courseid, $userid)) as $ue) {
            if ((int)$ue->status === 0 && ($ue->timeend == 0 || $ue->timeend > $now)) {
                return true;
            }
        }
        return false;
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
        $minstart = null;
        foreach ($this->filter_enrolments($this->get_course_user_enrolments($courseid, $userid)) as $ue) {
            if ($ue->timestart > 0 && ($minstart === null || $ue->timestart < $minstart)) {
                $minstart = (int)$ue->timestart;
            }
        }
        return $minstart;
    }

    /**
     * Load (once per request) the user's enrolment rows for a course, UNFILTERED by enrol
     * method/instance so several dripcontent conditions on the same course can share the set.
     * This collapses the previous per-CM/per-section N+1 into one query per (course, user).
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @return array Enrolment row objects (id, enrolid, timestart, timeend, status, enrol).
     */
    protected function get_course_user_enrolments($courseid, $userid) {
        global $DB;

        $key = $courseid . ':' . $userid;
        if (!array_key_exists($key, self::$enrolmentcache)) {
            $sql = "SELECT ue.id, ue.enrolid, ue.timestart, ue.timeend, ue.status, e.enrol
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE e.courseid = :courseid
                       AND ue.userid = :userid";
            self::$enrolmentcache[$key] = $DB->get_records_sql($sql, [
                'courseid' => $courseid,
                'userid' => $userid,
            ]);
        }
        return self::$enrolmentcache[$key];
    }

    /**
     * Apply this condition's enrol instance / method filter to a set of enrolment rows.
     * enrolinstanceids takes precedence over enrolmentmethods (mirrors the original SQL).
     *
     * @param array $rows Enrolment rows from {@see get_course_user_enrolments()}.
     * @return array Filtered rows.
     */
    protected function filter_enrolments(array $rows) {
        if (!empty($this->enrolinstanceids)) {
            $ids = array_map('intval', $this->enrolinstanceids);
            return array_filter($rows, function($ue) use ($ids) {
                return in_array((int)$ue->enrolid, $ids, true);
            });
        }
        if (!empty($this->enrolmentmethods)) {
            $methods = $this->enrolmentmethods;
            return array_filter($rows, function($ue) use ($methods) {
                return in_array($ue->enrol, $methods, true);
            });
        }
        return $rows;
    }

    /**
     * Bulk-load every enrolment of a course for ALL users in a single query and seed the
     * per-(course,user) cache. Intended for the notification cron, which evaluates many
     * users for the same course: turns O(users) lookups into one query.
     *
     * @param int $courseid Course ID.
     */
    public static function preload_course_enrolments($courseid) {
        global $DB;

        $sql = "SELECT ue.id, ue.userid, ue.enrolid, ue.timestart, ue.timeend, ue.status, e.enrol
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid";
        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $byuser = [];
        foreach ($rows as $r) {
            $byuser[$r->userid][$r->id] = $r;
        }
        foreach ($byuser as $userid => $urows) {
            self::$enrolmentcache[$courseid . ':' . $userid] = $urows;
        }
    }

    /**
     * Reset the per-request caches. Intended for long-running processes (e.g. the
     * notification cron) that iterate many courses and must not accumulate memory.
     *
     * @param int|null $courseid If given, only drop that course's enrolment entries.
     */
    public static function reset_request_cache(?int $courseid = null) {
        if ($courseid === null) {
            // Full reset: also clears the resolved-name cache and the request-stable
            // clock so long-running processes (cron) and PHPUnit start fresh.
            self::$enrolmentcache = [];
            self::$enrolnamescache = [];
            self::$requesttime = null;
            return;
        }
        $prefix = $courseid . ':';
        foreach (array_keys(self::$enrolmentcache) as $key) {
            if (strpos($key, $prefix) === 0) {
                unset(self::$enrolmentcache[$key]);
            }
        }
    }

    /**
     * Calculate total active subscription seconds for a user.
     *
     * This method sums up all periods where the user had an active enrolment,
     * excluding gaps where they were not subscribed.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int $now Current timestamp.
     * @return int Total active seconds.
     */
    protected function calculate_active_subscription_seconds($courseid, $userid, $now) {
        // Reuse the per-request enrolment cache; merge_periods() sorts, so no ORDER BY needed.
        $enrolments = [];
        foreach ($this->filter_enrolments($this->get_course_user_enrolments($courseid, $userid)) as $ue) {
            if ($ue->timestart > 0) {
                $enrolments[] = $ue;
            }
        }

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

        // Calculate total seconds.
        $totalseconds = 0;
        foreach ($merged as $period) {
            $totalseconds += ($period['end'] - $period['start']);
        }

        return $totalseconds;
    }

    /**
     * Merge overlapping time periods.
     *
     * @param array $periods Array of periods with 'start' and 'end' keys.
     * @return array Merged periods.
     */
    protected function merge_periods(array $periods) {
        // Early return for empty or single period.
        if (empty($periods)) {
            return [];
        }
        if (count($periods) === 1) {
            return $periods;
        }

        // Sort by start time.
        usort($periods, function($a, $b) {
            return $a['start'] - $b['start'];
        });

        $merged = [];
        $current = $periods[0];
        $count = count($periods);

        for ($i = 1; $i < $count; $i++) {
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
        switch ($this->unit) {
            case self::UNIT_MONTHS:
                // Use PHP's date functions for accurate month calculation.
                $date = new \DateTime('@' . $starttime);
                $date->modify('+' . $this->value . ' months');
                return $date->getTimestamp();

            case self::UNIT_WEEKS:
                return $starttime + ($this->value * self::WEEKSECS);

            case self::UNIT_DAYS:
            default:
                return $starttime + ($this->value * DAYSECS);
        }
    }

    /**
     * Get required seconds for subscription mode.
     *
     * @return int Required seconds.
     */
    protected function get_required_seconds() {
        switch ($this->unit) {
            case self::UNIT_MONTHS:
                // Approximate: 30 days per month.
                return $this->value * 30 * DAYSECS;

            case self::UNIT_WEEKS:
                return $this->value * self::WEEKSECS;

            case self::UNIT_DAYS:
            default:
                return $this->value * DAYSECS;
        }
    }

    /**
     * Get the remaining time until the condition is met.
     *
     * @param \stdClass $course Course object.
     * @param int $userid User ID.
     * @return int|null Remaining seconds, or null if already met or cannot calculate.
     */
    public function get_remaining_time($course, $userid) {
        $now = self::get_time();

        switch ($this->mode) {
            case self::MODE_COURSEDAYS:
                $firstenrol = $this->get_first_enrolment_time($course->id, $userid);
                if (!$firstenrol) {
                    return null;
                }
                $required = $this->calculate_required_time($firstenrol);
                return max(0, $required - $now);

            case self::MODE_COURSESTARTDAYS:
                $required = $this->calculate_required_time($course->startdate);
                return max(0, $required - $now);

            case self::MODE_SUBSCRIPTIONDAYS:
                $activeseconds = $this->calculate_active_subscription_seconds($course->id, $userid, $now);
                $requiredseconds = $this->get_required_seconds();
                return max(0, $requiredseconds - $activeseconds);

            case self::MODE_DATERANGE:
                if ($this->fromdate && $now < $this->fromdate) {
                    return $this->fromdate - $now;
                }
                return 0;

            default:
                return null;
        }
    }

    /**
     * Format remaining time as human-readable string.
     *
     * @param int $seconds Remaining seconds.
     * @return string Formatted string.
     */
    public static function format_remaining_time($seconds) {
        if ($seconds <= 0) {
            return '';
        }

        $days = floor($seconds / DAYSECS);
        $hours = floor(($seconds % DAYSECS) / HOURSECS);

        if ($days > 30) {
            $months = floor($days / 30);
            $remainingdays = $days % 30;
            if ($remainingdays > 0) {
                return get_string('remaining_months_days', 'availability_dripcontent',
                    (object)['months' => $months, 'days' => $remainingdays]);
            }
            return get_string('remaining_months', 'availability_dripcontent', $months);
        } else if ($days > 0) {
            if ($hours > 0) {
                return get_string('remaining_days_hours', 'availability_dripcontent',
                    (object)['days' => $days, 'hours' => $hours]);
            }
            return get_string('remaining_days', 'availability_dripcontent', $days);
        } else {
            return get_string('remaining_hours', 'availability_dripcontent', $hours);
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
        global $USER;

        $desc = $this->get_either_description($not, false);

        // Add remaining time if not available.
        if ($full && !$not) {
            $course = $info->get_course();
            if (!$this->check_condition($course, $USER->id)) {
                $remaining = $this->get_remaining_time($course, $USER->id);
                if ($remaining !== null && $remaining > 0) {
                    $formattedtime = self::format_remaining_time($remaining);
                    if ($formattedtime) {
                        $desc .= ' (' . $formattedtime . ')';
                    }
                }
            }
        }

        return $desc;
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
                $key = $prefix . $this->unit . '_enrolment';
                $desc = get_string($key, 'availability_dripcontent', $this->value);
                break;

            case self::MODE_COURSEDAYS_WITHIN:
                $key = $prefix . $this->unit . '_enrolment_within';
                $desc = get_string($key, 'availability_dripcontent', $this->value);
                break;

            case self::MODE_COURSESTARTDAYS:
                $key = $prefix . $this->unit . '_coursestart';
                $desc = get_string($key, 'availability_dripcontent', $this->value);
                break;

            case self::MODE_SUBSCRIPTIONDAYS:
                if ($this->value == 0) {
                    $desc = get_string($prefix . 'subscription_required', 'availability_dripcontent');
                } else {
                    $key = $prefix . $this->unit . '_subscription';
                    $desc = get_string($key, 'availability_dripcontent', $this->value);
                }
                break;

            case self::MODE_DATERANGE:
                $desc = $this->get_daterange_description($prefix);
                break;

            default:
                $desc = '';
        }

        // Append enrol instance names if filtering by specific instances.
        if (!empty($this->enrolinstanceids) && $this->mode !== self::MODE_DATERANGE) {
            $instancenames = $this->get_enrol_instance_names();
            if (!empty($instancenames)) {
                $desc .= ' (' . get_string('enrolinstances_in', 'availability_dripcontent',
                    implode(', ', $instancenames)) . ')';
            }
        }

        if ($not) {
            $desc = get_string('not', 'availability') . ' ' . $desc;
        }

        return $desc;
    }

    /**
     * Get display names for the configured enrol instance IDs.
     *
     * @return array Array of instance display names.
     */
    protected function get_enrol_instance_names() {
        global $DB;

        if (empty($this->enrolinstanceids)) {
            return [];
        }

        // Resolve only the instance IDs not yet cached this request.
        $missing = [];
        foreach ($this->enrolinstanceids as $id) {
            if (!array_key_exists((int)$id, self::$enrolnamescache)) {
                $missing[] = (int)$id;
            }
        }
        if (!empty($missing)) {
            list($insql, $params) = $DB->get_in_or_equal($missing, SQL_PARAMS_NAMED);
            $instances = $DB->get_records_select('enrol', "id $insql", $params);
            foreach ($missing as $id) {
                // Default to null so a missing instance is not re-queried.
                self::$enrolnamescache[$id] = null;
            }
            foreach ($instances as $instance) {
                if (!empty($instance->name)) {
                    self::$enrolnamescache[(int)$instance->id] = $instance->name;
                } else {
                    self::$enrolnamescache[(int)$instance->id] = get_string('pluginname', 'enrol_' . $instance->enrol);
                }
            }
        }

        $names = [];
        foreach ($this->enrolinstanceids as $id) {
            $name = self::$enrolnamescache[(int)$id] ?? null;
            if (!empty($name)) {
                $names[] = $name;
            }
        }
        return $names;
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
            return get_string($prefix === 'full_' ? 'full_afterdate' : 'short_afterdate',
                'availability_dripcontent', userdate($this->fromdate, $format));
        } else if ($this->todate) {
            return get_string($prefix === 'full_' ? 'full_beforedate' : 'short_beforedate',
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
        // Stable per request: every condition on the page evaluates against the same
        // instant (avoids off-by-seconds drift) and improves cache reuse.
        return self::$requesttime ??= time();
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

    /**
     * Get the mode.
     *
     * @return string Mode constant.
     */
    public function get_mode() {
        return $this->mode;
    }

    /**
     * Get the unit.
     *
     * @return string Unit constant.
     */
    public function get_unit() {
        return $this->unit;
    }

    /**
     * Get the value.
     *
     * @return int Value.
     */
    public function get_value() {
        return $this->value;
    }
}
