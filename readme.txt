=== Nav Menu Roles ===

Contributors: helgatheviking
Donate link: https://www.paypal.me/helgatheviking
Tags: menu, menus, nav menu, nav menus
Requires at least: 4.5.0
Tested up to: 4.7.0
Stable tag: 1.8.6
License: GPLv3

Hide custom menu items based on user roles. PLEASE READ THE FAQ IF YOU ARE NOT SEEING THE SETTINGS.

== Description ==

This plugin lets you hide custom menu items based on user roles.  So if you have a link in the menu that you only want to show to logged in users, certain types of users, or even only to logged out users, this plugin is for you.

Nav Menu Roles is very flexible. In addition to standard user roles, you can customize the functionality by adding your own check boxes with custom labels using the `nav_menu_roles` filter and then using the `nav_menu_roles_item_visibility` filter to check against whatever criteria you need. You can check against any user meta values (like capabilities) and any custom attributes added by other plugins.

= IMPORTANT NOTE =

In WordPress menu items and pages are completely separate entities. Nav Menu Roles does not restrict access to content. Nav Menu Roles is *only* for showing/hiding *nav menu* items. If you wish to restrict content then you need to also be using a membership plugin.

= Usage =

1. Go to Appearance > Menus
1. Set the "Display Mode" to either "logged in users", "logged out users", or "everyone". "Everyone" is the default.
1. If you wish to customize by role, set the "Display Mode" to "Logged In Users" and under "Restrict menu item to a minimum role" check the boxes next to the desired roles. **Keep in mind that the role doesn't limit the item strictly to that role, but to everyone who has that role's capability.** For example: an item set to "Subscriber" will be visible by Subscribers *and* by admins. Think of this more as a minimum role required to see an item. 

= Support =

