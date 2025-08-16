<?php
/*
Plugin Name: Meow Lightbox
Plugin URI: https://meowapps.com/plugin/meow-lightbox
Description: Beautiful Lightbox designed for photography, displaying EXIF data. Highly optimized for speed and elegance. You’ll love it!
Version: 5.3.3
Author: Jordy Meow
Author URI: https://meowapps.com
Text Domain: meow-lightbox
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( !defined( 'MWL_VERSION' ) ) {
  define( 'MWL_VERSION', '5.3.3' );
  define( 'MWL_PREFIX', 'mwl' );
  define( 'MWL_DOMAIN', ' meow-lightbox' );
  define( 'MWL_ENTRY', __FILE__ );
  define( 'MWL_PATH', dirname( __FILE__ ) );
  define( 'MWL_URL', plugin_dir_url( __FILE__ ) );
}

require_once( 'classes/init.php');

?>
