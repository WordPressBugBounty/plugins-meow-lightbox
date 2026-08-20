<?php

class Meow_MWL_Exif {

	// The GPS coordinates and the lens are cached in our own post meta. They used to be stored in
	// the attachment metadata, but that required a call to wp_update_attachment_metadata(), and
	// third-party plugins hooked on it (IPTC/EXIF mappers) were then rewriting the caption and the
	// description of the media, silently reverting the edits made by the user.
	const GEO_META_KEY = '_mwl_geo';
	const LENS_META_KEY = '_mwl_lens';

  static function gps2Num( $coordPart ) {
		$parts = explode( '/', $coordPart );
		if ( count( $parts ) <= 0 )
				return 0;
		if ( count( $parts ) == 1 )
				return $parts[0];
		return floatval( $parts[0] ) / floatval( $parts[1] );
	}

	static function convert_gps( $exifCoord, $hemi ) {

		if( is_array( $exifCoord ) ) {
			$degrees = count( $exifCoord ) > 0 ? Meow_MWL_Exif::gps2Num( $exifCoord[0] ) : 0;
			$minutes = count( $exifCoord ) > 1 ? Meow_MWL_Exif::gps2Num( $exifCoord[1] ) : 0;
			$seconds = count( $exifCoord ) > 2 ? Meow_MWL_Exif::gps2Num( $exifCoord[2] ) : 0;
			$flip = ( $hemi == 'W' or $hemi == 'S' ) ? -1 : 1;
			return $flip * ( $degrees + $minutes / 60 + $seconds / 3600 );
		}

		if ( is_float( $exifCoord ) ) {
			return $exifCoord;
		}


		return null;
	}

	// Sets the coordinates in the in-memory metadata (never written back to the database), so that
	// the rest of the plugin and the mwl_img_* filters keep finding them where they expect them.
	static function set_gps_in_meta( &$meta, $coordinates ) {
		if ( !is_array( $meta ) ) {
			$meta = array();
		}
		if ( !isset( $meta['image_meta'] ) || !is_array( $meta['image_meta'] ) ) {
			$meta['image_meta'] = array();
		}
		$meta['image_meta']['geo_coordinates'] = $coordinates;
		if ( empty( $coordinates ) ) {
			return false;
		}
		$parts = explode( ',', $coordinates );
		$meta['image_meta']['geo_latitude']  = $parts[0];
		$meta['image_meta']['geo_longitude'] = isset( $parts[1] ) ? $parts[1] : '';
		return $coordinates;
	}

	static function cache_gps_data( $id, &$meta, $coordinates ) {
		update_post_meta( $id, self::GEO_META_KEY, $coordinates );
		return self::set_gps_in_meta( $meta, $coordinates );
	}

	static function get_gps_data( $id, &$meta ) {
		// Legacy: the coordinates were stored in the attachment metadata by the previous versions.
		if ( isset( $meta['image_meta']['geo_coordinates'] ) ) {
			return empty( $meta['image_meta']['geo_coordinates'] ) ? false : $meta['image_meta']['geo_coordinates'];
		}

		// Already resolved: an empty value means the file has no usable GPS data.
		if ( metadata_exists( 'post', $id, self::GEO_META_KEY ) ) {
			return self::set_gps_in_meta( $meta, (string) get_post_meta( $id, self::GEO_META_KEY, true ) );
		}

		$file = get_attached_file( $id );
		$pp = $file ? pathinfo( $file ) : array();
		$extension = isset( $pp['extension'] ) ? strtolower( $pp['extension'] ) : '';

		// Nothing to read, and nothing to cache either (this check costs nothing).
		if ( !in_array( $extension, array( 'jpg', 'jpeg', 'tiff' ) ) ) {
			return self::set_gps_in_meta( $meta, "" );
		}

		$exif = @exif_read_data( $file );
		if ( !$exif || !isset( $exif["GPSLongitude"] ) || !isset( $exif['GPSLongitudeRef'] )
			|| !isset( $exif["GPSLatitude"] ) || !isset( $exif['GPSLatitudeRef'] ) ) {
			return self::cache_gps_data( $id, $meta, "" );
		}

		$latitude  = Meow_MWL_Exif::convert_gps( $exif["GPSLatitude"], $exif['GPSLatitudeRef'] );
		$longitude = Meow_MWL_Exif::convert_gps( $exif["GPSLongitude"], $exif['GPSLongitudeRef'] );

		if ( $latitude === null || $longitude === null ) {
			return self::cache_gps_data( $id, $meta, "" );
		}

		return self::cache_gps_data( $id, $meta, $latitude . ',' . $longitude );
	}

}

?>