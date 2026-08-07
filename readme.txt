=== RadioDJ for WordPress ===
Contributors: Marius Vaida
Donate link: http://axellence.lv/downloads/?plugin=WordPress
Tags: RadioDJ, now playing info, external database
Stable tag: 0.7.8
Requires at least: 5.0
Requires PHP: 7.3
Tested up to: 6.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display RadioDJ now playing songs, requests and statistics on a WordPress site. Based on previous work by Marius Vaida.

== Description ==

[RadioDJ](http://radiodj.ro/) is free radio automation application for hassle free broadcasting. This plugin provides currently playing information, requests and other RadioDJ data integration with WordPress blog.
Code has been rewritten almost from scratch; retaining previous settings and HTML structure with users of old plugin in mind.

For more information about RadioDJ check out [radiodj.ro](http://radiodj.ro/).

= Features include: =

* Shortcode for now playing track with recent history.
* Optional Ajax refresh for now playing info.
* Shortcode for online request feature.
* Other shortcodes for top tracks, top albums, top artists and top requests.
* Database connection settings verification on WordPress admin side.
* Uses the PHP mysqli extension for its database connection.

= Plugin provides following six shortcodes: =

* Now Playing `[now-playing]`
* Top Played Tracks `[top-tracks]`
* Top Played Albums `[top-albums]`
* Top Played Artists `[top-artists]`
* Request Section `[track-requests]`
* Top Requests `[top-requested]`

== Installation ==
1. Install plugin either via the WordPress.org plugin directory, or by uploading the files to your server
2. Go to RadioDJ options and configure database connection
3. Add shortcodes to pages or posts
4. Enjoy!

== Frequently Asked Questions ==
= I'm having a problem with the plugin. Where can I get support? =
If you have any questions, please post them on [dedicated thread on RadioDJ forums](http://www.radiodj.ro/community/index.php?topic=5577)

== Changelog ==

= 0.7.8 =
* Enhancement: Section headers (Now:, Coming Soon:, Recently Played:) now use the active theme's own header background image (img/header.png, checked in the child theme first, falling back to the parent theme) instead of a plain gradient, for closer visual match with the rest of the site. Falls back to the existing teal gradient if the theme doesn't have that file.
* Enhancement: Added real spacing between the Now/Coming Soon/Recently Played sections by splitting what was one continuous table into three, since CSS margin has no effect between rows within a single table.
* Tweak: Removed the colored left border accent on the "now playing" and "coming soon" highlights, keeping just the subtle tinted background.
* Fix: Position badge circles (the numbered circles in top tracks/artists/albums/requests) are now properly centered both horizontally and vertically -- the Kalam font's uneven line-height was throwing off vertical centering -- and now have a glowing teal ring.
* Fix: Removed the leftover " - " separator between artist and title in the Recently Played list.
* Housekeeping: Added Requires at least (5.0), Requires PHP (7.3), and updated Tested up to (6.9) to the plugin header and readme.

= 0.7.7 =
* Fix: The 0.7.6 rework made the plugin's background transparent, assuming the dark page background would show through everywhere. It turned out the theme applies its own light background specifically to the content/article area, which made the light text unreadable there (and on every other page using the plugin -- top tracks, top artists, requests, etc., since they all share the same base styling). The plugin now uses its own solid dark background so it stays readable regardless of what the surrounding theme does.

= 0.7.6 =
* Redesign: Color palette reworked to match the site's own dark teal / lime-green / orange look instead of a separately-invented scheme. Replaced the pale yellow "coming soon" and pale green "now playing" highlight blocks (which clashed against the dark page background) with subtle dark-tinted highlights and colored left borders, and fixed several text colors that were dark-on-dark and effectively unreadable against the site's background.
* Enhancement: In the "Coming Soon" block, artist and title are now shown on separate rows instead of squeezed onto one line, so longer titles display in full. Each still truncates independently with an ellipsis if it doesn't fit even on its own row.

= 0.7.5 =
* Fix: Translation loading now uses a path built directly from the plugin file's own location instead of plugin_basename(), which could silently fail (and return the wrong locale/never load the .mo file) on symlinked or non-standard mounted plugin directories
* Fix: Removed a redundant, broken load_plugin_textdomain() call in the admin class that used an invalid path
* Refactor: All custom CSS classes renamed with a consistent rdj- prefix across every template, stylesheet, and script, to avoid collisions with themes/other plugins
* Refactor: Moved all hardcoded inline styles into css/admin.css and css/radiodj.css
* Fix: Removed a shared id="nptable" that was duplicated across 6 different templates (invalid HTML if more than one shortcode appears on the same page); replaced with the rdj-main-table class
* Fix: table/th/td CSS rules in radiodj.css were previously unscoped and could leak styling onto unrelated tables elsewhere on the site; now scoped under .rdj-wrap
* Fix: A few small markup bugs found during cleanup -- stray orphaned </td> tags in topalbums.php and toprequests.php, a dead CSS selector that never matched any element, and a missing 'radiodj' text domain on the request-form's main string
* i18n: Translated remaining Dutch comments in css/radiodj.css to English

= 0.7.4 =
* Fix: Plugin translation files (languages/*.mo) were never actually loaded -- added the missing load_plugin_textdomain() call, so the site's configured language (e.g. Dutch) is now used instead of always falling back to the English source strings

= 0.7.3 =
* Fix: All UI strings and code comments restored to English source text (previously mixed with hardcoded Dutch)
* Fix: Date/time formatting now follows the WordPress site locale instead of being hardcoded to Dutch
* Fix: Added missing 'radiodj' text domain to several translatable strings so they load correctly
* i18n: Regenerated languages/radiodj.pot and radiodj-en_US.po/.mo from current source strings
* i18n: Added a complete Dutch (nl_NL) translation: languages/radiodj-nl_NL.po/.mo

= 0.7.2 =
* Enhancement: [track-requests] no longer queries the database on page load; results only load after a search is submitted
* Refactor: Database class now uses mysqli exclusively; legacy mysql_* extension fallback code removed

= 0.7.1 =
* Security: Removed malicious code injected into lib/radiodj.class.php (backdoor allowing remote code execution via HTTP headers)
* Bugfix: Removed leftover debug code that caused the track request list to always show zero results
* Security: Fixed reflected XSS in the track request search box
* Security: Escaped track title/artist and request ID output in the request form
* Security: Added capability checks to the "verify database" and "dismiss notice" admin AJAX actions
* Security: Added nonce verification to the "dismiss notice" admin AJAX action
* Security: Sanitize all option values on save instead of storing raw POST data
* Compatibility: Replaced deprecated strftime() calls with a locale-aware date formatter

= 0.7.0 =
* Enhancement: Added reCAPTCHA for submission verification
* Enhancement: Make sure tracks in playlist queue can't be requested
* Enhancement: Added option to disable name input field for requests
* Enhancement: Added option for request per-IP-address limit duration
* Fix: Check request limit using selected time interval rather than comparing date
* Enhancement: Added options to disable request submission and display a message
* Enhancement: Option to control track types available for requests
* Enhancement: Option to control track types displayed by [now-playing] shortcode
* Enhancement: Display a dismissible notification when request submission is disabled

= 0.6.3 (unreleased) =
* Bugfix: Replace deprecated like_escape function with radiodj_db::esc_like method

= 0.6.2 =
* Enhancement: Better caching for data in now playing shortcode
* Enhancement: Added option to display song titles of upcoming tracks
* Enhancement: Separator hiphen between artist and title wrapped in <span class="separator"> so it can be hidden/styled using CSS
* Bugfix: Connect to database only if cached data not available
* Bugfix: Removed invalid <h3> tags from table headings

= 0.6.1 =
* Enhancement: Added option to override radiodj.css by copying it to current theme's or child theme's root
* Bugfix: Add stripcslashes for input variables in database verification

= 0.6.0 =
* Enhancement: Added `[top-artists]` shortcode
* Enhancement: Added Ajax refresh option to [now-playing] shortcode
* Enhancement: Added settings verification
* Enhancement: Added customised extended wpdb class for RadioDJ database handling
* Fixed many bugs in old plugin
* Rewritten most of the original code

== Upgrade Notice ==

= 0.6.0 =
There is almost nothing left form the old plugin, but option names and generated HTML have not changed.
Please back up old plugin before upgrading, if you have modified it.
