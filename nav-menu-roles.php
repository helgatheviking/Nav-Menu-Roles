<?php
/*
Plugin Name: Nav Menu Roles
Plugin URI: http://www.kathyisawesome.com/449/nav-menu-roles/
Description: Hide custom menu items based on user roles
Version: 1.3.4
Author: Kathy Darling
Author URI: http://www.kathyisawesome.com
License: GPL2

    Copyright 2012  Kathy Darling  (email: kathy.darling@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


// don't load directly
if ( ! function_exists( 'is_admin' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}


if ( ! class_exists( "Nav_Menu_Roles" ) ) :

class Nav_Menu_Roles {

    function __construct(){

        // Include some admin files
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        // Register Importer
        add_action( 'admin_init', array( $this, 'register_importer' ) );

        // load the textdomain
        add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

        // switch the admin walker
        add_filter( 'wp_edit_nav_menu_walker', array( $this, 'edit_nav_menu_walker' ), 10, 2 );

        // save the menu item meta
        add_action( 'wp_update_nav_menu_item', array( $this, 'nav_update'), 10, 3 );

        // add meta to menu item
        add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_item' ) );

        // exclude items via filter instead of via custom Walker
        if ( ! is_admin() ) {
          add_filter( 'wp_get_nav_menu_items', array( $this, 'exclude_menu_items' ), 10, 3 );
        }
    }

    /**
     * Include required admin files.
     *
     * @access public
     * @return void
     */
    function admin_init() {
      /* include the custom admin walker */
      include_once( plugin_dir_path( __FILE__ ) . 'inc/class.Walker_Nav_Menu_Edit_Roles.php');

    }


    /**
     * Register the Importer
     * the regular Importer skips post meta for the menu items
     *
     * @access private
     * @return void
     */
    function register_importer(){

      include_once( plugin_dir_path( __FILE__ ) . 'inc/class.Nav_Menu_Roles_Import.php');

      // Register the new importer
      if ( defined( 'WP_LOAD_IMPORTERS' ) ) {

        // Register the custom importer we've created.
        $roles_import = new Nav_Menu_Roles_Import();

        register_importer('nav_menu_roles', __('Nav Menu Roles', 'nav-menu-roles'), sprintf( __('Import %snav menu roles%s and other menu item meta skipped by the default importer', 'nav-menu-roles'), '<strong>', '</strong>'), array( $roles_import, 'dispatch' ) );

      }

    }

    /**
     * Make Plugin Translation-ready
     * CALLBACK FUNCTION FOR:  add_action( 'plugins_loaded', array( $this,'load_text_domain'));
     * @since 1.0
     */

    function load_text_domain() {
        load_plugin_textdomain( 'nav-menu-roles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Override the Admin Menu Walker
     * @since 1.0
     */
    function edit_nav_menu_walker( $walker, $menu_id ) {
        return 'Walker_Nav_Menu_Edit_Roles';
    }

    /**
     * Save the roles as menu item meta
     * @return string
     * @since 1.0
     */
    function nav_update( $menu_id, $menu_item_db_id, $args ) {
        global $wp_roles;

        $allowed_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names );

        // verify this came from our screen and with proper authorization.
        if ( ! isset( $_POST['nav-menu-role-nonce'] ) || ! wp_verify_nonce( $_POST['nav-menu-role-nonce'], 'nav-menu-nonce-name' ) )
            return;

        $saved_data = false;

        if ( isset( $_POST['nav-menu-logged-in-out'][$menu_item_db_id]  )  && in_array( $_POST['nav-menu-logged-in-out'][$menu_item_db_id], array( 'in', 'out' ) ) ) {
              $saved_data = $_POST['nav-menu-logged-in-out'][$menu_item_db_id];
        } elseif ( isset( $_POST['nav-menu-role'][$menu_item_db_id] ) ) {
            $custom_roles = array();
            // only save allowed roles
            foreach( $_POST['nav-menu-role'][$menu_item_db_id] as $role ) {
                if ( array_key_exists ( $role, $allowed_roles ) ) $custom_roles[] = $role;
            }
            if ( ! empty ( $custom_roles ) ) $saved_data = $custom_roles;
        }

        if ( $saved_data ) {
            update_post_meta( $menu_item_db_id, '_nav_menu_role', $saved_data );
        } else {
            delete_post_meta( $menu_item_db_id, '_nav_menu_role' );
        }
    }

    /**
     * Adds value of new field to $item object
     * is be passed to Walker_Nav_Menu_Edit_Custom
     * @since 1.0
     */
    function setup_nav_item( $menu_item ) {

        $roles = get_post_meta( $menu_item->ID, '_nav_menu_role', true );

        if ( ! empty( $roles ) ) {
            $menu_item->roles = $roles;
        }
        return $menu_item;
    }

    /**
     * Exclude menu items via wp_get_nav_menu_items filter
     * this fixes plugin's incompatibility with theme's that use their own custom Walker
     * Thanks to Evan Stein @vanpop http://vanpop.com/
     * @since 1.2
     */
    function exclude_menu_items( $items ) {

      // Iterate over the items to search and destroy
      foreach ( $items as $key => $item ) {

        if( isset( $item->roles ) ) {

          switch( $item->roles ) {
            case 'in' :
              $visible = is_user_logged_in() ? true : false;
              break;
            case 'out' :
              $visible = ! is_user_logged_in() ? true : false;
              break;
            default:
              $visible = false;
              if ( is_array( $item->roles ) && ! empty( $item->roles ) ) foreach ( $item->roles as $role ) {
                if ( current_user_can( $role ) ) $visible = true;
              }
              break;
          }
          if ( ! $visible ) unset( $items[$key] ) ;
        }

      }
      return $items;
    }

} // end class

endif; // class_exists check


/**
* Launch the whole plugin
*/
global $Nav_Menu_Roles;
$Nav_Menu_Roles = new Nav_Menu_Roles();
