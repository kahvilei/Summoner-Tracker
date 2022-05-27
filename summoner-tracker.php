<?php

/**
 * Plugin Name: Summoner Tracker
 * Plugin URI: https://www.wordpress.org/summoner-tracker
 * Description: This tracker allows worpress site users to access and maintain data for specific summoners using their own API key
 * Version: 1.0
 * Requires at least: 5.6
 * Author: Kathryn Huff
 * Author URI: http://app.kathrynhuff.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: summoner-tracker
 * Domain Path: /languages
 */


if( ! defined( 'ABSPATH') ){
    exit;
}

if( ! class_exists( 'Summoner_Tracker' ) ){
    class Summoner_Tracker{

        private $plugin_name = "summoner-tracker";

        function __construct(){
            $this->define_constants();
            add_action('admin_menu', array( $this, 'add_plugin_admin_menu' ), 9);

            require_once( SUMMONER_TRACKER_PATH . 'post-types/class.summoner-post-type.php' );
            $Summoner_Post_Type = new Summoner_Post_Type();

            add_filter( 'archive_template', array( $this, 'load_custom_archive_template' ) );
            add_filter( 'single_template', array( $this, 'load_custom_single_template' ) );
            add_action('admin_init', array( $this, 'register_and_build_fields' ));

        }

        private function define_constants(){
            define( 'SUMMONER_TRACKER_PATH', plugin_dir_path( __FILE__ ) );
            define( 'SUMMONER_TRACKER_URL', plugin_dir_url( __FILE__ ) );
            define( 'SUMMONER_TRACKER_VERSION', '1.0.0' );
        }

        public function add_plugin_admin_menu(){
            //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
            add_menu_page(  $this->plugin_name, 'Summoner Tracker', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard'), 'dashicons-chart-area', 26 );

            //add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
            add_submenu_page( $this->plugin_name, 'Summoner Tracker Settings', 'Settings', 'administrator', $this->plugin_name.'-settings', array( $this, 'display_plugin_admin_settings'));
        }

        public function display_plugin_admin_dashboard() {
            require_once SUMMONER_TRACKER_PATH . 'views/'.$this->plugin_name.'-admin-display.php';
        }

        public function display_plugin_admin_settings() {
            // set this var to be used in the settings-display view
            $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
            if(isset($_GET['error_message'])){
                add_action('admin_notices', array($this, 'plugin_name_settings_messages'));
                do_action( 'admin_notices', $_GET['error_message'] );
            }
            require_once 'partials/'.$this->plugin_name.'-admin-settings-display.php';
        }

        public function plugin_name_settings_messages($error_message){
            switch ($error_message) {
                case '1':
                    $message = __( 'There was an error adding this setting. Please try again.  If this persists, shoot me an email.', 'kat@kathrynhuff.com' );
                    $err_code = esc_attr( 'plugin_name_example_setting' );
                    $setting_field = 'plugin_name_example_setting';
                    break;
            }
            $type = 'error';
            add_settings_error(
                $setting_field,
                $err_code,
                $message,
                $type
            );
        }

        public function register_and_build_fields() {
            /**
             * First, we add_settings_section. This is necessary since all future settings must belong to one.
             * Second, add_settings_field
             * Third, register_setting
             */
            add_settings_section(
            // ID used to identify this section and with which to register options
                'summoner_tracker_general_section',
                // Title to be displayed on the administration page
                '',
                // Callback used to render the description of the section
                array( $this, 'summoner_tracker_display_general_account' ),
                // Page on which to add this section of options
                'summoner_tracker_general_settings'
            );
            unset($args);
            $args = array (
                'type'      => 'input',
                'subtype'   => 'text',
                'id'    => 'summoner_tracker_api_key',
                'name'      => 'summoner_tracker_api_key',
                'required' => 'true',
                'get_options_list' => '',
                'value_type'=>'normal',
                'wp_data' => 'option'
            );
            add_settings_field(
                'summoner_tracker_api_key',
                'API Key',
                array( $this, 'summoner_tracker_render_settings_field' ),
                'summoner_tracker_general_settings',
                'summoner_tracker_general_section',
                $args
            );


            register_setting(
                'summoner_tracker_general_settings',
                'summoner_tracker_api_key'
            );

        }

        public function summoner_tracker_display_general_account() {
            echo '<p>This plugin requires a riot API key to function. If you do not have an API key yet, <a target="_blank" href = "https://developer.riotgames.com/docs/portal#web-apis_api-keys">go here to apply for one</a>.</p><p><b>Note:</b> if you are using this plugin on a public-facing website, do not use a developer key.</p>';
        }

        public function summoner_tracker_render_settings_field($args) {
            /* EXAMPLE INPUT
                      'type'      => 'input',
                      'subtype'   => '',
                      'id'    => $this->plugin_name.'_example_setting',
                      'name'      => $this->plugin_name.'_example_setting',
                      'required' => 'required="required"',
                      'get_option_list' => "",
                        'value_type' = serialized OR normal,
            'wp_data'=>(option or post_meta),
            'post_id' =>
            */
            if($args['wp_data'] == 'option'){
                $wp_data_value = get_option($args['name']);
            } elseif($args['wp_data'] == 'post_meta'){
                $wp_data_value = get_post_meta($args['post_id'], $args['name'], true );
            }

            switch ($args['type']) {

                case 'input':
                    $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
                    if($args['subtype'] != 'checkbox'){
                        $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
                        $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                        $step = (isset($args['step'])) ? 'step="'.$args['step'].'"' : '';
                        $min = (isset($args['min'])) ? 'min="'.$args['min'].'"' : '';
                        $max = (isset($args['max'])) ? 'max="'.$args['max'].'"' : '';
                        if(isset($args['disabled'])){
                            // hide the actual input bc if it was just a disabled input the information saved in the database would be wrong - bc it would pass empty values and wipe the actual information
                            echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'_disabled" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="'.$args['id'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
                        } else {
                            echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
                        }
                        /*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/

                    } else {
                        $checked = ($value) ? 'checked' : '';
                        echo '<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" name="'.$args['name'].'" size="40" value="1" '.$checked.' />';
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }

        public function load_custom_archive_template( $tpl ){
                if( is_post_type_archive( 'summoner' ) ){
                    $tpl = SUMMONER_TRACKER_PATH . 'views/templates/archive-summoner.php';
                }  
            return $tpl;
        }

        public function load_custom_single_template( $tpl ){
                if( is_singular( 'summoner' ) ){
                    $tpl = SUMMONER_TRACKER_PATH . 'views/templates/single-summoner.php';
                }
            return $tpl;
        }

        public static function activate(){
            update_option( 'rewrite_rules', '' );
            require_once( SUMMONER_TRACKER_PATH . 'functions/class.lol-match-table.php' );
            $Lol_Match_Table = new Lol_Match_Table();
        }

        public static function deactivate(){
            flush_rewrite_rules();
            unregister_post_type( 'summoner' );
        }

        public static function uninstall(){

        }

    }
}

if( class_exists( 'Summoner_Tracker' ) ){
    register_activation_hook( __FILE__, array( 'Summoner_Tracker', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'Summoner_Tracker', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'Summoner_Tracker', 'uninstall' ) );

    $Summoner_Tracker = new Summoner_Tracker();
} 