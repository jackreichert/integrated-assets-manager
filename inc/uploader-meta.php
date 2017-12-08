<?php

class IAM_Assets_Uploader_Meta {

	/**
	 * Assets_Manager_Admin constructor.
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
		add_action( 'add_meta_boxes', [ $this, 'assets_manager_register_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_scripts' ] );
		add_action( 'save_post', [ $this, 'attach_assets_on_save' ], 10, 3 );
	}

	public function attach_assets_on_save( $post_id ) {
		if ( isset( $_POST['asset_id'] ) ) {
			if ( ! get_post_status( $post_id ) ) {
				return;
			}

			if ( is_array( $_POST['asset_id'] ) ) {
				$this->attach_assets_to_post( $_POST['asset_id'], $post_id );
			}
		}
	}

	/**
	 * @param $asset_ids
	 * @param $post_id
	 */
	public static function attach_assets_to_post( $asset_ids, $post_id ) {
		$assets = [];
		foreach ( $asset_ids as $asset_id ) {
			if ( 'attachment' === get_post_type( intval( $asset_id ) ) ) {
				$assets[] = intval( $asset_id );
			}
		}

		if ( count( $assets ) ) {
			delete_post_meta( $post_id, 'post_asset_list' );
			add_post_meta( $post_id, 'post_asset_list', $assets );
		} else {
			delete_post_meta( $post_id, 'post_asset_list' );
		}
	}

	/**
	 * Meta box containing attachments and settings
	 */
	// todo: if is disabled, note here and in js, show filetype
	/**
	 * Add meta boxes to asset type post edit page
	 */
	public function assets_manager_register_meta_box() { # meta box on plupload page
		add_meta_box( 'attached_assets', __( 'Attached Assets', 'attached_assets_textdomain' ), [
			$this,
			'assets_manager_attached_meta_box'
		], '', 'normal' );
	}


	public function assets_manager_attached_meta_box() {
		global $post;
		$assets = IAM::get_all_assets( $post->ID ); ?>
		<div class="assets">
			<ul id="filelist" class="assets">
				<?php foreach ( $assets as $i => $asset ) : ?>
					<li id="asset_<?php echo $asset->get_id(); ?>" class="asset">
						<input type="hidden" class="asset_id" name="asset_id[]" value="<?php echo $asset->get_id(); ?>" />
						<a href="<?php echo $asset->get_permalink(); ?>"><?php echo $asset->get_title(); ?></a>
						<span class="dashicons dashicons-edit edit corner"></span>
						<span class="dashicons dashicons-trash remove corner"></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<button class="button button-large asset-button" id="asset_attach_button">Attach Files</button>
		<?php
	}

	/**
	 * Extracts time unit value from set value
	 *
	 * @param $expires
	 *
	 * @return mixed
	 */
	public function get_expires_val( $expires ) {
		$expires_val = current( explode( ' ', $expires ) );

		return $expires_val;
	}

	/**
	 * Enqueues scripts for page
	 */
	public function load_admin_scripts() {
		global $post;
		if ( is_admin() && is_object( $post ) ) {
			wp_enqueue_script( 'IAM_post_meta_js', plugin_dir_url( __FILE__ ) . '../js/post-meta.js', [
				'jquery',
				'jquery-ui-sortable',
				'plupload-all'
			], 20170322 );
			wp_localize_script( 'IAM_post_meta_js', 'IAM_post_meta', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'amNonce' => wp_create_nonce( 'update-IAMnonce' )
			] );

			wp_enqueue_style( 'IAM_post_meta_css', plugin_dir_url( __FILE__ ) . '../css/post-meta.css' );
		}
	}

}