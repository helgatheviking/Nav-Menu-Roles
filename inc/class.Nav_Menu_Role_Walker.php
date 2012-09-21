<?php

/**
 * Walker that checks the role of the current user for each item
 * Replacement for the native Walker, but uses the parent's display methods
 */


class Nav_Menu_Role_Walker extends Walker_Nav_Menu {

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) { 

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
			if ( $visible ) parent::start_el( $output, $item, $depth, $args );
	    } else {
	    	parent::start_el( $output, $item, $depth, $args );
	    }
	}

}