<?php
/**
 * Plugin Name: Necessary Tools
 * Version:     1.1.1
 * Description: Adds some tools to WordPress that should be supplied by default.
 * Author:      Forest Hoffman
 * Author URI:  http://foresthoffman.com/
 * Plugin URI:  https://wordpress.org/plugins/necessary-tools/
 * License:     GPL2
 * 
 * Necessary Tools is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *  
 * Necessary Tools is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *  
 * You should have received a copy of the GNU General Public License
 * along with Necessary Tools. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
 * 
 * @package necessary-tools
 */

require( dirname( __FILE__ ) . '/necessary-tools-export.php' );

class NecessaryToolsPlugin {

	/**
	 * Holds strings that are used in creating nonces for the plugin's features.
	 *
	 * @var array
	 */
	public $nonce_actions = array(
		'nt_options_page' => 'nt_options_page',
		'nt_clone_post' => 'nt_clone_post',
		'nt_export_page' => 'nt_export_page'
	);

	/**
	 * An instance of the custom exporter in the 'NecessaryToolsExport' class.
	 *
	 * @var NecessaryToolsExport
	 */
	public $exporter;

	public function __construct() {
		$this->exporter = new NecessaryToolsExport();

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Registers stylesheet and scripts, and adds actions/filters.
	 */
	public function init() {

		// registering options page JS
		wp_register_script(
			'nt_scripts_admin_options',
			plugins_url( '/scripts/admin_options.min.js', __FILE__ ),
			array( 'jquery' )
		);

		// registering clone post JS
		wp_register_script( 
			'nt_scripts_clone_post',
			plugins_url( '/scripts/clone_post.min.js', __FILE__ ),
			array( 'jquery' )
		);

		// registering export page JS
		wp_register_script(
			'nt_scripts_admin_export',
			plugins_url( '/scripts/admin_export.min.js', __FILE__ ),
			array( 'jquery' )
		);

		// registering general admin styles
		wp_register_style(
			'nt_admin_styles',
			plugins_url( '/styles/admin_styles.css', __FILE__ )
		);

		// registering admin styles for clone post button
		wp_register_style(
			'nt_admin_styles_edit',
			plugins_url( '/styles/admin_styles_edit.css', __FILE__ )
		);

		// registering bootstrap
		wp_register_style(
			'nt_bootstrap',
			plugins_url( '/node_modules/bootstrap/dist/css/bootstrap.min.css', __FILE__ )
		);

		// registering export page style
		wp_register_style(
			'nt_styles_admin_export',
			plugins_url( '/styles/admin_export.css', __FILE__ ),
			array( 'nt_bootstrap' )
		);

		add_action( 'admin_menu'                 , array( $this, 'add_pages' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_clone_post_button' ) );
		add_action( 'wp_ajax_nt_save_options'    , array( $this, 'save_options' ) );
		add_action( 'wp_ajax_nt_clone_post'      , array( $this, 'clone_post' ) );
		add_action( 'wp_ajax_nt_export'          , array( $this, 'export_ajax' ) );
		add_action( 'wp_ajax_nt_export_page'     , array( $this, 'update_export_table_ajax' ) );
		add_action( 'admin_enqueue_scripts'      , array( $this, 'load_admin_styles_and_scripts' ) );
	}

	public function load_admin_styles_and_scripts( $hook ) {
		$localized_array = array(
			'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce' => ''
		);

		if ( 'edit.php' === $hook || 'post.php' === $hook || 'post-new.php' === $hook ) {
			wp_enqueue_style( 'nt_admin_styles_edit' );
		} else if ( 'tools_page_nt-export-page' === $hook ) {
			$localized_array['nonce'] = wp_create_nonce( $this->nonce_actions['nt_export_page'] );

			wp_enqueue_script( 'nt_scripts_admin_export' );
			wp_localize_script( 'nt_scripts_admin_export', '_nt_export', $localized_array );

			wp_enqueue_style( 'nt_styles_admin_export' );
		} else if ( 'toplevel_page_nt-options-page' === $hook ) {
			$localized_array['nonce'] = wp_create_nonce( $this->nonce_actions['nt_options_page'] );
			$localized_array['page_slug'] = substr( $hook, strlen( 'toplevel_page_' ) );

			wp_enqueue_script( 'nt_scripts_admin_options' );
			wp_localize_script( 'nt_scripts_admin_options', '_nt_options', $localized_array );

			wp_enqueue_style( 'nt_bootstrap' );
		}

		wp_enqueue_style( 'nt_admin_styles' );
	}

	/**
	 * Adds Necessary Tools admin menu pages.
	 */
	public function add_pages() {
		$export_post_enabled = (boolean) get_option( 'nt_export_post_enabled', true );
		if ( $export_post_enabled ) {

			// adds the Export pages for exporting custom post types individually or in bulk
			$export_page_title  = 'Necessary Tools Export Page';
			$export_menu_title  = 'Necessary Tools Export';
			$export_capability  = 'export';
			$export_menu_slug   = 'nt-export-page';
			$export_function    = array( $this, 'render_export_page' );
			add_management_page(
				$export_page_title,
				$export_menu_title,
				$export_capability,
				$export_menu_slug,
				$export_function
			);
		}

		// adds the options page for disabling certain plugin features, in case they are unwanted or
		// conflict with any other plugins
		$opt_page_title = 'Necessary Tools Plugin Options';
		$opt_menu_title = 'Necessary Tools Options';
		$opt_capability = 'manage_options';
		$opt_menu_slug  = 'nt-options-page';
		$opt_function   = array( $this, 'render_options_page' );
		$opt_icon_url   = plugins_url( 'icon-20x20.png', __FILE__ );
		$opt_position   = 76;
		add_menu_page(
			$opt_page_title,
			$opt_menu_title,
			$opt_capability,
			$opt_menu_slug,
			$opt_function,
			$opt_icon_url,
			$opt_position
		);
	}

	/**
	 * Creates an HTML table of a specific post type.
	 *
	 * @param string $post_type   A custom or default post type.
	 * @param string $post_status The post status.
	 * @return string The HTML of the table to render.
	 */
	public function create_post_table( $post_type, $post_status ) {
		$args = array(
			'post_type' => $post_type,
			'post_status' => $post_status
		);
		$posts = get_posts( $args );
		?>
		<table class="table">
			<thead>
				<tr>
					<th>
						<label class="screen-reader-text" for="nt-select-all-1"><?php echo __( 'Select All', 'necessary-tools' ); ?></label>
						<input id="nt-select-all-1" class="nt-cb nt-select-all" type="checkbox" />
					</th>
					<th><?php echo __( 'Title', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Author', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Categories', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Tags', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Comments', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Date', 'necessary-tools' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $posts as $p_key => $p_obj ) :
					$author_obj = get_userdata( $p_obj->post_author );
					$categories = get_the_category( $p_obj->ID );
					$tags = wp_get_post_tags( $p_obj->ID );
					$dt = time() - strtotime( $p_obj->post_date_gmt );
					$hrs_published = getdate( $dt )['hours'];
				?>
				<tr>
					<td>
						<label class="screen-reader-text" for="nt-select-<?php echo $p_key; ?>"><?php echo __( 'Select ' . $p_obj->post_title, 'necessary-tools' ); ?></label>
						<input id="nt-select-<?php echo $p_key; ?>" class='nt-cb nt-cb-normal' type='checkbox' />
						<input class='nt-post-id' type='hidden' value='<?php echo $p_obj->ID; ?>' />
					</td>
					<td>
						<a href="<?php echo esc_url( get_permalink( $p_obj->ID ) ); ?>" target="_blank" rel="nofollow"><?php echo $p_obj->post_title; ?></a>
					</td>
					<td>
						<a href="<?php echo esc_url( get_author_posts_url( $author_obj->ID ) ); ?>" target="_blank" rel="nofollow"><?php echo $author_obj->display_name; ?></a>
					</td>
					<td>
						<?php foreach ( $categories as $cat_key => $cat_term ) : ?>
							<a href="<?php echo esc_url( get_category_link( $cat_term->cat_ID ) ); ?>" target="_blank" rel="nofollow"><?php echo $cat_term->cat_name; ?></a><?php echo $cat_key + 1 < count( $categories ) ? ', ' : ''; ?>
						<?php endforeach; ?>
					</td>
					<td>
						<?php foreach ( $tags as $tag_key => $tag_term ) : ?>
							<a href="<?php echo esc_url( get_tag_link( $tag_term->term_id ) ); ?>" target="_blank" rel="nofollow"><?php echo $tag_term->name; ?></a><?php echo $tag_key + 1 < count( $tags ) ? ', ' : ''; ?>
						<?php endforeach; ?>
					</td>
					<td>
						<label class="screen-reader-text" for="nt-comment-link-<?php echo $p_key ?>"><?php echo __( 'Go to the comment section of ' . $p_obj->post_title, 'necessary-tools' ); ?></label>
						<a id="nt-comment-link-<?php echo $p_key ?>" href="<?php echo esc_url( get_comments_link( $p_obj->ID ) ); ?>" target="_blank" rel="nofollow"><?php echo get_comments_number( $p_obj->ID ); ?></a>
					</td>
					<td>
						<?php echo __( 'Published', 'necessary-tools' ); ?>
						<br/>
						<abbr title="<?php echo $p_obj->post_date ?>"><?php echo $hrs_published > 0 ? "{$hrs_published} hours ago" : "{$hrs_published} hour ago"; ?></abbr>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th>
						<label class="screen-reader-text" for="nt-select-all-2"><?php echo __( 'Select All', 'necessary-tools' ); ?></label>
						<input id="nt-select-all-2" class="nt-cb nt-select-all" type="checkbox" />
					</th>
					<th><?php echo __( 'Title', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Author', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Categories', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Tags', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Comments', 'necessary-tools' ); ?></th>
					<th><?php echo __( 'Date', 'necessary-tools' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * Renders the Necessary Tools Export page under the 'Tools' admin menu.
	 */
	public function render_export_page() {
		$export_post_enabled = (boolean) get_option( 'nt_export_post_enabled', true );
		if ( ! $export_post_enabled ) {
			wp_die( 0 );
		}

		if ( current_user_can( 'export' ) ) :

			// the default post types are included as static options in the type select field,
			// no need for them to be included in the query
			$post_types = get_post_types( array( '_builtin' => false ), 'objects' );
		?>
			<div class='wrap'>
				<div class='alert alert-danger nt-hidden' role='alert'></div>
				<h2 class='nt-page-header'><?php echo esc_html( get_admin_page_title() ); ?></h2>
				<p>Export custom posts types, individually or in bulk. The larger the number of files to export, the longer the process will take.</p>
				<div class='nt-export-options'>
					<h4>Export Settings</h4>

					<!-- post type option -->
					<div class='nt-export-option-wrap'>
						<label class='nt-export-option-label' for='nt-export-post-type'>
							Post Type:
						</label>
						<select class='nt-export-post-type'>
							<option value='post' selected>Post</option>
							<option value='page'>Page</option>
							<?php foreach( $post_types as $type_obj ) : ?>
								<option value='<?php echo $type_obj->name; ?>'><?php echo $type_obj->label; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<!-- #post type option -->

					<!-- post status option -->
					<div class='nt-export-option-wrap'>
						<label class='nt-export-option-label' for='nt-export-post-status'>
							Post Status:
						</label>
						<select class='nt-export-post-status'>
							<option value='publish' selected>Publish</option>
							<option value='future'>Future</option>
							<option value='draft'>Draft</option>
							<option value='pending'>Pending</option>
							<option value='private'>Private</option>
							<option value='trash'>Trash</option>
						</select>
					</div>
					<!-- #post status option -->

					<!-- post list -->
					<?php $this->create_post_table( 'post', 'publish' ); ?>
					<!-- #post list -->
				</div>
				<p class='nt-btn-wrap nt-btn-export'><a href='#' class='btn btn-primary' role='button'>Export</a></p>
			</div>
		<?php
		else :
			echo "<h1>Uh-oh! You can't view this page, because of a lack of permissions.</h1>";
			wp_die();
		endif;
	}

	/**
	 * Handles the POST request when the export page's post type changes. Responds with the updated
	 * table upon success.
	 */
	public function update_export_table_ajax() {
		$export_post_enabled = (boolean) get_option( 'nt_export_post_enabled', true );

		if ( ! current_user_can( 'export' ) ||
				! isset( $_POST['post_type'] ) ||
				! isset( $_POST['post_status'] ) ||
				! check_ajax_referer( $this->nonce_actions['nt_export_page'] ) ||
				! $export_post_enabled ) {
			wp_die( 0 );
		}

		$post_type = sanitize_key( $_POST['post_type'] );
		$post_status = sanitize_key( $_POST['post_status'] );

		// outputs the table HTML in response to the POST request
		$this->create_post_table( $post_type, $post_status );
		wp_die();
	}

	/**
	 * Handles the GET request when the "Export" button on the export page is hit. Responds with the
	 * proper file transfer headers and the downloadable WXR xml file.
	 */
	public function export_ajax() {
		$export_post_enabled = (boolean) get_option( 'nt_export_post_enabled', true );

		if ( ! current_user_can( 'export' ) ||
				! isset( $_GET['post_ids'] ) ||
				! check_ajax_referer( $this->nonce_actions['nt_export_page'] ) ||
				! $export_post_enabled ) {
			wp_die( 0 );
		}

		$ids_array = explode( ',', $_GET['post_ids'] );

		$this->exporter->export_wp( array( 'ids' => $ids_array ) );
		die();
	}

	/**
	 * Renders the options page for the plugin.
	 */
	public function render_options_page() {
		if ( current_user_can( 'manage_options' ) ) :

			/**
			 * Gets the (potentially) stored options indicating whether the features are enabled or
			 * disabled. 'get_option()' returns option values a strings, so the values are cast to
			 * booleans just in case.
			 */
			$clone_post_enabled  = (boolean) get_option( 'nt_clone_post_enabled' , true );
			$export_post_enabled = (boolean) get_option( 'nt_export_post_enabled', true );
		?>
			<div class='wrap'>
				<div class='alert alert-danger nt-hidden' role='alert'></div>
				<div class='alert alert-success nt-hidden' role='alert'></div>
				<h2 class='nt-page-header'><?php echo esc_html( get_admin_page_title() ); ?></h2>
				<p>This page is here to allow you to turn features provided by this plugin off, if for some reason you don't need them, are trying to debug a plugin conflict, etc.</p>
				<div class='nt-options'>
					<h4>Features</h4>
					<!-- clone post feature -->
					<div class='nt-option-wrap'>
						<label class='nt-option-label' for='nt-option-clone'>
							Clone Post Button:
						</label>
						<input id='nt-option-clone' class='nt-option-input' type='checkbox'
							<?php echo $clone_post_enabled ? 'checked' : ''; ?> />
						<input class='nt-option-input' type='hidden' value='clone_post' />
					</div>
					<!-- #clone post feature -->

					<!-- export posts feature -->
					<div class='nt-option-wrap'>
						<label class='nt-option-label' for='nt-option-export'>
							Export Posts Page:
						</label>
						<input id='nt-option-export' class='nt-option-input' type='checkbox'
							<?php echo $export_post_enabled ? 'checked' : ''; ?> />
						<input class='nt-option-input' type='hidden' value='export_post' />
					</div>
					<!-- #export posts feature -->
				</div>
				<p class='nt-btn-wrap nt-btn-save'><a href='#' class='btn btn-primary' role='button'>Save</a></p>
			</div>
		<?php
		else :
			echo "<h1>Uh-oh! You can't view this page, because of a lack of permissions.</h1>";
			wp_die();
		endif;
	}

	/**
	 * Handles save requests for the plugin's options page.
	 *
	 * This handler is looking for an array of key-value pairs that will determine whether or not
	 * a plugin feature is enabled. The array should contain the name of the feature (e.g.
	 * 'clone_post') and the value will contain an int of either 0 or 1 (on and off, respectively).
	 */
	public function save_options() {
		if ( ! current_user_can( 'manage_options' ) ||
				! isset( $_POST['features'] ) ||
				! check_ajax_referer( $this->nonce_actions['nt_options_page'] ) ) {
			wp_die( 0 );
		}

		$defaults = array( 'clone_post', 'export_post' );
		$features = $_POST['features'];
		foreach ( $features as $arr ) {
			$key = sanitize_key( $arr[0] );
			if ( -1 !== array_search( $key, $defaults ) ) {
				$option_name = "nt_{$key}_enabled";
				$option_value = 1 === intval( $arr[1] ) ? true : false;
				$old_value = get_option( $option_name, true );

				$success = update_option( $option_name, $option_value );

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! $success ) {
					error_log( sprintf(
						'NecessaryToolsPlugin::%s(): updating \'%s\' failed, ' .
							'old_value = (%s) %s; new_value = (%s) %s',
						__FUNCTION__,
						$option_name,
						gettype( $old_value ),
						print_r( $old_value, true ),
						gettype( $option_value ),
						print_r( $option_value, true )
					));
				}
			}
		}

		wp_die( 1 );
	}

	/**
	 * Renders the "Clone" button in the post submission box of the post edit page.
	 */
	public function render_clone_post_button() {
		$clone_post_enabled = (boolean) get_option( 'nt_clone_post_enabled', true );

		if ( ! $clone_post_enabled ) {
			return;
		}

		global $post;

		$post_type = get_post_type( $post );
		$nonce = wp_create_nonce( "{$this->nonce_actions['nt_clone_post']}_{$post->ID}" );

		$localized_array = array(
			'admin_url' => esc_url( admin_url() ),
			'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'post_id' => $post->ID,
			'post_type' => $post_type,
			'nonce' => $nonce
		);

		// When the "Clone" button is pressed, send an ajax call to the nt_clone_post() function to
		// clone the post using the sent post id and post type.
		echo "
			<div class='misc-pub-section misc-pub-section-last'>
				<input id='nt-button-clone' class='button button-large alignright' type='button' value='Clone' />
			</div>
		";

		wp_enqueue_script( 'nt_scripts_clone_post' );
		wp_localize_script( 'nt_scripts_clone_post', 'nt_clone_post_ajax_php_vars', $localized_array );
	}

	public function clone_post() {
		$clone_post_enabled = (boolean) get_option( 'nt_clone_post_enabled', true );

		if ( ! isset( $_POST['post_id'] ) ||
				! isset( $_POST['post_type'] ) ||
				! isset( $_POST['_ajax_nonce'] ) ||
				! current_user_can( 'edit_posts' ) ||
				! $clone_post_enabled ) {
			wp_die( 0 );
		}

		// Grab the post id from the AJAX call and use it to grab data from the original post.
		$post_id = intval( $_POST['post_id'] );
		$post_type = sanitize_key( $_POST['post_type'] );
		$nonce = sanitize_key( $_POST['_ajax_nonce'] );
		if ( empty( $post_id ) ||
				$post_id < 0 ||
				empty( $post_type ) ||
				! post_type_exists( $post_type ) ||
				! check_ajax_referer( "{$this->nonce_actions['nt_clone_post']}_{$post_id}" ) ) {
			wp_die( 0 );
		}

		$post_data = get_post( $post_id, ARRAY_A );
		$post_custom = get_post_custom( $post_id );
		if ( empty( $post_data ) ) {
			wp_die( 0 );
		}
		$post_tax = get_object_taxonomies( $post_type, 'objects' );

		// Change the post status to "draft", leave the guid up to WordPress,
		// and remove all other post data.
		$post_data['post_status'] = 'draft';
		$post_data['guid'] = '';
		unset( $post_data['ID'] );
		unset( $post_data['post_title'] );
		unset( $post_data['post_name'] );
		unset( $post_data['post_modified'] );
		unset( $post_data['post_modified_gmt'] );
		unset( $post_data['post_date'] );
		unset( $post_data['post_date_gmt'] );

		// Clone the original post with the modified data from above, and retreive the new post's id.
		$clone_id = wp_insert_post( $post_data );

		if ( ! empty( $post_tax ) ) {
			foreach ( $post_tax as $tax_slug => $tax ) {
				$terms = get_the_terms( $post_id, $tax_slug );
				if ( ! empty( $terms ) ) {
					$term_slugs_array = array();
					foreach ( $terms as $key => $term ) {
						$term_slugs_array[] = $term->slug;
					}
					wp_set_object_terms( $clone_id, $term_slugs_array, $tax_slug );
				}
			}
		}

		if ( ! empty( $clone_id ) ) {
			$url = admin_url( "post.php?post={$clone_id}&action=edit" );
			
			// Add the original post's meta data to the clone.
			foreach ( $post_custom as $key => $value ) {
				for ( $i = 0; $i < count( $value ); $i++ ) {
					
					// unserialize each value, but use the serialized value if it's not an array
					if ( is_serialized( $value[ $i ] ) ) {
						$uns = unserialize( $value[ $i ] );
						add_post_meta( $clone_id, $key, $uns, true );
					} else {
						add_post_meta( $clone_id, $key, $value[ $i ], true );
					}
				}
			}
			wp_die( $url );
		} else {
			wp_die( 0 );
		}
	}
}

$NecessaryToolsPlugin = new NecessaryToolsPlugin();
