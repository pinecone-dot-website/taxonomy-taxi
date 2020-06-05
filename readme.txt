=== Taxonomy Taxi ===
Contributors:       postpostmodern, pinecone-dot-io
Donate link:        https://cash.app/$EricEaglstun
Tags:               custom taxonomies, taxonomy, term
Requires at least:  4.8
Tested up to:       5.4.1
Stable tag:         trunk

== Description ==
Automatically display custom taxonomy information in wp-admin/edit.php
- requires PHP >= 7.0, WP >= 4.8

== Installation ==
1. Place /taxonomi-taxi/ directory in /wp-content/plugins/
1. Add custom taxonomies manually, or through a plugin like [Custom Post Type UI](http://webdevstudios.com/support/wordpress-plugins/)
1. Your edit posts table (/wp-admin/edit.php) will now show all associated taxonomies automatically!
1. Remove unneeded columns from the settings page (/wp-admin/options-general.php?page=taxonomy_taxi)

== Changelog ==
= 1.1.0 =
* Require PHP >= 7.0, WP >= 4.8
* Code formatting
* Fix bug in filtering taxonomy from admin edit row, use taxonomy query_var

= 1.0.0 =
* Seems to be working pretty dang well

= 0.9.9.6 =
* Fix saving options with all taxonomies deselected

= 0.9.9.2 =
* Support media library list

= 0.9.9 =
* Initial settings page to show / hide columns

= 0.9.8 =
* Initial support to filter on having no taxonomies

= 0.9.7.1 =
* Standardizing junk, add composer

= 0.97 = 
* Forgot

= 0.96 =
* Prep for 1.0 release

= 0.95 =
* Fix ordering in sortable column

= 0.91 =
* Fix links to categories and tags

= 0.89 =
* Fix warnings when viewing post type with zero taxonomies

= 0.88 =
* Show terms with 0 count, fix when taxonomy does not have a proper 'all_items' label

= 0.85 =
* Support quick edit

= 0.8 =
* Move to standard format, php >= 5.3

= 0.76 =
* Move to github

= 0.75 =
* Fix data to show in column on hierarchical post types

= 0.7 =
* Use wp_dropdown_categories() for filtering ui

= 0.61 =
* Fix for hierarchical post types

= 0.6 =
* Minor cleanup, use WP_Post class for post results

= 0.58 =
* Order taxonomies alphabetically, sortable column for custom post types *

= 0.57 =
* Minor code cleanup, screenshots *

= 0.56 =
* Fixed bug in post_type when clicking on custom taxonomy in edit table

= 0.55 =
* Fixed bug in filtering table multiple times ($post_type was being set to an array)
* Applies filters and actions only on wp-admin/edit.php, using `load-edit.php` action

= 0.51 =
* Fixed bug in table names

= 0.5 =
* First release

== Screenshots ==
1. hack
2. Custom 'sausage' taxonomy (for reference)
3. Displaying 'Sausage' Column, which can be filtered to a specific term through the added drop-down, or clicking on individual term.
