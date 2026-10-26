<?php
/**
 * Nav Menu Roles - Block Editor Integration
 *
 * Adds Nav Menu Roles settings to Navigation block links as block attributes
 * and applies role-based visibility when the blocks are rendered.
 *
 * @since 3.0.0
 * @package Nav Menu Roles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nav Menu Roles Blocks
 */
class Nav_Menu_Roles_Blocks {

	/**
	 * Blocks that support Nav Menu Roles settings.
	 *
	 * @var string[]
	 */
	const BLOCKS = array( 'core/navigation-link', 'core/navigation-submenu' );

	/**
	 * Hook in methods.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Enqueue block editor assets.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'block_editor_assets' ) );

		// Declare additional attributes for the navigation link blocks.
		add_filter( 'block_type_metadata', array( __CLASS__, 'add_block_attributes' ) );

		// Apply role-based visibility when rendering.
		foreach ( self::BLOCKS as $block_name ) {
			add_filter( 'render_block_' . $block_name, array( __CLASS__, 'render_block' ), 10, 2 );
		}
	}

	/**
	 * Load the scripts which extends navigation link controls.
	 *
	 * @since 3.0.0
	 */
	public static function block_editor_assets() {
		global $wp_roles;

		$asset_file = nav_menu_roles()->plugin_path( 'assets/js/blocks/settings.asset.php' );

		// Assets have not been built.
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$script_dependencies = include $asset_file;

		wp_enqueue_script(
			'nav-menu-roles-block-editor',
			nav_menu_roles()->plugin_url( 'assets/js/blocks/settings.js' ),
			$script_dependencies['dependencies'],
			$script_dependencies['version'],
			true
		);

		wp_set_script_translations( 'nav-menu-roles-block-editor', 'nav-menu-roles', nav_menu_roles()->plugin_path( 'languages' ) );

		// Pass role data to the script via global window variable.
		$allowed_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names );

		wp_localize_script(
			'nav-menu-roles-block-editor',
			'navMenuRolesData',
			array(
				'roles' => $allowed_roles,
			)
		);
	}

	/**
	 * Add custom attributes to the navigation link blocks.
	 *
	 * @since 3.0.0
	 *
	 * @param array $metadata Block metadata.
	 * @return array Modified block metadata.
	 */
	public static function add_block_attributes( $metadata ) {
		if ( ! isset( $metadata['name'] ) || ! in_array( $metadata['name'], self::BLOCKS, true ) ) {
			return $metadata;
		}

		$metadata['attributes']['nmrDisplayMode'] = array(
			'type'    => 'string',
			'default' => 'show',
		);
		$metadata['attributes']['nmrAudience']    = array(
			'type'    => 'string',
			'default' => '',
		);
		$metadata['attributes']['nmrRoles']       = array(
			'type'    => 'array',
			'default' => array(),
			'items'   => array(
				'type' => 'string',
			),
		);

		return $metadata;
	}

	/**
	 * Convert block attributes to the roles format used by classic menu items.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attrs Block attributes.
	 * @return string|array 'in', 'out', an array of role keys, or empty string for everyone.
	 */
	public static function get_roles_from_attributes( $attrs ) {
		$audience = isset( $attrs['nmrAudience'] ) ? $attrs['nmrAudience'] : '';

		if ( 'out' === $audience ) {
			return 'out';
		}

		if ( 'in' === $audience ) {
			$roles = isset( $attrs['nmrRoles'] ) && is_array( $attrs['nmrRoles'] ) ? nav_menu_roles()->sanitize_meta( $attrs['nmrRoles'] ) : array();
			return ! empty( $roles ) ? array_values( $roles ) : 'in';
		}

		return '';
	}

	/**
	 * Render the navigation link block with role-based visibility.
	 *
	 * Hiding a submenu block also hides all of its inner links.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         Parsed block data.
	 * @return string Modified block content.
	 */
	public static function render_block( $block_content, $block ) {
		$attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

		// Skip blocks that have never had Nav Menu Roles settings.
		if ( ! isset( $attrs['nmrDisplayMode'] ) && ! isset( $attrs['nmrAudience'] ) ) {
			return $block_content;
		}

		$roles        = self::get_roles_from_attributes( $attrs );
		$display_mode = isset( $attrs['nmrDisplayMode'] ) && 'hide' === $attrs['nmrDisplayMode'] ? 'hide' : 'show';

		$visible = nav_menu_roles()->is_visible_for_roles( $roles );

		// Invert visibility if display mode is "hide".
		if ( 'hide' === $display_mode ) {
			$visible = ! $visible;
		}

		// Mimic the menu item properties for the shared visibility filter.
		$item = (object) array(
			'ID'           => 0,
			'title'        => isset( $attrs['label'] ) ? $attrs['label'] : '',
			'url'          => isset( $attrs['url'] ) ? $attrs['url'] : '',
			'object_id'    => isset( $attrs['id'] ) ? $attrs['id'] : 0,
			'display_mode' => $display_mode,
			'block'        => $block,
		);

		if ( ! empty( $roles ) ) {
			$item->roles = $roles;
		}

		/** This filter is documented in inc/class-nav-menu-roles.php */
		$visible = apply_filters( 'nav_menu_roles_item_visibility', $visible, $item );

		if ( ! $visible ) {
			return '';
		}

		return self::add_role_classes( $block_content, $roles );
	}

	/**
	 * Add the NMR roles as CSS classes, matching classic menu items.
	 *
	 * @since 3.0.0
	 *
	 * @param string       $block_content The block content.
	 * @param string|array $roles         'in', 'out', or an array of role keys.
	 * @return string Modified block content.
	 */
	public static function add_role_classes( $block_content, $roles ) {
		if ( empty( $roles ) ) {
			return $block_content;
		}

		switch ( $roles ) {
			case 'in':
				$classes = array( 'nmr-logged-in' );
				break;
			case 'out':
				$classes = array( 'nmr-logged-out' );
				break;
			default:
				$classes = array_map(
					function ( $role ) {
						return 'nmr-' . $role;
					},
					(array) $roles
				);
				break;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );

		if ( $processor->next_tag( 'li' ) ) {
			foreach ( $classes as $class ) {
				$processor->add_class( sanitize_html_class( $class ) );
			}
			$block_content = $processor->get_updated_html();
		}

		return $block_content;
	}
}

// Initialize the plugin.
Nav_Menu_Roles_Blocks::init();
