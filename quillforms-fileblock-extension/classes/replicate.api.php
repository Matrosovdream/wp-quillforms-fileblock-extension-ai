<?php
class Replicate_API {

    private $token;
    private $version;
    private $url;

    public function __construct() {
        $this->token = get_option('fileblock_ie_token');
        $this->version = get_option('fileblock_ie_version');
        $this->url = "https://api.replicate.com/v1/predictions";
    }

    public function process_image( $url, $question ) {
        // Generate a unique cache key based on image URL and question
        $cache_key = 'replicate_' . md5($url . '_' . $question);
        $cached_result = get_transient($cache_key);

        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $prediction = $this->create_prediction( $url, $question );

        // Initialize polling parameters for Exponential Backoff
        $count = 0;
        $maxAttempts = 5; // Reduced attempts for faster response
        $delaySeconds = 1;

        while( $count < $maxAttempts ) {
            $res = $this->get_prediction( $prediction['id'] );
            if( isset($res['output']) && !empty($res['output']) ) { 
                // Cache the result for 12 hours
                set_transient( $cache_key, $res['output'], 12 * HOUR_IN_SECONDS );
                return $res['output']; 
            }

            sleep($delaySeconds);
            $count++;
            $delaySeconds *= 2; // Exponential backoff
        }

        // Handle timeout scenario
        error_log("Replicate API timeout for image URL: {$url}, question: {$question}");
        return null;
    }

    private function create_prediction( $image_url, $question ) {
        $url = $this->url;
        $data = array(
            'version' => $this->version,
            'input' => array(
                'task' => 'visual_question_answering',
                'question' => $question,
                'image' => $this->prepare_post_image( $image_url )
            )
        );

        return $this->query( $url, $data, $method="POST" );
    }

    private function get_prediction( $prediction_id ) {
        $url = $this->url.'/'.$prediction_id;
        return $this->query( $url );
    }

    private function prepare_post_image( $image_url ) {
        // Resize and compress the image before encoding
        $compressed_image = $this->resize_and_compress_image( $image_url, 800, 800, 85 );
        if ( !$compressed_image ) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($compressed_image);
    }

    private function resize_and_compress_image( $image_path, $max_width, $max_height, $quality ) {
        // Fetch the image content
        $image_content = file_get_contents( $image_path );
        if ( !$image_content ) {
            return null;
        }

        // Create an image resource
        $image = imagecreatefromstring( $image_content );
        if ( !$image ) {
            return null;
        }

        // Get current dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Calculate new dimensions while maintaining aspect ratio
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = (int)($width * $ratio);
            $new_height = (int)($height * $ratio);
        } else {
            $new_width = $width;
            $new_height = $height;
        }

        // Create a new true color image
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, 
                           $new_width, $new_height, $width, $height);

        // Start output buffering to capture the image data
        ob_start();
        imagejpeg($resized_image, null, $quality); // Compress to specified quality
        $compressed_image = ob_get_clean();

        // Free up memory
        imagedestroy($image);
        imagedestroy($resized_image);

        return $compressed_image;
    }

    public function verify_url( $url ) {
        if( 
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !is_readable( $url ) 
            ) { 
            return false;
        }

        return true;
    }

    private function query( $url, $data=array(), $method="GET" ) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Token ' . $this->token
        ));

        if( $method == 'POST' ) {
            $data_string = json_encode($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('CURL Error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        return json_decode($result, true);
    }

}
