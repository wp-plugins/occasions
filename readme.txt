=== Occasions ===
Contributors: Alphawolf
Donate link: http://www.schloebe.de/donate/
Tags: admin, ajax, management, cms, occasion, plugin, inline, google, time, doodle, event management, events
Requires at least: 2.5
Tested up to: 4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Do it like Google! Define any number of occasions in your BE with a fancy AJAX-Interface and the plugin will display them in time... just like Google.

== Description ==

Do it like Google! Just like Google change their logos on certain occasions such as Christmas or New Year's Eve, the Occasions plugin offers the possibility to display text or HTML on specified dates. The occasions can be easily managed by a fancy and comfortable AJAX-Interface.

[Developer on Google+](https://plus.google.com/118074611982254715031 "Developer on Google+") | [Developer on Twitter](http://twitter.com/wpseek "Developer on Twitter")

[Become A Patron, Support The Developer.](http://www.patreon.com/oliver_schloebe "Become A Patron, Support The Developer.")

[vimeo http://vimeo.com/9598898]

**Features:**

* "Date dependencies" on chosing start and end dates
* AJAX interface for managing the occasions
* Object-oriented code
* Supports changing location/ renaming of wp-content/ folder with WP 2.6
* Entirely possible to be localized, including the JS calendar

**Usage:**

To display the list of current occasions, use:

`<?php
if( class_exists('Occasions') ) {
  $Occasions->_output();
}
?>`

To return the output of current occasions, e.g. to load into a variable, use:

`<?php
if( class_exists('Occasions') ) {
   $occasionsoutput = $Occasions->_return();
}
?>`

There is also a Shortcode available:

`[Occasions]`


**Included languages:**

* English
* German (de_DE) (Thanks to me ;-))
* Belorussian (by_BY) (Thanks for contributing belorussian language goes to [Marcis Gasuns](http://www.fatcow.com))
* Russian (ru_RU) (Thanks for contributing russian language goes to [Thomas Gorny](http://www.ipower.com))
* Hebrew (he_IL) (Thanks for contributing hebrew language goes to Atar4U / https://profiles.wordpress.org/ahrale/)

**Looking for more WordPress plugins? Visit [www.schloebe.de/portfolio/](http://www.schloebe.de/portfolio/)**

== Frequently Asked Questions ==

= Why isn't this or that implemented to improve the admin interface? =

If you have suggestions please let me know by dropping me a line via e-mail or the wp.org forums.

= History? =

Please visit [the official website](http://www.schloebe.de/wordpress/occasions-plugin/#english "Occasions") for the latest information on this plugin.

= Where can I get more information? =

Please visit [the official website](http://www.schloebe.de/wordpress/occasions-plugin/#english "Occasions") for the latest information on this plugin.

== Installation ==

1. Download the plugin and unzip it.
2. Upload the folder occasions/ to your /wp-content/plugins/ folder.
3. Activate the plugin from your WordPress admin panel.
4. Installation finished.

== Changelog ==

= 1.1 =
* Added hebrew localization (Thanks to Atar4U)

= 1.0.4 =
* Readme.txt updated to be more compliant with the readme.txt standard
* Moved screenshots off the package to the assets/ folder

= 1.0.3 =
* Maintenance Release

= 1.0.2 =
* Maintenance Release

= 1.0.1 =
* Changed database collation to fix issues on a few systems

== Other Notes ==

= Licence =

This plugins is released under the GPL, you can use it free of charge on your personal or commercial blog.

== Screenshots ==

1. Event Management

1. JS Calendar

1. Options

= Demo Video =

[vimeo http://vimeo.com/9598898]