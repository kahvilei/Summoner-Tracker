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

        function __construct(){
            $this->define_constants();
            add_action('_admin_menu', array( $this, 'add_plugin_admin_menu' ), 9);

            require_once( SUMMONER_TRACKER_PATH . 'post-types/class.summoner-post-type.php' );
            $Summoner_Post_Type = new Summoner_Post_Type();

            add_filter( 'archive_template', array( $this, 'load_custom_archive_template' ) );
            add_filter( 'single_template', array( $this, 'load_custom_single_template' ) );
            add_action('init', array( $this, 'set_up_styles' ),100);
            add_action('init', array( $this, 'set_up_scripts' ),100);

        }

        private function define_constants(){
            define( 'SUMMONER_TRACKER_PATH', plugin_dir_path( __FILE__ ) );
            define( 'SUMMONER_TRACKER_URL', plugin_dir_url( __FILE__ ) );
            define( 'SUMMONER_TRACKER_VERSION', '1.0.0' );
        }

        public function add_plugin_admin_menu(){
            require_once( SUMMONER_TRACKER_PATH . 'functions/class.summoner-tracker-admin.php' );
            $Summoner_Tracker_Admin = new Summoner_Tracker_Admin();
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

        public function set_up_styles() {
            wp_register_style( 'summonertrackermainstyles', SUMMONER_TRACKER_URL . 'assets/css/summoner-main.css' );
            wp_enqueue_style( 'summonertrackermainstyles' );
        }

        public function set_up_scripts() {
            wp_register_script( 'summonertrackermanualupdate', SUMMONER_TRACKER_URL . 'assets/js/summoner-manual-update.js' );
            wp_enqueue_script("jquery");
            wp_enqueue_script( 'summonertrackermanualupdate', array('jquery') );
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