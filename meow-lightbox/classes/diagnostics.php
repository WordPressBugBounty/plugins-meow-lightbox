<?php

/**
 * Diagnostics for the most common Meow Lightbox issue: the lightbox cannot reach the
 * REST API to fetch the EXIF of the images, and the visitor gets a 401 / 403 / 404.
 *
 * Everything here is written to be understandable by non-technical users: every check
 * returns a plain-language summary plus a short list of solutions. The results are
 * rendered by app/admin/components/Diagnostics.js, which also runs its own checks from
 * the browser (to catch CDN / firewall blocking that the server cannot see by itself).
 */
class Meow_MWL_Diagnostics {

	private $core;
	private $plugin_paths = null;

	// Plugins known to interfere with the REST API, either by blocking it or by caching
	// the pages (which makes the security nonce embedded in the HTML expire).
	private $known_plugins = [
		// Security / firewall: they may block or restrict the REST API.
		'wordfence'                              => [ 'Wordfence Security', 'security' ],
		'better-wp-security'                     => [ 'Solid Security (iThemes)', 'security' ],
		'all-in-one-wp-security-and-firewall'    => [ 'All In One WP Security', 'security' ],
		'sucuri-scanner'                         => [ 'Sucuri Security', 'security' ],
		'ninjafirewall'                          => [ 'NinjaFirewall', 'security' ],
		'wp-cerber'                              => [ 'WP Cerber Security', 'security' ],
		'defender-security'                      => [ 'Defender Security', 'security' ],
		'bulletproof-security'                   => [ 'BulletProof Security', 'security' ],
		'security-malware-firewall'              => [ 'Security & Malware scan by CleanTalk', 'security' ],
		'wp-hide-security-enhancer'              => [ 'WP Hide & Security Enhancer', 'security' ],
		'hide-my-wp'                             => [ 'Hide My WP', 'security' ],
		'wps-hide-login'                         => [ 'WPS Hide Login', 'security' ],
		'shield-security'                        => [ 'Shield Security', 'security' ],
		'malcare-security'                       => [ 'MalCare Security', 'security' ],
		// Plugins whose only job is to switch the REST API off.
		'disable-json-api'                       => [ 'Disable REST API', 'rest-blocker' ],
		'disable-rest-api'                       => [ 'Disable REST API', 'rest-blocker' ],
		'disable-wp-rest-api'                    => [ 'Disable WP REST API', 'rest-blocker' ],
		'disable-rest-api-and-require-jwt-oauth-authentication' => [ 'Disable REST API and Require JWT/OAuth', 'rest-blocker' ],
		'wp-rest-api-controller'                 => [ 'WP REST API Controller', 'rest-blocker' ],
		// Caching / optimization: they may serve a cached page with an expired nonce.
		'wp-rocket'                              => [ 'WP Rocket', 'cache' ],
		'litespeed-cache'                        => [ 'LiteSpeed Cache', 'cache' ],
		'w3-total-cache'                         => [ 'W3 Total Cache', 'cache' ],
		'wp-super-cache'                         => [ 'WP Super Cache', 'cache' ],
		'wp-fastest-cache'                       => [ 'WP Fastest Cache', 'cache' ],
		'cache-enabler'                           => [ 'Cache Enabler', 'cache' ],
		'breeze'                                 => [ 'Breeze', 'cache' ],
		'swift-performance-lite'                 => [ 'Swift Performance', 'cache' ],
		'swift-performance'                      => [ 'Swift Performance', 'cache' ],
		'hummingbird-performance'                => [ 'Hummingbird', 'cache' ],
		'sg-cachepress'                          => [ 'SiteGround Optimizer', 'cache' ],
		'nitropack'                              => [ 'NitroPack', 'cache' ],
		'wp-optimize'                            => [ 'WP-Optimize', 'cache' ],
		'powered-cache'                          => [ 'Powered Cache', 'cache' ],
		'comet-cache'                            => [ 'Comet Cache', 'cache' ],
		'flying-press'                           => [ 'FlyingPress', 'cache' ],
		'seraphinite-accelerator'                => [ 'Seraphinite Accelerator', 'cache' ],
		'wp-cloudflare-page-cache'               => [ 'Super Page Cache for Cloudflare', 'cache' ],
		'cloudflare'                             => [ 'Cloudflare', 'cache' ],
		'surge'                                  => [ 'Surge', 'cache' ],
		'autoptimize'                            => [ 'Autoptimize', 'optimizer' ],
		'perfmatters'                            => [ 'Perfmatters', 'optimizer' ],
		'wp-meteor'                              => [ 'WP Meteor', 'optimizer' ],
	];

