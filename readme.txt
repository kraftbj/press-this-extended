=== Press This Extended ===
Contributors: kraftbj
Tags: press this, bookmarklet, quick post
Requires at least: 6.9
Tested up to: 6.7
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides a modern settings UI for the Press This plugin filters. Supports both Press This 1.x and 2.x.

== Description ==

Press This Extended provides a user-friendly settings interface for customizing the [Press This plugin](https://github.com/WordPress/press-this). It automatically detects which version of Press This you have installed and shows the appropriate options.

= Features =

**For Press This 1.x and 2.x:**

* Toggle media discovery (suggest images from source pages)
* Toggle text discovery (auto-suggest blockquotes)
* Customize blockquote HTML formatting
* Customize citation/source link formatting
* Control redirect behavior after publishing
* Control redirect behavior after saving drafts
* Option to redirect the parent window after saving

**Additional features for Press This 2.x:**

* **Block Picker** - Choose which blocks are available in the Press This editor
* **Post Type Selection** - Create Press This posts as any public post type
* **Post Format Controls** - Override or set default post formats
* **Media Sideload Settings** - Configure max file size and allowed image types
* **URL Proxy Toggle** - Enable server-side URL fetching for Direct Access Mode

= Modern Interface =

Built with WordPress components (React), the settings page provides a native WordPress admin experience with:

* Organized settings panels by category
* Version-aware UI that only shows relevant options
* Block picker with search, categories, and bulk selection
* Real-time save with visual feedback

= Backward Compatibility =

Upgrading from version 1.x? Your existing settings are automatically migrated to the new format.

== Installation ==

1. Install and activate the [Press This plugin](https://github.com/WordPress/press-this) first
2. Upload the `press-this-extended` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Visit Settings → Press This to configure your options

Or install directly from WordPress:

1. Visit Plugins → Add New
2. Search for "Press This Extended"
3. Click Install and then Activate
4. Visit Settings → Press This

== Frequently Asked Questions ==

= What is Press This? =

Press This is a bookmarklet tool that lets you quickly create posts from any webpage. Select text, click the bookmarklet, and a popup opens with the content ready to publish.

= Which version of Press This do I need? =

This plugin works with both Press This 1.x and 2.x. Install it from https://github.com/WordPress/press-this

= Why don't I see all the settings? =

Settings are shown based on which version of Press This you have installed. Press This 2.x includes additional filters for block editor settings, post types, and more.

= Can I use this without Press This installed? =

The plugin will load, but the settings won't do anything without Press This. A notice will appear on the settings page if Press This is not detected.

== Screenshots ==

1. Settings page with Press This 2.x detected
2. Block picker for selecting allowed blocks
3. Content and formatting options
4. Redirection settings

== Changelog ==

= 2.0.0 =
* Complete rewrite with modern WordPress architecture
* New React-based settings page using WordPress components
* Support for Press This 2.x filters (allowed blocks, post types, post formats, etc.)
* Block picker UI for selecting which blocks are available
* Version detection to show appropriate options
* Automatic migration of settings from 1.x
* Settings moved from Writing page to dedicated Settings → Press This page
* Requires WordPress 6.9+ and PHP 7.4+

= 1.1.0 =
* New "Add to Homescreen" actions on iOS and Chrome for Android will create a web app for Press This.
* Removes Text Editor setting since this is a default feature of WordPress 4.3.
* Removes the User Agent filter to prevent blocking of Press This. Merged into WordPress 4.3.

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 2.0.0 =
Major update with support for Press This 2.x. Your existing settings will be automatically migrated. Now requires WordPress 6.9+ and PHP 7.4+.
