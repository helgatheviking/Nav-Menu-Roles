=== Nav Menu Roles ===

Contributors: helgatheviking
Donate link: https://paypal.me/helgatheviking
Tags: menu, menus, nav menu, nav menus
Requires at least: 6.6.0
Tested up to: 7.1.0
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv3

Hide custom menu items based on user roles.

== Description ==

This plugin lets you hide custom menu items based on user roles.  So if you have a link in the menu that you only want to show to logged in users, certain types of users, or even only to logged out users, this plugin is for you.

Nav Menu Roles is very flexible. In addition to standard user roles, you can customize the functionality by adding your own check boxes with custom labels using the `nav_menu_roles` filter and then using the `nav_menu_roles_item_visibility` filter to check against whatever criteria you need. You can check against any user meta values (like capabilities) and any custom attributes added by other plugins.

= IMPORTANT NOTE =

In WordPress menu items and pages are completely separate entities. Nav Menu Roles does not restrict access to content. Nav Menu Roles is *only* for showing/hiding *nav menu* items. If you wish to restrict content then you need to also be using a membership plugin.

= Usage =

1. Go to Appearance > Menus
1. Set the "Target audience" to either "Everyone", "Logged In Users", or "Logged Out Users". "Everyone" is the default.
1. If you wish to customize by role, set the "Target audience" to "Logged In Users" and under "Target role" check the boxes next to the desired roles. The item will be visible to users who have *any* of the checked roles. **Roles are not hierarchical**, so an item set to "Subscriber" is *not* visible to Administrators unless you also check "Administrator". If no roles are checked, the item is visible to all logged in users.
1. Set the "Display Mode" to "Show" to display the item only to the selected audience, or to "Hide" to hide the item from the selected audience and show it to everyone else. "Show" is the default.

= Support =

