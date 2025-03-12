<?php

require_once MWL_PATH . '/vendor/autoload.php';

use DiDom\Document;
use KubAT\PhpSimple\HtmlDomParser;

class Meow_MWL_Core {
	public $images = [];
	public $isInfinite = false;
	public $isObMode = false; // use OB on the whole page, or only go through the the_content ($renderingMode will be ignored)
	public $parsingEngine = 'HtmlDomParser'; // 'HtmlDomParser' (less prone to break badly formatted HTML) or 'DiDom' (faster)
	public $renderingMode = 'rewrite'; // 'replace' within the HTML or 'rewrite' the DOM completely
	public $imageSize = false;
	public $disableCache = false;
	private $isEnqueued = false;
	private $option_name = 'mwl_options';
	private $map = null;

	private $image_attributes = [ 'data-mwl-img-id', 'data-envira-item-id'];

	public function __construct() {
		MeowCommon_Helpers::is_rest() && new Meow_MWL_Rest( $this );

		add_filter( 'mgl_force_rewrite_mwl_data', array( $this, 'mgl_force_rewrite_mwl_data' ), 10, 1 );

		$this->isObMode = $this->get_option( 'output_buffering', $this->isObMode );

		if ( class_exists( 'MeowPro_MWL_Core' ) ) {
			$this->map = new MeowPro_MWL_Core( $this );
		}

		//$this->parsingEngine = 'HtmlDomParser';
		add_action( 'edit_attachment', array( $this, 'edit_attachment' ), 10, 1 );

		// The Lightbox should be completely off if the request is asynchronous
		$recent_common = method_exists( 'MeowCommon_Helpers', 'is_pagebuilder_request' );
		if ( MeowCommon_Helpers::is_asynchronous_request() || ( $recent_common && MeowCommon_Helpers::is_pagebuilder_request() ) ) {
			return;
		}

		$this->imageSize = $this->get_option( 'image_size', 'srcset' );
		$this->disableCache = $this->get_option( 'disable_cache' );
		$this->isInfinite = $this->get_option( 'infinite', false );
		$this->parsingEngine = $this->get_option( 'parsing_engine', $this->parsingEngine );
		$this->renderingMode = $this->isObMode ? 'rewrite' : $this->get_option( 'rendering_mode', $this->renderingMode );

		// Admin
		if ( is_admin() ) {
			load_plugin_textdomain( MWL_DOMAIN, false, MWL_PATH . '/languages' );
			new Meow_MWL_Admin( $this );
		}
		// Client
		else if ( !MeowCommon_Helpers::is_rest() ) {
			new Meow_MWL_Filters();
			add_action( 'mwl_lightbox_added', array( $this, 'lightbox_added' ), 10, 1 );

			// Try to take advantage of the Responsive Images feature of WP 4.4+ to make things faster.
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'wp_get_attachment_image_attributes' ), 10, 2 );

