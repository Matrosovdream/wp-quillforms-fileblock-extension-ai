<?php
class Fileblock_ie {

    public function __construct() {

        $this->include_classes();
        $this->include_hooks();

    }

    public function include_classes() {

        require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/classes/replicate.api.php' );
        require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/classes/admin.settings.php' );
        require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/classes/helper.files.php' );
        require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/classes/helper.class.php' );

    }

    public function include_hooks() {

        require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/hooks/page.scripts.php' );
        require_once( FILEBLOCK_IE_PLUGIN_DIR_ABS.'/hooks/ajax.php' );

    }

}

new Fileblock_ie();