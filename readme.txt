=== Taxonomy Taxi ===
Contributors: postpostmodern
Donate link: http://www.heifer.org/
Tags: custom taxonomies, taxonomy
Requires at least: 3.1.3
Tested up to: 3.1.3
Stable tag: trunk

== Description ==
Automatically display custom taxonomy information in wp-admin/edit.php
Tested with Wordpress 3.1.3 
Requires PHP 5.  PHP 4 is like 10 years old, you know?

== Installation ==
1. Place /taxonomi-taxi/ directory in /wp-content/plugins/
1. Add custom taxonomies manually, or through a plugin like [Custom Post Type UI](http://webdevstudios.com/support/wordpress-plugins/)
1. Your edit posts table (/wp-admin/edit.php) will now show all associated taxonomies automatically!

== Changelog ==
= 0.55 =
* Fixed bug in filtering table multiple times ($post_type was being set to an array)
* Applies filters and actions only on wp-admin/edit.php, using `load-edit.php` action

= 0.51 =
* Fixed bug in table names

= 0.5 =
* First release

== Screenshots ==
1. hack