<?php

class Meow_MWL_Rest
{
  private $core;
	private $namespace = 'meow-lightbox/v1';

	public function __construct( $core ) {
    $this->core = $core;

		// FOR DEBUG
		// For experiencing the UI behavior on a slower install.
		// sleep(1);
		// For experiencing the UI behavior on a buggy install.
		// trigger_error( "Error", E_USER_ERROR);
		// trigger_error( "Warning", E_USER_WARNING);
		// trigger_error( "Notice", E_USER_NOTICE);
		// trigger_error( "Deprecated", E_USER_DEPRECATED);

		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
	}

	function rest_api_init() {
		register_rest_route( $this->namespace, '/update_option', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_update_option' )
		) );
		register_rest_route( $this->namespace, '/all_settings', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_all_settings' )
		) );
		register_rest_route( $this->namespace, '/reset_options', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_reset_options' )
		) );
		register_rest_route( $this->namespace, '/reset_cache', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_reset_cache' )
		) );

		register_rest_route( $this->namespace, '/get_logs', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_get_logs' )
		) );
		register_rest_route( $this->namespace, '/clear_logs', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_clear_logs' )
		) );

		register_rest_route( $this->namespace, '/regenerate_mwl_data', array(
			'methods' => 'POST',
			'permission_callback' => "__return_true",
			'callback' => array( $this, 'rest_regenerate_mwl_data' )
		) );
  }


  	function rest_regenerate_mwl_data( $request ) {

		$images = $request->get_param( 'images' );
		$page_url = $request->get_param( 'page_url' ); // Get the page URL from the request

		$data = [];

		foreach( $images as $image ) {

			$found = false;

			// Try by direct ID.
			if ( !$found ) {
				$id = intval( $image['id'] );
				if ( $id ) {
					$is_attachment = get_post_type( $id ) === 'attachment';
					if ( $is_attachment ) {
						$found = true;
					}
				}
			}

			if( !empty( $image['url'] ) ) {
				
				// Try by cleaned URL (removing size suffixes).
				$pattern = '/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/';
				$clean_url = preg_replace( $pattern, '', $image['url'] );
				
				if ( !$found ) {
					$id = attachment_url_to_postid( $clean_url );
					if ( $id ) {
						$is_attachment = get_post_type( $id ) === 'attachment';
						if ( $is_attachment ) {
							$found = true;
						}
					}
				}

				// Try by attachment URL.
				if ( !$found ) {
					$id = attachment_url_to_postid( $image['url'] );
					if ( $id ) {
						$is_attachment = get_post_type( $id ) === 'attachment';
						if ( $is_attachment ) {
							$found = true;
						}
					}
				}

				// Try by resolving the image ID (handles thumbnails and CDN URLs).
				if ( !$found ) {
					$id = $this->core->resolve_image_id( $image['url'] );
					if ( $id ) {
						$is_attachment = get_post_type( $id ) === 'attachment';
						if ( $is_attachment ) {
							$found = true;
						}
					}
				}
			}


			// Not found, skip.
			if( !$found ) {
					$data[] = [
						'url' => $image['url'],
						'id' => $id,
						'data' => [ 'success' => false, 'message' => 'Image not found or is not an attachment.' ]
					];
					continue;
				}

				$res =  $this->core->get_exif_info( $id );

				$data[] = [
					'url' => $image['url'],
					'id' => $id,
					'data' => $res
				];
		}

		$response = [ 'success' => true, 'data' => $data ];
		
		// Update the page-level dynamic cache for the current page
		// This converts the response format to the mwl_data format
		if ( !$this->core->get_option( 'disable_cache' ) && $page_url ) {
			$page_cache = [];
			foreach ( $data as $item ) {
				if ( isset( $item['id'] ) && isset( $item['data'] ) && $item['data']['success'] !== false ) {
					$page_cache[$item['id']] = $item['data'];
				}
			}
			
			if ( !empty( $page_cache ) ) {
				// Get existing page cache and merge with new data
				$existing_cache = $this->core->get_page_dynamic_cache( $page_url );
				if ( $existing_cache ) {
					$page_cache = array_replace( $existing_cache, $page_cache );
				}

				$this->core->set_page_dynamic_cache( $page_cache, $page_url );
			}
		}

		return new WP_REST_Response( $response, 200 );
	}


	function rest_get_logs() {
		$logs = $this->core->get_logs();
		return new WP_REST_Response( [ 'success' => true, 'data' => $logs ], 200 );
	}

	function rest_clear_logs() {
		$this->core->clear_logs();
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	function rest_all_settings() {
		return new WP_REST_Response( [ 'success' => true, 'data' => $this->core->get_all_options() ], 200 );
	}

	function rest_reset_options() {
		$this->core->reset_options();
		return new WP_REST_Response( [ 'success' => true, 'options' => $this->core->get_all_options() ], 200 );
	}

	function rest_reset_cache() {
		global $wpdb;
		// Clear EXIF caches
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_mwl_exif_%'" );
		// Clear page-level dynamic content caches
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mwl_page_dynamic_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mwl_page_dynamic_%'" );
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	function rest_update_option( $request ) {
		try {
			$params = $request->get_json_params();
			$value = $params['options'];
			$options = $this->core->update_options( $value );
			$success = !!$options;
			$message = __( $success ? 'OK' : "Could not update options.", MWL_DOMAIN );
			return new WP_REST_Response([ 'success' => $success, 'message' => $message, 'options' => $success ? $options : null ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

}

?>