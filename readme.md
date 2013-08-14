# Nav Menu Roles

Contributors: helgatheviking
Donate link: https://inspirepay.com/pay/helgatheviking
Tags: menu, menus, nav menu, nav menus
Requires at least: 3.4
Tested up to: 3.6
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Control menu output based on user role.

## Description

This plugin lets you hide custom menu items based on user roles.  So if you have a link in the menu that you only want to show to logged in users, certain types of users, or even only to logged out users, this plugin is for you.

## Installation

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

1. Go to Appearance > Menus
1. Edit the menu items accordingly.  First select whether you'd like to display the item to all logged in users, all logged out users or to customize by role.
1. If you chose customize by role, then you you can check the boxes next to the roles you'd like to restrict visibility to.
1. If you choose 'By Role' and don't check any boxes, the item will be visible to everyone like normal.

## FAQ

### 1.I don't see the Nav Menu Roles options in the admin menu items?

This is likely because you have another plugin (or theme) that is also trying to alter the same code that creates the Menu section in the admin.  For example, the UberMenu Mega Menus plugin is a known conflict with Nav Menu Roles.

This is not a failure of Nav Menu Roles and there isn't anything I can do about it. WordPress does not have sufficient hooks in this area of the admin and until they do plugins are forced to replace everything via custom admin menu Walker, of which there can be only one. Until these hooks are added the menu modification plugins are unfortunately going to conflict with one another.

There's a possibility this will be added eventually
http://core.trac.wordpress.org/ticket/18584

When/if it is, I will update Nav Menu Roles.

###2. What happened to my menu roles on import/export?

The Nav Menu Roles plugin stores 1 piece of post *meta* to every menu item/post.  This is exported just fine by the default Export tool.

However, the Import plugin only imports certain post meta for menu items.  As of version 1.3, I've added a custom Importer to Nav Menu Roles as a work around.

###3. How Do I Use the Custom Importer?

1. Go to Tools>Export, choose to export All Content and download the Export file
1. Go to Tools>Import on your new site and perform your normal WordPress import
1. Return to Tools>Import and this time select the Nav Menu Roles importer.
1. Use the same .xml file and perform a second import
1. No duplicate posts will be created but all menu post meta (including your Nav Menu Roles info) will be imported