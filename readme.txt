=== Sheet Calendar ===

Contributor(s): Sheila Carr
Tags: calendar, events calendar, google sheets, printable calendar, monthly calendar, schedule calendar, event calendar, google sheet events, schedule
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1
License: GPLv2 or later

== Description ==

Sheet Calendar creates a printable monthly calendar powered by a Google Sheet.

Maintain your events in Google Sheets and automatically display them on a WordPress page as a formatted calendar. The plugin includes print-friendly layouts, a 2-up half-sheet printing option, multi-day event support, and an admin event exclusion tool for managing printed calendars.

Sheet Calendar is ideal for organizations, studios, and community groups that already manage schedules in Google Sheets and want an easy way to display a calendar on their website and generate print versions of it with just a few clicks.

Key features include:

• Google Sheets powered calendar - Pull events directly from your Google Sheet  
• Printable monthly layout  
• 2-up half-sheet printing  
• Event exclusion tool
• Multi-day event support  
• Clickable event links  
• Calendar caching for performance
• Manual Clear Cache button in admin
• Logo and footer information support
• Customizable highlight color
• QR code option
• Clean uninstall (removes plugin data)

PRINTING

The calendar includes two print modes:

Standard Print

  A single-page monthly calendar.

2-Up Print

  A two-per-page layout designed for:
  Letter paper (8.5 × 11)
  Cutting in half
  Front/back printing

This format is ideal for distributing physical calendars.


EXCLUDING EVENTS FROM PRINT

Click Exclude Events to temporarily hide specific events.

Hidden events:

  • remain in the sheet
  • are hidden from both the website and printed calendar
  • are saved server-side and apply to all visitors


CACHE SYSTEM

The plugin caches sheet data for 1 hour to improve performance.

If you update the Google Sheet and want immediate changes:

Go to Settings → Sheet Calendar

Click Clear Calendar Cache and reload the page


SHORTCODE

Display the calendar on any page with this shortcode:

  [sheet_calendar]


SETTINGS PAGE

Available settings include:

• Google Sheet URL
• Logo image
• Footer text
• Address
• Phone
• Website
• QR code
• Enable/disable QR display
• Show or Hide print option on website


== Installation ==

Upload the plugin folder to /wp-content/plugins/

Activate the plugin in WordPress → Plugins

Go to Settings → Sheet Calendar

Enter your Google Sheet CSV URL

Configure optional settings such as logo, footer text, contact information, and QR code


UNINSTALL

Deleting the plugin removes:

  stored plugin options

  cached sheet data

No leftover data remains in the database.


== Google Sheet Setup ==

Your Google Sheet must be published as CSV and must be published to the web so the plugin can access the CSV data.

Steps to publish:

1. Open your Google Sheet.
2. Click **File → Share → Publish to web**.
3. In the dialog that opens:
   - Choose the sheet you want to publish (usually **Entire Document**).
   - Select **CSV** as the format.
4. Click **Publish**.
5. Copy the generated CSV URL.
6. Paste that URL into **Settings → Sheet Calendar → Sheet URL**.

Example CSV URL:
  
https://docs.google.com/spreadsheets/d/XXXXXXXXXXXX/export?format=csv

Important:

The sheet must remain published for the calendar to work.  
The plugin reads the public CSV export of the sheet and does not require login access.
The Google Sheet does not need to be publicly editable. 
Only the published CSV version needs to be accessible.

If your calendar does not appear, confirm that the sheet is still published and that the CSV link is correct.

Required Sheet Columns

Your sheet should contain the following columns:

title (required)
start_date (required)
calendar (required — must contain "yes" to display the event)
start_time (optional)
end_date (optional)
instructor (optional)
link_url (optional)

Example row:

  Yoga Class | 2025-06-12 | 10:00 | 2025-06-12 | Jane Doe | https://website.com/yoga

Multi-Day Events

If an event spans multiple days, the calendar automatically displays:

Start date
End date

Intermediate days are handled automatically

Multi-day events appear only once in the Exclude Events modal, even though they appear on multiple days in the calendar.

Event Links

If a link_url column is present in the sheet, the event becomes clickable on the calendar page.


== Changelog ==
1.1

Updated event filter to use calendar column
Server-side event exclusion system
Admin-controlled exclusions apply to all visitors
Clear cache and test sheet connection improvements
Powered by branding

1.0

Initial release.

Google Sheets event import
printable monthly calendar
2-up print layout
event exclusion system
clickable event links
multi-day event support
caching system
admin settings page



== Frequently Asked Questions ==

How do I connect my Google Sheet?

  Open your Google Sheet.

  Click File → Share → Publish to web.

  Choose CSV format.

  Copy the generated URL.

  Paste it into Settings → Sheet Calendar → Sheet URL.


Why aren't my events updating?

  The calendar caches Google Sheet data for performance.

  If you recently updated the sheet:

  Go to Settings → Sheet Calendar.

  Click Clear Calendar Cache.

  The calendar will refresh immediately.


Can events link to another page?

 Yes.
 If a link_url column exists in your sheet, the event title becomes clickable on the calendar page.


Can I create multi-day events?

 Yes.
 Add both a start date and end date in the sheet.
 The calendar will automatically display the event on each day it spans.
 Multi-day events appear only once in the exclusion modal.


Does this plugin work with recurring events?

 Currently recurring events should be added as separate rows in the sheet.
 Future versions may include built-in recurrence handling.


What happens if Google Sheets is temporarily unavailable?

 The plugin automatically falls back to the most recent cached data so the calendar continues to display. Initial load sometimes fails when Google Sheets is a little slow. Refresh the page to load it.


Can I print the calendar?

 Yes. The plugin includes two print options:

 Standard Print

   Single full-page calendar

   Oriented vertically or horizontally in printer settings

 2-Up Print

   Two calendars per page

   Intended for double-sided printing

   Designed for cutting in half

   Ideal for distributing printed calendars


== Screenshots ==

1. Calendar display on the website
2. Admin settings page
3. Month Selection dropdown menu
4. Exclude Events UI
5. 1-up print preview
6. 1-up print page in browser
7. 2-up print preview, page 1 (front)
8. 2-up print preview, page 2 (back)