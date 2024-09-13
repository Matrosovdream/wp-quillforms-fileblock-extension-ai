<?php
use Fileblock_ie_helper as Helper;

// Enqueue scripts and styles properly
add_action( 'wp_enqueue_scripts', 'enqueue_fileblock_scripts_and_styles' );
function enqueue_fileblock_scripts_and_styles() {
    global $post;

    // Check if this form is Active for Replicate AI
    if( !Helper::is_ai_active( $post->ID ) ) { return; }

    // Enqueue Main JS (minified)
    wp_enqueue_script(
        'fileblock-ie-js',
        FILEBLOCK_IE_PLUGIN_FILE . '/assets/fileblock-ie.min.js', // Ensure this file is minified
        array('jquery'), // Dependencies
        filemtime( FILEBLOCK_IE_PLUGIN_DIR_ABS . '/assets/fileblock-ie.min.js' ), // Version based on file modification time
        true // Load in footer
    );

    // Enqueue CSS (minified)
    wp_enqueue_style(
        'fileblock-ie-css',
        FILEBLOCK_IE_PLUGIN_FILE . '/assets/fileblock-css.min.css', // Ensure this file is minified
        array(),
        filemtime( FILEBLOCK_IE_PLUGIN_DIR_ABS . '/assets/fileblock-css.min.css' )
    );

    // Enqueue Preloaders CSS (minified)
    wp_enqueue_style(
        'fileblock-preloaders-css',
        FILEBLOCK_IE_PLUGIN_FILE . '/assets/fileblock-preloaders.min.css', // Ensure this file is minified
        array(),
        filemtime( FILEBLOCK_IE_PLUGIN_DIR_ABS . '/assets/fileblock-preloaders.min.css' )
    );
}

// Optional: Use WordPress's built-in jQuery instead of external CDN
function load_bundled_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'load_bundled_jquery'); 
