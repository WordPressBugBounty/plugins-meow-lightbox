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
		$page_url = $request->get_param( 'page_url' );
		$cache_enabled = !$this->core->get_option( 'disable_cache' );

		$data = [];

		// Check page cache first if enabled
		$page_cache = null;
		if ( $cache_enabled && $page_url ) {
			$page_cache = $this->core->get_page_dynamic_cache( $page_url );
		}

		if( !empty( $images ) ) 
		{
			foreach( $images as $image ) {
				// Quick check: if image has an ID and it's in cache, use it directly
				$id = isset( $image['id'] ) ? intval( $image['id'] ) : 0;
				if ( $id && $page_cache && isset( $page_cache[$id] ) ) {
					$data[] = [
						'url' => $image['url'] ?? null,
						'id' => $id,
						'data' => $page_cache[$id]
					];
					continue;
				}

				// Need to resolve the ID (handles cases where ID is missing or invalid)
				$id = $this->core->resolve_image_id_from_data( $image );
				
				// Check cache again with resolved ID
				if ( $page_cache && isset( $page_cache[$id] ) ) {
					$data[] = [
						'url' => $image['url'] ?? null,
						'id' => $id,
						'data' => $page_cache[$id]
					];
					continue;
				}

				// Not in cache, fetch EXIF info
				$res = $this->core->get_exif_info( $id );
				$data[] = [
					'url' => $image['url'] ?? null,
					'id' => $id,
					'data' => $res
				];
			}
		}

		// Update the page-level dynamic cache with any new data
		if ( $cache_enabled && $page_url ) {
			$new_cache_entries = [];
			foreach ( $data as $item ) {
				if ( isset( $item['id'] ) && isset( $item['data'] ) && $item['data']['success'] !== false ) {
					// Only add if not already in cache
					if ( !$page_cache || !isset( $page_cache[$item['id']] ) ) {
						$new_cache_entries[$item['id']] = $item['data'];
					}
				}
			}
			
			if ( !empty( $new_cache_entries ) ) {
				$updated_cache = $page_cache ? array_replace( $page_cache, $new_cache_entries ) : $new_cache_entries;
				$this->core->set_page_dynamic_cache( $updated_cache, $page_url );
			}
		}

		$response = [ 'success' => true, 'data' => $data ];
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