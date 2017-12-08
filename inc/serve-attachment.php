<?php

class IAM_Serve_Attachment {
	/**
	 * Assets_Manager_Serve_Attachment constructor.
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
		add_action( 'pre_get_posts', array( $this, 'main' ), 1, 1 );
	}

	/**
	 * Checks via action if file should be served and serves file
	 *
	 * @param $wp_query
	 */
	public function main( $wp_query ) {
		if ( is_admin() ) {
			return;
		}

		if ( isset( $wp_query->query ) && isset( $wp_query->query['iam'] ) ) {
			$asset_id = alphaID( $wp_query->query['iam'], true, 5, IAM::get_assets_hash() );
		} else {
			return;
		}

		// checks to see if asset is active, tie in plugins can hook here
		do_action( 'pre_asset_serve', $asset_id );

		if ( headers_sent() ) {
			die( 'Headers Sent' );
		}
;
		$iamSize = isset( $wp_query->query['iamSize'] ) ? $wp_query->query['iamSize'] : '';
		$asset   = new IAM_Asset( $asset_id, $iamSize );
		if ( ! empty( $asset->get_path() ) && is_readable( $asset->get_path() ) ) {
			$this->serve_file( $asset );
		} else {
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}
	}

	/**
	 * Serves the file
	 *
	 * @param IAM_Asset $asset
	 */
	public function serve_file( IAM_Asset $asset ) {
		$filetype            = wp_check_filetype( basename( $asset->get_path() ) );
		$content_disposition = $this->determine_content_disposition( $filetype );
		$this->send_headers( $filetype, $content_disposition, $asset->get_filename(), $asset->get_path() );

		ob_clean();
		flush();

		$handle = fopen( $asset->get_path(), "rb" );
		while ( ! feof( $handle ) ) {
			echo fread( $handle, 512 );
		}
		fclose( $handle );

		exit();
	}

	/**
	 * If Microsoft attachment, should serve as attachment
	 *
	 * @param $filetype
	 *
	 * @return string
	 */
	public function determine_content_disposition( $filetype ) {
		if ( strpos( $filetype['type'], 'msword' ) > 0 || strpos( $filetype['type'], 'ms-excel' ) || strpos( $filetype['type'], 'officedocument' ) ) {
			$content_disposition = 'attachment';
		} else {
			$content_disposition = 'inline';
		}

		return $content_disposition;
	}

	/**
	 * Sends headers
	 *
	 * @param $filetype
	 * @param $content_disposition
	 */
	public function send_headers( $filetype, $content_disposition, $title, $path ) {
		$pathinfo = pathinfo( $title );
		$filename = ( isset( $pathinfo['extension'] ) && ! empty( $pathinfo['extension'] ) ) ? $title : "$title.{$filetype['ext']}";
		header( "HTTP/1.1 200 OK" );
		header( "Pragma: public" );
		header( "Expires: 0" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Cache-Control: private", false );
		header( "Content-Description: File Transfer" );
		header( "Content-Type: " . $filetype['type'] );
		header( 'Content-Disposition: ' . $content_disposition . '; filename="' . $filename . '"' );
		header( "Content-Transfer-Encoding: binary" );
		header( "Content-Length: " . (string) ( filesize( $path ) ) );
	}

	/**
	 * Get's filename and path
	 *
	 * @return array
	 */
	public function get_file_location() {
		$upload_dir = wp_upload_dir();
		$filepath   = get_post_meta( $this->attachment_id, '_wp_attached_file', true );
		$path       = $upload_dir['basedir'] . '/' . $filepath;
		$filename   = end( explode( '/', $filepath ) );

		return array( $path, $filename );
	}
}
