<?php

class Hash_Attachments {
	function init() {
		$this->hooks();
	}

	private function hooks() {
		add_action( 'add_attachment', [ $this, 'hash_attachment' ] );
		add_action( 'edit_attachment', [ $this, 'hash_attachment' ] );
	}

	public function hash_attachment( $attach_id ) {
		$path = get_attached_file( $attach_id, true );
		$md5  = md5_file( $path );
		update_post_meta( $attach_id, 'IAM_md5', $md5 );
	}
}