=== Sick Network Child ===
Contributors: sickmarketing
Donate link: 
Tags: WordPress Management, WordPress Controller
Author URI: http://sickplugins.com
Plugin URI: http://sicknetwork.com
Requires at least: 3.4
Tested up to: 3.6
Stable tag: 0.1.34
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to manage multiple blogs from one dashboard by providing a secure connection between your child site and your Sick Network dashboard.

== Description ==

[Sick Network](http://sicknetwork.com) is a self-hosted WordPress management system that allows you to manage an endless amount of WordPress blogs from one dashboard on your server.

The Sick Network child plugin is used so the installed blog can be securely managed remotely by your Network.

**Features include:**

* Connect and control all your WordPress installs even those on different hosts!
* Update all WordPress installs, Plugins and Themes from one location
* Manage and Add all your Posts from one location
* Manage and Add all your Pages from one location
* Run everything from 1 Dashboard that you host!


== Installation ==

1. Upload the sicknetwork folder to the /wp-content/plugins/ directory
2. Activate the Sick Network Child plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What is the purpose of this plugin? =

It allows the connection between the Sick Network main dashboard plugin and the site it is installed on.

= Do I configure anything with this plugin? =

There is nothing you have to configure with this plugin but there is an option for additional security during the connection between the Network Dashboard and child site that can be turned on in the Sick Network Settings page of the child site.

Additional FAQs can be found at the [Sick Network FAQ](http://faq.sicknetwork.com) site.

== Screenshots ==

1. The Dashboard Screen
2. The Posts Screen
3. The Pages Screen
4. The Sites Screen
5. The Plugins Screen
6. The Themes Screen
7. The Users Screen
8. The Groups Screen
9. The Offline Checks Screen
10. The Backups Screen

== Changelog ==

= 0.1.34 =
* Fix for new clone flow with bigger databases ("Error importing database")

= 0.1.33 =
* New clone flow
* Added functionality to show size of child site

= 0.1.32 =
* Added extra synchronisation points for faster updates on the main dashboard
* Fix for backups: excludes from full backups was not always working properly

= 0.1.31 =
* Bugfix: Fixes backup issues on some hosts

= 0.1.30 =
* Added feedback if backup directory is not present + uses WP Filesystem classes

= 0.1.29 =
* Fix: Themes listing for auto updates

= 0.1.28 =
* Bugfix: Updating user password removed user roles

= 0.1.27 =
* Fixes issue with activate on install plugin
* Preventing invalid htaccess

= 0.1.26 =
* Bugfix in htaccess creation

= 0.1.25 =
* Added profile picture to user info
* Added better support for hiding footprint

= 0.1.24 =
* Fixes issue with Heatmap footprint hiding

= 0.1.23 =
* Bugfix for database export when trying to import manually.

= 0.1.22 =
* Extra feedback to the main to support a loading bar on the main.
* Bug fix: Bug caused some files not to be added to a full backup.

= 0.1.21 =
* Better support for backups on child sites by using less memory on backup.

= 0.1.20 =
* Bug fix for some plugins not upgrading correctly and not telling the main site something went wrong.

= 0.1.19 =
* Better support for big sites with users that have more then 500 posts.

= 0.1.18 =
* Plugin conflict with WP Zon Builder fixed.

= 0.1.17 =
* Security bugfix (removing wp/plugin updates)

= 0.1.16 =
* Bug fixes
* Ability to enable a unique security ID

= 0.1.15 =
* Added alternative zip support (for backups)

= 0.1.14 =
* Better support for new database export on main site.
* Bugfix in full export.

= 0.1.13 =
* Integrate generate content
* Fixed database backup issue for big databases

= 0.1.12 =
* Added automatic sync when site is first added.
* Added sync for categories for posts.

= 0.1.11 =
* Added possibility to cache plugins/themes/users for big networks to make searching go faster on the main.

= 0.1.10 =
* Added possibility to activate plugin on installation

= 0.1.9 =
* Added possibility to update non-admin user passwords.
* Added link to click when posting new page/post from main.
* Added possibility to mark all searched comments as spam or approve or delete (was limited at 40).

= 0.1.8 =
* Added possibility to exclude folders from backup by name

= 0.1.7 =
* Added possibility to search users by name

= 0.1.6 =
* Added footprint settings functionality. Fixed some minor issues.

= 0.1.4 =
* Code Tweaks to help some users connect

= 0.1.3 =
* Unreleased internal version no upgrade needed

= 0.1.2 =
* Fixed Class Error on Install that some users were having

== Upgrade Notice ==

= 0.1.4 =
* No Upgrade neeeded

= 0.1.3 =
* Unreleased internal version no upgrade needed

= 0.1.2 =
* Fixed Class Error on Install that some users were having as always we recommend you update right away