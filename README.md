# Drip Content - Moodle Availability Condition

[![Moodle Plugin](https://img.shields.io/badge/Moodle-4.4%2B-orange)](https://moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Languages](https://img.shields.io/badge/Languages-EN%20%7C%20ES-green)](#languages)

A Moodle availability condition plugin that provides flexible content dripping based on time criteria. Perfect for subscription-based courses, timed content releases, and progressive learning paths.

## Features

### Four Access Modes

| Mode | Description | Use Case |
|------|-------------|----------|
| **Time since enrolment** | Days/weeks/months since first enrolment | Progressive course content |
| **Time since course start** | Days/weeks/months since course start date | Scheduled releases for all users |
| **Active subscription** | Only counts active (paid) periods | Subscription-based platforms |
| **Date range** | Between specific dates | Seasonal or event-based content |

### Three Time Units

- **Days** - Fine-grained control for daily content releases
- **Weeks** - Convenient for weekly schedules
- **Months** - Perfect for subscription tiers (1 month, 3 months, etc.)

### Key Capabilities

- **Gap-aware calculation** - Subscription time excludes periods without active enrolment
- **Overlap handling** - Multiple enrolment periods are merged correctly
- **Status-aware** - Only counts `status=0` (active) enrolments
- **Enrolment method filter** - Only count specific enrolment types (e.g., PayPal only)
- **Remaining time display** - Shows users how long until content unlocks
- **Flexible dates** - Support for "from only", "to only", or full date ranges
- **Unlock notifications** - Email and/or platform notifications when content becomes available
- **Multi-language** - English and Spanish included

## Installation

### Via Git (Recommended)

```bash
cd /path/to/moodle/availability/condition/
git clone https://github.com/hyukudan/moodle-dripcontent.git dripcontent
```

### Manual Installation

1. Download the ZIP from GitHub
2. Extract to `availability/condition/dripcontent/`
3. Visit Site Administration > Notifications

### Verify Installation

After installation, you should see "Drip Content" (or "Contenido gradual" in Spanish) as an option when adding access restrictions to activities.

## Usage

### Adding a Restriction

1. Edit any activity or resource
2. Scroll to "Restrict access"
3. Click "Add restriction..."
4. Select "Drip Content"
5. Configure your restriction

### Mode: Time since Enrolment

Unlocks content after X days/weeks/months since the user **first enrolled**.

```
Example: "Available after 7 days since enrolment"
- User enrolls January 1st
- Content available January 8th
```

### Mode: Time since Course Start

Unlocks content after X days/weeks/months since the **course start date**. Same for all users.

```
Example: "Available after 2 weeks since course start"
- Course starts January 1st
- Content available January 15th for ALL users
```

### Mode: Active Subscription Time

Unlocks content after X days/weeks/months of **active subscription only**.

```
Example: "Available after 2 months of active subscription"

Timeline:
- January: User subscribes (1 month counted)
- February-March: Subscription lapses (NOT counted)
- April: User re-subscribes (2nd month counted)
- Content unlocks during April
```

This mode is ideal for:
- Monthly subscription platforms
- Pay-per-month access models
- Membership sites with recurring billing

#### Enrolment Method Filter

For subscription mode, you can optionally filter by specific enrolment methods:
- Only count PayPal enrolments
- Only count Stripe enrolments
- Exclude manual enrolments (VIP users)

### Mode: Date Range

Content available only during specific dates.

```
Examples:
- "From January 1, 2025 to March 31, 2025" (seasonal content)
- "After July 1, 2025" (launch date)
- "Until December 31, 2025" (expiring content)
```

## Notification System

The plugin can notify users when content becomes available.

### Configuration

Go to **Site Administration > Plugins > Availability restrictions > Drip Content**

| Setting | Description |
|---------|-------------|
| Enable notifications | Turn notifications on/off |
| Notification method | Email only, Platform only, or Both |

### How It Works

1. A scheduled task runs every 15 minutes
2. Checks all modules with dripcontent conditions
3. For each enrolled user, checks if content is now available
4. Sends notification if user hasn't been notified before
5. Records notification to prevent duplicates

## Technical Details

### How Active Subscription Time Works

The plugin queries `mdl_user_enrolments` and calculates:

1. **Fetches all enrolment periods** for the user in the course
2. **Filters by status** - Only `status=0` (active) counts
3. **Filters by enrolment method** - If configured
4. **Respects timeend** - If set, the period ends there
5. **Merges overlaps** - Multiple concurrent enrolments don't double-count
6. **Sums active time** - Total seconds of actual subscription

### Database Tables

| Table | Purpose |
|-------|---------|
| `mdl_user_enrolments` | Enrolment periods (timestart, timeend, status) |
| `mdl_enrol` | Enrolment methods per course |
| `mdl_availability_dripcontent_ntf` | Tracks sent notifications |

### Time Calculations

- **Days**: `value * 86400 seconds`
- **Weeks**: `value * 604800 seconds`
- **Months**: PHP `DateTime::modify('+N months')` for accuracy

## Languages

Currently supported:
- **English (en)** - Complete
- **Spanish (es)** - Complete

### Adding a Language

1. Copy `lang/en/availability_dripcontent.php` to `lang/XX/`
2. Translate all strings
3. Submit a pull request

## Requirements

| Requirement | Version |
|-------------|---------|
| Moodle | 4.4+ |
| PHP | 8.1+ |

## Comparison with availability_days

| Feature | availability_days | availability_dripcontent |
|---------|-------------------|-------------------------|
| Days since enrolment | ✅ | ✅ |
| Days since course start | ✅ | ✅ |
| Weeks support | ❌ | ✅ |
| Months support | ❌ | ✅ |
| Active subscription only | ❌ | ✅ |
| Enrolment method filter | ❌ | ✅ |
| Date ranges | ❌ | ✅ |
| Gap-aware calculation | ❌ | ✅ |
| Remaining time display | ❌ | ✅ |
| Unlock notifications | ❌ | ✅ |

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run PHP CodeSniffer: `phpcs --standard=moodle .`
5. Submit a pull request

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

See [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html) for details.

## Credits

Developed by [hyukudan](https://github.com/hyukudan)

---

**Questions?** Open an issue on GitHub.
