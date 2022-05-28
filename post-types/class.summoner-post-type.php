<?php

include SUMMONER_TRACKER_PATH . '/functions/class.summoner-update-manager.php';

if (!class_exists('Summoner_Post_Type')) {
    class Summoner_Post_Type
    {
        function __construct()
        {
            add_action('init', array($this, 'create_post_type'));
            add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
            add_action('save_post', array($this, 'save_post'), 10, 2);
            add_filter('manage_summoner_posts_columns', array($this, 'summoner_cpt_columns'));
            add_action('manage_summoner_posts_custom_column', array($this, 'summoner_custom_columns'), 10, 2);
            add_action('shutdown', array($this, 'batch_summoner_updates'));
            //add_action('init', array($this, 'summoner_update_button'), 10, 2);
            add_action("wp_ajax_summoner_update_button", array($this, 'summoner_update_button'));
        }

        public static function summoner_list()
        {
            $args = array(
                'post_type' => 'summoner'
            );
            return get_posts($args);
        }

        public function create_post_type()
        {
            register_post_type(
                'summoner',
                array(
                    'label' => 'Summoner',
                    'description' => 'Summoners',
                    'labels' => array(
                        'name' => 'Summoners',
                        'singular_name' => 'Summoner'
                    ),
                    'public' => true,
                    'supports' => false,
                    'hierarchical' => false,
                    'show_ui' => true,
                    'show_in_menu' => 'summoner-tracker',
                    'menu_position' => 5,
                    'show_in_admin_bar' => true,
                    'show_in_nav_menus' => true,
                    'can_export' => true,
                    'has_archive' => true,
                    'exclude_from_search' => false,
                    'publicly_queryable' => true,
                    'menu_icon' => 'dashicons-images-alt2'
                )
            );
        }

        // runs a check on summoners, makes sure everything has been updated at least in the past hour
        public function batch_summoner_updates()
        {
            foreach($this->summoner_list() as $summoner_post){
                $summoner = new Summoner_Update_Manager($summoner_post);
                if($summoner->connector_status == "200"){
                    $summoner->conditional_summoner_update();
                }elseif($summoner->connector_status == "401" || $summoner->connector_status == "403"){
                    echo '<div class="summoner-stats-wrapper"><div class="no-summoner-match-data">API key rejected by riot. Please verify you are using a valid API key. <a>Click here for more info</a> </div></div>';
                    return;
                }else{
                    echo '<div class="summoner-stats-wrapper"><div class="no-summoner-match-data">There was an error updating user "' . get_post_meta($summoner_post, 'id_text'). $summoner->connector_status. '", make sure their username is still correct.</div></div>';
                }
            }
        }

        public function summoner_update_button()
        {
            if ( !wp_verify_nonce( $_REQUEST['nonce'], "summoner_update_nonce")) {
                exit("No naughty business please");
            }
            $this->summoner_data_refresh($_REQUEST["post_id"]);
        }

        public static function summoner_data_refresh($post_id)
        {
            $summoner = new Summoner_Update_Manager(get_post($post_id));
            $summoner->run_summoner_update();
        }

        public function summoner_cpt_columns($columns)
        {
            $columns['id_text'] = esc_html__('Summoner ID', 'summoner');
            $columns['region_select'] = esc_html__('Region', 'summoner');
            $columns['summoner_level'] = esc_html__('Summoner Level', 'summoner');

            return $columns;
        }

        //adds and escapes data to our custom columns
        public function summoner_custom_columns($column, $post_id)
        {
            switch ($column) {
                case 'id_text':
                    echo esc_html(get_post_meta($post_id, 'id_text', true));
                    break;
                case 'region_select':
                    echo esc_html(get_post_meta($post_id, 'region_select', true));
                    break;
                case 'summoner_level':
                    echo esc_html(get_post_meta($post_id, 'summoner_level', true));
                    break;
            }

            //refreshes the title of a summoner based on custom input (summoner ID)
            $post_update = array(
                'ID' => $post_id,
                'post_title' => get_post_meta($post_id, 'id_text', true)
            );
            wp_update_post($post_update);
        }

        //adds input to the summoner edit page
        public function add_meta_boxes()
        {
            add_meta_box(
                'summoner_meta_box',
                'Summoner Info',
                array($this, 'add_inner_meta_boxes'),
                'summoner',
                'normal',
                'high'
            );
        }

        //callback function for our metabox
        public function add_inner_meta_boxes($post)
        {
            require_once(SUMMONER_TRACKER_PATH . 'views/summoner_metabox.php');
        }

        //save function that handle user inpit
        public function save_post($post_id)
        {
            //verifies nonce subitted by form
            if (isset($_POST['summoner_nonce'])) {
                if (!wp_verify_nonce($_POST['summoner_nonce'], 'summoner_nonce')) {
                    return;
                }
            }
            //verifies no autosaving is happening before publishing
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            //verifies current user has the correct permissions to perform save
            if (isset($_POST['post_type']) && $_POST['post_type'] === 'summoner') {
                if (!current_user_can('edit_page', $post_id)) {
                    return;
                } elseif (!current_user_can('edit_post', $post_id)) {
                    return;
                }
            }

            //performs save of summoner id and dropdown region selection
            if (isset($_POST['action']) && $_POST['action'] == 'editpost') {
                $old_id_text = get_post_meta($post_id, 'id_text', true);
                $new_id_text = $_POST['id_text'];
                $old_region_select = get_post_meta($post_id, 'region_select', true);
                $new_region_select = $_POST['region_select'];

                //updates summoner id with sanitization/validation
                if (empty($new_id_text)) {
                    update_post_meta($post_id, 'id_text', 'Summoner ID');
                } else {
                    update_post_meta($post_id, 'id_text', sanitize_text_field($new_id_text), $old_id_text);
                }

                //updates region select, no validation needed
                update_post_meta($post_id, 'region_select', $new_region_select, $old_region_select);
                $this->summoner_data_refresh($post_id);

            }
        }

    }
}