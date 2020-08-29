<?php
/**
 * Nav Menu Roles Importer - import menu item meta
 *
 * Create HTML list of nav menu input items.
 * Copied from Walker_Nav_Menu_Edit class in core /wp-admin/includes/nav-menu.php
 *
 * @package Nav Menu Roles\Classes
 * @since 1.3
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
	return;
}

/** Display verbose errors */
if ( ! defined( 'IMPORT_DEBUG' ) ) {
	define( 'IMPORT_DEBUG', false );
}

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require $class_wp_importer;
	}
}

/**
 * Nav Menu Roles Importer class
 */
if ( class_exists( 'WP_Importer' ) && ! class_exists( 'Nav_Menu_Roles_Import' ) ) {
	class Nav_Menu_Roles_Import extends WP_Importer {

		var $max_wxr_version = 1.2; // max. supported WXR version

		var $id; // WXR attachment ID

		// information to import from WXR file
		var $version;
		var $posts    = array();
		var $base_url = '';


		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->import_page = 'woocommerce_tax_rate_csv';
		}

		/**
		 * Registered callback function for the WordPress Importer
		 *
		 * Manages the three separate stages of the WXR import process
		 */
		function dispatch() {
			$this->header();

			$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
			switch ( $step ) {
				case 0:
					$this->greet();
					break;
				case 1:
					check_admin_referer( 'import-upload' );
					if ( $this->handle_upload() ) {
						$file = get_attached_file( $this->id );
						set_time_limit( 0 );
						$this->import( $file );
					}
					break;
			}

			$this->footer();
		}

		/**
		 * The main controller for the actual import stage.
		 *
		 * @param string $file Path to the WXR file for importing
		 */
		function import( $file ) {
			add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
			add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

			$this->import_start( $file );

			wp_suspend_cache_invalidation( true );
			$this->process_nav_menu_meta();
			wp_suspend_cache_invalidation( false );

			$this->import_end();
		}

		/**
		 * Parses the WXR file and prepares us for the task of processing parsed data
		 *
		 * @param string $file Path to the WXR file for importing
		 */
		function import_start( $file ) {
			if ( ! is_file( $file ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'nav-menu-roles' ) . '</strong><br />';
				echo esc_html__( 'The file does not exist, please try again.', 'nav-menu-roles' ) . '</p>';
				$this->footer();
				die();
			}

			$import_data = $this->parse( $file );

			if ( is_wp_error( $import_data ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'nav-menu-roles' ) . '</strong><br />';
				echo esc_html( $import_data->get_error_message() ) . '</p>';
				$this->footer();
				die();
			}

			$this->version  = $import_data['version'];
			$this->posts    = $import_data['posts'];
			$this->base_url = esc_url( $import_data['base_url'] );

			wp_defer_term_counting( true );
			wp_defer_comment_counting( true );

			do_action( 'import_start' );
		}

		/**
		 * Performs post-import cleanup of files and the cache
		 */
		function import_end() {
			wp_import_cleanup( $this->id );

			wp_cache_flush();
			foreach ( get_taxonomies() as $tax ) {
				delete_option( "{$tax}_children" );
				_get_term_hierarchy( $tax );
			}

			wp_defer_term_counting( false );
			wp_defer_comment_counting( false );

			echo '<p>' . esc_html__( 'All done.', 'nav-menu-roles' ) . ' <a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Have fun!', 'nav-menu-roles' ) . '</a>' . '</p>';

			do_action( 'import_end' );
		}

		/**
		 * Handles the WXR upload and initial parsing of the file to prepare for
		 * displaying author import options
		 *
		 * @return bool False if error uploading or invalid file, true otherwise
		 */
		function handle_upload() {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'nav-menu-roles' ) . '</strong><br />';
				echo esc_html( $file['error'] ) . '</p>';
				return false;
			} elseif ( ! file_exists( $file['file'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'nav-menu-roles' ) . '</strong><br />';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				printf( esc_html__( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'nav-menu-roles' ), '<code>' . esc_html( $file['file'] ) . '</code>' );
				echo '</p>';
				return false;
			}

			$this->id    = (int) $file['id'];
			$import_data = $this->parse( $file['file'] );
			if ( is_wp_error( $import_data ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'nav-menu-roles' ) . '</strong><br />';
				echo esc_html( $import_data->get_error_message() ) . '</p>';
				return false;
			}

			$this->version = $import_data['version'];
			if ( $this->version > $this->max_wxr_version ) {
				echo '<div class="error"><p><strong>';
				printf( esc_html__( 'This WXR file (version %s) may not be supported by this version of the importer. Please consider updating.', 'nav-menu-roles' ), esc_html( $import_data['version'] ) );
				echo '</strong></p></div>';
			}

			return true;
		}



		/**
		 * Create new posts based on import information
		 *
		 * Posts marked as having a parent which doesn't exist will become top level items.
		 * Doesn't create a new post if: the post type doesn't exist, the given post ID
		 * is already noted as imported or a post with the same title and date already exists.
		 * Note that new/updated terms, comments and meta are imported for the last of the above.
		 */
		function process_nav_menu_meta() {
			foreach ( $this->posts as $post ) {

				// we only want to deal with the nav_menu_item posts
				if ( 'nav_menu_item' != $post['post_type'] || ! empty( $post['post_id'] ) ) {
					continue;
				}

				// ok we've got a nav_menu_item
				$post_id = (int) $post['post_id'];

				// add/update post meta
				if ( isset( $post['postmeta'] ) ) {
					foreach ( $post['postmeta'] as $meta ) {
						$key   = apply_filters( 'import_post_meta_key', $meta['key'] );
						$value = false;

						if ( $key ) {
							// export gets meta straight from the DB so could have a serialized string
							if ( ! $value ) {
								$value = maybe_unserialize( $meta['value'] );
							}

							update_post_meta( $post_id, $key, $value );
							do_action( 'import_post_meta', $post_id, $key, $value );

						}
					}
				}
			}

			unset( $this->posts );
		}




		/**
		 * Parse a WXR file
		 *
		 * @param string $file Path to WXR file for parsing
		 * @return array Information gathered from the WXR file
		 */
		function parse( $file ) {
			$parser = new WXR_Parser();
			return $parser->parse( $file );
		}

		// Display import page title
		function header() {
			echo '<div class="wrap">';
			echo '<h2>' . esc_html__( 'Import Nav Menu Roles', 'nav-menu-roles' ) . '</h2>';

			$updates  = get_plugin_updates();
			$basename = plugin_basename( __FILE__ );
			if ( isset( $updates[ $basename ] ) ) {
				$update = $updates[ $basename ];
				echo '<div class="error"><p><strong>';
				printf( esc_html__( 'A new version of this importer is available. Please update to version %s to ensure compatibility with newer export files.', 'nav-menu-roles' ), esc_html( $update->update->new_version ) );
				echo '</strong></p></div>';
			}
		}

		// Close div.wrap
		function footer() {
			echo '</div>';
		}

		/**
		 * Display introductory text and file upload form
		 */
		function greet() {
			echo '<div class="narrow">';
			echo '<p>' . esc_html__( 'Re-Upload your normal WordPress eXtended RSS (WXR) file and we&#8217;ll import the Nav Menu Roles and any other missing post meta for the Nav Menu items.', 'nav-menu-roles' ) . '</p>';
			echo '<p>' . esc_html__( 'Choose a WXR (.xml) file to upload, then click Upload file and import.', 'nav-menu-roles' ) . '</p>';
			wp_import_upload_form( 'admin.php?import=nav_menu_roles&amp;step=1' );
			echo '</div>';
		}

		/**
		 * Decide if the given meta key maps to information we will want to import
		 *
		 * @param string $key The meta key to check
		 * @return string|bool The key if we do want to import, false if not
		 */
		function is_valid_meta_key( $key ) {
			// skip attachment metadata since we'll regenerate it from scratch
			// skip _edit_lock as not relevant for import
			if ( in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata', '_edit_lock' ), true ) ) {
				return false;
			}
			return $key;
		}


		/**
		 * Added to http_request_timeout filter to force timeout at 60 seconds during import
		 * @param int $val
		 * @return int
		 */
		public function bump_request_timeout( $val ) {
			return 60;
		}

		// return the difference in length between two strings
		function cmpr_strlen( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}


	} // end class
} // end if
