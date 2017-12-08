<?php

/**
 * Class Asset
 */
class IAM_Asset {
	private $id;
	private $link;
	private $title;
	private $parent;
	private $hits;

	private $path;

	private $keys;
	private $begin_date;
	private $enabled;
	private $expires_date;
	private $obfuscated;
	private $requires_login;
	private $members_only;

	/**
	 * Asset constructor.
	 *
	 * @param        $asset_id
	 * @param string $iamSize
	 */
	public function __construct( $asset_id, $iamSize = '' ) {
		if ( intval( $asset_id ) ) {
			$this->id = $asset_id;
			$this->get_asset_meta();

			$post         = get_post( $this->id );
			$this->title  = $post->post_title;
			$this->parent = $post->post_parent;

			$this->load_path( $iamSize );
			$this->obfuscate_link( $iamSize );
			$this->calculate_hits();
		}
	}

	/**
	 * Loads asset's meta to object
	 */
	private function get_asset_meta() {
		$this->keys = array(
			'requires_login',
			'enabled',
			'obfuscated',
			'begin_date',
			'expires_date',
			'members_only',
		);

		foreach ( $this->keys as $key ) {
			$meta_key     = "IAM_$key";
			$this->{$key} = get_post_meta( $this->id, $meta_key, true );
		}
	}

	/**
	 * Loads path to Asset object
	 *
	 * @param string $iamSize
	 */
	private function load_path( $iamSize = '' ) {
		$upload_dir = wp_upload_dir();
		$this->path = $upload_dir['basedir'] . '/' . get_post_meta( $this->id, '_wp_attached_file', true );

		if ( $iamSize ) {
			$sizes = explode( 'x', $iamSize );
			if ( 2 == count( $sizes ) ) {
				$ext        = '.' . $this->get_extension();
				$thumb_path = str_replace( $ext, "-$iamSize$ext", $this->path );
				if ( is_readable( $thumb_path ) ) {
					$this->path = $thumb_path;
				}
			}
		}
	}

	/**
	 * Gets extension for file
	 *
	 * @return mixed
	 */
	public function get_extension() {
		return pathinfo( $this->path, PATHINFO_EXTENSION );
	}

	/**
	 * Obfuscates the asset link
	 *
	 * @param string $img_size
	 *
	 * @return string|void
	 */
	private function obfuscate_link( $img_size = '' ) {
		$size_add = '';

		$img_size_pieces = explode( 'x', $img_size );
		if ( 2 === count( $img_size_pieces ) ) {
			$size_add = '-' . implode( 'x', $img_size_pieces );
		}
		$hash       = alphaID( intval( $this->id ), false, 5, IAM::get_assets_hash() );
		$this->link = home_url( 'a/' . $hash . '/' . $this->get_filename( $size_add ) );

	}

	public function get_filename( $size_add = "" ) {
		return sanitize_file_name( $this->title ) . $size_add . '.' . $this->get_extension();
	}

	/**
	 * Get hit count for attachment
	 *
	 * @return mixed|string
	 */
	private function calculate_hits() {
		global $wpdb;
		$table_name = $wpdb->prefix . "assets_log";
		$hits       = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(count) as hits FROM $table_name WHERE aID = %d;", $this->id ) );

		$this->hits = is_null( $hits ) ? '0' : $hits;
	}

	/**
	 * Retreives assets hit count
	 *
	 * @return mixed
	 */
	public function get_hits() {
		return $this->hits;
	}

	/**
	 * Gets permalink for asset
	 *
	 * @return false|string
	 */
	public function get_permalink() {
		if ( $this->is_obfuscated() ) {
			return $this->link;
		} else {
			remove_filter( 'wp_get_attachment_url', array( 'IAM_Rewrite_Query', 'return_post_url' ) );
			$link = wp_get_attachment_url( $this->id );
			add_filter( 'wp_get_attachment_url', array( 'IAM_Rewrite_Query', 'return_post_url' ), 1, 2 );

			return $link;
		}
	}

	/**
	 * Checks if asset is obfuscated
	 *
	 * @return bool
	 */
	public function is_obfuscated() {
		return ( empty( $this->obfuscated ) && $this->bool( IAM::get_default_obfuscate() ) || $this->bool( $this->obfuscated ) );
	}

	/**
	 * Used because true/false values were saved as strings, for backwards compatability with the older plugin
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	private function bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Gets title of asset
	 *
	 * @return mixed
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Gets path of asset
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Gets formatted date of asset
	 *
	 * @return string formatted date
	 */
	public function get_date( $date_field ) {
		if ( ! in_array( $date_field, array( 'begin', 'expires' ) ) ) {
			return false;
		}

		$date_field_value = $this->{$date_field . "_date"};
		if ( is_numeric( $date_field_value ) ) {
			return date( 'F j, Y H:i', $date_field_value );
		}

		return $date_field_value;
	}

	/**
	 * Gets id of asset
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Checks basic settings if asset should be served
	 */
	public function can_serve_file() {
		return $this->is_enabled() && $this->has_begun() && ! $this->has_expired();
	}

	/**
	 * Checks if asset is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->bool( $this->enabled );
	}

	/**
	 * Checks if asset begin time has passed
	 *
	 * @return bool
	 */
	public function has_begun() {
		return $this->begin_date && current_time( 'timestamp' ) >= intval( $this->begin_date ) || ! $this->begin_date;
	}

	/**
	 * Checks if asset has expired
	 *
	 * @return bool
	 */
	public function has_expired() {
		return $this->expires_date && current_time( 'timestamp' ) > intval( $this->expires_date );
	}

	/**
	 * Checks if asset requires login for access
	 *
	 * @return bool
	 */
	public function requires_login() {
		return $this->bool( $this->requires_login );
	}

	/**
	 * Checks if asset is members only
	 *
	 * @return bool
	 */
	public function members_only() {
		return $this->bool( $this->members_only );
	}

	/**
	 * Sets meta
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return bool
	 */
	public function set( $key, $value ) {
		if ( ! in_array( $key, $this->keys ) ) {
			error_log( "$key not found" );

			return false;
		}

		$meta_key = "IAM_$key";

		delete_post_meta( $this->id, $meta_key );
		$sanitized = $this->sanitize( $key, $value );
		add_post_meta( $this->id, $meta_key, $sanitized, true );
	}

	/**
	 * Sanitizes data
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return string
	 */
	private function sanitize( $key, $value ) {
		switch ( $key ) {
			case 'enabled':
			case 'obfuscated':
			case 'requires_login':
				return $this->bool( $value ) ? 'true' : 'false';
			case 'begin_date':
			case 'expires_date':
				return $this->is_valid_date( $value ) ? $value : '';
			default:
				return $value;
		}

	}

	/**
	 * Checks if is valid date
	 *
	 * @param $date_string
	 *
	 * @return bool
	 */
	private function is_valid_date( $date_string ) {
		return $this->is_unix_timestamp( $date_string ) || (bool) strtotime( $date_string );
	}

	/**
	 * Checks if date is unix timestamp
	 *
	 * @param $date_string
	 *
	 * @return bool
	 */
	private function is_unix_timestamp( $date_string ) {
		return (string) (int) $date_string == $date_string;
	}

}