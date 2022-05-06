<?php

require_once(SUMMONER_TRACKER_PATH . 'functions/class.riot-connector.php');

if (!class_exists('Lol_Match_Table')) {
    class Lol_Match_Table
    {

        const TABLE_NAME = 'wp_lol_match_table';

        function __construct()
        {
            $this->summoner_create_match_db();
        }

        public function summoner_create_match_db()
        {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $wpdb->prefix . 'lol_match_table';

            $sql = "CREATE TABLE $table_name (
                `match_id` varchar(255),
                `summoner_name` varchar(255),
                `win-loss` BOOLEAN,
                `champion_id` varchar(255),
                `champion_name` varchar(255),
                `vision_score` INT,
                `match_type` varchar(255),
                `match_time` double,
                `unique_id` varchar(255) PRIMARY KEY
            );";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}

