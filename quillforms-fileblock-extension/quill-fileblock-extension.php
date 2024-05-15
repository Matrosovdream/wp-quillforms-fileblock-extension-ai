<?php
/**
 * Plugin Name:       Quill Forms File Block - API extension
 * Plugin URI:        
 * Description:       Extension checks uploaded images for face verification
 * Version:           1.0.0
 * Author:            Stan Matrosov
 * Author URI:        https://github.com/matrosovdream
 * Text Domain:       quillforms-fileblock
 */

defined( 'ABSPATH' ) || exit;

// Plugin Folder Path absolute
if ( ! defined( 'FILEBLOCK_IE_PLUGIN_DIR_ABS' ) ) {
	define( 'FILEBLOCK_IE_PLUGIN_DIR_ABS', plugin_dir_path( __FILE__ ) );
}

// Plugin file for CSS/JS scripts
if ( ! defined( 'FILEBLOCK_IE_PLUGIN_FILE' ) ) {
	define( 'FILEBLOCK_IE_PLUGIN_FILE', plugin_dir_url( __FILE__ ) );
}

require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/classes/init.php' );


add_action('init', 'init22');
function init22() {

	if( $_GET['test'] ) {

		$url = 'https://profilebak2stg.wpenginepowered.com/tinder_ai_picture.jpg';

		$api = new Replicate_API();

		$question = "are the lips pickered?";
		$output = $api->process_image( $url, $question );
	
		echo $output;

		die();

	}

}