	public function __construct( $core ) {
		$this->core = $core;
	}

	/*******************************************************************************
	 * ENTRY POINT
	 ******************************************************************************/

	public function run() {
		$checks = [];

		$permalinks = $this->check_permalinks();
		$checks[] = $permalinks;

		$plugins = $this->detect_plugins();

		$loopback = $this->check_loopback();
		$checks[] = $loopback;

		$rest_index = $this->check_rest_index( $loopback['status'] === 'error' );
		$checks[] = $rest_index;

		$endpoint = $this->check_lightbox_endpoint( $loopback['status'] === 'error' );
		$checks[] = $endpoint;

		$nonce = $this->check_nonce_and_cache( $plugins );
		$checks[] = $nonce;

		// A live anonymous request just succeeded, so nothing on the site is actually
		// blocking us. Whatever the next two checks find is then informative, not a problem:
		// plenty of well-behaved plugins hook the REST API without ever getting in our way.
		$endpoint_ok = $endpoint['status'] === 'ok';

		$filters = $this->check_rest_filters( $endpoint_ok );
		$checks[] = $filters;

		$plugins_check = $this->check_plugins( $plugins, $endpoint_ok );
		$checks[] = $plugins_check;

		$urls = $this->check_urls();
		$checks[] = $urls;

		$settings = $this->check_settings();
		$checks[] = $settings;

		$conclusion = $this->build_conclusion( [
			'permalinks' => $permalinks,
			'loopback'   => $loopback,
			'rest_index' => $rest_index,
			'endpoint'   => $endpoint,
			'nonce'      => $nonce,
			'filters'    => $filters,
			'plugins'    => $plugins_check,
			'urls'       => $urls,
		] );

		return [
			'conclusion'  => $conclusion,
			'checks'      => $checks,
			'environment' => $this->get_environment(),
		];
	}

	/*******************************************************************************
	 * CHECKS
	 ******************************************************************************/

	private function check_permalinks() {
		$structure = get_option( 'permalink_structure' );
		if ( empty( $structure ) ) {
			return $this->result( 'permalinks', 'Permalinks', 'error',
				'Your permalinks are set to "Plain". WordPress then serves the REST API from an unusual address, and many themes, caches and firewalls fail to reach it.',
				'permalink_structure is empty.',
				[
					[ 'text' => 'Go to Settings → Permalinks and choose any structure other than "Plain" (for example "Post name"), then save.', 'url' => admin_url( 'options-permalink.php' ), 'url_label' => 'Open Permalinks' ],
				]
			);
		}
		return $this->result( 'permalinks', 'Permalinks', 'ok',
			'Your permalinks are set up correctly.',
			'permalink_structure: ' . $structure
		);
	}

	private function check_loopback() {
		$response = wp_remote_get( home_url( '/' ), $this->request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->result( 'loopback', 'Server Self-Request', 'error',
				'Your server cannot open your own website. The tests below that are run "from your server" are therefore not reliable — trust the tests run from your browser instead.',
				'Error: ' . $response->get_error_message(),
				[
					[ 'text' => 'This is usually a hosting or firewall restriction (loopback requests blocked). Ask your host to allow the server to call its own domain.' ],
				]
			);
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 400 ) {
			return $this->result( 'loopback', 'Server Self-Request', 'warning',
				'Your server can reach your website, but the homepage answered with an error (' . $code . '). The tests run "from your server" may not be reliable.',
				'HTTP ' . $code . ' on ' . home_url( '/' )
			);
		}
		return $this->result( 'loopback', 'Server Self-Request', 'ok',
			'Your server can open your own website.',
			'HTTP ' . $code . ' on ' . home_url( '/' )
		);
	}