			// MODE: Output Buffering
			if ( $this->isObMode ) {
				// Read the whole page, and add the mwl_data in the head.
				add_action( 'init', array( $this, 'start_ob' ) );
				add_action( 'shutdown', array( $this, 'end_ob' ), 100 );
				$this->renderingMode = 'rewrite';

				// This doesn't work well with the conditional loading of the scripts, so we need to load it anyway.
				if( !$this->isEnqueued )
				{
					$this->log( '⚠️ The scripts were enqueued manually because the OB mode is enabled.' );
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

					if ($this->map !== null) {
						$this->map->lightbox_added();
					}
				}
				
			}
			// MODE: Standard (Responsive Images)
			else {
				// Analyze only page/post content and write the data in the footer.
				add_filter( 'the_content', array( $this, 'lightboxify' ), 20 );
				add_action( 'wp_footer', array( $this, 'wp_footer' ), 100 );
			}
			// All Mode: Need to handle the Meow Gallery (which is JS only)
			add_action( 'mgl_gallery_created', array( $this, 'meow_gallery_created' ), 10, 3 );
			add_action( 'mgl_collection_created', array( $this, 'meow_gallery_created' ), 10, 3 );
		}
	}

	public function can_access_settings() {
		return apply_filters( 'mwl_allow_setup', current_user_can( 'manage_options' ) );
	}

	public function can_access_features() {
		return apply_filters( 'mwl_allow_usage', current_user_can( 'upload_files' ) );
	}

	public function lightbox_added( $mediaId ) {
		if ( $this->isEnqueued ) {
			return;
		}
		$this->isEnqueued = true;
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		$this->log( '🟢 Lightbox Scripts sent.' );
		$this->enqueue_scripts();
	}

	function enqueue_scripts() {
		// CSS
    $physical_file = MWL_PATH . '/app/style.min.css';
    $cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : MWL_VERSION;
    wp_enqueue_style( 'leaflet-css', plugins_url( '/app/style.min.css', __DIR__ ), null, $cache_buster );

		// Touchswipe
		$physical_file = MWL_PATH . '/app/touchswipe.min.js';
    $cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : MWL_VERSION;
    // wp_enqueue_script( 'touchswipe-js', plugins_url( '/app/touchswipe.min.js', __DIR__ ), array('jquery'), $cache_buster, false );

		// JS
		$physical_file = MWL_PATH . '/app/lightbox.js';
    $cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : MWL_VERSION;
    wp_enqueue_script( 'mwl-build-js', plugins_url( '/app/lightbox.js', __DIR__ ), null, $cache_buster, false );

		wp_localize_script( 'mwl-build-js', 'mwl_settings',
			array(
				'api_url' => get_rest_url( null, '/meow-lightbox/v1/' ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
				'plugin_url' => plugin_dir_url(__FILE__),
				'settings' => array(
					'rtf_slider_fix' => $this->get_option( 'rtf_slider_fix', false ),
					'engine' => $this->get_option( 'engine', 'default' ),
					'backdrop_opacity' => $this->get_option( 'backdrop_opacity', 85 ),
					'theme' => $this->get_option( 'theme', 'dark' ),
					'orientation' => $this->get_option( 'orientation', 'auto' ),
					'selector' => $this->get_option( 'selector', '.entry-content, .gallery, .mgl-gallery, .wp-block-gallery,  .wp-block-image' ),
					'selector_ahead' => $this->get_option( 'selector_ahead', false ),
					'deep_linking' => $this->get_option( 'deep_linking', false ),

					'social_sharing' => $this->get_option( 'social_sharing', false ),
					'social_sharing_facebook' => $this->get_option( 'social_sharing_facebook', true ),
					'social_sharing_twitter' => $this->get_option( 'social_sharing_twitter', true ),
					'social_sharing_pinterest' => $this->get_option( 'social_sharing_pinterest', true ),

					'separate_galleries' => $this->get_option( 'separate_galleries', false ),
					'animation_toggle' => $this->get_option( 'animation_toggle', 'none' ),
					'animation_speed' => $this->get_option( 'animation_speed', 'normal' ),
					'low_res_placeholder' => $this->get_option( 'low_res_placeholder', false ),
					'wordpress_big_image' => $this->get_option( 'wordpress_big_image', false ),
					'right_click_protection' => !$this->get_option( 'right_click', false ),
					'magnification' => $this->get_option( 'magnification', true ),
					'anti_selector' => $this->get_option( 'anti_selector', '.blog, .archive, .emoji, .attachment-post-image, .no-lightbox' ),
					'preloading' => $this->get_option( 'preloading', false ),
					'download_link' => $this->get_option( 'download_link', false ),
					'caption_source' => $this->get_option( 'caption_origin', 'caption' ),
					'caption_ellipsis' => $this->get_option( 'caption_ellipsis', true ),
					'exif' => array(
						'title' => $this->get_option( 'exif_title', true ),
						'caption' => $this->get_option( 'exif_caption', true ),
						'camera' => $this->get_option( 'exif_camera', true ),
						'lens' => $this->get_option( 'exif_lens', false ),
						'date' => $this->get_option( 'exif_date', false ),
						'date_timezone' => $this->get_option( 'exif_date_timezone', false ),
						'shutter_speed' => $this->get_option( 'exif_shutter_speed', true ),
						'aperture' => $this->get_option( 'exif_aperture', true ),
						'focal_length' => $this->get_option( 'exif_focal_length', true ),
						'iso' => $this->get_option( 'exif_iso', true ),
						'keywords' => $this->get_option( 'exif_keywords', false ),
					),
					'slideshow' => array(
						'enabled' => $this->get_option( 'slideshow', false ),
						'timer' => $this->get_option( 'slideshow_timer', 3000 )
					),
					'map' => array(
						'enabled' => $this->get_option( 'map', false ),
						// 'engine' => $this->get_option( 'map_engine', 'googlemaps' ),
						// 'api_key' => $this->get_option( 'map_api_key', "" ),
						// 'style' => json_decode( $this->get_option( 'map_style', null ) )
						// 'position' => $this->get_option( 'map_position', 'bottom-right' ),
						// 'margin' => (int)$this->get_option( 'map_margin', 10 ),
						// 'size' => (int)$this->get_option( 'map_size', 70 )
					)
				)
			)
		);

		// Remove PrettyPhoto (Visual Composer's Lightbox)
		wp_enqueue_script( 'prettyphoto' );
		wp_deregister_script( 'prettyphoto' );
	}

	/*******************************************************************************
	 * RUNNING OPERATIONS
	 ******************************************************************************/

	function edit_attachment( $post_id ) {
		delete_transient( 'mwl_exif_' . $post_id . '_XX' );
		delete_transient( 'mwl_exif_' . $post_id . '_OO' );
		delete_transient( 'mwl_exif_' . $post_id . '_XO' );
		delete_transient( 'mwl_exif_' . $post_id . '_OX' );
	}

	/*******************************************************************************
	 * HELPERS
	 ******************************************************************************/

	function get_exif_info( $id ) {

		// The transient should only match a certain media entry with three given options, as only those three options
		// has an influence on the process that follows
		if ( !$this->disableCache ) {
			$transient_name = 'mwl_exif_' . $id . '_' . ( $this->get_option( 'map', false ) ? 'O' : 'X' ) .
				( $this->get_option( 'exif_lens', false ) ? 'O' : 'X' ) . ( $this->imageSize === 'srcset' ? '' : ( '_' . $this->imageSize ) );
			$info = get_transient( $transient_name );

			if ( is_array( $info ) && $info['data']['gps'] != 'N/A' && $this->get_option( 'map', false ) ) {
				if ($this->map !== null) {
						$this->map->lightbox_added();
					} else {
						$this->log( '❌ The pro version is not installed, but the map option is enabled.' );
					}
			}

			if ( $info ) {
				return $info;
			}
		}
		
		$message = null;

		$p = get_post( $id );
		if ( empty( $p ) ) {
			$message = "This attachment does not exist.";
			return array(
				'success' => false,
				'message' => $message
			);
		}

		$meta = wp_get_attachment_metadata( $id );
		if ( empty( $meta ) || $meta === false ) {
			$message = "No meta was found for this ID.";
			return array(
				'success' => false,
				'message' => $message
			);
		}

		if ( !array_key_exists( 'mime_type', $meta ) ) {
			$meta['mime_type'] = '';
		}
		$mime_type = $meta['mime_type'];

		$is_video = wp_attachment_is( 'video', $p ) || strpos( $mime_type, 'video' ) !== false;
		$is_image = wp_attachment_is_image( $p );

		if ( !$is_image && !$is_video ) {

			$message = "This attachment does not exist, is not an image, or is not a video.";
			return array(
				'success' => false,
				'message' => $message
			);
		}

		// Check for special metadata (gps, lens)
		
		if ( !is_array( $meta['image_meta'] ) ) {
			$meta['image_meta'] = array();
		}

		$gps = array_key_exists( 'geo_coordinates', $meta['image_meta'] ) ? $meta['image_meta']['geo_coordinates'] : null;
		if ( !isset( $gps ) && $this->get_option( 'map', false ) ) {
			$gps = Meow_MWL_Exif::get_gps_data( $id, $meta );
		}

		// Only load the map scripts if we need them
		if ( !empty( $gps ) && $this->get_option( 'map', false ) ) {
			if ($this->map !== null) {
				$this->map->lightbox_added();
			} else {
				$this->log( '❌ The pro version is not installed, but the map option is enabled.' );
			}
		}


		$displayLens = $this->get_option( 'exif_lens', false );
		if ( $displayLens && !isset( $meta['image_meta']['lens'] ) ) {
			$file = get_attached_file( $id );
			$pp = pathinfo( $file );
			$meta['image_meta']['lens'] = "";
			if ( in_array( strtolower( $pp['extension'] ), array( 'jpg', 'jpeg', 'tiff' ) ) ) {
				$exif = @exif_read_data( $file );
				if ( $exif && isset( $exif['UndefinedTag:0xA434'] ) )
					$meta['image_meta']['lens'] = empty( $exif['UndefinedTag:0xA434'] ) ? "" : $exif['UndefinedTag:0xA434'];
			}
			wp_update_attachment_metadata( $id, $meta );
		}

		// Prepare the final info variable containing the metadata
		$title = isset( $p->post_title ) ? $p->post_title : "";
		$caption =  isset( $p->post_excerpt ) ? $p->post_excerpt : "";
		$description = isset( $p->post_content ) ? $p->post_content : "";
		$dl = null;
		$file = null;
		$file_srcset = null;
		$file_sizes = null;

		if ( $this->imageSize === 'srcset' ) {
			$file = wp_get_attachment_url( $id );

			$file_srcset = wp_get_attachment_image_srcset( $id, 'full' );
			$file_sizes = wp_get_attachment_image_sizes( $id, 'full' );
			$dl = wp_get_attachment_image_url( $id, 'full' );
		}
		else {
			$arr = wp_get_attachment_image_src( $id, $this->imageSize );

			$file = $arr[0];
			$dl = $arr[0];
		}

		// Initialize metadata with an empty string if it does not exist.
		$image_meta_keys = [
			'geo_coordinates',
			'created_timestamp',
			'copyright',
			'camera',
			'lens',
			'aperture',
			'focal_length',
			'iso',
			'shutter_speed',
			'keywords',
		];
		foreach ($image_meta_keys as $image_meta_key) {
			if ( !isset( $meta['image_meta'][$image_meta_key] ) )
				$meta['image_meta'][$image_meta_key] = '';
		}

		// The way the date was calculated before November 2nd
		// $previous_version_date = "";
		// if ( isset( $meta['image_meta']['created_timestamp'] ) && $meta['image_meta']['created_timestamp'] != 0 ) {
		// 	$timestamp	= $meta['image_meta']['created_timestamp'];

		// 	$using_timezone = $this->get_option( 'exif_date_timezone', false );
		// 	if ( $using_timezone ) {
		// 		$timezone = wp_timezone_string();
			
		// 		try {
		// 			$timestamp = $meta['image_meta']['created_timestamp'] + (int)substr( $timezone, 0, 3 ) * 3600;
		// 		}
		// 		catch (Exception $e) {
		// 			error_log( 'Meow Lightbox: Couldn\'t use WordPress Timezone. ' . $e->getMessage() );
		// 		}
		// 	}
			
		// 	$date_format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );

		// 	// Make sure $timestamp is of type int
		// 	try {
		// 		$timestamp = (int)$timestamp;
		// 		$previous_version_date = date( $date_format, $timestamp );
		// 	}
		// 	catch (Exception $e) {
		// 		$timestamp_type = gettype( $timestamp );
		// 		error_log( "Meow Lightbox: Couldn't convert $timestamp_type timestamp to integer. " . $e->getMessage() );
		// 	}
		// }

		// The way the date is calculated since November 2nd
		$date = "";
		if ( isset( $meta['image_meta']['created_timestamp'] ) && $meta['image_meta']['created_timestamp'] != 0 ) {
			$timestamp = (int) $meta['image_meta']['created_timestamp'];
			$using_timezone = $this->get_option( 'exif_date_timezone', false );
			if ( $using_timezone ) {
				$timezone = wp_timezone_string();
				try {
					$timezone_offset = (int) substr( $timezone, 0, 3 );
					$timestamp += $timezone_offset * 3600;
				}
				catch (Exception $e) {
					$this->log( 'Meow Lightbox: Couldn\'t use WordPress Timezone. ' . $e->getMessage() );
				}
			}

			$date_format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
			$date = date( $date_format, $timestamp );
		}

		// Check if the Meow_MWL_Filters filters exist - Try with mwl_img_focal_length
		if ( !has_filter( 'mwl_img_focal_length' ) ){
			// Instantiate the Meow_MWL_Filters class
			$filters = new Meow_MWL_Filters();
		}

		// Prepare the download link and the file based on the big image option
		$big_image = $this->get_option( 'wordpress_big_image', false );
		if ( $big_image && function_exists( 'wp_get_original_image_url' ) ) {
			$dl = wp_get_original_image_url( $id );
			$file = wp_get_original_image_url( $id );
		}


		// Remove the camera from the lens if it is there, so it doesn't show twice
		$camera = $meta['image_meta']['camera'];
		$lense  = $displayLens ? $meta['image_meta']['lens'] : '';
		$lense  = str_replace( $camera, '', $lense );

		$info = array(
			'success' => true,
			'file' => $file,
			'file_srcset' => $file_srcset,
			'file_sizes' => $file_sizes,
			'dimension' => array( 
				'width' => isset($meta['width']) ? $meta['width'] : null, 
				'height' => isset($meta['height']) ? $meta['height'] : null 
			),
			'download_link' => apply_filters( 'mwl_download_link', $dl, $id, $meta ),
			'data' => array(
				'id' => (int)$id,
				'title' => apply_filters( 'mwl_img_title', $title, $id, $meta ),
				'caption' => apply_filters( 'mwl_img_caption', $caption, $id, $meta ),
				'description' => apply_filters( 'mwl_img_description', $description, $id, $meta ),
				'gps' => apply_filters( 'mwl_img_gps', $meta['image_meta']['geo_coordinates'],	$id, $meta ),
				'copyright' => apply_filters( 'mwl_img_copyright', $meta['image_meta']['copyright'], $id, $meta ),
				'camera' => apply_filters( 'mwl_img_camera',  $camera, $id, $meta ),
				'date' => apply_filters( 'mwl_img_date', $date, $id, $meta ),
				'lens' => apply_filters( 'mwl_img_lens', $lense, $id, $meta ),
				'aperture' => apply_filters( 'mwl_img_aperture', $meta['image_meta']['aperture'], $id, $meta ),
				'focal_length' => apply_filters( 'mwl_img_focal_length', $meta['image_meta']['focal_length'], $id, $meta ),
				'iso' => apply_filters( 'mwl_img_iso', $meta['image_meta']['iso'], $id, $meta ),
				'shutter_speed' => apply_filters( 'mwl_img_shutter_speed', $meta['image_meta']['shutter_speed'], $id, $meta ),
				'keywords' => apply_filters( 'mwl_img_keywords', $meta['image_meta']['keywords'], $id, $meta ),
			)
		);
		if ( !$this->disableCache ) {
			set_transient( $transient_name, $info, 3 * MONTH_IN_SECONDS );
		}

		return $info;
	}

	static function installed() {
		return true;
	}

	/******************************************************************************
		FUNCTIONS TO CLEAN AND GET THE MEDIA IDS FROM ATTACHMENT URLS
	******************************************************************************/

	// Clean the path from the domain and common folders
	// Originally written for the WP Retina 2x plugin
	function get_pathinfo_from_image_src( $image_src ) {
		$uploads = wp_upload_dir();
		$uploads_url = trailingslashit( $uploads['baseurl'] );
		if ( strpos( $image_src, $uploads_url ) === 0 )
			return ltrim( substr( $image_src, strlen( $uploads_url ) ), '/');
		else if ( strpos( $image_src, wp_make_link_relative( $uploads_url ) ) === 0 )
			return ltrim( substr( $image_src, strlen( wp_make_link_relative( $uploads_url ) ) ), '/');
		$img_info = parse_url( $image_src );
		return ltrim( $img_info['path'], '/' );
	}

	private function resolve_from_database( $url ) {
		global $wpdb;
		$pattern = '/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/';
		$url = preg_replace( $pattern, '', $url );
		$url = $this->get_pathinfo_from_image_src( $url );
		$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%s'", '%' . $url . '%' );
		$attachment = $wpdb->get_col( $query );
		return empty( $attachment ) ? null : $attachment[0];
	}

	private function convert_cdn( $url ) {
		$cdn_static_url = $this->get_option( 'cdn_support_dest', false );
		if( $cdn_static_url ) {
			$upload_folder = $this->get_option( 'cdn_support_src', '' );
			if ( empty( $upload_folder ) ) {
				$upload_folder = wp_upload_dir()['baseurl'];
			}
			$cdn_static_url = rtrim( $cdn_static_url, '/' );
			$upload_folder = rtrim( $upload_folder, '/' );

			$cdn_url = str_replace( $cdn_static_url, $upload_folder, $url );
			if ( filter_var( $cdn_url, FILTER_VALIDATE_URL ) ) {
				$url = $cdn_url;
			}else{
				$this->log( '❌ The CDN URL is not valid: ' . $cdn_url );
			}
		}
		return $url;
	}


	function resolve_image_id( $url ) {
		$url = $this->convert_cdn( $url );	
		return $this->resolve_from_database( $url );	
	}

	/******************************************************************************
		GENERATING PAGE PROCESS
	******************************************************************************/

	// When we are lucky (within a gallery), we can do this nicely, no need of the ob
	function wp_get_attachment_image_attributes( $attr, $attachment ) {
		$id = $attachment->ID;
		if ( !strpos( $attr['class'], 'wp-image-' . $id ) ) {
			$attr['class'] .= ' wp-image-' . $id;
		}
		if ( empty( $attr['data-mwl-img-id'] ) ) {
			if ( !in_array( $id, $this->images ) ) {
				array_push( $this->images, $id );
			}
			$attr['data-mwl-img-id'] = $id;
		}
		return $attr;
	}


	/**
	 * 	* How it works:
	 * 	The images needs to have the data-mwl-img-id attribute so we can link them to the mwl_data.
	 * 	This will also make sure we get the EXIF for the detected IDs ( to write the mwl_data ).
	 * 
	 *  The goal here is to find the media ID from the DOM element.
	 *      We first check if the element has a class with the ID.
	 * 	    Then we check if the element has an attribute with the ID. ( This can be done by other plugins, so we can add these attributes in $this->image_attributes )
 	 * 	    If not, we try to resolve the ID from the URL ( aggressive_resolve option ).

	 * Once we have the ID we can write data-mwl-img-id in the element. Then in the front we can link the element to the mwl_data.
	 * 
	 * @param $element
	 * @param $buffer
	 * 
	 */
	function lightboxify_element( $element, $buffer ) {
		$mediaId = null;
		$classes = '';
		// Capture the original HTML string for this element.
		$from = (string) $element;
	
		// Retrieve the anti-selectors and trim off any leading dots.
		$anti_selectors = $this->get_option( 'anti_selector', '.blog, .archive, .emoji, .attachment-post-image, .no-lightbox' );
		$anti_selectors = array_map( function( $selector ) {
			return ltrim( $selector, '.' );
		}, preg_split( '/[\s,]+/', $anti_selectors ) );
	
		// Get the classes from the element.
		if ( $this->parsingEngine === 'HtmlDomParser' ) {
			$classes = $element->class;
		} else {
			$classes = $element->attr('class');
		}
	
		// Check if any anti-selector is present in the classes.
		if ( $classes ) {
			foreach ( $anti_selectors as $anti_selector ) {
				if ( strpos( $classes, $anti_selector ) !== false ) {
					return $this->renderingMode === 'replace' ? false : $buffer;
				}
			}
		}
	
		// Check for a WordPress media ID in the classes.
		if ( is_string( $classes ) ) {

			if ( preg_match( '/wp-image-([0-9]{1,10})/i', $classes, $matches ) ) {
				$mediaId = $matches[1];
			}
			// Added check for video class ( we add this through the Meow Gallery plugin )
			elseif ( preg_match( '/wp-video-([0-9]{1,10})/i', $classes, $matches ) ) {
				$mediaId = $matches[1];
			}
		}
	
		// If no mediaId was found via classes, resolve it from the element’s URL.
		if ( !$mediaId ) {
			$src = null;
			$mglSrc = null;
			$url = null;
			$tag = '';
	
			// Get the tag name and the src attributes based on the parsing engine.
			if ( $this->parsingEngine === 'HtmlDomParser' ) {
				$tag = $element->tag;
				$src = $element->src;
				$mglSrc = $element->{'mgl-src'};
			} else if ( $this->parsingEngine === 'DiDom' ) {
				$tag = $element->tag;
				$src = $element->attr('src');
				$mglSrc = $element->attr('mgl-src');
			}
	
			// For video elements, if the src attribute is empty, check for a <source> tag.
			if ( $tag === 'video' && empty( $src ) ) {
				$sourceElements = $element->find('source');
				if ( !empty( $sourceElements) ) {
					if ( $this->parsingEngine === 'HtmlDomParser' ) {
						$src = $sourceElements[0]->src;
					} else {
						$src = $sourceElements[0]->attr('src');
					}
				}
			}
	
			// Decide which URL to use.
			if ( $this->isInfinite ) {
				$url = $mglSrc;
			}
			if ( empty( $url ) ) {
				$url = $src;
			}
	
			// If a URL was found, try to resolve a media ID.
			if ( !empty( $url ) ) {
				if ( $this->get_option( 'agressive_resolve' ) ) {
					// This is the same for both images and videos.
					$mediaId = $this->resolve_image_id( $url );

				} else {
					$filename = basename( $url );
					$this->log( '⚠️ No media ID could be found for ' . $filename . '. Enable the Agressive Resolve to force the ID detection.' );
				}
			}
		}
	
		// If still no mediaId, try to extract it from other element attributes.
		if ( !$mediaId ) {
			$attributes = apply_filters( 'mwl_image_attributes', $this->image_attributes );
			foreach ( $attributes as $attribute ) {
				if ( $this->parsingEngine === 'HtmlDomParser' ) {
					$mediaId = $element->{$attribute};
				} else {
					$mediaId = $element->attr( $attribute );
				}
				if ( $mediaId ) {
					break;
				}
			}
		}
	
		// If a mediaId was found, add it to the element.
		if ( $mediaId ) {

			if ( isset( $tag ) && $tag === 'video' ) {
				if ( $this->parsingEngine === 'HtmlDomParser' ) {
					$element->{'data-mwl-video-id'} = $mediaId;
				} else {
					$element->attr( 'data-mwl-video-id', $mediaId );
				}
			} else {
				if ( $this->parsingEngine === 'HtmlDomParser' ) {
					$element->{'data-mwl-img-id'} = $mediaId;
				} else {
					$element->attr( 'data-mwl-img-id', $mediaId );
				}
			}
	
			// Add the mediaId to the images array
			if ( !in_array( $mediaId, $this->images ) ) {
				array_push( $this->images, $mediaId );
			}
	
			if ( !$this->isEnqueued ) {
				$this->log( '⚡ The scripts were enqueued because the lightbox detected a media element.' );
				do_action( 'mwl_lightbox_added', $mediaId );
			}
			
			return $this->renderingMode === 'replace'
				? str_replace( trim( $from, "</> " ), trim( $element, "</> " ), $buffer )
				: 1;
		}
		
		return $this->renderingMode === 'replace' ? false : $buffer;
	}

	function lightboxify( $buffer ) {
		if ( !isset( $buffer ) || trim( $buffer ) === '' )
			return $buffer;
	
		// Initialize engine
		$html = null;
		$hasChanges = false;
		if ( $this->parsingEngine === 'HtmlDomParser' ) {
			$html = new HtmlDomParser();
			$html = $html->str_get_html( $buffer, true, true, DEFAULT_TARGET_CHARSET, false );
		}
	
		if( !$html ){
			$this->log( '⚠️ HtmlDomParser wasn\'t used. Trying DiDom.' );
			$html = new Document();
			if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) )
				$html->loadHtml( $buffer, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			else
				$html->loadHtml( $buffer, 0 );
		}
		
		if ( !$html ) {
			$this->log( '🪲 the DOM is empty.' );
			return $buffer;
		}
	
		$selector_ahead = $this->get_option( 'selector_ahead', false );
		if ( $selector_ahead ) {
	
			$classes = $this->get_option( 'selector', '.entry-content, .gallery, .mgl-gallery, .wp-block-gallery' );
			$classes = explode( ',', $classes );
	
			if ( empty( $classes ) ) {
				$this->log( '🪲 No classes were found in the ahead selector.' );
			}
		
			foreach ( $classes as $class ) {
	
				$class = trim( $class );
				// Include both img and video elements (and direct child selectors)
				$elements = $html->find("$class img, $class > img, $class video, $class > video");
				
				if ( empty( $elements ) ) {
					$this->log( '🪲 No elements were found in the ahead selector for the class: ' . $class );
				} else {
					$this->log( '🟢 Elements were found in the ahead selector for the class: ' . $class );
				}
				
				foreach ( $elements as $element ) {
					if ( $this->renderingMode === 'replace' ) {
						$buffer = $this->lightboxify_element( $element, $buffer );
					}
					else {
						$hasChanges = $this->lightboxify_element( $element, $buffer ) || $hasChanges;
					}
				}
			}
		} else {
			// Find both img and video elements in the DOM
			$elements = $html->find( 'img, video' );
			$this->log( '🟢 ' . count( $elements ) . ' elements found.' );
			if ( count( $elements ) === 0 ) {
				$this->log( '🪲 No elements were found in the DOM.' );
			}
			foreach ( $elements as $element ) {
				if ( $this->renderingMode === 'replace' ) {
					$buffer = $this->lightboxify_element( $element, $buffer );
				}
				else {
					$hasChanges = $this->lightboxify_element( $element, $buffer ) || $hasChanges;
				}
			}
		}
	
		if ( $this->isObMode ) {
			$matches = preg_split('/(<body.*?>)/i', $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	
			// If the body tag is not found, we can't do anything.
			if ( count( $matches ) < 2 ) {
				$this->log( '⚠️ Output Buffering was used on a non HTML response. Returning the current buffer.' );
				return $buffer;
			}
	
			$mwlData = $this->write_mwl_data( true );
	
			$head = isset( $matches[0] ) ? $matches[0] : '';
			$body = isset( $matches[1] ) ? $matches[1] : '';
			$footer = isset( $matches[2] ) ? $matches[2] : '';
			$html = $head . $body . $mwlData . $footer;
		}
	
		if ( $this->renderingMode === 'replace' ) {
			return $buffer;
		}
		return $hasChanges ? $html : $buffer;
	}

	public function meow_gallery_created( $atts, $image_ids, $layout ) {
		foreach ( $image_ids as $image_id ) {
			$image_id = (int)$image_id;
			if ( !in_array( $image_id, $this->images ) ) {
				array_push( $this->images, $image_id );
			}
		}
		if ( $this->isEnqueued ) {
			return;
		}

		$this->log( '⚡ The scripts have been added to the because a Meow Gallery was detected.' );
		do_action( 'mwl_lightbox_added', $image_ids );
	}

	public function mgl_force_rewrite_mwl_data( $images_ids ) {
		$images_ids = array_map( 'intval', $images_ids );
		$this->images = array_merge( $this->images, $images_ids );

		return $this->get_mwl_data();
	}

	function get_mwl_data() {
		$images_info = [];	
		foreach ( $this->images as $image ) {
			$images_info[$image] = $this->get_exif_info( $image );
		}
		return $images_info;
	}

	function write_mwl_data( $returnOnly = false ) {
		if ( !empty( $this->images ) ) {
			$images_info = $this->get_mwl_data();
			$html = '<script type="application/javascript" id="mwl-data-script">' . PHP_EOL;
			$html .= 'var mwl_data = ' . json_encode( $images_info ) . ';' . PHP_EOL;
			$html .= '</script>' . PHP_EOL;
			if ( $returnOnly ) {
				return $html;
			}
			echo $html;
		}
	}




	function wp_footer() {
		$this->write_mwl_data();
	}

	/******************************************************************************
		OB MODE
	******************************************************************************/

	function start_ob() {
		ob_start( array( $this, "lightboxify" ) );
	}

	function end_ob() {
		@ob_end_flush();
	}

	/******************************************************************************
		Options
	******************************************************************************/

	function get_option( $option_name, $default = null ) {
		$options = $this->get_all_options();
		return $options[ $option_name ] ?? $default;
	}

	function reset_options() {
		delete_option( $this->option_name );
	}

	function list_options() {
		return array(
			'backdrop_opacity' => 85,
			'theme' => 'dark',
			'download_link' => false,
			'image_size' => 'srcset',
			'deep_linking' => false,
			'low_res_placeholder' => false,
			'wordpress_big_image' => false,
			'agressive_resolve' => false,
			'cdn_support_src' => '',
			'cdn_support_dest' => '',
			'slideshow' => false,
			'slideshow_timer' => 3000,
			'map' => false,
			'exif_title' => true,
			'exif_caption' => true,
			'exif_camera' => true,
			'exif_lens' => true,
			'exif_shutter_speed' => true,
			'exif_aperture' => true,
			'exif_focal_length' => true,
			'exif_iso' => true,
			'exif_date' => false,
			'exif_date_timezone' => false,
			'exif_keywords' => false,
			'caption_origin' => 'caption',
			'caption_ellipsis' => true,
			'magnification' => true,
			'right_click' => false,
			
			'social_sharing' => false,
			'social_sharing_facebook' => true,
			'social_sharing_twitter' => true,
			'social_sharing_pinterest' => true,

			'separate_galleries' => false,
			'animation_toggle' => 'none',
			'animation_speed' => 'normal',
			'output_buffering' => true,
			'debug_logs' => false,
			'parsing_engine' => 'HtmlDomParser',
			'selector_ahead' => false,
			'selector' => '.entry-content, .gallery, .mgl-gallery, .wp-block-gallery,  .wp-block-image',
			'anti_selector' => '.blog, .archive, .emoji, .attachment-post-image, .no-lightbox',
			'map_engine' => 'googlemaps',
			'googlemaps_token' => '',
			'googlemaps_style' => '[]',
			'googlemaps_map_type' => 'satellite',
			'mapbox_token' => '',
			'mapbox_style' => '{"username":"", "style_id":""}',
			'maptiler_token' => '',
			'disable_cache' => '',
			'map_zoom_level' => 12,
			'engine' => 'default',
		);
	}

	function get_all_options() {
		$options = get_option( $this->option_name, null );
		$options = $this->check_options( $options );
		return $options;
	}

	// Upgrade from the old way of storing options to the new way.
	function check_options( $options = [] ) {
		$plugin_options = $this->list_options();
		$options = empty( $options ) ? [] : $options;
		$hasChanges = false;
		foreach ( $plugin_options as $option => $default ) {
			// The option already exists
			if ( isset( $options[$option] ) ) {
				continue;
			}
			// The option does not exist, so we need to add it.
			// Let's use the old value if any, or the default value.
			$options[$option] = get_option( 'mwl_' . $option, $default );
			delete_option( 'mwl_' . $option );
			$hasChanges = true;
		}
		if ( $hasChanges ) {
			update_option( $this->option_name , $options );
		}
		return $options;
	}

	function update_options( $options ) {
		if ( !update_option( $this->option_name, $options, false ) ) {
			return false;
		}
		$options = $this->sanitize_options();
		return $options;
	}


	// Validate and keep the options clean and logical.
	function sanitize_options() {
		$options = $this->get_all_options();
		// something to do
		return $options;
	}

	function get_logs() {
		$log_file_path = $this->get_logs_path();

		if ( !file_exists( $log_file_path ) ) {
			return "Empty log file.";
		}

		$content = file_get_contents( $log_file_path );
		$lines = explode( "\n", $content );
		$lines = array_filter( $lines );
		$lines = array_reverse( $lines );
		$content = implode( "\n", $lines );
		return $content;
	}

	function clear_logs() {
		$logPath = $this->get_logs_path();
		if ( file_exists( $logPath ) ) {
			unlink( $logPath );
		}

		$options = $this->get_all_options();
		$options['logs_path'] = null;
		$this->update_options( $options );
	}

	function get_logs_path() {
		$uploads_dir = wp_upload_dir();
		$uploads_dir_path = trailingslashit( $uploads_dir['basedir'] );

		$path = $this->get_option( 'logs_path' );

		if ( $path && file_exists( $path ) ) {
			// make sure the path is legal (within the uploads directory with the MWL_PREFIX and log extension)
			if ( strpos( $path, $uploads_dir_path ) !== 0 || strpos( $path, MWL_PREFIX ) === false || substr( $path, -4 ) !== '.log' ) {
				$path = null;
			} else {
				return $path;
			}
		}

		if ( !$path ) {
			$path = $uploads_dir_path . MWL_PREFIX . "_" . $this->random_ascii_chars() . ".log";
			if ( !file_exists( $path ) ) {
				touch( $path );
			}
			$options = $this->get_all_options();
			$options['logs_path'] = $path;
			$this->update_options( $options );
		}

		return $path;
	}

	function log( $data = null ) {
		if ( !$this->get_option( 'debug_logs', false ) ) { return false; }
		$log_file_path = $this->get_logs_path();
		$fh = @fopen( $log_file_path, 'a' );
		if ( !$fh ) { return false; }
		$date = date( "Y-m-d H:i:s" );
		if ( is_null( $data ) ) {
			fwrite( $fh, "\n" );
		}
		else {
			fwrite( $fh, "$date: {$data}\n" );
			//error_log( "[MEOW LIGHTBOX] $data" );
		}
		fclose( $fh );
		return true;
	}

	private function random_ascii_chars( $length = 8 ) {
		$characters = array_merge( range( 'A', 'Z' ), range( 'a', 'z' ), range( '0', '9' ) );
		$characters_length = count( $characters );
		$random_string = '';

		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}

		return $random_string;
	}

}

?>