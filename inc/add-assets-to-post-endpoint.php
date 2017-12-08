<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'Add_Assets_to_Post_Endpoint' ) ) :
	class Add_Assets_to_Post_Endpoint {
		/**
		 * Not using constructor so tests can be implemented
		 */
		function init() {
			add_filter( 'rest_prepare_post', [ $this, 'add_assets_to_post' ], 10, 2 );
		}

		/**
		 * Attaches an asset to a post
		 *
		 * @param $data
		 * @param $post
		 *
		 * @return mixed
		 */
		function add_assets_to_post( $data, $post ) {
			$_data = $data->data;
			$attached_assets = IAM::get_assets( $post->ID );
			$assets          = [];
			foreach ( $attached_assets as $asset ) {
				$assets[] = [
					'id' => $asset->get_id(),
					'title' => $asset->get_title(),
				    'link' => $asset->get_permalink(),
				    'ext' => $asset->get_extension()
				];
			}
			$_data['assets'] = $assets;
			$data->data      = $_data;

			return $data;
		}
	}
endif;