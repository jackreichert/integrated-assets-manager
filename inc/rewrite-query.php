<?php

class IAM_Rewrite_Query {
	static function return_post_url( $url, $post_id ) {
		$asset = new IAM_Asset( $post_id );
		if ( $asset->is_obfuscated() ) {
			return $asset->get_permalink();
		} else {
			return $url;
		}
	}

	function init() {
		$this->load_actions();
	}

	function load_actions() {
		add_action( 'init', array( $this, 'add_iamSize_rewrite_rule' ) );
		add_action( 'init', array( $this, 'add_asset_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_asset_query_var' ) );
		add_filter( 'wp_get_attachment_url', array( $this, 'return_post_url' ), 1, 2 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'add_size_to_attachment_src' ), 10, 4 );
	}

	function add_size_to_attachment_src( $image, $attachment_id, $size, $icon ) {
		$sizes = $this->get_image_sizes();

		if ( is_array( $size ) && 1 < count( $size ) ) {
			$size_array = array( $size[0], $size[1] );
			$crop       = isset( $image[3] ) ? $image[3] : 1;
		} elseif ( ! is_array( $size ) && isset( $size ) && isset( $sizes[ $size ] ) ) {
			$size_array = array( $sizes[ $size ]['width'], $sizes[ $size ]['height'] );
			$crop       = $sizes[ $size ]['crop'];
		} else {
			$size_array = array( $image[1], $image[2] );
			$crop       = $image[3];
		}
		$size_x = implode( 'x', $size_array );

		$asset = new IAM_Asset( $attachment_id, $size_x );
		if ( $asset->is_obfuscated() ) {
			$image = array( $asset->get_permalink(), $size_array[0], $size_array[1], $crop );
		}

		return $image;
	}

	private function get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}

		return $sizes;
	}

	function add_asset_query_var( $vars ) {
		$vars[] = "iam";
		$vars[] = "iamSize";

		return $vars;
	}

	function add_asset_rewrite_rule() {
		$rule = '^a\/([A-Za-z0-9]+)\/?';
		add_rewrite_rule( $rule, 'index.php?iam=$matches[1]', 'top' );

		$this->flush_rules_if_not_set( $rule );
	}

	private function flush_rules_if_not_set( $rule = '' ) {
		if ( $rule ) {
			$rules = get_option( 'rewrite_rules' );
			if ( ! isset( $rules[ $rule ] ) ) {
				global $wp_rewrite;
				$wp_rewrite->flush_rules();
			}
		}
	}

	function add_iamSize_rewrite_rule() {
		$rule = '^a\/([A-Za-z0-9]+).+-([0-9]+x[0-9]+)';
		add_rewrite_rule( $rule, 'index.php?iam=$matches[1]&iamSize=$matches[2]', 'top' );

		$this->flush_rules_if_not_set( $rule );
	}
}