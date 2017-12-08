<?php

class IAM_Check_Asset_Restrictions {

	/**
	 * Check_Asset_Restrictions constructor.
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
		add_filter( 'pre_asset_serve', array( $this, 'asset_active_check' ), 1, 1 ); # check asset criteria
	}

	/**
	 * Checks assets settings if should be served
	 *
	 * @param $asset_id
	 *
	 * @return IAM_Asset|void
	 */
	public function asset_active_check( $asset_id ) {
		$asset = new IAM_Asset( $asset_id );
		if ( ! $asset->is_obfuscated() ) {
			return;
		}

		if ( ! $asset->can_serve_file() ) {
			$this->no_serve_message();
		}

		if ( ( $asset->requires_login() ) && ! is_user_logged_in() ) {
			wp_redirect( wp_login_url( $asset->get_permalink() ) );
			exit();
		}
	}

	/**
	 * Filter to update, change asset's no_serve message
	 *
	 * @param string $message
	 */
	protected function no_serve_message( $message = 'This file has expired.' ) {
		echo apply_filters( 'asset_no_serve_message', __( $message ) );
		exit();
	}
}