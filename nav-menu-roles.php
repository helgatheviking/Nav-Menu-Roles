<?php
/*
Plugin Name: Nav Menu Roles
Plugin URI: http://www.kathyisawesome.com/449/nav-menu-roles/ ‎
Description: Hide custom menu items based on user roles
Version: 1.1
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

        // Include required files
        $this->includes();

        // load the textdomain
        add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

        // switch the admin walker
        add_filter( 'wp_edit_nav_menu_walker', array( $this, 'edit_nav_menu_walker' ), 10, 2 );

        // add meta to menu item
        add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_item' ) );
        // save the menu item meta
        add_action( 'wp_update_nav_menu_item', array( $this, 'nav_update'), 10, 3 );
        // switch the front-end walker
        add_filter( 'wp_nav_menu_args', array( $this, 'nav_menu_args' ), 99 );

    }

    /**
     * Include required core files.
     *
     * @access public
     * @return void
     */
    function includes() {
        if ( is_admin() ) { 
            $this->admin_includes();
        } else { 
            $this->frontend_includes();
        }

    }


    /**
     * Include required admin files.
     *
     * @access public
     * @return void
     */
    function admin_includes() {
        /* include the custom admin walker */
        include_once( plugin_dir_path( __FILE__ ) . 'inc/class.Walker_Nav_Menu_Edit_Roles.php');
    }

    /**
     * Include required frontend files.
     *
     * @access public
     * @return void
     */
    function frontend_includes() {
        /* include the custom front-end walker */
        include_once( plugin_dir_path( __FILE__ ) . 'inc/class.Nav_Menu_Role_Walker.php');
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
     * Change the args of the front-end menu
     * @since 1.0
     */
    function nav_menu_args ( $args ) {
        return array_merge( $args, array(
        'walker' => new Nav_Menu_Role_Walker(),
        ) );
    }

} // end class

endif; // class_exists check


/**
* Launch the whole plugin
*/
global $Nav_Menu_Roles;
$Nav_Menu_Roles = new Nav_Menu_Roles();