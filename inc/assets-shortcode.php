<?php

class IAM_Assets_Shortcode {
	/**
	 * Assets_Manager_Public constructor.
	 */
	public function __construct() {
	}

	/**
	 * Runs essential pieces of plugin to run within WordPress
	 */
	public function init() {
		$this->load_actions();
	}

	/**
	 * Registers WordPress actions
	 */
	private function load_actions() {
		add_shortcode( 'assets', [ $this, 'assets_shortcode' ] );

	}

	/**
	 * Shortcode that lists attached assets
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function assets_shortcode( $post_id = 0 ) {
		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		$content = '';
		$assets  = IAM::get_all_assets( $post_id );
		if ( count( $assets ) ) {
			$content = '<ul>';
			foreach ( $assets as $asset ) {
				if ( ! $asset->is_obfuscated() || $asset->can_serve_file() ) {
					$content .= '<li><a href="' . $asset->get_permalink() . '">' . $asset->get_title() . '</a> (' . $asset->get_extension() . ')</li>';
				}
			}
			$content .= '</ul>';
		}

		return $content;
	}

	/**
	 * Gets list of enabled attachments
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	private function get_enabled_attachments( $post_id ) {
		$attachments = get_posts( array(
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'meta_query'     => array(
				array(
					'key'     => 'enabled',
					'value'   => 'true',
					'compare' => 'IN'
				)
			),
			'order'          => 'ASC',
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'order',
			'posts_per_page' => - 1
		) );

		return $attachments;
	}

	/**
	 * Formats attachments as an unordered list
	 *
	 * @param $attachments
	 *
	 * @return string
	 */
	private function format_attachments_as_list( $attachments ) {
		$content = '<hr><ul class="assets-list">';
		foreach ( $attachments as $i => $attach ) {
			$asset = new IAM_Asset( $attach->ID );
			if ( $asset->can_serve_asset() ) {
				$content .= '<li><a href="' . $asset->get_meta( 'link' ) . '>' . $asset->get_meta( 'title' ) . '</a> <i>(' . $asset->get_meta( 'extension' ) . ')</i></li>';
			}
		}
		$content .= '</ul>';

		return $content;
	}
}