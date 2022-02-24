<?php
/**
 * Nav Menu Roles - Customizer
 *
 * @since 2.0.0
 * @package Nav Menu Roles\Includes
 * @link  https://wordpress.stackexchange.com/questions/372493/add-settings-to-menu-items-in-the-customizer
 *
 */

namespace Customize_Nav_Menu_Roles;

use WP_Customize_Manager;
use WP_Customize_Nav_Menu_Item_Setting;

/**
 * Hooks.
 */

// Add new fields in the Customizer.
add_action( 'wp_nav_menu_item_custom_fields_customize_template', __NAMESPACE__ . '\customizer_custom_fields' );

// Add some JS.
add_action( 'customize_controls_enqueue_scripts', __NAMESPACE__ . '\customizer_scripts' );

// Workaround for previewing changes.
if ( \Nav_Menu_Roles::is_wp_gte( '4.9' ) ) {
	add_action( 'customize_register', __NAMESPACE__ . '\customizer_preview', 1000 );
}

// Workaround for saving changes.
add_action( 'customize_save_after', __NAMESPACE__ . '\customizer_save' );


/**
 * Display the fields in the Customizer.
 */
function customizer_custom_fields() {

	global $wp_roles;

	/**
	 * Pass the menu item to the filter function.
	 * This change is suggested as it allows the use of information from the menu item (and
	 * by extension the target object) to further customize what filters appear during menu
	 * construction.
	 */
	$display_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names );

	// Alpha sort roles by label.
	asort( $display_roles );

	if ( ! $display_roles ) {
		return;
	}

	?>
	<fieldset class="nav_menu_role_display_mode">
			<legend class="customize-control-title"><?php esc_html_e( 'Display Mode', 'nav-menu-roles' ); ?></legend>

			<label for="edit-menu-item-role_display_mode_show-{{ data.menu_item_id }}">
				<input type="radio" class="nav-menu-display-mode" name="nav-menu-display-mode[{{ data.menu_item_id }}]" id="edit-menu-item-role_display_mode_show-{{ data.menu_item_id }}" value="show" />
				<?php esc_html_e( 'Show', 'nav-menu-roles' ); ?>   
			</label>
		
			<label for="edit-menu-item-role_display_mode_hide-{{ data.menu_item_id }}">
				<input type="radio" class="nav-menu-display-mode" name="nav-menu-display-mode[{{ data.menu_item_id }}]" id="edit-menu-item-role_display_mode_hide-{{ data.menu_item_id }}" value="hide" />
				<?php esc_html_e( 'Hide', 'nav-menu-roles' ); ?>	       
			</label>

	</fieldset>

	<fieldset class="nav_menu_role_authentication">
		<legend class="customize-control-title"><?php esc_html_e( 'Target Audience', 'nav-menu-roles' ); ?></legend>

		<label for="edit-menu-item-role_logged_in-{{ data.menu_item_id }}">
			<input type="radio" id="edit-menu-item-role_logged_in-{{ data.menu_item_id }}" value="in" name="menu-item-role-{{ data.menu_item_id }}" />
			<?php esc_html_e( 'Logged In Users', 'nav-menu-roles' ); ?><br/>
		</label>
		<label for="edit-menu-item-role_logged_out-{{ data.menu_item_id }}">
			<input type="radio" id="edit-menu-item-role_logged_out-{{ data.menu_item_id }}" value="out" name="menu-item-role-{{ data.menu_item_id }}" />
			<?php esc_html_e( 'Logged Out Users', 'nav-menu-roles' ); ?><br/>
		</label>
		<label for="edit-menu-item-role_everyone-{{ data.menu_item_id }}">
			<input type="radio" id="edit-menu-item-role_everyone-{{ data.menu_item_id }}" value="" name="menu-item-role-{{ data.menu_item_id }}" />
			<?php esc_html_e( 'Everyone', 'nav-menu-roles' ); ?><br/>
		</label>
	</fieldset>

	<fieldset class="nav_menu_roles">
		<legend class="customize-control-title"><?php esc_html_e( 'Target Roles', 'nav-menu-roles' ); ?></legend>

		<?php foreach ( $display_roles as $role => $name ) : ?>
			<label for="edit-menu-item-role_<?php echo esc_attr( $role ); ?>-{{ data.menu_item_id }}">
				<input type="checkbox" id="edit-menu-item-role_<?php echo esc_attr( $role ); ?>-{{ data.menu_item_id }}" class="edit-menu-item-role" value="<?php echo esc_attr( $role ); ?>" />
				<?php echo esc_html( $name ); ?><br/>
			</label>
		<?php endforeach; ?>

	</fieldset>
	<?php
}

