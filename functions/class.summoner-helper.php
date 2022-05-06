<?php
if (!class_exists('Summoner_Helper')) {
    class Summoner_Helper
    {
        private $post;
        private $gameCount = 100;
        private $gameMode = '';

        private $gameModeQuery = '';

        function __construct($post){
            $this->post = $post;
        }

        public function set_game_mode($gameMode){
            $this->gameMode = $gameMode;
            $this->set_game_mode_query($gameMode);
        }

        private function set_game_mode_query($gameMode){
            $gameModeQuery = 'AND `match_type` = ';
            if($gameMode == 'any' || $gameMode == 'all'){
                $gameModeQuery = '';
            }elseif($gameMode == 'aram'){
                $this->gameModeQuery = $gameModeQuery . "'ARAM'";
            }elseif($gameMode == 'classic'){
                $this->gameModeQuery = $gameModeQuery . "'CLASSIC'";
            }
        }

        public function set_game_count($gameCount){
            $this->gameCount = (int)$gameCount;
        }

        public function summoner_name(){
            return get_post_meta( $this->post->ID, 'id_text', true );
        }

        public function summoner_icon(){
            return get_post_meta( $this->post->ID, 'summoner_icon', true );
        }

        public function summoner_level(){
            return get_post_meta( $this->post->ID, 'summoner_level', true );
        }

        public function summoner_region(){
            return get_post_meta( $this->post->ID, 'region_select', true );
        }

        public function summoner_id(){
            return $this->post->ID;
        }

        public function time_since_last_edit(){
            $last_edit = (int)get_post_meta($this->post->ID, 'last_query_stamp', true);
            if(!is_null($last_edit)){
                return $last_edit - (int)current_time('timestamp');
            }
            return 100000000;
        }

        private function query_match_list($selection){
            global $wpdb;
            return $wpdb->get_col("SELECT `". $selection ."` 
                                         FROM `wp_lol_match_table` 
                                         WHERE `summoner_name` = '" . $this->summoner_name() ."' " .
                                         $this->gameModeQuery . " 
                                         ORDER BY `match_time` 
                                         DESC LIMIT " . $this->gameCount);
        }

        public function win_rate(){
            $win_loss_list = $this->query_match_list('win-loss');
            $win_percentage = round(100 * array_sum($win_loss_list)/count($win_loss_list), 2);

            return $win_percentage;
        }

        public function win_rate_color(){
            $win_percentage = $this->win_rate();
            $win_rate_color = '#000';
            if($win_percentage > 50){$win_rate_color = '#00d084';};
            if($win_percentage < 50){$win_rate_color = '#ff6900';};

            return $win_rate_color;
        }

        public function get_champ_id($champ_name){
            global $wpdb;
            return $wpdb->get_var("SELECT `champion_id` 
                                         FROM `wp_lol_match_table` 
                                         WHERE `summoner_name` = '" . $this->summoner_name() ."' 
                                         AND `champion_name` = '" . $champ_name ."' 
                                         ");
        }

        public function favorite_champ_id(){
            $champ_ID_list = $this->query_match_list('champion_id');

            $values = array_count_values($champ_ID_list);
            $champ_ID_mode = array_search(max($values), $values);

            return $champ_ID_mode;
        }

        public function favorite_champ(){
            $champ_list = $this->query_match_list('champion_name');

            $values = array_count_values($champ_list);
            $champ_mode = array_search(max($values), $values);

            return $champ_mode;
        }

        public function favorite_champ_list(){
            $champ_list = $this->query_match_list('champion_name');

            $champs = array_count_values($champ_list);
            $top_played = max($champs);

            $favorite_champs = [];
            $list_counter = 0;
            $reverse_list_counter = $top_played;
            for($n=1;$n<=$top_played;$n++){
                if(array_search($reverse_list_counter, $champs) && $list_counter <= 5){
                     foreach(array_keys($champs, $reverse_list_counter) as $key){
                         array_push($favorite_champs, $key);
                         $list_counter++;
                         if($list_counter == 5){
                             return $favorite_champs;
                         }
                     }
                }
                if($list_counter == 5){
                    return $favorite_champs;
                }
                $reverse_list_counter--;
            }
            return $favorite_champs;
        }

        public function last_game_time(){
            global $wpdb;
            return $wpdb->get_var("SELECT `match_time`, `unique_id` FROM `wp_lol_match_table` WHERE `summoner_name` = '" . $this->summoner_name() ."' ORDER BY `match_time` DESC LIMIT 1 ");
        }
    }
}