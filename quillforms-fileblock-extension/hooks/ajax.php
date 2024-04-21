<?php
add_action('wp_ajax_fileblock_verify_image', 'fileblock_verify_image_func');
add_action('wp_ajax_nopriv_fileblock_verify_image', 'fileblock_verify_image_func');
function fileblock_verify_image_func() {

    if ($_FILES && $_FILES["image"]["error"]== UPLOAD_ERR_OK)
    {

        // $_FILES["image"]
        $blob = new Replicate_helper_blobs( "image", $_POST['image_type'] );

        // We take just images
        if( !$blob->check_type() ) { return true; }

        // Return error if the image wasn't uploaded
        if( !$blob->upload_file() ) {
            $data = array( "error" => true, "message" => "Error occured" );
            echo json_encode( $data );
        }
        
        // Send to Replicate API
        $api = new Replicate_API();

        //$question = get_option('fileblock_ie_question');
        $question = $_POST['question'];
		$output = $api->process_image( $blob->image_url, $question );

        $answer = explode('Answer: ', $output)[1];
        $data = array( 
            "question" => $question,
            "result" => $answer,
            "url" => $blob->image_url, 
        );

        echo json_encode( $data );

        // Don't keep traces
        $blob->remove_file();
        
        //echo $image_url;

    } else {
        $data = array( "error" => true, "message" => "Error occured" );
        echo json_encode( $data );
    }

    exit();
}


add_action('wp_enqueue_scripts', 'localize_script');
function localize_script() {
    wp_localize_script('your-script-handle', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}


