=== Necessary-tools ===
Contributors: foresthoffman
Tags: export, duplication, posts, admin
Requires at least: 3.0.2
Tested up to: 4.7.3
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Necessary Tools provides useful features that should be included in WordPress by default.

== Description ==

This plugin contains features that improve the quality-of-life for WordPress users.

* Cloning Posts: clone any post or page, duplicating everything but it's title (supports custom post types)
* Exporting Posts: export posts or pages individually or in bulk (supports custom post types)

= Cloning Posts =

Re-creating posts with similar structure or content can be time-consuming, but cloning makes the process nearly instantaneous!

The cloning feature adds a small grey button, with the text "Clone", above the Update/Publish button in the Post/Page edit page. As stated above, all content, meta data, and taxonomies are copied to the clone from the original.

= Exporting Posts =

Exporting ALL of a WordPress site's posts in order to import a handful of posts to another site is frustrating. However, selectively exporting posts removes the hassle!

The "Necessary Tools Export" page can be found under the "Tools" admin menu. By default, the table on the page will be populated with any published posts available. The post type and post status dropdowns allow you to change which posts are listed. After selecting a few posts, hitting the export button will prompt to download the XML file containing the post data.

The XML file can then be imported on any other WordPress installation, via the "Import" page in the "Tools" admin menu.

= Compatibility Troubleshooting =

If there are any plugin or theme conflicts with Necessary Tools, Necessary Tools features can be enabled/disabled at will via the "Necessary Tools Options" page in the admin menu. This should hopefully make troubleshooting easier.

== Installation ==

1. Upload `necessary-tools.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.1.1 =
* Update tags

= 1.1.0 =
* Add options page for enabling/disabling features
 * Add option for clone post button
 * Add option for export posts page

= 1.0.1 =
* Because SVN...

= 1.0.0 =
* Add a page for exporting posts individually or in bulk

= 0.3.4 =
* Update plugin URI and WP up-to version

= 0.3.3 =
* Update plugin directories

= 0.3.2 =
* Update .gitignore

= 0.3.1 =
* Add nonce validation to the cloning feature

= 0.3 =
* Update the readme.txt
* Added GPLv2 License

= 0.2 =
* Add cloning feature for all post types

= 0.1 =
* Intital commit