Support is handled in the [WordPress forums](https://wordpress.org/support/plugin/nav-menu-roles). Please note that support is limited and does not cover any custom implementation of the plugin. Before posting, please read the [FAQ](http://wordpress.org/plugins/nav-menu-roles/faq/). Also, please verify the problem with other plugins disabled and while using a default theme. 

Please report any bugs, errors, warnings, code problems to [GitHub](https://github.com/helgatheviking/nav-menu-roles/issues)

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Appearance > Menus
1. Edit the menu items accordingly. First select whether you'd like to display the item to Everyone, all logged out users, or all logged in users. 
1. Logged in users can be further limited to specific roles by checking the boxes next to the roles you'd like to restrict visibility to.

== Screenshots ==

1. Show the new options for the menu items in the admin menu customizer

== Frequently Asked Questions ==

= I don't see Appearance > Menus. Where are the settings? =

Block themes, such as Twenty Twenty-Five, use the Navigation block instead of classic menus, so WordPress hides Appearance > Menus.

Since version 3.0.0, the Nav Menu Roles settings are in the Site Editor instead:

1. Go to Appearance > Editor > Navigation and edit your menu.
1. Select a link or submenu.
1. In the block settings sidebar, open the "Nav Menu Roles" panel.

The settings work the same way as for classic menus. Hiding a submenu also hides all of the links inside it. Links generated automatically by the Page List block cannot be restricted.

Classic themes still use Appearance > Menus.

= I don't see the Nav Menu Roles options in Appearance > Menus? =

Nav Menu Roles adds its options through the `wp_nav_menu_item_custom_fields` hook, which has been part of WordPress since 5.4. If a theme or plugin replaces the menu editor's Walker and leaves that hook out, no plugin can add fields there.

To check, switch to a default theme and disable other plugins one at a time. If you find the conflict, ask its author to include the core `wp_nav_menu_item_custom_fields` hook. Visibility on the front end still works while the options are missing, so you can also switch themes temporarily to edit the settings, then switch back.

= I'm using XYZ Membership plugin and I don't see its "levels"? =

There are apparently a few membership plugins out there that *don't* use traditional WordPress roles/capabilities. My plugin will list any role registered in the traditional WordPress way. If your membership plugin is using some other system, then Nav Menu Roles won't work with it out of the box.  Since 1.3.5 I've added a filter called `nav_menu_roles_item_visibility` just before my code decides whether to show/hide a menu item. There's also always been the `nav_menu_roles` filter which lets you modify the roles listed in the admin. Between these two, I believe you have enough to integrate Nav Menu Roles with any membership plugin. 

Here's an example where I've added a new pseudo role, creatively called "new-role".  The first function adds it to the menu item admin screen. The second function is pretty generic and won't actually do anything because you need to supply your own logic based on the plugin you are using.  Nav Menu Roles will save the new "role" info and add it to the item in an array to the `$item->roles` variable.

= Existing Compatibility Plugins =

1. [Wishlists Memberships](https://github.com/helgatheviking/nav-menu-roles-wishlists-memberships)
2. [WooCommerce Memberships](https://github.com/helgatheviking/nav-menu-roles-woocommerce-memberships)

If your membership plugin is not listed here, you may be able to use the above bridge plugins as a template. Scroll down to the bottom of the main plugin file and you will see a section for "Helper Functions". If you modify the 3 wrapper functions according to your membership plugin's logic, the rest of the plugin should handle the integration with Nav Menu Roles.

= Adding a new "role" =

The roles in NMR are filterable distinct from the global `$wp_roles`. This allows for compatibility to be added between plugins that don't use the core roles to determine access, like some membership plugins. 

`
/**
 * Add custom roles to Nav Menu Roles menu list
 * param: $roles an array of all available roles, by default is global $wp_roles 
 * return: array
 */
function kia_new_roles( $roles ) {
  $roles['new-role-key'] = 'new-role';
  return $roles;
}
add_filter( 'nav_menu_roles', 'kia_new_roles' );
`

Note, if you want to add a WordPress capability the above is literally all you need. Because Nav Menu Roles checks whether a role has permission to view the menu item using `current_user_can($role) you do not need to right a custom callback for the `nav_menu_roles_item_visibility` filter.

In case you *do* need to check your visibility status against something very custom, here is how you'd go about it:

`
/**
  * Change visibility of each menu item
  * @param bool $visible
  * @param obj $item The complete menu object. Nav Menu Roles adds its info to the following keys {
  *        	roles - Possible value: "in" (all logged in) | "out" (all logged out) | an array of specific roles, ex: [ "administrator", "editor" ]
  *   		display_mode - Possible values: "show" (show if conditions met) | "hide" (hide if conditions met)
  * return boolean
  */
function kia_item_visibility( $visible, $item ) {
  if ( isset( $item->roles ) && is_array( $item->roles ) && in_array( 'new-role-key', $item->roles ) ) {
  /*  if ( // your own custom check on the current user versus 'new-role' status ) {
        $visible = true;
      } else {
        $visible = false;
    }
  */  }
  return $visible;
}
add_filter( 'nav_menu_roles_item_visibility', 'kia_item_visibility', 10, 2 );
`

Note that you have to generate your own if/then logic. I can't provide free support for custom integration with another plugin. You may [contact me](http://kathyisawesome.com/contact) to discuss hiring me, or I would suggest using a plugin that supports WordPress' roles, such as Justin Tadlock's [Members](http://wordpress.org/plugins/members).

= Sort the roles alphabetically =

Add the following snippet to your theme's `functions.php` file:

`
/*
 * Sort the NMR roles
 * @param: $roles an array of all available roles with ID=>Name
 * @return: array
 */
function kia_sort_roles( $roles ) {
  if ( is_admin() ) {
    $array_lowercase = array_map( 'strtolower', $roles );
    array_multisort( $array_lowercase, SORT_ASC, SORT_STRING, $roles );
    return $roles;
  }
}
add_filter( 'nav_menu_roles', 'kia_sort_roles' );
`

= The menu exploded? Why are all my pages displaying for logged out users? =

If every item in your menu is configured to display to logged in users (either all logged in users, or by specific role), then when a logged out visitor comes to your site there are no items in the menu to display.  `wp_nav_menu()` will then try check its `fallback_cb` argument... which defaults to `wp_page_menu`.

Therefore, if you have no items to display, WordPress will end up displaying ALL your pages!!

If you don't want this, you must set the fallback argument to be a null string. 

`
wp_nav_menu( array( 'theme_location' => 'primary-menu', 'fallback_cb' => '' ) );
`

You must do this for every effected instance of `wp_nav_menu()` in your theme templates. It is not possible for me to tell you where they are located, but `header.php` is a very common location for the main menu.

Alternatively, you could do this universally by adding the following snippet to your child theme's *function.php* file or by adding it via the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin:

`
/**
 * Disables the fallback page menu for all menus
 *
 * @param array $args Array of wp_nav_menu() arguments.
 * @return array
 */
function kia_nav_menu_args( $args ) {
  $args['fallback_cb'] = '';
  return $args;
}
add_filter( 'wp_nav_menu_args', 'kia_nav_menu_args' );
`

= What happened to my menu roles on import/export? =

The Nav Menu Roles plugin stores 1 piece of post *meta* to every menu item/post.  This is exported just fine by the default Export tool.

However, the Import plugin only imports certain post meta for menu items.  As of version 1.3, I've added a custom Importer to Nav Menu Roles as a work around.

= How Do I Use the Custom Importer? =

1. Go to Tools>Export, choose to export All Content and download the Export file
1. Go to Tools>Import on your new site and perform your normal WordPress import
1. Return to Tools>Import and this time select the Nav Menu Roles importer.
1. Use the same .xml file and perform a second import
1. No duplicate posts will be created but all menu post meta (including your Nav Menu Roles info) will be imported

= Is Nav Menu Roles compatible with WPML ? =

Yes, but manually. WPML developers have informed me that the meta data for nav menu items is **not** synced by WPML, meaning that menus copied into a new language will not bring their custom Nav Menu Roles settings. However, if you manually reconfigure the settings, the new language menu will work as expected.

== Changelog ==

= 3.0.0 =
* Requires: PHP 7.4 and WordPress 6.6
* New: Add support for block themes and the Navigation block.
* New: Add wpml-config.xml

= 2.1.3 = 
* Requires: PHP 7.4 and WordPress 6.3
* Fix: RTL layout style.

= 2.1.2 = 
* Fix: Typos.

= 2.1.1 = 
* Fix: Top-level display of hidden child menu items of hidden parent menu items.

= 2.1.0 = 
* New: Add support for "hiding" a menu item by role.

= 2.0.2 = 
* Fix: PHP Fatal error: Uncaught Error: Call to undefined method WP_Customize_Manager::settings_previewed(). settings_previewed() does not exist until WordPress 3.9.0+.

= 2.0.1 = 
* Tweak: Alphabetically sort role names.

= 2.0.0 = 
* New: Customizer support! props @westonruter!

= 1.10.2 = 
* Fix:  Strict array typecasting for nav menu classes.

= 1.10.1 = 
* Fix:  Only auto-apply classes on front-end. Prevents appending duplicate classes on each save.

= 1.10.0 = 
* Fix: My hook made it into core for WP5.4!! Can finally stop overriding the admin menu Walker.

= 1.9.5 = 
* Fix: PHP Notice:  Undefined variable $item

= 1.9.4 = 
* Add the NMR roles as css classes to the menu output
* Improve menu editor role checkbox list UX, by making list items full-width. Props @lkraav

= 1.9.3 =
* Check all object properties exist before accessing. Resolves PHP notices for custom menu items.

= 1.9.2 =
* Include a !empty() check on menu $items
* Update donation link

= 1.9.1 =
* Update donation link
* Update required and tested against versions

= 1.9.0 =
* Add support for multisite. Props @open-dsi and @fiech.

= 1.8.5 =
* Use new Walker for WP4.7

= 1.8.4 =
* Prevent nav menu items edited in the customizer from rendering when they should be excluded

= 1.8.3 = 
* Remove deprecated screen_icon()

= 1.8.2 = 
* Reduce number of parameters passed to `add_action_links` filter

= 1.8.1 = 
* Switch input names to use a counter [nav-menu-role][100][1]. For some reason [nav-menu-role][100][] doesn't post an array and hyphenated names [nav-menu-role][100][gold-plan] wreak havoc on the save routine. Shouldn't impact anyone not using hyphenated role names. 

= 1.8.0 = 
* Fix style issue in WordPress 4.5

= 1.7.9 = 
* revert priority of walker back to default because themes are not actually using the hook to add their own fields. sadface. 

= 1.7.8 = 
* remove all admin notices

= 1.7.7 =
* add fancy debug messages

= 1.7.6 =
* tweak CSS to initially hide checkboxes on newly added menu items (defaults to "Everyone" so roles should not appear)

= 1.7.5 =
* Update Walker_Nav_Menu_Edit_Roles to mimic Walker_Nav_Menu in WordPress 4.4

= 1.7.4 =
* Change language in metabox to try to explain min caps versus strict role checking
* keep tweaking the FAQ

= 1.7.3 =
* update readme, update error notice, add more links to the FAQ

= 1.7.2 =
* add Italian language. props @sododesign

= 1.7.1 =
* Updated FAQ with patch instructions for conflicting plugins/themes
* add Portuguese language. props @brunobarros

= 1.7.0 =
* adjust admin UI to be more user-friendly. Options are now: show to everyone, show to logged out users, and show to logged in users (optionally, logged in users by specific role)

= 1.6.5 =
* add Guajarati language. props @rohilmistry93

= 1.6.4 =
* more language issues -> sync svn+git version numbers

= 1.6.3 =
* Try again to add languages. Where'd they all go?

= 1.6.2 =
* Add French translation. Props @Philippe Gilles

= 1.6.1 =
* Update list of conflits
* Don't display radio buttons if no roles - allows for granular permissions control

= 1.6.0 =
* Feature: Hiding a parent menu item will automatically hide all its children
* Feature: Add compatibility with Menu Item Visibility Control plugin and any plugin/theme that is willing to add its inputs via the `wp_nav_menu_item_custom_fields` hook. See the [FAQ](http://wordpress.org/plugins/nav-menu-roles/faq/#compatibility) to make our plugins compatible.

= 1.5.1 =
* Hopefully fix missing nav-menu-roles.min.js SVN issue

= 1.5.0 =
* Switch to instance of plugin
* Add notice when conflicting plugins are detected 
* Remove some extraneous parameters
* Add Spanish translation thanks to @deskarrada

= 1.4.1 =
* update to WP 3.8 version of Walker_Nav_Menu_Edit (prolly not any different from 3.7.1)
* minor CSS adjustment to admin menu items
* checked against WP 3.8

= 1.4 =
* Add to FAQ
* add JS flair to admin menu items
* update to WP 3.7.1 version of Walker_Nav_Menu_Edit

= 1.3.5 =
* Add nav_menu_roles_item_visibility filter to work with plugins that don't use traditional roles

= 1.3.4 =
* Update admin language thanks to @hassanhamm
* Add Arabic translation thanks to @hassanhamm

= 1.3.3 =
* Fix Nav_Menu_Roles_Import not found error

= 1.3.2 =
* Stupid comment error causing save issues

= 1.3.1 =
* SVN failure to include importer files!

= 1.3 =
* Added custom importer

= 1.2 =
* Major fix for theme's that use their own custom Walkers, thanks to Evan Stein @vanpop http://vanpop.com/
* Instead of a custom nav Walker, menu items are controlled through the wp_get_nav_menu_items filter
* Remove the custom nav Walker code

= 1.1.1 =
* Fix link to plugin site
* Fix labels in admin Walker

= 1.1 =
* Clean up debug messages

= 1.0 =
* Initial release
