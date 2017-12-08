=== Integrated Assets Manager ===
Contributors: jackreichert
Tags: uploads, file sharing, file management, asset management, assets, share file, content, links, admin, social
Requires at least: 3.7
Tested up to: 4.9.1
Stable tag: trunk

Integrated file management tools into WP Media.

== Description ==

This is the complete rebuild V2 of my plugin [Assets Manager](https://wordpress.org/plugins/assets-manager/). It is a self-hosted file sharing tool. Born out of the need for a file sharing tool that was not blocked by high security firewalls, such as many existing file sharing services are, Assets Manager was developed. When you upload a file, or set of files, Assets Manager generates obscured links to the files so that you can control how those files are shared.

[Here’s how it works.](http://www.jackreichert.com/2015/11/15/how-assets-manager-replaced-our-sharefile/)

= Features =
* Set an expiration period for when the file link will expire.
* Disable links after they've been shared (no more fretting when sending out emails).
* Force anyone trying to access a link to log into your site.

For more information check out the full blogpost about [Assets Manager](http://www.jackreichert.com/2014/01/12/introducing-assets-manager-for-wordpress/).
Questions? Comments? Requests? [Contact me](http://www.jackreichert.com/contact/).

## Installation

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Resave your 'Pretty Permalinks' structure under "Settings > Permalinks".

## Frequently Asked Questions

### What's "integrated" about it?

The original version used a post type to attach assets to. This version integrates into the media library so that you can obfuscate and expire **any** attachment.

### What about foo bar?

Answer to foo bar dilemma.

## Changelog

= 2.0.1 =

* This is the first version of the rebuild.
