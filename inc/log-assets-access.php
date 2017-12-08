<?php

class IAM_Log_Assets_Access {

	/**
	 * Assets_Manager_Log_Assets_Access constructor.
	 */
	public function __construct() {
	}

	/**
	 * Sets up log database on plugin activation
	 */
	public static function create_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . "assets_log";
		$sql        = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,			
			uID VARCHAR(7) NOT NULL DEFAULT 0,
			aID int(11) NOT NULL DEFAULT 0,
			count int(11) NOT NULL DEFAULT 0,
			UNIQUE KEY id (id)
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
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
		add_action( 'pre_asset_serve', array( $this, 'log_asset' ), 10, 1 ); # check asset criteria
	}

	/**
	 * Logs visit to asset
	 *
	 * @param $aID
	 */
	public function log_asset( $aID ) {
		global $wpdb;
		$uID        = get_current_user_id();
		$table_name = $wpdb->prefix . "assets_log";

		if ( current_user_can( 'upload_files' ) ) {
			return;
		}

		$existing_log_for_asset = $this->get_existing_log_for_asset( $aID, $wpdb, $table_name, $uID );

		if ( 0 == count( $existing_log_for_asset ) ) {
			$this->insert_new_log_for_asset( $aID, $wpdb, $table_name, $uID );
		} else {
			$this->update_log_for_asset( $aID, $existing_log_for_asset, $wpdb, $table_name, $uID );
		}

	}

	/**
	 * @param $aID
	 * @param $wpdb
	 * @param $table_name
	 * @param $uID
	 *
	 * @return array|null|object
	 */
	private function get_existing_log_for_asset( $aID, $wpdb, $table_name, $uID ) {
		$query                  = $wpdb->prepare( "SELECT count FROM $table_name WHERE aID = %d AND uID = %d;", $aID, $uID );
		$existing_log_for_asset = $wpdb->get_results( $query, ARRAY_A );

		return $existing_log_for_asset;
	}

	/**
	 * @param $aID
	 * @param $wpdb
	 * @param $table_name
	 * @param $uID
	 */
	private function insert_new_log_for_asset( $aID, $wpdb, $table_name, $uID ) {
		$wpdb->insert(
			$table_name,
			array( 'uID' => $uID, 'aID' => $aID, 'count' => 1 ),
			array( '%s', '%s', '%d' )
		);
	}

	/**
	 * @param $aID
	 * @param $existing_log_for_asset
	 * @param $wpdb
	 * @param $table_name
	 * @param $uID
	 */
	private function update_log_for_asset( $aID, $existing_log_for_asset, $wpdb, $table_name, $uID ) {
		$count = ( isset( $existing_log_for_asset[0]['count'] ) ) ? intval( $existing_log_for_asset[0]['count'] ) + 1 : 1;
		$wpdb->update(
			$table_name,
			array( 'count' => $count ),
			array( 'uID' => $uID, 'aID' => $aID ),
			array( '%d', '%d' ),
			array( '%d' )
		);
	}
}
