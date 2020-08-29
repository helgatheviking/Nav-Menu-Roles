<?php
// phpcs:ignoreFile

/**
 * Custom Walker for Nav Menu Editor
 * Add wp_nav_menu_item_custom_fields hook
 * Hat tip to @kucrut and Menu Icons for the preg_replace() idea
 * means I no longer have to translate core strings
 *
 * @package Nav Menu Roles
 * @since 1.8.6
 * @uses Walker_Nav_Menu_Edit
 */
class Walker_Nav_Menu_Edit_Roles extends Walker_Nav_Menu_Edit {

	/**
	 * Start the element output.
	 *
	 * @see Walker_Nav_Menu::start_el()
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item   Menu item data object.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   Not used.
	 * @param int    $id     Not used.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$item_output = '';
		$output .= parent::start_el( $item_output, $item, $depth, $args, $id );
		$output .= preg_replace(
			// NOTE: Check this regex on major WP version updates!
			'/(?=<fieldset[^>]+class="[^"]*field-move)/',
			$this->get_custom_fields( $item, $depth, $args ),
			$item_output
		);
	}


	/**
	 * Get custom fields
	 *
	 * @access protected
	 * @since 0.1.0
	 * @uses do_action() Calls 'menu_item_custom_fields' hook
	 *
	 * @param object $item  Menu item data object.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args  Menu item args.
	 * @param int    $id    Nav menu ID.
	 *
	 * @return string Form fields
	 */
	protected function get_custom_fields( $item, $depth, $args = array(), $id = 0 ) {
		ob_start();
		$item_id = intval( $item->ID );
		/**
		 * Get menu item custom fields from plugins/themes
		 *
		 * @since 0.1.0
		 *
		 * @param int    $item_id post ID of menu
		 * @param object $item  Menu item data object.
		 * @param int    $depth  Depth of menu item. Used for padding.
		 * @param array  $args  Menu item args.
		 *
		 * @return string Custom fields
		 */
		do_action( 'wp_nav_menu_item_custom_fields', $item_id, $item, $depth, $args );
		return ob_get_clean();
	}
} // Walker_Nav_Menu_Edit
