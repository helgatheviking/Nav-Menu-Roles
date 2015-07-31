<?php
/*
Plugin Name: Nav Menu Roles
Plugin URI: http://www.kathyisawesome.com/449/nav-menu-roles/
Description: Hide custom menu items based on user roles
Version: 1.7.1
Author: Kathy Darling
Author URI: http://www.kathyisawesome.com
License: GPL2

Copyright 2014 Kathy Darling(email: kathy.darling@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA

*/


// don't load directly
if ( ! function_exists( 'is_admin' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


if ( ! class_exists( "Nav_Menu_Roles" ) ) :

class Nav_Menu_Roles {

	/**
	* @var Nav_Menu_Roles The single instance of the class
	* @since 1.5
	*/
	protected static $_instance = null;

	/**
	* @var string donate url
	* @since 1.5
	*/
	public static $donate_url = "https://inspirepay.com/pay/helgatheviking";

	/**
	* @var string version number
	* @since 1.7.1
	*/
	public $version = '1.7.1';

	/**
	* Main Nav Menu Roles Instance
	*
	* Ensures only one instance of Nav Menu Roles is loaded or can be loaded.
	*
	* @since 1.5
	* @static
	* @see Nav_Menu_Roles()
	* @return Nav_Menu_Roles - Main instance
	*/
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	* Cloning is forbidden.
	*
	* @since 1.5
	*/
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' , 'nav-menu-roles'), '1.5' );
	}

	/**
	* Unserializing instances of this class is forbidden.
	*
	* @since 1.5
	*/
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' , 'nav-menu-roles'), '1.5' );
	}

	/**
	* Nav_Menu_Roles Constructor.
	* @access public
	* @return Nav_Menu_Roles
	* @since  1.0
	*/
	function __construct(){

		// Admin functions
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		
		// load the textdomain
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

		// add a notice that NMR is conflicting with another plugin
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'activated_plugin', array( $this, 'delete_transient' ) );
		add_action( 'deactivated_plugin', array( $this, 'delete_transient' ) );

		// switch the admin walker
		add_filter( 'wp_edit_nav_menu_walker', array( $this, 'edit_nav_menu_walker' ) );

		// add new fields via hook
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'custom_fields' ), 10, 4 );

		// add some JS
		add_action( 'admin_enqueue_scripts' , array( $this, 'enqueue_scripts' ) );

		// save the menu item meta
		add_action( 'wp_update_nav_menu_item', array( $this, 'nav_update'), 10, 2 );

		// add meta to menu item
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_item' ) );

		// exclude items via filter instead of via custom Walker
		if ( ! is_admin() ) {
			add_filter( 'wp_get_nav_menu_items', array( $this, 'exclude_menu_items' ) );
		}

	}

	/**
	* Include the custom admin walker
	*
	* @access public
	* @return void
	*/
	function admin_init() {
		include_once( plugin_dir_path( __FILE__ ) . 'inc/class.Walker_Nav_Menu_Edit_Roles.php');

		// Register Importer
		$this->register_importer();

		// save user notice
		$this->nag_ignore();
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
	* Display a Notice if plugin conflicts with another
	* @since 1.5
	*/
	function admin_notice() {
		global $pagenow, $wp_filter;

		// quit early if not on the menus page
		if( ! in_array( $pagenow, array( 'nav-menus.php', 'plugins.php' ) ) ){
			return;
		}

		// Get any existing copy of our transient data
		if ( false === ( $conflicts = get_transient( 'nav_menu_roles_conflicts' ) ) ) {

			// It wasn't there, so regenerate the data and save the transient
			global $wp_filter;

			$filters = is_array( $wp_filter['wp_edit_nav_menu_walker'] ) ? array_shift( $wp_filter['wp_edit_nav_menu_walker'] ) : array();

			foreach( $filters as $filter ){
				// we expect to see NVR so collect everything else
				if( ! is_a( $filter['function'][0], 'Nav_Menu_Roles') ) {
					$conflicts[] = is_object( $filter['function'][0] ) ? get_class( $filter['function'][0] ) : $filter['function'][0];
				}

			}
		}


		// Check Transient for conflicts and show error
		if ( ! empty ( $conflicts ) ) {
			global $current_user ;
			$user_id = $current_user->ID;

			if ( ! get_user_meta( $user_id, 'nmr_ignore_notice' ) ) {

				echo '<div class="updated">
				<p>';
				printf ( __( 'Nav Menu Roles has detected a possible conflict with the following functions or classes: %1$s. Please see the %2$sFAQ%3$s for more information and possible resolution. | %4$sHide Notice%3$s', 'nav-menu-roles' ),
				'<code>' . implode( $conflicts, ', ' ) . '</code>',
				'<a href="http://wordpress.org/plugins/nav-menu-roles/faq#conflict" target="_blank">',
				'</a>',
				'<a href="?nmr_nag_ignore=0">' );
				echo '</p>
				</div>';

			}

		}
		
	}


	/**
	* Allow the notice to be dismissable
	* @since 1.6
	*/
	function nag_ignore() {
		global $current_user;
		$user_id = $current_user->ID;
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset($_GET['nmr_nag_ignore']) && '0' == $_GET['nmr_nag_ignore'] ) {
			add_user_meta( $user_id, 'nmr_ignore_notice', 'true', true );
		}
	}

	/**
	* Delete the transient when a plugin is activated or deactivated
	* @since 1.5
	*/
	function delete_transient() {
		delete_transient( 'nav_menu_roles_conflicts' );
	}


	/**
	* Override the Admin Menu Walker
	* @since 1.0
	*/
	function edit_nav_menu_walker( $walker ) {
		return 'Walker_Nav_Menu_Edit_Roles';
	}


	/**
	* Add fields to hook added in Walker
	* This will allow us to play nicely with any other plugin that is adding the same hook
	* @params obj $item - the menu item 
	* @params array $args 
	* @since 1.6.0
	*/
	function custom_fields( $item_id, $item, $depth, $args ) {
		global $wp_roles;

		/**
		* Pass the menu item to the filter function.
		* This change is suggested as it allows the use of information from the menu item (and
		* by extension the target object) to further customize what filters appear during menu
		* construction.
		*/
		$display_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names, $item );


		/**
		* If no roles are being used, don't display the role selection radio buttons at all.
		* Unless something deliberately removes the WordPress roles from this list, nothing will
		* be functionally altered to the end user.
		* This change is suggested for the benefit of users constructing granular admin permissions
		* using extensive custom roles as it is an effective means of stopping admins with partial
		* permissions to the menu from accidentally removing all restrictions from a menu item to
		* which they do not have access.
		*/
		if( ! $display_roles ) return;

		/* Get the roles saved for the post. */
		$roles = get_post_meta( $item->ID, '_nav_menu_role', true );

		// by default nothing is checked (will match "everyone" radio)
		$logged_in_out = '';

		// specific roles are saved as an array, so "in" or an array equals "in" is checked
		if( is_array( $roles ) || $roles == 'in' ){
			$logged_in_out = 'in';
		} else if ( $roles == 'out' ){
			$logged_in_out = 'out';
		}

		// the specific roles to check
		$checked_roles = is_array( $roles ) ? $roles : false;

		?>

		<input type="hidden" name="nav-menu-role-nonce" value="<?php echo wp_create_nonce( 'nav-menu-nonce-name' ); ?>" />

		<div class="field-nav_menu_role nav_menu_logged_in_out_field description-wide" style="margin: 5px 0;">
		    <span class="description"><?php _e( "Display Mode", 'nav-menu-roles' ); ?></span>
		    <br />

		    <input type="hidden" class="nav-menu-id" value="<?php echo $item->ID ;?>" />

		    <div class="logged-input-holder" style="float: left; width: 35%;">
		        <input type="radio" class="nav-menu-logged-in-out" name="nav-menu-logged-in-out[<?php echo $item->ID ;?>]" id="nav_menu_logged_in-for-<?php echo $item->ID ;?>" <?php checked( 'in', $logged_in_out ); ?> value="in" />
		        <label for="nav_menu_logged_in-for-<?php echo $item->ID ;?>">
		            <?php _e( 'Logged In Users', 'nav-menu-roles'); ?>
		        </label>
		    </div>

		    <div class="logged-input-holder" style="float: left; width: 35%;">
		        <input type="radio" class="nav-menu-logged-in-out" name="nav-menu-logged-in-out[<?php echo $item->ID ;?>]" id="nav_menu_logged_out-for-<?php echo $item->ID ;?>" <?php checked( 'out', $logged_in_out ); ?> value="out" />
		        <label for="nav_menu_logged_out-for-<?php echo $item->ID ;?>">
		            <?php _e( 'Logged Out Users', 'nav-menu-roles'); ?>
		        </label>
		    </div>

		    <div class="logged-input-holder" style="float: left; width: 30%;">
		        <input type="radio" class="nav-menu-logged-in-out" name="nav-menu-logged-in-out[<?php echo $item->ID ;?>]" id="nav_menu_by_role-for-<?php echo $item->ID ;?>" <?php checked( '', $logged_in_out ); ?> value="" />
		        <label for="nav_menu_by_role-for-<?php echo $item->ID ;?>">
		            <?php _e( 'Everyone', 'nav-menu-roles'); ?>
		        </label>
		    </div>

		</div>

		<div class="field-nav_menu_role nav_menu_role_field description-wide" style="margin: 5px 0;">
		    <span class="description"><?php _e( "Limit logged in users to specific roles", 'nav-menu-roles' ); ?></span>
		    <br />

		    <?php

		    /* Loop through each of the available roles. */
		    foreach ( $display_roles as $role => $name ) {

		        /* If the role has been selected, make sure it's checked. */
		        $checked = checked( true, ( is_array( $checked_roles ) && in_array( $role, $checked_roles ) ), false );
		        
		        ?>

		        <div class="role-input-holder" style="float: left; width: 33.3%; margin: 2px 0;">
		        <input type="checkbox" name="nav-menu-role[<?php echo $item->ID ;?>][<?php echo $role; ?>]" id="nav_menu_role-<?php echo $role; ?>-for-<?php echo $item->ID ;?>" <?php echo $checked; ?> value="<?php echo $role; ?>" />
		        <label for="nav_menu_role-<?php echo $role; ?>-for-<?php echo $item->ID ;?>">
		        <?php echo esc_html( $name ); ?>
		        </label>
		        </div>

		<?php } ?>

		</div>

		<?php 
	}


	/**
	* Save the roles as menu item meta
	* @return null
	* @since 1.4
	* 
	*/
	function enqueue_scripts( $hook ){
		if ( $hook == 'nav-menus.php' ){
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'nav-menu-roles', plugins_url( 'js/nav-menu-roles' . $suffix . '.js' , __FILE__ ), array( 'jquery' ), $this->version, true );
		}
	}

	/**
	* Save the roles as menu item meta
	* @return string
	* @since 1.0
	*/
	function nav_update( $menu_id, $menu_item_db_id ) {
		global $wp_roles;

		$allowed_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names );

		// verify this came from our screen and with proper authorization.
		if ( ! isset( $_POST['nav-menu-role-nonce'] ) || ! wp_verify_nonce( $_POST['nav-menu-role-nonce'], 'nav-menu-nonce-name' ) )
			return;

		$saved_data = false;

		if ( isset( $_POST['nav-menu-logged-in-out'][$menu_item_db_id]  )  && $_POST['nav-menu-logged-in-out'][$menu_item_db_id] == 'in' && ! empty ( $_POST['nav-menu-role'][$menu_item_db_id] ) ) {
			$custom_roles = array();
			// only save allowed roles
			foreach( $_POST['nav-menu-role'][$menu_item_db_id] as $role ) {
				if ( array_key_exists ( $role, $allowed_roles ) ) $custom_roles[] = $role;
			}
			if ( ! empty ( $custom_roles ) ) $saved_data = $custom_roles;
		} else if ( isset( $_POST['nav-menu-logged-in-out'][$menu_item_db_id]  )  && in_array( $_POST['nav-menu-logged-in-out'][$menu_item_db_id], array( 'in', 'out' ) ) ) {
			$saved_data = $_POST['nav-menu-logged-in-out'][$menu_item_db_id];
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

		$hide_children_of = array();

		// Iterate over the items to search and destroy
		foreach ( $items as $key => $item ) {

			$visible = true;

			// hide any item that is the child of a hidden item
			if( in_array( $item->menu_item_parent, $hide_children_of ) ){
				$visible = false;
				$hide_children_of[] = $item->ID; // for nested menus
			}

			// check any item that has NMR roles set
			if( $visible && isset( $item->roles ) ) {

				// check all logged in, all logged out, or role
				switch( $item->roles ) {
					case 'in' :
					$visible = is_user_logged_in() ? true : false;
						break;
					case 'out' :
					$visible = ! is_user_logged_in() ? true : false;
						break;
					default:
						$visible = false;
						if ( is_array( $item->roles ) && ! empty( $item->roles ) ) {
							foreach ( $item->roles as $role ) {
								if ( current_user_can( $role ) ) 
									$visible = true;
							}
						}

						break;
				}

			}

			// add filter to work with plugins that don't use traditional roles
			$visible = apply_filters( 'nav_menu_roles_item_visibility', $visible, $item );

			// unset non-visible item
			if ( ! $visible ) {
				$hide_children_of[] = $item->ID; // store ID of item 
				unset( $items[$key] ) ;
			}

		}

		return $items;
}

} // end class

endif; // class_exists check


/**
* Launch the whole plugin
 * Returns the main instance of Nav Menu Roles to prevent the need to use globals.
 *
 * @since  1.5
 * @return Nav_Menu_Roles
*/
function Nav_Menu_Roles() {
	return Nav_Menu_Roles::instance();
}

// Global for backwards compatibility.
$GLOBALS['Nav_Menu_Roles'] = Nav_Menu_Roles();