<?php
use Fileblock_ie_helper as Helper;

// Rewrite this part
add_action( 'wp_footer', 'add_stylesheet_to_footer' );
function add_stylesheet_to_footer() {

    global $post;

    // Check if this form is Active for Replicate AI
    if( !Helper::is_ai_active( $post->ID ) ) { return; }

    // Main JS
    echo '<script src="'.FILEBLOCK_IE_PLUGIN_FILE.'/assets/fileblock-ie.js?time='.time().'" crossorigin="anonymous"></script>';

    // CSS
    echo '<link rel="stylesheet" type="text/css" href="'.FILEBLOCK_IE_PLUGIN_FILE.'/assets/fileblock-css.css?time='.time().'" />';
    echo '<link rel="stylesheet" type="text/css" href="'.FILEBLOCK_IE_PLUGIN_FILE.'/assets/fileblock-preloaders.css?time='.time().'" />';
	
}



function load_external_jquery() {

	wp_register_script( 'jquery2', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js', null, null, true );
    wp_enqueue_script('jquery2');
    
}
add_action('wp_enqueue_scripts', 'load_external_jquery'); 
