<?php
/*
 * Plugin Name: Integrated Assets Manager
 * Description: Integragted file management tools into WP Media
 * Version: 2.0.1
 * Author: Jack Reichert
 * Text Domain: integrated-assets-manager
 * License: GPL3
*/

$Integrated_Assets_Manager = new IAM();

class IAM {
	/*
	 * Assets Manager class construct
	 */
	public function __construct() {
		$this->include_dependencies();
		$this->setup();
		$this->teardown();
		$this->instantiate_components();
	}

	/**
	 * Include all dependencies
	 */
	public function include_dependencies() {
		require_once 'inc/alphaID.php';
		require_once 'inc/asset.php';
		require_once 'inc/log-assets-access.php';
		require_once 'inc/check-asset-restrictions.php';
		require_once 'inc/serve-attachment.php';
		require_once 'inc/assets-shortcode.php';
		require_once 'inc/uploader-meta.php';
		require_once 'inc/hash-uploads.php';
		require_once 'inc/ajax-endpoints.php';
		require_once 'inc/attachment-settings.php';
		require_once 'inc/plugin-settings.php';
		require_once 'inc/rewrite-query.php';
		require_once 'inc/add-assets-to-post-endpoint.php';
	}

	/**
	 * Plugin activation
	 */
	public function setup() {
		register_activation_hook( __FILE__, array( $this, 'wp_assets_manager_activate' ) );
	}

	/**
	 * Plugin deactivation
	 */
	public function teardown() {
		register_deactivation_hook( __FILE__, array( $this, 'wp_assets_manager_deactivate' ) );
	}

	/**
	 * Instantiates all components of plugin
	 */
	public function instantiate_components() {
		$Rewrite_Query = new IAM_Rewrite_Query();
		$Rewrite_Query->init();

		$IAM_Plugin_Settings = new IAM_Plugin_Settings();
		$IAM_Plugin_Settings->init();

		$Log_Assets_Access = new IAM_Log_Assets_Access();
		$Log_Assets_Access->init();

		$Hash_Attachments = new Hash_Attachments();
		$Hash_Attachments->init();

		$Check_Asset_Restrictions = new IAM_Check_Asset_Restrictions();
		$Check_Asset_Restrictions->init();

		$Serve_File = new IAM_Serve_Attachment();
		$Serve_File->init();

		$Assets_Shortcode = new IAM_Assets_Shortcode();
		$Assets_Shortcode->init();

		$Assets_Uploader_Meta = new IAM_Assets_Uploader_Meta();
		$Assets_Uploader_Meta->init();

		$Ajax_Endpoints = new IAM_Ajax_Endpoints();
		$Ajax_Endpoints->init();

		$Add_Assets_to_Post_Endpoint = new Add_Assets_to_Post_Endpoint();
		$Add_Assets_to_Post_Endpoint->init();

		if ( is_admin() ) {
			$Attachment_Settings = new IAM_Attachment_Settings();
			$Attachment_Settings->init();
		}
	}

	/**
	 * Returns the plugin option whether all attachments should be obfuscated by default or not
	 *
	 * @return mixed
	 */
	public static function get_default_obfuscate() {
		$plugin_options = get_option( 'iam' );

		return $plugin_options['default_obfuscate'];
	}

	/**
	 * Returns the default hash that is used to obfuscate files
	 *
	 * @return mixed
	 */
	public static function get_assets_hash() {
		$plugin_options = get_option( 'iam' );

		return $plugin_options['asset_hash'];
	}

	/**
	 * Gets assets attached to a post
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function get_assets( $post_id ) {
		$assets           = self::get_all_assets( $post_id );
		$can_serve_assets = [];
		if ( count( $assets ) ) {
			foreach ( $assets as $asset ) {
				if ( ! $asset->is_obfuscated() || $asset->can_serve_file() ) {
					$can_serve_assets[] = $asset;
				}
			}
		}

		return $can_serve_assets;
	}

	/**
	 * Gets assets attached to a postordered by set order
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function get_all_assets( $post_id ) {
		$post_asset_list = get_post_meta( $post_id, 'post_asset_list', true );
		if ( ! empty( $post_asset_list ) && is_string( $post_asset_list ) ) {
			$post_asset_list = [ $post_asset_list ];
		}
		$assets = [];
		if ( ! empty( $post_asset_list ) && is_array( $post_asset_list ) ) {
			foreach ( $post_asset_list as $asset_id ) {
				$assets[] = new IAM_Asset( $asset_id );
			}
		}

		return $assets;
	}

	/**
	 * Run this on plugin activation
	 */
	public function wp_assets_manager_activate() {
		IAM_Log_Assets_Access::create_log_table();
	}

	/**
	 * Clean up after deactivation
	 */
	public function wp_assets_manager_deactivate() {
		flush_rewrite_rules();
	}
}
