# Drip Content - Moodle Availability Condition

[![Moodle Plugin](https://img.shields.io/badge/Moodle-4.4%2B-orange)](https://moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Languages](https://img.shields.io/badge/Languages-EN%20%7C%20ES-green)](#languages)

A Moodle availability condition plugin that provides flexible content dripping based on time criteria. Perfect for subscription-based courses, timed content releases, and progressive learning paths.

## Features

### Three Access Modes

| Mode | Description | Use Case |
|------|-------------|----------|
| **Time in Course** | Days/months since first enrolment | Progressive course content |
| **Active Subscription** | Only counts active (paid) periods | Subscription-based platforms |
| **Date Range** | Between specific dates | Seasonal or event-based content |

### Time Units

- **Days** - Fine-grained control for daily content releases
- **Months** - Convenient for subscription tiers (1 month, 3 months, etc.)

### Key Capabilities

- **Gap-aware calculation** - Subscription time excludes periods without active enrolment
- **Overlap handling** - Multiple enrolment periods are merged correctly
- **Status-aware** - Only counts `status=0` (active) enrolments
- **Flexible dates** - Support for "from only", "to only", or full date ranges
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
5. Configure your restriction:

### Mode: Time in Course

Unlocks content after X days/months since the user **first enrolled**.

```
Example: "Available after 7 days in the course"
- User enrolls January 1st
- Content available January 8th
```

### Mode: Active Subscription Time

Unlocks content after X days/months of **active subscription only**.

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

### Mode: Date Range

Content available only during specific dates.

```
Examples:
- "From January 1, 2025 to March 31, 2025" (seasonal content)
- "After July 1, 2025" (launch date)
- "Until December 31, 2025" (expiring content)
```

## Technical Details

### How Active Subscription Time Works

The plugin queries `mdl_user_enrolments` and calculates:

1. **Fetches all enrolment periods** for the user in the course
2. **Filters by status** - Only `status=0` (active) counts
3. **Respects timeend** - If set, the period ends there
4. **Merges overlaps** - Multiple concurrent enrolments don't double-count
5. **Sums active time** - Total days of actual subscription

```sql
-- Simplified query logic
SELECT timestart, timeend, status
FROM mdl_user_enrolments ue
JOIN mdl_enrol e ON e.id = ue.enrolid
WHERE e.courseid = ? AND ue.userid = ?
```

### Database Tables Used

| Table | Purpose |
|-------|---------|
| `mdl_user_enrolments` | Enrolment periods (timestart, timeend, status) |
| `mdl_enrol` | Enrolment methods per course |

### Month Calculation

Months are calculated using PHP's `DateTime::modify()`:

```php
$date->modify('+3 months');
```

This handles varying month lengths correctly (28-31 days).

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
| Days since course start | ✅ | ❌ (planned) |
| Months support | ❌ | ✅ |
| Active subscription only | ❌ | ✅ |
| Date ranges | ❌ | ✅ |
| Gap-aware calculation | ❌ | ✅ |

## Roadmap

- [ ] Add "days since course start" mode
- [ ] Admin settings for default values
- [ ] Weeks as time unit
- [ ] Integration with specific enrolment plugins (PayPal, Stripe)
- [ ] Bulk apply restrictions to multiple activities
- [ ] Report showing user progress/unlock timeline

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

Developed by [Prepara Oposiciones](https://preparaoposiciones.es)

Inspired by [availability_days](https://moodle.org/plugins/availability_days) by Valery Fremaux.

---

**Questions?** Open an issue on GitHub.