	private function check_rest_index( $loopback_failed ) {
		$url = get_rest_url();
		$response = wp_remote_get( $url, $this->request_args() );

		if ( is_wp_error( $response ) ) {
			return $this->result( 'rest_index', 'REST API (from your server)', $loopback_failed ? 'unknown' : 'error',
				$loopback_failed
					? 'Could not be tested, because your server cannot open your own website at all.'
					: 'Your server could not reach the WordPress REST API. Something (a firewall, a security plugin, or your host) is blocking it.',
				'Error: ' . $response->get_error_message() . ' — URL: ' . $url,
				$loopback_failed ? [] : [
					[ 'text' => 'Temporarily disable your security and firewall plugins, then run the diagnostic again to find the culprit.' ],
				]
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );
		$details = 'HTTP ' . $code . ' — URL: ' . $url;

		if ( $code === 401 || $code === 403 ) {
			return $this->result( 'rest_index', 'REST API (from your server)', 'error',
				'Your REST API answered "not allowed" (' . $code . '). It is most likely restricted to logged-in users, so your visitors cannot load the photo information.',
				$details . ' — ' . $this->extract_message( $json, $body ),
				[
					[ 'text' => 'If you use a plugin such as "Disable REST API", allow anonymous access to the meow-lightbox/v1 namespace (whitelist it).' ],
					[ 'text' => 'In your security plugin, whitelist the URL /wp-json/meow-lightbox/ for everybody.' ],
					[ 'text' => 'If you cannot change that, disable Dynamic Fetch below: the lightbox will still work, but images added after page load will show no EXIF.', 'action' => 'skip_dynamic_fetch' ],
				]
			);
		}

		if ( $code === 404 ) {
			return $this->result( 'rest_index', 'REST API (from your server)', 'error',
				'Your REST API was not found (404). It is either switched off, or your permalinks / rewrite rules are broken.',
				$details . ' — ' . $this->extract_message( $json, $body ),
				[
					[ 'text' => 'Go to Settings → Permalinks and click Save (this rebuilds the rewrite rules).', 'url' => admin_url( 'options-permalink.php' ), 'url_label' => 'Open Permalinks' ],
					[ 'text' => 'Deactivate any plugin whose purpose is to disable the REST API.' ],
					[ 'text' => 'If your host or your .htaccess blocks /wp-json/, ask them to allow it.' ],
				]
			);
		}

		if ( $code !== 200 || !is_array( $json ) ) {
			return $this->result( 'rest_index', 'REST API (from your server)', 'error',
				'Your REST API answered something unexpected (' . $code . '). It is probably intercepted by a plugin or by your host.',
				$details . ' — ' . substr( wp_strip_all_tags( (string) $body ), 0, 300 )
			);
		}

		$namespaces = isset( $json['namespaces'] ) && is_array( $json['namespaces'] ) ? $json['namespaces'] : [];
		if ( !in_array( 'meow-lightbox/v1', $namespaces, true ) ) {
			return $this->result( 'rest_index', 'REST API (from your server)', 'error',
				'Your REST API works, but the Meow Lightbox part of it is missing. A plugin is very likely removing it.',
				$details . ' — namespaces: ' . implode( ', ', $namespaces ),
				[
					[ 'text' => 'Look for a plugin that filters the REST API endpoints (a security plugin, or one that "whitelists" namespaces) and allow meow-lightbox/v1.' ],
				]
			);
		}

		return $this->result( 'rest_index', 'REST API (from your server)', 'ok',
			'Your REST API is reachable and Meow Lightbox is properly registered in it.',
			$details
		);
	}

	private function check_lightbox_endpoint( $loopback_failed ) {
		$solutions = [
			[ 'text' => 'Whitelist /wp-json/meow-lightbox/ in your security, firewall or cache plugin.' ],
			[ 'text' => 'Make sure the REST API is available to visitors who are not logged in.' ],
			[ 'text' => 'As a last resort, disable Dynamic Fetch: the lightbox keeps working, but images loaded after the page will show no EXIF.', 'action' => 'skip_dynamic_fetch' ],
		];

		// The lightbox reads with GET (ping) and writes with POST (regenerate_mwl_data). Some
		// firewalls only block POST, so both have to be tried to see the real picture.
		$attempts = [
			[
				'label' => 'GET',
				'url'   => get_rest_url( null, '/meow-lightbox/v1/ping' ),
				'args'  => $this->request_args(),
			],
			[
				'label' => 'POST',
				'url'   => get_rest_url( null, '/meow-lightbox/v1/regenerate_mwl_data' ),
				'args'  => array_merge( $this->request_args(), [
					'method'  => 'POST',
					'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ],
					'body'    => wp_json_encode( [ 'images' => [], 'page_url' => home_url( '/' ) ] ),
				] ),
			],
		];

		$details = [];
		foreach ( $attempts as $attempt ) {
			$response = wp_remote_request( $attempt['url'], $attempt['args'] );

			if ( is_wp_error( $response ) ) {
				return $this->result( 'endpoint', 'Lightbox Endpoint (from your server)', $loopback_failed ? 'unknown' : 'error',
					$loopback_failed
						? 'Could not be tested, because your server cannot open your own website at all.'
						: 'Your server could not reach the Meow Lightbox endpoint (' . $attempt['label'] . ').',
					implode( "\n", $details ) . ( $details ? "\n" : '' )
						. $attempt['label'] . ' ' . $attempt['url'] . ' — Error: ' . $response->get_error_message(),
					$loopback_failed ? [] : $solutions
				);
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body, true );
			$details[] = $attempt['label'] . ' ' . $attempt['url'] . ' — HTTP ' . $code;

			if ( $code !== 200 || !isset( $json['success'] ) || !$json['success'] ) {
				$details[ count( $details ) - 1 ] .= ' — ' . $this->extract_message( $json, $body );
				return $this->result( 'endpoint', 'Lightbox Endpoint (from your server)', 'error',
					'The Meow Lightbox endpoint refused an anonymous ' . $attempt['label'] . ' request (' . $code . '). This is the exact error your visitors get.',
					implode( "\n", $details ),
					$solutions
				);
			}
		}

		return $this->result( 'endpoint', 'Lightbox Endpoint (from your server)', 'ok',
			'A visitor who is not logged in can read the photo information. This is exactly what the lightbox needs.',
			implode( "\n", $details )
		);
	}

	private function check_nonce_and_cache( $plugins ) {
		$caching = array_values( array_filter( $plugins, function( $p ) {
			return $p['category'] === 'cache';
		} ) );
		$has_page_cache = !empty( $caching ) || ( defined( 'WP_CACHE' ) && WP_CACHE );
		$names = implode( ', ', array_map( function( $p ) { return $p['name']; }, $caching ) );

		if ( !$has_page_cache ) {
			return $this->result( 'cache', 'Page Cache', 'ok',
				'No page cache detected, so the security token sent by the lightbox should always be fresh.',
				'WP_CACHE: ' . ( defined( 'WP_CACHE' ) && WP_CACHE ? 'true' : 'false' )
			);
		}

		return $this->result( 'cache', 'Page Cache', 'warning',
			'A page cache is active' . ( $names ? ' (' . $names . ')' : '' ) . '. Cached pages keep the security token (nonce) of the moment they were saved. After about 24 hours that token expires, and the lightbox then receives a 403 error even though everything else is set up correctly. This is the most frequent cause of the problem.',
			'Detected: ' . ( $names ? $names : 'WP_CACHE constant is true' ),
			[
				[ 'text' => 'In your cache plugin, exclude /wp-json/ (and, if there is such an option, all REST API requests) from the cache.' ],
				[ 'text' => 'Lower the cache lifetime of your pages to less than 12 hours so the token never expires.' ],
				[ 'text' => 'If you use a CDN (Cloudflare, etc.), add a rule to bypass the cache for /wp-json/*.' ],
				[ 'text' => 'Or simply disable Dynamic Fetch: no request is made anymore, so no token can expire.', 'action' => 'skip_dynamic_fetch' ],
			]
		);
	}

	private function check_rest_filters( $endpoint_ok ) {
		$hooks = [ 'rest_authentication_errors', 'rest_pre_dispatch', 'rest_endpoints', 'rest_enabled', 'rest_jsonp_enabled' ];
		$core_callbacks = [
			'rest_cookie_check_errors',
			'rest_application_password_check_errors',
			'wp_is_rest_endpoint',
			'rest_filter_response_fields',
			'rest_send_allow_header',
		];

		$found = [];
		foreach ( $hooks as $hook ) {
			if ( empty( $GLOBALS['wp_filter'][ $hook ] ) ) {
				continue;
			}
			$callbacks = $GLOBALS['wp_filter'][ $hook ]->callbacks;
			foreach ( $callbacks as $entries ) {
				foreach ( $entries as $entry ) {
					$name = $this->callback_name( $entry['function'] );
					if ( in_array( $name, $core_callbacks, true ) ) {
						continue;
					}
					$origin = $this->callback_origin( $entry['function'] );
					if ( $origin['name'] === 'meow-lightbox' || $origin['name'] === 'wordpress' ) {
						continue;
					}
					$found[] = [
						'hook'     => $hook,
						'callback' => $name,
						'origin'   => $origin['name'],
						'file'     => $origin['file'],
					];
				}
			}
		}

		if ( empty( $found ) ) {
			return $this->result( 'filters', 'REST API Modifications', 'ok',
				'No plugin is modifying who is allowed to use the REST API.',
				'No third-party callback found on: ' . implode( ', ', $hooks )
			);
		}

		$origins = array_values( array_unique( array_map( function( $f ) { return $f['origin']; }, $found ) ) );
		$lines = array_map( function( $f ) {
			return $f['origin'] . ' → ' . $f['hook'] . ' → ' . $f['callback']
				. ( $f['file'] ? ' (' . $f['file'] . ')' : '' );
		}, $found );

		// Hooking the REST API is completely normal: WooCommerce, ACF, Polylang and many
		// others do it without ever bothering us. Only flag it when a request was actually
		// refused, otherwise this check would scare people whose site works perfectly.
		if ( $endpoint_ok ) {
			return $this->result( 'filters', 'REST API Modifications', 'ok',
				count( $found ) . ' plugin hook(s) were found on the REST API (' . implode( ', ', $origins ) . '). This is perfectly normal, and none of them is blocking Meow Lightbox: an anonymous request to our endpoints went through just fine.',
				implode( "\n", $lines )
			);
		}

		return $this->result( 'filters', 'REST API Modifications', 'warning',
			'Our endpoints are being refused, and these plugins change how the REST API behaves: ' . implode( ', ', $origins ) . '. One of them may be responsible.',
			implode( "\n", $lines ),
			[
				[ 'text' => 'Deactivate them one by one and run the diagnostic again, to see which one is responsible.' ],
				[ 'text' => 'Once identified, look in its settings for a way to allow the meow-lightbox/v1 endpoints for visitors who are not logged in.' ],
			]
		);
	}

	private function check_plugins( $plugins, $endpoint_ok ) {
		$risky = array_values( array_filter( $plugins, function( $p ) {
			return $p['category'] === 'security' || $p['category'] === 'rest-blocker';
		} ) );

		if ( empty( $risky ) ) {
			return $this->result( 'plugins', 'Security Plugins', 'ok',
				'No plugin known to block the REST API was found.',
				count( $plugins ) . ' known plugin(s) detected in total.'
			);
		}

		$names = implode( ', ', array_map( function( $p ) { return $p['name']; }, $risky ) );

		// Same reasoning as above: being installed is not the same as being in the way.
		if ( $endpoint_ok ) {
			return $this->result( 'plugins', 'Security Plugins', 'ok',
				'These plugins can restrict the REST API: ' . $names . '. Right now none of them is blocking Meow Lightbox, since an anonymous request to our endpoints went through just fine.',
				$names
			);
		}

		$blockers = array_filter( $risky, function( $p ) { return $p['category'] === 'rest-blocker'; } );

		return $this->result( 'plugins', 'Security Plugins', empty( $blockers ) ? 'warning' : 'error',
			( empty( $blockers )
				? 'Our endpoints are being refused, and these security plugins can restrict the REST API: '
				: 'A plugin whose purpose is to disable the REST API is active: ' ) . $names . '.',
			$names,
			[
				[ 'text' => 'In its settings, allow the REST API for visitors who are not logged in, or whitelist the namespace meow-lightbox/v1.' ],
				[ 'text' => 'To confirm it is the culprit, deactivate it for a minute and run the diagnostic again.' ],
			]
		);
	}

	private function check_urls() {
		$home = home_url();
		$site = site_url();
		$home_scheme = parse_url( $home, PHP_URL_SCHEME );
		$site_scheme = parse_url( $site, PHP_URL_SCHEME );

		if ( $home_scheme !== $site_scheme ) {
			return $this->result( 'urls', 'Site Addresses', 'warning',
				'Your WordPress Address and Site Address do not use the same protocol (one is http, the other is https). The browser then blocks the request the lightbox makes.',
				'Home: ' . $home . ' — Site: ' . $site,
				[
					[ 'text' => 'Go to Settings → General and make sure both addresses start with the same protocol (https is recommended).', 'url' => admin_url( 'options-general.php' ), 'url_label' => 'Open General Settings' ],
				]
			);
		}

		if ( is_ssl() && $home_scheme === 'http' ) {
			return $this->result( 'urls', 'Site Addresses', 'warning',
				'Your site is being served over https, but WordPress still thinks it is http. The lightbox request will be considered insecure and blocked by the browser.',
				'Home: ' . $home . ' — is_ssl(): true',
				[
					[ 'text' => 'Update the addresses in Settings → General so they start with https.', 'url' => admin_url( 'options-general.php' ), 'url_label' => 'Open General Settings' ],
				]
			);
		}

		return $this->result( 'urls', 'Site Addresses', 'ok',
			'Your site addresses are consistent.',
			'Home: ' . $home . ' — Site: ' . $site
		);
	}

	private function check_settings() {
		$skip = (bool) $this->core->get_option( 'skip_dynamic_fetch', false );
		$delay = (int) $this->core->get_option( 'rendering_delay', 300 );
		$details = 'Dynamic Fetch: ' . ( $skip ? 'disabled' : 'enabled' )
			. ' — Rendering Delay: ' . $delay . 'ms'
			. ' — Output Buffering: ' . ( $this->core->get_option( 'use_output_buffering', false ) ? 'on' : 'off' )
			. ' — Cache: ' . ( $this->core->get_option( 'disable_cache', false ) ? 'disabled' : 'enabled' );

		if ( $skip ) {
			return $this->result( 'settings', 'Lightbox Settings', 'ok',
				'Dynamic Fetch is disabled, so the lightbox never calls the REST API from your pages. This error cannot happen anymore (images loaded after the page will simply have no EXIF).',
				$details
			);
		}

		return $this->result( 'settings', 'Lightbox Settings', 'ok',
			'Dynamic Fetch is enabled: the lightbox asks your site for the EXIF of images that appear after the page has loaded.',
			$details,
			[
				[ 'text' => 'If you cannot fix the REST API, disabling Dynamic Fetch removes the error entirely.', 'action' => 'skip_dynamic_fetch' ],
			]
		);
	}

	/*******************************************************************************
	 * CONCLUSION
	 ******************************************************************************/

	private function build_conclusion( $c ) {
		// The most likely cause, in order of how decisive each signal is.
		if ( $c['permalinks']['status'] === 'error' ) {
			return $this->conclusion( 'error', 'Your permalinks are set to "Plain"',
				'WordPress serves its REST API from an unusual address when permalinks are "Plain", and the lightbox cannot reach it. This is almost certainly your problem, and it takes ten seconds to fix.',
				$c['permalinks']['solutions'] );
		}

		if ( $c['rest_index']['status'] === 'error' ) {
			return $this->conclusion( 'error', 'Your REST API is not available',
				$c['rest_index']['summary'] . ' The lightbox uses it to read the EXIF of your photos, so it fails.',
				$c['rest_index']['solutions'] );
		}

		if ( $c['endpoint']['status'] === 'error' ) {
			return $this->conclusion( 'error', 'The lightbox endpoint is blocked for visitors',
				'Your REST API works in general, but the Meow Lightbox part of it is refused when the request does not come from a logged-in user. A security or firewall plugin is the usual suspect.',
				$c['endpoint']['solutions'] );
		}

		if ( $c['plugins']['status'] === 'error' ) {
			return $this->conclusion( 'error', 'A plugin is disabling the REST API',
				$c['plugins']['summary'],
				$c['plugins']['solutions'] );
		}

		if ( $c['loopback']['status'] === 'error' ) {
			return $this->conclusion( 'warning', 'Your server cannot test itself',
				'Your server is not allowed to open your own website, so the checks above are inconclusive. The checks run from your browser (below) are the ones to trust here.',
				$c['loopback']['solutions'] );
		}

		if ( $c['urls']['status'] === 'warning' ) {
			return $this->conclusion( 'warning', 'Your site addresses are inconsistent',
				$c['urls']['summary'],
				$c['urls']['solutions'] );
		}

		// Nothing is broken. A page cache is still worth mentioning, because a cached page
		// can serve an expired security token later on — but that is a "if it ever happens"
		// note, not a problem, so the conclusion stays green.
		if ( $c['nonce']['status'] === 'warning' ) {
			return $this->conclusion( 'ok', 'Everything works',
				'The lightbox can read the EXIF of your photos, including for visitors who are not logged in. One thing to keep in mind: your pages are cached, and a cached page keeps the security token that was valid when it was saved. If that token expires (after about 24 hours), visitors start getting a 403 error. So if your visitors do report the problem while everything looks fine here, your cache is almost certainly the cause.',
				$c['nonce']['solutions'] );
		}

		return $this->conclusion( 'ok', 'Everything looks fine',
			'The lightbox can read the EXIF of your photos, both in general and for visitors who are not logged in. If your visitors still report an error, run the browser checks below from the page where the problem happens, and check your CDN.',
			[] );
	}

	/*******************************************************************************
	 * HELPERS
	 ******************************************************************************/

	private function result( $id, $label, $status, $summary, $details = '', $solutions = [] ) {
		return [
			'id'        => $id,
			'label'     => $label,
			'status'    => $status,
			'summary'   => $summary,
			'details'   => $details,
			'solutions' => $solutions,
		];
	}

	private function conclusion( $status, $title, $message, $solutions ) {
		return [
			'status'    => $status,
			'title'     => $title,
			'message'   => $message,
			'solutions' => $solutions,
		];
	}

	// Requests made without any cookie, so they behave like a visitor who is not logged in.
	private function request_args() {
		return [
			'timeout'     => 15,
			'redirection' => 5,
			'sslverify'   => false,
			'cookies'     => [],
			'headers'     => [ 'Accept' => 'application/json' ],
		];
	}

	private function extract_message( $json, $body ) {
		if ( is_array( $json ) && !empty( $json['message'] ) ) {
			return ( !empty( $json['code'] ) ? $json['code'] . ': ' : '' ) . $json['message'];
		}
		return substr( trim( wp_strip_all_tags( (string) $body ) ), 0, 300 );
	}

	private function get_active_plugins() {
		$active = (array) get_option( 'active_plugins', [] );
		if ( is_multisite() ) {
			$network = (array) get_site_option( 'active_sitewide_plugins', [] );
			$active = array_merge( $active, array_keys( $network ) );
		}
		return array_unique( $active );
	}

	private function detect_plugins() {
		$found = [];
		foreach ( $this->get_active_plugins() as $plugin_file ) {
			$folder = dirname( $plugin_file );
			if ( !isset( $this->known_plugins[ $folder ] ) ) {
				continue;
			}
			list( $name, $category ) = $this->known_plugins[ $folder ];
			$found[] = [ 'slug' => $folder, 'name' => $name, 'category' => $category ];
		}
		return $found;
	}

	private function callback_name( $callback ) {
		if ( is_string( $callback ) ) {
			return $callback;
		}
		if ( $callback instanceof Closure ) {
			return 'Closure';
		}
		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
			return $class . '::' . $callback[1];
		}
		if ( is_object( $callback ) ) {
			return get_class( $callback ) . '::__invoke';
		}
		return 'unknown';
	}

