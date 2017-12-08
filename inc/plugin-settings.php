<?php

class IAM_Plugin_Settings {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	public $options = array();

	public function __construct() {
	}

	/**
	 * Start up
	 */
	public function init() {
		$this->init_options();
		$this->load_actions();
	}

	private function init_options() {
		$this->options = get_option( 'iam' );
		$this->set_defaults_on_first_load();

		if ( array_keys( $this->options ) != array_keys( $this->defaults() ) ) {
			array_merge( $this->defaults(), $this->options );
		}
	}

	private function defaults() {
		require_once( ABSPATH . WPINC . '/pluggable.php' );

		return array(
			'default_obfuscate' => 'no',
			'asset_hash'        => wp_generate_password( 64, false, false ),
			'has_updated'       => false,
			'archived_hashes'   => array()
		);
	}

	function load_actions() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_IAM_settings', array( $this, 'IAM_settings_ajax_func' ) );
		if ( ! $this->options['has_updated'] ) {
			add_action( 'admin_notices', array( $this, 'add_admin_update_notice' ) );
		}
	}

	function add_admin_update_notice() {
		?>
        <div class="notice notice-warning">
            <p><?php _e( 'WordPress Assets Manager is almost ready, check out the <a href="' . admin_url( 'options-general.php?page=wp-assets-manager' ) . '">plugin settings</a> to complete the installation.', 'my-text-domain' ); ?></p>
        </div>
		<?php
	}

	function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_wp-assets-manager' != $hook ) {
			return;
		}
		wp_enqueue_script( 'IAM_settings_js', plugin_dir_url( __FILE__ ) . '../js/settings-page.js' );
		wp_localize_script(
			'IAM_settings_js', 'IAM_Ajax', array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'iamSettingsNonce' => wp_create_nonce( 'IAM_settings-nonce' )
			)
		);
	}

	public function IAM_settings_ajax_func() {
		$nonce = $_POST['iamSettingsNonce'];
		if ( ! wp_verify_nonce( $nonce, 'IAM_settings-nonce' ) ) {
			die ( 'Busted!' );
		}

		$response = wp_generate_password( 64, false, false );

		header( "Content-Type: application/json" );
		echo json_encode( $response );
		exit;
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			'Integrated Assets Manager',       // Page title
			'Integrated Assets Manager Settings',          // Menu title
			'manage_options',       // Capability
			'wp-assets-manager', // Menu slug
			array( $this, 'create_admin_page' ) // Function
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() { ?>
        <div class="wrap">
            <h2>Integrated Assets Manager</h2>

            <form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'IAM_option_group' );
				do_settings_sections( 'wp-assets-manager' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'IAM_option_group', // Option group
			'iam', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		/*
		 * Settings section
		 */
		add_settings_section(
			'IAM_plugin_settings', // ID
			'Assets Manager Settings', // Title
			array( $this, 'IAM_section_info' ), // Callback
			'wp-assets-manager' // Page
		);

		/*
		 * Settings fields
		 */
		add_settings_field(
			'default_obfuscate',
			'Obfuscate links by default?',
			array( $this, 'default_obfuscate_callback' ),
			'wp-assets-manager',
			'IAM_plugin_settings'
		);

		add_settings_field(
			'asset_hash',
			'Global hash',
			array( $this, 'asset_hash_callback' ),
			'wp-assets-manager',
			'IAM_plugin_settings'
		);

	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param $input
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		$new_input = array();

		if ( isset( $input['default_obfuscate'] ) ) {
			$new_input['default_obfuscate'] = sanitize_text_field( $input['default_obfuscate'] );
		}

		if ( isset( $input['asset_hash'] ) ) {
			$new_input['asset_hash'] = sanitize_text_field( $input['asset_hash'] );
		}
		if ( $this->options['asset_hash'] !== $input['asset_hash'] ) {
			$new_input['archived_hashes']   = $this->options['archived_hashes'];
			$new_input['archived_hashes'][] = array(
				'hash'      => $this->options['asset_hash'],
				'last_used' => time()
			);
		}

		$new_input['has_updated'] = true;

		flush_rewrite_rules();

		return $new_input;
	}

	/**
	 * Print the Assets Manager Section text
	 */
	public function IAM_section_info() {
		print '';
	}

	public function default_obfuscate_callback() {
		$selcted = ( isset( $this->options['default_obfuscate'] ) && 'no' == $this->options['default_obfuscate'] ) ? 'no' : 'yes'; ?>
        <select id="default_obfuscate" name="iam[default_obfuscate]">
            <option value="yes" <?php echo selected('yes', $selcted ); ?>>Yes</option>
            <option value="no" <?php echo selected( 'no', $selcted ); ?>>No</option>
        </select>
		<?php
	}

	public function asset_hash_callback() {
		printf(
			'<input type="text" id="asset_hash" readonly="readonly" name="iam[asset_hash]" value="%s" size="75" />',
			( isset( $this->options['asset_hash'] ) && '' != $this->options['asset_hash'] ) ? esc_attr( $this->options['asset_hash'] ) : wp_generate_password( 64, false, false )
		); ?><br>
        <strong>Note:</strong> If you update this ALL previous obfuscated links will be lost.
        <button id="generate_hash">Regenerate Hash</button>
        <div id="regenHashNotice"></div>
		<?php
	}

	private function set_defaults_on_first_load() {
		if ( $this->options == false ) {
			$this->options = $this->defaults();
			update_option( 'iam', $this->options );
		}
	}

}