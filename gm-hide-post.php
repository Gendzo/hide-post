<?php
/*
   Plugin Name: Hide Post (on Homepage)
   Plugin URI: https://www.gendzo.sk/
   Description: Hide post on homepage based on custom metadata info.
   Version: 1.0.0
   Author: Gendzo macher
   Author URI: https://www.gendzo.sk/autor/gendzo-macher/
   License: GPL2
*/

if ( ! function_exists('gm_hp_posts') ) {

     // Pridaj vlastné metabox údaje do admin rozhrania
	function gm_hp_meta_boxes() {
		add_meta_box(
			'gm_hp_meta',
			'Hide on home',
			'gm_hp_render_meta_boxes',
			null,
			'side',
			'high'
		);
	}
    function gm_hp_render_meta_boxes( $post ) {
		$meta = get_post_meta( $post->ID, '_HideOnHomePage', true );
		if( isset( $meta ) && $meta == '1' ) $HideOnHomePage = 1;
		else $HideOnHomePage = 0;
		wp_nonce_field( basename( __FILE__ ), 'gm_hp_nonce' ); ?>
		<p>
			<input type="checkbox" id="gm_hp_homepage" name="HideOnHomePage" value="1" <?php checked( $HideOnHomePage, 1 ); ?> />
			<label for="gm_hp_homepage">Hide this post on homepage</label>
		</p>
	<?php }
    add_action( 'add_meta_boxes', 'gm_hp_meta_boxes' );

	// Uloz meta data info do databazy
    function gm_hp_save_meta( $post_id ) {
		global $post;
		// Verify nonce
		if ( !isset( $_POST['gm_hp_nonce'] ) || !wp_verify_nonce( $_POST['gm_hp_nonce'], basename(__FILE__) ) ) {
			return $post_id;
		}
		// Check Autosave
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) {
			return $post_id;
		}
		// Don't save if only a revision
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}
		// Check permissions
		if ( !current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}
		if( isset( $_POST['HideOnHomePage'] ) && $_POST['HideOnHomePage'] == '1' )
			update_post_meta( $post->ID, '_HideOnHomePage', '1' );
		else
			delete_post_meta( $post->ID, '_HideOnHomePage', '1');
	}
	add_action( 'save_post', 'gm_hp_save_meta',  10, 2 );

	// Zobraz informaciu o stave aj v zozname clankov
	function gm_hp_add_column( $columns ) {
		return array_merge( $columns, array('gm_hphp' => 'HomePage') );
	}
	add_filter( 'manage_posts_columns', 'gm_hp_add_column', 10);
	// Napln zoznam clankov datami
	function gm_hp_column_val( $column ) {
		global $post;
		if ( $column == 'gm_hphp') {
			$metaData = get_post_meta( $post->ID, '_HideOnHomePage', true );
			if ( $metaData == '1' ) echo ('<span style="color: red;"><strong>no</strong></span>');
			else echo ('<span style="color: green;"><strong>yes</strong></span>');
		}
	}
	add_action( 'manage_posts_custom_column' , 'gm_hp_column_val', 10 );
		
	// Na hlavnej stranke zobrazuj iba obsah, ktory nema priznak _HideOnHomePage
	function gm_hp_posts($query) {
		if( $query->is_main_query() && !is_admin() && $query->is_home() ){
			$meta_query = $query->get('meta_query');
			if( !is_array($meta_query) ) $meta_query = array();
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_HideOnHomePage',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key' => '_HideOnHomePage',
					'value'   => 1,
					'compare' => '!=',
				)
			);
			$query->set('meta_query', $meta_query);
		}
		return $query;
	}
	add_action('pre_get_posts', 'gm_hp_posts', 500 );

}

?>