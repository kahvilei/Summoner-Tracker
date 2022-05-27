<?php

if( ! class_exists( 'Summoner_Tracker_Admin' ) ) {
    class Summoner_Tracker_Admin
    {

        private $plugin_name = "summoner-tracker";

        function __construct()
        {
            add_action('admin_menu', array($this, 'add_plugin_admin_menu'), 9);
            add_action('admin_init', array($this, 'register_and_build_fields'));
        }

        public function add_plugin_admin_menu()
        {
            //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
            add_menu_page($this->plugin_name, 'Summoner Tracker', 'administrator', $this->plugin_name, null, 'dashicons-chart-area', 26);
            add_submenu_page($this->plugin_name, 'Settings', 'Settings', 'administrator', $this->plugin_name, array($this, 'display_plugin_admin_dashboard'));
        }

        public function display_plugin_admin_dashboard()
        {
            require_once SUMMONER_TRACKER_PATH . './views/' . $this->plugin_name . '-admin-display.php';
        }

        public function register_and_build_fields()
        {
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
                array($this, 'summoner_tracker_display_general_account'),
                // Page on which to add this section of options
                'summoner_tracker_general_settings'
            );
            unset($args);
            $args = array(
                'type' => 'input',
                'subtype' => 'text',
                'id' => 'summoner_tracker_api_key',
                'name' => 'summoner_tracker_api_key',
                'required' => 'true',
                'get_options_list' => '',
                'value_type' => 'normal',
                'wp_data' => 'option'
            );
            add_settings_field(
                'summoner_tracker_api_key',
                'API Key',
                array($this, 'summoner_tracker_render_settings_field'),
                'summoner_tracker_general_settings',
                'summoner_tracker_general_section',
                $args
            );


            register_setting(
                'summoner_tracker_general_settings',
                'summoner_tracker_api_key'
            );

        }

        public function summoner_tracker_display_general_account()
        {
            echo '<p>This plugin requires a riot API key to function. If you do not have an API key yet, <a target="_blank" href = "https://developer.riotgames.com/docs/portal#web-apis_api-keys">go here to apply for one</a>.</p><p><b>Note:</b> if you are using this plugin on a public-facing website, do not use a developer key.</p>';
        }

        public function summoner_tracker_render_settings_field($args)
        {
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
            if ($args['wp_data'] == 'option') {
                $wp_data_value = get_option($args['name']);
            } elseif ($args['wp_data'] == 'post_meta') {
                $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
            }

            switch ($args['type']) {

                case 'input':
                    $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
                    if ($args['subtype'] != 'checkbox') {
                        $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
                        $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                        $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
                        $min = (isset($args['min'])) ? 'min="' . $args['min'] . '"' : '';
                        $max = (isset($args['max'])) ? 'max="' . $args['max'] . '"' : '';
                        if (isset($args['disabled'])) {
                            // hide the actual input bc if it was just a disabled input the information saved in the database would be wrong - bc it would pass empty values and wipe the actual information
                            echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                        } else {
                            echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                        }
                        /*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/

                    } else {
                        $checked = ($value) ? 'checked' : '';
                        echo '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" name="' . $args['name'] . '" size="40" value="1" ' . $checked . ' />';
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }
    }
}