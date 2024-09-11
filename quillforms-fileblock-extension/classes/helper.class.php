<?php
class Fileblock_ie_helper {

    public static function get_quill_forms() {
        
        $posts = get_posts(array(
            'post_type' => 'quill_forms',
            'post_status' => 'publish',
            'numberposts' => -1
        ));

        $set = [];
        foreach( $posts as $post ) {
            $set[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_post_permalink($post->ID)
            );
        }

        /*
        echo "<pre>";
        print_r( get_post_meta( 38460 ) );
        echo "</pre>";
        */

        return $set;


    }

    public static function is_ai_active( $form_id ) {

        $active_forms = get_option('fileblock_ie_active_forms');

        if( 
            is_iterable($active_forms) && 
            in_array( $form_id, $active_forms ) 
            ) {
            return true;
        }

    }

}