/**
 * Load the customizer scripts which extends nav menu item controls.
 */
function customizer_scripts() {
	$script_dependencies = include plugin_dir_path( __DIR__ ) . '/dist/customize-controls.asset.php';
	wp_enqueue_script(
		'customize-nav-menu-roles',
		plugins_url( 'dist/customize-controls.js', dirname( __FILE__ ) ),
		array_merge( array( 'customize-nav-menus' ), $script_dependencies['dependencies'] ),
		$script_dependencies['version'],
		true
	);
}

/**
 * Get posted value for a setting's display mode.
 *
 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
 *
 * @return array|string|null Roles value or null if no posted value present.
 */
function get_display_mode_post_data( WP_Customize_Nav_Menu_Item_Setting $setting ) {
	if ( ! $setting->post_value() ) {
		return null;
	}

	$unsanitized_post_value = $setting->manager->unsanitized_post_values()[ $setting->id ];
	return isset( $unsanitized_post_value['display_mode'] ) ? $unsanitized_post_value['display_mode'] : 'show';
}

/**
 * Get posted value for a setting's roles.
 *
 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
 *
 * @return array|string|null Roles value or null if no posted value present.
 */
function get_roles_post_data( WP_Customize_Nav_Menu_Item_Setting $setting ) {
	if ( ! $setting->post_value() ) {
		return null;
	}

	$unsanitized_post_value = $setting->manager->unsanitized_post_values()[ $setting->id ];
	return isset( $unsanitized_post_value['roles'] ) ? $unsanitized_post_value['roles'] : '';
}

/**
 * Preview changes to the nav menu item roles.
 *
 * Note the unimplemented to-do in the doc block for the setting's preview method.
 *
 * @see WP_Customize_Nav_Menu_Item_Setting::preview()
 *
 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
 */
function preview_nav_menu_setting_postmeta( WP_Customize_Nav_Menu_Item_Setting $setting ) {

	$mode = get_display_mode_post_data( $setting );

	if ( null !== $mode ) {

		$mode = Nav_Menu_Roles()->sanitize_meta_mode( $mode );

		add_filter(
			'get_post_metadata',
			static function ( $value, $object_id, $meta_key ) use ( $setting, $mode ) {
				if ( $object_id === $setting->post_id && '_nav_menu_role_display_mode' === $meta_key ) {
					return array( $mode );
				}
				return $value;
			},
			10,
			3
		);

	}

	$roles = get_roles_post_data( $setting );

	if ( null !== $roles ) {

		$roles = Nav_Menu_Roles()->sanitize_meta( $roles );

		add_filter(
			'get_post_metadata',
			static function ( $value, $object_id, $meta_key ) use ( $setting, $roles ) {
				if ( $object_id === $setting->post_id && '_nav_menu_role' === $meta_key ) {
					return array( $roles );
				}
				return $value;
			},
			10,
			3
		);

	}

}

/**
 * Save changes to the nav menu item roles.
 *
 * Note the unimplemented to-do in the doc block for the setting's preview method.
 *
 * @see WP_Customize_Nav_Menu_Item_Setting::update()
 *
 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
 */
function save_nav_menu_setting_postmeta( WP_Customize_Nav_Menu_Item_Setting $setting ) {
	$mode = get_display_mode_post_data( $setting );
	if ( null !== $mode ) {
		update_post_meta( $setting->post_id, '_nav_menu_role_display_mode', $mode );
	}

	$roles = get_roles_post_data( $setting );
	if ( null !== $roles ) {
		update_post_meta( $setting->post_id, '_nav_menu_role', $roles );
	}
}

// Set up previewing.
function customizer_preview( WP_Customize_Manager $wp_customize ) {
	if ( $wp_customize->settings_previewed() ) {
		foreach ( $wp_customize->settings() as $setting ) {
			if ( $setting instanceof WP_Customize_Nav_Menu_Item_Setting ) {
				preview_nav_menu_setting_postmeta( $setting );
			}
		}
	}
}

/**
 * Set up saving.
 */
function customizer_save( WP_Customize_Manager $wp_customize ) {
	foreach ( $wp_customize->settings() as $setting ) {
		if ( $setting instanceof WP_Customize_Nav_Menu_Item_Setting && $setting->check_capabilities() ) {
			save_nav_menu_setting_postmeta( $setting );
		}
	}
}
