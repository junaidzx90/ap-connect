<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.fiverr.com/junaidzx90
 * @since      1.0.0
 *
 * @package    Ap_Connect
 * @subpackage Ap_Connect/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ap_Connect
 * @subpackage Ap_Connect/admin
 * @author     junaidzx90 <admin@easeare.com>
 */
class Ap_Connect_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ap-connect-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ap-connect-admin.js', array( 'jquery' ), $this->version, false );

	}

	function junu_menupage_register(){
		add_menu_page( 'APConnect', 'APConnect', 'manage_options', 'appconect', [$this,'appconect_menupage_display'], 'dashicons-xing', 45 );
		
		add_settings_section('junu_appconnect_settings', 'Match Field Settings', '', 'junu_appconnect_settings_page');
		add_settings_field('appconect_apikey_view_page', 'SECURE KEY', array($this, 'appconect_apikey_view_page_cb'), 'junu_appconnect_settings_page', 'junu_appconnect_settings');
        register_setting('junu_appconnect_settings', 'appconect_apikey_view_page');
	}
	// menupage callback
	function appconect_menupage_display(){
		?>
		<h3 class="ttl">APPOST SECURE KEY</h3>
		<hr>
		<form action="options.php" method="post" id="junu_appost_settings">
		<table>
		<?php
			settings_fields( 'junu_appconnect_settings' );
			do_settings_fields( 'junu_appconnect_settings_page', 'junu_appconnect_settings' );
		?>
		</table>
		<?php submit_button(); ?>
		</form>
		<?php
	}
	// Input view
	function appconect_apikey_view_page_cb(){
		?>
		<input type="password" class="securekey" name="appconect_apikey_view_page" value="<?php echo get_option('appconect_apikey_view_page'); ?>" placeholder="Secure Key">
		<?php
	}

	function junu_insert_attachment($url, $parent_post_id = null) {

		if( !class_exists( 'WP_Http' ) )
		include_once( ABSPATH . WPINC . '/class-http.php' );

		$http = new WP_Http();
		$response = $http->request( $url );
		if( $response['response']['code'] != 200 ) {
			return false;
		}

		$upload = wp_upload_bits( basename($url), null, $response['body'] );
		if( !empty( $upload['error'] ) ) {
			return false;
		}

		$file_path = $upload['file'];
		$file_name = basename( $file_path );
		$file_type = wp_check_filetype( $file_name, null );
		$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
		$wp_upload_dir = wp_upload_dir();

		$post_info = array(
			'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
			'post_mime_type' => $file_type['type'],
			'post_title'     => $attachment_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );

		// Include image.php
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id,  $attach_data );
		
		return $attach_id;
	}

	
	/**
	 * POST REQU API
	 */
	function junu_airpost_create_post(){
		//GET PROJECT MEDIA
		register_rest_route( 'apc/v1','createpost',[
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this,'junu_request_post_to_posts'),
			'permission_callback' => '__return_true'
		]);
	}

	function junu_request_post_to_posts($param){
		
		if($param['appost_key'] == get_option( 'appconect_apikey_view_page' )){
			
			$post_author = $param['post_author'];
			$post_title = $param['post_title'];
			$post_excerpt = $param['post_excerpt'];
			$post_content = $param['post_content'];
			$post_status = $param['post_status'];
			$categories = $param['categories'];
			$tag_list = $param['tag_list'];
			$attachment_url = $param['attachment_url'];
			$categories = json_decode($categories,true);

			if (file_exists (ABSPATH.'/wp-admin/includes/taxonomy.php')) {
				require_once (ABSPATH.'/wp-admin/includes/taxonomy.php'); 
			}

			$new_cat_IDS = [];
			foreach($categories as $cat){
				$args = array('cat_name' => $cat,'taxonomy' => 'category', 'category_description' => '','', 'category_parent' => '');
				$cat_id = wp_insert_category( $args );
				if(!$cat_id){
					$new_cat_IDS[] = get_cat_ID( $cat );
				}else{
					$new_cat_IDS[] = $cat_id;
				}
			}
			
			$args = array(
				'post_author' => $post_author,
				'post_title' => $post_title,
				'post_excerpt' => $post_excerpt,
				'post_content' => $post_content,
				'post_status' => $post_status,
				'post_category' => $new_cat_IDS
			);
			
			$post_id = wp_insert_post( $args );
			wp_set_post_tags( $post_id, $tag_list, true );
			$thumb_id = $this->junu_insert_attachment($attachment_url,$post_id);
			set_post_thumbnail( $post_id, $thumb_id);
			//plugin is activated
			update_post_meta($post_id, '_yoast_wpseo_title', $param['wpseo_title']);
			update_post_meta($post_id, '_yoast_wpseo_metadesc', $param['wpseo_metadesc']);
			update_post_meta($post_id, '_yoast_wpseo_linkdex', $param['yoast_wpseo_linkdex']);
			update_post_meta($post_id, '_yoast_wpseo_focuskw', $param['wpseo_focuskw']);
			update_post_meta($post_id, '_yoast_wpseo_content_score', $param['wpseo_content_score']);
			update_post_meta($post_id, '_yoast_wpseo_is_cornerstone', $param['wpseo_is_cornerstone']);
			update_post_meta($post_id, '_yoast_wpseo_estimated-reading-time-minutes', $param['wpseo_estimated-reading-time-minutes']);
			update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', $param['wpseo_meta-robots-noindex']);
			update_post_meta($post_id, '_yoast_wpseo_meta-robots-adv', $param['wpseo_meta-robots-adv']);
			update_post_meta($post_id, '_yoast_wpseo_bctitle', $param['wpseo_bctitle']);
			update_post_meta($post_id, '_yoast_wpseo_canonical', $param['wpseo_canonical']);
			update_post_meta($post_id, '_yoast_wpseo_schema_page_type', $param['wpseo_schema_page_type']);
			update_post_meta($post_id, '_yoast_wpseo_schema_article_type', $param['wpseo_schema_article_type']);
			update_post_meta($post_id, '_yoast_wpseo_opengraph-title', $param['wpseo_opengraph-title']);
			update_post_meta($post_id, '_yoast_wpseo_opengraph-description', $param['wpseo_opengraph-description']);
			update_post_meta($post_id, '_yoast_wpseo_opengraph-image', $param['wpseo_opengraph-image']);
			update_post_meta($post_id, '_yoast_wpseo_opengraph-image-id', $param['wpseo_opengraph-image-id']);
			update_post_meta($post_id, '_yoast_wpseo_twitter-title', $param['wpseo_twitter-title']);
			update_post_meta($post_id, '_yoast_wpseo_twitter-description', $param['wpseo_twitter-description']);
			update_post_meta($post_id, '_yoast_wpseo_twitter-image', $param['wpseo_twitter-image']);
			update_post_meta($post_id, '_yoast_wpseo_twitter-image-id', $param['wpseo_twitter-image-id']);
			update_post_meta($post_id, '_yoast_wpseo_primary_category', $param['wpseo_primary_category_term']);
		}
		
		$insertedurl = get_the_permalink( $parent_post_id );
		return '$insertedurl';
	}

}