Support is handled in the [WordPress forums](https://wordpress.org/support/plugin/nav-menu-roles). Please note that support is limited and does not cover any custom implementation of the plugin. Before posting, please read the [FAQ](http://wordpress.org/plugins/nav-menu-roles/faq/). Also, please verify the problem with other plugins disabled and while using a default theme. 

Please report any bugs, errors, warnings, code problems to [Github](https://github.com/helgatheviking/nav-menu-roles/issues)

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Appearance > Menus
1. Edit the menu items accordingly. First select whether you'd like to display the item to Everyone, all logged out users, or all logged in users. 
1. Logged in users can be further limited to specific roles by checking the boxes next to the roles you'd like to restrict visibility to.

== Screenshots ==

1. Show the new options for the menu items in the admin menu customizer

== Frequently Asked Questions ==

= I don't see the Nav Menu Roles options in the admin menu items?  =

This is because you have another plugin (or theme) that is also trying to alter the same code that creates the Menu section in the admin.  

WordPress does not have sufficient hooks in this area of the admin and until they do plugins are forced to replace everything via custom admin menu Walker, of which there can be only one. There's a [trac ticket](http://core.trac.wordpress.org/ticket/18584) for this, but it has been around a while. 

**A non-exhaustive list of known conflicts:**

1. UberMenu 2.x Mega Menus plugin (UberMenu 3.x supports NMR!)
2. Add Descendants As Submenu Items plugin
3. Navception plugin
4. Suffusion theme
5. BeTheme
6. Yith Menu
7. Jupiter Theme
8. iMedica theme
9. Prostyler EVO theme
10. Mega Main Plugin


= Workaround #1 =
[Shazdeh](https://profiles.wordpress.org/shazdeh/) had the genius idea to not wait for a core hook and simply add the hook ourselves. If all plugin and theme authors use the same hook, we can make our plugins play together.

Therefore, as of version 1.6 I am modifying my admin nav menu Walker to *only* adding the following lines (right after the description input):

`
<?php 
// Place this in your admin nav menu Walker
do_action( 'wp_nav_menu_item_custom_fields', $item_id, $item, $depth, $args );
// end added section 
?>
` 

**Ask your conflicting plugin/theme's author to add this code to his plugin or theme and our plugins will become compatible.**

= Instructions for Patching Your Plugin/Theme =

Should you wish to attempt this patch yourself, you can modify your conflicting plugin/theme's admin menu Walker class. 

**Reminder: I do not provide support for fixing your plugin/theme. If you aren't comfortable with the following instructions, contact the developer of the conflicting plugin/theme!**

  1\. Find the class that extends the `Walker_Nav_Menu`. The fastest way to do this is to search your whole plugin/theme folder for `extends Walker_Nav_Menu`. When you find the file that contains this text you willl know which file you need to edit. Once you find it here's what the beginning of that class will look like:

`class YOUR_THEME_CUSTOM_WALKER extends Walker_Nav_Menu {}`

  2\. Find the `start_el()` method

In that file you will eventually see a class method that looks like:

`function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
  // some stuff truncated for brevity
}
`

  3\. Paste my action hook somewhere in this method!

In Nav Menu Roles, I have placed the hook directly after the description, like so:

`
<p class="field-description description description-wide">
  <label for="edit-menu-item-description-<?php echo $item_id; ?>">
    <?php _e( 'Description' ); ?><br />
    <textarea id="edit-menu-item-description-<?php echo $item_id; ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo $item_id; ?>]"><?php echo esc_html( $item->description ); // textarea_escaped ?></textarea>
    <span class="description"><?php _e('The description will be displayed in the menu if the current theme supports it.'); ?></span>
  </label>
</p>

<?php 
// Add this directly after the description paragraph in the start_el() method
do_action( 'wp_nav_menu_item_custom_fields', $item_id, $item, $depth, $args );
// end added section 
?>
`

= Workaround #2 =

As a workaround, you can switch to a default theme (or disable the conflicting plugin), edit the Nav Menu Roles, for each menu item, then revert to your original theme/ reenable the conflicting plugin. The front-end functionality of Nav Menu Roles will still work. 

= Workaround #3 =

Download and install this [tiny plugin](https://gist.github.com/helgatheviking/d00f9c033a4b0aab0f69cf50d7dcd89c). Activate it when you need to make the NMR options appear and then disable it when you are done editing. 

= I'm using XYZ Membership plugin and I don't see its "levels"? =

There are apparently a few membership plugins out there that *don't* use traditional WordPress roles/capabilities. My plugin will list any role registered in the traditional WordPress way. If your membership plugin is using some other system, then Nav Menu Roles won't work with it out of the box.  Since 1.3.5 I've added a filter called `nav_menu_roles_item_visibility` just before my code decides whether to show/hide a menu item. There's also always been the `nav_menu_roles` filter which lets you modify the roles listed in the admin. Between these two, I believe you have enough to integrate Nav Menu Roles with any membership plugin. 

Here's an example where I've added a new pseudo role, creatively called "new-role".  The first function adds it to the menu item admin screen. The second function is pretty generic and won't actually do anything because you need to supply your own logic based on the plugin you are using.  Nav Menu Roles will save the new "role" info and add it to the item in an array to the `$item->roles` variable.

= Adding a new "role" =

`
/*
 * Add custom roles to Nav Menu Roles menu list
 * param: $roles an array of all available roles, by default is global $wp_roles 
 * return: array
 */
function kia_new_roles( $roles ){
  $roles['new-role-key'] = 'new-role';
  return $roles;
}
add_filter( 'nav_menu_roles', 'kia_new_roles' );
`

Note, if you want to add a WordPress capability the above is literally all you need. Because Nav Menu Roles checks whether a role has permission to view the menu item using `current_user_can($role) you do not need to right a custom callback for the `nav_menu_roles_item_visibility` filter.

In case you *do* need to check your visibility status against something very custom, here is how you'd go about it:

`
/*
 * Change visibilty of each menu item
 * param: $visible boolean
 * param: $item object, the complete menu object. Nav Menu Roles adds its info to $item->roles
 * $item->roles can be "in" (all logged in), "out" (all logged out) or an array of specific roles
 * return boolean
 */
function kia_item_visibility( $visible, $item ){
  if( isset( $item->roles ) && is_array( $item->roles ) && in_array( 'new-role-key', $item->roles ) ){
  /*  if ( // your own custom check on the current user versus 'new-role' status ){
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

= The menu exploded? Why are all my pages displaying for logged out users? =

If every item in your menu is configured to display to logged in users (either all logged in users, or by specific role), then when a logged out visitor comes to your site there are no items in the menu to display.  `wp_nav_menu()` will then try check its `fallback_cb` argument... which defaults to `wp_page_menu`.

Therefore, if you have no items to display, WordPress will end up displaying ALL your pages!!

If you don't want this, you must set the fallback argument to be a null string.

`
wp_nav_menu( array( 'theme_location' => 'primary-menu', 'fallback_cb' => '' ) );
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

== Changelog ==

= 1.8.5 =
* Use new Walker for WP4.7

= 1.8.4 =
* Prevent nav menu items edited in the customizer from rendering when they should be excluded

= 1.8.3 = 
* Remove deprecated screen_icon()

= 1.8.2 = 
* Reduce number of parameters passed to `add_action_links` filter

= 1.8.1 = 
* Switch input names to use a counter [nav-menu-role][100][1]. For some reason [nav-menu-role][100][] doesn't post an array and hypenated names [nav-menu-role][100][gold-plan] wreak havoc on the save routine. Shouldn't impact anyone not using hyphenated role names. 

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
* add Portugeuse language. props @brunobarros

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