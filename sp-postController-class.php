<?php

if(!class_exists('SP_PostController')):
	
	
class SP_PostController{
	/** @public int (post) ID */
	public $id;
	/** @public int (post) ID */
	public $post_slug;
	/** @public int (post) ID */
	public $post_content;
	/** @public int (post) ID */
	public $post_author;
	/** @public int (post) ID */
	public $post_title;
	/** @public int (post) ID */
	public $post_status;
	/** @public int (post) ID */
	public $post_type;
	/** @public int (post) ID */
	public $post_excerpt;
	/** @public int (post) ID */
	public $post;
	
	public function __construct($post_id = 0){
		$this->init( $post_id );
	}
	
	protected function init( $post_id ){
		$this->id = absint( $post_id );
		if($this->id){
			$this->post = get_post( $this->id );
			$this->post_title = $this->get_title();
			$this->post_content = $this->post->post_content;
			$this->post_status = $this->post->post_status;
		}
	}
	
	public function set_author($author){
		if(!is_int($author)){
			 if (!username_exists( $author ) ){
			 	$this->post_author = '';
			 }
			 $user = get_user_by( 'login', $author );
			 $author = $user->id;
		}
		$this->post_author = int($author);
	}
		
	public function get_author(){
		return $this->post_author;
	}
	
	public function set_post_type($post_type){
		$this->post_type = $post_type;
	}
	public function get_post_type(){
		return $this->post_type;
	}
	public function get_thumbnail_id(){
		return get_post_thumbnail_id( $this->id ); 
	}
	public function get_thumbnail_src($size='thumbnail'){
		$attachment_id = $this->get_thumbnail_id();
		$image_attributes = wp_get_attachment_image_src( $attachment_id, $size ); // returns an array
		if( $image_attributes ) {
			return $image_attributes[0];
		}
		return false;
	}
	public function create($args){
		$result = array();
		$defaults = array (
			'post_type' => $this->post_type,
			'post_author'   => $this->get_author(),
			'post_status' => "draft"
		);
		
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters('sp_pre_post_args', $args);
		//if post_id exists then perform update else insert
		if(!$this->id){
			$this->id = wp_insert_post( $args );
			do_action('sp_after_post_insert', $this->id, $args);	
		}else{
			$args['ID'] = $this->id;
			$this->id = wp_update_post( $args );
			do_action('sp_after_post_update', $this->id, $args);	
		}
		if (is_wp_error($this->id)) {
			$errors = $post_id->get_error_messages();
			$result['result'] = false;
			foreach ($errors as $error) {
				$result['error_messages'][] =  $error;
			}
		}else{
			$result['result'] = true;
			$result['post_id'] = $this->id;
			do_action('sp_after_post_save');	
		}
		return apply_filters('sp_pcontroller_create', $result);
	}
	
	public function after_post_save($post_id=''){
		do_action('sp_after_post_save');	
	}
	
	public function get_post(){
		if(!$this->id){
			return false;
		}
		return $this->post;
	}
	
	public function delete($force_delete=false){
		return wp_delete_post( $this->id, $force_delete );
	}
	
	
	public function set_title($title){
		$post_array();
		if(!$title){
			return false;
		}
		$post_array['post_title'] = sanitize_text_field($title);
		$this->update_post($post_array);
	}
	
	public function get_title(){
		return $this->post->post_title;
	}
	public function set_slug($slug){
		$post_array();
		if(!$slug){
			return false;
		}
		$post_array['post_name'] = sanitize_text_field($slug);
		$this->update_post($post_array);
	}
	
	public function get_slug(){
		return $this->post_slug;
	}
	
	public function set_content(){
	
	}
	
	public function update_post($post_array = array()){
		 if($this->id && !empty($post_array)){
		 	wp_update_post( $post_array );
		 }
	}
	
	public function get_posts($args,  $per_page = 20, $page = 1){
		$offset = $per_page * $page;
		$paged = ( get_query_var('paged') ) ? get_query_var('paged') : $page;
		$defaults = array (
			'posts_per_page' => $per_page,
			'paged' => $paged
		);
		
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters('sp_get_posts_args', $args);
		$posts = get_posts( $args );
		return $posts;	
	}
	
	public function get_meta($meta_key='', $single=true){
		if(!$meta_key){
			return;
		}
		return get_post_meta($this->id, $meta_key, $single);
	}
	public function reArrayFiles($file_post) {
	
		$file_ary = array();
		$file_count = count($file_post['name']);
		$file_keys = array_keys($file_post);
	
		for ($i=0; $i<$file_count; $i++) {
			foreach ($file_keys as $key) {
				$file_ary[$i][$key] = $file_post[$key][$i];
			}
		}
	
		return $file_ary;
	}
	/*
	 *	Upload files to Media folder
	 */
	public function upload_media($files_key='', $insert_attachment = true, $overwrite = true){
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		// These files need to be included as dependencies when on the front end.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		if(empty($files_key)){
			return false;
		}
		if(empty($_FILES[$files_key])){
			return false;
		}
		$result = array();
		$upload_overrides = array( 'test_form' => false );
		$attachment_id = media_handle_upload( $files_key, $this->id);
		if ( !is_wp_error( $attachment_id ) ) {
			if($insert_attachment){
				if($overwrite){
					//$this->delete_post_media( $this->id );
				}
				//$movefile['extension'] = $file_type['ext'];
				//$movefile['attachment_id'] = $attach_id;
			}
			$result[] = $attachment_id;
		}else{
			  $error_string = $attachment_id->get_error_message();
echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
		}
		return $result;
	}
	
	public function delete_post_media( $post_id ) {
	
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_parent'    => $post_id
		) );
	
		foreach ( $attachments as $attachment ) {
			if ( false === wp_delete_attachment( $attachment->ID ) ) {
				// Log failure to delete attachment.
			}
		}
	}
}
endif;