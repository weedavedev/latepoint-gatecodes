# LatePoint Gate Codes

A WordPress plugin that enhances the LatePoint booking system by adding automatically generated gate codes to booking confirmations and summaries.

## Description

LatePoint Gate Codes is an addon for the LatePoint booking plugin that generates and displays gate codes for approved bookings. These gate codes can be used for physical access control systems, allowing clients to enter premises using automatically generated codes.

## Features

* Automatically generates gate codes based on agent ID and booking week
* Displays gate codes in booking confirmations
* Shows gate codes in booking summaries
* Handles single and multiple bookings
* Mobile-friendly responsive design
* Shows gate codes only for approved bookings
* Only displays codes for bookings within 2 days (past or future)

## Requirements

* WordPress 5.0 or higher
* PHP 7.0 or higher
* LatePoint plugin must be installed and activated

## Installation

1. Upload the `latepoint-gate-codes` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. No configuration is needed - gate codes will automatically appear in booking confirmations and summaries

## Usage

Once installed and activated, gate codes will automatically appear in:
- Booking confirmation pages
- Booking summary panels

The gate code is formatted as: `#[agentID][agentID][weeknum]`

For example, if the agent ID is 5 and the booking is in week 12 of the year, the gate code would be: `#5512`

## Global Function

The plugin provides a global function that can be used in themes or other plugins:

```php
$gate_code = get_gate_code($agent_id, $date_string);
```

## Changelog

### 1.0.5
* Fixed logic for showing gate codes within 2 days
* Improved error handling and logging
* Enhanced CSS styling

### 1.0.1
* Bug fixes and performance improvements

### 1.0.0
* Initial release

## Support

For support, please contact Wallace Development at https://wallacedevelopment.co.uk

## License

GPLv2 or later
http://www.gnu.org/licenses/gpl-2.0.html
