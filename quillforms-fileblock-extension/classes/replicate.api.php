<?php
class Replicate_API {

    private $token;
    private $version;
    private $url;

    public function __construct() {

        // Should be replaced by get_option()
        $this->token = get_option('fileblock_ie_token');
        $this->version = get_option('fileblock_ie_version');
        $this->url = "https://api.replicate.com/v1/predictions";

    }

    public function process_image( $url, $question ) {

        $prediction = $this->create_prediction( $url, $question );

        // Repeat 10 seconds till result is ready
        $count = 0;
        while( $count < 10 ) {

            $res = $this->get_prediction( $prediction['id'] );
            if( $res['output'] ) { break; }

            sleep(1);

            $count++;
        }

        return $res['output'];

        echo $prediction['id']; echo "<br/>";
        echo "<pre>";
        print_r($res);
        echo "</pre>";

    }

    private function create_prediction( $image_url, $question ) {

        $image = 'data: image/jpeg;base64,' .  base64_encode(file_get_contents( $image_url ));

        $url = $this->url;
        $data = array(
            'version' => $this->version,
            'input' => array(
                'task' => 'visual_question_answering',
                'question' => $question,
                //'caption' => 'The face is not visible enough',
                'image' => $image,
            )
        );

        return $this->query( $url, $data, $method="POST" );

    }

    private function get_prediction( $prediction_id ) {

        $url = $this->url.'/'.$prediction_id;
        return $this->query( $url );

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
        curl_close($ch);

        return json_decode($result, true);

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

}
