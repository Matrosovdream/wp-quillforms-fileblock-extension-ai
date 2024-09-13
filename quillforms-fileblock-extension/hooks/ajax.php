<?php
add_action('wp_ajax_fileblock_verify_image', 'fileblock_verify_image_func');
add_action('wp_ajax_nopriv_fileblock_verify_image', 'fileblock_verify_image_func');

function fileblock_verify_image_func() {

    // Validate and sanitize inputs
    if ( ! isset($_POST['question']) || empty($_POST['question']) ) {
        wp_send_json_error( array( "message" => "No question provided." ) );
    }
    if ( ! isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK ) {
        wp_send_json_error( array( "message" => "Error occurred during file upload." ) );
    }

    // Initialize helper
    $blob = new Replicate_helper_blobs( "image", $_POST['image_type'] );

    // Validate image type
    if( !$blob->check_type() ) { 
        wp_send_json_error( array( "message" => "Invalid image type." ) );
    }

    // Upload the image
    if( !$blob->upload_file() ) {
        wp_send_json_error( array( "message" => "Failed to upload image." ) );
    }

    // Initialize Replicate API
    $api = new Replicate_API();

    // Retrieve and sanitize the question
    $question = sanitize_text_field( $_POST['question'] );
    $output = $api->process_image( $blob->image_url, $question );

    if ( $output ) {
        $answer = isset(explode('Answer: ', $output)[1]) ? explode('Answer: ', $output)[1] : $output;
        $data = array( 
            "question" => $question,
            "result" => $answer,
            "url" => $blob->image_url, 
        );
        wp_send_json_success( $data );
    } else {
        wp_send_json_error( array( "message" => "Verification timed out." ) );
    }
}


add_action('wp_ajax_fileblock_convert_heic_image', 'fileblock_convert_heic_image_func');
add_action('wp_ajax_nopriv_fileblock_convert_heic_image', 'fileblock_convert_heic_image_func');
function fileblock_convert_heic_image_func() {
    if ($_FILES && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {

        $image = $_FILES["image"]["tmp_name"];

        $filename = str_replace( '.HEIC', '.jpeg', $_FILES["image"]["name"] );
        $destination = wp_upload_dir()["path"] . "/" . $filename;
        $image_url = wp_upload_dir()["url"] . "/" . $filename;

        // Convert HEIC image to JPEG using Imagick
        $imagick = new Imagick($image);
        $imagick->setImageFormat("jpeg");
        $imagick->writeImage($destination);

        // Return final URL
        echo json_encode(array("url" => $image_url, "filename" => $filename));
    } else {
        $data = array("error" => true, "message" => "Error occurred");
        echo json_encode($data);
    }

    exit();
}



add_action('wp_enqueue_scripts', 'localize_script');
function localize_script() {
    wp_localize_script('your-script-handle', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}


