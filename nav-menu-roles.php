<?php
/**
 * Plugin Name: Nav Menu Roles
 * Plugin URI: http://www.kathyisawesome.com/449/nav-menu-roles/
 * Description: Hide custom menu items based on user roles.
 * Version: 2.1.0
 * Author: Kathy Darling
 * Author URI: http://www.kathyisawesome.com
 * License: GPL-3.0
 * Text Domain: nav-menu-roles
 *
 * Copyright 2020 Kathy Darling
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * @package Nav Menu Roles
 */

// Don't load directly.
if ( ! function_exists( 'is_admin' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Execute when not already loaded.
if ( ! class_exists( 'Nav_Menu_Roles' ) ) {

	require_once dirname( __FILE__ ) . '/inc/class-nav-menu-roles.php';

	/**
	 * Launch the whole plugin
	 * Returns the main instance of Nav Menu Roles to prevent the need to use globals.
	 *
	 * @since  1.5
	 * @return Nav_Menu_Roles
	 */
	function nav_menu_roles() {
		$instance = Nav_Menu_Roles::instance( __FILE__ );

		// Global for backwards compatibility.
		$GLOBALS['Nav_Menu_Roles'] = $instance;

		return $instance;
	}
	add_action( 'plugins_loaded', 'nav_menu_roles' );

}
