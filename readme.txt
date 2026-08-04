=== RadioDJ for WordPress ===
Contributors: Marius Vaida
Donate link: http://axellence.lv/downloads/?plugin=WordPress
Tags: RadioDJ, now playing info, external database
Stable tag: 0.7.4
Requires at least: 3.8
Tested up to: 4.6
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
