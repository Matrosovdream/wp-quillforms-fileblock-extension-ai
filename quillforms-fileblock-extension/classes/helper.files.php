<?php
class Replicate_helper_blobs {

    private $filetype;
    private $file_extension;

    private $tmp_name;
    private $tmp_folder = '/wp-content/uploads/QuillForms/Temp/';

    // Final result
    public $image_url='';
    private $image_url_abs='';

    public function __construct( $tmp_name, $file_memo ) {

        $this->parse_file_memo( $file_memo );

        $this->tmp_name = $tmp_name;

    }

    private function parse_file_memo( $file_memo ) {

        $this->file_type = explode('/', $file_memo)[0];
        $this->file_extension = explode('/', $file_memo)[1];

    }

    public function upload_file() {

        $filename = time().'.'.$this->file_extension;
        $folder = $this->tmp_folder;

        // Create folder if it doesn't exist
        if (!file_exists( $_SERVER['DOCUMENT_ROOT'].$folder )) {
            mkdir( $_SERVER['DOCUMENT_ROOT'].$folder, 0777, true);
        }

        $path_abs = $_SERVER['DOCUMENT_ROOT'].$folder.$filename;
        $image_url = 'https://'.$_SERVER['SERVER_NAME'].$folder.$filename;

        // Save on the server
        if( move_uploaded_file( $_FILES[ $this->tmp_name ]["tmp_name"], $path_abs ) ) {

            $this->image_url = $image_url;
            $this->image_url_abs = $path_abs;

            return true;

        } else {
            return false;
        }
        

    }

    public function remove_file() {

        unlink( $this->image_url_abs );

    }

    public function check_type() {

        // We take just images
        if( $this->file_type != 'image' ) { return false; }

        return true;

    }

}