	// Real paths of the active plugin folders, so callbacks are still attributed correctly
	// when the plugins are symlinked (a very common setup on development installs).
	private function get_plugin_paths() {
		if ( $this->plugin_paths !== null ) {
			return $this->plugin_paths;
		}
		$paths = [];
		foreach ( $this->get_active_plugins() as $plugin_file ) {
			$folder = dirname( $plugin_file );
			if ( $folder === '.' || $folder === '' ) {
				continue;
			}
			$real = realpath( WP_PLUGIN_DIR . '/' . $folder );
			if ( $real ) {
				$paths[ wp_normalize_path( $real ) ] = $folder;
			}
		}
		$this->plugin_paths = $paths;
		return $paths;
	}

	// Returns [ 'name' => plugin folder (or 'wordpress' / 'theme' / 'unknown'), 'file' => path
	// inside that folder ], so a callback can be traced back to whoever registered it.
	private function callback_origin( $callback ) {
		$file = null;
		try {
			if ( $callback instanceof Closure ) {
				$file = ( new ReflectionFunction( $callback ) )->getFileName();
			}
			else if ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
				$file = ( new ReflectionMethod( $callback ) )->getFileName();
			}
			else if ( is_string( $callback ) && function_exists( $callback ) ) {
				$file = ( new ReflectionFunction( $callback ) )->getFileName();
			}
			else if ( is_array( $callback ) && count( $callback ) === 2 ) {
				$file = ( new ReflectionMethod( $callback[0], $callback[1] ) )->getFileName();
			}
			else if ( is_object( $callback ) && method_exists( $callback, '__invoke' ) ) {
				$file = ( new ReflectionMethod( $callback, '__invoke' ) )->getFileName();
			}
		}
		catch ( Throwable $e ) {
			$file = null;
		}

