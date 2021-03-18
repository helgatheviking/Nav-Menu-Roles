<?php
/**
 * Plugin Name: Nav Menu Roles
 * Plugin URI: http://www.kathyisawesome.com/449/nav-menu-roles/
 * Description: Hide custom menu items based on user roles.
 * Version: 2.0.1
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

/**
 * Nav Menu Roles class
 */
class Nav_Menu_Roles {

	/**
	 * @var Nav_Menu_Roles The single instance of the class
	 * @since 1.5
	 */
	protected static $_instance = null;

	/**
	* @constant string donate url
	* @since 1.9.1
	*/
	const DONATE_URL = 'https://www.paypal.com/fundraiser/charity/1451316';

	/**
	* @constant string version number
	* @since 1.7.0
	*/
	const VERSION = '1.10.2';

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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning this object is forbidden.', 'nav-menu-roles' ), '1.5' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.5
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'nav-menu-roles' ), '1.5' );
	}

	/**
	 * Nav_Menu_Roles Constructor.
	 * @access public
	 * @return Nav_Menu_Roles
	 * @since  1.0
	 */
	public function __construct() {

		require_once 'inc/customizer.php';

		// Admin functions.
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Load the textdomain.
		add_action( 'init', array( $this, 'load_text_domain' ) );

		// Register the meta key.
		add_action( 'init', array( $this, 'register_meta' ) );

		// Add FAQ and Donate link to plugin.
		add_filter( 'plugin_row_meta', array( $this, 'add_action_links' ), 10, 2 );

		// Maybe switch the admin walker.
		if ( ! self::is_wp_gte( '5.4' ) ) {
			add_filter( 'wp_edit_nav_menu_walker', array( $this, 'edit_nav_menu_walker' ) );
		}

		// Add new fields via hook.
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'custom_fields' ), 10, 4 );

		// Add some JS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Save the menu item meta.
		add_action( 'wp_update_nav_menu_item', array( $this, 'nav_update' ), 10, 2 );

		// Add meta to menu item.
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_item' ) );

		// Exclude items via filter instead of via custom Walker.
		if ( ! is_admin() ) {
			// Because WP_Customize_Nav_Menu_Item_Setting::filter_wp_get_nav_menu_items() runs at 10.
			add_filter( 'wp_get_nav_menu_items', array( $this, 'exclude_menu_items' ), 20 );
		}

		// Upgrade routine.
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );

	}

	/**
	 * Include the custom admin walker
	 *
	 * @access public
	 * @return void
	 */
	public function admin_init() {

		// Register Importer.
		$this->register_importer();

	}


	/**
	 * Register the Importer
	 * the regular Importer skips post meta for the menu items
	 *
	 * @access private
	 * @return void
	 */
	public function register_importer() {
		// Register the new importer.
		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {

			include_once plugin_dir_path( __FILE__ ) . 'inc/class-nav-menu-roles-import.php';
			// Register the custom importer we've created.
			$roles_import = new Nav_Menu_Roles_Import();

			register_importer( 'nav_menu_roles', __( 'Nav Menu Roles', 'nav-menu-roles' ), __( 'Import <strong>nav menu roles</strong> and other menu item meta skipped by the default importer', 'nav-menu-roles' ), array( $roles_import, 'dispatch' ) );

		}

	}

	/**
	 * Make Plugin Translation-ready
	 *
	 * @since 1.0
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'nav-menu-roles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Register the meta keys for nav menus.
	 *
	 * @since 2.0
	 */
	public function register_meta() {
		register_meta(
			'post',
			'_nav_menu_role',
			array(
				'object_subtype'    => 'nav_menu_item',
				'type'              => 'mixed',
				'sanitize_callback' => array( $this, 'sanitize_meta' ),
			)
		);
	}

	/**
	 * Sanitize the meta.
	 *
	 * @since 2.0.0
	 *
	 * @param  mixed  $meta_value The meta value.
	 * @return mixed              The meta value.
	 *
	 */
	public function sanitize_meta( $meta_value ) {
		global $wp_roles;

		$clean = '';

		if ( is_array( $meta_value ) ) {

			$clean = array();

			/**
			* Pass the menu item to the filter function.
			* This change is suggested as it allows the use of information from the menu item (and
			* by extension the target object) to further customize what filters appear during menu
			* construction.
			*/
			$allowed_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names );

			// Only save allowed roles.
			$clean = array_intersect( $meta_value, array_keys( $allowed_roles ) );

		} elseif ( in_array( $meta_value, array( 'in', 'out' ) ) ) {
			$clean = $meta_value;
		}

		return $clean;
	}

	/**
	 * Display a Notice if plugin conflicts with another
	 *
	 * @since 1.5
	 * @deprecated will removed in 2.0
	 */
	public function admin_notice() {
		_deprecated_function( __METHOD__, '1.7.8' );
	}


	/**
	 * Allow the notice to be dismissable
	 *
	 * @since 1.6
	 * @deprecated will removed in 2.0
	 */
	public function nag_ignore() {
		_deprecated_function( __METHOD__, '1.7.8' );
	}

	/**
	 * Delete the transient when a plugin is activated or deactivated
	 *
	 * @since 1.5
	 * @deprecated will removed in 2.0
	 */
	public function delete_transient() {
		_deprecated_function( __METHOD__, '1.7.8' );
		delete_transient( 'nav_menu_roles_conflicts' );
	}


	/**
	 * Add docu link
	 *
	 * @since 1.7.3
	 * @param array $plugin_meta
	 * @param string $plugin_file
	 */
	public function add_action_links( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( __FILE__ ) === $plugin_file ) {
			$plugin_meta[] = sprintf( '<a class="dashicons-before dashicons-welcome-learn-more" href="https://wordpress.org/plugins/nav-menu-roles/faq/#conflict">%s</a>', __( 'FAQ', 'nav-menu-roles' ) );
			$plugin_meta[] = '<a class="dashicons-before dashicons-admin-generic" href="' . self::DONATE_URL . '" target="_blank">' . __( 'Donate', 'nav-menu-roles' ) . '</a>';
		}
		return $plugin_meta;
	}


	/**
	 * Override the Admin Menu Walker
	 *
	 * @since 1.0
	 */
	public function edit_nav_menu_walker( $walker ) {
		if ( ! class_exists( 'Walker_Nav_Menu_Edit_Roles' ) ) {

			if ( self::is_wp_gte( '4.7' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'inc/class-walker-nav-menu-edit-roles-4.7.php';
			} elseif ( self::is_wp_gte( '4.5' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'inc/class-walker-nav-menu-edit-roles-4.5.php';
			} else {
				require_once plugin_dir_path( __FILE__ ) . 'inc/class-walker-nav-menu-edit-roles.php';
			}
		}
		return 'Walker_Nav_Menu_Edit_Roles';
	}


	/**
	 * Add fields to hook added in Walker
	 * This will allow us to play nicely with any other plugin that is adding the same hook
	 * @params obj $item - the menu item
	 * @params array $args
	 * @since 1.6.0
	 */
	public function custom_fields( $item_id, $item, $depth, $args ) {
		global $wp_roles;

		/**
		* Pass the menu item to the filter function.
		* This change is suggested as it allows the use of information from the menu item (and
		* by extension the target object) to further customize what filters appear during menu
		* construction.
		*/
		$display_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names, $item );

		// Alpha sort roles by label.
		asort( $wp_roles->role_names );

		/**
		* If no roles are being used, don't display the role selection radio buttons at all.
		* Unless something deliberately removes the WordPress roles from this list, nothing will
		* be functionally altered to the end user.
		* This change is suggested for the benefit of users constructing granular admin permissions
		* using extensive custom roles as it is an effective means of stopping admins with partial
		* permissions to the menu from accidentally removing all restrictions from a menu item to
		* which they do not have access.
		*/
		if ( ! $display_roles ) {
			return;
		}

		/* Get the roles saved for the post. */
		$roles = get_post_meta( $item->ID, '_nav_menu_role', true );

		// By default nothing is checked (will match "everyone" radio).
		$logged_in_out = '';

		// Specific roles are saved as an array, so "in" or an array equals "in" is checked.
		if ( is_array( $roles ) || 'in' === $roles ) {
			$logged_in_out = 'in';
		} elseif ( 'out' === $roles ) {
			$logged_in_out = 'out';
		}

		// The specific roles to check.
		$checked_roles = is_array( $roles ) ? $roles : false;

		// Whether to display the role checkboxes.
		$hidden = 'in' === $logged_in_out ? '' : 'display: none;';

		$float = is_rtl() ? 'float:"right";' : 'float:"left";';

		?>

		<input type="hidden" name="nav-menu-role-nonce" value="<?php echo esc_attr( wp_create_nonce( 'nav-menu-nonce-name' ) ); ?>" />

		<fieldset class="field-nav_menu_role nav_menu_logged_in_out_field description-wide" style="margin: 5px 0;">
			<legend class="description"><?php esc_html_e( 'Display Mode', 'nav-menu-roles' ); ?></legend>

			<input type="hidden" class="nav-menu-id" value="<?php echo esc_attr( $item->ID ); ?>" />

			<label for="nav_menu_logged_in-for-<?php echo esc_attr( $item->ID ); ?>" style="<?php echo esc_attr( $float ); ?> width: 35%;">
				<input type="radio" class="nav-menu-logged-in-out" name="nav-menu-logged-in-out[<?php echo esc_attr( $item->ID ); ?>]" id="nav_menu_logged_in-for-<?php echo esc_attr( $item->ID ); ?>" <?php checked( 'in', $logged_in_out ); ?> value="in" />
				<?php esc_html_e( 'Logged In Users', 'nav-menu-roles' ); ?>   
			</label>
		
			<label for="nav_menu_logged_out-for-<?php echo esc_attr( $item->ID ); ?>" style="<?php echo esc_attr( $float ); ?> width: 35%;">
				<input type="radio" class="nav-menu-logged-in-out" name="nav-menu-logged-in-out[<?php echo esc_attr( $item->ID ); ?>]" id="nav_menu_logged_out-for-<?php echo esc_attr( $item->ID ); ?>" <?php checked( 'out', $logged_in_out ); ?> value="out" />
				<?php esc_html_e( 'Logged Out Users', 'nav-menu-roles' ); ?>	       
			</label>

			<label for="nav_menu_by_role-for-<?php echo esc_attr( $item->ID ); ?>" style="<?php echo esc_attr( $float ); ?> width: 30%;">
				<input type="radio" class="nav-menu-logged-in-out" name="nav-menu-logged-in-out[<?php echo esc_attr( $item->ID ); ?>]" id="nav_menu_by_role-for-<?php echo esc_attr( $item->ID ); ?>" <?php checked( '', $logged_in_out ); ?> value="" />
				<?php esc_html_e( 'Everyone', 'nav-menu-roles' ); ?>
			</label>

		</fieldset>

		<fieldset class="field-nav_menu_role nav_menu_role_field description-wide" style="margin: 5px 0; <?php echo esc_attr( $hidden ); ?>">
			<legend class="description"><?php esc_html_e( 'Restrict menu item to a minimum role', 'nav-menu-roles' ); ?></legend>
			<br />

			<?php

			$i = 1;

			/* Loop through each of the available roles. */
			foreach ( $display_roles as $role => $name ) {

				/* If the role has been selected, make sure it's checked. */
				$checked = checked( true, ( is_array( $checked_roles ) && in_array( $role, $checked_roles ) ), false );
				?>

				<label for="nav_menu_role-<?php echo esc_attr( $role ); ?>-for-<?php echo esc_attr( $item->ID ); ?>" style="display: block; margin: 2px 0;">
					<input type="checkbox" name="nav-menu-role[<?php echo esc_attr( $item->ID ); ?>][<?php echo esc_attr( $i ); ?>]" id="nav_menu_role-<?php echo esc_attr( $role ); ?>-for-<?php echo esc_attr( $item->ID ); ?>" <?php echo esc_attr( $checked ); ?> value="<?php echo esc_attr( $role ); ?>" />
					<?php echo esc_html( $name ); ?>
					<?php $i++; ?>
				</label>

		<?php } ?>

		</fieldset>

		<?php
	}


	/**
	 * Load the scripts on the menu page.
	 *
	 * @since 1.4
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'nav-menus.php' === $hook ) {
			wp_enqueue_script( 'nav-menu-roles', plugins_url( 'dist/js/roles.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
		}
	}

	/**
	 * Save the roles as menu item meta
	 *
	 * @since 1.0
	 * @return string
	 */
	public function nav_update( $menu_id, $menu_item_db_id ) {

		// Verify this came from our screen and with proper authorization.
		if ( ! isset( $_POST['nav-menu-role-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nav-menu-role-nonce'] ), 'nav-menu-nonce-name' ) ) {
			return;
		}

		if ( isset( $_POST['nav-menu-logged-in-out'][ $menu_item_db_id ] ) ) {

			if ( 'in' === $_POST['nav-menu-logged-in-out'][ $menu_item_db_id ] && ! empty( $_POST['nav-menu-role'][ $menu_item_db_id ] ) ) {
				$meta = wp_unslash( $_POST['nav-menu-role'][ $menu_item_db_id ] );
			} else {
				$meta = wp_unslash( $_POST['nav-menu-logged-in-out'][ $menu_item_db_id ] );
			}

			update_post_meta( $menu_item_db_id, '_nav_menu_role', $meta ); // Sanitization handled by $this->sanitize_meta().

		} else {
			delete_post_meta( $menu_item_db_id, '_nav_menu_role' );
		}
	}

	/**
	 * Adds value of new field to $item object
	 * is be passed to Walker_Nav_Menu_Edit_Custom
	 *
	 * @since 1.0
	 */
	public function setup_nav_item( $menu_item ) {

		if ( is_object( $menu_item ) && isset( $menu_item->ID ) ) {

			$roles = get_post_meta( $menu_item->ID, '_nav_menu_role', true );

			if ( ! empty( $roles ) ) {
				$menu_item->roles = $roles;

				// Add the NMR roles as CSS info.
				$new_classes = array();

				switch ( $roles ) {
					case 'in':
						$new_classes[] = 'nmr-logged-in';
						break;
					case 'out':
						$new_classes[] = 'nmr-logged-out';
						break;
					default:
						if ( is_array( $menu_item->roles ) && ! empty( $menu_item->roles ) ) {
							foreach ( $menu_item->roles as $role ) {
								$new_classes[] = 'nmr-' . $role;
							}
						}
						break;
				}

				// Only apply classes on front-end.
				if ( ! is_admin() ) {
					$menu_item->classes = array_unique( array_merge( (array) $menu_item->classes, (array) $new_classes ) );
				}
			}
		}
		return $menu_item;
	}

	/**
	 * Exclude menu items via wp_get_nav_menu_items filter
	 * this fixes plugin's incompatibility with theme's that use their own custom Walker
	 * Thanks to Evan Stein @vanpop http://vanpop.com/
	 *
	 * @since 1.2
	 *
	 * @param  WP_Post[] array of Nav Menu Post objects
	 *
	 * Multisite compatibility added in 1.9.0
	 * by @open-dsi https://www.open-dsi.fr/ with props to @fiech
	 */
	public function exclude_menu_items( $items ) {

		$hide_children_of = array();

		if ( ! empty( $items ) ) {

			// Iterate over the items to search and destroy.
			foreach ( $items as $key => $item ) {

				$visible = true;

				// Hide any item that is the child of a hidden item.
				if ( isset( $item->menu_item_parent ) && in_array( $item->menu_item_parent, $hide_children_of ) ) {
					$visible = false;
				}

				// Check any item that has NMR roles set.
				if ( $visible && isset( $item->roles ) ) {

					// Check all logged in, all logged out, or role.
					switch ( $item->roles ) {
						case 'in':
							/**
							 * Multisite compatibility.
							 *
							 * For the logged in condition to work,
							 * the user has to be a logged in member of the current blog
							 * or be a logged in super user.
							 */
							$visible = is_user_member_of_blog() || is_super_admin() ? true : false;
							break;
						case 'out':
							/**
							 * Multisite compatibility.
							 *
							 * For the logged out condition to work,
							 * the user has to be either logged out
							 * or not be a member of the current blog.
							 * But they also may not be a super admin,
							 * because logged in super admins should see the internal stuff, not the external.
							 */
							$visible = ! is_user_member_of_blog() && ! is_super_admin() ? true : false;
							break;
						default:
							$visible = false;
							if ( is_array( $item->roles ) && ! empty( $item->roles ) ) {
								foreach ( $item->roles as $role ) {
									if ( current_user_can( $role ) ) {
										$visible = true;
										break;
									}
								}
							}

							break;
					}
				}

				/*
				 * Filter: nav_menu_roles_item_visibility
				 * Add filter to work with plugins that don't use traditional roles
				 *
				 * @param bool $visible
				 * @param object $item
				 */
				$visible = apply_filters( 'nav_menu_roles_item_visibility', $visible, $item );

				// Unset non-visible item.
				if ( ! $visible ) {
					if ( isset( $item->ID ) ) {
						$hide_children_of[] = $item->ID; // Store ID of item to hide it's children.
					}
					unset( $items[ $key ] );
				}
			}
		}

		return $items;
	}


	/**
	 * Maybe upgrade
	 *
	 * @access public
	 * @return void
	 */
	public function maybe_upgrade() {
		$db_version = get_option( 'nav_menu_roles_db_version', false );

		// 1.7.7 upgrade: changed the debug notice so the old transient is invalid.
		if ( false === $db_version || version_compare( '1.7.7', $db_version, '<' ) ) {
			update_option( 'nav_menu_roles_db_version', self::VERSION );
		}
	}

	/**
	 * Test WordPress version
	 *
	 * @access public
	 * @param  string $version - A WordPress version to compare against current version.
	 * @return boolean
	 */
	public static function is_wp_gte( $version = '5.4' ) {
		global $wp_version;
		return version_compare( strtolower( $wp_version ), strtolower( $version ), '>=' );
	}

} // end class

/**
 * Launch the whole plugin
 * Returns the main instance of Nav Menu Roles to prevent the need to use globals.
 *
 * @since  1.5
 * @return Nav_Menu_Roles
 */
function nav_menu_roles() {
	return Nav_Menu_Roles::instance();
}

add_action( 'plugins_loaded', 'nav_menu_roles' );

// Global for backwards compatibility.
$GLOBALS['Nav_Menu_Roles'] = Nav_Menu_Roles();
