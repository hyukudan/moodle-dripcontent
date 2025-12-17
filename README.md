# Drip Content - Moodle Availability Condition

A Moodle availability condition plugin that controls access to course content based on time criteria.

## Features

### Three Access Modes

1. **Time in Course** (`coursedays`)
   - Counts days/months since the user first enrolled in the course
   - Continuous time calculation (like the original `availability_days` plugin)

2. **Active Subscription Time** (`subscriptiondays`)
   - Only counts time when the user has an active (paid) enrolment
   - Gaps in subscription are NOT counted
   - Perfect for subscription-based courses where content should unlock based on actual paid time

3. **Date Range** (`daterange`)
   - Content available between specific dates
   - Useful for time-limited content releases

### Time Units

- **Days**: Fine-grained control
- **Months**: Convenient for subscription-based content

## Installation

1. Download or clone this repository
2. Copy to `availability/condition/dripcontent/` in your Moodle installation
3. Visit Site Administration > Notifications to complete the installation

## Usage

1. Edit any activity or resource in your course
2. Under "Restrict access", click "Add restriction"
3. Select "Drip Content"
4. Choose your mode:
   - **Time in course**: Enter number of days/months since enrolment
   - **Active subscription time**: Enter number of days/months of active subscription
   - **Date range**: Select from/to dates

## Active Subscription Time Explained

This mode is designed for subscription-based courses. It calculates the total time a user has been actively subscribed, excluding any gaps.

**Example:**
- User subscribes on January 1st (1 month)
- User's subscription lapses February 1st - March 31st (not counted)
- User re-subscribes on April 1st (1 month)
- **Total active time: 2 months** (not 4 months)

The plugin considers:
- `user_enrolments.timestart` and `user_enrolments.timeend`
- `user_enrolments.status` (only active enrolments count)
- Overlapping enrolment periods are merged

## Requirements

- Moodle 4.4 or higher
- PHP 8.1 or higher

## License

GNU GPL v3 or later - http://www.gnu.org/copyleft/gpl.html

## Credits

Developed by [Prepara Oposiciones](https://preparaoposiciones.es)

Based on the original [availability_days](https://moodle.org/plugins/availability_days) plugin by Valery Fremaux.
