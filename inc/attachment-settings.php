<?php

class IAM_Attachment_Settings {
	function init() {
		$this->load_actions();
	}

	function load_actions() {
		add_filter( "attachment_fields_to_edit", array( $this, 'IAM_attachment_fields_to_edit' ), 10, 2 );
		add_action( "attachment_fields_to_save", array( $this, 'IAM_attachment_fields_to_save' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'IAM_upload_admin_scripts' ) );
		add_action( 'add_attachment', array( $this, 'IAM_default_obfuscate' ), 10, 1 );
	}

	function IAM_default_obfuscate( $post_ID ) {
		if ( IAM::get_default_obfuscate() ) {
			update_post_meta( $post_ID, 'IAM_obfuscate', 'true' );
			update_post_meta( $post_ID, 'IAM_enabled', 'true' );
		}
	}

	function IAM_upload_admin_scripts( $hook ) {
		if ( 'upload.php' != $hook && 'post.php' != $hook ) {
			return;
		}

		wp_enqueue_script( 'IAM_media_js', plugin_dir_url( __FILE__ ) . '../js/uploader.js' );
		wp_localize_script( 'IAM_media_js', 'IAM_Ajax', array(
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'iamUploadNonce' => wp_create_nonce( 'IAM_upload-nonce' )
		) );

		wp_enqueue_script( 'datetimepicker_js', plugin_dir_url( __FILE__ ) . '../js/jquery.datetimepicker.full.min.js', array(
			'jquery',
			'IAM_media_js'
		), 'c05c507' );
		wp_enqueue_style( 'datetimepicker_css', plugin_dir_url( __FILE__ ) . '../css/jquery.datetimepicker.css', array(), 'c05c507' );

	}


	function IAM_attachment_fields_to_edit( $form_fields, $post ) {
		$asset = new IAM_Asset( $post->ID );
		if ( ! $this->can_edit_uploads( $post->post_author ) ) {
			$form_fields['IAM_obfuscate_notice'] = array(
				'label' => __( "Why can't I edit these?" ),
				"input" => "html",
				"html"  => "<input type='hidden' />",
				'helps' => __( 'This file was uploaded by: ' . get_user_meta( $post->post_author, 'nickname', true ) . ' (' . get_the_author_meta( 'email', $post->post_author ) . ').<br />You can only update these settings if you are the owner of the upload.' )
			);
		}

		$asset_post_list             = $this->get_asset_post_list( $post->ID );
		$form_fields["IAM_attached"] = array(
			"label" => __( "Attached to:" ),
			"input" => "html",
			"html"  => '<input type="hidden" />',
			"helps" => $this->format_posts_as_links( $asset_post_list )
		);

		$form_fields["IAM_obfuscate"] = array(
			"label" => __( "Obfuscate file path?" ),
			"input" => "html",
			"html"  => '<input type="checkbox" name="IAM_obfuscate" value="true" ' . ( 'true' == $asset->is_obfuscated() ? 'checked="checked"' : '' ) . ( ! $this->can_edit_uploads( $post->post_author ) ? ' disabled="disabled"' : '' ) . ' />',
			"helps" => "This will use the Attachment Page to serve the attachment instead of the actual path on the server."
		);

		$form_fields["IAM_enabled"] = array(
			"label" => __( "Enabled?" ),
			"input" => "html",
			"html"  => '<input type="checkbox" name="IAM_enabled" value="true" ' . ( 'true' == $asset->is_enabled() ? 'checked="checked"' : '' ) . ' />',
			"helps" => 'If this file is not enabled, people trying to see the file will see: "This file has expired."'
		);

		$form_fields["IAM_begin_date"] = array(
			"label" => __( "When should this file start being available?" ),
			"input" => "html",
			"html"  => '<input type="text" id="Begin_Date" name="IAM_begin_date" value="' . $asset->get_date( 'begin' ) . '"' . ( ! $this->can_edit_uploads( $post->post_author ) ? ' disabled="disabled"' : '' ) . ' />&nbsp;' . ( $this->can_edit_uploads( $post->post_author ) ? '<button class="begin_clear_date" class="button">clear</button>' : '' ),
		);

		$form_fields["IAM_expires_date"] = array(
			"label" => __( "When should this expire?" ),
			"input" => "html",
			"html"  => '<input type="text" id="Expires_Date" name="IAM_expires_date" value="' . $asset->get_date( 'expires' ) . '"' . ( ! $this->can_edit_uploads( $post->post_author ) ? ' disabled="disabled"' : '' ) . ' />&nbsp;' . ( $this->can_edit_uploads( $post->post_author ) ? '<button class="end_clear_date" class="button">clear</button>' : '' ),
			"helps" => 'Date/Time fields use this site\'s <a href="' . admin_url( 'options-general.php' ) . '">Timezone</a>.'
		);

		$form_fields["IAM_requires_login"] = array(
			"label" => __( "Requires login?" ),
			"input" => "html",
			"html"  => '<input type="checkbox" name="IAM_requires_login" value="true" ' . ( 'true' == $asset->requires_login() ? 'checked="checked"' : '' ) . ( ! $this->can_edit_uploads( $post->post_author ) ? ' disabled="disabled"' : '' ) . ' />',
			"helps" => 'If this is checked only logged in users will be able to access this file.'
		);

		return $form_fields;
	}

