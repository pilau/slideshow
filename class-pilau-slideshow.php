<?php
/**
 * Pilau Slideshow
 *
 * @package   Pilau_Slideshow
 * @author    Steve Taylor
 * @license   GPL-2.0+
 * @copyright 2014 Public Life
 */

/**
 * Plugin class
 *
 * @package Pilau_Slideshow
 * @author  Steve Taylor
 */
class Pilau_Slideshow {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1
	 *
	 * @var     string
	 */
	protected $version = '0.1';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.1
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'pilau-slideshow';

	/**
	 * Instance of this class.
	 *
	 * @since    0.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * The plugin's settings.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $settings = null;

	/**
	 * Are we on a post where a slideshow is active?
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $slideshow_active = false;

	/**
	 * Custom fields for the current post.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $custom_fields = null;

	/**
	 * Image size details for the slideshow in the current post.
	 *
	 * @since    0.1
	 *
	 * @var      array
	 */
	protected $image_size = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1
	 */
	private function __construct() {

		// Set the settings
		//$this->settings = $this->get_settings();

		// Global init
		add_action( 'init', array( $this, 'init' ) );

		// Admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add the settings page and menu item.
		//add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		//add_action( 'admin_init', array( $this, 'process_plugin_admin_settings' ) );

		// Load admin style sheet and JavaScript.
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'init', array( $this, 'register_custom_fields' ) );
		add_action( 'template_redirect', array( $this, 'output_init' ) );
		add_action( 'wp_head', array( $this, 'dynamic_css' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

	}

	/**
	 * Initialize
	 *
	 * @since    0.1
	 */
	public function init() {

		// Load plugin text domain
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	}

	/**
	 * Initialize admin
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function admin_init() {

		// Output dependency notices
		if ( ! defined( 'SLT_CF_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'output_dcf_dependency_notice' ) );
		}

	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1
	 */
	public function enqueue_styles() {

		if ( ! apply_filters( 'ps_disable_default_css', false ) && $this->slideshow_active ) {
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1
	 */
	public function enqueue_scripts() {

		if ( $this->slideshow_active ) {
			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Pilau slideshow', $this->plugin_slug ),
			__( 'Pilau slideshow', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Get the plugin's settings
	 *
	 * @since    0.1
	 */
	public function get_settings() {

		$settings = get_option( $this->plugin_slug . '_settings' );

		if ( ! $settings ) {
			// Defaults
			$settings = array();
		}

		return $settings;
	}

	/**
	 * Set the plugin's settings
	 *
	 * @since    0.1
	 */
	public function set_settings( $settings ) {
		return update_option( $this->plugin_slug . '_settings', $settings );
	}

	/**
	 * Process the settings page for this plugin.
	 *
	 * @since    0.1
	 */
	public function process_plugin_admin_settings() {

		// Submitted?
		if ( isset( $_POST[ $this->plugin_slug . '_settings_admin_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_settings', $this->plugin_slug . '_settings_admin_nonce' ) ) {

			// Gather into array
			$settings = array();

			// Save as option
			$this->set_settings( $settings );

			// Redirect
			wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin_slug . '&done=1' ) );

		}

	}

	/**
	 * Output Developer's Custom Fields dependency notice
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function output_dcf_dependency_notice() {
		echo '<div class="error"><p>' . __( 'The Pilau Slideshow plugin depends on the <a href="http://wordpress.org/plugins/developers-custom-fields/">Developer\'s Custom Fields</a> plugin, which isn\'t currently activated', $this->plugin_slug ) . '</p></div>';
	}

	/**
	 * Scope for slideshows
	 *
	 * @since	0.1
	 * @return	array
	 */
	private function scope() {

		// Default to nothing - filter must be used to defined scope
		return apply_filters( 'ps_scope', array() );

	}

	/**
	 * Check if the scope matches the current post (on front-end)
	 *
	 * @since	0.1
	 * @return	array
	 */
	private function in_scope() {
		global $post;

		// Use the DCF scope checker
		return ( ! is_admin() && is_object( $post ) && function_exists( 'slt_cf_check_scope' ) && slt_cf_check_scope( array( 'scope' => $this->scope() ), 'post', get_post_type(), $post->ID ) );

	}

	/**
	 * Register custom fields
	 *
	 * @since	0.1
	 */
	public function register_custom_fields() {

		if ( function_exists( 'slt_cf_register_box' ) ) {

			// Set up image size options
			$image_sizes = get_intermediate_image_sizes();
			$image_size_options = array();
			foreach ( $image_sizes as $image_size ) {
				$image_size_options[ $image_size ] = $image_size;
			}

			$args = apply_filters( 'ps_custom_field_box_args', array(
				'type'			=> 'post',
				'title'			=> 'Slideshow',
				'id'			=> 'pilau-slideshow-box',
				'context'		=> 'normal',
				'priority'		=> 'high',
				'description'	=> __( 'To use the slideshow on this page, upload images through the <i>Add Media</i> button above the content editor, and select and order the ones you want to include below.', $this->plugin_slug ),
				'fields'	=> array(
					array(
						'name'			=> 'ps-image-size',
						'label'			=> __( 'Image size', $this->plugin_slug ),
						'options'		=> $image_size_options,
						'type'			=> 'select',
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'update_core' )
					),
					array(
						'name'					=> 'ps-images',
						'label'					=> __( 'Images', $this->plugin_slug ),
						'type'					=> 'checkboxes',
						'multiple'				=> true,
						'sortable'				=> true,
						'checkboxes_thumbnail'	=> true,
						'single'				=> false,
						'options_type'			=> 'posts',
						'options_query'			=> array(
							'post_type'			=> 'attachment',
							'post_status'		=> 'inherit',
							'posts_per_page'	=> -1,
							'post_parent'		=> '[OBJECT_ID]',
							'post_mime_type'	=> 'image',
							'orderby'			=> 'title',
							'order'				=> 'ASC'
						),
						'scope'					=> $this->scope(),
						'capabilities'			=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-rotate-type',
						'label'			=> __( 'Rotate type', $this->plugin_slug ),
						'type'			=> 'radio',
						'description'	=> __( 'Note that scrolling slideshows are not currently responsive to screens narrower than the normal slideshow width.', $this->plugin_slug ),
						'options'		=> array(
							__( 'Fade', $this->plugin_slug )	=> 'fade',
							__( 'Scroll', $this->plugin_slug )	=> 'scroll'
						),
						'default'		=> 'fade',
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-rotate-fade-type',
						'label'			=> __( 'Fade type', $this->plugin_slug ),
						'type'			=> 'radio',
						'options'		=> array(
							__( 'Crossfade', $this->plugin_slug )			=> 'crossfade',
							__( 'Fade through colour', $this->plugin_slug )	=> 'colour'
						),
						'default'		=> 'crossfade',
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-rotate-fade-colour',
						'label'			=> __( 'Fade colour', $this->plugin_slug ),
						'type'			=> 'colorpicker',
						'default'		=> 'ffffff',
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-rotate-speed',
						'label'			=> __( 'Rotate speed', $this->plugin_slug ),
						'type'			=> 'text',
						'description'	=> __( 'For fading rotation that crossfades and scrolling rotation, this will be the duration of the crossfade or scroll. For fading through a colour, this will be the duration of the fade out as well as the duration of the fade in.', $this->plugin_slug ),
						'input_suffix'	=> ' ' . __( 'milliseconds', $this->plugin_slug ),
						'default'		=> '500',
						'width'			=> 8,
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-autorotate',
						'label'			=> __( 'Auto-rotate?', $this->plugin_slug ),
						'type'			=> 'checkbox',
						'description'	=> __( 'Start automatic rotation when page loads?', $this->plugin_slug ),
						'default'		=> false,
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-autorotate-interval',
						'label'			=> __( 'Auto-rotate interval', $this->plugin_slug ),
						'type'			=> 'text',
						'description'	=> __( 'How long to pause on each slide during auto-rotation.', $this->plugin_slug ),
						'input_suffix'	=> ' ' . __( 'milliseconds', $this->plugin_slug ),
						'default'		=> '5000',
						'width'			=> 10,
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
					array(
						'name'			=> 'ps-hover-behaviour',
						'label'			=> __( 'Behaviour when mouse hovers over slideshow', $this->plugin_slug ),
						'type'			=> 'radio',
						'options'		=> array(
							__( 'Pause', $this->plugin_slug )	=> 'pause',
							__( 'Stop', $this->plugin_slug )	=> 'stop'
						),
						'default'		=> 'pause',
						'scope'			=> $this->scope(),
						'capabilities'	=> array( 'edit_posts', 'edit_pages' )
					),
				)
			));

			slt_cf_register_box( $args );

		}

	}

	/**
	 * Initialize for output on the frontend
	 *
	 * @since	0.1
	 * @return	array
	 */
	public function output_init() {
		global $post, $_wp_additional_image_sizes;

		// Store whether we're on a page with a slideshow
		$this->slideshow_active = $this->in_scope();

		if ( $this->slideshow_active ) {

			// Get all custom fields for this post
			$this->custom_fields = slt_cf_all_field_values( 'post', $post->ID, array( 'ps-images' ) );

			// Get image size details
			$this->image_size = array();
			$this->image_size['name'] = isset( $this->custom_fields['ps-image-size'] ) ? $this->custom_fields['ps-image-size'] : 'large';

			if ( in_array( $this->image_size['name'], array( 'thumbnail', 'medium', 'large' ) ) ) {

				// Get WP image size details
				$this->image_size['width'] = get_option( $this->image_size['name'] . '_size_w' );
				$this->image_size['height'] = get_option( $this->image_size['name'] . '_size_h' );

			} else {

				// Get custom image size details
				$custom_image_size = $_wp_additional_image_sizes[ $this->image_size['name'] ];
				$this->image_size['width'] = $custom_image_size['width'];
				$this->image_size['height'] = $custom_image_size['height'];

			}

		}

	}

	/**
	 * Dynamic CSS on the frontend
	 *
	 * @since	0.1
	 * @return	array
	 */
	public function dynamic_css() {

		// Are we on a page with a slideshow?
		if ( $this->slideshow_active ) {

			// Work out proportions for height
			$proportion = $this->image_size['height'] / $this->image_size['width'];
			$half_proportional_height = $proportion * 100 / 2;

			// Output styles
			?>

			<style type="text/css">
				.ps-slideshow .ps-wrapper {
					width: <?php echo $this->image_size['width']; ?>px;
					max-width: 100%;
					height: 0;
					padding-top: <?php echo $half_proportional_height; ?>%;
					padding-bottom: <?php echo $half_proportional_height; ?>%;
					background-color: #<?php echo $this->custom_fields['ps-rotate-fade-colour']; ?>;
				}
			</style>

			<?php

		}

	}

	/**
	 * Output a slideshow's markup
	 *
	 * @since	0.1
	 * @return	array
	 */
	public function slideshow() {

		// Are we on a page with a slideshow?
		if ( $this->slideshow_active ) {

			// Initialize classes and data attributes for main container element
			$classes = array( 'ps-slideshow' );
			$data_attributes = array();

			// Rotate type
			if ( isset( $this->custom_fields['ps-rotate-type'] ) ) {

				$classes[] = 'ps-rotate-type-' . $this->custom_fields['ps-rotate-type'];
				$data_attributes['ps-rotate-type'] = $this->custom_fields['ps-rotate-type'];

				if ( $this->custom_fields['ps-rotate-type'] == 'fade' ) {

					// Fade type
					if ( isset( $this->custom_fields['ps-rotate-fade-type'] ) ) {

						$classes[] = 'ps-rotate-fade-type-' . $this->custom_fields['ps-rotate-fade-type'];
						$data_attributes['ps-rotate-fade-type'] = $this->custom_fields['ps-rotate-fade-type'];

						// Crossfade
						if ( $this->custom_fields['ps-rotate-fade-type'] != 'crossfade' && isset( $this->custom_fields['ps-rotate-fade-colour'] ) ) {
							$data_attributes['ps-fade-colour'] = $this->custom_fields['ps-rotate-fade-colour'];
						}

					}

				}

			}

			// Rotate speed
			if ( isset( $this->custom_fields['ps-rotate-speed'] ) && $this->custom_fields['ps-rotate-speed'] ) {
				$data_attributes['ps-rotate-speed'] = $this->custom_fields['ps-rotate-speed'];
			}

			// Autorotate
			if ( isset( $this->custom_fields['ps-autorotate'] ) && $this->custom_fields['ps-autorotate'] ) {
				$classes[] = 'ps-autorotate';
			}

			// Autorotate interval
			if ( isset( $this->custom_fields['ps-autorotate-interval'] ) && $this->custom_fields['ps-autorotate-interval'] ) {
				$data_attributes['ps-autorotate-interval'] = $this->custom_fields['ps-autorotate-interval'];
			}

			// Hover behaviour
			if ( isset( $this->custom_fields['ps-hover-behaviour'] ) ) {
				$classes[] = 'ps-hover-behaviour-' . $this->custom_fields['ps-hover-behaviour'];
				$data_attributes['ps-hover-behaviour'] = $this->custom_fields['ps-hover-behaviour'];
			}

			?>

			<div class="<?php echo implode( ' ', $classes ); ?>"<?php
				if ( $data_attributes ) {
					foreach ( $data_attributes as $key => $value ) {
						echo ' data-' . $key . '="' . esc_attr( $value ) . '"';
					}
				}
			?>>
				<div class="ps-wrapper">
					<ul class="ps-list">

						<?php $i = 1; ?>

						<?php foreach ( $this->custom_fields['ps-images'] as $image_id ) { ?>

							<?php

							// Initialize classes for li
							$li_classes = array( 'slide' );

							// Get image src and size
							$image_infos = wp_get_attachment_image_src( $image_id, $this->image_size['name'] );

							if ( $image_infos ) {

								// Construct markup
								$image_markup = apply_filters( 'ps_slideshow_image_markup', '<img src="' . $image_infos[0] . '" width="' . $image_infos[1] . '" height="' . $image_infos[2] . '" alt="' . get_the_title( $image_id ) . '">' );

								?>

								<li class="<?php echo implode( ' ', $li_classes ); ?>" id="slide-<?php echo $i; ?>">
									<?php echo $image_markup; ?>
								</li>

								<?php

								$i++;

							}

							?>

						<?php } ?>

					</ul>
				</div>
			</div>

		<?php

		}

	}

}