		if ( empty( $file ) ) {
			return [ 'name' => 'unknown', 'file' => null ];
		}

		$file = wp_normalize_path( $file );

		foreach ( [ WP_PLUGIN_DIR, WPMU_PLUGIN_DIR ] as $dir ) {
			$dir = wp_normalize_path( $dir );
			if ( strpos( $file, $dir . '/' ) === 0 ) {
				$relative = substr( $file, strlen( $dir ) + 1 );
				$parts = explode( '/', $relative );
				return count( $parts ) > 1
					? [ 'name' => $parts[0], 'file' => implode( '/', array_slice( $parts, 1 ) ) ]
					: [ 'name' => $relative, 'file' => null ];
			}
		}

		// Symlinked plugins live outside WP_PLUGIN_DIR once the path is resolved.
		foreach ( $this->get_plugin_paths() as $real_path => $folder ) {
			if ( strpos( $file, $real_path . '/' ) === 0 ) {
				return [ 'name' => $folder, 'file' => substr( $file, strlen( $real_path ) + 1 ) ];
			}
		}

		if ( strpos( $file, wp_normalize_path( get_theme_root() ) . '/' ) === 0 ) {
			return [ 'name' => 'theme', 'file' => basename( $file ) ];
		}
		if ( strpos( $file, wp_normalize_path( ABSPATH ) . 'wp-includes/' ) === 0
			|| strpos( $file, wp_normalize_path( ABSPATH ) . 'wp-admin/' ) === 0 ) {
			return [ 'name' => 'wordpress', 'file' => basename( $file ) ];
		}
		return [ 'name' => 'unknown', 'file' => $file ];
	}

	private function get_environment() {
		global $wp_version;
		$theme = wp_get_theme();
		return [
			'wordpress'  => $wp_version,
			'php'        => PHP_VERSION,
			'plugin'     => MWL_VERSION,
			'theme'      => $theme ? $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) : 'unknown',
			'home_url'   => home_url(),
			'site_url'   => site_url(),
			'rest_url'   => get_rest_url( null, '/meow-lightbox/v1/' ),
			'is_ssl'     => is_ssl(),
			'multisite'  => is_multisite(),
			'permalinks' => get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'plain',
			'exif'       => function_exists( 'exif_read_data' ),
		];
	}
}

?>
