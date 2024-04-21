<?php
class Fileblock_ie_settings {

    public function __construct() {

        $this->setup_admin_page();

    }

    private function setup_admin_page() {
        add_action('admin_menu', array($this, 'fileblock_ie_menu'));
        add_action('admin_init', array($this, 'fileblock_ie_settings_init'));
    }

    public function fileblock_ie_menu() {
        add_menu_page(
            'Fileblock Image Extension Settings',
            'Fileblock Image Extension',
            'manage_options',
            'fileblock_ie_settings',
            array($this, 'fileblock_ie_page')
        );
    }

    public function fileblock_ie_page() {
        ?>
        <div class="wrap">
            <h1>Fileblock Image Extension Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fileblock_ie_group');
                do_settings_sections('fileblock_ie_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function fileblock_ie_settings_init() {

        
        register_setting('fileblock_ie_group', 'fileblock_ie_token');
        register_setting('fileblock_ie_group', 'fileblock_ie_version');
        register_setting('fileblock_ie_group', 'fileblock_ie_question');

        add_settings_section(
            'fileblock_ie_section',
            'Replicate API Settings',
            array($this, 'fileblock_ie_section_callback'),
            'fileblock_ie_settings'
        );

        add_settings_field(
            'fileblock_ie_token',
            'API Token',
            array($this, 'fileblock_ie_token_callback'),
            'fileblock_ie_settings',
            'fileblock_ie_section'
        );

        add_settings_field(
            'fileblock_ie_version',
            'API Version',
            array($this, 'fileblock_ie_version_callback'),
            'fileblock_ie_settings',
            'fileblock_ie_section'
        );

        add_settings_field(
            'fileblock_ie_question',
            'Image recognition Question',
            array($this, 'fileblock_ie_question_callback'),
            'fileblock_ie_settings',
            'fileblock_ie_section'
        );

    }

    
    public function fileblock_ie_section_callback() {
        echo '';
    }

    public function fileblock_ie_token_callback() {
        $value = get_option('fileblock_ie_token');
        echo '<input type="text" name="fileblock_ie_token" value="' . esc_attr($value) . '" style="width: 400px;" />';
        echo '<br/><span></span>';
    }

    public function fileblock_ie_version_callback() {
        $value = get_option('fileblock_ie_version');
        echo '<input type="text" name="fileblock_ie_version" value="' . esc_attr($value) . '" style="width: 400px;" />';
        echo '<br/><span></span>';
    }

    public function fileblock_ie_question_callback() {
        $value = get_option('fileblock_ie_question');
        echo '<input type="text" name="fileblock_ie_question" value="' . esc_attr($value) . '" style="width: 400px;" />';
        echo '<br/><span></span>';
    }
    

}

new Fileblock_ie_settings();


