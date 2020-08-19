<?php
/**
 * Nav Menu Roles - Customizer
 *
 * @since 2.0.0
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
add_action( 'customize_register', __NAMESPACE__ . '\customizer_preview', 1000 );

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

	if ( ! $display_roles ) {
		return;
	}

	?>
	<fieldset class="nav_menu_role_authentication">
		<legend class="customize-control-title"><?php _e( 'Display Mode', 'nav-menu-roles' ); ?></legend>

		<label for="edit-menu-item-role_logged_in-{{ data.menu_item_id }}">
			<input type="radio" id="edit-menu-item-role_logged_in-{{ data.menu_item_id }}" value="in" name="menu-item-role-{{ data.menu_item_id }}" />
			<?php _e( 'Logged In Users', 'nav-menu-roles' ); ?><br/>
		</label>
		<label for="edit-menu-item-role_logged_out-{{ data.menu_item_id }}">
			<input type="radio" id="edit-menu-item-role_logged_out-{{ data.menu_item_id }}" value="out" name="menu-item-role-{{ data.menu_item_id }}" />
			<?php _e( 'Logged Out Users', 'nav-menu-roles' ); ?><br/>
		</label>
		<label for="edit-menu-item-role_everyone-{{ data.menu_item_id }}">
			<input type="radio" id="edit-menu-item-role_everyone-{{ data.menu_item_id }}" value="" name="menu-item-role-{{ data.menu_item_id }}" />
			<?php _e( 'Everyone', 'nav-menu-roles' ); ?><br/>
		</label>
	</fieldset>

	<fieldset class="nav_menu_roles">
		<legend class="customize-control-title"><?php _e( 'Restrict menu item to minimum role', 'nav-menu-roles' ); ?></legend>

		<?php foreach ( $display_roles as $role => $name ) : ?>
			<label for="edit-menu-item-role_<?php echo $role; ?>-{{ data.menu_item_id }}">
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
	wp_enqueue_script(
		'customize-nav-menu-roles',
		plugin_dir_url( __DIR__ ) . '/js/nav-menu-roles-customize-controls.js',
		[ 'customize-nav-menus' ],
		\Nav_Menu_Roles::VERSION,
		true
	);
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
	$roles = get_roles_post_data( $setting );
	if ( null === $roles ) {
		return;
	}

	$roles = Nav_Menu_Roles()->sanitize_meta( $roles );

	add_filter(
		'get_post_metadata',
		static function ( $value, $object_id, $meta_key ) use ( $setting, $roles ) {
			if ( $object_id === $setting->post_id && '_nav_menu_role' === $meta_key ) {
				return [ $roles ];
			}
			return $value;
		},
		10,
		3
	);
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
