<?php

class IAM_Ajax_Endpoints {
	/**
	 * Not using constructor so tests can be implemented
	 */
	public function init() {
		$this->load_actions();
	}

	/**
	 * Registers WordPress actions
	 */
	private function load_actions() {
		add_action( 'wp_ajax_IAM_upload', array( $this, 'post_update_get_asset_data' ) );
		add_action( 'wp_ajax_IAM_settings', array( $this, 'get_new_global_hash' ) );
		add_action( 'wp_ajax_IAM_attach_to_post', array( $this, 'attach_asset_to_post' ) );
	}

	/**
	 * Gets asset data for ajax calls
	 */
	function post_update_get_asset_data() {
		$nonce = $_POST['iamUploadNonce'];
		if ( ! wp_verify_nonce( $nonce, 'IAM_upload-nonce' ) ) {
			die ( 'Busted!' );
		}

		$asset_id = intval( $_POST['asset_id'] );
		// generate the response
		$response['url'] = wp_get_attachment_url( $asset_id );
		$response['title'] = get_the_title( $asset_id );

		$response['status'] = ( IAM::get_default_obfuscate() ) ? 'active' : 'disabled';

		// response output
		header( "Content-Type: application/json" );
		echo json_encode( $response );
		exit;
	}

	/**
	 * Generates new global hash
	 */
	public function get_new_global_hash() {
		$nonce = $_POST['amwpSettingsNonce'];
		if ( ! wp_verify_nonce( $nonce, 'amwp_settings-nonce' ) ) {
			die ( 'Busted!' );
		}

		$response = wp_generate_password( 64, false, false );

		header( "Content-Type: application/json" );
		echo json_encode( $response );
		exit;

	}

}