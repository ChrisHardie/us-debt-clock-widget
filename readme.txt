=== U.S. Debt Clock Widget ===
Contributors: chrishardie
Tags: economy,finance,widgets,money,government,debt
Requires at least: 2.8
Tested up to: 5.6
Version: 1.6
Stable tag: trunk
License: GPL2
License URI: http://www.gnu.org/licenses/gpl.html

This plugin creates a widget displaying the most recently available amount of the U.S. National Debt.

== Description ==
This plugin creates a widget displaying the most recently available amount of the U.S. National Debt. Data is retrieved from treasurydirect.gov and a source link can optionally be displayed.

Want to help make this plugin better? <a href="https://github.com/ChrisHardie/us-debt-clock-widget">Pull requests are welcome</a>.

== Installation ==
1. Upload the `us-debt-clock-widget` folder to the `/wp-content/plugins/` directory
2. Activate the U.S. Debt Clock Widget plugin through the `Plugins` menu in the WordPress dashboard
3. Add the widget to an available widget area and configure the display options

== Screenshots ==
1. The widget front-end display
2. The widget options configuration display

== Changelog ==

= 1.6 =

* Fix: use newer Treasury debt feed and info URLs, as they did not redirect the old ones.

= 1.5 =

* Fix: catch undefined variable scenario
* Improvement: additional code standards and formatting updates

= 1.4 =

* Fix: change data source from treasury.io to treasurydirect.gov
* Improvement: code style and formatting

= 1.3 =

* Fix: unfortunate math error that had debt displaying as billions, not trillions,  h/t Scott Cooper

= 1.2 =

* Fix: error in get_debt logic that was preventing successful data from being returned
* Fix: update widget registration to work with recent versions of WordPress
* Improvement: Update code formatting and escaping to better adhere to WordPress coding standards
* Improvement: Delete transient when plugin is deactivated

= 1.1 =

* Improvement: Handle invalid data from treasury.io more gracefully

= 1.0 =

* Initial release