	private function can_edit_uploads( $post_author ) {
		return get_current_user_id() == $post_author || current_user_can( 'edit_others_posts' ) || current_user_can( 'edit_others_uploads' );
	}

	private function get_asset_post_list( $asset_id ) {
		$post_ids = [];
		if ( intval( $asset_id ) ) {
			global $wpdb;
			$asset_id = intval( $asset_id );
			$query    = "SELECT post_id FROM $wpdb->postmeta where meta_key='post_asset_list' and meta_value like '%i:$asset_id%';";

			$post_ids = $wpdb->get_results( $query, ARRAY_A );
			if ( is_wp_error( $post_ids ) ) {
				return [];
			}
		}

		return wp_list_pluck( $post_ids, 'post_id' );
	}

	private function format_posts_as_links( $post_ids ) {
		$links = [];
		foreach ( $post_ids as $post_id ) {
			$links[] = sprintf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit#attached_assets', $post_id ) ), get_the_title( $post_id ) );
		}

		return implode( $links, ', ' );
	}

	function IAM_attachment_fields_to_save( $post ) {
		$asset = new IAM_Asset( $post["ID"] );

		if ( isset( $_REQUEST['IAM_obfuscate'] ) ) {
			$asset->set( 'obfuscated', 'true' );
		} else {
			$asset->set( 'obfuscated', 'false' );
		}

		if ( isset( $_REQUEST['IAM_enabled'] ) ) {
			$asset->set( 'enabled', 'true' );
		} else {
			$asset->set( 'enabled', 'false' );
		}

		if ( isset( $_REQUEST['IAM_requires_login'] ) ) {
			$asset->set( 'requires_login', 'true' );
		} else {
			$asset->set( 'requires_login', 'false' );
		}

		delete_post_meta( $post['ID'], 'IAM_begin_date' );
		if ( isset( $_REQUEST['IAM_begin_date'] ) && $date = (bool) strtotime( $_REQUEST['IAM_begin_date'] ) && isset( $_REQUEST['IAM_obfuscate'] ) ) {
			$asset->set( 'begin_date', strtotime( $_REQUEST['IAM_begin_date'] ) );
		}

		delete_post_meta( $post['ID'], 'IAM_expires_date' );
		if ( isset( $_REQUEST['IAM_expires_date'] ) && $date = (bool) strtotime( $_REQUEST['IAM_expires_date'] ) && isset( $_REQUEST['IAM_obfuscate'] ) ) {
			$asset->set( 'expires_date', strtotime( $_REQUEST['IAM_expires_date'] ) );
		}

		return $post;
	}

}