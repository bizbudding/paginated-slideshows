<?php
/**
 * Plugin Name:        Paginated Slideshows
 * Plugin URI:         https://github.com/bizbudding/paginated-slideshows
 * Description:        Create paginated slideshows that can easily be attached to one or more posts.
 * Version:            0.1.0
 *
 * Author:             BizBudding, Mike Hemberger
 * Author URI:         https://bizbudding.com
 *
 * GitHub Plugin URI:  https://github.com/bizbudding/paginated-slideshows
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Paginated_Slideshows_Setup Class.
 *
 * @since 1.0.0
 */
final class Paginated_Slideshows_Setup {

	/**
	 * @var Paginated_Slideshows_Setup The one true Paginated_Slideshows_Setup
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Main Paginated_Slideshows_Setup Instance.
	 *
	 * Insures that only one instance of Paginated_Slideshows_Setup exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    Paginated_Slideshows_Setup::setup_constants() Setup the constants needed.
	 * @uses    Paginated_Slideshows_Setup::includes() Include the required files.
	 * @uses    Paginated_Slideshows_Setup::setup() Activate, deactivate, etc.
	 * @see     Paginated_Slideshows()
	 * @return  object | Paginated_Slideshows_Setup The one true Paginated_Slideshows_Setup
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new Paginated_Slideshows_Setup;
			// Methods
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'paginated-slideshows' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'paginated-slideshows' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'PAGINATED_SLIDESHOWS_VERSION' ) ) {
			define( 'PAGINATED_SLIDESHOWS_VERSION', '0.1.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'PAGINATED_SLIDESHOWS_DIR' ) ) {
			define( 'PAGINATED_SLIDESHOWS_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'PAGINATED_SLIDESHOWS_INCLUDES_DIR' ) ) {
			define( 'PAGINATED_SLIDESHOWS_INCLUDES_DIR', PAGINATED_SLIDESHOWS_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'PAGINATED_SLIDESHOWS_URL' ) ) {
			define( 'PAGINATED_SLIDESHOWS_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'PAGINATED_SLIDESHOWS_FILE' ) ) {
			define( 'PAGINATED_SLIDESHOWS_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'PAGINATED_SLIDESHOWS_BASENAME' ) ) {
			define( 'PAGINATED_SLIDESHOWS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function includes() {
		foreach ( glob( PAGINATED_SLIDESHOWS_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	public function setup() {

		add_action( 'admin_init',          array( $this, 'updater' ) );
		add_action( 'init',                array( $this, 'register_content_types' ) );
		add_action( 'p2p_init',            array( $this, 'register_p2p_connections' ) );
		// add_action( 'acf/save_post',       array( $this, 'update_slideshow_post_data' ), 20 ); // No longer using, easier to tweak if we dynamically build the markup.
		add_action( 'wp_enqueue_scripts',  array( $this, 'inline_styles' ), 1000 );
		add_action( 'the_post',            array( $this, 'create_pages' ) );

		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	public function updater() {
		/**
		 * Setup the updater.
		 *
		 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
		 *
		 * @return  void
		 */
		if ( ! class_exists( 'Puc_v4p3_Factory' ) ) {
			require_once MAI_FAVORITES_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php'; // 4.3.1
		}
		$updater = Puc_v4p3_Factory::buildUpdateChecker( 'https://github.com/bizbudding/paginated-slideshows/', __FILE__, 'paginated-slideshows' );
	}

	public function register_content_types() {

		/***********************
		 *  Custom Post Types  *
		 ***********************/

		register_post_type( 'slideshow', array(
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'labels'              => array(
				'name'               => _x( 'Slideshows', 'Slideshow general name',         'paginated-slideshows' ),
				'singular_name'      => _x( 'Slideshow',  'Slideshow singular name',        'paginated-slideshows' ),
				'menu_name'          => _x( 'Slideshows', 'Slideshow admin menu',           'paginated-slideshows' ),
				'name_admin_bar'     => _x( 'Slideshow',  'Slideshow add new on admin bar', 'paginated-slideshows' ),
				'add_new'            => _x( 'Add New',    'Slideshow',                      'paginated-slideshows' ),
				'add_new_item'       => __( 'Add New Slideshow',                            'paginated-slideshows' ),
				'new_item'           => __( 'New Slideshow',                                'paginated-slideshows' ),
				'edit_item'          => __( 'Edit Slideshow',                               'paginated-slideshows' ),
				'view_item'          => __( 'View Slideshow',                               'paginated-slideshows' ),
				'all_items'          => __( 'All Slideshows',                               'paginated-slideshows' ),
				'search_items'       => __( 'Search Slideshows',                            'paginated-slideshows' ),
				'parent_item_colon'  => __( 'Parent Slideshows:',                           'paginated-slideshows' ),
				'not_found'          => __( 'No Slideshows found.',                         'paginated-slideshows' ),
				'not_found_in_trash' => __( 'No Slideshows found in Trash.',                'paginated-slideshows' )
			),
			'menu_icon'          => 'dashicons-images-alt2',
			'public'             => false,
			'publicly_queryable' => false,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_ui'            => true,
			'rewrite'            => false,
			'supports'           => array( 'title' ),
			// 'taxonomies'         => array( 'slideshow_cat' ),
		) );

		// register_extended_post_type( 'slideshow', array(
		// 	'menu_position' => 5,
		// 	'menu_icon'		=> 'dashicons-images-alt2',
		// 	'admin_cols' 	=> array(
		// 		'slideshow_to_posts' => array(
		// 		    'title'      => 'Slideshows',
		// 		    'connection' => 'slideshow_to_posts',
		// 		),
		// 	),
		// ), array(
		// 	'singular' => 'Slideshow',
		// 	'plural'   => 'Slideshows',
		// ) );

	}

	/**
	 * Register Posts to Posts connections
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	function register_p2p_connections() {
		p2p_register_connection_type( array(
			'name'            => 'slideshow_to_posts',
			'from'            => 'slideshow',
			'to'              => 'post',
			'cardinality'     => 'one-to-many',
			'can_create_post' => false,
			'from_labels'     => array(
				'singular_name' => __( 'Slideshow', 'collegespun' ),
				'search_items'  => __( 'Search slideshows', 'collegespun' ),
				'not_found'     => __( 'No slideshows found.', 'collegespun' ),
				'create'        => __( 'Connect a slideshow', 'collegespun' ),
			),
			'to_labels'       => array(
				'singular_name' => __( 'Post', 'collegespun' ),
				'search_items'  => __( 'Search posts', 'collegespun' ),
				'not_found'     => __( 'No posts found.', 'collegespun' ),
				'create'        => __( 'Connect a post', 'collegespun' ),
			),
		) );
	}

	/**
	 * NO LONGER USING, BUT SAVING CAUSE IT'S COOL.
	 *
	 * Update slideshow meta data into the post content so it's much quicker to load when displaying.
	 *
	 * @param  int     $post_id  The post ID.
	 * @param  object  $post     The post object.
	 * @param  bool    $update   Whether this is an existing post being updated or not.
	 *
	 * @return void
	 */
	public function update_slideshow_post_data( $post_id ) {

		if ( 'slideshow' !== get_post_type( $post_id ) ) {
			return;
		}

		$content = '';

		// Unhook this function so it doesn't loop infinitely.
		remove_action( 'acf/save_post', array( $this, 'update_slideshow_post_data' ), 20 );

		// Get the slides.
		$slides = $this->get_field( $post_id, $this->get_config() );

		/**
		 * Get slides.
		 * The initial $slides array was the entire field group,
		 * this grabs only the repeater field.
		 */
		$slides = isset( $slides[ 'slides' ] ) ? $slides[ 'slides' ] : '';

		if ( $slides ) {

			// http://stackoverflow.com/questions/2348205/how-to-get-last-key-in-an-array
			$last_item_key = key( array_slice( $slides, -1, 1, TRUE ) );

			foreach ( $slides as $key => $slide ) {

				$content .= '<div class="slideshow-slide">';
					$content .= $slide['title'] ? '<h3 class="slide-title>' . esc_html($slide['title']) . '</h3>' : '';
					$content .= $slide['image'] ? '<div class="slide-image">' . wp_get_attachment_image( $slide['image'], 'featured' ) . '</div>' : '';
					$content .= $slide['content'] ? wp_kses_post($slide['content']) : '';
				$content .= '</div>';

				// If not the last slide, add the next page break.
				if ( $key != $last_item_key ) {
					$content .= '<!--nextpage-->';
				}

			}

		}

		// Update the post, which calls save_post again.
		wp_update_post( array(
			'ID'			=> $post_id,
			'post_content'	=> $content,
		));

		// Re-hook this function.
		add_action( 'acf/save_post', array( $this, 'update_slideshow_post_data' ), 20 );
	}

	/**
	 * Retrieves all post meta data according to the structure in the $config array.
	 *
	 * Provides a convenient and more performant alternative to ACF's get_field().
	 *
	 * This function is especially useful when working with ACF repeater fields and
	 * flexible content layouts.
	 *
	 * @link    https://www.timjensen.us/acf-get-field-alternative/
	 *
	 * @version 1.2.5
	 *
	 * @param  integer  $post_id  Required. Post ID.
	 * @param  array    $config   Required. An array that represents the structure of
	 *                            the custom fields. Follows the same format as the
	 *                            ACF export field groups array.
	 * @return array
	 */
	function get_field( $post_id, array $config ) {

		$results = array();

		foreach ( $config as $field ) {

			if ( empty( $field['name'] ) ) {
				continue;
			}

			$meta_key = $field['name'];

			if ( isset( $field['meta_key_prefix'] ) ) {
				$meta_key = $field['meta_key_prefix'] . $meta_key;
			}

			$field_value = get_post_meta( $post_id, $meta_key, true );

			if ( isset( $field['layouts'] ) ) { // We're dealing with flexible content layouts.

				if ( empty( $field_value ) ) {
					continue;
				}

				// Build a keyed array of possible layout types.
				$layout_types = [];
				foreach ( $field['layouts'] as $key => $layout_type ) {
					$layout_types[ $layout_type['name'] ] = $layout_type;
				}

				foreach ( $field_value as $key => $current_layout_type ) {
					$new_config = $layout_types[ $current_layout_type ]['sub_fields'];

					if ( empty( $new_config ) ) {
						continue;
					}

					foreach ( $new_config as &$field_config ) {
						$field_config['meta_key_prefix'] = $meta_key . "_{$key}_";
					}

					$results[ $field['name'] ][] = array_merge(
						[
							'acf_fc_layout' => $current_layout_type,
						],
						$this->get_field( $post_id, $new_config )
					);
				}
			} elseif ( isset( $field['sub_fields'] ) ) { // We're dealing with repeater fields.

				if ( empty( $field_value ) ) {
					continue;
				}

				for ( $i = 0; $i < $field_value; $i ++ ) {
					$new_config = $field['sub_fields'];

					if ( empty( $new_config ) ) {
						continue;
					}

					foreach ( $new_config as &$field_config ) {
						$field_config['meta_key_prefix'] = $meta_key . "_{$i}_";
					}

					$results[ $field['name'] ][] = $this->get_field( $post_id, $new_config );
				}
			} else {
				$results[ $field['name'] ] = $field_value;
			} // End if().
		} // End foreach().

		return $results;
	}

	public function get_config() {
		return array(
			array (
				'name'       => 'slides',
				'sub_fields' => array (
					array (
						'name' => 'title',
					),
					array (
						'name' => 'image',
					),
					array (
						'name' => 'content',
					),
				),
			),
		);
	}

	public function field_group() {

		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group( array(
			'key'    => 'group_5a314dee51d76',
			'title'  => 'Slideshows',
			'fields' => array(
				array(
					'key'               => 'field_5a314e222cdf7',
					'label'             => 'Slides',
					'name'              => 'slides',
					'type'              => 'repeater',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'collapsed'    => 'field_5a314e362cdf8',
					'min'          => 1,
					'max'          => 0,
					'layout'       => 'block',
					'button_label' => 'Add Slide',
					'sub_fields'   => array(
						array(
							'key'               => 'field_5a314e362cdf8',
							'label'             => 'Title',
							'name'              => 'title',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value' => '',
							'placeholder'   => '',
							'prepend'       => '',
							'append'        => '',
							'maxlength'     => '',
						),
						array(
							'key'               => 'field_5a314e5b2cdf9',
							'label'             => 'Image',
							'name'              => 'image',
							'type'              => 'image',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'library'       => 'all',
							'min_width'     => '',
							'min_height'    => '',
							'min_size'      => '',
							'max_width'     => '',
							'max_height'    => '',
							'max_size'      => '',
							'mime_types'    => '',
						),
						array(
							'key'               => 'field_5a314eda2cdfa',
							'label'             => 'Content',
							'name'              => 'content',
							'type'              => 'wysiwyg',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value' => '',
							'tabs'          => 'all',
							'toolbar'       => 'full',
							'media_upload'  => 1,
							'delay'         => 1,
						),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'slideshow',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => 1,
			'description'           => '',
		) );

	}

	// Output inline styles in the theme.
	public function inline_styles() {

		// wp_register_style( 'paginated-slideshows', PAGINATED_SLIDESHOWS_URL . 'css/paginated-slideshows.css', array(), PAGINATED_SLIDESHOWS_VERSION );

		$handle  = defined( 'CHILD_THEME_NAME' ) && CHILD_THEME_NAME ? sanitize_title_with_dashes( CHILD_THEME_NAME ) : 'child-theme';

		$css = "
		.slideshow {
			border: 1px solid rgba(0,0,0,0.05);
			border-radius: 3px;
		}
		.slideshow-title {
			background-color: #f1f1f1;
			font-size: 1.25rem;
			text-align: center;
			padding: 16px;
			margin: 0;
		}
		.slideshow-slide {
			padding: 24px;
		}
		.slideshow-pagination {
			margin-top: 0;
			margin-bottom: 24px;
		}
		.slideshow-pagination ul,
		.entry-content .slideshow-pagination ul {
			margin-left: 0;
		}
		.slideshow-pagination a {
			min-width: 120px;
			font-size: .8rem;
		}
		";

		wp_add_inline_style( $handle, $css );

	}

	public function create_pages( $post ) {

		global $pages, $multipage, $numpages;

		// Get the slideshow.
		$slideshow = p2p_type( 'slideshow_to_posts' )->get_connected( $post->ID );

		// Bail if no slideshow.
		if ( ! $slideshow->posts ) {
			return;
		}

		/**
		 * Get the first slideshow.
		 * Even if multiple, we can only use one without things breaking.
		 */
		$slideshow = $slideshow->posts[0];

		// Get the slides.
		$slides = $this->get_field( $slideshow->ID, $this->get_config() );

		if ( ! $slides ) {
			return;
		}

		/**
		 * Get slides.
		 * The initial $slides array was the entire field group,
		 * this grabs only the repeater field.
		 */
		$slides = isset( $slides[ 'slides' ] ) ? $slides[ 'slides' ] : '';

		if ( ! $slides ) {
			return;
		}

		add_filter( 'wp_link_pages_link', function( $link ) {
			return str_replace( '/">', '/#slideshow">', $link );
		});

		remove_action( 'genesis_entry_content', 'genesis_do_post_content_nav', 12 );

		wp_enqueue_style( 'paginated-slideshows' );

		$count       = count( $slides );
		$total_pages = count( $pages ) + $count;
		$page_index  = count( $pages ) - 1;

		if ( $count > 1 ) {
			$multipage = true;
			$numpages  = $total_pages;
		}

		// Loop through pages.
		foreach ( $slides as $slide ) {
			// Prevent building a page that doesn't exist yet.
			if ( isset( $pages[$page_index] ) ) {
				$pages[$page_index] = $pages[$page_index];
			} else {
				$pages[$page_index] = '';
			}
			$pages[$page_index] .= '<div id="slideshow" class="slideshow">';
				$pages[$page_index] .= '<h2 class="slideshow-title">' . get_the_title( $slideshow->ID ) . '</h2>';
				$pages[$page_index] .= '<div class="slideshow-slide">';
					$pages[$page_index] .= $slide['title'] ? '<h3 class="slide-title">' . esc_html( $slide['title'] ) . '</h3>' : '';
					$pages[$page_index] .= $slide['image'] ? '<p class="slide-image">' . wp_get_attachment_image( $slide['image'], 'featured' ) . '</p>' : '';
					$pages[$page_index] .= $slide['content'] ? apply_filters( 'the_content', $slide['content'] ) : '';
				$pages[$page_index] .= '</div>';
				$pages[$page_index] .= wp_link_pages( array(
					'before'           => '<div class="slideshow-pagination archive-pagination pagination">',
					'after'            => '</div>',
					'link_before'      => '',
					'link_after'       => '',
					'next_or_number'   => 'next',
					'separator'        => '',
					'previouspagelink' => '«&nbsp;&nbsp;Previous',
					'nextpagelink'     => 'Next&nbsp;&nbsp;»',
					'pagelink'         => '%',
					'echo'             => 0,
				) );
			$pages[$page_index] .= '</div>';
			$page_index++;
		}

	}

	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}

}

/**
 * The main function for that returns Paginated_Slideshows_Setup
 *
 * The main function responsible for returning the one true Paginated_Slideshows_Setup
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Paginated_Slideshows(); ?>
 *
 * @since 1.0.0
 *
 * @return object|Paginated_Slideshows_Setup The one true Paginated_Slideshows_Setup Instance.
 */
function Paginated_Slideshows() {
	return Paginated_Slideshows_Setup::instance();
}

// Get Paginated_Slideshows Running.
Paginated_Slideshows();
